<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const PRIMARY_STORE_ID = 1;

    private const STORE_TABLES = [
        'alertas_stock',
        'anaqueles',
        'clientes',
        'compras',
        'gastos',
        'productos',
        'usuarios',
        'ventas',
    ];

    public function up(): void
    {
        if (! Schema::hasTable('tiendas') || ! DB::table('tiendas')->where('id_tienda', self::PRIMARY_STORE_ID)->exists()) {
            throw new RuntimeException('No se encontró la sucursal principal con id 1; no se modificaron los datos.');
        }

        DB::transaction(function () {
            foreach (self::STORE_TABLES as $table) {
                if (Schema::hasTable($table) && Schema::hasColumn($table, 'id_tienda')) {
                    DB::table($table)
                        ->where('id_tienda', '!=', self::PRIMARY_STORE_ID)
                        ->update(['id_tienda' => self::PRIMARY_STORE_ID]);
                }
            }

            DB::table('tiendas')
                ->where('id_tienda', '!=', self::PRIMARY_STORE_ID)
                ->delete();
        });
    }

    public function down(): void
    {
        throw new RuntimeException('La consolidación de sucursales es irreversible; restaura un respaldo previo para revertirla.');
    }
};
