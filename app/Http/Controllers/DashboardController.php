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

        if ($usuario->rol !== 'superadmin') {
            $ventas->where('v.id_tienda', $usuario->id_tienda);
        }

        $inicioMes = now()->startOfMonth()->toDateString();
        $finMes = now()->endOfMonth()->toDateString();
        $ventasMes = (clone $ventas)
            ->whereBetween('v.fecha', [$inicioMes, $finMes])
            ->sum('v.total');
        $ventasCount = (clone $ventas)
            ->whereBetween('v.fecha', [$inicioMes, $finMes])
            ->count();

        $productos = DB::table('productos')->where('activo', 1);
        $clientes = DB::table('clientes')->where('activo', 1);
        $usuarios = DB::table('usuarios')->where('activo', 1);
        $alertas = DB::table('productos')
            ->where('activo', 1)
            ->whereColumn('stock_actual', '<=', 'stock_minimo');

        if ($usuario->rol !== 'superadmin') {
            $productos->where('id_tienda', $usuario->id_tienda);
            $clientes->where('id_tienda', $usuario->id_tienda);
            $usuarios->where('id_tienda', $usuario->id_tienda);
            $alertas->where('id_tienda', $usuario->id_tienda);
        }

        $ultimasVentas = (clone $ventas)
            ->leftJoin('clientes as c', 'v.id_cliente', '=', 'c.id_cliente')
            ->leftJoin('tiendas as t', 'v.id_tienda', '=', 't.id_tienda')
            ->orderByDesc('v.id_venta')
            ->limit(8)
            ->get([
                'v.id_venta',
                'v.folio',
                'v.fecha',
                'v.total',
                'c.nombre as cliente',
                't.nombre as tienda',
            ]);

        return view('dashboard', [
            'ventasMes' => $ventasMes,
            'ventasCount' => $ventasCount,
            'productosCount' => $productos->count(),
            'clientesCount' => $clientes->count(),
            'usuariosCount' => $usuarios->count(),
            'alertasCount' => $alertas->count(),
            'ultimasVentas' => $ultimasVentas,
        ]);
    }
}
