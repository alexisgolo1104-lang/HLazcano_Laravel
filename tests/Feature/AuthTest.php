<?php

namespace Tests\Feature;

use App\Models\Usuario;
use App\Notifications\TemporaryPasswordNotification;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
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
            $table->string('nombre', 120)->collation('NOCASE');
            $table->string('correo', 100)->unique();
            $table->string('telefono', 20)->nullable();
            $table->string('contrasena', 255);
            $table->string('rol', 100);
            $table->unsignedInteger('id_tienda');
            $table->boolean('activo')->default(true);
            $table->boolean('debe_cambiar_contrasena')->default(false);
            $table->date('ultimo_acceso')->nullable();
            $table->string('ip_ultimo', 45)->nullable();
            $table->date('creado_en');
        });
        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('usuarios');
        Schema::dropIfExists('tiendas');
        Schema::dropIfExists('password_reset_tokens');

        parent::tearDown();
    }

    public function test_guest_authentication_pages_link_to_each_other(): void
    {
        $this->get(route('login'))
            ->assertOk()
            ->assertSee('login-wrap')
            ->assertSee(asset('img/hilos.jpeg'), false)
            ->assertSee('Crear una cuenta')
            ->assertSee('¿Olvidaste tu contraseña?')
            ->assertSee(route('password.request'))
            ->assertSee(route('register'));

        $this->get(route('register'))
            ->assertOk()
            ->assertSee('login-wrap')
            ->assertSee('Administrador (requiere aprobación)')
            ->assertSee('Número de teléfono')
            ->assertSee('Volver a iniciar sesión')
            ->assertSee(route('login'));
    }

    public function test_authenticated_user_can_log_out_using_post(): void
    {
        $usuario = Usuario::query()->create([
            'nombre' => 'Ana Ramírez',
            'correo' => 'ana@example.test',
            'contrasena' => bcrypt('secreto123'),
            'rol' => 'empleado',
            'id_tienda' => 1,
            'activo' => 1,
            'creado_en' => now()->toDateString(),
        ]);

        $this->actingAs($usuario)
            ->post(route('logout'))
            ->assertRedirect(route('login'));

        $this->assertGuest();
    }

    public function test_authenticated_user_can_log_out_using_get_from_legacy_links(): void
    {
        $usuario = Usuario::query()->create([
            'nombre' => 'Ana Ramírez',
            'correo' => 'ana@example.test',
            'contrasena' => bcrypt('secreto123'),
            'rol' => 'empleado',
            'id_tienda' => 1,
            'activo' => 1,
            'creado_en' => now()->toDateString(),
        ]);

        $this->actingAs($usuario)
            ->get(route('logout'))
            ->assertRedirect(route('login'));

        $this->assertGuest();
    }

    public function test_password_recovery_sends_a_temporary_password_and_stores_only_its_hash(): void
    {
        Notification::fake();
        $usuario = Usuario::query()->create([
            'nombre' => 'Ana Ramírez',
            'correo' => 'ana@example.test',
            'contrasena' => bcrypt('anterior123'),
            'rol' => 'empleado',
            'id_tienda' => 1,
            'activo' => 1,
            'creado_en' => now()->toDateString(),
        ]);

        $this->get(route('password.request'))
            ->assertOk()
            ->assertSee('Enviar contraseña temporal');

        $this->post(route('password.email'), ['correo' => $usuario->correo])
            ->assertRedirect()
            ->assertSessionHas('status');

        $contrasenaTemporal = null;
        Notification::assertSentTo($usuario, TemporaryPasswordNotification::class, function (TemporaryPasswordNotification $notification) use (&$contrasenaTemporal): bool {
            $contrasenaTemporal = $notification->password;

            return true;
        });
        $this->assertNotNull($contrasenaTemporal);
        $this->assertTrue(password_verify($contrasenaTemporal, $usuario->fresh()->contrasena));
        $this->assertTrue($usuario->fresh()->debe_cambiar_contrasena);

        $this->post(route('login.store'), [
            'usuario' => $usuario->correo,
            'contrasena' => $contrasenaTemporal,
        ])->assertRedirect(route('password.change'));

        $this->get(route('dashboard'))->assertRedirect(route('password.change'));
        $this->get(route('password.change'))
            ->assertOk()
            ->assertSee('debes reemplazar la contraseña temporal');

        $this->put(route('password.change.update'), [
            'contrasena_actual' => $contrasenaTemporal,
            'contrasena' => 'NuevaClave456!',
            'contrasena_confirmation' => 'NuevaClave456!',
        ])->assertRedirect(route('dashboard'));

        $this->assertFalse($usuario->fresh()->debe_cambiar_contrasena);
        $this->assertTrue(password_verify('NuevaClave456!', $usuario->fresh()->contrasena));
    }

    public function test_authenticated_user_can_change_password_from_the_panel(): void
    {
        $usuario = Usuario::query()->create([
            'nombre' => 'Ana Ramírez',
            'correo' => 'ana@example.test',
            'contrasena' => bcrypt('anterior123'),
            'rol' => 'empleado',
            'id_tienda' => 1,
            'activo' => 1,
            'creado_en' => now()->toDateString(),
        ]);

        $this->actingAs($usuario)
            ->get(route('password.change'))
            ->assertOk()
            ->assertSee('Actualizar contraseña');

        $this->put(route('password.change.update'), [
            'contrasena_actual' => 'anterior123',
            'contrasena' => 'NuevaClave456!',
            'contrasena_confirmation' => 'NuevaClave456!',
        ])->assertRedirect(route('dashboard'));

        $this->assertTrue(password_verify('NuevaClave456!', $usuario->fresh()->contrasena));
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
            'creado_en' => now()->toDateString(),
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

    public function test_username_login_requires_exact_name_casing(): void
    {
        Usuario::query()->create([
            'nombre' => 'Ana Ramírez',
            'correo' => 'ana@example.test',
            'contrasena' => 'secreto123',
            'rol' => 'empleado',
            'id_tienda' => 1,
            'activo' => 1,
            'creado_en' => now()->toDateString(),
        ]);

        $this->post(route('login.store'), [
            'usuario' => 'ana ramírez',
            'contrasena' => 'secreto123',
        ])->assertSessionHasErrors('usuario');
        $this->assertGuest();

        $this->post(route('login.store'), [
            'usuario' => 'Ana Ramírez',
            'contrasena' => 'secreto123',
        ])->assertRedirect(route('dashboard'));
        $this->assertAuthenticated();
    }

    public function test_registration_validates_identity_and_creates_an_employee_account(): void
    {
        DB::table('tiendas')->insert(['nombre' => 'Sucursal de prueba', 'activo' => 1]);

        $response = $this->post(route('register.store'), [
            'nombre' => 'María Pérez',
            'correo' => 'maria@example.test',
            'telefono' => '2221234567',
            'contrasena' => 'secreto123',
            'contrasena_confirmation' => 'secreto123',
            'rol' => 'empleado',
        ]);

        $response->assertRedirect(route('login'));
        $this->assertDatabaseHas('usuarios', [
            'correo' => 'maria@example.test',
            'telefono' => '2221234567',
            'rol' => 'empleado',
            'id_tienda' => 1,
            'creado_en' => now()->toDateString(),
        ]);
        $this->assertTrue(password_verify('secreto123', Usuario::query()->where('correo', 'maria@example.test')->value('contrasena')));
    }

    public function test_admin_registration_requires_superadmin_approval_before_login(): void
    {
        DB::table('tiendas')->insert(['nombre' => 'Sucursal de prueba', 'activo' => 1]);

        $response = $this->post(route('register.store'), [
            'nombre' => 'Carlos Admin',
            'correo' => 'carlos@example.test',
            'contrasena' => 'secreto123',
            'contrasena_confirmation' => 'secreto123',
            'rol' => 'admin',
        ]);

        $response->assertRedirect(route('login'));
        $response->assertSessionHas('status', 'La solicitud de cuenta de administrador fue enviada. Un superadministrador debe aprobarla antes de que puedas iniciar sesión.');
        $this->assertDatabaseHas('usuarios', [
            'correo' => 'carlos@example.test',
            'rol' => 'admin',
            'activo' => 0,
        ]);

        $this->post(route('login.store'), [
            'usuario' => 'carlos@example.test',
            'contrasena' => 'secreto123',
        ])->assertSessionHasErrors('usuario');
        $this->assertGuest();
    }

    public function test_only_superadmin_can_approve_a_pending_admin_account(): void
    {
        $pendingAdmin = Usuario::query()->create([
            'nombre' => 'Carlos Admin',
            'correo' => 'carlos@example.test',
            'contrasena' => 'secreto123',
            'rol' => 'admin',
            'id_tienda' => 1,
            'activo' => 0,
            'creado_en' => now()->toDateString(),
        ]);

        $ordinaryAdmin = Usuario::query()->create([
            'nombre' => 'Ana Admin',
            'correo' => 'ana@example.test',
            'contrasena' => 'secreto123',
            'rol' => 'admin',
            'id_tienda' => 1,
            'activo' => 1,
            'creado_en' => now()->toDateString(),
        ]);

        $this->actingAs($ordinaryAdmin)
            ->post(route('usuarios.aprobar-administrador', $pendingAdmin->id_usuario))
            ->assertForbidden();
        $this->assertDatabaseHas('usuarios', ['id_usuario' => $pendingAdmin->id_usuario, 'activo' => 0]);

        $superadmin = Usuario::query()->create([
            'nombre' => 'Super Administrador',
            'correo' => 'superadmin@example.test',
            'contrasena' => 'secreto123',
            'rol' => 'superadmin',
            'id_tienda' => 1,
            'activo' => 1,
            'creado_en' => now()->toDateString(),
        ]);

        $this->actingAs($superadmin)
            ->post(route('usuarios.aprobar-administrador', $pendingAdmin->id_usuario))
            ->assertRedirect(route('usuarios'));

        $this->assertDatabaseHas('usuarios', ['id_usuario' => $pendingAdmin->id_usuario, 'activo' => 1]);
    }

    public function test_public_registration_cannot_assign_superadmin_role(): void
    {
        DB::table('tiendas')->insert(['nombre' => 'Sucursal de prueba', 'activo' => 1]);

        $this->post(route('register.store'), [
            'nombre' => 'Carlos Admin',
            'correo' => 'carlos@example.test',
            'contrasena' => 'secreto123',
            'contrasena_confirmation' => 'secreto123',
            'rol' => 'superadmin',
        ])->assertSessionHasErrors('rol');

        $this->assertDatabaseMissing('usuarios', ['correo' => 'carlos@example.test']);
    }
}
