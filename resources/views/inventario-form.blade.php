@extends('layouts.app')

@section('titulo', isset($producto) ? 'Editar producto' : 'Nuevo producto')

@section('contenido')
@php($editando = isset($producto))
<section class="card product-form-card">
  <form method="POST" action="{{ $editando ? route('inventario.update', $producto->id_producto) : route('inventario.store') }}">
    @csrf
    @if ($editando) @method('PUT') @endif

    <header class="product-form-header">
      <h2>{{ $editando ? 'Editar producto' : 'Nuevo producto' }}</h2>
      <a class="product-form-close" href="{{ route('inventario') }}" aria-label="Cerrar">×</a>
    </header>

    <div class="product-form-body">
      <div class="form-row form-2 product-form-grid">
        <div class="form-group">
          <label class="form-label" for="id_catalogo">{{ $editando ? 'Producto del catálogo' : 'Nombre del producto (catálogo)' }} *</label>
          <select class="form-control" id="id_catalogo" name="id_catalogo" required>
            <option value="">Seleccionar del catálogo...</option>
            @foreach ($catalogos as $catalogo)
              <option
                value="{{ $catalogo->id_catalogo }}"
                data-type="{{ $catalogo->tipo_nombre }}"
                data-color="{{ $catalogo->color_nombre }}"
                data-color-hex="{{ $catalogo->color_hex }}"
                data-unit="{{ $catalogo->unidad_nombre }} ({{ $catalogo->unidad_codigo }})"
                @selected(old('id_catalogo', $producto->id_catalogo ?? '') == $catalogo->id_catalogo)
              >{{ $catalogo->nombre }}</option>
            @endforeach
          </select>
          @error('id_catalogo')<small class="text-danger">{{ $message }}</small>@enderror
          @unless ($editando)<small class="form-hint">Selecciona del catálogo para evitar errores de escritura.</small>@endunless
        </div>
      </div>

      <div class="form-row form-2 product-form-grid">
        <div class="form-group">
          <label class="form-label" for="id_tipo">Tipo de hilo</label>
          <input class="form-control" id="id_tipo" value="" placeholder="Se asigna del catálogo" disabled>
        </div>
        <div class="form-group">
          <label class="form-label" for="id_color">Color</label>
          <div class="product-color-field">
            <span class="product-color-swatch" id="color_muestra" aria-hidden="true"></span>
            <input class="form-control" id="id_color" value="" placeholder="Se asigna del catálogo" disabled>
          </div>
        </div>
      </div>

      <div class="form-row form-3 product-form-grid">
        <div class="form-group">
          <label class="form-label" for="stock_actual">{{ $editando ? 'Existencia' : 'Stock inicial' }}</label>
          <input class="form-control" id="stock_actual" type="number" value="{{ number_format((float) ($producto->stock_actual ?? 0), 2, '.', '') }}" disabled>
        </div>
        <div class="form-group">
          <label class="form-label" for="stock_minimo">Stock mínimo *</label>
          <input class="form-control" id="stock_minimo" name="stock_minimo" type="number" step="0.01" min="0" value="{{ old('stock_minimo', $producto->stock_minimo ?? '5.00') }}" required>
        </div>
        <div class="form-group">
          <label class="form-label" for="id_unidad">Unidad</label>
          <input class="form-control" id="id_unidad" value="" placeholder="Se asigna del catálogo" disabled>
        </div>
      </div>

      <div class="form-row form-2 product-form-grid">
        <div class="form-group">
          <label class="form-label" for="precio_compra">Precio de compra</label>
          <input class="form-control" id="precio_compra" type="number" value="{{ number_format((float) ($producto->precio_compra ?? 0), 2, '.', '') }}" disabled>
        </div>
        <div class="form-group">
          <label class="form-label" for="precio_venta">Precio de venta</label>
          <input class="form-control" id="precio_venta" name="precio_venta" type="number" step="0.01" min="0" value="{{ old('precio_venta', $producto->precio_venta ?? '') }}">
        </div>
      </div>

      <div class="form-row form-2 product-form-grid">
        <div class="form-group">
          <label class="form-label" for="id_anaquel">Anaquel</label>
          <select class="form-control" id="id_anaquel" name="id_anaquel">
            <option value="">Seleccionar...</option>
            @foreach ($anaqueles as $anaquel)<option value="{{ $anaquel->id_anaquel }}" @selected(old('id_anaquel', $producto->id_anaquel ?? '') == $anaquel->id_anaquel)>{{ $anaquel->codigo }} — {{ $anaquel->descripcion }}</option>@endforeach
          </select>
        </div>
        <div class="form-group">
          <label class="form-label" for="posicion_anaquel">Posición en anaquel</label>
          <input class="form-control" id="posicion_anaquel" name="posicion_anaquel" maxlength="50" placeholder="Ej: Fila 1, Columna 2" value="{{ old('posicion_anaquel', $producto->posicion_anaquel ?? '') }}">
        </div>
      </div>
    </div>

    <footer class="product-form-footer">
      <a class="btn" href="{{ route('inventario') }}">Cancelar</a>
      <button class="btn btn-primary" type="submit">{{ $editando ? 'Guardar cambios' : '✓ Guardar producto' }}</button>
    </footer>
  </form>
</section>
@endsection

@push('scripts')
<script>
  const catalogSelect = document.getElementById('id_catalogo');
  const catalogFields = {
    tipo: document.getElementById('id_tipo'),
    color: document.getElementById('id_color'),
    unidad: document.getElementById('id_unidad'),
  };
  const colorSwatch = document.getElementById('color_muestra');

  function updateCatalogFields() {
    const option = catalogSelect.selectedOptions[0];
    catalogFields.tipo.value = option.dataset.type || '';
    catalogFields.color.value = option.dataset.color || '';
    catalogFields.unidad.value = option.dataset.unit || '';
    colorSwatch.style.backgroundColor = option.dataset.colorHex || '#ccc';
  }

  catalogSelect.addEventListener('change', updateCatalogFields);
  updateCatalogFields();
</script>
@endpush
