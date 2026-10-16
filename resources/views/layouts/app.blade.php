<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="csrf-token" content="{{ csrf_token() }}">
  <title>@yield('titulo', 'Panel') · HLazcano</title>
  <link rel="stylesheet" href="{{ asset('hlazcano.css') }}">
  @stack('estilos')
</head>
<body>
<div class="app">
  <nav class="sidebar">
    <div class="sb-logo">
      <div class="sb-logo-mark">HL</div>
      <div>
        <div class="sb-logo-text">HLazcano</div>
        <div class="sb-logo-sub">Sistema de gestión</div>
      </div>
    </div>
    <nav>
      <div class="sb-section">Principal</div>
      <a class="sb-item {{ request()->routeIs('dashboard') ? 'active' : '' }}" href="{{ route('dashboard') }}"><span class="icon">◉</span>Dashboard</a>
      <div class="sb-section">Operación</div>
      <a class="sb-item {{ request()->routeIs('inventario*') ? 'active' : '' }}" href="{{ route('inventario') }}"><span class="icon">📦</span>Inventario</a>
      <a class="sb-item {{ request()->routeIs('ventas*') ? 'active' : '' }}" href="{{ route('ventas') }}"><span class="icon">🛒</span>Ventas</a>
      @if (auth()->user()->rol !== 'empleado')
      <a class="sb-item {{ request()->routeIs('compras*') ? 'active' : '' }}" href="{{ route('compras') }}"><span class="icon">📥</span>Compras</a>
      @endif
      <a class="sb-item {{ request()->routeIs('clientes*') ? 'active' : '' }}" href="{{ route('clientes') }}"><span class="icon">👥</span>Clientes</a>
      <div class="sb-section">Análisis</div>
      <a class="sb-item {{ request()->routeIs('alertas') ? 'active' : '' }}" href="{{ route('alertas') }}"><span class="icon">🔔</span>Alertas</a>
      <a class="sb-item {{ request()->routeIs('localizador') ? 'active' : '' }}" href="{{ route('localizador') }}"><span class="icon">🗺️</span>Localizador</a>
      <a class="sb-item {{ request()->routeIs('reportes') ? 'active' : '' }}" href="{{ route('reportes') }}"><span class="icon">📊</span>Reportes</a>
      @if (auth()->user()->rol !== 'empleado')
      <div class="sb-section">Configuración</div>
      <a class="sb-item {{ request()->routeIs('usuarios') ? 'active' : '' }}" href="{{ route('usuarios') }}"><span class="icon">👤</span>Usuarios</a>
      <a class="sb-item {{ request()->routeIs('catalogos') ? 'active' : '' }}" href="{{ route('catalogos') }}"><span class="icon">📋</span>Catálogos</a>
      @endif
      @if (in_array(auth()->user()->rol, ['admin', 'superadmin'], true))
      <a class="sb-item {{ request()->routeIs('respaldos*') ? 'active' : '' }}" href="{{ route('respaldos') }}"><span class="icon">💾</span>Respaldos</a>
      @endif
    </nav>
    <div class="sb-footer">
      <div class="sb-user">
        <div class="sb-avatar">{{ mb_strtoupper(mb_substr(auth()->user()->nombre, 0, 1)) }}</div>
        <div class="sb-info">
          <div class="sb-name">{{ auth()->user()->nombre }}</div>
          <div class="sb-role">{{ ucfirst(auth()->user()->rol) }}</div>
        </div>
        <form method="POST" action="{{ route('logout') }}">
          @csrf
          <button class="sb-logout" type="submit" title="Cerrar sesión">↩</button>
        </form>
      </div>
    </div>
  </nav>

  <div class="main-area">
    <header class="topbar">
      <div class="page-title">@yield('titulo')</div>
      <div class="topbar-store">📍 {{ auth()->user()->tienda->nombre ?? 'Sucursal asignada' }}</div>
      <div class="topbar-user"><span class="tu-name">{{ auth()->user()->nombre }}</span></div>
    </header>
    <main class="content">
      @if (session('status'))
        <div class="notice notice-info mb-16" role="status">{{ session('status') }}</div>
      @endif
      @if ($errors->any())
        <div class="notice notice-danger mb-16" role="alert">
          <ul>@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
        </div>
      @endif
      @yield('contenido')
    </main>
  </div>
</div>
@stack('scripts')
</body>
</html>
