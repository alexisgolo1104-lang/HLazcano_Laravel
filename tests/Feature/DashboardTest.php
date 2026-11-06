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
            $table->string('tipo_cliente')->nullable();
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
        Schema::create('catalogo_productos', function (Blueprint $table) {
            $table->increments('id_catalogo');
            $table->string('nombre');
            $table->boolean('activo')->default(true);
        });
        Schema::create('unidades', function (Blueprint $table) {
            $table->increments('id_unidad');
            $table->string('codigo');
        });
        Schema::create('productos', function (Blueprint $table) {
            $table->increments('id_producto');
            $table->unsignedInteger('id_catalogo');
            $table->unsignedInteger('id_unidad');
            $table->string('presentacion')->nullable();
            $table->boolean('activo')->default(true);
            $table->unsignedInteger('id_tienda');
            $table->decimal('stock_actual', 10, 2)->default(0);
            $table->decimal('stock_minimo', 10, 2)->default(0);
        });

        DB::table('tiendas')->insert(['id_tienda' => 1, 'nombre' => 'Sucursal 1 — Santa María Texmelucan', 'notas' => 'Nota de tienda']);
        DB::table('clientes')->insert([
            'id_cliente' => 1,
            'nombre' => 'Cliente',
            'tipo_cliente' => 'Comprador individual',
            'notas' => 'Nota de cliente',
            'activo' => true,
            'id_tienda' => 1,
        ]);
        DB::table('clientes')->insert([
            'id_cliente' => 2,
            'nombre' => 'Taller',
            'tipo_cliente' => 'Taller / Negocio',
            'activo' => true,
            'id_tienda' => 1,
        ]);
        DB::table('catalogo_productos')->insert(['id_catalogo' => 1, 'nombre' => 'Hilo de prueba']);
        DB::table('unidades')->insert(['id_unidad' => 1, 'codigo' => 'kg']);
        DB::table('productos')->insert([
            'id_producto' => 1,
            'id_catalogo' => 1,
            'id_unidad' => 1,
            'presentacion' => '500g',
            'activo' => true,
            'id_tienda' => 1,
            'stock_actual' => 2,
            'stock_minimo' => 5,
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
        Schema::dropIfExists('unidades');
        Schema::dropIfExists('catalogo_productos');
        Schema::dropIfExists('ventas');
        Schema::dropIfExists('clientes');
        Schema::dropIfExists('usuarios');
        Schema::dropIfExists('tiendas');

        parent::tearDown();
    }

    public function test_dashboard_charts_use_active_sales_and_current_store_data(): void
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
            ->assertSee('Ventas por día')
            ->assertSee('Hilo de prueba')
            ->assertSee('Comprador individual')
            ->assertSee('Taller / Negocio')
            ->assertSee('1 venta este mes')
            ->assertDontSee('Sucursal 1')
            ->assertSee('$100.00')
            ->assertDontSee('$150.00');
    }
}
