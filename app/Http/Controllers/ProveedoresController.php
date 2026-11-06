<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ProveedoresController extends Controller
{
    public function index(): View
    {
        $this->authorizeSupplierManagement();

        return view('panel.proveedores', [
            'proveedores' => DB::table('proveedores')->orderBy('nombre')->get(),
            'proveedorEditar' => null,
        ]);
    }

    public function edit(int $id): View
    {
        $this->authorizeSupplierManagement();
        $proveedor = DB::table('proveedores')->where('id_proveedor', $id)->first();
        abort_if(! $proveedor, 404);

        return view('panel.proveedores', [
            'proveedores' => DB::table('proveedores')->orderBy('nombre')->get(),
            'proveedorEditar' => $proveedor,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorizeSupplierManagement();
        $data = $this->validateProveedor($request);
        $data['activo'] = 1;
        $data['creado_en'] = now()->toDateString();
        DB::table('proveedores')->insert($data);

        return redirect()->route('proveedores')->with('status', 'Proveedor registrado correctamente.');
    }

    public function update(Request $request, int $id): RedirectResponse
    {
        $this->authorizeSupplierManagement();
        abort_if(! DB::table('proveedores')->where('id_proveedor', $id)->exists(), 404);
        DB::table('proveedores')->where('id_proveedor', $id)->update($this->validateProveedor($request, $id));

        return redirect()->route('proveedores')->with('status', 'Proveedor actualizado correctamente.');
    }

    public function toggleActive(int $id): RedirectResponse
    {
        $this->authorizeSupplierManagement();
        $proveedor = DB::table('proveedores')->where('id_proveedor', $id)->first();
        abort_if(! $proveedor, 404);
        DB::table('proveedores')->where('id_proveedor', $id)->update(['activo' => ! $proveedor->activo]);

        return redirect()->route('proveedores')->with('status', $proveedor->activo
            ? 'Proveedor desactivado correctamente.'
            : 'Proveedor restaurado correctamente.');
    }

    private function authorizeSupplierManagement(): void
    {
        abort_unless(in_array(Auth::user()->rol, ['superadmin', 'admin'], true), 403);
    }

    private function validateProveedor(Request $request, ?int $id = null): array
    {
        return $request->validate([
            'nombre' => ['required', 'string', 'max:120', Rule::unique('proveedores', 'nombre')->ignore($id, 'id_proveedor')],
            'telefono' => ['nullable', 'string', 'max:20'],
            'correo' => ['nullable', 'email', 'max:100'],
            'direccion' => ['nullable', 'string', 'max:200'],
            'notas' => ['nullable', 'string', 'max:150'],
        ]);
    }
}
