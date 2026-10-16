@extends('layouts.app')

@section('titulo', 'Inventario')

@section('contenido')
<div class="flex justify-between items-center mb-12">
  <div class="text-muted text-sm">{{ $productos->count() }} productos registrados</div>
  @if (in_array(auth()->user()->rol, ['superadmin', 'admin'], true))
    <a class="btn btn-primary" href="{{ route('inventario.create') }}">+ Nuevo producto</a>
  @endif
</div>
<div class="filter-bar">
  <input id="filtro-producto" class="form-control" placeholder="Buscar producto...">
  <select id="filtro-tipo" class="form-control"><option value="">Todos los tipos</option>@foreach ($tipos as $tipo)<option>{{ $tipo->nombre }}</option>@endforeach</select>
  <select id="filtro-color" class="form-control"><option value="">Todos los colores</option>@foreach ($colores as $color)<option>{{ $color->nombre }}</option>@endforeach</select>
  <select id="filtro-stock" class="form-control"><option value="">Todo el stock</option><option value="bajo">Bajo stock</option><option value="normal">Normal</option></select>
</div>
<div class="table-wrap">
  <table id="tabla-inventario">
    <thead><tr><th>Producto</th><th>Tipo</th><th>Color</th><th>Stock</th><th>Mín.</th><th>P. compra</th><th>P. venta</th><th>Sucursal</th><th>Anaquel</th><th>Acciones</th></tr></thead>
    <tbody>
    @forelse ($productos as $producto)
      @php($bajo = (float) $producto->stock_actual <= (float) $producto->stock_minimo)
      <tr data-tipo="{{ $producto->tipo_nombre }}" data-color="{{ $producto->color_nombre }}" data-stock="{{ $bajo ? 'bajo' : 'normal' }}">
        <td><strong>{{ $producto->catalogo_nombre ?? '—' }}</strong><br><span class="text-muted">{{ $producto->presentacion }}</span></td>
        <td>{{ $producto->tipo_nombre ?? '—' }}</td>
        <td><span class="color-pill"><span style="background:{{ $producto->color_hex ?? '#ccc' }};width:12px;height:12px;border-radius:50%;display:inline-block;border:1px solid #ccc"></span>{{ $producto->color_nombre ?? '—' }}</span></td>
        <td><span class="badge {{ $bajo ? 'badge-danger' : 'badge-ok' }}">{{ number_format((float) $producto->stock_actual, 2) }} {{ $producto->unidad_codigo }}</span></td>
        <td>{{ number_format((float) $producto->stock_minimo, 2) }} {{ $producto->unidad_codigo }}</td>
        <td>${{ number_format((float) $producto->precio_compra, 2) }}</td>
        <td>${{ number_format((float) $producto->precio_venta, 2) }}</td>
        <td>{{ $producto->tienda_nombre }}</td>
        <td>{{ $producto->anaquel_codigo ?? '—' }}</td>
        <td class="tbl-actions">
          @if (in_array(auth()->user()->rol, ['superadmin', 'admin'], true))
            <a class="btn btn-sm" href="{{ route('inventario.edit', $producto->id_producto) }}">Editar</a>
            <form method="POST" action="{{ route('inventario.destroy', $producto->id_producto) }}" onsubmit="return confirm('¿Desactivar este producto?')">
              @csrf @method('DELETE')
              <button class="btn btn-sm btn-danger" type="submit">Desactivar</button>
            </form>
          @else
            <span class="text-muted">Solo lectura</span>
          @endif
        </td>
      </tr>
    @empty
      <tr><td colspan="10" class="text-muted">No hay productos registrados.</td></tr>
    @endforelse
    </tbody>
  </table>
</div>
@endsection

@push('scripts')
<script>
  const search = document.getElementById('filtro-producto');
  const filters = ['filtro-tipo', 'filtro-color', 'filtro-stock'].map(id => document.getElementById(id));
  function filterInventory() {
    const text = search.value.trim().toLocaleLowerCase();
    const [type, color, stock] = filters.map(filter => filter.value);
    document.querySelectorAll('#tabla-inventario tbody tr[data-tipo]').forEach(row => {
      const matches = row.textContent.toLocaleLowerCase().includes(text)
        && (!type || row.dataset.tipo === type)
        && (!color || row.dataset.color === color)
        && (!stock || row.dataset.stock === stock);
      row.hidden = !matches;
    });
  }
  search.addEventListener('input', filterInventory);
  filters.forEach(filter => filter.addEventListener('change', filterInventory));
</script>
@endpush
