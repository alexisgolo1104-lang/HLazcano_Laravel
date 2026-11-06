<?php

namespace Tests\Feature;

use App\Models\Usuario;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class ComprasTicketTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('tiendas', function (Blueprint $table) {
            $table->increments('id_tienda');
            $table->string('nombre');
            $table->string('direccion')->nullable();
            $table->string('telefono')->nullable();
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
        Schema::create('proveedores', function (Blueprint $table) {
            $table->increments('id_proveedor');
            $table->string('nombre');
            $table->string('telefono')->nullable();
            $table->string('correo')->nullable();
            $table->string('direccion')->nullable();
        });
        Schema::create('compras', function (Blueprint $table) {
            $table->increments('id_compra');
            $table->string('folio')->nullable();
            $table->unsignedInteger('id_proveedor');
            $table->unsignedInteger('id_usuario');
            $table->unsignedInteger('id_tienda');
            $table->decimal('total', 12, 2);
            $table->string('notas')->nullable();
            $table->date('fecha');
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
            $table->unsignedInteger('id_unidad');
            $table->unsignedInteger('id_tienda')->nullable();
            $table->string('presentacion')->nullable();
        });
        Schema::create('detalle_compra', function (Blueprint $table) {
            $table->increments('id_detalle');
            $table->unsignedInteger('id_compra');
            $table->unsignedInteger('id_producto');
            $table->decimal('cantidad', 10, 2);
            $table->decimal('precio_unit', 10, 2);
            $table->decimal('subtotal', 12, 2);
        });

        DB::table('tiendas')->insert([
            'id_tienda' => 1,
            'nombre' => 'Sucursal 1 — Santa María Texmelucan',
            'direccion' => 'Calle Centro 10',
            'telefono' => '222-123-4567',
        ]);
        DB::table('proveedores')->insert([
            'id_proveedor' => 1,
            'nombre' => 'Proveedor de prueba',
            'telefono' => '222-111-0000',
            'correo' => 'proveedor@example.test',
        ]);
        DB::table('catalogo_productos')->insert(['id_catalogo' => 1, 'nombre' => 'Hilo de prueba']);
        DB::table('unidades')->insert(['id_unidad' => 1, 'codigo' => 'kg']);
        DB::table('productos')->insert([
            'id_producto' => 1,
            'id_catalogo' => 1,
            'id_unidad' => 1,
            'presentacion' => '500g',
        ]);
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('detalle_compra');
        Schema::dropIfExists('productos');
        Schema::dropIfExists('unidades');
        Schema::dropIfExists('catalogo_productos');
        Schema::dropIfExists('compras');
        Schema::dropIfExists('proveedores');
        Schema::dropIfExists('usuarios');
        Schema::dropIfExists('tiendas');

        parent::tearDown();
    }

    public function test_admin_can_view_a_standalone_purchase_ticket_for_their_store(): void
    {
        $usuario = Usuario::query()->create([
            'nombre' => 'Administrador',
            'correo' => 'admin@example.test',
            'contrasena' => 'hash',
            'rol' => 'admin',
            'id_tienda' => 1,
            'activo' => true,
        ]);
        DB::table('compras')->insert([
            'id_compra' => 1,
            'folio' => 'C-001',
            'id_proveedor' => 1,
            'id_usuario' => $usuario->id_usuario,
            'id_tienda' => 1,
            'total' => 80,
            'fecha' => '2026-10-28',
            'notas' => 'Entrada de inventario',
        ]);
        DB::table('detalle_compra')->insert([
            'id_compra' => 1,
            'id_producto' => 1,
            'cantidad' => 2,
            'precio_unit' => 40,
            'subtotal' => 80,
        ]);

        $this->actingAs($usuario)
            ->get(route('compras.ticket', 1))
            ->assertOk()
            ->assertSee('<!DOCTYPE html>', false)
            ->assertSee('size:80mm auto', false)
            ->assertSee('Proveedor de prueba')
            ->assertSee('Hilo de prueba')
            ->assertSee('Compra C-001')
            ->assertSee('Compra registrada')
            ->assertSee('Calle Centro 10')
            ->assertDontSee('Sucursal 1')
            ->assertDontSee('Cancelar / devolver')
            ->assertDontSee('class="app"', false)
            ->assertDontSee('hlazcano.css', false);
    }

    public function test_employee_cannot_open_purchase_tickets(): void
    {
        $usuario = Usuario::query()->create([
            'nombre' => 'Empleado',
            'correo' => 'empleado@example.test',
            'contrasena' => 'hash',
            'rol' => 'empleado',
            'id_tienda' => 1,
            'activo' => true,
        ]);

        $this->actingAs($usuario)
            ->get(route('compras.ticket', 1))
            ->assertForbidden();
    }

    public function test_product_purchase_history_groups_previous_suppliers_for_the_current_store(): void
    {
        $usuario = Usuario::query()->create([
            'nombre' => 'Administrador',
            'correo' => 'admin@example.test',
            'contrasena' => 'hash',
            'rol' => 'admin',
            'id_tienda' => 1,
            'activo' => true,
        ]);
        DB::table('proveedores')->insert([
            'id_proveedor' => 2,
            'nombre' => 'Segundo proveedor',
        ]);
        DB::table('productos')->where('id_producto', 1)->update(['id_tienda' => 1]);
        DB::table('compras')->insert([
            [
                'id_compra' => 1,
                'folio' => 'C-001',
                'id_proveedor' => 1,
                'id_usuario' => $usuario->id_usuario,
                'id_tienda' => 1,
                'total' => 80,
                'fecha' => '2026-10-28',
            ],
            [
                'id_compra' => 2,
                'folio' => 'C-002',
                'id_proveedor' => 2,
                'id_usuario' => $usuario->id_usuario,
                'id_tienda' => 1,
                'total' => 100,
                'fecha' => '2026-10-30',
            ],
            [
                'id_compra' => 3,
                'folio' => 'C-003',
                'id_proveedor' => 1,
                'id_usuario' => $usuario->id_usuario,
                'id_tienda' => 2,
                'total' => 120,
                'fecha' => '2026-10-31',
            ],
        ]);
        DB::table('detalle_compra')->insert([
            ['id_compra' => 1, 'id_producto' => 1, 'cantidad' => 2, 'precio_unit' => 40, 'subtotal' => 80],
            ['id_compra' => 2, 'id_producto' => 1, 'cantidad' => 4, 'precio_unit' => 25, 'subtotal' => 100],
            ['id_compra' => 3, 'id_producto' => 1, 'cantidad' => 6, 'precio_unit' => 20, 'subtotal' => 120],
        ]);

        $this->actingAs($usuario)
            ->get(route('compras.historial-producto', 1))
            ->assertOk()
            ->assertJsonCount(2)
            ->assertJsonFragment([
                'proveedor' => 'Proveedor de prueba',
                'ultima_compra' => '2026-10-28',
                'total_compras' => 1,
            ])
            ->assertJsonFragment([
                'proveedor' => 'Segundo proveedor',
                'ultima_compra' => '2026-10-30',
                'total_compras' => 1,
            ]);
    }
}
