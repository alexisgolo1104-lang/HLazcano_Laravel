<?php

namespace App\Http\Controllers;

use App\Models\Venta;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Illuminate\Support\Str;

class VentasController extends Controller
{
    public function index(): View
    {
        $usuario = Auth::user();
        $ventas = DB::table('ventas as v')
            ->leftJoin('usuarios as u', 'v.id_usuario', '=', 'u.id_usuario')
            ->leftJoin('clientes as c', 'v.id_cliente', '=', 'c.id_cliente')
            ->leftJoin('tiendas as s', 'v.id_tienda', '=', 's.id_tienda')
            ->when($usuario->rol !== 'superadmin', fn ($query) => $query->where('v.id_tienda', $usuario->id_tienda))
            ->orderByDesc('v.id_venta')
            ->limit(100)
            ->get(['v.*', 'u.nombre as vendedor', 'c.nombre as cliente', 's.nombre as tienda']);

        $productos = DB::table('productos as p')
            ->join('catalogo_productos as c', 'p.id_catalogo', '=', 'c.id_catalogo')
            ->leftJoin('unidades as u', 'p.id_unidad', '=', 'u.id_unidad')
            ->where('p.activo', 1)
            ->where('p.stock_actual', '>', 0)
            ->where('p.precio_venta', '>', 0)
            ->when($usuario->rol !== 'superadmin', fn ($query) => $query->where('p.id_tienda', $usuario->id_tienda))
            ->orderBy('c.nombre')
            ->get(['p.id_producto', 'p.id_tienda', 'p.stock_actual', 'p.precio_venta', 'c.nombre', 'p.presentacion', 'u.codigo as unidad']);

        $clientes = DB::table('clientes')
            ->where('activo', 1)
            ->when($usuario->rol !== 'superadmin', fn ($query) => $query->where('id_tienda', $usuario->id_tienda))
            ->orderBy('nombre')
            ->get(['id_cliente', 'id_tienda', 'nombre']);

        $tiendas = $usuario->rol === 'superadmin'
            ? DB::table('tiendas')->where('activo', 1)->orderBy('nombre')->get(['id_tienda', 'nombre'])
            : collect();

        return view('ventas', compact('ventas', 'productos', 'clientes', 'tiendas'));
    }

