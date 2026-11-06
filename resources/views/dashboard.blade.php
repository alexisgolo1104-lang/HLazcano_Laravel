@extends('layouts.app')

@section('titulo', 'Dashboard')

@push('estilos')
<style>
  .dashboard-stats{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:14px;margin-bottom:20px}
  .dashboard-stat{min-width:0}
  .dashboard-stat .stat-num{color:var(--text)}
  .dashboard-stat:first-child .stat-num{color:#2563EB}
  .dashboard-stat.alert-stat .stat-num{color:var(--danger)}
  .dashboard-charts{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:16px}
  .dashboard-card{min-width:0;min-height:300px}
  .dashboard-card .card-title{margin-bottom:12px}
  .chart-link{font-size:12px;font-weight:500;color:#1E3A8A;text-decoration:none;border:1px solid var(--border);border-radius:7px;padding:4px 9px}
  .chart-link:hover{background:var(--surf2)}
  .sales-chart{display:block;width:100%;height:auto;overflow:visible}
  .sales-chart .grid-line{stroke:#E5EAF3;stroke-width:1}
  .sales-chart .axis-label{fill:#5A6075;font-size:11px}
  .sales-chart .sales-line{fill:none;stroke:#2563EB;stroke-width:3;stroke-linecap:round;stroke-linejoin:round}
  .sales-chart .sales-point{fill:#2563EB;stroke:#fff;stroke-width:2}
  .stock-list{display:grid;gap:17px}
  .stock-item{display:grid;grid-template-columns:minmax(110px,1fr) minmax(90px,1.7fr) auto;gap:10px;align-items:center;font-size:12px}
  .stock-name{overflow:hidden;text-overflow:ellipsis;white-space:nowrap;color:var(--muted)}
  .stock-track{height:10px;background:#EDF0F7;border-radius:99px;overflow:hidden}
  .stock-bar{height:100%;background:linear-gradient(90deg,#2563EB,#60A5FA);border-radius:inherit}
  .stock-value{white-space:nowrap;color:var(--text);font-variant-numeric:tabular-nums}
  .customer-chart{display:grid;grid-template-columns:minmax(130px, .8fr) minmax(180px,1.2fr);gap:18px;align-items:center;min-height:205px}
  .customer-donut{display:block;width:100%;max-width:190px;margin:auto}
  .customer-donut circle{fill:none;stroke-width:18}
  .customer-donut .donut-track{stroke:#EDF0F7}
  .customer-donut .donut-total{fill:var(--text);font-size:18px;font-weight:700;text-anchor:middle;dominant-baseline:middle}
  .customer-legend{list-style:none;display:grid;gap:12px}
  .customer-legend li{display:grid;grid-template-columns:10px minmax(0,1fr) auto;gap:9px;align-items:center;font-size:12px}
  .legend-dot{width:10px;height:10px;border-radius:3px}
  .legend-name{color:var(--muted)}
  .legend-count{font-weight:600;white-space:nowrap}
  .dashboard-alerts{display:grid;gap:9px}
  .dashboard-alert{display:flex;align-items:center;gap:9px;padding:10px 12px;background:var(--dangbg);border:1px solid var(--dangli);border-radius:9px;font-size:12px}
  .alert-indicator{width:9px;height:9px;flex:0 0 auto;border-radius:50%;background:var(--danger)}
  .alert-product{min-width:0;flex:1}
  .alert-product strong{display:block;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;font-weight:600}
  .alert-product span{color:var(--muted)}
  .dashboard-empty{padding:36px 12px;text-align:center;color:var(--muted);font-size:12px}
  @media(max-width:950px){.dashboard-stats{grid-template-columns:repeat(2,minmax(0,1fr))}.dashboard-charts{grid-template-columns:1fr}}
  @media(max-width:560px){.dashboard-stats{grid-template-columns:1fr 1fr;gap:9px}.dashboard-stat{padding:13px}.dashboard-stat .stat-num{font-size:23px}.dashboard-card{padding:14px}.stock-item{grid-template-columns:minmax(90px,1fr) minmax(55px,1fr) auto;gap:7px;font-size:11px}.customer-chart{grid-template-columns:1fr;gap:8px}.customer-donut{max-width:145px}.customer-legend{gap:8px}}
</style>
@endpush

@section('contenido')
@php
  $chartLeft = 44;
  $chartRight = 724;
  $chartTop = 24;
  $chartBottom = 190;
  $totalMaxVenta = max(array_column($ventasPorDia, 'total'));
  $maxVenta = $totalMaxVenta ?: 1;
  $ventasPuntos = array_map(function ($indice, $venta) use ($chartLeft, $chartRight, $chartTop, $chartBottom, $maxVenta) {
      $x = $chartLeft + ($chartRight - $chartLeft) * $indice / 6;
      $y = $chartBottom - ($chartBottom - $chartTop) * $venta['total'] / $maxVenta;

      return ['x' => $x, 'y' => $y, 'venta' => $venta];
  }, array_keys($ventasPorDia), $ventasPorDia);
  $lineaVentas = implode(' ', array_map(fn ($punto) => $punto['x'] . ',' . $punto['y'], $ventasPuntos));
  $maxStock = max((float) ($productosConStock->max('stock_actual') ?? 0), 0);
  $totalClientesPorTipo = array_sum(array_column($clientesPorTipo, 'cantidad'));
  $circunferenciaDonut = 2 * pi() * 52;
  $desfaseDonut = 0;
@endphp

<div class="dashboard-stats">
  <div class="stat-card dashboard-stat">
    <div class="stat-num">${{ number_format((float) $ventasMes, 2) }}</div>
    <div class="stat-lbl">Ventas del mes</div>
    <div class="stat-delta up">{{ number_format($ventasCount) }} {{ $ventasCount === 1 ? 'venta' : 'ventas' }} este mes</div>
  </div>
  <div class="stat-card dashboard-stat">
    <div class="stat-num">{{ number_format($productosCount) }}</div>
    <div class="stat-lbl">Productos registrados</div>
    <div class="stat-delta up">Inventario activo</div>
  </div>
  <div class="stat-card dashboard-stat alert-stat">
    <div class="stat-num">{{ number_format($alertasCount) }}</div>
    <div class="stat-lbl">Alertas activas</div>
    <div class="stat-delta dn">{{ number_format($alertasCount) }} {{ $alertasCount === 1 ? 'producto requiere' : 'productos requieren' }} atención</div>
  </div>
  <div class="stat-card dashboard-stat">
    <div class="stat-num">{{ number_format($clientesCount) }}</div>
    <div class="stat-lbl">Clientes registrados</div>
    <div class="stat-delta up">Clientes activos</div>
  </div>
</div>

<div class="dashboard-charts">
  <section class="card dashboard-card">
    <div class="card-title">
      <span>Ventas por día — últimos 7 días</span>
      <a class="chart-link" href="{{ route('reportes') }}">Ver reporte →</a>
    </div>
    <svg class="sales-chart" viewBox="0 0 760 225" role="img" aria-label="Gráfica de ventas diarias de los últimos siete días">
      @foreach ([0, 0.5, 1] as $nivel)
        @php
          $y = $chartBottom - ($chartBottom - $chartTop) * $nivel;
          $valor = $totalMaxVenta * $nivel;
        @endphp
        <line class="grid-line" x1="{{ $chartLeft }}" y1="{{ $y }}" x2="{{ $chartRight }}" y2="{{ $y }}"/>
        <text class="axis-label" x="2" y="{{ $y + 4 }}">${{ number_format($valor, 0) }}</text>
      @endforeach
      <polyline class="sales-line" points="{{ $lineaVentas }}"/>
      @foreach ($ventasPuntos as $punto)
        <circle class="sales-point" cx="{{ $punto['x'] }}" cy="{{ $punto['y'] }}" r="5">
          <title>{{ $punto['venta']['etiqueta'] }}: ${{ number_format($punto['venta']['total'], 2) }} ({{ $punto['venta']['cantidad'] }} ventas)</title>
        </circle>
        <text class="axis-label" x="{{ $punto['x'] }}" y="216" text-anchor="middle">{{ $punto['venta']['etiqueta'] }}</text>
      @endforeach
    </svg>
  </section>

  <section class="card dashboard-card">
    <div class="card-title">
      <span>Inventario: stock actual por producto</span>
      <a class="chart-link" href="{{ route('inventario') }}">Ver inventario →</a>
    </div>
    @if ($productosConStock->isNotEmpty())
      <div class="stock-list">
        @foreach ($productosConStock as $producto)
          @php($porcentajeStock = $maxStock > 0 ? max(2, (float) $producto->stock_actual / $maxStock * 100) : 0)
        <div class="stock-item">
          <span class="stock-name" title="{{ $producto->nombre ?? 'Producto' }} {{ $producto->presentacion }}">{{ $producto->nombre ?? 'Producto' }} {{ $producto->presentacion }}</span>
          <div class="stock-track" role="img" aria-label="Stock de {{ $producto->nombre ?? 'producto' }}: {{ number_format((float) $producto->stock_actual, 2) }} {{ $producto->unidad ?? '' }}">
            <div class="stock-bar" style="width:{{ $porcentajeStock }}%"></div>
          </div>
          <span class="stock-value">{{ number_format((float) $producto->stock_actual, 2) }} {{ $producto->unidad }}</span>
        </div>
        @endforeach
      </div>
    @else
      <div class="dashboard-empty">No hay productos activos para mostrar.</div>
    @endif
  </section>

  <section class="card dashboard-card">
    <div class="card-title"><span>Clientes por tipo</span></div>
    @if ($totalClientesPorTipo > 0)
      <div class="customer-chart">
        <svg class="customer-donut" viewBox="0 0 140 140" role="img" aria-label="Distribución de {{ $totalClientesPorTipo }} clientes por tipo">
          <circle class="donut-track" cx="70" cy="70" r="52"/>
          @foreach ($clientesPorTipo as $tipo)
            @php($segmentoDonut = $tipo['cantidad'] / $totalClientesPorTipo * $circunferenciaDonut)
            <circle cx="70" cy="70" r="52" stroke="{{ $tipo['color'] }}" stroke-dasharray="{{ $segmentoDonut }} {{ $circunferenciaDonut - $segmentoDonut }}" stroke-dashoffset="{{ -$desfaseDonut }}" transform="rotate(-90 70 70)">
              <title>{{ $tipo['tipo'] }}: {{ $tipo['cantidad'] }}</title>
            </circle>
            @php($desfaseDonut += $segmentoDonut)
          @endforeach
          <text class="donut-total" x="70" y="70">{{ $totalClientesPorTipo }}</text>
        </svg>
        <ul class="customer-legend">
          @foreach ($clientesPorTipo as $tipo)
            <li>
              <span class="legend-dot" style="background:{{ $tipo['color'] }}"></span>
              <span class="legend-name">{{ $tipo['tipo'] }}</span>
              <span class="legend-count">{{ number_format($tipo['cantidad']) }} {{ $tipo['cantidad'] === 1 ? 'cliente' : 'clientes' }}</span>
            </li>
          @endforeach
        </ul>
      </div>
    @else
      <div class="dashboard-empty">Todavía no hay clientes activos registrados.</div>
    @endif
  </section>

  <section class="card dashboard-card">
    <div class="card-title">
      <span>Productos con alerta</span>
      <a class="chart-link" href="{{ route('alertas') }}">Ver alertas →</a>
    </div>
    @if ($alertasProductos->isNotEmpty())
      <div class="dashboard-alerts">
        @foreach ($alertasProductos as $producto)
          <div class="dashboard-alert">
            <span class="alert-indicator" aria-hidden="true"></span>
            <div class="alert-product">
              <strong>{{ $producto->nombre ?? 'Producto' }} {{ $producto->presentacion }}</strong>
              <span>Stock: {{ number_format((float) $producto->stock_actual, 2) }} {{ $producto->unidad }} · Mín.: {{ number_format((float) $producto->stock_minimo, 2) }} {{ $producto->unidad }}</span>
            </div>
          </div>
        @endforeach
      </div>
    @else
      <div class="dashboard-empty">No hay productos con bajo stock.</div>
    @endif
  </section>
</div>
@endsection
