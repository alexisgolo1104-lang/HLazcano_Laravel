<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class PanelController extends Controller
{
    public function alertas(): View
    {
        $usuario = Auth::user();
        $productos = DB::table('productos as p')
            ->join('catalogo_productos as c', 'p.id_catalogo', '=', 'c.id_catalogo')
            ->leftJoin('tipos_hilo as t', 'p.id_tipo', '=', 't.id_tipo')
            ->leftJoin('anaqueles as a', 'p.id_anaquel', '=', 'a.id_anaquel')
            ->leftJoin('tiendas as s', 'p.id_tienda', '=', 's.id_tienda')
            ->where('p.activo', 1)
            ->whereColumn('p.stock_actual', '<=', 'p.stock_minimo')
            ->when($usuario->rol !== 'superadmin', fn ($query) => $query->where('p.id_tienda', $usuario->id_tienda))
            ->orderBy('p.stock_actual')
            ->get([
                'p.id_producto',
                'p.stock_actual',
                'p.stock_minimo',
                'c.nombre',
                't.nombre as tipo',
                'a.codigo as anaquel',
                's.nombre as tienda',
            ]);

        return view('panel.alertas', compact('productos'));
    }

    public function localizador(): View
    {
        $usuario = Auth::user();
        $productos = DB::table('productos as p')
            ->join('catalogo_productos as c', 'p.id_catalogo', '=', 'c.id_catalogo')
            ->leftJoin('tipos_hilo as t', 'p.id_tipo', '=', 't.id_tipo')
            ->leftJoin('colores as co', 'p.id_color', '=', 'co.id_color')
            ->leftJoin('anaqueles as a', 'p.id_anaquel', '=', 'a.id_anaquel')
            ->leftJoin('tiendas as s', 'p.id_tienda', '=', 's.id_tienda')
            ->where('p.activo', 1)
            ->when($usuario->rol !== 'superadmin', fn ($query) => $query->where('p.id_tienda', $usuario->id_tienda))
            ->orderBy('c.nombre')
            ->get(['p.id_producto', 'p.stock_actual', 'c.nombre', 't.nombre as tipo', 'co.nombre as color', 'a.codigo as anaquel', 'a.descripcion', 's.nombre as tienda']);

        return view('panel.localizador', compact('productos'));
    }

    public function reportes(): View
    {
        $usuario = Auth::user();
        $query = DB::table('ventas as v')
            ->whereBetween('v.fecha', [now()->startOfMonth()->toDateString(), now()->endOfMonth()->toDateString()])
            ->where(function ($query) {
                $query->whereNull('v.notas')
                    ->orWhere(function ($query) {
                        $query->where('v.notas', 'not like', 'CANCELADA:%')
                            ->where('v.notas', 'not like', 'DEVOLUCION:%');
                    });
            })
            ->when($usuario->rol !== 'superadmin', fn ($builder) => $builder->where('v.id_tienda', $usuario->id_tienda));
        $ventas = (clone $query)->orderByDesc('v.fecha')->orderByDesc('v.id_venta')->get();

        return view('panel.reportes', [
            'total' => (clone $query)->sum('v.total'),
            'cantidad' => (clone $query)->count(),
            'ventas' => $ventas,
        ]);
    }

    public function usuarios(): View
    {
        abort_unless(in_array(Auth::user()->rol, ['superadmin', 'admin'], true), 403);

        $usuarios = DB::table('usuarios as u')
            ->leftJoin('tiendas as t', 'u.id_tienda', '=', 't.id_tienda')
            ->orderBy('u.nombre')
            ->get(['u.id_usuario', 'u.nombre', 'u.correo', 'u.rol', 'u.activo', 'u.ultimo_acceso', 't.nombre as tienda']);

        return view('panel.usuarios', compact('usuarios'));
    }

    public function catalogos(): View
    {
        abort_unless(in_array(Auth::user()->rol, ['superadmin', 'admin'], true), 403);

        return view('panel.catalogos', [
            'catalogos' => DB::table('catalogo_productos')->where('activo', 1)->orderBy('nombre')->get(),
            'tipos' => DB::table('tipos_hilo')->where('activo', 1)->orderBy('nombre')->get(),
            'colores' => DB::table('colores')->where('activo', 1)->orderBy('nombre')->get(),
            'unidades' => DB::table('unidades')->orderBy('nombre')->get(),
            'proveedores' => DB::table('proveedores')->where('activo', 1)->orderBy('nombre')->get(),
        ]);
    }
}
