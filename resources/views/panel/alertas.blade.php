@extends('layouts.app')

@section('titulo', 'Alertas')

@section('contenido')
@php
  $definiciones = [
    'stock_critico' => ['titulo' => 'Bajo stock crítico — requiere compra urgente', 'color' => 'critical', 'nombre' => 'Crítico'],
    'stock_minimo' => ['titulo' => 'Stock en nivel mínimo — atención requerida', 'color' => 'minimum', 'nombre' => 'Alerta'],
    'sin_movimiento' => ['titulo' => 'Sin movimiento más de 30 días — revisar si hay sobrestock', 'color' => 'idle', 'nombre' => 'Sin movimiento'],
  ];
  $grupos = $alertas->groupBy('tipo_alerta');
  $filtroTipo = ['critico' => 'stock_critico', 'alerta' => 'stock_minimo', 'sin-movimiento' => 'sin_movimiento'][$filter] ?? null;
@endphp

<div class="alert-summary-grid">
  <article class="alert-stat alert-stat--critical"><strong>{{ $totales['critico'] }}</strong><span>Crítico — bajo stock</span></article>
  <article class="alert-stat alert-stat--minimum"><strong>{{ $totales['alerta'] }}</strong><span>Alerta — stock mínimo</span></article>
  <article class="alert-stat alert-stat--idle"><strong>{{ $totales['sin_movimiento'] }}</strong><span>Sin movimiento +30 días</span></article>
  <article class="alert-stat alert-stat--attended"><strong>{{ $totales['atendidas_hoy'] }}</strong><span>Atendidas hoy</span></article>
</div>

<div class="alert-toolbar">
  <nav class="alert-tabs" aria-label="Filtrar alertas">
    <a class="alert-tab {{ $filter === 'todas' ? 'active' : '' }}" href="{{ route('alertas', ['filtro' => 'todas']) }}">Todas ({{ $totales['todas'] }})</a>
    <a class="alert-tab {{ $filter === 'critico' ? 'active' : '' }}" href="{{ route('alertas', ['filtro' => 'critico']) }}"><span class="alert-tab-dot critical"></span>Crítico ({{ $totales['critico'] }})</a>
    <a class="alert-tab {{ $filter === 'alerta' ? 'active' : '' }}" href="{{ route('alertas', ['filtro' => 'alerta']) }}"><span class="alert-tab-dot minimum"></span>Alerta ({{ $totales['alerta'] }})</a>
    <a class="alert-tab {{ $filter === 'sin-movimiento' ? 'active' : '' }}" href="{{ route('alertas', ['filtro' => 'sin-movimiento']) }}">◷ Sin movimiento ({{ $totales['sin_movimiento'] }})</a>
    <a class="alert-tab {{ $filter === 'atendidas' ? 'active' : '' }}" href="{{ route('alertas', ['filtro' => 'atendidas']) }}">✓ Atendidas ({{ $totales['atendidas'] }})</a>
  </nav>
  @if ($filter !== 'atendidas' && $totales['todas'] > 0)
    <form method="POST" action="{{ route('alertas.atender-todas') }}">@csrf<button class="btn" type="submit">Marcar todas atendidas</button></form>
  @endif
</div>

@php
  $tiposVisibles = $definiciones;
  if ($filter !== 'atendidas' && $filtroTipo) {
    $tiposVisibles = [$filtroTipo => $definiciones[$filtroTipo]];
  }
@endphp
@foreach ($tiposVisibles as $tipo => $definicion)
  @php
    $items = $grupos->get($tipo, collect());
  @endphp
  @if ($items->isNotEmpty())
    <section class="alert-section">
      <h2 class="alert-section-title alert-section-title--{{ $filter === 'atendidas' ? 'done' : $definicion['color'] }}"><span class="alert-tab-dot {{ $filter === 'atendidas' ? 'done' : $definicion['color'] }}"></span>{{ $filter === 'atendidas' ? 'Atendidas — '.$definicion['nombre'] : $definicion['titulo'] }}</h2>
      @foreach ($items as $alerta)
        @php
          $stock = (float) $alerta->stock_actual;
          $minimo = (float) $alerta->stock_minimo;
          $dias = $alerta->ultimo_movimiento ? (int) \Illuminate\Support\Carbon::parse($alerta->ultimo_movimiento)->diffInDays(now(), true) : null;
          $nombreCompleto = trim($alerta->nombre_producto.' '.($alerta->presentacion ?? ''));
          $color = $filter === 'atendidas' ? 'done' : $definicion['color'];
        @endphp
        <article class="alert-item alert-item--{{ $color }}">
          <span class="alert-dot alert-dot--{{ $color }}" aria-hidden="true"></span>
          <div class="alert-item-info">
            <div class="alert-product-name">{{ $nombreCompleto }}</div>
            @if ($tipo === 'sin_movimiento')
              <div class="alert-product-meta">Último movimiento: {{ $alerta->ultimo_movimiento ? \Illuminate\Support\Carbon::parse($alerta->ultimo_movimiento)->format('d/m/Y') : 'Sin registro' }} · Stock: {{ number_format($stock, 2) }} {{ $alerta->unidad }} · ${{ number_format((float) $alerta->precio_venta, 2) }}/{{ $alerta->unidad }}</div>
              <div class="alert-product-location">Sucursal {{ auth()->user()->id_tienda }} · {{ $alerta->anaquel ?? 'Sin anaquel' }} · {{ $dias ?? '—' }} días sin movimiento</div>
              <div class="alert-product-hint">Considera reducir precio, cambiar ubicación o notificar a clientes frecuentes.</div>
            @else
              <div class="alert-product-meta">Stock: {{ number_format($stock, 2) }} {{ $alerta->unidad }} · Mínimo: {{ number_format($minimo, 2) }} {{ $alerta->unidad }} · Déficit: {{ number_format(max(0, $minimo - $stock), 2) }} {{ $alerta->unidad }}</div>
              <div class="alert-product-location">Sucursal {{ auth()->user()->id_tienda }} · {{ $alerta->anaquel ?? 'Sin anaquel' }} · Hace {{ $dias ?? '—' }} días</div>
            @endif
            @if ($filter === 'atendidas')<div class="alert-product-location">Atendida {{ $alerta->fecha_atencion ?? '' }}{{ $alerta->atendida_por ? ' por '.$alerta->atendida_por : '' }}</div>@endif
          </div>
          <div class="alert-item-actions">
            <a class="btn btn-sm" href="{{ route('inventario') }}#producto-{{ $alerta->id_producto }}">Ver en inventario</a>
            @if ($tipo !== 'sin_movimiento' && $filter !== 'atendidas' && auth()->user()->rol !== 'empleado')
              <a class="btn btn-sm btn-primary" href="{{ route('compras', ['producto' => $alerta->id_producto]) }}">🛒 Comprar ahora</a>
            @endif
          </div>
        </article>
      @endforeach
    </section>
  @endif
@endforeach

@if ($alertas->isEmpty())
  <div class="empty-state"><div class="icon">✓</div><h3>{{ $filter === 'atendidas' ? 'No hay alertas atendidas.' : 'No hay alertas en esta categoría.' }}</h3></div>
@endif
@endsection
