@extends('layouts.guest')

@section('titulo', 'Crear cuenta')

@section('contenido')
<form method="POST" action="{{ route('register.store') }}">
  @csrf
  <div class="form-group mb-12">
    <label class="form-label" for="nombre">Nombre completo</label>
    <input class="form-control" id="nombre" name="nombre" value="{{ old('nombre') }}" maxlength="120" autocomplete="name" required>
  </div>
  <div class="form-group mb-12">
    <label class="form-label" for="correo">Correo electrónico</label>
    <input class="form-control" id="correo" name="correo" type="email" value="{{ old('correo') }}" maxlength="100" autocomplete="email" required>
  </div>
  <div class="form-group mb-12">
    <label class="form-label" for="contrasena">Contraseña (mínimo 6 caracteres)</label>
    <input class="form-control" id="contrasena" name="contrasena" type="password" minlength="6" autocomplete="new-password" required>
  </div>
  <div class="form-group mb-16">
    <label class="form-label" for="contrasena_confirmation">Confirmar contraseña</label>
    <input class="form-control" id="contrasena_confirmation" name="contrasena_confirmation" type="password" minlength="6" autocomplete="new-password" required>
  </div>
  <button class="btn btn-primary w-full" type="submit" style="justify-content:center">Crear cuenta</button>
</form>
<div class="auth-links"><a href="{{ route('login') }}">Volver a iniciar sesión</a></div>
@endsection
