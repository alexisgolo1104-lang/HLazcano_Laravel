@extends('layouts.app')

@section('titulo', 'Alertas de inventario')

@section('contenido')
<section class="card">
  <div class="card-title">Productos en nivel mínimo o sin existencias</div>
  <div class="table-wrap"><table><thead><tr><th>Producto</th><th>Tipo</th><th>Existencia</th><th>Mínimo</th><th>Sucursal</th><th>Anaquel</th></tr></thead><tbody>
    @forelse ($productos as $producto)
      <tr><td>{{ $producto->nombre }}</td><td>{{ $producto->tipo ?? '—' }}</td><td><span class="badge badge-danger">{{ number_format((float) $producto->stock_actual, 2) }}</span></td><td>{{ number_format((float) $producto->stock_minimo, 2) }}</td><td>{{ $producto->tienda }}</td><td>{{ $producto->anaquel ?? '—' }}</td></tr>
    @empty
      <tr><td colspan="6" class="text-muted">No hay productos con alertas de stock.</td></tr>
    @endforelse
  </tbody></table></div>
</section>
@endsection
