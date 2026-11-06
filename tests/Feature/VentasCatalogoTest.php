<?php

namespace Tests\Feature;

use App\Models\Usuario;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class VentasCatalogoTest extends TestCase
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
        Schema::create('clientes', function (Blueprint $table) {
            $table->increments('id_cliente');
            $table->string('nombre');
            $table->boolean('activo')->default(true);
            $table->unsignedInteger('id_tienda');
        });
        Schema::create('ventas', function (Blueprint $table) {
            $table->increments('id_venta');
            $table->unsignedInteger('id_usuario')->nullable();
            $table->unsignedInteger('id_cliente')->nullable();
            $table->unsignedInteger('id_tienda');
            $table->string('folio');
            $table->date('fecha')->nullable();
            $table->decimal('descuento', 5, 2)->default(0);
            $table->decimal('subtotal', 10, 2)->default(0);
            $table->decimal('total', 10, 2)->default(0);
            $table->string('notas')->nullable();
        });
        Schema::create('detalle_venta', function (Blueprint $table) {
            $table->increments('id_detalle');
            $table->unsignedInteger('id_venta');
            $table->unsignedInteger('id_producto');
            $table->decimal('cantidad', 10, 2);
            $table->decimal('precio_unit', 10, 2);
            $table->decimal('subtotal', 10, 2);
        });
        Schema::create('tipos_hilo', function (Blueprint $table) {
            $table->increments('id_tipo');
            $table->string('nombre');
            $table->boolean('activo')->default(true);
        });
        Schema::create('catalogo_productos', function (Blueprint $table) {
            $table->increments('id_catalogo');
            $table->string('nombre');
        });
        Schema::create('unidades', function (Blueprint $table) {
            $table->increments('id_unidad');
            $table->string('codigo');
        });
        Schema::create('productos', function (Blueprint $table) {
            $table->increments('id_producto');
            $table->unsignedInteger('id_catalogo');
            $table->unsignedInteger('id_tipo');
            $table->unsignedInteger('id_unidad')->nullable();
            $table->unsignedInteger('id_tienda');
            $table->string('presentacion')->nullable();
            $table->decimal('stock_actual', 10, 2);
            $table->decimal('precio_venta', 10, 2);
            $table->boolean('activo')->default(true);
            $table->date('actualizado_en')->nullable();
        });

        DB::table('tiendas')->insert(['id_tienda' => 1, 'nombre' => 'HLazcano']);
        DB::table('tipos_hilo')->insert([
            ['id_tipo' => 1, 'nombre' => 'Acrílico'],
            ['id_tipo' => 2, 'nombre' => 'Algodón'],
        ]);
        DB::table('catalogo_productos')->insert([
            ['id_catalogo' => 1, 'nombre' => 'Hilo acrílico blanco 500g'],
            ['id_catalogo' => 2, 'nombre' => 'Hilo algodón rojo 250g'],
        ]);
        DB::table('unidades')->insert(['id_unidad' => 1, 'codigo' => 'kg']);
        DB::table('productos')->insert([
            [
                'id_producto' => 1,
                'id_catalogo' => 1,
                'id_tipo' => 1,
                'id_unidad' => 1,
                'id_tienda' => 1,
                'presentacion' => '500g',
                'stock_actual' => 12,
                'precio_venta' => 65,
            ],
            [
                'id_producto' => 2,
                'id_catalogo' => 2,
                'id_tipo' => 2,
                'id_unidad' => 1,
                'id_tienda' => 1,
                'presentacion' => '250g',
                'stock_actual' => 4,
                'precio_venta' => 78,
            ],
        ]);
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('productos');
        Schema::dropIfExists('unidades');
        Schema::dropIfExists('catalogo_productos');
        Schema::dropIfExists('detalle_venta');
        Schema::dropIfExists('tipos_hilo');
        Schema::dropIfExists('ventas');
        Schema::dropIfExists('clientes');
        Schema::dropIfExists('usuarios');
        Schema::dropIfExists('tiendas');

        parent::tearDown();
    }

    public function test_sales_product_picker_filters_by_material_and_shows_selected_price_and_stock(): void
    {
        $usuario = Usuario::query()->create([
            'nombre' => 'Vendedor',
            'correo' => 'vendedor@example.test',
            'contrasena' => 'hash',
            'rol' => 'empleado',
            'id_tienda' => 1,
            'activo' => true,
            'debe_cambiar_contrasena' => false,
        ]);

        $this->actingAs($usuario)
            ->get(route('ventas'))
            ->assertOk()
            ->assertSee('Filtrar por material')
            ->assertSee('Todos los materiales')
            ->assertSee('Acrílico')
            ->assertSee('Algodón')
            ->assertSee('>Hilo acrílico blanco 500g</option>', false)
            ->assertDontSee('Hilo acrílico blanco 500g 500g')
            ->assertSee('data-material="1" data-price="65.00" data-stock="12.00" data-unit="kg"', false)
            ->assertSee('data-material="2" data-price="78.00" data-stock="4.00" data-unit="kg"', false)
            ->assertSee('id="producto-precio"', false)
            ->assertSee('id="producto-stock"', false)
            ->assertDontSee('Descuento')
            ->assertDontSee('name="descuento"', false)
            ->assertSee('option.dataset.material !== material', false)
            ->assertSee('id="app-toast-stack"', false)
            ->assertSee('window.showToast =', false)
            ->assertSee('role="alertdialog"', false)
            ->assertSee("form.method.toLowerCase() === 'get'", false)
            ->assertSee('¿Estás seguro de que deseas realizar estos cambios?')
            ->assertSee('form.requestSubmit()', false)
            ->assertSee('data-confirm-skip', false)
            ->assertSee('sessionStorage.setItem(saleDraftKey', false)
            ->assertSee('restoreSaleDraft()', false)
            ->assertSee('client: document.getElementById', false)
            ->assertSee('items: saleItems.map', false)
            ->assertDontSee('alert(', false)
            ->assertDontSee('window.confirm(', false);
    }

    public function test_sales_ignore_discounts_and_store_the_full_subtotal_as_total(): void
    {
        $usuario = Usuario::query()->create([
            'nombre' => 'Vendedor',
            'correo' => 'vendedor@example.test',
            'contrasena' => 'hash',
            'rol' => 'empleado',
            'id_tienda' => 1,
            'activo' => true,
            'debe_cambiar_contrasena' => false,
        ]);

        $this->actingAs($usuario)
            ->followingRedirects()
            ->post(route('ventas.store'), [
                'items' => json_encode([
                    ['id_producto' => 1, 'cantidad' => 2],
                ]),
                'descuento' => 50,
            ])
            ->assertOk()
            ->assertSee('const saleDraftCompleted = true;', false)
            ->assertSee('sessionStorage.removeItem(saleDraftKey)', false);

        $this->assertDatabaseHas('ventas', [
            'id_venta' => 1,
            'descuento' => 0,
            'subtotal' => 130,
            'total' => 130,
        ]);
        $this->assertDatabaseHas('productos', ['id_producto' => 1, 'stock_actual' => 10]);
    }
}
