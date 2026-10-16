@extends('layouts.app')

@section('titulo', 'Localizador')

@section('contenido')
<div class="card mb-12">
  <label class="form-label" for="buscar-localizador">Buscar producto, color, tipo o anaquel</label>
  <input class="form-control" id="buscar-localizador" placeholder="Escribe para filtrar">
</div>
<div class="table-wrap"><table id="tabla-localizador"><thead><tr><th>Producto</th><th>Tipo</th><th>Color</th><th>Sucursal</th><th>Anaquel</th><th>Posición</th><th>Existencia</th></tr></thead><tbody>
  @forelse ($productos as $producto)
    <tr><td>{{ $producto->nombre }}</td><td>{{ $producto->tipo ?? '—' }}</td><td>{{ $producto->color ?? '—' }}</td><td>{{ $producto->tienda ?? '—' }}</td><td>{{ $producto->anaquel ?? 'Sin asignar' }}</td><td>{{ $producto->descripcion ?? '—' }}</td><td>{{ number_format((float) $producto->stock_actual, 2) }}</td></tr>
  @empty
    <tr><td colspan="7" class="text-muted">No hay productos activos para localizar.</td></tr>
  @endforelse
</tbody></table></div>
@endsection

@push('scripts')
<script>
  document.getElementById('buscar-localizador').addEventListener('input', event => {
    const text = event.target.value.toLocaleLowerCase();
    document.querySelectorAll('#tabla-localizador tbody tr').forEach(row => { row.hidden = !row.textContent.toLocaleLowerCase().includes(text); });
  });
</script>
@endpush
