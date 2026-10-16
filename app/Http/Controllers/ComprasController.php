<?php

namespace App\Http\Controllers;

use App\Models\Compra;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class ComprasController extends Controller
{
    public function index(): View
    {
        $usuario = Auth::user();
        abort_unless($usuario->rol !== 'empleado', 403);

        $compras = DB::table('compras as c')
            ->leftJoin('proveedores as p', 'c.id_proveedor', '=', 'p.id_proveedor')
            ->leftJoin('tiendas as s', 'c.id_tienda', '=', 's.id_tienda')
            ->when($usuario->rol !== 'superadmin', fn ($query) => $query->where('c.id_tienda', $usuario->id_tienda))
            ->orderByDesc('c.fecha')
            ->orderByDesc('c.id_compra')
            ->limit(100)
            ->get(['c.*', 'p.nombre as proveedor', 's.nombre as tienda']);

        $productos = DB::table('productos as p')
            ->join('catalogo_productos as c', 'p.id_catalogo', '=', 'c.id_catalogo')
            ->leftJoin('unidades as u', 'p.id_unidad', '=', 'u.id_unidad')
            ->where('p.activo', 1)
            ->when($usuario->rol !== 'superadmin', fn ($query) => $query->where('p.id_tienda', $usuario->id_tienda))
            ->orderBy('c.nombre')
            ->get(['p.id_producto', 'p.id_tienda', 'p.stock_actual', 'p.precio_compra', 'p.precio_venta', 'c.nombre', 'p.presentacion', 'u.codigo as unidad']);

        $proveedores = DB::table('proveedores')->where('activo', 1)->orderBy('nombre')->get(['id_proveedor', 'nombre']);
        $tiendas = $usuario->rol === 'superadmin'
            ? DB::table('tiendas')->where('activo', 1)->orderBy('nombre')->get(['id_tienda', 'nombre'])
            : collect();

        return view('compras', compact('compras', 'productos', 'proveedores', 'tiendas'));
    }

    public function store(Request $request): RedirectResponse
    {
        $usuario = Auth::user();
        abort_unless($usuario->rol !== 'empleado', 403);

        $items = json_decode((string) $request->input('items', ''), true);
        $validated = Validator::make([
            'items' => $items,
            'id_proveedor' => $request->input('id_proveedor'),
            'id_tienda' => $request->input('id_tienda'),
            'fecha' => $request->input('fecha'),
            'folio' => $request->input('folio'),
            'notas' => $request->input('notas'),
        ], [
            'items' => ['required', 'array', 'min:1'],
            'items.*.id_producto' => ['required', 'integer', 'distinct'],
            'items.*.cantidad' => ['required', 'numeric', 'gt:0', 'max:99999999.99'],
            'items.*.precio_unit' => ['required', 'numeric', 'gt:0', 'max:99999999.99'],
            'items.*.precio_venta' => ['required', 'numeric', 'gt:0', 'max:99999999.99'],
            'id_proveedor' => ['required', 'integer', 'exists:proveedores,id_proveedor'],
            'id_tienda' => [$usuario->rol === 'superadmin' ? 'required' : 'nullable', 'integer', 'exists:tiendas,id_tienda'],
            'fecha' => ['required', 'date_format:Y-m-d'],
            'folio' => ['nullable', 'string', 'max:30'],
            'notas' => ['nullable', 'string', 'max:150'],
        ])->validate();

        $storeId = $usuario->rol === 'superadmin'
            ? (int) $validated['id_tienda']
            : (int) $usuario->id_tienda;
        $purchaseId = DB::transaction(function () use ($validated, $storeId, $usuario) {
            $supplier = DB::table('proveedores')
                ->where('id_proveedor', $validated['id_proveedor'])
                ->where('activo', 1)
                ->exists();

            if (! $supplier) {
                throw ValidationException::withMessages(['id_proveedor' => 'Selecciona un proveedor activo.']);
            }

            $lines = [];
            $total = 0.0;
            foreach ($validated['items'] as $item) {
                $product = DB::table('productos')
                    ->where('id_producto', $item['id_producto'])
                    ->where('id_tienda', $storeId)
                    ->where('activo', 1)
                    ->lockForUpdate()
                    ->first();

                if (! $product) {
                    throw ValidationException::withMessages(['items' => 'Uno de los productos no pertenece a la sucursal seleccionada.']);
                }

                $quantity = (float) $item['cantidad'];
                $price = (float) $item['precio_unit'];
                $salePrice = (float) $item['precio_venta'];
                $lineTotal = round($quantity * $price, 2);
                $total += $lineTotal;
                if ((float) $product->stock_actual + $quantity > 99999999.99) {
                    throw ValidationException::withMessages(['items' => 'La cantidad excede el límite de inventario permitido.']);
                }
                $lines[] = [$product, $quantity, $price, $salePrice, $lineTotal];
            }
            if ($total > 9999999999.99) {
                throw ValidationException::withMessages(['items' => 'El total de la compra excede el límite permitido.']);
            }

            $purchase = Compra::query()->create([
                'folio' => $validated['folio'] ?: null,
                'id_proveedor' => $validated['id_proveedor'],
                'id_usuario' => $usuario->id_usuario,
                'id_tienda' => $storeId,
                'total' => round($total, 2),
                'notas' => $validated['notas'] ?: null,
                'fecha' => $validated['fecha'],
            ]);
            $purchaseId = $purchase->id_compra;

            foreach ($lines as [$product, $quantity, $price, $salePrice, $lineTotal]) {
                DB::table('detalle_compra')->insert([
                    'id_compra' => $purchaseId,
                    'id_producto' => $product->id_producto,
                    'cantidad' => $quantity,
                    'precio_unit' => $price,
                    'subtotal' => $lineTotal,
                ]);
                DB::table('productos')->where('id_producto', $product->id_producto)
                    ->increment('stock_actual', $quantity, [
                        'precio_compra' => $price,
                        'precio_venta' => $salePrice,
                        'actualizado_en' => now()->toDateString(),
                    ]);
            }

            return $purchaseId;
        });

        return redirect()->route('compras')->with('status', "Compra #{$purchaseId} registrada y existencias actualizadas.");
    }
}
