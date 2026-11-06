<?php

namespace App\Http\Controllers;

use App\Models\Compra;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class ComprasController extends Controller
{
    public function ticket(int $id): View
    {
        $usuario = Auth::user();
        abort_unless($usuario->rol !== 'empleado', 403);

        $compra = DB::table('compras as c')
            ->leftJoin('proveedores as p', 'c.id_proveedor', '=', 'p.id_proveedor')
            ->leftJoin('tiendas as s', 'c.id_tienda', '=', 's.id_tienda')
            ->leftJoin('usuarios as u', 'c.id_usuario', '=', 'u.id_usuario')
            ->where('c.id_compra', $id)
            ->where('c.id_tienda', $usuario->id_tienda)
            ->first([
                'c.*',
                'p.nombre as proveedor_nombre',
                'p.telefono as proveedor_telefono',
                'p.correo as proveedor_correo',
                'p.direccion as proveedor_direccion',
                's.direccion as sucursal_direccion',
                's.telefono as sucursal_telefono',
                'u.nombre as usuario_nombre',
            ]);

        abort_if(! $compra, 404);

        $detalles = DB::table('detalle_compra as d')
            ->join('productos as p', 'd.id_producto', '=', 'p.id_producto')
            ->join('catalogo_productos as c', 'p.id_catalogo', '=', 'c.id_catalogo')
            ->leftJoin('unidades as u', 'p.id_unidad', '=', 'u.id_unidad')
            ->where('d.id_compra', $id)
            ->orderBy('d.id_detalle')
            ->get([
                'd.cantidad',
                'd.precio_unit',
                'd.subtotal',
                'c.nombre as nombre_producto',
                'p.presentacion',
                'u.codigo as unidad',
            ]);

        return view('panel.compra-ticket', compact('compra', 'detalles'));
    }

    public function index(): View
    {
        $usuario = Auth::user();
        abort_unless($usuario->rol !== 'empleado', 403);

        $compras = DB::table('compras as c')
            ->leftJoin('proveedores as p', 'c.id_proveedor', '=', 'p.id_proveedor')
            ->where('c.id_tienda', $usuario->id_tienda)
            ->orderByDesc('c.fecha')
            ->orderByDesc('c.id_compra')
            ->limit(100)
            ->get(['c.*', 'p.nombre as proveedor']);

        $productos = DB::table('productos as p')
            ->join('catalogo_productos as c', 'p.id_catalogo', '=', 'c.id_catalogo')
            ->leftJoin('unidades as u', 'p.id_unidad', '=', 'u.id_unidad')
            ->where('p.activo', 1)
            ->where('p.id_tienda', $usuario->id_tienda)
            ->orderBy('c.nombre')
            ->get(['p.id_producto', 'p.id_tienda', 'p.stock_actual', 'p.precio_compra', 'p.precio_venta', 'c.nombre', 'p.presentacion', 'u.codigo as unidad']);

        $proveedores = DB::table('proveedores')->where('activo', 1)->orderBy('nombre')->get(['id_proveedor', 'nombre']);
        $productoSeleccionado = (int) request()->query('producto', 0);
        if (! $productos->contains('id_producto', $productoSeleccionado)) {
            $productoSeleccionado = 0;
        }

        return view('compras', compact('compras', 'productos', 'proveedores', 'productoSeleccionado'));
    }

    public function historialProducto(int $id): JsonResponse
    {
        $usuario = Auth::user();
        abort_unless($usuario->rol !== 'empleado', 403);

        $productoExiste = DB::table('productos')
            ->where('id_producto', $id)
            ->where('id_tienda', $usuario->id_tienda)
            ->exists();
        abort_if(! $productoExiste, 404);

        $historial = DB::table('detalle_compra as d')
            ->join('compras as c', 'd.id_compra', '=', 'c.id_compra')
            ->join('proveedores as p', 'c.id_proveedor', '=', 'p.id_proveedor')
            ->where('d.id_producto', $id)
            ->where('c.id_tienda', $usuario->id_tienda)
            ->groupBy('p.id_proveedor', 'p.nombre')
            ->orderByDesc(DB::raw('MAX(c.fecha)'))
            ->get([
                'p.nombre as proveedor',
                DB::raw('MAX(c.fecha) as ultima_compra'),
                DB::raw('COUNT(DISTINCT c.id_compra) as total_compras'),
            ]);

        return response()->json($historial);
    }

    public function store(Request $request): RedirectResponse
    {
        $usuario = Auth::user();
        abort_unless($usuario->rol !== 'empleado', 403);

        $items = json_decode((string) $request->input('items', ''), true);
        $validated = Validator::make([
            'items' => $items,
            'id_proveedor' => $request->input('id_proveedor'),
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
            'fecha' => ['required', 'date_format:Y-m-d'],
            'folio' => ['nullable', 'string', 'max:30'],
            'notas' => ['nullable', 'string', 'max:150'],
        ])->validate();

        $storeId = (int) $usuario->id_tienda;
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
                    throw ValidationException::withMessages(['items' => 'Uno de los productos no pertenece a la sucursal única del sistema.']);
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
