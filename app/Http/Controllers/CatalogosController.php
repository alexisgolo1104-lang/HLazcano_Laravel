<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class CatalogosController extends Controller
{
    private const SECCIONES = ['tipos', 'colores', 'anaqueles', 'unidades'];

    public function index(Request $request): View
    {
        $this->authorizeCatalogManagement();
        $seccion = $request->query('seccion', 'tipos');
        abort_unless(in_array($seccion, [...self::SECCIONES, 'productos'], true), 404);

        return view('panel.catalogos', [
            'seccion' => $seccion,
            'registros' => $this->registros($seccion),
            'registroEditar' => null,
            'mostrarFormulario' => $request->boolean('nuevo'),
            'configuracion' => $this->configuracion($seccion),
            'planoAnaqueles' => $seccion === 'anaqueles' ? $this->planoAnaqueles() : collect(),
            'conteosPlano' => $seccion === 'anaqueles' ? $this->conteosPlano() : collect(),
            'tiposProducto' => $seccion === 'productos' ? DB::table('tipos_hilo')->where('activo', 1)->orderBy('nombre')->get(['id_tipo', 'nombre']) : collect(),
            'coloresProducto' => $seccion === 'productos' ? DB::table('colores')->where('activo', 1)->orderBy('nombre')->get(['id_color', 'nombre']) : collect(),
            'unidadesProducto' => $seccion === 'productos' ? DB::table('unidades')->orderBy('nombre')->get(['id_unidad', 'nombre', 'codigo']) : collect(),
        ]);
    }

    public function edit(string $seccion, int $id): View|RedirectResponse
    {
        $this->authorizeCatalogManagement();
        abort_unless(in_array($seccion, [...self::SECCIONES, 'productos'], true), 404);
        $registro = $this->registroQuery($seccion, $id)->first();
        if (! $registro && $seccion === 'anaqueles') {
            return redirect()
                ->route('catalogos', ['seccion' => 'anaqueles'])
                ->with('status', 'Ese anaquel ya no existe. Puedes registrar uno nuevo.');
        }
        abort_if(! $registro, 404);

        return view('panel.catalogos', [
            'seccion' => $seccion,
            'registros' => $this->registros($seccion),
            'registroEditar' => $registro,
            'mostrarFormulario' => true,
            'configuracion' => $this->configuracion($seccion),
            'planoAnaqueles' => $seccion === 'anaqueles' ? $this->planoAnaqueles() : collect(),
            'conteosPlano' => $seccion === 'anaqueles' ? $this->conteosPlano() : collect(),
            'tiposProducto' => $seccion === 'productos' ? DB::table('tipos_hilo')->where('activo', 1)->orderBy('nombre')->get(['id_tipo', 'nombre']) : collect(),
            'coloresProducto' => $seccion === 'productos' ? DB::table('colores')->where('activo', 1)->orderBy('nombre')->get(['id_color', 'nombre']) : collect(),
            'unidadesProducto' => $seccion === 'productos' ? DB::table('unidades')->orderBy('nombre')->get(['id_unidad', 'nombre', 'codigo']) : collect(),
        ]);
    }

    public function store(Request $request, string $seccion): RedirectResponse
    {
        $this->authorizeCatalogManagement();
        if ($seccion === 'productos') {
            $data = $this->validateCatalogProduct($request);

            DB::table('catalogo_productos')->insert($data + [
                'presentacion' => null,
                'descripcion' => null,
                'activo' => 1,
                'creado_en' => now()->toDateString(),
            ]);

            return redirect()->route('catalogos', ['seccion' => 'productos'])
                ->with('status', 'Producto agregado al catálogo. Ya está disponible para registrarlo en Inventario.');
        }
        abort_unless(in_array($seccion, self::SECCIONES, true), 404);

        $data = $this->validateRegistro($request, $seccion);
        if (in_array($seccion, ['tipos', 'colores'], true)) {
            $data['creado_en'] = now()->toDateString();
        }
        if ($seccion === 'anaqueles') {
            $data['id_tienda'] = Auth::user()->id_tienda;
            $data['activo'] = 1;
        }
        if (in_array($seccion, ['tipos', 'colores'], true)) {
            $data['activo'] = 1;
        }

        DB::table($this->tabla($seccion))->insert($data);

        return redirect()->route('catalogos', ['seccion' => $seccion])->with('status', 'Registro creado correctamente.');
    }

    public function update(Request $request, string $seccion, int $id): RedirectResponse
    {
        $this->authorizeCatalogManagement();
        if ($seccion === 'productos') {
            $catalogo = DB::table('catalogo_productos')->where('id_catalogo', $id)->where('activo', 1)->first();
            abort_if(! $catalogo, 404);

            DB::table('catalogo_productos')
                ->where('id_catalogo', $id)
                ->update($this->validateCatalogProduct($request, $id));

            return redirect()->route('catalogos', ['seccion' => 'productos'])
                ->with('status', 'Producto del catálogo actualizado correctamente.');
        }
        abort_unless(in_array($seccion, self::SECCIONES, true), 404);
        $registro = $this->registroQuery($seccion, $id)->first();
        abort_if(! $registro, 404);

        $data = $this->validateRegistro($request, $seccion, $id);
        DB::table($this->tabla($seccion))
            ->where($this->llave($seccion), $id)
            ->when($seccion === 'anaqueles', fn ($query) => $query->where('id_tienda', Auth::user()->id_tienda))
            ->update($data);

        return redirect()->route('catalogos', ['seccion' => $seccion])->with('status', 'Registro actualizado correctamente.');
    }

    public function deactivateProduct(int $id): RedirectResponse
    {
        $this->authorizeCatalogManagement();
        $updated = DB::table('catalogo_productos')
            ->where('id_catalogo', $id)
            ->where('activo', 1)
            ->update(['activo' => 0]);
        abort_if(! $updated, 404);

        return redirect()->route('catalogos', ['seccion' => 'productos'])
            ->with('status', 'Producto desactivado del catálogo. Los registros existentes en Inventario se conservaron.');
    }

    private function validateCatalogProduct(Request $request, ?int $id = null): array
    {
        $uniqueName = Rule::unique('catalogo_productos', 'nombre');
        if ($id !== null) {
            $uniqueName->ignore($id, 'id_catalogo');
        }

        return $request->validate([
            'nombre' => ['required', 'string', 'max:160', $uniqueName],
            'id_tipo' => ['required', 'integer', Rule::exists('tipos_hilo', 'id_tipo')->where('activo', 1)],
            'id_color' => ['required', 'integer', Rule::exists('colores', 'id_color')->where('activo', 1)],
            'id_unidad' => ['required', 'integer', 'exists:unidades,id_unidad'],
        ]);
    }

    private function authorizeCatalogManagement(): void
    {
        abort_unless(in_array(Auth::user()->rol, ['superadmin', 'admin'], true), 403);
    }

    private function registros(string $seccion)
    {
        $query = match ($seccion) {
            'productos' => DB::table('catalogo_productos as c')
                ->join('tipos_hilo as t', 'c.id_tipo', '=', 't.id_tipo')
                ->join('colores as co', 'c.id_color', '=', 'co.id_color')
                ->join('unidades as u', 'c.id_unidad', '=', 'u.id_unidad')
                ->where('c.activo', 1)
                ->orderBy('c.nombre')
                ->get([
                    'c.id_catalogo as id',
                    'c.nombre',
                    't.nombre as tipo',
                    'co.nombre as color',
                    'u.nombre as unidad',
                ]),
            'tipos' => DB::table('tipos_hilo as t')
                ->leftJoinSub($this->conteoProductos('id_tipo'), 'uso', 'uso.id', '=', 't.id_tipo')
                ->where('t.activo', 1)
                ->orderBy('t.nombre')
                ->get(['t.id_tipo as id', 't.nombre', 't.descripcion', 't.creado_en', DB::raw('COALESCE(uso.total, 0) as productos_usando')]),
            'colores' => DB::table('colores as c')
                ->leftJoinSub($this->conteoProductos('id_color'), 'uso', 'uso.id', '=', 'c.id_color')
                ->where('c.activo', 1)
                ->orderBy('c.nombre')
                ->get(['c.id_color as id', 'c.nombre', 'c.codigo_hex', 'c.creado_en', DB::raw('COALESCE(uso.total, 0) as productos_usando')]),
            'anaqueles' => DB::table('anaqueles as a')
                ->leftJoinSub($this->conteoProductos('id_anaquel'), 'uso', 'uso.id', '=', 'a.id_anaquel')
                ->where('a.id_tienda', Auth::user()->id_tienda)
                ->where('a.activo', 1)
                ->orderBy('a.codigo')
                ->get(['a.id_anaquel as id', 'a.codigo', 'a.descripcion', DB::raw('COALESCE(uso.total, 0) as productos_usando')]),
            'unidades' => DB::table('unidades as u')
                ->leftJoinSub($this->conteoProductos('id_unidad'), 'uso', 'uso.id', '=', 'u.id_unidad')
                ->orderBy('u.nombre')
                ->get(['u.id_unidad as id', 'u.nombre', 'u.codigo', DB::raw('COALESCE(uso.total, 0) as productos_usando')]),
        };

        return $query;
    }

    private function conteoProductos(string $columna)
    {
        return DB::table('productos')
            ->select($columna.' as id', DB::raw('COUNT(*) as total'))
            ->where('activo', 1)
            ->where('id_tienda', Auth::user()->id_tienda)
            ->groupBy($columna);
    }

    private function planoAnaqueles()
    {
        return DB::table('anaqueles as a')
            ->where('a.id_tienda', Auth::user()->id_tienda)
            ->where('a.activo', 1)
            ->orderBy('a.codigo')
            ->get(['a.id_anaquel', 'a.codigo', 'a.descripcion']);
    }

    private function conteosPlano()
    {
        return DB::table('productos')
            ->select('id_anaquel', DB::raw('COUNT(*) as total'))
            ->where('activo', 1)
            ->where('id_tienda', Auth::user()->id_tienda)
            ->whereNotNull('id_anaquel')
            ->groupBy('id_anaquel')
            ->pluck('total', 'id_anaquel');
    }

    private function registroQuery(string $seccion, int $id)
    {
        if ($seccion === 'productos') {
            return DB::table('catalogo_productos')
                ->select('*', DB::raw('id_catalogo as id'))
                ->where('id_catalogo', $id)
                ->where('activo', 1);
        }

        $query = DB::table($this->tabla($seccion))
            ->select('*', DB::raw($this->llave($seccion).' as id'))
            ->where($this->llave($seccion), $id);

        if (in_array($seccion, ['tipos', 'colores'], true)) {
            $query->where('activo', 1);
        } elseif ($seccion === 'anaqueles') {
            $query->where('id_tienda', Auth::user()->id_tienda)->where('activo', 1);
        }

        return $query;
    }

    private function validateRegistro(Request $request, string $seccion, ?int $id = null): array
    {
        $ignore = fn (string $table, string $column) => $id === null
            ? Rule::unique($table, $column)
            : Rule::unique($table, $column)->ignore($id, $this->llave($seccion));

        $rules = match ($seccion) {
            'tipos' => [
                'nombre' => ['required', 'string', 'max:80', $ignore('tipos_hilo', 'nombre')],
                'descripcion' => ['nullable', 'string', 'max:200'],
            ],
            'colores' => [
                'nombre' => ['required', 'string', 'max:60', $ignore('colores', 'nombre')],
                'codigo_hex' => ['nullable', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            ],
            'anaqueles' => [
                'codigo' => ['required', 'string', 'max:20', Rule::unique('anaqueles', 'codigo')
                    ->where('id_tienda', Auth::user()->id_tienda)->ignore($id, 'id_anaquel')],
                'descripcion' => ['nullable', 'string', 'max:150'],
            ],
            'unidades' => [
                'nombre' => ['required', 'string', 'max:60', $ignore('unidades', 'nombre')],
                'codigo' => ['required', 'string', 'max:20', $ignore('unidades', 'codigo')],
            ],
        };

        return $request->validate($rules);
    }

    private function configuracion(string $seccion): array
    {
        return match ($seccion) {
            'productos' => [
                'titulo' => 'Productos', 'singular' => 'producto',
                'columnas' => [['Nombre', 'nombre'], ['Tipo de hilo', 'tipo'], ['Color', 'color'], ['Unidad', 'unidad']],
                'campos' => [],
            ],
            'tipos' => [
                'titulo' => 'Tipos de hilo', 'singular' => 'tipo de hilo', 'icono' => '📋',
                'columnas' => [['Nombre', 'nombre'], ['Descripción', 'descripcion'], ['Productos usando', 'productos_usando'], ['Creado', 'creado_en']],
                'campos' => [['nombre', 'Nombre', 'text', 80], ['descripcion', 'Descripción', 'textarea', 200]],
            ],
            'colores' => [
                'titulo' => 'Colores', 'singular' => 'color', 'icono' => '🎨',
                'columnas' => [['Muestra', 'muestra'], ['Nombre', 'nombre'], ['Código HEX', 'codigo_hex'], ['Productos usando', 'productos_usando'], ['Creado', 'creado_en']],
                'campos' => [['nombre', 'Nombre', 'text', 60], ['codigo_hex', 'Código HEX', 'color', 7]],
            ],
            'anaqueles' => [
                'titulo' => 'Anaqueles', 'singular' => 'anaquel', 'icono' => '🗄️',
                'columnas' => [['Código', 'codigo'], ['Descripción', 'descripcion'], ['Productos usando', 'productos_usando']],
                'campos' => [['codigo', 'Código', 'text', 20], ['descripcion', 'Descripción', 'textarea', 150]],
            ],
            'unidades' => [
                'titulo' => 'Unidades', 'singular' => 'unidad', 'icono' => '📏',
                'columnas' => [['Nombre', 'nombre'], ['Código', 'codigo'], ['Productos usando', 'productos_usando']],
                'campos' => [['nombre', 'Nombre', 'text', 60], ['codigo', 'Código', 'text', 20]],
            ],
        };
    }

    private function tabla(string $seccion): string
    {
        return match ($seccion) {
            'tipos' => 'tipos_hilo',
            'colores' => 'colores',
            'anaqueles' => 'anaqueles',
            'unidades' => 'unidades',
        };
    }

    private function llave(string $seccion): string
    {
        return match ($seccion) {
            'tipos' => 'id_tipo',
            'colores' => 'id_color',
            'anaqueles' => 'id_anaquel',
            'unidades' => 'id_unidad',
        };
    }
}
