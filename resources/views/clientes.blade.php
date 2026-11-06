@extends('layouts.app')

@section('titulo', 'Clientes')

@section('contenido')
<div class="grid-2">
  <section class="card">
    <div class="card-title">{{ $clienteEditar ? 'Editar cliente' : 'Registrar cliente' }}</div>
    <form method="POST" action="{{ $clienteEditar ? route('clientes.update', $clienteEditar->id_cliente) : route('clientes.store') }}">
      @csrf
      @if ($clienteEditar) @method('PUT') @endif
      <div class="form-group mb-12"><label class="form-label" for="nombre">Nombre *</label><input class="form-control" id="nombre" name="nombre" value="{{ old('nombre', $clienteEditar->nombre ?? '') }}" maxlength="120" required></div>
      <div class="form-row form-2">
        <div class="form-group"><label class="form-label" for="telefono">Teléfono</label><input class="form-control" id="telefono" name="telefono" value="{{ old('telefono', $clienteEditar->telefono ?? '') }}" maxlength="20"></div>
        <div class="form-group"><label class="form-label" for="correo">Correo</label><input class="form-control" id="correo" name="correo" type="email" value="{{ old('correo', $clienteEditar->correo ?? '') }}" maxlength="100"></div>
      </div>
      <div class="form-row form-2">
        <div class="form-group"><label class="form-label" for="tipo_cliente">Tipo de cliente</label><input class="form-control" id="tipo_cliente" name="tipo_cliente" value="{{ old('tipo_cliente', $clienteEditar->tipo_cliente ?? '') }}" maxlength="150"></div>
        <div class="form-group"><label class="form-label" for="rfc">RFC</label><input class="form-control" id="rfc" name="rfc" value="{{ old('rfc', $clienteEditar->rfc ?? '') }}" maxlength="20"></div>
      </div>
      <div class="form-group mb-12"><label class="form-label" for="direccion">Dirección</label><input class="form-control" id="direccion" name="direccion" value="{{ old('direccion', $clienteEditar->direccion ?? '') }}" maxlength="200"></div>
      <div class="form-group mb-12"><label class="form-label" for="notas">Notas</label><textarea class="form-control" id="notas" name="notas" maxlength="150">{{ old('notas', $clienteEditar->notas ?? '') }}</textarea></div>
      <button class="btn btn-primary" type="submit">{{ $clienteEditar ? 'Guardar cambios' : 'Guardar cliente' }}</button>
      @if ($clienteEditar)
        <a class="btn btn-sm" href="{{ route('clientes') }}">Cancelar</a>
      @endif
    </form>
  </section>
  <section class="card">
    <div class="card-title">Clientes registrados</div>
    <div class="table-wrap"><table><thead><tr><th>Cliente</th><th>Teléfono</th><th>Correo</th><th>Tipo</th><th>RFC</th><th>Acciones</th></tr></thead><tbody>
      @forelse ($clientes as $cliente)
        <tr>
          <td>{{ $cliente->nombre }}</td><td>{{ $cliente->telefono ?? '—' }}</td><td>{{ $cliente->correo ?? '—' }}</td><td>{{ $cliente->tipo_cliente ?? '—' }}</td><td>{{ $cliente->rfc ?? '—' }}</td>
          <td>
            <div class="tbl-actions">
              <a class="btn btn-sm" href="{{ route('clientes.edit', $cliente->id_cliente) }}">Editar</a>
              <form method="POST" action="{{ route('clientes.destroy', $cliente->id_cliente) }}" data-confirm="¿Desactivar este cliente?">
                @csrf @method('DELETE')
                <button class="btn btn-sm btn-danger" type="submit">Desactivar</button>
              </form>
            </div>
          </td>
        </tr>
      @empty
        <tr><td colspan="6" class="text-muted">No hay clientes registrados.</td></tr>
      @endforelse
    </tbody></table></div>
  </section>
</div>
@endsection
