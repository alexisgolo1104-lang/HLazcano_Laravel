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
    <label class="form-label" for="telefono">Número de teléfono</label>
    <input class="form-control" id="telefono" name="telefono" type="tel" value="{{ old('telefono') }}" maxlength="20" autocomplete="tel">
  </div>
  <div class="form-group mb-12">
    <label class="form-label" for="rol">Tipo de cuenta</label>
    <select class="form-control" id="rol" name="rol" required>
      <option value="empleado" @selected(old('rol', 'empleado') === 'empleado')>Empleado</option>
      <option value="admin" @selected(old('rol') === 'admin')>Administrador (requiere aprobación)</option>
    </select>
    <small class="text-muted">Las cuentas de administrador solo se activan después de la aprobación de un superadministrador.</small>
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
