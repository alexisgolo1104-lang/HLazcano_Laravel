@extends('layouts.app')

@section('titulo', 'Cambiar contraseña')

@section('contenido')
@if (auth()->user()->debe_cambiar_contrasena)
  <div class="notice notice-info mb-16" role="status">Por seguridad, debes reemplazar la contraseña temporal antes de continuar.</div>
@endif
<section class="card">
  <div class="card-title">{{ auth()->user()->debe_cambiar_contrasena ? 'Define tu nueva contraseña' : 'Actualizar contraseña' }}</div>
  <form method="POST" action="{{ route('password.change.update') }}">
    @csrf
    @method('PUT')
    <div class="form-row form-2">
      <div class="form-group">
        <label class="form-label" for="contrasena_actual">Contraseña actual o temporal</label>
        <input class="form-control" id="contrasena_actual" name="contrasena_actual" type="password" autocomplete="current-password" required autofocus>
      </div>
      <div class="form-group">
        <label class="form-label" for="contrasena">Nueva contraseña</label>
        <input class="form-control" id="contrasena" name="contrasena" type="password" minlength="8" autocomplete="new-password" required>
      </div>
      <div class="form-group">
        <label class="form-label" for="contrasena_confirmation">Confirmar nueva contraseña</label>
        <input class="form-control" id="contrasena_confirmation" name="contrasena_confirmation" type="password" minlength="8" autocomplete="new-password" required>
      </div>
    </div>
    <button class="btn btn-primary" type="submit">Guardar contraseña</button>
  </form>
</section>
@endsection