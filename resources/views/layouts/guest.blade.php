<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="csrf-token" content="{{ csrf_token() }}">
  <title>@yield('titulo') · HLazcano</title>
  <link rel="stylesheet" href="{{ asset('hlazcano.css') }}">
</head>
<body>
  <main class="login-wrap" style="background-image: linear-gradient(180deg,rgba(8,12,25,.35),rgba(8,12,25,.75)), url('{{ asset('img/hilos.jpeg') }}')">
    <section class="login-card" aria-labelledby="auth-title">
      <div class="login-logo">
        <div class="login-icon">HL</div>
        <h1 class="login-title" id="auth-title">HLazcano</h1>
        <div class="login-sub">Sistema de gestión de inventario</div>
      </div>
      @if (session('status'))
        <div class="notice notice-info mb-16" role="status">{{ session('status') }}</div>
      @endif
      @if ($errors->any())
        <div class="auth-errors" role="alert">
          <ul>@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
        </div>
      @endif
      @yield('contenido')
    </section>
  </main>
</body>
</html>
