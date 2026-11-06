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
        return view('clientes', [
            'clientes' => $this->clientesQuery()->get(['c.*']),
            'clienteEditar' => null,
        ]);
    }

    public function edit(int $id): View
    {
        $cliente = $this->clienteQuery($id)->first();
        abort_if(! $cliente, 404);

        return view('clientes', [
            'clientes' => $this->clientesQuery()->get(['c.*']),
            'clienteEditar' => $cliente,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $usuario = Auth::user();
        $data = $this->validateCliente($request);
        $data['id_tienda'] = $usuario->id_tienda;
        $data['activo'] = 1;
        $data['creado_en'] = now()->toDateString();

        Cliente::query()->create($data);

        return redirect()->route('clientes')->with('status', 'Cliente registrado correctamente.');
    }

    public function update(Request $request, int $id): RedirectResponse
    {
        $cliente = $this->clienteQuery($id)->first();
        abort_if(! $cliente, 404);

        $data = $this->validateCliente($request);
        $data['actualizado_en'] = now()->toDateString();

        DB::table('clientes')
            ->where('id_cliente', $id)
            ->update($data);

        return redirect()->route('clientes')->with('status', 'Cliente actualizado correctamente.');
    }

    public function destroy(int $id): RedirectResponse
    {
        $updated = $this->clienteQuery($id)->update([
            'activo' => 0,
            'actualizado_en' => now()->toDateString(),
        ]);
        abort_if(! $updated, 404);

        return redirect()->route('clientes')->with('status', 'Cliente desactivado correctamente.');
    }

    private function validateCliente(Request $request): array
    {
        return $request->validate([
            'nombre' => ['required', 'string', 'max:120'],
            'telefono' => ['nullable', 'string', 'max:20'],
            'correo' => ['nullable', 'email', 'max:100'],
            'direccion' => ['nullable', 'string', 'max:200'],
            'rfc' => ['nullable', 'string', 'max:20'],
            'tipo_cliente' => ['nullable', 'string', 'max:150'],
            'notas' => ['nullable', 'string', 'max:150'],
        ]);
    }

    private function clientesQuery()
    {
        return DB::table('clientes as c')
            ->where('c.activo', 1)
            ->where('c.id_tienda', Auth::user()->id_tienda)
            ->orderBy('c.nombre');
    }

    private function clienteQuery(int $id)
    {
        $usuario = Auth::user();

        return DB::table('clientes')
            ->where('id_cliente', $id)
            ->where('activo', 1)
            ->where('id_tienda', $usuario->id_tienda);
    }
}
