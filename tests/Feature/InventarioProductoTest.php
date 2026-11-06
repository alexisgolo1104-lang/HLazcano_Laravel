<?php

namespace Tests\Feature;

use App\Models\Usuario;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class InventarioProductoTest extends TestCase
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
            $table->unsignedInteger('id_tienda');
            $table->boolean('activo')->default(true);
            $table->boolean('debe_cambiar_contrasena')->default(false);
        });
        Schema::create('tipos_hilo', function (Blueprint $table) {
            $table->increments('id_tipo');
            $table->string('nombre');
            $table->boolean('activo')->default(true);
        });
        Schema::create('colores', function (Blueprint $table) {
            $table->increments('id_color');
            $table->string('nombre');
            $table->string('codigo_hex');
            $table->boolean('activo')->default(true);
        });
        Schema::create('unidades', function (Blueprint $table) {
            $table->increments('id_unidad');
            $table->string('nombre');
            $table->string('codigo');
        });
        Schema::create('catalogo_productos', function (Blueprint $table) {
            $table->increments('id_catalogo');
            $table->string('nombre');
            $table->unsignedInteger('id_tipo');
            $table->unsignedInteger('id_color');
            $table->unsignedInteger('id_unidad');
            $table->string('presentacion')->nullable();
            $table->boolean('activo')->default(true);
        });
        Schema::create('anaqueles', function (Blueprint $table) {
            $table->increments('id_anaquel');
            $table->unsignedInteger('id_tienda');
            $table->string('codigo');
            $table->string('descripcion')->nullable();
            $table->boolean('activo')->default(true);
        });
        Schema::create('productos', function (Blueprint $table) {
            $table->increments('id_producto');
            $table->unsignedInteger('id_catalogo');
            $table->unsignedInteger('id_tipo');
            $table->unsignedInteger('id_color');
            $table->unsignedInteger('id_unidad');
            $table->unsignedInteger('id_proveedor')->nullable();
            $table->unsignedInteger('id_anaquel')->nullable();
            $table->unsignedInteger('id_tienda');
            $table->string('presentacion')->nullable();
            $table->string('posicion_anaquel')->nullable();
            $table->decimal('stock_actual', 10, 2)->default(0);
            $table->decimal('stock_minimo', 10, 2)->default(0);
            $table->decimal('precio_compra', 10, 2)->nullable();
            $table->decimal('precio_venta', 10, 2)->nullable();
            $table->boolean('activo')->default(true);
            $table->date('creado_en');
            $table->date('actualizado_en')->nullable();
        });

        DB::table('tiendas')->insert(['id_tienda' => 1, 'nombre' => 'HLazcano']);
        DB::table('tipos_hilo')->insert(['id_tipo' => 1, 'nombre' => 'Acrílico']);
        DB::table('colores')->insert(['id_color' => 1, 'nombre' => 'Blanco', 'codigo_hex' => '#FFFFFF']);
        DB::table('unidades')->insert(['id_unidad' => 1, 'nombre' => 'Kilogramo', 'codigo' => 'kg']);
        DB::table('catalogo_productos')->insert([
            'id_catalogo' => 1,
            'nombre' => 'Hilo acrílico blanco',
            'id_tipo' => 1,
            'id_color' => 1,
            'id_unidad' => 1,
            'presentacion' => '500g',
        ]);
        DB::table('anaqueles')->insert([
            'id_anaquel' => 1,
            'id_tienda' => 1,
            'codigo' => 'A1',
            'descripcion' => 'Anaquel de prueba',
        ]);
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('productos');
        Schema::dropIfExists('anaqueles');
        Schema::dropIfExists('catalogo_productos');
        Schema::dropIfExists('unidades');
        Schema::dropIfExists('colores');
        Schema::dropIfExists('tipos_hilo');
        Schema::dropIfExists('usuarios');
        Schema::dropIfExists('tiendas');

        parent::tearDown();
    }

    public function test_new_product_form_hides_provider_and_locks_catalog_fields(): void
    {
        $this->actingAs($this->admin())
            ->get(route('inventario.create'))
            ->assertOk()
            ->assertSee('Tipo de hilo')
            ->assertSee('Stock inicial')
            ->assertDontSee('Presentación')
            ->assertDontSee('id="presentacion"', false)
            ->assertSee('id="id_tipo" value="" placeholder="Se asigna del catálogo" disabled', false)
            ->assertSee('id="id_color" value="" placeholder="Se asigna del catálogo" disabled', false)
            ->assertSee('id="stock_actual" type="number" value="0.00" disabled', false)
            ->assertSee('id="id_unidad" value="" placeholder="Se asigna del catálogo" disabled', false)
            ->assertSee('id="precio_compra" type="number" value="0.00" disabled', false)
            ->assertDontSee('name="id_proveedor"', false)
            ->assertDontSee('Tienda destino');
    }

    public function test_new_product_uses_catalog_values_and_ignores_locked_field_tampering(): void
    {
        $this->actingAs($this->admin())
            ->post(route('inventario.store'), [
                'id_catalogo' => 1,
                'id_tipo' => 999,
                'id_color' => 999,
                'id_unidad' => 999,
                'presentacion' => 'Alterada',
                'stock_actual' => 99,
                'stock_minimo' => 5,
                'precio_compra' => 999,
                'precio_venta' => 65,
                'id_proveedor' => 99,
            ])
            ->assertRedirect(route('inventario'));

        $this->assertDatabaseHas('productos', [
            'id_catalogo' => 1,
            'id_tipo' => 1,
            'id_color' => 1,
            'id_unidad' => 1,
            'presentacion' => '500g',
            'stock_actual' => 0,
            'stock_minimo' => 5,
            'precio_compra' => 0,
            'precio_venta' => 65,
            'id_proveedor' => null,
            'id_tienda' => 1,
        ]);
    }

    public function test_edit_form_locks_the_same_fields_and_update_preserves_them(): void
    {
        $productoId = DB::table('productos')->insertGetId([
            'id_catalogo' => 1,
            'id_tipo' => 1,
            'id_color' => 1,
            'id_unidad' => 1,
            'id_proveedor' => 7,
            'id_anaquel' => 1,
            'id_tienda' => 1,
            'presentacion' => '500g',
            'posicion_anaquel' => 'Fila 1, Columna 1',
            'stock_actual' => 12,
            'stock_minimo' => 5,
            'precio_compra' => 25,
            'precio_venta' => 45,
            'activo' => true,
            'creado_en' => now()->toDateString(),
        ]);
        DB::table('productos')->insert([
            'id_catalogo' => 1,
            'id_tipo' => 1,
            'id_color' => 1,
            'id_unidad' => 1,
            'id_tienda' => 1,
            'stock_actual' => 2,
            'stock_minimo' => 1,
            'activo' => true,
            'creado_en' => now()->toDateString(),
        ]);

        $this->actingAs($this->admin())
            ->get(route('inventario.edit', $productoId))
            ->assertOk()
            ->assertDontSee('Presentación')
            ->assertDontSee('id="presentacion"', false)
            ->assertSee('id="id_tipo" value="" placeholder="Se asigna del catálogo" disabled', false)
            ->assertSee('id="id_color" value="" placeholder="Se asigna del catálogo" disabled', false)
            ->assertSee('id="stock_actual" type="number" value="12.00" disabled', false)
            ->assertSee('id="id_unidad" value="" placeholder="Se asigna del catálogo" disabled', false)
            ->assertSee('id="precio_compra" type="number" value="25.00" disabled', false)
            ->assertDontSee('name="id_proveedor"', false);

        $this->put(route('inventario.update', $productoId), [
            'id_catalogo' => 1,
            'id_anaquel' => 1,
            'posicion_anaquel' => 'Fila 2, Columna 2',
            'stock_minimo' => 7,
            'precio_venta' => 80,
            'id_tipo' => 99,
            'id_color' => 99,
            'id_unidad' => 99,
            'presentacion' => 'Alterada',
            'stock_actual' => 99,
            'precio_compra' => 999,
            'id_proveedor' => 99,
        ])->assertRedirect(route('inventario'));

        $this->assertDatabaseHas('productos', [
            'id_producto' => $productoId,
            'id_tipo' => 1,
            'id_color' => 1,
            'id_unidad' => 1,
            'presentacion' => '500g',
            'stock_actual' => 12,
            'stock_minimo' => 7,
            'precio_compra' => 25,
            'precio_venta' => 80,
            'id_proveedor' => null,
            'posicion_anaquel' => 'Fila 2, Columna 2',
        ]);
    }

    private function admin(): Usuario
    {
        return Usuario::query()->create([
            'nombre' => 'Administradora',
            'correo' => 'admin@example.test',
            'contrasena' => 'hash',
            'rol' => 'admin',
            'id_tienda' => 1,
            'activo' => true,
            'debe_cambiar_contrasena' => false,
        ]);
    }
}
