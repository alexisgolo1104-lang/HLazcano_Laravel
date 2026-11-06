<?php

namespace Tests\Feature;

use App\Models\Usuario;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class CatalogosProveedoresTest extends TestCase
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
            $table->string('correo', 100);
            $table->string('telefono', 20)->nullable();
            $table->string('contrasena', 255);
            $table->string('rol', 100);
            $table->unsignedInteger('id_tienda');
            $table->boolean('activo')->default(true);
            $table->date('creado_en');
        });
        Schema::create('tipos_hilo', function (Blueprint $table) {
            $table->increments('id_tipo');
            $table->string('nombre', 80);
            $table->string('descripcion', 200)->nullable();
            $table->boolean('activo')->default(true);
            $table->date('creado_en');
        });
        Schema::create('colores', function (Blueprint $table) {
            $table->increments('id_color');
            $table->string('nombre', 60);
            $table->char('codigo_hex', 7)->nullable();
            $table->boolean('activo')->default(true);
            $table->date('creado_en');
        });
        Schema::create('anaqueles', function (Blueprint $table) {
            $table->increments('id_anaquel');
            $table->string('codigo', 20);
            $table->string('descripcion', 150)->nullable();
            $table->unsignedInteger('id_tienda');
            $table->boolean('activo')->default(true);
        });
        Schema::create('catalogo_productos', function (Blueprint $table) {
            $table->increments('id_catalogo');
            $table->string('nombre', 160);
            $table->unsignedInteger('id_tipo');
            $table->unsignedInteger('id_color');
            $table->unsignedInteger('id_unidad');
            $table->string('presentacion', 40)->nullable();
            $table->string('descripcion', 150)->nullable();
            $table->boolean('activo')->default(true);
            $table->date('creado_en');
        });
        Schema::create('unidades', function (Blueprint $table) {
            $table->increments('id_unidad');
            $table->string('codigo', 20);
            $table->string('nombre', 60);
        });
        Schema::create('productos', function (Blueprint $table) {
            $table->increments('id_producto');
            $table->unsignedInteger('id_catalogo');
            $table->unsignedInteger('id_tipo')->nullable();
            $table->unsignedInteger('id_color')->nullable();
            $table->unsignedInteger('id_unidad')->nullable();
            $table->unsignedInteger('id_anaquel')->nullable();
            $table->unsignedInteger('id_tienda');
            $table->string('presentacion')->nullable();
            $table->decimal('stock_actual', 10, 2)->default(0);
            $table->decimal('stock_minimo', 10, 2)->default(0);
            $table->decimal('precio_venta', 10, 2)->nullable();
            $table->boolean('activo')->default(true);
        });
        Schema::create('proveedores', function (Blueprint $table) {
            $table->increments('id_proveedor');
            $table->string('nombre', 120);
            $table->string('telefono', 20)->nullable();
            $table->string('correo', 100)->nullable();
            $table->string('direccion', 200)->nullable();
            $table->string('notas', 150)->nullable();
            $table->boolean('activo')->default(true);
            $table->date('creado_en');
        });
        DB::table('tiendas')->insert(['id_tienda' => 1, 'nombre' => 'Tienda principal']);
    }

    protected function tearDown(): void
    {
        foreach (['proveedores', 'productos', 'catalogo_productos', 'unidades', 'anaqueles', 'colores', 'tipos_hilo', 'usuarios', 'tiendas'] as $table) {
            Schema::dropIfExists($table);
        }

        parent::tearDown();
    }

    public function test_admin_can_create_and_edit_catalog_records_and_search_current_section(): void
    {
        $admin = $this->createUser('admin');
        $this->actingAs($admin)
            ->get(route('catalogos', ['seccion' => 'tipos']))
            ->assertOk()
            ->assertSee('Tipos de hilo')
            ->assertSee('Colores')
            ->assertSee('Anaqueles')
            ->assertSee('Unidades')
            ->assertSee('catalog-search')
            ->assertSee('Nuevo tipo de hilo');

        $this->post(route('catalogos.store', 'tipos'), [
            'nombre' => 'Lino',
            'descripcion' => 'Fibra natural',
        ])->assertRedirect(route('catalogos', ['seccion' => 'tipos']));
        $typeId = DB::table('tipos_hilo')->where('nombre', 'Lino')->value('id_tipo');

        $this->get(route('catalogos.edit', ['seccion' => 'tipos', 'id' => $typeId]))
            ->assertOk()
            ->assertSee('Editar tipo de hilo')
            ->assertSee('Lino');
        $this->put(route('catalogos.update', ['seccion' => 'tipos', 'id' => $typeId]), [
            'nombre' => 'Lino fino',
            'descripcion' => 'Fibra natural suave',
        ])->assertRedirect(route('catalogos', ['seccion' => 'tipos']));
        $this->assertDatabaseHas('tipos_hilo', ['id_tipo' => $typeId, 'nombre' => 'Lino fino']);

        $this->post(route('catalogos.store', 'colores'), [
            'nombre' => 'Turquesa',
            'codigo_hex' => '#20B2AA',
        ])->assertRedirect(route('catalogos', ['seccion' => 'colores']));
        $this->post(route('catalogos.store', 'unidades'), [
            'nombre' => 'Metro',
            'codigo' => 'm',
        ])->assertRedirect(route('catalogos', ['seccion' => 'unidades']));

        $this->assertDatabaseHas('colores', ['nombre' => 'Turquesa', 'codigo_hex' => '#20B2AA']);
        $this->assertDatabaseHas('unidades', ['nombre' => 'Metro', 'codigo' => 'm']);
    }

    public function test_admin_can_add_catalog_product_and_select_it_in_inventory(): void
    {
        $admin = $this->createUser('admin');
        DB::table('tipos_hilo')->insert([
            'nombre' => 'Algodón',
            'activo' => 1,
            'creado_en' => now()->toDateString(),
        ]);
        DB::table('colores')->insert([
            'nombre' => 'Azul',
            'codigo_hex' => '#0000FF',
            'activo' => 1,
            'creado_en' => now()->toDateString(),
        ]);
        DB::table('unidades')->insert(['nombre' => 'Kilogramo', 'codigo' => 'kg']);

        $typeId = DB::table('tipos_hilo')->value('id_tipo');
        $colorId = DB::table('colores')->value('id_color');
        $unitId = DB::table('unidades')->value('id_unidad');

        $this->actingAs($admin)->get(route('catalogos', ['seccion' => 'productos']))
            ->assertOk()
            ->assertSee('Nuevo producto');
        $this->actingAs($admin)->get(route('catalogos', ['seccion' => 'productos', 'nuevo' => 1]))
            ->assertOk()
            ->assertSee('Nombre del producto')
            ->assertSee('Tipo de hilo')
            ->assertSee('Color')
            ->assertSee('Unidad');

        $this->actingAs($admin)->post(route('catalogos.store', 'productos'), [
            'nombre' => 'Hilo azul de algodón',
            'id_tipo' => $typeId,
            'id_color' => $colorId,
            'id_unidad' => $unitId,
        ])->assertRedirect(route('catalogos', ['seccion' => 'productos']));

        $this->assertDatabaseHas('catalogo_productos', [
            'nombre' => 'Hilo azul de algodón',
            'id_tipo' => $typeId,
            'id_color' => $colorId,
            'id_unidad' => $unitId,
            'activo' => 1,
        ]);
        $catalogId = DB::table('catalogo_productos')->where('nombre', 'Hilo azul de algodón')->value('id_catalogo');
        $this->actingAs($admin)->get(route('catalogos', ['seccion' => 'productos']))
            ->assertOk()
            ->assertSee('Editar')
            ->assertSee('Desactivar');
        $this->actingAs($admin)->get(route('catalogos.edit', ['seccion' => 'productos', 'id' => $catalogId]))
            ->assertOk()
            ->assertSee('Editar producto del catálogo')
            ->assertSee('Hilo azul de algodón')
            ->assertSee('value="'.$typeId.'" selected', false);
        $this->actingAs($admin)->put(route('catalogos.update', ['seccion' => 'productos', 'id' => $catalogId]), [
            'nombre' => 'Hilo azul de algodón premium',
            'id_tipo' => $typeId,
            'id_color' => $colorId,
            'id_unidad' => $unitId,
        ])->assertRedirect(route('catalogos', ['seccion' => 'productos']));
        $this->assertDatabaseHas('catalogo_productos', [
            'id_catalogo' => $catalogId,
            'nombre' => 'Hilo azul de algodón premium',
        ]);
        $this->actingAs($admin)->get(route('inventario.create'))
            ->assertOk()
            ->assertSee('Hilo azul de algodón premium');

        $this->actingAs($admin)->patch(route('catalogos.productos.estado', $catalogId))
            ->assertRedirect(route('catalogos', ['seccion' => 'productos']));
        $this->assertDatabaseHas('catalogo_productos', ['id_catalogo' => $catalogId, 'activo' => 0]);
        $this->actingAs($admin)->get(route('inventario.create'))
            ->assertOk()
            ->assertDontSee('Hilo azul de algodón premium');
    }

    public function test_anaqueles_are_scoped_to_the_current_store_and_catalogs_are_role_protected(): void
    {
        $admin = $this->createUser('admin');
        $employee = $this->createUser('empleado');

        $this->actingAs($admin)->post(route('catalogos.store', 'anaqueles'), [
            'codigo' => 'A-1',
            'descripcion' => 'Anaquel de prueba',
            'id_tienda' => 999,
        ])->assertRedirect(route('catalogos', ['seccion' => 'anaqueles']));
        $this->assertDatabaseHas('anaqueles', ['codigo' => 'A-1', 'id_tienda' => 1]);

        $shelfId = DB::table('anaqueles')->where('codigo', 'A-1')->value('id_anaquel');
        DB::table('anaqueles')->where('id_anaquel', $shelfId)->delete();
        $this->actingAs($admin)
            ->get(route('catalogos.edit', ['seccion' => 'anaqueles', 'id' => $shelfId]))
            ->assertRedirect(route('catalogos', ['seccion' => 'anaqueles']))
            ->assertSessionHas('status', 'Ese anaquel ya no existe. Puedes registrar uno nuevo.');

        $this->actingAs($admin)->post(route('catalogos.store', 'anaqueles'), [
            'codigo' => 'A-1',
            'descripcion' => 'Anaquel de prueba',
            'id_tienda' => 999,
        ])->assertRedirect(route('catalogos', ['seccion' => 'anaqueles']));
        $shelfId = DB::table('anaqueles')->where('codigo', 'A-1')->value('id_anaquel');
        DB::table('tipos_hilo')->insert([
            'id_tipo' => 1,
            'nombre' => 'Algodón',
            'activo' => 1,
            'creado_en' => now()->toDateString(),
        ]);
        DB::table('colores')->insert([
            'id_color' => 1,
            'nombre' => 'Azul',
            'codigo_hex' => '#0000FF',
            'activo' => 1,
            'creado_en' => now()->toDateString(),
        ]);
        DB::table('catalogo_productos')->insert([
            'id_catalogo' => 1,
            'nombre' => 'Hilo de prueba',
            'id_tipo' => 1,
            'id_color' => 1,
            'id_unidad' => 1,
            'activo' => 1,
            'creado_en' => now()->toDateString(),
        ]);
        DB::table('productos')->insert([
            'id_catalogo' => 1,
            'id_tipo' => 1,
            'id_color' => 1,
            'id_anaquel' => $shelfId,
            'id_tienda' => 1,
            'presentacion' => '100g',
            'stock_actual' => 6,
            'stock_minimo' => 2,
            'precio_venta' => 45,
            'activo' => 1,
        ]);
        $this->actingAs($admin)->get(route('localizador', ['anaquel' => $shelfId]))
            ->assertOk()
            ->assertSee('Plano visual')
            ->assertSee('A-1')
            ->assertSee('Zona A')
            ->assertSee('Hilo de prueba')
            ->assertSee('stock_actual')
            ->assertSee('100g');

        $this->actingAs($employee)->get(route('catalogos'))->assertForbidden();
        $this->actingAs($employee)->put(route('catalogos.update', ['seccion' => 'anaqueles', 'id' => $shelfId]), [
            'codigo' => 'No autorizado',
        ])->assertForbidden();
    }

    public function test_suppliers_are_managed_in_separate_admin_only_module(): void
    {
        $admin = $this->createUser('admin');
        $employee = $this->createUser('empleado');

        $this->actingAs($admin)->get(route('proveedores'))
            ->assertOk()
            ->assertSee('Registrar proveedor')
            ->assertSee('supplier-search');
        $this->post(route('proveedores.store'), [
            'nombre' => 'Distribuidora de prueba',
            'correo' => 'ventas@example.test',
            'telefono' => '5551234567',
        ])->assertRedirect(route('proveedores'));

        $supplierId = DB::table('proveedores')->where('nombre', 'Distribuidora de prueba')->value('id_proveedor');
        $this->actingAs($admin)->patch(route('proveedores.estado', $supplierId))->assertRedirect(route('proveedores'));
        $this->assertDatabaseHas('proveedores', ['id_proveedor' => $supplierId, 'activo' => 0]);

        $this->actingAs($employee)->get(route('proveedores'))->assertForbidden();
        $this->actingAs($employee)->post(route('proveedores.store'), ['nombre' => 'No autorizado'])->assertForbidden();
    }

    private function createUser(string $role): Usuario
    {
        return Usuario::query()->create([
            'nombre' => 'Usuario de prueba',
            'correo' => uniqid('usuario-', true).'@example.test',
            'contrasena' => 'hash-original',
            'rol' => $role,
            'id_tienda' => 1,
            'activo' => true,
            'creado_en' => now()->toDateString(),
        ]);
    }
}
