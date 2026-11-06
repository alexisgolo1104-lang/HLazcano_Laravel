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
    <nav class="sb-nav-scroll" aria-label="Menú principal">
      <div class="sb-section">Principal</div>
      <a class="sb-item {{ request()->routeIs('dashboard') ? 'active' : '' }}" href="{{ route('dashboard') }}"><span class="icon">◉</span>Dashboard</a>
      <div class="sb-section">Operación</div>
      <a class="sb-item {{ request()->routeIs('inventario*') ? 'active' : '' }}" href="{{ route('inventario') }}"><span class="icon">📦</span>Inventario</a>
      <a class="sb-item {{ request()->routeIs('ventas*') ? 'active' : '' }}" href="{{ route('ventas') }}"><span class="icon">🛒</span>Ventas</a>
      @if (auth()->user()->rol !== 'empleado')
      <a class="sb-item {{ request()->routeIs('compras*') ? 'active' : '' }}" href="{{ route('compras') }}"><span class="icon">📥</span>Compras</a>
      @endif
      <a class="sb-item {{ request()->routeIs('clientes*') ? 'active' : '' }}" href="{{ route('clientes') }}"><span class="icon">👥</span>Clientes</a>
      @if (in_array(auth()->user()->rol, ['admin', 'superadmin'], true))
      <a class="sb-item {{ request()->routeIs('proveedores*') ? 'active' : '' }}" href="{{ route('proveedores') }}"><span class="icon">🏭</span>Proveedores</a>
      @endif
      <div class="sb-section">Análisis</div>
      <a class="sb-item {{ request()->routeIs('alertas') ? 'active' : '' }}" href="{{ route('alertas') }}"><span class="icon">🔔</span>Alertas</a>
      <a class="sb-item {{ request()->routeIs('localizador') ? 'active' : '' }}" href="{{ route('localizador') }}"><span class="icon">🗺️</span>Localizador</a>
      <a class="sb-item {{ request()->routeIs('reportes') ? 'active' : '' }}" href="{{ route('reportes') }}"><span class="icon">📊</span>Reportes</a>
      @if (auth()->user()->rol !== 'empleado')
      <div class="sb-section">Configuración</div>
      <a class="sb-item {{ request()->routeIs('usuarios') ? 'active' : '' }}" href="{{ route('usuarios') }}"><span class="icon">👤</span>Usuarios</a>
      <a class="sb-item {{ request()->routeIs('catalogos') ? 'active' : '' }}" href="{{ route('catalogos') }}"><span class="icon">📋</span>Catálogos</a>
      @endif
      @if (auth()->user()->rol === 'superadmin')
      <div class="sb-section">Finanzas</div>
      <a class="sb-item {{ request()->routeIs('egresos*') ? 'active' : '' }}" href="{{ route('egresos') }}"><span class="icon">💸</span>Egresos</a>
      @endif
      @if (in_array(auth()->user()->rol, ['admin', 'superadmin'], true))
      <a class="sb-item {{ request()->routeIs('respaldos*') ? 'active' : '' }}" href="{{ route('respaldos') }}"><span class="icon">💾</span>Respaldos</a>
      @endif
      <a class="sb-item {{ request()->routeIs('password.change*') ? 'active' : '' }}" href="{{ route('password.change') }}"><span class="icon">🔑</span>Cambiar contraseña</a>
    </nav>
    <div class="sb-footer">
      <div class="sb-user">
        <div class="sb-avatar">{{ mb_strtoupper(mb_substr(auth()->user()->nombre, 0, 1)) }}</div>
        <div class="sb-info">
          <div class="sb-name">{{ auth()->user()->nombre }}</div>
          <div class="sb-role">{{ ucfirst(auth()->user()->rol) }}</div>
        </div>
        <form method="POST" action="{{ route('logout') }}" data-confirm-skip>
          @csrf
          <button class="sb-logout" type="submit" title="Cerrar sesión">↩</button>
        </form>
      </div>
    </div>
  </nav>

  <div class="main-area">
    <header class="topbar">
      <div class="page-title">@yield('titulo')</div>
      <a class="topbar-notif" href="{{ route('alertas') }}" title="Ver alertas" aria-label="Ver alertas: {{ $alertasNuevas ?? 0 }} pendientes">
        <span aria-hidden="true">🔔</span>
        @if (($alertasNuevas ?? 0) > 0)<span class="notif-count">{{ $alertasNuevas > 99 ? '99+' : $alertasNuevas }}</span>@endif
      </a>
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
<div class="app-toast-stack" id="app-toast-stack" aria-live="polite" aria-atomic="false"></div>
<div class="app-confirm-overlay" id="app-confirm-overlay" hidden>
  <section class="app-confirm-dialog" role="alertdialog" aria-modal="true" aria-labelledby="app-confirm-title" aria-describedby="app-confirm-message">
    <h2 id="app-confirm-title">Confirmar acción</h2>
    <p id="app-confirm-message"></p>
    <div class="app-confirm-actions">
      <button class="btn" id="app-confirm-cancel" type="button">Cancelar</button>
      <button class="btn btn-danger" id="app-confirm-accept" type="button">Confirmar</button>
    </div>
  </section>
