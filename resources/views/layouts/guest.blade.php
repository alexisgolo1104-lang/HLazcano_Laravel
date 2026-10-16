<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="csrf-token" content="{{ csrf_token() }}">
  <title>@yield('titulo') · HLazcano</title>
  <link rel="stylesheet" href="{{ asset('hlazcano.css') }}">
  <style>
    body{min-height:100vh;display:grid;place-items:center;padding:24px;background:#f4f6fa}
    .auth-card{width:min(100%,440px);padding:30px;background:#fff;border:1px solid #d8dce8;border-radius:14px;box-shadow:0 4px 24px rgba(0,0,0,.12)}
    .auth-brand{text-align:center;margin-bottom:24px}
    .auth-brand-mark{margin:0 auto 10px;width:44px;height:44px;border-radius:11px;background:#c0392b;color:#fff;display:grid;place-items:center;font-weight:800}
    .auth-brand h1{font-size:22px}
    .auth-links{text-align:center;margin-top:16px}
    .auth-errors{margin:0 0 16px;padding:10px 14px;border-radius:8px;background:#fdecea;color:#9f2016}
  </style>
</head>
<body>
  <main class="auth-card">
    <div class="auth-brand">
      <div class="auth-brand-mark">HL</div>
      <h1>HLazcano</h1>
      <div class="text-muted">Sistema de gestión de inventario</div>
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
  </main>
</body>
</html>
