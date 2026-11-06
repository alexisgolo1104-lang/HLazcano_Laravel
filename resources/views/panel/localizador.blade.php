@extends('layouts.app')

@section('titulo', 'Localizador')

@section('contenido')
<section class="card locator-filters">
  <div class="locator-filter">
    <label class="form-label" for="locator-product">Producto</label>
    <select class="form-control" id="locator-product">
      <option value="">Seleccionar producto...</option>
      @foreach ($productos as $producto)
        <option value="{{ $producto->id_producto }}">{{ $producto->nombre }}{{ $producto->presentacion ? ' '.$producto->presentacion : '' }}</option>
      @endforeach
    </select>
  </div>
  <div class="locator-filter">
    <label class="form-label" for="locator-color">Color</label>
    <select class="form-control" id="locator-color"><option value="">Cualquier color</option>@foreach ($colores as $color)<option value="{{ $color }}">{{ $color }}</option>@endforeach</select>
  </div>
  <div class="locator-filter">
    <label class="form-label" for="locator-type">Tipo</label>
    <select class="form-control" id="locator-type"><option value="">Cualquier tipo</option>@foreach ($tipos as $tipo)<option value="{{ $tipo }}">{{ $tipo }}</option>@endforeach</select>
  </div>
  <button class="btn btn-primary locator-search-button" id="locator-search" type="button">🔍 Buscar</button>
</section>

<div class="locator-content">
  <section class="card locator-map">
    <h2>Plano visual</h2>
    @include('panel.partials.floorplan', ['anaqueles' => $anaqueles, 'conteos' => $conteosAnaquel, 'selectedShelfId' => $selectedShelfId])
    <div class="notice notice-info locator-hint">💡 Selecciona un anaquel para ver su contenido o filtra por producto, color o tipo.</div>
  </section>

  <section class="card locator-results" aria-live="polite">
    <h2>Resultado de búsqueda</h2>
    <div id="locator-results-content" class="locator-results-content"></div>
  </section>
</div>
<a class="btn btn-sm locator-back" href="{{ route('catalogos', ['seccion' => 'anaqueles']) }}">← Volver a Anaqueles</a>
@endsection

@push('estilos')
<style>
  .locator-filters{display:grid;grid-template-columns:minmax(220px,2fr) minmax(150px,1fr) minmax(150px,1fr) auto;gap:12px;align-items:end;margin-bottom:16px}
  .locator-filters .form-label{display:block;margin-bottom:5px}
  .locator-search-button{min-height:40px}
  .locator-content{display:grid;grid-template-columns:minmax(0,1fr) minmax(0,1fr);gap:16px}
  .locator-map,.locator-results{min-height:370px}
  .locator-map h2,.locator-results h2{font-size:15px;margin-bottom:16px}
  .locator-hint{margin:12px 0 0}
  .locator-results-content{display:grid;gap:8px}
  .locator-result-title{font-weight:700;margin-bottom:2px}
  .locator-result{border:1px solid var(--border);border-radius:10px;padding:12px 14px;display:flex;justify-content:space-between;align-items:center;gap:10px}
  .locator-result.low{background:var(--dangbg);border-color:var(--dangli)}
  .locator-result-name{font-weight:600}
  .locator-result-detail{font-size:12px;color:var(--muted)}
  .locator-result-status{font-size:12px;white-space:nowrap;color:var(--blue)}
  .locator-result.low .locator-result-status{color:var(--danger)}
  .locator-back{margin-top:14px}
  @media(max-width:850px){.locator-filters{grid-template-columns:1fr 1fr}.locator-content{grid-template-columns:1fr}}
  @media(max-width:480px){.locator-filters{grid-template-columns:1fr}}
</style>
@endpush

@push('scripts')
<script>
  (() => {
    const products = @js($productos);
    const initialShelfId = @js($selectedShelfId);
    const shelves = @js($anaqueles);
    const selectedProduct = document.getElementById('locator-product');
    const selectedColor = document.getElementById('locator-color');
    const selectedType = document.getElementById('locator-type');
    const results = document.getElementById('locator-results-content');
    let activeShelfId = initialShelfId;
    let searchApplied = Boolean(initialShelfId);

    function renderResults() {
      const filtered = products.filter(product => {
        if (activeShelfId && Number(product.id_anaquel) !== Number(activeShelfId)) return false;
        if (selectedProduct.value && Number(product.id_producto) !== Number(selectedProduct.value)) return false;
        if (selectedColor.value && product.color !== selectedColor.value) return false;
        if (selectedType.value && product.tipo !== selectedType.value) return false;
        return true;
      });

      results.replaceChildren();
      if (!searchApplied) {
        results.textContent = 'Selecciona un anaquel en el plano o aplica filtros para ver productos.';
        return;
      }

      const shelf = shelves.find(item => Number(item.id_anaquel) === Number(activeShelfId));
      const title = document.createElement('div');
      title.className = 'locator-result-title';
      title.textContent = `${shelf ? `Anaquel ${shelf.codigo}` : 'Productos encontrados'} — ${filtered.length} producto(s)`;
      results.appendChild(title);

      if (filtered.length === 0) {
        const empty = document.createElement('p');
        empty.className = 'text-muted';
        empty.textContent = 'No se encontraron productos con esos criterios.';
        results.appendChild(empty);
        return;
      }

      filtered.forEach(product => {
        const low = Number(product.stock_actual) <= Number(product.stock_minimo);
        const item = document.createElement('article');
        item.className = `locator-result${low ? ' low' : ''}`;
        const details = document.createElement('div');
        const name = document.createElement('div');
        name.className = 'locator-result-name';
        name.textContent = `${product.nombre}${product.presentacion ? ` ${product.presentacion}` : ''}`;
        const detail = document.createElement('div');
        detail.className = 'locator-result-detail';
        detail.textContent = `${Number(product.stock_actual).toLocaleString('es-MX')} en existencia${product.precio_venta ? ` · $${Number(product.precio_venta).toFixed(2)}` : ''}${product.posicion ? ` · ${product.posicion}` : ''}`;
        details.append(name, detail);
        const status = document.createElement('span');
        status.className = 'locator-result-status';
        status.textContent = low ? '⚠ Bajo' : 'OK';
        item.append(details, status);
        results.appendChild(item);
      });
    }

    document.querySelectorAll('.floorplan-block').forEach(link => {
      link.addEventListener('click', event => {
        event.preventDefault();
        activeShelfId = Number(new URL(link.href).searchParams.get('anaquel'));
        searchApplied = true;
        document.querySelectorAll('.floorplan-block').forEach(shelfLink => {
          shelfLink.classList.toggle('is-selected', shelfLink === link);
          if (shelfLink === link) shelfLink.setAttribute('aria-current', 'location');
          else shelfLink.removeAttribute('aria-current');
        });
        renderResults();
      });
    });

    document.getElementById('locator-search').addEventListener('click', () => {
      activeShelfId = null;
      searchApplied = true;
      document.querySelectorAll('.floorplan-block').forEach(link => {
        link.classList.remove('is-selected');
        link.removeAttribute('aria-current');
      });
      renderResults();
    });

    renderResults();
  })();
</script>
@endpush