</div>
<script>
  (() => {
    const toastStack = document.getElementById('app-toast-stack');
    const confirmOverlay = document.getElementById('app-confirm-overlay');
    const confirmMessage = document.getElementById('app-confirm-message');
    const confirmCancel = document.getElementById('app-confirm-cancel');
    const confirmAccept = document.getElementById('app-confirm-accept');
    let pendingForm = null;
    let previouslyFocused = null;

    window.showToast = (message, type = 'success') => {
      const toast = document.createElement('div');
      toast.className = `app-toast app-toast-${type}`;
      toast.setAttribute('role', type === 'error' ? 'alert' : 'status');

      const text = document.createElement('span');
      text.textContent = message;
      toast.appendChild(text);

      const dismiss = document.createElement('button');
      dismiss.type = 'button';
      dismiss.className = 'app-toast-dismiss';
      dismiss.setAttribute('aria-label', 'Cerrar notificación');
      dismiss.textContent = '×';
      dismiss.addEventListener('click', () => toast.remove());
      toast.appendChild(dismiss);

      toastStack.appendChild(toast);
      window.setTimeout(() => toast.remove(), 5000);
    };

    function closeConfirmation(confirmed) {
      confirmOverlay.hidden = true;
      document.body.classList.remove('app-confirm-open');

      const form = pendingForm;
      pendingForm = null;
      if (confirmed && form) {
        form.dataset.confirmed = 'true';
        form.requestSubmit();
      } else {
        previouslyFocused?.focus();
      }
    }

    document.addEventListener('submit', event => {
      const form = event.target;
      if (!(form instanceof HTMLFormElement) || form.method.toLowerCase() === 'get' || form.hasAttribute('data-confirm-skip')) return;
      if (form.dataset.confirmed === 'true') {
        delete form.dataset.confirmed;
        return;
      }

      event.preventDefault();
      pendingForm = form;
      previouslyFocused = document.activeElement;
      confirmMessage.textContent = form.dataset.confirm || '¿Estás seguro de que deseas realizar estos cambios?';
      confirmOverlay.hidden = false;
      document.body.classList.add('app-confirm-open');
      confirmCancel.focus();
    }, true);

    confirmCancel.addEventListener('click', () => closeConfirmation(false));
    confirmAccept.addEventListener('click', () => closeConfirmation(true));
    confirmOverlay.addEventListener('click', event => {
      if (event.target === confirmOverlay) closeConfirmation(false);
    });
    document.addEventListener('keydown', event => {
      if (!confirmOverlay.hidden && event.key === 'Escape') closeConfirmation(false);
    });
  })();
</script>
@stack('scripts')
</body>
</html>
