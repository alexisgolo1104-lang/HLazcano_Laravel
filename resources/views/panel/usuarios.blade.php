@extends('layouts.app')

@section('titulo', 'Usuarios')

@section('contenido')
@if ($equipoCrear)
  <section class="card mb-16">
    <div class="card-title">Registrar integrante sin cuenta del sistema</div>
    <form method="POST" action="{{ route('equipo.store') }}">
      @csrf
      <div class="form-row form-2">
        <div class="form-group"><label class="form-label" for="nuevo-equipo-nombre">Nombre *</label><input class="form-control" id="nuevo-equipo-nombre" name="nombre" value="{{ old('nombre') }}" maxlength="120" required></div>
        <div class="form-group"><label class="form-label" for="nuevo-equipo-puesto">Puesto *</label><input class="form-control" id="nuevo-equipo-puesto" name="puesto" value="{{ old('puesto') }}" maxlength="100" placeholder="Montacarguista, cargador, repartidor..." required></div>
        <div class="form-group"><label class="form-label" for="nuevo-equipo-telefono">Teléfono</label><input class="form-control" id="nuevo-equipo-telefono" name="telefono" type="tel" value="{{ old('telefono') }}" maxlength="20"></div>
        <div class="form-group"><label class="form-label" for="nuevo-equipo-sueldo">Sueldo quincenal</label><input class="form-control" id="nuevo-equipo-sueldo" name="sueldo" type="number" min="0" max="9999999999.99" step="0.01" value="{{ old('sueldo') }}"></div>
      </div>
      <button class="btn btn-primary" type="submit">Registrar integrante</button>
      <a class="btn btn-sm" href="{{ route('usuarios') }}">Cancelar</a>
    </form>
  </section>
@endif
@if ($miembroEditar)
  <section class="card mb-16">
    <div class="card-title">Editar integrante: {{ $miembroEditar->nombre }}</div>
    <form method="POST" action="{{ route('equipo.update', $miembroEditar->id_empleado) }}">
      @csrf
      @method('PUT')
      <div class="form-row form-2">
        <div class="form-group"><label class="form-label" for="equipo-nombre">Nombre *</label><input class="form-control" id="equipo-nombre" name="nombre" value="{{ old('nombre', $miembroEditar->nombre) }}" maxlength="120" required></div>
        <div class="form-group"><label class="form-label" for="equipo-puesto">Puesto *</label><input class="form-control" id="equipo-puesto" name="puesto" value="{{ old('puesto', $miembroEditar->puesto) }}" maxlength="100" placeholder="Montacarguista, cargador, repartidor..." required></div>
        <div class="form-group"><label class="form-label" for="equipo-telefono">Teléfono</label><input class="form-control" id="equipo-telefono" name="telefono" type="tel" value="{{ old('telefono', $miembroEditar->telefono) }}" maxlength="20"></div>
        <div class="form-group"><label class="form-label" for="equipo-sueldo">Sueldo mensual</label><input class="form-control" id="equipo-sueldo" name="sueldo" type="number" min="0" max="9999999999.99" step="0.01" value="{{ old('sueldo', $miembroEditar->sueldo) }}"></div>
      </div>
      <button class="btn btn-primary" type="submit">Guardar cambios</button>
      <a class="btn btn-sm" href="{{ route('usuarios') }}">Cancelar</a>
    </form>
  </section>
@endif
@if ($usuarioEditar)
  <section class="card mb-16">
    <div class="card-title">Editar cuenta: {{ $usuarioEditar->nombre }}</div>
    <form method="POST" action="{{ route('usuarios.update', $usuarioEditar->id_usuario) }}">
      @csrf
      @method('PUT')
      <div class="form-row form-2">
        <div class="form-group">
          <label class="form-label" for="nombre">Nombre *</label>
          <input class="form-control" id="nombre" name="nombre" value="{{ old('nombre', $usuarioEditar->nombre) }}" maxlength="120" required>
        </div>
        <div class="form-group">
          <label class="form-label" for="correo">Correo *</label>
          <input class="form-control" id="correo" name="correo" type="email" value="{{ old('correo', $usuarioEditar->correo) }}" maxlength="100" required>
        </div>
        <div class="form-group">
          <label class="form-label" for="telefono">Número de teléfono</label>
          <input class="form-control" id="telefono" name="telefono" type="tel" value="{{ old('telefono', $usuarioEditar->telefono) }}" maxlength="20" autocomplete="tel">
        </div>
        <div class="form-group">
          <label class="form-label" for="sueldo">Sueldo quincenal</label>
          <input class="form-control" id="sueldo" name="sueldo" type="number" min="0" max="9999999999.99" step="0.01" value="{{ old('sueldo', $usuarioEditar->sueldo) }}">
        </div>
        <div class="form-group">
          <label class="form-label" for="contrasena">Cambiar contraseña</label>
          <input class="form-control" id="contrasena" name="contrasena" type="password" minlength="6" autocomplete="new-password">
          <small class="text-muted">Déjala vacía para conservar la contraseña actual.</small>
        </div>
        <div class="form-group">
          <label class="form-label" for="contrasena_confirmation">Confirmar nueva contraseña</label>
          <input class="form-control" id="contrasena_confirmation" name="contrasena_confirmation" type="password" minlength="6" autocomplete="new-password">
        </div>
      </div>
      <div>
        <button class="btn btn-primary" type="submit">Guardar cambios</button>
        <a class="btn btn-sm" href="{{ route('usuarios') }}">Cancelar</a>
      </div>
    </form>
  </section>