    public function store(Request $request): RedirectResponse
    {
        $usuario = Auth::user();
        $items = json_decode((string) $request->input('items', ''), true);
        $validated = Validator::make([
            'items' => $items,
            'id_cliente' => $request->input('id_cliente'),
            'descuento' => $request->input('descuento', 0),
            'notas' => $request->input('notas'),
            'id_tienda' => $request->input('id_tienda'),
        ], [
            'items' => ['required', 'array', 'min:1'],
            'items.*.id_producto' => ['required', 'integer', 'distinct'],
            'items.*.cantidad' => ['required', 'numeric', 'gt:0', 'max:99999999.99'],
            'id_cliente' => ['nullable', 'integer', 'exists:clientes,id_cliente'],
            'descuento' => ['required', 'numeric', 'min:0', 'max:100'],
            'notas' => ['nullable', 'string', 'max:150'],
            'id_tienda' => [$usuario->rol === 'superadmin' ? 'required' : 'nullable', 'integer', 'exists:tiendas,id_tienda'],
        ])->validate();

        $storeId = $usuario->rol === 'superadmin'
            ? (int) $validated['id_tienda']
            : (int) $usuario->id_tienda;

        $saleId = DB::transaction(function () use ($validated, $storeId, $usuario) {
            $subtotal = 0.0;
            $lines = [];

            foreach ($validated['items'] as $item) {
                $product = DB::table('productos')
                    ->where('id_producto', $item['id_producto'])
                    ->where('id_tienda', $storeId)
                    ->where('activo', 1)
                    ->lockForUpdate()
                    ->first();

                if (! $product) {
                    throw ValidationException::withMessages(['items' => 'Uno de los productos no está disponible en esa sucursal.']);
                }

                $quantity = (float) $item['cantidad'];
                if ((float) $product->stock_actual < $quantity) {
                    throw ValidationException::withMessages(['items' => "No hay suficiente existencia para el producto {$product->id_producto}."]);
                }

                $price = (float) $product->precio_venta;
                if ($price <= 0) {
                    throw ValidationException::withMessages(['items' => "El producto {$product->id_producto} no tiene precio de venta configurado."]);
                }
                $lineTotal = round($quantity * $price, 2);
                $subtotal += $lineTotal;
                $lines[] = [$product, $quantity, $price, $lineTotal];
            }

            $total = round($subtotal * (1 - (float) $validated['descuento'] / 100), 2);
            if ($total > 9999999999.99) {
                throw ValidationException::withMessages(['items' => 'El total de la venta supera el límite permitido.']);
            }
            if ($validated['id_cliente']) {
                $clientQuery = DB::table('clientes')
                    ->where('id_cliente', $validated['id_cliente'])
                    ->where('activo', 1);
                if ($usuario->rol !== 'superadmin') {
                    $clientQuery->where('id_tienda', $storeId);
                }
                if (! $clientQuery->exists()) {
                    throw ValidationException::withMessages(['id_cliente' => 'El cliente no está activo en la sucursal seleccionada.']);
                }
            }

            $sale = Venta::query()->create([
                'folio' => 'V-'.now()->format('Ymd').'-'.random_int(1000, 9999),
                'id_usuario' => $usuario->id_usuario,
                'id_cliente' => $validated['id_cliente'] ?: null,
                'id_tienda' => $storeId,
                'descuento' => $validated['descuento'],
                'subtotal' => round($subtotal, 2),
                'total' => $total,
                'notas' => $validated['notas'] ?: null,
                'fecha' => now()->toDateString(),
            ]);
            $saleId = $sale->id_venta;

            foreach ($lines as [$product, $quantity, $price, $lineTotal]) {
                DB::table('detalle_venta')->insert([
                    'id_venta' => $saleId,
                    'id_producto' => $product->id_producto,
                    'cantidad' => $quantity,
                    'precio_unit' => $price,
                    'subtotal' => $lineTotal,
                ]);
                DB::table('productos')
                    ->where('id_producto', $product->id_producto)
                    ->decrement('stock_actual', $quantity, ['actualizado_en' => now()->toDateString()]);
            }

            return $saleId;
        });

        return redirect()->route('ventas')->with('status', "Venta #{$saleId} registrada correctamente.");
    }

    public function cancelar(Request $request, int $id): RedirectResponse
    {
        $data = $request->validate([
            'tipo' => ['required', 'in:cancelacion,devolucion'],
            'motivo' => ['required', 'string', 'max:100'],
        ]);
        $usuario = Auth::user();

        DB::transaction(function () use ($data, $id, $usuario) {
            $ventaQuery = DB::table('ventas')->where('id_venta', $id);
            if ($usuario->rol !== 'superadmin') {
                $ventaQuery->where('id_tienda', $usuario->id_tienda);
            }
            $venta = $ventaQuery->lockForUpdate()->first();
            abort_if(! $venta, 404);

            if (Str::startsWith(mb_strtoupper(trim((string) $venta->notas)), ['CANCELADA:', 'DEVOLUCION:'])) {
                throw ValidationException::withMessages([
                    'tipo' => 'La venta ya fue cancelada o devuelta.',
                ]);
            }

            $detalles = DB::table('detalle_venta')->where('id_venta', $id)->get();
            if ($detalles->isEmpty()) {
                throw ValidationException::withMessages([
                    'tipo' => 'No se encontraron productos para devolver a inventario.',
                ]);
            }

            foreach ($detalles as $detalle) {
                DB::table('productos')->where('id_producto', $detalle->id_producto)
                    ->increment('stock_actual', (float) $detalle->cantidad, [
                        'actualizado_en' => now()->toDateString(),
                    ]);
            }

            $prefix = $data['tipo'] === 'devolucion' ? 'DEVOLUCION: ' : 'CANCELADA: ';
            $note = $prefix.$data['motivo'];
            $original = trim((string) $venta->notas);
            if ($original !== '') {
                $remaining = max(0, 150 - mb_strlen($note) - mb_strlen(' — Nota: '));
                $note .= ' — Nota: '.mb_substr($original, 0, $remaining);
            }

            DB::table('ventas')->where('id_venta', $id)->update(['notas' => $note]);
        });

        return redirect()->route('ventas')->with('status', 'La venta fue marcada y el stock fue restaurado.');
    }
}
