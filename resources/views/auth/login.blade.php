@extends('layouts.guest')

@section('titulo', 'Iniciar sesión')

@section('contenido')
<form method="POST" action="{{ route('login.store') }}">
  @csrf
  <div class="form-group mb-12">
    <label class="form-label" for="usuario">Usuario o correo electrónico</label>
    <input class="form-control" id="usuario" name="usuario" value="{{ old('usuario') }}" autocomplete="username" required autofocus>
  </div>
  <div class="form-group mb-16">
    <label class="form-label" for="contrasena">Contraseña</label>
    <input class="form-control" id="contrasena" name="contrasena" type="password" autocomplete="current-password" required>
  </div>
  <button class="btn btn-primary w-full" type="submit" style="justify-content:center">Ingresar al sistema</button>
</form>
<div class="auth-links"><a href="{{ route('register') }}">Crear una cuenta</a></div>
@endsection
