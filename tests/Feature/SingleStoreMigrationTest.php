<?php

namespace Tests\Feature;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class SingleStoreMigrationTest extends TestCase
{
    private const LINKED_TABLES = [
        'alertas_stock',
        'anaqueles',
        'clientes',
        'compras',
        'gastos',
        'productos',
        'usuarios',
        'ventas',
    ];

    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('tiendas', function (Blueprint $table) {
            $table->increments('id_tienda');
            $table->string('nombre');
        });

        foreach (self::LINKED_TABLES as $name) {
            Schema::create($name, function (Blueprint $table) {
                $table->increments('id');
                $table->unsignedInteger('id_tienda');
            });
        }

        DB::table('tiendas')->insert([
            ['id_tienda' => 1, 'nombre' => 'Sucursal única'],
            ['id_tienda' => 2, 'nombre' => 'Sucursal secundaria'],
        ]);
        foreach (self::LINKED_TABLES as $name) {
            DB::table($name)->insert([
                ['id' => 1, 'id_tienda' => 1],
                ['id' => 2, 'id_tienda' => 2],
            ]);
        }
    }

    protected function tearDown(): void
    {
        foreach (self::LINKED_TABLES as $name) {
            Schema::dropIfExists($name);
        }
        Schema::dropIfExists('tiendas');

        parent::tearDown();
    }

    public function test_migration_moves_linked_records_and_removes_secondary_stores(): void
    {
        $migration = require database_path('migrations/2026_10_28_000000_consolidate_to_single_store.php');
        $migration->up();

        $this->assertSame(1, DB::table('tiendas')->count());
        $this->assertDatabaseHas('tiendas', ['id_tienda' => 1, 'nombre' => 'Sucursal única']);
        $this->assertDatabaseMissing('tiendas', ['id_tienda' => 2]);

        foreach (self::LINKED_TABLES as $table) {
            $this->assertSame(2, DB::table($table)->where('id_tienda', 1)->count(), "{$table} records were not moved.");
            $this->assertSame(0, DB::table($table)->where('id_tienda', '!=', 1)->count(), "{$table} still references a secondary store.");
        }
    }

    public function test_store_name_no_longer_includes_a_branch_number(): void
    {
        $migration = require database_path('migrations/2026_10_28_000001_rename_single_store.php');
        $migration->up();

        $this->assertDatabaseHas('tiendas', ['id_tienda' => 1, 'nombre' => 'HLazcano']);
    }
}
