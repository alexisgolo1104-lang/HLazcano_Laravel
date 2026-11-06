<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class EgresosController extends Controller
{
    private const CATEGORIES = [
        'servicio_luz' => 'Servicio de luz',
        'servicio_agua' => 'Servicio de agua',
        'sueldo' => 'Sueldo',
        'otros_gastos' => 'Otros gastos',
    ];

    public function index(Request $request): View
    {
        $this->authorizeSuperadmin();
        $data = $request->validate(['mes' => ['nullable', 'date_format:Y-m']]);
        $month = $data['mes'] ?? now()->format('Y-m');
        $start = now()->createFromFormat('Y-m-d', $month.'-01')->startOfMonth()->toDateString();
        $end = now()->createFromFormat('Y-m-d', $month.'-01')->endOfMonth()->toDateString();
        $storeId = (int) Auth::user()->id_tienda;

        $expensesQuery = DB::table('gastos as g')
            ->leftJoin('usuarios as usuario_empleado', function ($join) {
                $join->on('g.id_empleado', '=', 'usuario_empleado.id_usuario')
                    ->where('g.origen_empleado', '=', 'usuario');
            })
            ->leftJoin('equipo_trabajo as miembro', function ($join) {
                $join->on('g.id_empleado', '=', 'miembro.id_empleado')
                    ->where('g.origen_empleado', '=', 'equipo');
            })
            ->where('g.id_tienda', $storeId)
            ->whereBetween('g.fecha', [$start, $end]);
        $expenses = (clone $expensesQuery)
            ->orderByDesc('g.fecha')
            ->orderByDesc('g.id_gastos')
            ->get([
                'g.id_gastos', 'g.concepto', 'g.categoria', 'g.monto', 'g.fecha', 'g.notas',
                DB::raw('COALESCE(usuario_empleado.nombre, miembro.nombre) as empleado'),
            ]);

        $purchasesQuery = DB::table('compras as c')
            ->leftJoin('proveedores as p', 'c.id_proveedor', '=', 'p.id_proveedor')
            ->where('c.id_tienda', $storeId)
            ->whereBetween('c.fecha', [$start, $end]);
        $purchases = (clone $purchasesQuery)
            ->orderByDesc('c.fecha')
            ->orderByDesc('c.id_compra')
            ->get(['c.id_compra', 'c.fecha', 'c.folio', 'c.total', 'p.nombre as proveedor']);

        $totals = [
            'servicios' => (clone $expensesQuery)->whereIn('g.categoria', ['servicio_luz', 'servicio_agua'])->sum('g.monto'),
            'sueldos' => (clone $expensesQuery)->where('g.categoria', 'sueldo')->sum('g.monto'),
            'otros' => (clone $expensesQuery)->where('g.categoria', 'otros_gastos')->sum('g.monto'),
            'compras' => (clone $purchasesQuery)->sum('c.total'),
            'registrados' => (clone $expensesQuery)->sum('g.monto'),
        ];
        $totals['total'] = $totals['registrados'] + $totals['compras'];

        $empleadosConCuenta = DB::table('usuarios')
            ->where('id_tienda', $storeId)
            ->whereIn('rol', ['empleado', 'admin'])
            ->where('activo', 1)
            ->orderBy('nombre')
            ->get(['id_usuario', 'nombre', 'rol', 'sueldo'])
            ->map(fn ($empleado) => (object) [
                'destinatario' => 'usuario:'.$empleado->id_usuario,
                'nombre' => $empleado->nombre,
                'puesto' => $empleado->rol === 'admin' ? 'Administrador' : 'Empleado',
                'cuenta' => true,
                'sueldo' => $empleado->sueldo,
            ]);
        $empleadosSinCuenta = DB::table('equipo_trabajo')
            ->where('id_tienda', $storeId)
            ->where('activo', 1)
            ->orderBy('nombre')
            ->get(['id_empleado', 'nombre', 'puesto', 'sueldo'])
            ->map(fn ($empleado) => (object) [
                'destinatario' => 'equipo:'.$empleado->id_empleado,
                'nombre' => $empleado->nombre,
                'puesto' => $empleado->puesto,
                'cuenta' => false,
                'sueldo' => $empleado->sueldo,
            ]);
        $empleados = $empleadosConCuenta
            ->concat($empleadosSinCuenta)
            ->sortBy('nombre', SORT_NATURAL | SORT_FLAG_CASE)
            ->values();

        return view('egresos', [
            'empleados' => $empleados,
            'expenses' => $expenses,
            'purchases' => $purchases,
            'totals' => $totals,
            'categories' => self::CATEGORIES,
            'month' => $month,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorizeSuperadmin();
        $data = $request->validate([
            'categoria' => ['required', 'in:servicio_luz,servicio_agua,sueldo,otros_gastos'],
            'concepto' => ['required', 'string', 'max:150'],
            'monto' => ['required', 'numeric', 'gt:0', 'max:9999999999.99'],
            'fecha' => ['required', 'date_format:Y-m-d', 'before_or_equal:today'],
            'destinatario' => ['nullable', 'required_if:categoria,sueldo', 'prohibited_unless:categoria,sueldo', 'regex:/\A(usuario|equipo):[1-9][0-9]*\z/'],
            'notas' => ['nullable', 'string', 'max:150'],
        ]);

        $employeeId = null;
        $employeeSource = null;
        if ($data['categoria'] === 'sueldo') {
            [$employeeSource, $employeeId] = explode(':', $data['destinatario'], 2);
            $employeeId = (int) $employeeId;
            $employeeIsEligible = $employeeSource === 'usuario'
                ? DB::table('usuarios')
                    ->where('id_usuario', $employeeId)
                    ->where('id_tienda', Auth::user()->id_tienda)
                    ->whereIn('rol', ['empleado', 'admin'])
                    ->where('activo', 1)
                    ->exists()
                : DB::table('equipo_trabajo')
                    ->where('id_empleado', $employeeId)
                    ->where('id_tienda', Auth::user()->id_tienda)
                    ->where('activo', 1)
                    ->exists();
            if (! $employeeIsEligible) {
                return back()->withErrors(['destinatario' => 'Selecciona un integrante activo de esta sucursal.'])->withInput();
            }
        }

        DB::table('gastos')->insert([
            'concepto' => $data['concepto'],
            'categoria' => $data['categoria'],
            'monto' => $data['monto'],
            'id_tienda' => Auth::user()->id_tienda,
            'id_usuario' => Auth::id(),
            'id_empleado' => $employeeId,
            'origen_empleado' => $employeeSource,
            'notas' => $data['notas'] ?? null,
            'fecha' => $data['fecha'],
        ]);

        return redirect()->route('egresos', ['mes' => substr($data['fecha'], 0, 7)])
            ->with('status', 'El egreso fue registrado correctamente.');
    }

    private function authorizeSuperadmin(): void
    {
        abort_unless(Auth::user()?->rol === 'superadmin', 403);
    }
}