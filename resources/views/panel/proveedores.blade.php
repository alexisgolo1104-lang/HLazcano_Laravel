@extends('layouts.app')

@section('titulo', 'Proveedores')

@section('contenido')
<div class="grid-2 supplier-grid">
  <section class="card">
    <div class="card-title">{{ $proveedorEditar ? 'Editar proveedor' : 'Registrar proveedor' }}</div>
    <form method="POST" action="{{ $proveedorEditar ? route('proveedores.update', $proveedorEditar->id_proveedor) : route('proveedores.store') }}">
      @csrf
      @if ($proveedorEditar) @method('PUT') @endif
      <div class="form-group mb-12"><label class="form-label" for="proveedor-nombre">Nombre *</label><input class="form-control" id="proveedor-nombre" name="nombre" value="{{ old('nombre', $proveedorEditar->nombre ?? '') }}" maxlength="120" required></div>
      <div class="form-row form-2">
        <div class="form-group"><label class="form-label" for="proveedor-telefono">Teléfono</label><input class="form-control" id="proveedor-telefono" name="telefono" type="tel" value="{{ old('telefono', $proveedorEditar->telefono ?? '') }}" maxlength="20"></div>
        <div class="form-group"><label class="form-label" for="proveedor-correo">Correo</label><input class="form-control" id="proveedor-correo" name="correo" type="email" value="{{ old('correo', $proveedorEditar->correo ?? '') }}" maxlength="100"></div>
      </div>
      <div class="form-group mb-12"><label class="form-label" for="proveedor-direccion">Dirección</label><input class="form-control" id="proveedor-direccion" name="direccion" value="{{ old('direccion', $proveedorEditar->direccion ?? '') }}" maxlength="200"></div>
      <div class="form-group mb-12"><label class="form-label" for="proveedor-notas">Notas</label><textarea class="form-control" id="proveedor-notas" name="notas" maxlength="150">{{ old('notas', $proveedorEditar->notas ?? '') }}</textarea></div>
      <button class="btn btn-primary" type="submit">{{ $proveedorEditar ? 'Guardar cambios' : 'Guardar proveedor' }}</button>
      @if ($proveedorEditar)<a class="btn btn-sm" href="{{ route('proveedores') }}">Cancelar</a>@endif
    </form>
  </section>
  <section class="card">
    <div class="card-title">Proveedores registrados</div>
    <div class="filter-bar mb-12"><label class="sr-only" for="supplier-search">Buscar proveedor</label><input class="form-control" id="supplier-search" type="search" placeholder="🔍 Buscar proveedor..." autocomplete="off"></div>
    <div class="table-wrap"><table><thead><tr><th>Nombre</th><th>Correo</th><th>Teléfono</th><th>Estado</th><th>Acciones</th></tr></thead><tbody>
      @forelse ($proveedores as $proveedor)
        <tr data-supplier-row>
          <td>{{ $proveedor->nombre }}</td><td>{{ $proveedor->correo ?? '—' }}</td><td>{{ $proveedor->telefono ?? '—' }}</td><td>{{ $proveedor->activo ? 'Activo' : 'Desactivado' }}</td>
          <td><div class="tbl-actions">
            <a class="btn btn-sm" href="{{ route('proveedores.edit', $proveedor->id_proveedor) }}">Editar</a>
            <form method="POST" action="{{ route('proveedores.estado', $proveedor->id_proveedor) }}" data-confirm="{{ $proveedor->activo ? '¿Desactivar este proveedor?' : '¿Restaurar este proveedor?' }}">
              @csrf @method('PATCH')
              <button class="btn btn-sm {{ $proveedor->activo ? 'btn-danger' : '' }}" type="submit">{{ $proveedor->activo ? 'Desactivar' : 'Restaurar' }}</button>
            </form>
          </div></td>
        </tr>
      @empty
        <tr><td colspan="5" class="text-muted">No hay proveedores registrados.</td></tr>
      @endforelse
      <tr id="supplier-no-results" hidden><td colspan="5" class="text-muted">No se encontraron coincidencias.</td></tr>
    </tbody></table></div>
  </section>
</div>
@endsection

@push('estilos')
<style>
  .sr-only{position:absolute;width:1px;height:1px;padding:0;margin:-1px;overflow:hidden;clip:rect(0,0,0,0);white-space:nowrap;border:0}
  @media(max-width:900px){.supplier-grid{grid-template-columns:1fr}}
</style>
@endpush

@push('scripts')
<script>
  document.getElementById('supplier-search')?.addEventListener('input', (event) => {
    const query = event.target.value.trim().toLocaleLowerCase();
    const rows = [...document.querySelectorAll('[data-supplier-row]')];
    let visible = 0;
    rows.forEach((row) => {
      const matches = row.textContent.toLocaleLowerCase().includes(query);
      row.hidden = !matches;
      if (matches) visible++;
    });
    const noResults = document.getElementById('supplier-no-results');
    if (noResults) noResults.hidden = visible > 0 || rows.length === 0;
  });
</script>
@endpush
