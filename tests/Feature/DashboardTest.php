<?php

namespace Tests\Feature;

use App\Models\Usuario;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('tiendas', function (Blueprint $table) {
            $table->increments('id_tienda');
            $table->string('nombre');
            $table->string('notas')->nullable();
        });
        Schema::create('usuarios', function (Blueprint $table) {
            $table->increments('id_usuario');
            $table->string('nombre');
            $table->string('correo');
            $table->string('contrasena');
            $table->string('rol');
            $table->unsignedInteger('id_tienda');
            $table->boolean('activo')->default(true);
        });
        Schema::create('clientes', function (Blueprint $table) {
            $table->increments('id_cliente');
            $table->string('nombre');
            $table->string('notas')->nullable();
            $table->boolean('activo')->default(true);
            $table->unsignedInteger('id_tienda');
        });
        Schema::create('ventas', function (Blueprint $table) {
            $table->increments('id_venta');
            $table->string('folio');
            $table->date('fecha');
            $table->decimal('total', 12, 2);
            $table->string('notas')->nullable();
            $table->unsignedInteger('id_tienda');
            $table->unsignedInteger('id_cliente')->nullable();
        });
        Schema::create('productos', function (Blueprint $table) {
            $table->increments('id_producto');
            $table->boolean('activo')->default(true);
            $table->unsignedInteger('id_tienda');
            $table->decimal('stock_actual', 10, 2)->default(0);
            $table->decimal('stock_minimo', 10, 2)->default(0);
        });

        DB::table('tiendas')->insert(['id_tienda' => 1, 'nombre' => 'Sucursal', 'notas' => 'Nota de tienda']);
        DB::table('clientes')->insert([
            'id_cliente' => 1,
            'nombre' => 'Cliente',
            'notas' => 'Nota de cliente',
            'activo' => true,
            'id_tienda' => 1,
        ]);
        DB::table('ventas')->insert([
            [
                'folio' => 'V-001',
                'fecha' => now()->toDateString(),
                'total' => 100,
                'notas' => null,
                'id_tienda' => 1,
                'id_cliente' => 1,
            ],
            [
                'folio' => 'V-002',
                'fecha' => now()->toDateString(),
                'total' => 50,
                'notas' => 'CANCELADA: Prueba',
                'id_tienda' => 1,
                'id_cliente' => 1,
            ],
        ]);
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('productos');
        Schema::dropIfExists('ventas');
        Schema::dropIfExists('clientes');
        Schema::dropIfExists('usuarios');
        Schema::dropIfExists('tiendas');

        parent::tearDown();
    }

    public function test_dashboard_lists_only_active_sales_without_ambiguous_notes_columns(): void
    {
        $user = Usuario::query()->create([
            'nombre' => 'Vendedor',
            'correo' => 'vendedor@example.test',
            'contrasena' => 'hash',
            'rol' => 'empleado',
            'id_tienda' => 1,
            'activo' => true,
        ]);

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('V-001')
            ->assertDontSee('V-002')
            ->assertSee('$100.00');
    }
}
