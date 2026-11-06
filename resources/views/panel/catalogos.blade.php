@extends('layouts.app')

@section('titulo', 'Catálogos — '.$configuracion['titulo'])

@section('contenido')
<div class="notice notice-info mb-16">ℹ️ Los catálogos contienen datos predeterminados del sistema. Usarlos en formularios evita errores de escritura.</div>

<nav class="catalog-tabs" aria-label="Secciones de catálogos">
  @foreach (['tipos' => ['📋', 'Tipos de hilo'], 'colores' => ['🎨', 'Colores'], 'anaqueles' => ['🗄️', 'Anaqueles'], 'unidades' => ['📏', 'Unidades'], 'productos' => ['📦', 'Productos']] as $key => [$icon, $label])
    <a class="catalog-tab {{ $seccion === $key ? 'active' : '' }}" href="{{ route('catalogos', ['seccion' => $key]) }}" @if ($seccion === $key) aria-current="page" @endif>{{ $icon }} {{ $label }}</a>
  @endforeach
</nav>

<section class="catalog-section">
  <div class="catalog-section-heading">
    <div><h2>{{ $configuracion['titulo'] }}</h2><span class="badge badge-gray">{{ $registros->count() }} registros</span></div>
    <a class="btn btn-primary btn-sm" href="{{ route('catalogos', ['seccion' => $seccion, 'nuevo' => 1]) }}">+ Nuevo {{ $seccion === 'productos' ? 'producto' : $configuracion['singular'] }}</a>
  </div>

  @if (($mostrarFormulario || $registroEditar) && $seccion === 'productos')
    <section class="card mb-16">
      <div class="card-title">{{ $registroEditar ? 'Editar producto del catálogo' : 'Agregar producto al catálogo de Inventario' }}</div>
      @unless ($registroEditar)<p class="text-muted mb-12">Define el producto base aquí. Después podrás seleccionarlo al darlo de alta en Inventario.</p>@endunless
      <form method="POST" action="{{ $registroEditar ? route('catalogos.update', ['productos', $registroEditar->id]) : route('catalogos.store', 'productos') }}">
        @csrf
        @if ($registroEditar) @method('PUT') @endif
        <div class="form-row form-2">
          <div class="form-group">
            <label class="form-label" for="producto-nombre">Nombre del producto *</label>
            <input class="form-control" id="producto-nombre" name="nombre" maxlength="160" value="{{ old('nombre', $registroEditar->nombre ?? '') }}" required>
          </div>
          <div class="form-group">
            <label class="form-label" for="producto-tipo">Tipo de hilo *</label>
            <select class="form-control" id="producto-tipo" name="id_tipo" required>
              <option value="">Selecciona un tipo</option>
              @foreach ($tiposProducto as $tipo)<option value="{{ $tipo->id_tipo }}" @selected(old('id_tipo', $registroEditar->id_tipo ?? '') == $tipo->id_tipo)>{{ $tipo->nombre }}</option>@endforeach
            </select>
          </div>
          <div class="form-group">
            <label class="form-label" for="producto-color">Color *</label>
            <select class="form-control" id="producto-color" name="id_color" required>
              <option value="">Selecciona un color</option>
              @foreach ($coloresProducto as $color)<option value="{{ $color->id_color }}" @selected(old('id_color', $registroEditar->id_color ?? '') == $color->id_color)>{{ $color->nombre }}</option>@endforeach
            </select>
          </div>
          <div class="form-group">
            <label class="form-label" for="producto-unidad">Unidad *</label>
            <select class="form-control" id="producto-unidad" name="id_unidad" required>
              <option value="">Selecciona una unidad</option>
              @foreach ($unidadesProducto as $unidad)<option value="{{ $unidad->id_unidad }}" @selected(old('id_unidad', $registroEditar->id_unidad ?? '') == $unidad->id_unidad)>{{ $unidad->nombre }} ({{ $unidad->codigo }})</option>@endforeach
            </select>
          </div>
        </div>
        <button class="btn btn-primary" type="submit">{{ $registroEditar ? 'Guardar cambios' : 'Guardar producto en catálogo' }}</button>
        <a class="btn btn-sm" href="{{ route('catalogos', ['seccion' => 'productos']) }}">Cancelar</a>
      </form>
    </section>
  @elseif ($mostrarFormulario)
    <section class="card mb-16">
      <div class="card-title">{{ $registroEditar ? 'Editar '.$configuracion['singular'] : 'Nuevo '.$configuracion['singular'] }}</div>
      <form method="POST" action="{{ $registroEditar ? route('catalogos.update', [$seccion, $registroEditar->id]) : route('catalogos.store', $seccion) }}">
        @csrf
        @if ($registroEditar) @method('PUT') @endif
        <div class="form-row form-2">
          @foreach ($configuracion['campos'] as [$name, $label, $type, $maxlength])
            <div class="form-group">
              <label class="form-label" for="catalogo-{{ $name }}">{{ $label }}{{ in_array($name, ['nombre', 'codigo'], true) ? ' *' : '' }}</label>
              @if ($type === 'textarea')
                <textarea class="form-control" id="catalogo-{{ $name }}" name="{{ $name }}" maxlength="{{ $maxlength }}" rows="2">{{ old($name, $registroEditar->$name ?? '') }}</textarea>
              @else
                <input class="form-control" id="catalogo-{{ $name }}" name="{{ $name }}" type="text" value="{{ old($name, $registroEditar->$name ?? '') }}" maxlength="{{ $maxlength }}" @if (in_array($name, ['nombre', 'codigo'], true)) required @endif @if ($name === 'codigo_hex') placeholder="#RRGGBB" pattern="#[0-9A-Fa-f]{6}" @endif>
              @endif
            </div>
          @endforeach
        </div>
        <button class="btn btn-primary" type="submit">{{ $registroEditar ? 'Guardar cambios' : 'Crear '.$configuracion['singular'] }}</button>
        <a class="btn btn-sm" href="{{ route('catalogos', ['seccion' => $seccion]) }}">Cancelar</a>
      </form>
    </section>
  @endif

  <div class="filter-bar catalog-search">
    <label class="sr-only" for="catalog-search">Buscar en {{ $configuracion['titulo'] }}</label>
    <input class="form-control" id="catalog-search" type="search" placeholder="🔍 Buscar {{ strtolower($configuracion['singular']) }}..." autocomplete="off">
  </div>

  @if ($seccion === 'anaqueles')
    <div class="catalog-shelf-layout">
      <div class="table-wrap">
        <table id="catalog-table">
          <thead><tr><th>Código</th><th>Descripción</th><th>Productos</th><th>Acciones</th></tr></thead>
          <tbody>
            @forelse ($registros as $registro)
              <tr data-catalog-row>
                <td><strong>{{ $registro->codigo }}</strong></td>
                <td>{{ $registro->descripcion ?? '—' }}</td>
                <td>{{ $registro->productos_usando }} prod.</td>
                <td><div class="tbl-actions">
                  <a class="btn btn-sm" href="{{ route('catalogos.edit', [$seccion, $registro->id]) }}" aria-label="Editar anaquel {{ $registro->codigo }}">✏️</a>
                  <a class="btn btn-sm" href="{{ route('localizador', ['anaquel' => $registro->id]) }}">🗺️ Ver</a>
                </div></td>
              </tr>
            @empty
              <tr><td colspan="4" class="text-muted">No hay anaqueles activos.</td></tr>
            @endforelse
            <tr id="catalog-no-results" hidden><td colspan="4" class="text-muted">No se encontraron coincidencias.</td></tr>
          </tbody>
        </table>
      </div>
      <section class="card catalog-floorplan" aria-label="Plano visual de anaqueles">
        <h3>Plano visual</h3>
        @if ($planoAnaqueles->isEmpty())
          <p class="text-muted">Agrega anaqueles para visualizar el plano.</p>
        @else
          @include('panel.partials.floorplan', ['anaqueles' => $planoAnaqueles, 'conteos' => $conteosPlano, 'selectedShelfId' => null])
        @endif
      </section>
    </div>
  @else
  <div class="table-wrap">
    <table id="catalog-table">
      <thead><tr><th>#</th>
        @foreach ($configuracion['columnas'] as [$label, $key])<th>{{ $label }}</th>@endforeach
        <th>Acciones</th>
      </tr></thead>
      <tbody>
        @forelse ($registros as $index => $registro)
          <tr data-catalog-row>
            <td>{{ $index + 1 }}</td>
            @foreach ($configuracion['columnas'] as [$label, $key])
              <td>
                @if ($key === 'muestra')
                  <span class="catalog-color-swatch" style="background: {{ $registro->codigo_hex ?: '#ffffff' }}; {{ strtoupper((string) $registro->codigo_hex) === '#FFFFFF' ? 'border: 1px solid #c4c9d8;' : '' }}" aria-label="Muestra {{ $registro->nombre }}"></span>
                @elseif ($key === 'productos_usando')
                  <span class="badge {{ $registro->productos_usando ? 'badge-ok' : 'badge-gray' }}">{{ $registro->productos_usando }} productos</span>
                @elseif ($key === 'creado_en')
                  {{ $registro->creado_en ? \Illuminate\Support\Carbon::parse($registro->creado_en)->format('d/m/Y') : '—' }}
                @else
                  {{ $registro->$key ?? '—' }}
                @endif
              </td>
            @endforeach
            <td>
              @if ($seccion === 'productos')
                <div class="tbl-actions">
                  <a class="btn btn-sm" href="{{ route('catalogos.edit', [$seccion, $registro->id]) }}">✏️ Editar</a>
                  <form method="POST" action="{{ route('catalogos.productos.estado', $registro->id) }}" data-confirm="¿Desactivar este producto del catálogo? Los registros existentes en Inventario se conservarán.">
                    @csrf
                    @method('PATCH')
                    <button class="btn btn-sm btn-danger" type="submit">Desactivar</button>
                  </form>
                </div>
              @else
                <a class="btn btn-sm" href="{{ route('catalogos.edit', [$seccion, $registro->id]) }}">✏️ Editar</a>
              @endif
            </td>
          </tr>
        @empty
          <tr><td colspan="{{ count($configuracion['columnas']) + 2 }}" class="text-muted">No hay registros en este catálogo.</td></tr>
        @endforelse
        <tr id="catalog-no-results" hidden><td colspan="{{ count($configuracion['columnas']) + 2 }}" class="text-muted">No se encontraron coincidencias.</td></tr>
      </tbody>
    </table>
  </div>
  @endif
