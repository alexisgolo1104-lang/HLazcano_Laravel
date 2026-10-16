@extends('layouts.app')

@section('titulo', 'Dashboard')

@section('contenido')
<div class="grid-4">
  <div class="stat-card"><div class="stat-num">${{ number_format((float) $ventasMes, 2) }}</div><div class="stat-lbl">Ventas del mes</div></div>
  <div class="stat-card"><div class="stat-num">{{ number_format($ventasCount) }}</div><div class="stat-lbl">Transacciones del mes</div></div>
  <div class="stat-card"><div class="stat-num">{{ number_format($productosCount) }}</div><div class="stat-lbl">Productos activos</div></div>
  <div class="stat-card"><div class="stat-num">{{ number_format($alertasCount) }}</div><div class="stat-lbl">Productos con bajo stock</div></div>
  <div class="stat-card"><div class="stat-num">{{ number_format($usuariosCount) }}</div><div class="stat-lbl">Usuarios activos</div></div>
  <div class="stat-card"><div class="stat-num">{{ number_format($clientesCount) }}</div><div class="stat-lbl">Clientes activos</div></div>
</div>
<section class="card">
  <div class="card-title">Últimas ventas</div>
  <div class="table-wrap">
    <table>
      <thead><tr><th>Folio</th><th>Fecha</th><th>Cliente</th><th>Sucursal</th><th>Total</th></tr></thead>
      <tbody>
      @forelse ($ultimasVentas as $venta)
        <tr>
          <td>{{ $venta->folio }}</td>
          <td>{{ $venta->fecha }}</td>
          <td>{{ $venta->cliente ?? 'Venta directa' }}</td>
          <td>{{ $venta->tienda ?? '—' }}</td>
          <td>${{ number_format((float) $venta->total, 2) }}</td>
        </tr>
      @empty
        <tr><td colspan="5" class="text-muted">Todavía no hay ventas registradas.</td></tr>
      @endforelse
      </tbody>
    </table>
  </div>
</section>
@endsection
