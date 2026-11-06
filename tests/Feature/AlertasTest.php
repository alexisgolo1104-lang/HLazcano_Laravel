<?php

namespace Tests\Feature;

use App\Models\Usuario;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class AlertasTest extends TestCase
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
        });
        Schema::create('catalogo_productos', function (Blueprint $table) {
            $table->increments('id_catalogo');
            $table->string('nombre');
            $table->boolean('activo')->default(true);
        });
        Schema::create('tipos_hilo', function (Blueprint $table) {
            $table->increments('id_tipo');
            $table->string('nombre');
            $table->boolean('activo')->default(true);
        });
        Schema::create('colores', function (Blueprint $table) {
            $table->increments('id_color');
            $table->string('nombre');
            $table->string('codigo_hex')->nullable();
            $table->boolean('activo')->default(true);
        });
        Schema::create('unidades', function (Blueprint $table) {
            $table->increments('id_unidad');
            $table->string('codigo');
        });
        Schema::create('anaqueles', function (Blueprint $table) {
            $table->increments('id_anaquel');
            $table->string('codigo');
        });
        Schema::create('productos', function (Blueprint $table) {
            $table->increments('id_producto');
            $table->unsignedInteger('id_catalogo');
            $table->unsignedInteger('id_tipo');
            $table->unsignedInteger('id_color');
            $table->unsignedInteger('id_unidad');
            $table->unsignedInteger('id_anaquel')->nullable();
            $table->unsignedInteger('id_tienda');
            $table->string('presentacion')->nullable();
            $table->decimal('stock_actual', 10, 2);
            $table->decimal('stock_minimo', 10, 2);
            $table->decimal('precio_compra', 10, 2)->nullable();
            $table->decimal('precio_venta', 10, 2)->nullable();
            $table->boolean('activo')->default(true);
            $table->date('creado_en');
            $table->date('actualizado_en')->nullable();
        });
        Schema::create('ventas', function (Blueprint $table) {
            $table->increments('id_venta');
            $table->unsignedInteger('id_tienda');
            $table->date('fecha');
            $table->string('notas')->nullable();
        });
        Schema::create('detalle_venta', function (Blueprint $table) {
            $table->increments('id_detalle');
            $table->unsignedInteger('id_venta');
            $table->unsignedInteger('id_producto');
        });
        Schema::create('compras', function (Blueprint $table) {
            $table->increments('id_compra');
            $table->unsignedInteger('id_tienda');
            $table->date('fecha');
        });
        Schema::create('detalle_compra', function (Blueprint $table) {
            $table->increments('id_detalle');
            $table->unsignedInteger('id_compra');
            $table->unsignedInteger('id_producto');
        });
        Schema::create('movimientos_inventario', function (Blueprint $table) {
            $table->increments('id_movimiento');
            $table->unsignedInteger('id_producto');
            $table->date('fecha');
        });
        Schema::create('alertas_stock', function (Blueprint $table) {
            $table->increments('id_alerta');
            $table->unsignedInteger('id_producto');
            $table->unsignedInteger('id_tienda');
            $table->string('tipo_alerta');
            $table->boolean('atendida')->default(false);
            $table->string('accion_tomada')->nullable();
            $table->unsignedInteger('id_usuario_aten')->nullable();
            $table->string('notas_atencion')->nullable();
            $table->date('fecha_alerta');
            $table->date('fecha_atencion')->nullable();
            $table->decimal('stock_alerta', 10, 2)->nullable();
            $table->date('fecha_movimiento_alerta')->nullable();
        });

        DB::table('tiendas')->insert(['id_tienda' => 1, 'nombre' => 'Sucursal 1']);
        DB::table('tipos_hilo')->insert(['id_tipo' => 1, 'nombre' => 'Algodón']);
        DB::table('colores')->insert(['id_color' => 1, 'nombre' => 'Rojo', 'codigo_hex' => '#c0392b']);
        DB::table('unidades')->insert(['id_unidad' => 1, 'codigo' => 'kg']);
        DB::table('anaqueles')->insert(['id_anaquel' => 1, 'codigo' => 'A-1']);

        foreach ([
            [1, 'Crítico', 1, 5, now()->subDays(40)->toDateString()],
            [2, 'Mínimo', 4, 5, now()->subDays(40)->toDateString()],
            [3, 'Normal', 20, 5, now()->subDays(40)->toDateString()],
            [4, 'Próximo', 6, 5, now()->subDays(40)->toDateString()],
        ] as [$id, $name, $stock, $minimum, $createdAt]) {
            DB::table('catalogo_productos')->insert(['id_catalogo' => $id, 'nombre' => $name, 'activo' => true]);
            DB::table('productos')->insert([
                'id_producto' => $id,
                'id_catalogo' => $id,
                'id_tipo' => 1,
                'id_color' => 1,
                'id_unidad' => 1,
                'id_anaquel' => 1,
                'id_tienda' => 1,
                'presentacion' => '100g',
                'stock_actual' => $stock,
                'stock_minimo' => $minimum,
                'precio_venta' => 50,
                'activo' => true,
                'creado_en' => $createdAt,
            ]);
        }

        DB::table('ventas')->insert(['id_venta' => 1, 'id_tienda' => 1, 'fecha' => now()->subDays(10)->toDateString()]);
        DB::table('detalle_venta')->insert(['id_venta' => 1, 'id_producto' => 3]);
    }

    protected function tearDown(): void
    {
        foreach ([
            'alertas_stock', 'movimientos_inventario', 'detalle_compra', 'compras', 'detalle_venta', 'ventas',
            'productos', 'anaqueles', 'unidades', 'colores', 'tipos_hilo', 'catalogo_productos', 'usuarios', 'tiendas',
        ] as $table) {
            Schema::dropIfExists($table);
        }

        parent::tearDown();
    }

    public function test_alerts_are_grouped_and_the_bell_shows_pending_count(): void
    {
        $usuario = Usuario::query()->create([
            'nombre' => 'Ana',
            'correo' => 'ana@example.test',
            'contrasena' => 'hash',
            'rol' => 'admin',
            'id_tienda' => 1,
            'activo' => true,
        ]);

        $this->actingAs($usuario)
            ->get(route('alertas'))
            ->assertOk()
            ->assertSee('Bajo stock crítico')
            ->assertSee('Stock en nivel mínimo')
            ->assertSee('Sin movimiento más de 30 días')
            ->assertSee('Ver alertas: 6 pendientes')
            ->assertSee('Comprar ahora');
    }

    public function test_stock_at_minimum_reclassifies_an_existing_amber_alert_as_critical(): void
    {
        $usuario = Usuario::query()->create([
            'nombre' => 'Ana',
            'correo' => 'ana@example.test',
            'contrasena' => 'hash',
            'rol' => 'admin',
            'id_tienda' => 1,
            'activo' => true,
        ]);
        $amberAlertId = DB::table('alertas_stock')->insertGetId([
            'id_producto' => 2,
            'id_tienda' => 1,
            'tipo_alerta' => 'stock_minimo',
            'atendida' => 0,
            'fecha_alerta' => now()->toDateString(),
            'stock_alerta' => 6,
        ]);

        $this->actingAs($usuario)->get(route('alertas'))->assertOk();

        $this->assertDatabaseHas('alertas_stock', [
            'id_alerta' => $amberAlertId,
            'atendida' => 1,
            'accion_tomada' => 'resuelta',
        ]);
        $this->assertDatabaseHas('alertas_stock', [
            'id_producto' => 2,
            'tipo_alerta' => 'stock_critico',
            'atendida' => 0,
        ]);
        $this->assertDatabaseMissing('alertas_stock', [
            'id_producto' => 2,
            'tipo_alerta' => 'stock_minimo',
            'atendida' => 0,
        ]);
    }

    public function test_marking_all_alerts_attended_clears_the_pending_bell_count(): void
    {
        $usuario = Usuario::query()->create([
            'nombre' => 'Ana',
            'correo' => 'ana@example.test',
            'contrasena' => 'hash',
            'rol' => 'admin',
            'id_tienda' => 1,
            'activo' => true,
        ]);

        $this->actingAs($usuario)->get(route('alertas'))->assertOk();
        $this->post(route('alertas.atender-todas'))->assertRedirect();
        $this->assertSame(6, DB::table('alertas_stock')->where('atendida', 1)->count());
        $this->assertSame(0, DB::table('alertas_stock')->where('atendida', 0)->count());

        $this->get(route('alertas'))
            ->assertOk()
            ->assertDontSee('notif-count');
    }

    public function test_new_sale_reopens_attended_low_stock_alert(): void
    {
        $usuario = Usuario::query()->create([
            'nombre' => 'Ana',
            'correo' => 'ana@example.test',
            'contrasena' => 'hash',
            'rol' => 'admin',
            'id_tienda' => 1,
            'activo' => true,
        ]);

        $this->actingAs($usuario)->get(route('alertas'))->assertOk();
        $alerta = DB::table('alertas_stock')->where('tipo_alerta', 'stock_critico')->first();
        $this->post(route('alertas.atender-todas'))->assertRedirect();

        DB::table('productos')->where('id_producto', $alerta->id_producto)->decrement('stock_actual', 1);
        DB::table('ventas')->insert(['id_venta' => 2, 'id_tienda' => 1, 'fecha' => now()->toDateString()]);
        DB::table('detalle_venta')->insert(['id_venta' => 2, 'id_producto' => $alerta->id_producto]);

        $this->get(route('alertas'))
            ->assertOk()
            ->assertSee('Ver alertas: 1 pendientes');

        $this->assertDatabaseHas('alertas_stock', [
            'id_alerta' => $alerta->id_alerta,
            'atendida' => 0,
            'accion_tomada' => null,
        ]);
    }

    public function test_no_movement_alert_returns_after_another_30_days(): void
    {
        $usuario = Usuario::query()->create([
            'nombre' => 'Ana',
            'correo' => 'ana@example.test',
            'contrasena' => 'hash',
            'rol' => 'admin',
            'id_tienda' => 1,
            'activo' => true,
        ]);

        $this->actingAs($usuario)->get(route('alertas'))->assertOk();
        $alerta = DB::table('alertas_stock')->where('id_producto', 1)->where('tipo_alerta', 'sin_movimiento')->first();
        $this->post(route('alertas.atender-todas'))->assertRedirect();

        DB::table('ventas')->insert(['id_venta' => 2, 'id_tienda' => 1, 'fecha' => now()->toDateString()]);
        DB::table('detalle_venta')->insert(['id_venta' => 2, 'id_producto' => 1]);
        $this->get(route('alertas'))->assertOk();
        $this->assertDatabaseHas('alertas_stock', [
            'id_alerta' => $alerta->id_alerta,
            'atendida' => 1,
            'accion_tomada' => 'resuelta',
        ]);

        $this->travel(31)->days();
        $this->get(route('alertas'))->assertOk();

        $this->assertSame(2, DB::table('alertas_stock')
            ->where('id_producto', 1)
            ->where('tipo_alerta', 'sin_movimiento')
            ->count());
    }

    public function test_inventory_displays_critical_warning_and_normal_stock_colors(): void
    {
        $usuario = Usuario::query()->create([
            'nombre' => 'Ana',
            'correo' => 'ana@example.test',
            'contrasena' => 'hash',
            'rol' => 'admin',
            'id_tienda' => 1,
            'activo' => true,
        ]);

        $this->actingAs($usuario)
            ->get(route('inventario'))
            ->assertOk()
            ->assertSee('badge-danger')
            ->assertSee('badge-warn')
            ->assertSee('badge-ok')
            ->assertSee('Stock próximo al mínimo');
    }
}