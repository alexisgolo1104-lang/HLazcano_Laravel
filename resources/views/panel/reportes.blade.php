@extends('layouts.app')

@section('titulo', 'Reportes')

@section('contenido')
<div class="grid-2">
  <div class="stat-card"><div class="stat-num">${{ number_format((float) $total, 2) }}</div><div class="stat-lbl">Ingresos del mes</div></div>
  <div class="stat-card"><div class="stat-num">{{ number_format($cantidad) }}</div><div class="stat-lbl">Ventas del mes</div></div>
</div>
<section class="card">
  <div class="card-title">Ventas del mes en curso</div>
  <div class="table-wrap"><table><thead><tr><th>Folio</th><th>Fecha</th><th>Subtotal</th><th>Descuento</th><th>Total</th></tr></thead><tbody>
    @forelse ($ventas as $venta)
      <tr><td>{{ $venta->folio }}</td><td>{{ $venta->fecha }}</td><td>${{ number_format((float) $venta->subtotal, 2) }}</td><td>{{ number_format((float) $venta->descuento, 2) }}%</td><td>${{ number_format((float) $venta->total, 2) }}</td></tr>
    @empty
      <tr><td colspan="5" class="text-muted">No hay ventas en este periodo.</td></tr>
    @endforelse
  </tbody></table></div>
</section>
@endsection
