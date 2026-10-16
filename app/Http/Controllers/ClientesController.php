<?php

namespace App\Http\Controllers;

use App\Models\Cliente;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class ClientesController extends Controller
{
    public function index(): View
    {
        $usuario = Auth::user();
        $clientes = DB::table('clientes as c')
            ->leftJoin('tiendas as t', 'c.id_tienda', '=', 't.id_tienda')
            ->where('c.activo', 1)
            ->when($usuario->rol !== 'superadmin', fn ($query) => $query->where('c.id_tienda', $usuario->id_tienda))
            ->orderBy('c.nombre')
            ->get(['c.*', 't.nombre as tienda']);

        return view('clientes', compact('clientes'));
    }

    public function store(Request $request): RedirectResponse
    {
        $usuario = Auth::user();
        $data = $request->validate([
            'nombre' => ['required', 'string', 'max:120'],
            'telefono' => ['nullable', 'string', 'max:20'],
            'correo' => ['nullable', 'email', 'max:100'],
            'direccion' => ['nullable', 'string', 'max:200'],
            'rfc' => ['nullable', 'string', 'max:20'],
            'tipo_cliente' => ['nullable', 'string', 'max:150'],
            'notas' => ['nullable', 'string', 'max:150'],
        ]);
        $data['id_tienda'] = $usuario->id_tienda;
        $data['activo'] = 1;
        $data['creado_en'] = now()->toDateString();

        Cliente::query()->create($data);

        return redirect()->route('clientes')->with('status', 'Cliente registrado correctamente.');
    }
}
