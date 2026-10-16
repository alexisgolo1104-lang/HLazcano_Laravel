@extends('layouts.app')

@section('titulo', 'Catálogos')

@section('contenido')
<div class="grid-2">
  <section class="card"><div class="card-title">Productos del catálogo</div><div class="table-wrap"><table><thead><tr><th>Nombre</th><th>Presentación</th></tr></thead><tbody>
    @forelse ($catalogos as $item)<tr><td>{{ $item->nombre }}</td><td>{{ $item->presentacion ?? '—' }}</td></tr>@empty<tr><td colspan="2" class="text-muted">Sin registros</td></tr>@endforelse
  </tbody></table></div></section>
  <section class="card"><div class="card-title">Tipos de hilo</div><div class="table-wrap"><table><thead><tr><th>Nombre</th><th>Descripción</th></tr></thead><tbody>
    @forelse ($tipos as $item)<tr><td>{{ $item->nombre }}</td><td>{{ $item->descripcion ?? '—' }}</td></tr>@empty<tr><td colspan="2" class="text-muted">Sin registros</td></tr>@endforelse
  </tbody></table></div></section>
  <section class="card"><div class="card-title">Colores</div><div class="table-wrap"><table><thead><tr><th>Color</th><th>Código</th></tr></thead><tbody>
    @forelse ($colores as $item)<tr><td>{{ $item->nombre }}</td><td>{{ $item->codigo_hex ?? '—' }}</td></tr>@empty<tr><td colspan="2" class="text-muted">Sin registros</td></tr>@endforelse
  </tbody></table></div></section>
  <section class="card"><div class="card-title">Unidades</div><div class="table-wrap"><table><thead><tr><th>Nombre</th><th>Código</th></tr></thead><tbody>
    @forelse ($unidades as $unidad)<tr><td>{{ $unidad->nombre }}</td><td>{{ $unidad->codigo }}</td></tr>@empty<tr><td colspan="2" class="text-muted">Sin registros</td></tr>@endforelse
  </tbody></table></div></section>
  <section class="card"><div class="card-title">Proveedores</div><div class="table-wrap"><table><thead><tr><th>Nombre</th><th>Correo</th><th>Teléfono</th></tr></thead><tbody>
    @forelse ($proveedores as $proveedor)<tr><td>{{ $proveedor->nombre }}</td><td>{{ $proveedor->correo ?? '—' }}</td><td>{{ $proveedor->telefono ?? '—' }}</td></tr>@empty<tr><td colspan="3" class="text-muted">Sin registros</td></tr>@endforelse
  </tbody></table></div></section>
</div>
@endsection
