@extends('layouts.guest')

@section('titulo', 'Recuperar contraseña')

@section('contenido')
<form method="POST" action="{{ route('password.email') }}">
  @csrf
  <div class="form-group mb-16">
    <label class="form-label" for="correo">Correo electrónico</label>
    <input class="form-control" id="correo" name="correo" type="email" value="{{ old('correo') }}" autocomplete="email" maxlength="100" required autofocus>
  </div>
  <button class="btn btn-primary w-full" type="submit" style="justify-content:center">Enviar contraseña temporal</button>
</form>
<div class="auth-links"><a href="{{ route('login') }}">Volver a iniciar sesión</a></div>
@endsection