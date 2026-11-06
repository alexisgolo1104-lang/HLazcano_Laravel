<?php

namespace Tests\Feature;

use App\Models\Usuario;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class EgresosTest extends TestCase
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
                $table->decimal('sueldo', 12, 2)->nullable();
            $table->string('rol');
            $table->unsignedInteger('id_tienda');
            $table->boolean('activo')->default(true);
        });
        Schema::create('gastos', function (Blueprint $table) {
            $table->increments('id_gastos');
            $table->string('concepto');
            $table->string('categoria')->nullable();
            $table->decimal('monto', 12, 2);
            $table->unsignedInteger('id_tienda');
            $table->unsignedInteger('id_usuario');
            $table->unsignedInteger('id_empleado')->nullable();
            $table->string('origen_empleado', 20)->nullable();
            $table->string('notas')->nullable();
            $table->date('fecha');
        });
        Schema::create('compras', function (Blueprint $table) {
            $table->increments('id_compra');
            $table->string('folio')->nullable();
            $table->unsignedInteger('id_proveedor')->nullable();
            $table->unsignedInteger('id_usuario')->nullable();
            $table->unsignedInteger('id_tienda');
            $table->decimal('total', 12, 2)->nullable();
            $table->date('fecha');
        });
        Schema::create('proveedores', function (Blueprint $table) {
            $table->increments('id_proveedor');
            $table->string('nombre');
        });
        Schema::create('equipo_trabajo', function (Blueprint $table) {
            $table->increments('id_empleado');
            $table->string('nombre');
            $table->string('puesto');
            $table->string('telefono')->nullable();
            $table->decimal('sueldo', 12, 2)->nullable();
            $table->unsignedInteger('id_tienda');
            $table->boolean('activo')->default(true);
            $table->date('creado_en');
            $table->date('actualizado_en')->nullable();
        });
        DB::table('tiendas')->insert(['id_tienda' => 1, 'nombre' => 'Sucursal principal']);
    }

    protected function tearDown(): void
    {
        foreach (['compras', 'proveedores', 'equipo_trabajo', 'gastos', 'usuarios', 'tiendas'] as $table) {
            Schema::dropIfExists($table);
        }

        parent::tearDown();
    }

    public function test_only_superadmin_can_view_or_register_expenses(): void
    {
        $admin = $this->createUser('admin');

        $this->actingAs($admin)
            ->get(route('egresos'))
            ->assertForbidden();

        $this->post(route('egresos.store'), [
            'categoria' => 'servicio_luz',
            'concepto' => 'Recibo de luz',
            'monto' => 850,
            'fecha' => now()->toDateString(),
        ])->assertForbidden();

        $this->assertSame(0, DB::table('gastos')->count());
    }

    public function test_superadmin_records_service_and_employee_salary_and_totals_include_purchases(): void
    {
        $superadmin = $this->createUser('superadmin');
        $employee = $this->createUser('empleado');
        $admin = $this->createUser('admin');
        $employee->sueldo = 200;
        $employee->save();
        $admin->sueldo = 300;
        $admin->save();
        DB::table('equipo_trabajo')->insert([
            'id_empleado' => 1,
            'nombre' => 'Luis Torres',
            'puesto' => 'Montacarguista',
            'sueldo' => 400,
            'id_tienda' => 1,
            'activo' => true,
            'creado_en' => now()->toDateString(),
        ]);
        $externalMember = DB::table('equipo_trabajo')->where('id_empleado', 1)->first();
        DB::table('proveedores')->insert(['id_proveedor' => 1, 'nombre' => 'Proveedor']);
        DB::table('compras')->insert([
            'id_compra' => 1,
            'folio' => 'C-001',
            'id_proveedor' => 1,
            'id_usuario' => $superadmin->id_usuario,
            'id_tienda' => 1,
            'total' => 450,
            'fecha' => now()->toDateString(),
        ]);

        $this->actingAs($superadmin)
            ->get(route('egresos'))
            ->assertOk()
            ->assertSee('Compras registradas automáticamente')
            ->assertSee('Empleado o integrante del equipo')
            ->assertSee('empleado · Empleado · Con cuenta')
            ->assertSee('admin · Administrador · Con cuenta')
            ->assertSee('Luis Torres · Montacarguista · Sin cuenta')
            ->assertSee('data-sueldo="200.00"', false)
            ->assertSee('data-sueldo="300.00"', false)
            ->assertSee('data-sueldo="400.00"', false)
            ->assertDontSee('superadmin · Superadministrador')
            ->assertSee('$450.00')
            ->assertSee('$450.00');

        $this->from(route('egresos'))->post(route('egresos.store'), [
            'categoria' => 'servicio_luz',
            'concepto' => 'Recibo de luz',
            'monto' => 100,
            'fecha' => now()->toDateString(),
            'destinatario' => 'usuario:'.$employee->id_usuario,
        ])->assertSessionHasErrors('destinatario');

        $this->post(route('egresos.store'), [
            'categoria' => 'servicio_luz',
            'concepto' => 'Recibo de luz',
            'monto' => 100,
            'fecha' => now()->toDateString(),
        ])->assertRedirect();

        $this->post(route('egresos.store'), [
            'categoria' => 'sueldo',
            'concepto' => 'Nómina quincenal',
            'monto' => 250,
            'fecha' => now()->toDateString(),
            'destinatario' => 'usuario:'.$employee->id_usuario,
        ])->assertRedirect();

        $this->post(route('egresos.store'), [
            'categoria' => 'sueldo',
            'concepto' => 'Sueldo administrativo',
            'monto' => 300,
            'fecha' => now()->toDateString(),
            'destinatario' => 'usuario:'.$admin->id_usuario,
        ])->assertRedirect();

        $this->post(route('egresos.store'), [
            'categoria' => 'sueldo',
            'concepto' => 'Sueldo montacarguista',
            'monto' => 400,
            'fecha' => now()->toDateString(),
            'destinatario' => 'equipo:'.$externalMember->id_empleado,
        ])->assertRedirect();

        $this->post(route('egresos.store'), [
            'categoria' => 'otros_gastos',
            'concepto' => 'Reparación de estantería',
            'monto' => 75,
            'fecha' => now()->toDateString(),
        ])->assertRedirect();

        $this->assertDatabaseHas('gastos', [
            'concepto' => 'Nómina quincenal',
            'categoria' => 'sueldo',
            'monto' => 250,
            'id_empleado' => $employee->id_usuario,
            'origen_empleado' => 'usuario',
            'id_usuario' => $superadmin->id_usuario,
        ]);
        $this->assertDatabaseHas('gastos', [
            'concepto' => 'Sueldo administrativo',
            'categoria' => 'sueldo',
            'id_empleado' => $admin->id_usuario,
            'origen_empleado' => 'usuario',
        ]);
        $this->assertDatabaseHas('gastos', [
            'concepto' => 'Sueldo montacarguista',
            'categoria' => 'sueldo',
            'id_empleado' => $externalMember->id_empleado,
            'origen_empleado' => 'equipo',
        ]);

        $this->get(route('egresos'))
            ->assertOk()
            ->assertSee('Otros gastos')
            ->assertSee('$75.00')
            ->assertSee('$100.00')
            ->assertSee('$950.00')
            ->assertSee('$1,575.00')
            ->assertSee('Nómina quincenal')
            ->assertSee('empleado')
            ->assertSee('Luis Torres')
            ->assertSee('Reparación de estantería');
    }

    private function createUser(string $role): Usuario
    {
        return Usuario::query()->create([
            'nombre' => $role,
            'correo' => $role.'@example.test',
            'contrasena' => 'hash',
            'rol' => $role,
            'id_tienda' => 1,
            'activo' => true,
        ]);
    }
}