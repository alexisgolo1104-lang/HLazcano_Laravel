@extends('layouts.app')

@section('titulo', isset($producto) ? 'Editar producto' : 'Nuevo producto')

@section('contenido')
<section class="card">
  <form method="POST" action="{{ isset($producto) ? route('inventario.update', $producto->id_producto) : route('inventario.store') }}">
    @csrf
    @if (isset($producto)) @method('PUT') @endif
    <div class="form-row form-2">
      <div class="form-group">
        <label class="form-label" for="id_catalogo">Producto del catálogo *</label>
        <select class="form-control" id="id_catalogo" name="id_catalogo" required>
          <option value="">Selecciona un producto</option>
          @foreach ($catalogos as $catalogo)
            <option value="{{ $catalogo->id_catalogo }}" @selected(old('id_catalogo', $producto->id_catalogo ?? '') == $catalogo->id_catalogo)>{{ $catalogo->nombre }}</option>
          @endforeach
        </select>
        @error('id_catalogo')<small class="text-danger">{{ $message }}</small>@enderror
      </div>
      <div class="form-group">
        <label class="form-label" for="presentacion">Presentación</label>
        <input class="form-control" id="presentacion" name="presentacion" maxlength="40" value="{{ old('presentacion', $producto->presentacion ?? '') }}">
      </div>
    </div>
    <div class="form-row form-2">
      <div class="form-group">
        <label class="form-label" for="id_tipo">Tipo *</label>
        <select class="form-control" id="id_tipo" name="id_tipo" required>
          @foreach ($tipos as $tipo)<option value="{{ $tipo->id_tipo }}" @selected(old('id_tipo', $producto->id_tipo ?? '') == $tipo->id_tipo)>{{ $tipo->nombre }}</option>@endforeach
        </select>
      </div>
      <div class="form-group">
        <label class="form-label" for="id_color">Color *</label>
        <select class="form-control" id="id_color" name="id_color" required>
          @foreach ($colores as $color)<option value="{{ $color->id_color }}" @selected(old('id_color', $producto->id_color ?? '') == $color->id_color)>{{ $color->nombre }}</option>@endforeach
        </select>
      </div>
    </div>
    <div class="form-row form-2">
      <div class="form-group">
        <label class="form-label" for="id_unidad">Unidad *</label>
        <select class="form-control" id="id_unidad" name="id_unidad" required>
          @foreach ($unidades as $unidad)<option value="{{ $unidad->id_unidad }}" @selected(old('id_unidad', $producto->id_unidad ?? '') == $unidad->id_unidad)>{{ $unidad->nombre }} ({{ $unidad->codigo }})</option>@endforeach
        </select>
      </div>
      <div class="form-group">
        <label class="form-label" for="id_proveedor">Proveedor</label>
        <select class="form-control" id="id_proveedor" name="id_proveedor">
          <option value="">Sin proveedor</option>
          @foreach ($proveedores as $proveedor)
            <option value="{{ $proveedor->id_proveedor }}" @selected(old('id_proveedor', $producto->id_proveedor ?? '') == $proveedor->id_proveedor)>{{ $proveedor->nombre }}</option>
          @endforeach
        </select>
      </div>
    </div>
    <div class="form-row form-2">
      <div class="form-group">
        <label class="form-label" for="id_anaquel">Anaquel</label>
        <select class="form-control" id="id_anaquel" name="id_anaquel">
          <option value="">Sin asignar</option>
          @foreach ($anaqueles as $anaquel)<option value="{{ $anaquel->id_anaquel }}" @selected(old('id_anaquel', $producto->id_anaquel ?? '') == $anaquel->id_anaquel)>{{ $anaquel->codigo }} — {{ $anaquel->descripcion }}</option>@endforeach
        </select>
      </div>
      @if (auth()->user()->rol === 'superadmin')
      <div class="form-group">
        <label class="form-label" for="id_tienda">Sucursal *</label>
        <select class="form-control" id="id_tienda" name="id_tienda" required>
          @foreach ($tiendas as $tienda)<option value="{{ $tienda->id_tienda }}" @selected(old('id_tienda', $producto->id_tienda ?? '') == $tienda->id_tienda)>{{ $tienda->nombre }}</option>@endforeach
        </select>
      </div>
      @endif
    </div>
    <div class="form-row form-2">
      <div class="form-group"><label class="form-label" for="stock_actual">Existencia *</label><input class="form-control" id="stock_actual" name="stock_actual" type="number" step="0.01" min="0" value="{{ old('stock_actual', $producto->stock_actual ?? '0.00') }}" required></div>
      <div class="form-group"><label class="form-label" for="stock_minimo">Stock mínimo *</label><input class="form-control" id="stock_minimo" name="stock_minimo" type="number" step="0.01" min="0" value="{{ old('stock_minimo', $producto->stock_minimo ?? '0.00') }}" required></div>
    </div>
    <div class="form-row form-2">
      <div class="form-group"><label class="form-label" for="precio_compra">Precio de compra</label><input class="form-control" id="precio_compra" name="precio_compra" type="number" step="0.01" min="0" value="{{ old('precio_compra', $producto->precio_compra ?? '') }}"></div>
      <div class="form-group"><label class="form-label" for="precio_venta">Precio de venta</label><input class="form-control" id="precio_venta" name="precio_venta" type="number" step="0.01" min="0" value="{{ old('precio_venta', $producto->precio_venta ?? '') }}"></div>
    </div>
    <div class="form-group mb-16"><label class="form-label" for="posicion_anaquel">Ubicación dentro del anaquel</label><input class="form-control" id="posicion_anaquel" name="posicion_anaquel" maxlength="50" value="{{ old('posicion_anaquel', $producto->posicion_anaquel ?? '') }}"></div>
    <div class="flex gap-8">
      <button class="btn btn-primary" type="submit">{{ isset($producto) ? 'Guardar cambios' : 'Registrar producto' }}</button>
      <a class="btn" href="{{ route('inventario') }}">Cancelar</a>
    </div>
  </form>
</section>
@endsection
