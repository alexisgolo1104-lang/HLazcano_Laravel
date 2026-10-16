@extends('layouts.app')

@section('titulo', 'Respaldos')

@section('contenido')
<div class="notice notice-info mb-16">
  <strong>Respaldo de base de datos</strong> · El ZIP contiene las tablas y datos de la base configurada.
  Solo se aceptan archivos generados por esta aplicación y firmados con la misma clave APP_KEY.
</div>
<div class="grid-2">
  <section class="card">
    <div class="card-title">Exportar respaldo</div>
    <p class="text-muted mb-12">Último respaldo exitoso: {{ $ultimo ? \Illuminate\Support\Carbon::parse($ultimo)->format('d/m/Y H:i') : 'Nunca' }}</p>
    <form method="POST" action="{{ route('respaldos.exportar') }}">
      @csrf
      <button class="btn btn-primary" type="submit">Exportar base de datos (ZIP)</button>
    </form>
  </section>
  @if (auth()->user()->rol === 'superadmin')
  <section class="card">
    <div class="card-title">Restaurar respaldo</div>
    <div class="notice notice-danger mb-12" role="alert">
      La restauración reemplaza todas las tablas y sus datos actuales. Esta acción no se puede deshacer.
    </div>
    <form method="POST" action="{{ route('respaldos.importar') }}" enctype="multipart/form-data" onsubmit="return confirmRestore()">
      @csrf
      <div class="form-group">
        <label class="form-label" for="archivo">Archivo ZIP generado por esta aplicación</label>
        <input class="form-control" id="archivo" name="archivo" type="file" accept=".zip,application/zip" required>
      </div>
      <div class="form-group">
        <label class="form-label" for="confirmacion">Escribe RESTAURAR BASE DE DATOS para confirmar</label>
        <input class="form-control" id="confirmacion" name="confirmacion" autocomplete="off" required>
      </div>
      <button class="btn btn-danger" type="submit">Restaurar y cerrar sesión</button>
    </form>
  </section>
  @endif
</div>
<section class="card">
  <div class="card-title">Historial de respaldos</div>
  <div class="table-wrap">
    <table>
      <thead><tr><th>Fecha</th><th>Usuario</th><th>Archivo</th><th>Tamaño</th><th>Operación</th><th>Estado</th><th>Notas</th></tr></thead>
      <tbody>
        @forelse ($historial as $respaldo)
          <tr>
            <td>{{ \Illuminate\Support\Carbon::parse($respaldo->fecha_generacion)->format('d/m/Y H:i') }}</td>
            <td>{{ $respaldo->nombre_usuario }}</td>
            <td>{{ $respaldo->nombre_archivo }}</td>
            <td>{{ number_format((float) $respaldo->tamano_bytes / 1048576, 2) }} MB</td>
            <td>{{ ucfirst($respaldo->tipo_operacion) }}</td>
            <td><span class="badge {{ $respaldo->exito ? 'badge-ok' : 'badge-danger' }}">{{ $respaldo->exito ? 'Exitoso' : 'Fallido' }}</span></td>
            <td>{{ $respaldo->notas ?? '—' }}</td>
          </tr>
        @empty
          <tr><td colspan="7" class="text-muted">No hay respaldos registrados.</td></tr>
        @endforelse
      </tbody>
    </table>
  </div>
</section>
@endsection

@push('scripts')
<script>
  function confirmRestore() {
    return window.confirm('La restauración reemplazará la base de datos actual y cerrará tu sesión. ¿Continuar?');
  }
</script>
@endpush
