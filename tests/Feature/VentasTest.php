<?php

namespace Tests\Feature;

use App\Models\Usuario;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class VentasTest extends TestCase
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
        Schema::create('productos', function (Blueprint $table) {
            $table->increments('id_producto');
            $table->decimal('stock_actual', 10, 2);
            $table->date('actualizado_en')->nullable();
        });
        Schema::create('ventas', function (Blueprint $table) {
            $table->increments('id_venta');
            $table->string('folio');
            $table->unsignedInteger('id_tienda');
            $table->string('notas', 150)->nullable();
        });
        Schema::create('detalle_venta', function (Blueprint $table) {
            $table->increments('id_detalle');
            $table->unsignedInteger('id_venta');
            $table->unsignedInteger('id_producto');
            $table->decimal('cantidad', 10, 2);
        });
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('detalle_venta');
        Schema::dropIfExists('ventas');
        Schema::dropIfExists('productos');
        Schema::dropIfExists('usuarios');
        Schema::dropIfExists('tiendas');

        parent::tearDown();
    }

    public function test_cancellation_restores_stock_and_cannot_be_applied_twice(): void
    {
        $user = Usuario::query()->create([
            'nombre' => 'Vendedor',
            'correo' => 'vendedor@example.test',
            'contrasena' => 'hash',
            'rol' => 'empleado',
            'id_tienda' => 1,
            'activo' => true,
        ]);
        DB::table('productos')->insert(['id_producto' => 1, 'stock_actual' => 3]);
        DB::table('ventas')->insert([
            'id_venta' => 1,
            'folio' => 'V-001',
            'id_tienda' => 1,
            'notas' => 'Venta mostrador',
        ]);
        DB::table('detalle_venta')->insert([
            'id_venta' => 1,
            'id_producto' => 1,
            'cantidad' => 2,
        ]);

        $this->actingAs($user)->post(route('ventas.cancelar', 1), [
            'tipo' => 'cancelacion',
            'motivo' => 'Error de captura',
        ])->assertRedirect(route('ventas'));

        $this->assertDatabaseHas('productos', ['id_producto' => 1, 'stock_actual' => 5]);
        $this->assertDatabaseHas('ventas', ['id_venta' => 1, 'notas' => 'CANCELADA: Error de captura — Nota: Venta mostrador']);

        $this->post(route('ventas.cancelar', 1), [
            'tipo' => 'devolucion',
            'motivo' => 'Segundo intento',
        ])->assertSessionHasErrors('tipo');

        $this->assertDatabaseHas('productos', ['id_producto' => 1, 'stock_actual' => 5]);
    }
}
