@extends('layouts.app')

@section('titulo', 'Usuarios')

@section('contenido')
<section class="card">
  <div class="card-title">Cuentas del sistema</div>
  <div class="table-wrap"><table><thead><tr><th>Nombre</th><th>Correo</th><th>Rol</th><th>Sucursal</th><th>Estado</th><th>Último acceso</th></tr></thead><tbody>
    @forelse ($usuarios as $usuario)
      <tr><td>{{ $usuario->nombre }}</td><td>{{ $usuario->correo }}</td><td>{{ ucfirst($usuario->rol) }}</td><td>{{ $usuario->tienda ?? '—' }}</td><td>{{ $usuario->activo ? 'Activo' : 'Desactivado' }}</td><td>{{ $usuario->ultimo_acceso ?? '—' }}</td></tr>
    @empty
      <tr><td colspan="6" class="text-muted">No hay usuarios registrados.</td></tr>
    @endforelse
  </tbody></table></div>
</section>
@endsection
