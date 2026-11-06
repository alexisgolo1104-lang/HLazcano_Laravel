<?php

namespace Tests\Feature;

use App\Models\Cliente;
use App\Models\Usuario;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class ClientesTest extends TestCase
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
        Schema::create('clientes', function (Blueprint $table) {
            $table->increments('id_cliente');
            $table->string('nombre', 120);
            $table->string('telefono', 20)->nullable();
            $table->string('correo', 100)->nullable();
            $table->string('direccion', 200)->nullable();
            $table->string('rfc', 20)->nullable();
            $table->string('tipo_cliente', 150)->nullable();
            $table->unsignedInteger('id_tienda');
            $table->string('notas', 150)->nullable();
            $table->boolean('activo')->default(true);
            $table->date('creado_en');
            $table->date('actualizado_en')->nullable();
        });

        DB::table('tiendas')->insert([
            ['id_tienda' => 1, 'nombre' => 'Sucursal Uno'],
            ['id_tienda' => 2, 'nombre' => 'Sucursal Dos'],
        ]);
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('clientes');
        Schema::dropIfExists('usuarios');
        Schema::dropIfExists('tiendas');

        parent::tearDown();
    }

    public function test_client_can_be_updated_without_changing_its_store_or_creation_date(): void
    {
        $user = $this->createUser('empleado', 1);
        $cliente = $this->createCliente(1, [
            'nombre' => 'Cliente anterior',
            'creado_en' => '2026-01-15',
        ]);

        $this->actingAs($user)
            ->get(route('clientes.edit', $cliente->id_cliente))
            ->assertOk()
            ->assertSee('Cliente anterior');

        $this->actingAs($user)
            ->put(route('clientes.update', $cliente->id_cliente), [
                'nombre' => 'Cliente actualizado',
                'telefono' => '5551234567',
                'correo' => 'cliente@example.test',
                'direccion' => 'Calle Principal 10',
                'rfc' => 'RFC123',
                'tipo_cliente' => 'Taller',
                'notas' => 'Datos actualizados',
                'id_tienda' => 2,
                'activo' => 0,
            ])
            ->assertRedirect(route('clientes'));

        $this->assertDatabaseHas('clientes', [
            'id_cliente' => $cliente->id_cliente,
            'nombre' => 'Cliente actualizado',
            'telefono' => '5551234567',
            'correo' => 'cliente@example.test',
            'direccion' => 'Calle Principal 10',
            'rfc' => 'RFC123',
            'tipo_cliente' => 'Taller',
            'notas' => 'Datos actualizados',
            'id_tienda' => 1,
            'activo' => 1,
            'creado_en' => '2026-01-15',
            'actualizado_en' => now()->toDateString(),
        ]);
    }

    public function test_deactivating_client_preserves_record_and_hides_it_from_active_list(): void
    {
        $user = $this->createUser('empleado', 1);
        $cliente = $this->createCliente(1, ['nombre' => 'Cliente por desactivar']);

        $this->actingAs($user)
            ->delete(route('clientes.destroy', $cliente->id_cliente))
            ->assertRedirect(route('clientes'));

        $this->assertDatabaseHas('clientes', [
            'id_cliente' => $cliente->id_cliente,
            'nombre' => 'Cliente por desactivar',
            'activo' => 0,
        ]);
        $this->actingAs($user)
            ->get(route('clientes'))
            ->assertOk()
            ->assertDontSee('Cliente por desactivar');
    }

    public function test_user_cannot_edit_or_deactivate_client_from_another_store(): void
    {
        $user = $this->createUser('empleado', 1);
        $cliente = $this->createCliente(2);

        $this->actingAs($user)
            ->get(route('clientes.edit', $cliente->id_cliente))
            ->assertNotFound();
        $this->actingAs($user)
            ->put(route('clientes.update', $cliente->id_cliente), ['nombre' => 'Cambio no autorizado'])
            ->assertNotFound();
        $this->actingAs($user)
            ->delete(route('clientes.destroy', $cliente->id_cliente))
            ->assertNotFound();

        $this->assertDatabaseHas('clientes', [
            'id_cliente' => $cliente->id_cliente,
            'nombre' => $cliente->nombre,
            'activo' => 1,
        ]);
    }

    private function createUser(string $role, int $storeId): Usuario
    {
        return Usuario::query()->create([
            'nombre' => 'Usuario de prueba',
            'correo' => uniqid('usuario-', true).'@example.test',
            'contrasena' => 'hash',
            'rol' => $role,
            'id_tienda' => $storeId,
            'activo' => true,
        ]);
    }

    private function createCliente(int $storeId, array $attributes = []): Cliente
    {
        return Cliente::query()->create(array_merge([
            'nombre' => 'Cliente de prueba',
            'id_tienda' => $storeId,
            'activo' => 1,
            'creado_en' => now()->toDateString(),
        ], $attributes));
    }
}
