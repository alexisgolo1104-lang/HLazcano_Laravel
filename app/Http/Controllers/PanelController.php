<?php

namespace App\Http\Controllers;

use App\Services\InventoryAlertService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class PanelController extends Controller
{
    public function alertas(Request $request, InventoryAlertService $alertService): View
    {
        $usuario = Auth::user();
        $alertService->syncStore((int) $usuario->id_tienda);
        $filter = $request->query('filtro', 'todas');
        abort_unless(in_array($filter, ['todas', 'critico', 'alerta', 'sin-movimiento', 'atendidas'], true), 404);

        $query = DB::table('alertas_stock as al')
            ->join('productos as p', 'al.id_producto', '=', 'p.id_producto')
            ->join('catalogo_productos as c', 'p.id_catalogo', '=', 'c.id_catalogo')
            ->leftJoin('tipos_hilo as t', 'p.id_tipo', '=', 't.id_tipo')
            ->leftJoin('colores as co', 'p.id_color', '=', 'co.id_color')
            ->leftJoin('unidades as u', 'p.id_unidad', '=', 'u.id_unidad')
            ->leftJoin('anaqueles as a', 'p.id_anaquel', '=', 'a.id_anaquel')
            ->leftJoin('usuarios as ua', 'al.id_usuario_aten', '=', 'ua.id_usuario')
            ->where('al.id_tienda', $usuario->id_tienda);

        if ($filter === 'atendidas') {
            $query->where('al.atendida', 1);
        } else {
            $query->where('al.atendida', 0);
            match ($filter) {
                'critico' => $query->where('al.tipo_alerta', 'stock_critico'),
                'alerta' => $query->where('al.tipo_alerta', 'stock_minimo'),
                'sin-movimiento' => $query->where('al.tipo_alerta', 'sin_movimiento'),
                default => null,
            };
        }

        $alertas = $query
            ->orderByRaw("CASE al.tipo_alerta WHEN 'stock_critico' THEN 1 WHEN 'stock_minimo' THEN 2 ELSE 3 END")
            ->orderBy('p.stock_actual')
            ->get([
                'al.id_alerta', 'al.id_producto', 'al.tipo_alerta', 'al.atendida',
                'al.fecha_alerta', 'al.fecha_atencion', 'al.accion_tomada', 'ua.nombre as atendida_por',
                'p.stock_actual', 'p.stock_minimo', 'p.precio_venta', 'p.creado_en', 'p.actualizado_en',
                'c.nombre as nombre_producto', 'p.presentacion', 't.nombre as tipo_producto',
                'co.nombre as color_nombre', 'co.codigo_hex as color_hex', 'u.codigo as unidad',
                'a.codigo as anaquel',
            ]);

        $movimientos = $alertService->latestMovementDates((int) $usuario->id_tienda);
        foreach ($alertas as $alerta) {
            $alerta->ultimo_movimiento = $movimientos->get($alerta->id_producto)
                ?? $alerta->actualizado_en
                ?? $alerta->creado_en;
        }

        $pendientes = DB::table('alertas_stock')->where('id_tienda', $usuario->id_tienda)->where('atendida', 0);
        $totales = [
            'todas' => (clone $pendientes)->count(),
            'critico' => (clone $pendientes)->where('tipo_alerta', 'stock_critico')->count(),
            'alerta' => (clone $pendientes)->where('tipo_alerta', 'stock_minimo')->count(),
            'sin_movimiento' => (clone $pendientes)->where('tipo_alerta', 'sin_movimiento')->count(),
            'atendidas' => DB::table('alertas_stock')->where('id_tienda', $usuario->id_tienda)->where('atendida', 1)->count(),
            'atendidas_hoy' => DB::table('alertas_stock')
                ->where('id_tienda', $usuario->id_tienda)
                ->where('atendida', 1)
                ->whereDate('fecha_atencion', now()->toDateString())
                ->count(),
        ];

        return view('panel.alertas', compact('alertas', 'filter', 'totales'));
    }

    public function atenderTodasAlertas(): RedirectResponse
    {
        DB::table('alertas_stock')
            ->where('id_tienda', Auth::user()->id_tienda)
            ->where('atendida', 0)
            ->update([
                'atendida' => 1,
                'accion_tomada' => 'atendida',
                'id_usuario_aten' => Auth::id(),
                'notas_atencion' => 'Marcadas como atendidas desde alertas.',
                'fecha_atencion' => now()->toDateString(),
            ]);

        return redirect()->route('alertas')->with('status', 'Todas las alertas pendientes fueron atendidas.');
    }

    public function localizador(): View
    {
        $usuario = Auth::user();
        $anaqueles = DB::table('anaqueles')
            ->where('id_tienda', $usuario->id_tienda)
            ->where('activo', 1)
            ->orderBy('codigo')
            ->get(['id_anaquel', 'codigo', 'descripcion']);
        $selectedShelfId = request()->query('anaquel');
        if ($selectedShelfId !== null) {
            abort_unless(ctype_digit((string) $selectedShelfId), 404);
            $selectedShelfId = (int) $selectedShelfId;
            abort_unless($anaqueles->contains('id_anaquel', $selectedShelfId), 404);
        }

        $productos = DB::table('productos as p')
            ->join('catalogo_productos as c', 'p.id_catalogo', '=', 'c.id_catalogo')
            ->leftJoin('tipos_hilo as t', 'p.id_tipo', '=', 't.id_tipo')
            ->leftJoin('colores as co', 'p.id_color', '=', 'co.id_color')
            ->leftJoin('anaqueles as a', 'p.id_anaquel', '=', 'a.id_anaquel')
            ->where('p.activo', 1)
            ->where('p.id_tienda', $usuario->id_tienda)
            ->orderBy('c.nombre')
            ->get([
                'p.id_producto', 'p.id_anaquel', 'p.stock_actual', 'p.stock_minimo', 'p.precio_venta',
                'p.presentacion', 'c.nombre', 't.nombre as tipo', 'co.nombre as color',
                'a.codigo as anaquel', 'a.descripcion as posicion',
            ]);
        $conteosAnaquel = $productos->countBy('id_anaquel');
        $colores = $productos->pluck('color')->filter()->unique()->sort()->values();
        $tipos = $productos->pluck('tipo')->filter()->unique()->sort()->values();

        return view('panel.localizador', compact('productos', 'anaqueles', 'selectedShelfId', 'conteosAnaquel', 'colores', 'tipos'));
    }

    public function reportes(): View
    {
        $query = DB::table('ventas as v')
            ->where('v.id_tienda', Auth::user()->id_tienda)
            ->whereBetween('v.fecha', [now()->startOfMonth()->toDateString(), now()->endOfMonth()->toDateString()])
            ->where(function ($query) {
                $query->whereNull('v.notas')
                    ->orWhere(function ($query) {
                        $query->where('v.notas', 'not like', 'CANCELADA:%')
                            ->where('v.notas', 'not like', 'DEVOLUCION:%');
                    });
            });
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

        return view('panel.usuarios', [
            'usuarios' => $this->usuariosQuery()->get([
                'u.id_usuario',
                'u.nombre',
                'u.correo',
                'u.telefono',
                'u.sueldo',
                'u.rol',
                'u.activo',
                'u.ultimo_acceso',
            ]),
            'usuarioEditar' => null,
            'miembroEditar' => null,
            'equipo' => $this->equipoQuery()->get(),
            'equipoCrear' => false,
        ]);
    }

    public function editarUsuario(int $id): View
    {
        $this->assertCanManageUsers();
        $usuarioEditar = $this->usuariosAdministrablesQuery()
            ->where('u.id_usuario', $id)
            ->first(['u.id_usuario', 'u.nombre', 'u.correo', 'u.telefono', 'u.sueldo']);
        abort_if(! $usuarioEditar, 404);

        return view('panel.usuarios', [
            'usuarios' => $this->usuariosQuery()->get([
                'u.id_usuario',
                'u.nombre',
                'u.correo',
                'u.telefono',
                'u.sueldo',
                'u.rol',
                'u.activo',
                'u.ultimo_acceso',
            ]),
            'usuarioEditar' => $usuarioEditar,
            'miembroEditar' => null,
            'equipo' => $this->equipoQuery()->get(),
            'equipoCrear' => false,
        ]);
    }

    public function actualizarUsuario(Request $request, int $id): RedirectResponse
    {
        $this->assertCanManageUsers();
        $usuario = $this->usuariosAdministrablesQuery()
            ->where('u.id_usuario', $id)
            ->first(['u.id_usuario']);
        abort_if(! $usuario, 404);

        $data = $request->validate([
            'nombre' => [
                'required',
                'string',
                'max:120',
                'regex:/\A[\p{L}\p{M}]+(?:[ \'-][\p{L}\p{M}]+)*\z/u',
            ],
            'correo' => [
                'required',
                'string',
                'email',
                'max:100',
                Rule::unique('usuarios', 'correo')->ignore($id, 'id_usuario'),
            ],
            'telefono' => ['nullable', 'string', 'max:20'],
            'sueldo' => ['nullable', 'numeric', 'min:0', 'max:9999999999.99'],
            'contrasena' => ['nullable', 'string', 'min:6', 'confirmed'],
        ], [
            'nombre.regex' => 'El nombre solo puede contener letras, espacios, guiones y apóstrofes.',
            'correo.unique' => 'Ya existe una cuenta con ese correo.',
            'contrasena.confirmed' => 'Las contraseñas no coinciden.',
        ]);

        if ($request->filled('contrasena')) {
            $data['contrasena'] = Hash::make($data['contrasena']);
        } else {
            unset($data['contrasena']);
        }

        $this->usuariosAdministrablesQuery()
            ->where('u.id_usuario', $id)
            ->update($data);

        return redirect()->route('usuarios')->with('status', 'La cuenta fue actualizada correctamente.');
    }

    public function editarMiembroEquipo(int $id): View
    {
        $this->assertCanManageUsers();
        $miembroEditar = $this->equipoQuery()->where('id_empleado', $id)->first();
        abort_if(! $miembroEditar, 404);

        return view('panel.usuarios', [
            'usuarios' => $this->usuariosQuery()->get([
                'u.id_usuario', 'u.nombre', 'u.correo', 'u.telefono', 'u.sueldo',
                'u.rol', 'u.activo', 'u.ultimo_acceso',
            ]),
            'usuarioEditar' => null,
            'miembroEditar' => $miembroEditar,
            'equipo' => $this->equipoQuery()->get(),
            'equipoCrear' => false,
        ]);
    }

    public function crearMiembroEquipo(): View
    {
        $this->assertCanManageUsers();

        return view('panel.usuarios', [
            'usuarios' => $this->usuariosQuery()->get([
                'u.id_usuario', 'u.nombre', 'u.correo', 'u.telefono', 'u.sueldo',
                'u.rol', 'u.activo', 'u.ultimo_acceso',
            ]),
            'usuarioEditar' => null,
            'miembroEditar' => null,
            'equipo' => $this->equipoQuery()->get(),
            'equipoCrear' => true,
        ]);
    }

    public function storeMiembroEquipo(Request $request): RedirectResponse
    {
        $this->assertCanManageUsers();
        $data = $request->validate([
            'nombre' => ['required', 'string', 'max:120', 'regex:/\A[\p{L}\p{M}]+(?:[ \'-][\p{L}\p{M}]+)*\z/u'],
            'puesto' => ['required', 'string', 'max:100'],
            'telefono' => ['nullable', 'string', 'max:20'],
            'sueldo' => ['nullable', 'numeric', 'min:0', 'max:9999999999.99'],
        ]);

        DB::table('equipo_trabajo')->insert([
            ...$data,
            'id_tienda' => Auth::user()->id_tienda,
            'activo' => 1,
            'creado_en' => now()->toDateString(),
        ]);

        return redirect()->route('usuarios')->with('status', 'El integrante del equipo fue registrado.');
    }

    public function actualizarMiembroEquipo(Request $request, int $id): RedirectResponse
    {
        $this->assertCanManageUsers();
        abort_if(! $this->equipoQuery()->where('id_empleado', $id)->exists(), 404);
        $data = $request->validate([
            'nombre' => ['required', 'string', 'max:120', 'regex:/\A[\p{L}\p{M}]+(?:[ \'-][\p{L}\p{M}]+)*\z/u'],
            'puesto' => ['required', 'string', 'max:100'],
            'telefono' => ['nullable', 'string', 'max:20'],
            'sueldo' => ['nullable', 'numeric', 'min:0', 'max:9999999999.99'],
        ]);
        $data['actualizado_en'] = now()->toDateString();

        $this->equipoQuery()->where('id_empleado', $id)->update($data);

        return redirect()->route('usuarios')->with('status', 'El integrante del equipo fue actualizado.');
    }

    public function desactivarMiembroEquipo(int $id): RedirectResponse
    {
        $this->assertCanManageUsers();
        $updated = $this->equipoQuery()
            ->where('id_empleado', $id)
            ->where('activo', 1)
            ->update(['activo' => 0, 'actualizado_en' => now()->toDateString()]);
        abort_unless($updated === 1, 404);

        return redirect()->route('usuarios')->with('status', 'El integrante del equipo fue desactivado.');
    }

    public function desactivarUsuario(int $id): RedirectResponse
    {
        $this->assertCanManageUsers();
        $updated = $this->usuariosAdministrablesQuery()
            ->where('u.id_usuario', $id)
            ->where('u.rol', '<>', 'superadmin')
            ->where('u.activo', 1)
            ->update(['activo' => 0]);
        abort_unless($updated === 1, 404);

        return redirect()->route('usuarios')->with('status', 'La cuenta fue desactivada correctamente.');
    }

    public function aprobarAdministrador(int $id): RedirectResponse
    {
        abort_unless(Auth::user()->rol === 'superadmin', 403);

        $updated = DB::table('usuarios')
            ->where('id_usuario', $id)
            ->where('id_tienda', Auth::user()->id_tienda)
            ->where('rol', 'admin')
            ->where('activo', 0)
            ->update(['activo' => 1]);

        abort_unless($updated === 1, 404);

        return redirect()->route('usuarios')->with('status', 'La cuenta de administrador fue aprobada.');
    }

    private function assertCanManageUsers(): void
    {
        abort_unless(in_array(Auth::user()->rol, ['superadmin', 'admin'], true), 403);
    }

    private function usuariosQuery()
    {
        return DB::table('usuarios as u')
            ->where('u.id_tienda', Auth::user()->id_tienda)
            ->orderBy('u.nombre');
    }

    private function equipoQuery()
    {
        return DB::table('equipo_trabajo')
            ->where('id_tienda', Auth::user()->id_tienda)
            ->orderBy('nombre');
    }

    private function usuariosAdministrablesQuery()
    {
        if (Auth::user()->rol === 'superadmin') {
            return $this->usuariosQuery()->where(function ($query) {
                $query->where('u.rol', '<>', 'superadmin')
                    ->orWhere('u.id_usuario', Auth::id());
            });
        }

        return $this->usuariosQuery()->where('u.rol', 'empleado');
    }

}
