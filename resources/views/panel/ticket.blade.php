@extends('layouts.app')

@section('titulo', 'Ticket de venta')

@section('contenido')
<div class="flex justify-between items-center mb-12 no-print">
  <a class="btn" href="{{ route('ventas') }}">← Volver a ventas</a>
  <button class="btn btn-primary" type="button" onclick="window.print()">Imprimir / Guardar como PDF</button>
</div>
<article class="card ticket-document">
  <header class="ticket-header">
    <h1>HLazcano — Prendas de Punto</h1>
    <div>{{ $venta->sucursal_nombre }}</div>
    <div>{{ $venta->sucursal_direccion }}</div>
    @if ($venta->sucursal_telefono)<div>Tel. {{ $venta->sucursal_telefono }}</div>@endif
    <h2>Ticket {{ $venta->folio }}</h2>
  </header>
  <div class="ticket-meta">
    <div>Fecha: {{ \Illuminate\Support\Carbon::parse($venta->fecha)->format('d/m/Y') }}</div>
    <div>Cliente: {{ $venta->cliente_nombre ?? 'Cliente General' }}</div>
    @if ($venta->cliente_telefono)<div>Tel. cliente: {{ $venta->cliente_telefono }}</div>@endif
    @if ($venta->cliente_correo)<div>Correo: {{ $venta->cliente_correo }}</div>@endif
    <div>Atendió: {{ $venta->vendedor }}</div>
  </div>
  <div class="table-wrap">
    <table>
      <thead><tr><th>Cantidad</th><th>Producto</th><th>Precio</th><th>Importe</th></tr></thead>
      <tbody>
        @foreach ($detalles as $detalle)
          <tr>
            <td>{{ number_format((float) $detalle->cantidad, 2) }} {{ $detalle->unidad }}</td>
            <td>{{ $detalle->nombre_producto }} {{ $detalle->presentacion }}</td>
            <td>${{ number_format((float) $detalle->precio_unit, 2) }}</td>
            <td>${{ number_format((float) $detalle->subtotal, 2) }}</td>
          </tr>
        @endforeach
      </tbody>
    </table>
  </div>
  <div class="ticket-total">
    <div>Subtotal: ${{ number_format((float) $venta->subtotal, 2) }}</div>
    @if ((float) $venta->descuento > 0)
      <div>Descuento ({{ number_format((float) $venta->descuento, 2) }}%): -${{ number_format((float) $venta->subtotal * (float) $venta->descuento / 100, 2) }}</div>
    @endif
    <strong>Total: ${{ number_format((float) $venta->total, 2) }}</strong>
  </div>
  @if ($venta->notas)<p class="text-muted">Notas: {{ $venta->notas }}</p>@endif
  <p class="ticket-thanks">Gracias por su compra</p>
</article>
@endsection

@push('estilos')
<style>
  .ticket-document{max-width:760px;margin:0 auto;background:#fff}
  .ticket-header,.ticket-thanks{text-align:center}
  .ticket-header h1{font-size:22px;margin-bottom:6px}
  .ticket-header h2{font-size:16px;margin-top:14px}
  .ticket-meta,.ticket-total{display:grid;gap:6px;margin:20px 0}
  .ticket-total{text-align:right}
  .ticket-thanks{margin-top:28px}
  @media print{
    .sidebar,.topbar,.no-print{display:none!important}
    .app,.main-area,.content{display:block;height:auto;overflow:visible;padding:0}
    .ticket-document{max-width:none;border:0;box-shadow:none}
  }
</style>
@endpush
