<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(): View
    {
        $usuario = Auth::user();
        $ventas = DB::table('ventas as v')
            ->where(function ($query) {
                $query->whereNull('v.notas')
                    ->orWhere(function ($query) {
                        $query->where('v.notas', 'not like', 'CANCELADA:%')
                            ->where('v.notas', 'not like', 'DEVOLUCION:%');
                    });
            });

        $ventas->where('v.id_tienda', $usuario->id_tienda);

        $inicioMes = now()->startOfMonth()->toDateString();
        $finMes = now()->endOfMonth()->toDateString();
        $ventasMes = (clone $ventas)
            ->whereBetween('v.fecha', [$inicioMes, $finMes])
            ->sum('v.total');
        $ventasCount = (clone $ventas)
            ->whereBetween('v.fecha', [$inicioMes, $finMes])
            ->count();

        $fechaFin = now()->startOfDay();
        $fechaInicio = $fechaFin->copy()->subDays(6);
        $ventasAgrupadas = (clone $ventas)
            ->whereBetween('v.fecha', [$fechaInicio->toDateString(), $fechaFin->toDateString()])
            ->select('v.fecha', DB::raw('SUM(v.total) as total'), DB::raw('COUNT(*) as cantidad'))
            ->groupBy('v.fecha')
            ->get()
            ->keyBy(fn ($venta) => (string) $venta->fecha);
        $ventasPorDia = collect(range(6, 0))->map(function (int $dias) use ($fechaFin, $ventasAgrupadas) {
            $fecha = $fechaFin->copy()->subDays($dias);
            $venta = $ventasAgrupadas->get($fecha->toDateString());

            return [
                'fecha' => $fecha->toDateString(),
                'etiqueta' => $fecha->format('d/m'),
                'total' => (float) ($venta->total ?? 0),
                'cantidad' => (int) ($venta->cantidad ?? 0),
            ];
        })->all();

        $productos = DB::table('productos')->where('productos.activo', 1);
        $clientes = DB::table('clientes')->where('activo', 1);
        $alertas = DB::table('productos')
            ->where('productos.activo', 1)
            ->whereColumn('productos.stock_actual', '<=', 'productos.stock_minimo');

        $productos->where('productos.id_tienda', $usuario->id_tienda);
        $clientes->where('id_tienda', $usuario->id_tienda);
        $alertas->where('productos.id_tienda', $usuario->id_tienda);

        $productosConStock = (clone $productos)
            ->leftJoin('catalogo_productos as c', 'productos.id_catalogo', '=', 'c.id_catalogo')
            ->leftJoin('unidades as u', 'productos.id_unidad', '=', 'u.id_unidad')
            ->orderByDesc('productos.stock_actual')
            ->limit(6)
            ->get([
                'productos.stock_actual',
                'c.nombre',
                'productos.presentacion',
                'u.codigo as unidad',
            ]);

        $alertasProductos = (clone $alertas)
            ->leftJoin('catalogo_productos as c', 'productos.id_catalogo', '=', 'c.id_catalogo')
            ->leftJoin('unidades as u', 'productos.id_unidad', '=', 'u.id_unidad')
            ->orderBy('productos.stock_actual')
            ->limit(4)
            ->get([
                'productos.stock_actual',
                'productos.stock_minimo',
                'productos.presentacion',
                'c.nombre',
                'u.codigo as unidad',
            ]);

        $clientesPorTipo = (clone $clientes)
            ->select('tipo_cliente')
            ->get()
            ->countBy(function ($cliente) {
                $tipo = trim((string) $cliente->tipo_cliente);

                return $tipo !== '' ? $tipo : 'Sin categoría';
            });
        $coloresClientes = ['#2563EB', '#F59E0B', '#10B981', '#8B5CF6', '#EF4444'];
        $clientesPorTipo = $clientesPorTipo
            ->sortDesc()
            ->map(fn (int $cantidad, string $tipo) => ['tipo' => $tipo, 'cantidad' => $cantidad])
            ->values()
            ->map(function (array $cliente, int $indice) use ($coloresClientes) {
                $cliente['color'] = $coloresClientes[$indice % count($coloresClientes)];

                return $cliente;
            })
            ->values()
            ->all();

        return view('dashboard', [
            'ventasMes' => $ventasMes,
            'ventasCount' => $ventasCount,
            'productosCount' => $productos->count(),
            'clientesCount' => $clientes->count(),
            'alertasCount' => $alertas->count(),
            'ventasPorDia' => $ventasPorDia,
            'productosConStock' => $productosConStock,
            'clientesPorTipo' => $clientesPorTipo,
            'alertasProductos' => $alertasProductos,
        ]);
    }
}
