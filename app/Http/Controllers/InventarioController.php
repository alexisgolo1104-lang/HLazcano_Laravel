<?php

namespace App\Http\Controllers;

use App\Models\Producto;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class InventarioController extends Controller
{
    public function index(): View
    {
        $usuario = Auth::user();
        $productos = $this->productosQuery()
            ->where('p.id_tienda', $usuario->id_tienda)
            ->orderBy('c.nombre')
            ->get();

        $tipos = DB::table('tipos_hilo')->where('activo', 1)->orderBy('nombre')->get();
        $colores = DB::table('colores')->where('activo', 1)->orderBy('nombre')->get();

        return view('inventario', compact('productos', 'tipos', 'colores'));
    }

    public function create(): View
    {
        $this->authorizeInventoryWrite();

        return view('inventario-form', $this->formData());
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorizeInventoryWrite();
        $data = $this->validateProduct($request, catalogFieldsLocked: true);

        $catalogo = $this->activeCatalogProduct($data['id_catalogo']);
        $data = $this->catalogProductAttributes($catalogo) + $data + [
            'stock_actual' => 0,
            'precio_compra' => 0,
        ];
        $data['id_tienda'] = (int) Auth::user()->id_tienda;

        if ($this->catalogNameExists($data['id_catalogo'])) {
            throw ValidationException::withMessages([
                'id_catalogo' => 'Ya existe un producto con ese nombre, aunque tenga otra unidad de medida.',
            ]);
        }

        $this->validateProductRelations($data);
        $data['activo'] = 1;
        $data['creado_en'] = now()->toDateString();
        Producto::query()->create($data);

        return redirect()->route('inventario')->with('status', 'Producto registrado correctamente.');
    }

    public function edit(int $id): View
    {
        $this->authorizeInventoryWrite();
        $usuario = Auth::user();
        $producto = $this->productosQuery()
            ->where('p.id_producto', $id)
            ->where('p.id_tienda', $usuario->id_tienda)
            ->first();

        abort_if(! $producto, 404);

        return view('inventario-form', $this->formData() + ['producto' => $producto]);
    }

    public function update(Request $request, int $id): RedirectResponse
    {
        $this->authorizeInventoryWrite();
        $data = $this->validateProduct($request, catalogFieldsLocked: true);
        $usuario = Auth::user();
        $query = DB::table('productos')
            ->where('id_producto', $id)
            ->where('id_tienda', $usuario->id_tienda);

        $producto = $query->first();
        abort_if(! $producto, 404);

        $catalogo = $this->activeCatalogProduct($data['id_catalogo']);
        if ((int) $data['id_catalogo'] !== (int) $producto->id_catalogo
            && $this->catalogNameExists($data['id_catalogo'], $id)) {
            throw ValidationException::withMessages([
                'id_catalogo' => 'Ya existe otro producto con ese nombre en el inventario.',
            ]);
        }
        $data = $this->catalogProductAttributes($catalogo) + $data + [
            'stock_actual' => $producto->stock_actual,
            'precio_compra' => $producto->precio_compra,
            'id_proveedor' => null,
        ];
        $this->validateProductRelations($data);

        $data['actualizado_en'] = now()->toDateString();
        Producto::query()->where('id_producto', $id)->update($data);

        return redirect()->route('inventario')->with('status', 'Producto actualizado correctamente.');
    }

    public function destroy(int $id): RedirectResponse
    {
        $this->authorizeInventoryWrite();
        $usuario = Auth::user();
        $query = DB::table('productos')
            ->where('id_producto', $id)
            ->where('id_tienda', $usuario->id_tienda);

        $updated = $query->update([
            'activo' => 0,
            'actualizado_en' => now()->toDateString(),
        ]);
        abort_if(! $updated, 404);

        return redirect()->route('inventario')->with('status', 'Producto desactivado.');
    }

    private function productosQuery()
    {
        return DB::table('productos as p')
            ->leftJoin('catalogo_productos as c', 'p.id_catalogo', '=', 'c.id_catalogo')
            ->leftJoin('tipos_hilo as t', 'p.id_tipo', '=', 't.id_tipo')
            ->leftJoin('colores as co', 'p.id_color', '=', 'co.id_color')
            ->leftJoin('unidades as u', 'p.id_unidad', '=', 'u.id_unidad')
            ->leftJoin('anaqueles as a', 'p.id_anaquel', '=', 'a.id_anaquel')
            ->where('p.activo', 1)
            ->select(
                'p.*',
                'c.nombre as catalogo_nombre',
                't.nombre as tipo_nombre',
                'co.nombre as color_nombre',
                'co.codigo_hex as color_hex',
                'u.codigo as unidad_codigo',
                'a.codigo as anaquel_codigo',
            );
    }

    private function formData(): array
    {
        return [
            'catalogos' => DB::table('catalogo_productos as c')
                ->join('tipos_hilo as t', 'c.id_tipo', '=', 't.id_tipo')
                ->join('colores as co', 'c.id_color', '=', 'co.id_color')
                ->join('unidades as u', 'c.id_unidad', '=', 'u.id_unidad')
                ->where('c.activo', 1)
                ->select(
                    'c.*',
                    't.nombre as tipo_nombre',
                    'co.nombre as color_nombre',
                    'co.codigo_hex as color_hex',
                    'u.nombre as unidad_nombre',
                    'u.codigo as unidad_codigo',
                )
                ->orderBy('c.nombre')
                ->get(),
            'anaqueles' => DB::table('anaqueles')
                ->where('activo', 1)
                ->where('id_tienda', Auth::user()->id_tienda)
                ->orderBy('codigo')
                ->get(),
        ];
    }

    private function validateProduct(Request $request, bool $catalogFieldsLocked = false): array
    {
        $rules = [
            'id_catalogo' => ['required', 'integer', 'exists:catalogo_productos,id_catalogo'],
            'id_anaquel' => ['nullable', 'integer', 'exists:anaqueles,id_anaquel'],
            'posicion_anaquel' => ['nullable', 'string', 'max:50'],
            'stock_minimo' => ['required', 'numeric', 'min:0', 'max:99999999.99'],
            'precio_venta' => ['nullable', 'numeric', 'min:0', 'max:99999999.99'],
        ];

        if (! $catalogFieldsLocked) {
            $rules = array_merge($rules, [
                'id_tipo' => ['required', 'integer', 'exists:tipos_hilo,id_tipo'],
                'id_color' => ['required', 'integer', 'exists:colores,id_color'],
                'id_unidad' => ['required', 'integer', 'exists:unidades,id_unidad'],
                'presentacion' => ['nullable', 'string', 'max:40'],
                'stock_actual' => ['required', 'numeric', 'min:0', 'max:99999999.99'],
                'precio_compra' => ['nullable', 'numeric', 'min:0', 'max:99999999.99'],
            ]);
        }

        return $request->validate($rules);
    }

    private function activeCatalogProduct(int $catalogId): object
    {
        $catalogo = DB::table('catalogo_productos')
            ->where('id_catalogo', $catalogId)
            ->where('activo', 1)
            ->first();

        if (! $catalogo) {
            throw ValidationException::withMessages([
                'id_catalogo' => 'Selecciona un producto activo del catálogo.',
            ]);
        }

        return $catalogo;
    }

    private function catalogProductAttributes(object $catalogo): array
    {
        return [
            'id_tipo' => $catalogo->id_tipo,
            'id_color' => $catalogo->id_color,
            'id_unidad' => $catalogo->id_unidad,
            'presentacion' => $catalogo->presentacion,
        ];
    }

    private function catalogNameExists(int $catalogId, ?int $exceptProductId = null): bool
    {
        $name = DB::table('catalogo_productos')->where('id_catalogo', $catalogId)->value('nombre');

        return DB::table('productos as p')
            ->join('catalogo_productos as c', 'p.id_catalogo', '=', 'c.id_catalogo')
            ->where('c.nombre', $name)
            ->when($exceptProductId, fn ($query) => $query->where('p.id_producto', '!=', $exceptProductId))
            ->exists();
    }

    private function validateProductRelations(array $data): void
    {
        if (! empty($data['id_anaquel']) && ! DB::table('anaqueles')
            ->where('id_anaquel', $data['id_anaquel'])
            ->where('activo', 1)
            ->where('id_tienda', Auth::user()->id_tienda)
            ->exists()) {
            throw ValidationException::withMessages(['id_anaquel' => 'El anaquel no pertenece a la sucursal única del sistema.']);
        }
    }

    private function authorizeInventoryWrite(): void
    {
        abort_unless(in_array(Auth::user()->rol, ['superadmin', 'admin'], true), 403);
    }
}
