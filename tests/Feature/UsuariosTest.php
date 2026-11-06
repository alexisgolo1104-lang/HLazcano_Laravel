<?php

namespace Tests\Feature;

use App\Models\Usuario;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class UsuariosTest extends TestCase
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
            $table->string('telefono', 20)->nullable();
            $table->string('contrasena', 255);
            $table->decimal('sueldo', 12, 2)->nullable();
            $table->string('rol', 100);
            $table->unsignedInteger('id_tienda');
            $table->boolean('activo')->default(true);
            $table->date('ultimo_acceso')->nullable();
            $table->string('ip_ultimo', 45)->nullable();
            $table->date('creado_en');
        });
        Schema::create('equipo_trabajo', function (Blueprint $table) {
            $table->increments('id_empleado');
            $table->string('nombre', 120);
            $table->string('puesto', 100);
            $table->string('telefono', 20)->nullable();
            $table->decimal('sueldo', 12, 2)->nullable();
            $table->unsignedInteger('id_tienda');
            $table->boolean('activo')->default(true);
            $table->date('creado_en');
            $table->date('actualizado_en')->nullable();
        });

        DB::table('tiendas')->insert([
            ['id_tienda' => 1, 'nombre' => 'Sucursal Uno', 'activo' => 1],
            ['id_tienda' => 2, 'nombre' => 'Sucursal Dos', 'activo' => 1],
        ]);
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('usuarios');
        Schema::dropIfExists('equipo_trabajo');
        Schema::dropIfExists('tiendas');

        parent::tearDown();
    }

    public function test_admin_can_edit_employee_identity_without_changing_protected_account_data(): void
    {
        $admin = $this->createUser('admin', 1);
        $employee = $this->createUser('empleado', 1, [
            'nombre' => 'Ana Pérez',
            'correo' => 'ana@example.test',
            'contrasena' => 'password-original',
        ]);

        $this->actingAs($admin)
            ->get(route('usuarios.edit', $employee->id_usuario))
            ->assertOk()
            ->assertSee('Editar cuenta: Ana Pérez');
        $this->actingAs($admin)
            ->get(route('usuarios'))
            ->assertOk()
            ->assertSee(route('usuarios.edit', $employee->id_usuario))
            ->assertSee(route('usuarios.destroy', $employee->id_usuario));

        $this->actingAs($admin)
            ->put(route('usuarios.update', $employee->id_usuario), [
                'nombre' => 'Ana López',
                'correo' => 'ana.lopez@example.test',
                'telefono' => '2221234567',
                'sueldo' => 14500.50,
                'contrasena' => 'nueva-clave',
                'contrasena_confirmation' => 'nueva-clave',
                'rol' => 'superadmin',
                'id_tienda' => 2,
                'activo' => 0,
            ])
            ->assertRedirect(route('usuarios'));

        $this->assertDatabaseHas('usuarios', [
            'id_usuario' => $employee->id_usuario,
            'nombre' => 'Ana López',
            'correo' => 'ana.lopez@example.test',
            'telefono' => '2221234567',
            'sueldo' => 14500.50,
            'rol' => 'empleado',
            'id_tienda' => 1,
            'activo' => 1,
        ]);
        $this->assertTrue(password_verify('nueva-clave', $employee->fresh()->contrasena));
    }

    public function test_superadmin_can_edit_own_profile_and_cannot_deactivate_self(): void
    {
        $superadmin = $this->createUser('superadmin', 1, [
            'nombre' => 'Super Admin',
            'correo' => 'superadmin@example.test',
        ]);

        $this->actingAs($superadmin)
            ->get(route('usuarios'))
            ->assertOk()
            ->assertSee(route('usuarios.edit', $superadmin->id_usuario));

        $this->actingAs($superadmin)
            ->get(route('usuarios.edit', $superadmin->id_usuario))
            ->assertOk()
            ->assertSee('Cambiar contraseña');

        $this->actingAs($superadmin)
            ->put(route('usuarios.update', $superadmin->id_usuario), [
                'nombre' => 'Super Admin Actualizado',
                'correo' => 'superadmin.actualizado@example.test',
                'telefono' => '2227654321',
            ])
            ->assertRedirect(route('usuarios'));

        $this->assertDatabaseHas('usuarios', [
            'id_usuario' => $superadmin->id_usuario,
            'nombre' => 'Super Admin Actualizado',
            'correo' => 'superadmin.actualizado@example.test',
            'telefono' => '2227654321',
            'rol' => 'superadmin',
            'activo' => 1,
        ]);

        $this->actingAs($superadmin)
            ->delete(route('usuarios.destroy', $superadmin->id_usuario))
            ->assertNotFound();
        $this->assertDatabaseHas('usuarios', [
            'id_usuario' => $superadmin->id_usuario,
            'activo' => 1,
        ]);
    }

    public function test_admin_can_deactivate_an_employee_account_without_deleting_it(): void
    {
        $admin = $this->createUser('admin', 1);
        $employee = $this->createUser('empleado', 1, ['nombre' => 'Empleado activo']);

        $this->actingAs($admin)
            ->delete(route('usuarios.destroy', $employee->id_usuario))
            ->assertRedirect(route('usuarios'));

        $this->assertDatabaseHas('usuarios', [
            'id_usuario' => $employee->id_usuario,
            'nombre' => 'Empleado activo',
            'activo' => 0,
        ]);

        $this->actingAs($admin)
            ->get(route('usuarios'))
            ->assertOk()
            ->assertSee('Desactivado');
    }

    public function test_admin_can_register_edit_and_deactivate_team_member_without_account(): void
    {
        $admin = $this->createUser('admin', 1);

        $this->actingAs($admin)
            ->get(route('usuarios'))
            ->assertOk()
            ->assertSee('+ Registrar integrante')
            ->assertDontSee('Registrar integrante sin cuenta del sistema');

        $this->get(route('equipo.create'))
            ->assertOk()
            ->assertSee('Registrar integrante sin cuenta del sistema');

        $this->post(route('equipo.store'), [
            'nombre' => 'Luis Torres',
            'puesto' => 'Montacarguista',
            'telefono' => '2221234567',
            'sueldo' => 12500,
        ])->assertRedirect(route('usuarios'));

        $memberId = DB::table('equipo_trabajo')->value('id_empleado');
        $this->assertDatabaseHas('equipo_trabajo', [
            'id_empleado' => $memberId,
            'nombre' => 'Luis Torres',
            'puesto' => 'Montacarguista',
            'sueldo' => 12500,
            'id_tienda' => 1,
            'activo' => 1,
        ]);

        $this->get(route('equipo.edit', $memberId))
            ->assertOk()
            ->assertSee('Editar integrante: Luis Torres');

        $this->put(route('equipo.update', $memberId), [
            'nombre' => 'Luis Torres',
            'puesto' => 'Repartidor',
            'telefono' => '2227654321',
            'sueldo' => 13200,
        ])->assertRedirect(route('usuarios'));

        $this->assertDatabaseHas('equipo_trabajo', [
            'id_empleado' => $memberId,
            'puesto' => 'Repartidor',
            'sueldo' => 13200,
        ]);

        $this->delete(route('equipo.destroy', $memberId))
            ->assertRedirect(route('usuarios'));
        $this->assertDatabaseHas('equipo_trabajo', ['id_empleado' => $memberId, 'activo' => 0]);
    }

    public function test_team_members_are_scoped_to_store_and_employees_cannot_manage_them(): void
    {
        $admin = $this->createUser('admin', 1);
        $employee = $this->createUser('empleado', 1);
        DB::table('equipo_trabajo')->insert([
            'nombre' => 'Persona de otra sucursal',
            'puesto' => 'Cargador',
            'id_tienda' => 2,
            'activo' => 1,
            'creado_en' => now()->toDateString(),
        ]);
        $otherStoreMemberId = DB::table('equipo_trabajo')->value('id_empleado');

        $this->actingAs($admin)
            ->get(route('equipo.edit', $otherStoreMemberId))
            ->assertNotFound();
        $this->actingAs($admin)
            ->delete(route('equipo.destroy', $otherStoreMemberId))
            ->assertNotFound();
        $this->actingAs($employee)
            ->post(route('equipo.store'), [
                'nombre' => 'No autorizado',
                'puesto' => 'Cargador',
            ])
            ->assertForbidden();

        $this->assertDatabaseHas('equipo_trabajo', ['id_empleado' => $otherStoreMemberId, 'activo' => 1]);
    }

    public function test_admin_cannot_manage_admin_or_superadmin_accounts(): void
    {
        $admin = $this->createUser('admin', 1);
        $otherAdmin = $this->createUser('admin', 1);
        $superadmin = $this->createUser('superadmin', 1);

        foreach ([$otherAdmin, $superadmin] as $protectedUser) {
            $this->actingAs($admin)
                ->get(route('usuarios.edit', $protectedUser->id_usuario))
                ->assertNotFound();
            $this->actingAs($admin)
                ->put(route('usuarios.update', $protectedUser->id_usuario), [
                    'nombre' => 'Cambio no autorizado',
                    'correo' => 'no-autorizado@example.test',
                ])
                ->assertNotFound();
            $this->actingAs($admin)
                ->delete(route('usuarios.destroy', $protectedUser->id_usuario))
                ->assertNotFound();
        }

        $this->assertDatabaseHas('usuarios', [
            'id_usuario' => $otherAdmin->id_usuario,
            'activo' => 1,
        ]);
        $this->assertDatabaseHas('usuarios', [
            'id_usuario' => $superadmin->id_usuario,
            'activo' => 1,
        ]);
    }

    public function test_users_cannot_manage_accounts_from_another_store_and_employees_cannot_open_module(): void
    {
        $admin = $this->createUser('admin', 1);
        $employee = $this->createUser('empleado', 2);

        $this->actingAs($admin)
            ->get(route('usuarios.edit', $employee->id_usuario))
            ->assertNotFound();
        $this->actingAs($admin)
            ->delete(route('usuarios.destroy', $employee->id_usuario))
            ->assertNotFound();

        $employeeInStore = $this->createUser('empleado', 1);
        $this->actingAs($employeeInStore)
            ->get(route('usuarios'))
            ->assertForbidden();
        $this->actingAs($employeeInStore)
            ->put(route('usuarios.update', $employeeInStore->id_usuario), [
                'nombre' => 'Cambio no autorizado',
                'correo' => 'employee@example.test',
            ])
            ->assertForbidden();
    }

    private function createUser(string $role, int $storeId, array $attributes = []): Usuario
    {
        return Usuario::query()->create(array_merge([
            'nombre' => 'Usuario de prueba',
            'correo' => uniqid('usuario-', true).'@example.test',
            'telefono' => null,
            'contrasena' => 'hash-original',
            'rol' => $role,
            'id_tienda' => $storeId,
            'activo' => true,
            'creado_en' => now()->toDateString(),
        ], $attributes));
    }
}
