<?php

namespace Tests\Feature;

use App\Models\Usuario;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class AuthTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('tiendas', function (Blueprint $table) {
            $table->increments('id_tienda');
            $table->string('nombre');
            $table->boolean('activo')->default(true);
        });
        Schema::create('usuarios', function (Blueprint $table) {
            $table->increments('id_usuario');
            $table->string('nombre', 120);
            $table->string('correo', 100)->unique();
            $table->string('contrasena', 255);
            $table->string('rol', 100);
            $table->unsignedInteger('id_tienda');
            $table->boolean('activo')->default(true);
            $table->date('ultimo_acceso')->nullable();
            $table->string('ip_ultimo', 45)->nullable();
            $table->date('creado_en')->nullable();
        });
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('usuarios');
        Schema::dropIfExists('tiendas');

        parent::tearDown();
    }

    public function test_legacy_password_is_verified_and_upgraded_during_login(): void
    {
        $user = Usuario::query()->create([
            'nombre' => 'Ana Ramírez',
            'correo' => 'ana@example.test',
            'contrasena' => 'secreto123',
            'rol' => 'empleado',
            'id_tienda' => 1,
            'activo' => 1,
        ]);

        $response = $this->post(route('login.store'), [
            'usuario' => 'ana@example.test',
            'contrasena' => 'secreto123',
        ]);

        $response->assertRedirect(route('dashboard'));
        $this->assertAuthenticatedAs($user);
        $this->assertNotSame('secreto123', $user->fresh()->contrasena);
        $this->assertTrue(password_verify('secreto123', $user->fresh()->contrasena));
    }

    public function test_registration_validates_identity_and_creates_an_employee_account(): void
    {
        DB::table('tiendas')->insert(['nombre' => 'Sucursal de prueba', 'activo' => 1]);

        $response = $this->post(route('register.store'), [
            'nombre' => 'María Pérez',
            'correo' => 'maria@example.test',
            'contrasena' => 'secreto123',
            'contrasena_confirmation' => 'secreto123',
        ]);

        $response->assertRedirect(route('login'));
        $this->assertDatabaseHas('usuarios', [
            'correo' => 'maria@example.test',
            'rol' => 'empleado',
            'id_tienda' => 1,
        ]);
        $this->assertTrue(password_verify('secreto123', Usuario::query()->where('correo', 'maria@example.test')->value('contrasena')));
    }
}
