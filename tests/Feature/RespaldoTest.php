<?php

namespace Tests\Feature;

use App\Models\Usuario;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class RespaldoTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('tiendas', function (Blueprint $table) {
            $table->increments('id_tienda');
            $table->string('nombre');
        });
        Schema::create('usuarios', function (Blueprint $table) {
            $table->increments('id_usuario');
            $table->string('nombre');
            $table->string('correo');
            $table->string('contrasena');
            $table->string('rol');
            $table->unsignedInteger('id_tienda')->nullable();
            $table->boolean('activo')->default(true);
        });
        Schema::create('logs_respaldo', function (Blueprint $table) {
            $table->increments('id_log');
            $table->unsignedInteger('id_usuario');
            $table->dateTime('fecha_generacion')->useCurrent();
            $table->string('nombre_archivo');
            $table->unsignedBigInteger('tamano_bytes')->default(0);
            $table->string('tipo_operacion', 50)->default('exportacion');
            $table->boolean('exito')->default(true);
            $table->text('notas')->nullable();
        });
        \Illuminate\Support\Facades\DB::table('tiendas')->insert(['nombre' => 'Sucursal de prueba']);
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('logs_respaldo');
        Schema::dropIfExists('usuarios');
        Schema::dropIfExists('tiendas');

        parent::tearDown();
    }

    public function test_admin_can_view_the_backup_history(): void
    {
        $admin = $this->createUser('admin');

        $this->actingAs($admin)
            ->get(route('respaldos'))
            ->assertOk()
            ->assertSee('Exportar respaldo')
            ->assertSee('No hay respaldos registrados');
    }

    public function test_only_superadmins_can_submit_database_restores(): void
    {
        $admin = $this->createUser('admin');

        $this->actingAs($admin)
            ->post(route('respaldos.importar'))
            ->assertForbidden();
    }

    public function test_restore_requires_the_exact_confirmation_phrase(): void
    {
        $superadmin = $this->createUser('superadmin');

        $this->actingAs($superadmin)
            ->post(route('respaldos.importar'), [
                'confirmacion' => 'Sí, restaurar',
            ])
            ->assertSessionHasErrors(['archivo', 'confirmacion']);
    }

    private function createUser(string $role): Usuario
    {
        return Usuario::query()->create([
            'nombre' => $role,
            'correo' => $role.'@example.test',
            'contrasena' => 'hash',
            'rol' => $role,
            'id_tienda' => 1,
            'activo' => true,
        ]);
    }
}
