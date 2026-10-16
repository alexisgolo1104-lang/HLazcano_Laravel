<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use RuntimeException;
use ZipArchive;

class DatabaseBackupService
{
    private const FORMAT = 'hlazcano-database-backup';

    private const MAX_ARCHIVE_BYTES = 1073741824;

    public function export(): array
    {
        $this->ensureMysqlConnection();

        $directory = storage_path('app/private/backups');
        File::ensureDirectoryExists($directory);

        $id = (string) Str::uuid();
        $workDirectory = $directory.DIRECTORY_SEPARATOR.$id;
        File::ensureDirectoryExists($workDirectory);

        $filename = 'respaldo_hlazcano_'.now()->format('Y-m-d_H-i-s').'.zip';
        $archivePath = $directory.DIRECTORY_SEPARATOR.$id.'.zip';
        $zip = new ZipArchive();
        $opened = $zip->open($archivePath, ZipArchive::CREATE | ZipArchive::OVERWRITE);
        if ($opened !== true) {
            File::deleteDirectory($workDirectory);
            throw new RuntimeException('No fue posible crear el archivo ZIP del respaldo.');
        }

        $zipClosed = false;
        $archiveReady = false;
        try {
            $tables = $this->tableNames();
            $manifestTables = [];

            foreach ($tables as $table) {
                $this->assertSafeIdentifier($table);
                $schemaRow = DB::selectOne('SHOW CREATE TABLE `'.$table.'`');
                if (! $schemaRow) {
                    throw new RuntimeException("No fue posible leer el esquema de la tabla {$table}.");
                }
                $schema = array_values((array) $schemaRow)[1] ?? null;
                if (! is_string($schema) || $schema === '') {
                    throw new RuntimeException("El esquema de la tabla {$table} no es válido.");
                }

                $schemaPath = $workDirectory.DIRECTORY_SEPARATOR.$table.'.sql';
                $dataPath = $workDirectory.DIRECTORY_SEPARATOR.$table.'.jsonl';
                if (file_put_contents($schemaPath, $schema) === false) {
                    throw new RuntimeException("No fue posible escribir el esquema de {$table}.");
                }

                $rowCount = $this->writeTableData($table, $dataPath);
                $schemaEntry = 'schema/'.$table.'.sql';
                $dataEntry = 'data/'.$table.'.jsonl';
                if (! $zip->addFile($schemaPath, $schemaEntry) || ! $zip->addFile($dataPath, $dataEntry)) {
                    throw new RuntimeException("No fue posible agregar {$table} al archivo de respaldo.");
                }

                $manifestTables[] = [
                    'name' => $table,
                    'rows' => $rowCount,
                    'schema_sha256' => hash_file('sha256', $schemaPath),
                    'data_sha256' => hash_file('sha256', $dataPath),
                ];
            }

            if (! in_array('logs_respaldo', $tables, true)) {
                throw new RuntimeException('La tabla logs_respaldo no está disponible para registrar el respaldo.');
            }

            $manifest = [
                'format' => self::FORMAT,
                'version' => 1,
                'database' => config('database.connections.'.config('database.default').'.database'),
                'created_at' => now()->toIso8601String(),
                'tables' => $manifestTables,
            ];
            $manifestJson = json_encode($manifest, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            $document = json_encode([
                'manifest' => $manifest,
                'signature' => hash_hmac('sha256', $manifestJson, $this->signingKey()),
            ], JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

            if (! $zip->addFromString('manifest.json', $document) || ! $zip->close()) {
                throw new RuntimeException('No fue posible finalizar el archivo ZIP del respaldo.');
            }
            $zipClosed = true;
            $bytes = filesize($archivePath);
            if ($bytes === false) {
                throw new RuntimeException('No fue posible verificar el tamaño del respaldo generado.');
            }
            $archiveReady = true;

            return [
                'path' => $archivePath,
                'filename' => $filename,
                'bytes' => $bytes,
            ];
        } finally {
            if (! $zipClosed) {
                $zip->close();
            }
            if (! $archiveReady) {
                File::delete($archivePath);
            }
            File::deleteDirectory($workDirectory);
        }
    }

    public function restore(UploadedFile $file, int $requiredSuperadminId): array
    {
        $this->ensureMysqlConnection();

        $zip = new ZipArchive();
        if ($zip->open($file->getRealPath()) !== true) {
            throw new RuntimeException('El archivo no es un ZIP válido.');
        }

        try {
            $tables = $this->validateArchive($zip, $requiredSuperadminId);
            $currentTables = $this->tableNames();

            DB::statement('SET FOREIGN_KEY_CHECKS=0');
            try {
                foreach ($currentTables as $table) {
                    $this->assertSafeIdentifier($table);
                    DB::statement('DROP TABLE IF EXISTS `'.$table.'`');
                }

                foreach ($tables as $table) {
                    $schema = $zip->getFromName('schema/'.$table['name'].'.sql');
                    if (! is_string($schema) || $schema === '') {
                        throw new RuntimeException("Falta el esquema de la tabla {$table['name']}.");
                    }
                    DB::unprepared($schema);
                }

                foreach ($tables as $table) {
                    $this->insertTableData($zip, $table['name']);
                }

                if (in_array('sessions', array_column($tables, 'name'), true)) {
                    DB::table('sessions')->delete();
                }
            } finally {
                DB::statement('SET FOREIGN_KEY_CHECKS=1');
            }

            return [
                'tables' => count($tables),
                'database' => config('database.connections.'.config('database.default').'.database'),
            ];
        } finally {
            $zip->close();
        }
    }

    private function validateArchive(ZipArchive $zip, int $requiredSuperadminId): array
    {
        if ($zip->numFiles < 2 || $zip->numFiles > 1000) {
            throw new RuntimeException('El archivo de respaldo contiene una cantidad de archivos no válida.');
        }

        $manifestContents = $zip->getFromName('manifest.json');
        if (! is_string($manifestContents) || strlen($manifestContents) > 1048576) {
            throw new RuntimeException('El archivo de respaldo no contiene un manifiesto válido.');
        }

        $document = json_decode($manifestContents, true, 512, JSON_THROW_ON_ERROR);
        if (! is_array($document)) {
            throw new RuntimeException('La estructura del manifiesto del respaldo no es válida.');
        }
        $manifest = $document['manifest'] ?? null;
        $signature = $document['signature'] ?? null;
        if (! is_array($manifest) || ! is_string($signature)) {
            throw new RuntimeException('La estructura del manifiesto del respaldo no es válida.');
        }

        $manifestJson = json_encode($manifest, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $expectedSignature = hash_hmac('sha256', $manifestJson, $this->signingKey());
        if (! hash_equals($expectedSignature, $signature)) {
            throw new RuntimeException('La firma del respaldo no es válida o el archivo fue modificado.');
        }

        $database = config('database.connections.'.config('database.default').'.database');
        if (($manifest['format'] ?? null) !== self::FORMAT
            || ($manifest['version'] ?? null) !== 1
            || ($manifest['database'] ?? null) !== $database
            || ! is_array($manifest['tables'] ?? null)) {
            throw new RuntimeException('El respaldo no corresponde a esta aplicación o base de datos.');
        }

        $tables = $manifest['tables'];
        foreach ($tables as $table) {
            if (! is_array($table)) {
                throw new RuntimeException('La lista de tablas del respaldo no es válida.');
            }
        }
        $names = array_column($tables, 'name');
        if (count($names) > 300 || ! in_array('logs_respaldo', $names, true) || count($names) !== count(array_unique($names))) {
            throw new RuntimeException('La lista de tablas del respaldo no es válida.');
        }
        if (! in_array('usuarios', $names, true)) {
            throw new RuntimeException('El respaldo debe contener la tabla de usuarios.');
        }

        $expectedEntries = ['manifest.json'];
        $uncompressedBytes = strlen($manifestContents);
        foreach ($tables as $table) {
            $name = $table['name'] ?? null;
            if (! is_string($name)) {
                throw new RuntimeException('El respaldo contiene un nombre de tabla no válido.');
            }
            $this->assertSafeIdentifier($name);

            $schemaEntry = 'schema/'.$name.'.sql';
            $dataEntry = 'data/'.$name.'.jsonl';
            $expectedEntries[] = $schemaEntry;
            $expectedEntries[] = $dataEntry;
            $schemaStat = $zip->statName($schemaEntry);
            $dataStat = $zip->statName($dataEntry);
            $uncompressedBytes += (int) ($schemaStat['size'] ?? 0) + (int) ($dataStat['size'] ?? 0);
            if ((int) ($schemaStat['size'] ?? 0) > 5242880 || $uncompressedBytes > self::MAX_ARCHIVE_BYTES) {
                throw new RuntimeException('El respaldo supera el tamaño máximo permitido.');
            }

            $schema = $zip->getFromName($schemaEntry);
            if (! is_string($schema) || strlen($schema) > 5242880
                || ! preg_match('/\A[a-f0-9]{64}\z/', (string) ($table['schema_sha256'] ?? ''))
                || ! hash_equals((string) ($table['schema_sha256'] ?? ''), hash('sha256', $schema))
                || ! preg_match('/^\s*CREATE TABLE\s+`?'.preg_quote($name, '/').'`?\s+/i', $schema)) {
                throw new RuntimeException("El esquema de la tabla {$name} no es válido.");
            }

            if (! preg_match('/\A[a-f0-9]{64}\z/', (string) ($table['data_sha256'] ?? ''))) {
                throw new RuntimeException("La firma de los datos de {$name} no es válida.");
            }
            $rowCount = $this->validateTableData($zip, $dataEntry, (string) ($table['data_sha256'] ?? ''));
            if ($rowCount !== ($table['rows'] ?? null)) {
                throw new RuntimeException("Los datos de la tabla {$name} no coinciden con el manifiesto.");
            }
        }

        if (! $this->archiveContainsSuperadmin($zip, 'data/usuarios.jsonl', $requiredSuperadminId)) {
            throw new RuntimeException('El respaldo debe conservar la cuenta de superadministrador que inició la restauración.');
        }

        $actualEntries = [];
        for ($index = 0; $index < $zip->numFiles; $index++) {
            $entry = $zip->getNameIndex($index);
            if (! is_string($entry)) {
                throw new RuntimeException('El ZIP contiene una entrada no válida.');
            }
            $actualEntries[] = $entry;
        }
        sort($expectedEntries);
        sort($actualEntries);
        if ($actualEntries !== $expectedEntries) {
            throw new RuntimeException('El ZIP contiene archivos no esperados.');
        }

        return $tables;
    }

    private function validateTableData(ZipArchive $zip, string $entry, string $expectedHash): int
    {
        $stream = $zip->getStream($entry);
        if (! is_resource($stream)) {
            throw new RuntimeException("No fue posible leer el archivo de datos {$entry}.");
        }

        $hash = hash_init('sha256');
        $count = 0;
        try {
            while (($line = fgets($stream)) !== false) {
                hash_update($hash, $line);
                $row = json_decode($line, false, 512, JSON_THROW_ON_ERROR);
                if (! is_object($row)) {
                    throw new RuntimeException("El archivo de datos {$entry} contiene una fila no válida.");
                }
                foreach ($row as $column => $value) {
                    $this->assertSafeIdentifier((string) $column);
                    if (! is_null($value) && ! is_scalar($value)) {
                        throw new RuntimeException("El archivo de datos {$entry} contiene un valor no válido.");
                    }
                }
                $count++;
            }
        } finally {
            fclose($stream);
        }

        if (! hash_equals($expectedHash, hash_final($hash))) {
            throw new RuntimeException("La integridad de {$entry} no pudo verificarse.");
        }

        return $count;
    }

    private function insertTableData(ZipArchive $zip, string $table): void
    {
        $entry = 'data/'.$table.'.jsonl';
        $stream = $zip->getStream($entry);
        if (! is_resource($stream)) {
            throw new RuntimeException("No fue posible leer los datos de la tabla {$table}.");
        }

        $batch = [];
        try {
            while (($line = fgets($stream)) !== false) {
                $batch[] = (array) json_decode($line, true, 512, JSON_THROW_ON_ERROR);
                if (count($batch) >= 250) {
                    DB::table($table)->insert($batch);
                    $batch = [];
                }
            }
            if ($batch !== []) {
                DB::table($table)->insert($batch);
            }
        } finally {
            fclose($stream);
        }
    }

    private function archiveContainsSuperadmin(ZipArchive $zip, string $entry, int $userId): bool
    {
        $stream = $zip->getStream($entry);
        if (! is_resource($stream)) {
            return false;
        }

        try {
            while (($line = fgets($stream)) !== false) {
                $user = json_decode($line, false, 512, JSON_THROW_ON_ERROR);
                if (is_object($user)
                    && (int) ($user->id_usuario ?? 0) === $userId
                    && ($user->rol ?? null) === 'superadmin'
                    && (bool) ($user->activo ?? false)) {
                    return true;
                }
            }
        } finally {
            fclose($stream);
        }

        return false;
    }

    private function writeTableData(string $table, string $path): int
    {
        $handle = fopen($path, 'wb');
        if ($handle === false) {
            throw new RuntimeException("No fue posible crear el archivo de datos de {$table}.");
        }

        $count = 0;
        try {
            foreach (DB::table($table)->cursor() as $row) {
                $json = json_encode((array) $row, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                if (fwrite($handle, $json."\n") === false) {
                    throw new RuntimeException("No fue posible escribir los datos de {$table}.");
                }
                $count++;
            }
        } finally {
            fclose($handle);
        }

        return $count;
    }

    private function tableNames(): array
    {
        $tables = [];
        foreach (DB::select('SHOW FULL TABLES') as $row) {
            $values = array_values((array) $row);
            if (($values[1] ?? null) === 'BASE TABLE') {
                $tables[] = (string) $values[0];
            }
        }

        return $tables;
    }

    private function assertSafeIdentifier(string $identifier): void
    {
        if (! preg_match('/\A[A-Za-z0-9_]+\z/', $identifier)) {
            throw new RuntimeException('El respaldo contiene un identificador no permitido.');
        }
    }

    private function ensureMysqlConnection(): void
    {
        if (! in_array(DB::connection()->getDriverName(), ['mysql', 'mariadb'], true)) {
            throw new RuntimeException('Los respaldos completos requieren una conexión MySQL o MariaDB.');
        }
    }

    private function signingKey(): string
    {
        $key = config('app.key');
        if (! is_string($key) || $key === '') {
            throw new RuntimeException('La clave APP_KEY no está configurada para firmar respaldos.');
        }

        return $key;
    }
}