@endif
<section class="card">
  <div class="card-title">Cuentas del sistema</div>
  <div class="table-wrap"><table><thead><tr><th>Nombre</th><th>Correo</th><th>Teléfono</th><th>Rol</th><th>Sueldo quincenal</th><th>Estado</th><th>Último acceso</th><th>Acciones</th></tr></thead><tbody>
    @forelse ($usuarios as $usuario)
      <tr>
        <td>{{ $usuario->nombre }}</td>
        <td>{{ $usuario->correo }}</td>
        <td>{{ $usuario->telefono ?? '—' }}</td>
        <td>{{ $usuario->rol === 'admin' ? 'Administrador' : ucfirst($usuario->rol) }}</td>
        <td>{{ $usuario->sueldo !== null ? '$'.number_format((float) $usuario->sueldo, 2) : 'Sin asignar' }}</td>
        <td>{{ $usuario->activo ? 'Activo' : ($usuario->rol === 'admin' ? 'Pendiente de aprobación' : 'Desactivado') }}</td>
        <td>{{ $usuario->ultimo_acceso ?? '—' }}</td>
        <td>
          <div class="tbl-actions">
            @php($esSuperadminActual = auth()->user()->rol === 'superadmin' && $usuario->id_usuario === auth()->id())
            @php($puedeGestionarUsuario = $usuario->rol === 'empleado' || (auth()->user()->rol === 'superadmin' && ($usuario->rol === 'admin' || $esSuperadminActual)))
            @php($puedeAprobarAdministrador = auth()->user()->rol === 'superadmin' && $usuario->rol === 'admin' && ! $usuario->activo)
            @if ($puedeAprobarAdministrador)
              <form method="POST" action="{{ route('usuarios.aprobar-administrador', $usuario->id_usuario) }}">
                @csrf
                <button class="btn btn-primary" type="submit">Aprobar administrador</button>
              </form>
            @endif
            @if ($puedeGestionarUsuario)
              <a class="btn btn-sm" href="{{ route('usuarios.edit', $usuario->id_usuario) }}">Editar</a>
              @if ($usuario->activo && $usuario->rol !== 'superadmin')
                <form method="POST" action="{{ route('usuarios.destroy', $usuario->id_usuario) }}" data-confirm="¿Desactivar esta cuenta de usuario?">
                  @csrf
                  @method('DELETE')
                  <button class="btn btn-sm btn-danger" type="submit">Desactivar</button>
                </form>
              @endif
            @endif
            @if (! $puedeGestionarUsuario && ! $puedeAprobarAdministrador)
              —
            @endif
          </div>
        </td>
      </tr>
    @empty
      <tr><td colspan="8" class="text-muted">No hay usuarios registrados.</td></tr>
    @endforelse
  </tbody></table></div>
</section>

<section class="card mt-16">
  <div class="card-title">
    <span>Equipo sin cuenta del sistema</span>
    <a class="btn btn-primary" href="{{ route('equipo.create') }}">+ Registrar integrante</a>
  </div>
  <div class="table-wrap"><table><thead><tr><th>Nombre</th><th>Puesto</th><th>Teléfono</th><th>Sueldo quincenal</th><th>Estado</th><th>Acciones</th></tr></thead><tbody>
    @forelse ($equipo as $miembro)
      <tr>
        <td>{{ $miembro->nombre }}</td>
        <td>{{ $miembro->puesto }}</td>
        <td>{{ $miembro->telefono ?? '—' }}</td>
        <td>{{ $miembro->sueldo !== null ? '$'.number_format((float) $miembro->sueldo, 2) : 'Sin asignar' }}</td>
        <td>{{ $miembro->activo ? 'Activo' : 'Desactivado' }}</td>
        <td><div class="tbl-actions">
          <a class="btn btn-sm" href="{{ route('equipo.edit', $miembro->id_empleado) }}">Editar</a>
          @if ($miembro->activo)
            <form method="POST" action="{{ route('equipo.destroy', $miembro->id_empleado) }}" data-confirm="¿Desactivar a este integrante?">
              @csrf @method('DELETE')
              <button class="btn btn-sm btn-danger" type="submit">Desactivar</button>
            </form>
          @endif
        </div></td>
      </tr>
    @empty
      <tr><td colspan="6" class="text-muted">No hay integrantes registrados.</td></tr>
    @endforelse
  </tbody></table></div>
</section>
@endsection