</section>
@endsection

@push('estilos')
<style>
  .catalog-tabs{display:flex;gap:6px;overflow-x:auto;border-bottom:2px solid var(--border);margin-bottom:18px}
  .catalog-tab{padding:10px 14px;color:var(--muted);text-decoration:none;font-weight:600;white-space:nowrap;border-bottom:2px solid transparent;margin-bottom:-2px}
  .catalog-tab:hover,.catalog-tab.active{color:var(--blue2)}
  .catalog-tab.active{border-color:var(--blue2)}
  .catalog-section-heading{display:flex;justify-content:space-between;align-items:center;gap:12px;margin-bottom:12px}
  .catalog-section-heading>div{display:flex;align-items:center;gap:10px}
  .catalog-section-heading h2{font-size:15px}
  .catalog-search{margin-bottom:12px;padding:12px;background:var(--surface);border:1px solid var(--border);border-radius:var(--radius-lg);box-shadow:var(--shadow)}
  .catalog-color-swatch{display:inline-block;width:28px;height:22px;border-radius:5px;border:1px solid var(--border);vertical-align:middle}
  .catalog-shelf-layout{display:grid;grid-template-columns:minmax(0,1.05fr) minmax(320px,1fr);gap:16px;align-items:start}
  .catalog-floorplan h3{font-size:14px;margin-bottom:12px}
  .sr-only{position:absolute;width:1px;height:1px;padding:0;margin:-1px;overflow:hidden;clip:rect(0,0,0,0);white-space:nowrap;border:0}
  @media(max-width:900px){.catalog-shelf-layout{grid-template-columns:1fr}}
  @media(max-width:600px){.catalog-section-heading{align-items:flex-start;flex-direction:column}}
</style>
@endpush

@push('scripts')
<script>
  document.getElementById('catalog-search')?.addEventListener('input', (event) => {
    const query = event.target.value.trim().toLocaleLowerCase();
    const rows = [...document.querySelectorAll('[data-catalog-row]')];
    let visible = 0;
    rows.forEach((row) => {
      const matches = row.textContent.toLocaleLowerCase().includes(query);
      row.hidden = !matches;
      if (matches) visible++;
    });
    const noResults = document.getElementById('catalog-no-results');
    if (noResults) noResults.hidden = visible > 0 || rows.length === 0;
  });
</script>
@endpush
