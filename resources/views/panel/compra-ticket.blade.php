<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Ticket de compra {{ $compra->folio ?: '#'.$compra->id_compra }} · HLazcano</title>
  <style>
    *{box-sizing:border-box}
    body{margin:0;background:#f3f4f6;color:#111;font-family:Arial,sans-serif;font-size:12px;line-height:1.4}
    .ticket-actions{display:flex;justify-content:center;gap:10px;padding:18px}
    .ticket-actions a,.ticket-actions button{padding:8px 12px;border:1px solid #d1d5db;border-radius:6px;background:#fff;color:#111;text-decoration:none;font-size:13px;cursor:pointer}
    .ticket-actions button{background:#2563eb;border-color:#2563eb;color:#fff}
    .ticket-paper{width:80mm;margin:0 auto 24px;padding:5mm;background:#fff;box-shadow:0 1px 8px #0002}
    .ticket-header,.ticket-thanks{text-align:center}
    .ticket-header h1{margin:0 0 4px;font-size:16px}
    .ticket-header h2{margin:12px 0 0;font-size:13px}
    .ticket-meta{display:grid;gap:3px;margin:14px 0}
    .ticket-divider{border:0;border-top:1px dashed #777;margin:10px 0}
    .ticket-items{width:100%;border-collapse:collapse;table-layout:fixed;font-size:11px}
    .ticket-items th,.ticket-items td{padding:5px 2px;text-align:left;vertical-align:top;overflow-wrap:anywhere}
    .ticket-items th{border-bottom:1px solid #777}
    .ticket-items th:first-child,.ticket-items td:first-child{width:19%}
    .ticket-items th:nth-child(2),.ticket-items td:nth-child(2){width:39%}
    .ticket-items th:nth-child(3),.ticket-items td:nth-child(3){width:20%;text-align:right}
    .ticket-items th:last-child,.ticket-items td:last-child{width:22%;text-align:right}
    .ticket-total{display:grid;gap:4px;margin-top:10px;text-align:right}
    .ticket-total strong{font-size:14px}
    .ticket-notes{margin-top:12px;overflow-wrap:anywhere}
    .ticket-thanks{margin:18px 0 2px;font-weight:600}
    @media print{
      @page{size:80mm auto;margin:0}
      body{width:80mm;background:#fff;font-size:11px}
      .ticket-actions{display:none}
      .ticket-paper{width:80mm;margin:0;padding:4mm;box-shadow:none}
      .ticket-items tr,.ticket-total,.ticket-meta{break-inside:avoid}
    }
    @media screen and (max-width:420px){
      .ticket-paper{width:100%;max-width:80mm}
      .ticket-actions{padding:12px}
    }
  </style>
</head>
<body>
<div class="ticket-actions">
  <a href="{{ route('compras') }}" onclick="if (window.opener && !window.opener.closed) { window.opener.focus(); window.close(); if (!window.closed) window.location.href = this.href; return false; }">← Volver a compras</a>
  <button type="button" onclick="window.print()">Imprimir ticket</button>
</div>
<main class="ticket-paper">
  <article>
    <header class="ticket-header">
      <h1>HLazcano — Prendas de Punto</h1>
      @if ($compra->sucursal_direccion)<div>{{ $compra->sucursal_direccion }}</div>@endif
      @if ($compra->sucursal_telefono)<div>Tel. {{ $compra->sucursal_telefono }}</div>@endif
      <h2>Compra {{ $compra->folio ?: '#'.$compra->id_compra }}</h2>
    </header>
    <div class="ticket-meta">
      <div>Fecha: {{ \Illuminate\Support\Carbon::parse($compra->fecha)->format('d/m/Y') }}</div>
      <div>Proveedor: {{ $compra->proveedor_nombre }}</div>
      @if ($compra->proveedor_telefono)<div>Tel. proveedor: {{ $compra->proveedor_telefono }}</div>@endif
      @if ($compra->proveedor_correo)<div>Correo: {{ $compra->proveedor_correo }}</div>@endif
      <div>Registró: {{ $compra->usuario_nombre }}</div>
    </div>
    <hr class="ticket-divider">
    <table class="ticket-items">
      <thead><tr><th>Cant.</th><th>Producto</th><th>P. compra</th><th>Importe</th></tr></thead>
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
    <hr class="ticket-divider">
    <div class="ticket-total">
      <strong>Total: ${{ number_format((float) $compra->total, 2) }}</strong>
    </div>
    @if ($compra->notas)<p class="ticket-notes">Notas: {{ $compra->notas }}</p>@endif
    <p class="ticket-thanks">Compra registrada</p>
  </article>
</main>
</body>
</html>
