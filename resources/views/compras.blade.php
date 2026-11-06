@extends('layouts.app')

@section('titulo', 'Compras')

@section('contenido')
<div class="grid-2">
  <section class="card">
    <div class="card-title">Registrar compra / entrada de inventario</div>
    <form id="compra-form" method="POST" action="{{ route('compras.store') }}">
      @csrf
      <div class="form-row form-2">
        <div class="form-group"><label class="form-label" for="proveedor">Proveedor</label><select class="form-control" id="proveedor" name="id_proveedor" required><option value="">Selecciona proveedor</option>@foreach ($proveedores as $proveedor)<option value="{{ $proveedor->id_proveedor }}">{{ $proveedor->nombre }}</option>@endforeach</select></div>
      </div>
      <div class="form-row form-2">
        <div class="form-group"><label class="form-label" for="fecha">Fecha</label><input class="form-control" id="fecha" name="fecha" type="date" value="{{ now()->toDateString() }}" required></div>
        <div class="form-group"><label class="form-label" for="folio">Folio / referencia</label><input class="form-control" id="folio" name="folio" maxlength="30"></div>
      </div>
      <div class="form-group mb-12">
        <label class="form-label" for="producto-compra">Agregar producto</label>
        <select class="form-control purchase-product-select" id="producto-compra"><option value="">Selecciona producto</option>@foreach ($productos as $producto)<option value="{{ $producto->id_producto }}" data-stock="{{ $producto->stock_actual }}" data-cost="{{ $producto->precio_compra }}" data-sale="{{ $producto->precio_venta }}" @selected((int) $producto->id_producto === $productoSeleccionado)>{{ $producto->nombre }} {{ $producto->presentacion }} · Stock {{ number_format((float) $producto->stock_actual, 2) }} {{ $producto->unidad }}</option>@endforeach</select>
        <div class="form-row form-3 purchase-product-fields">
          <div class="form-group">
            <label class="form-label" for="cantidad-compra">Cantidad</label>
            <input class="form-control" id="cantidad-compra" type="number" min="0.01" step="0.01" value="1">
          </div>
          <div class="form-group">
            <label class="form-label" for="costo-compra">Precio de compra</label>
            <input class="form-control" id="costo-compra" type="number" min="0.01" step="0.01" placeholder="P. compra">
          </div>
          <div class="form-group">
            <label class="form-label" for="precio-venta-compra">Precio de venta</label>
            <input class="form-control" id="precio-venta-compra" type="number" min="0.01" step="0.01" placeholder="P. venta">
            <small class="purchase-margin" id="purchase-margin" aria-live="polite" hidden></small>
          </div>
          <button class="btn purchase-add-button" id="agregar-compra" type="button">Agregar</button>
        </div>
        <section class="purchase-history" aria-live="polite" aria-labelledby="purchase-history-title">
          <h3 id="purchase-history-title">Historial de proveedores del producto</h3>
          <div id="purchase-history-content" class="text-muted">Selecciona un producto para consultar sus proveedores anteriores.</div>
        </section>
      </div>
      <div class="table-wrap mb-12"><table><thead><tr><th>Producto</th><th>Cantidad</th><th>P. compra</th><th>P. venta</th><th>Subtotal</th><th></th></tr></thead><tbody id="compra-items"><tr><td colspan="6" class="text-muted">Agrega productos a la compra.</td></tr></tbody></table></div>
      <div class="form-group mb-12"><label class="form-label" for="notas-compra">Notas</label><textarea class="form-control" id="notas-compra" name="notas" maxlength="150"></textarea></div>
      <input type="hidden" id="compra-items-json" name="items">
      <div class="flex justify-between items-center mb-12"><strong>Total compra</strong><strong id="compra-total">$0.00</strong></div>
      <button class="btn btn-primary" type="submit">Registrar entrada de inventario</button>
    </form>
  </section>
  <section class="card">
    <div class="card-title">Historial de compras</div>
    <div class="table-wrap"><table><thead><tr><th>Fecha</th><th>Folio</th><th>Proveedor</th><th>Total</th><th>Acciones</th></tr></thead><tbody>
      @forelse ($compras as $compra)
        <tr><td>{{ $compra->fecha }}</td><td>{{ $compra->folio ?? '—' }}</td><td>{{ $compra->proveedor }}</td><td>${{ number_format((float) $compra->total, 2) }}</td><td><a class="btn btn-sm" href="{{ route('compras.ticket', $compra->id_compra) }}" target="_blank" rel="opener">Ticket</a></td></tr>
      @empty
        <tr><td colspan="5" class="text-muted">No hay compras registradas.</td></tr>
      @endforelse
    </tbody></table></div>
  </section>
</div>
@endsection

@push('scripts')
<script>
  const purchaseItems = [];
  const purchaseBody = document.getElementById('compra-items');
  const purchaseSelect = document.getElementById('producto-compra');
  const purchaseCost = document.getElementById('costo-compra');
  const purchaseSalePrice = document.getElementById('precio-venta-compra');
  const purchaseMargin = document.getElementById('purchase-margin');
  const purchaseHistory = document.getElementById('purchase-history-content');
  const purchaseHistoryUrl = @json(route('compras.historial-producto', ['id' => 'PRODUCT_ID']));
  let purchaseHistoryRequest;

  function updatePurchaseMargin() {
    const cost = Number(purchaseCost.value);
    const salePrice = Number(purchaseSalePrice.value);
    if (!Number.isFinite(cost) || !Number.isFinite(salePrice) || cost <= 0 || salePrice <= 0) {
      purchaseMargin.hidden = true;
      purchaseMargin.textContent = '';
      purchaseMargin.classList.remove('purchase-margin-positive', 'purchase-margin-negative');
      return;
    }

    const percentage = ((salePrice - cost) / cost) * 100;
    purchaseMargin.textContent = `Margen: ${percentage.toFixed(1)}%`;
    purchaseMargin.classList.toggle('purchase-margin-positive', percentage >= 0);
    purchaseMargin.classList.toggle('purchase-margin-negative', percentage < 0);
    purchaseMargin.hidden = false;
  }

  function renderPurchaseHistory(records) {
    purchaseHistory.replaceChildren();
    if (!records.length) {
      purchaseHistory.textContent = 'Este producto aún no tiene compras registradas.';
      purchaseHistory.classList.add('text-muted');
      return;
    }

    purchaseHistory.classList.remove('text-muted');
    const table = document.createElement('table');
    const head = document.createElement('thead');
    const headerRow = document.createElement('tr');
    ['Proveedor', 'Última compra', 'Compras'].forEach(label => {
      const cell = document.createElement('th');
      cell.textContent = label;
      headerRow.appendChild(cell);
    });
    head.appendChild(headerRow);
    table.appendChild(head);

    const body = document.createElement('tbody');
    records.forEach(record => {
      const row = document.createElement('tr');
      [record.proveedor, record.ultima_compra, record.total_compras].forEach(value => {
        const cell = document.createElement('td');
        cell.textContent = value;
        row.appendChild(cell);
      });
      body.appendChild(row);
    });
    table.appendChild(body);

    const wrapper = document.createElement('div');
    wrapper.className = 'table-wrap';
    wrapper.appendChild(table);
    purchaseHistory.appendChild(wrapper);
  }

  async function loadPurchaseHistory(productId) {
    purchaseHistoryRequest?.abort();
    purchaseHistory.replaceChildren();

    if (!productId) {
      purchaseHistory.textContent = 'Selecciona un producto para consultar sus proveedores anteriores.';
      purchaseHistory.classList.add('text-muted');
      return;
    }

    purchaseHistory.classList.add('text-muted');
    purchaseHistory.textContent = 'Cargando historial...';
    purchaseHistoryRequest = new AbortController();

    try {
      const response = await fetch(purchaseHistoryUrl.replace('PRODUCT_ID', encodeURIComponent(productId)), {
        headers: {Accept: 'application/json'},
        signal: purchaseHistoryRequest.signal,
      });
      if (!response.ok) throw new Error('No se pudo consultar el historial de compras.');
      renderPurchaseHistory(await response.json());
    } catch (error) {
      if (error.name === 'AbortError') return;
      purchaseHistory.textContent = 'No se pudo cargar el historial de proveedores.';
      purchaseHistory.classList.add('text-muted');
      window.showToast(error.message, 'error');
    }
  }

  function renderPurchaseItems() {
    purchaseBody.replaceChildren();
    let total = 0;
    purchaseItems.forEach((item, index) => {
      const lineTotal = item.quantity * item.cost;
      total += lineTotal;
      const row = document.createElement('tr');
      [item.name, item.quantity.toFixed(2), '$' + item.cost.toFixed(2), '$' + item.sale.toFixed(2), '$' + lineTotal.toFixed(2)].forEach(value => {
        const cell = document.createElement('td'); cell.textContent = value; row.appendChild(cell);
      });
      const action = document.createElement('td');
      const remove = document.createElement('button'); remove.type = 'button'; remove.className = 'btn btn-sm btn-danger'; remove.textContent = 'Quitar';
      remove.addEventListener('click', () => { purchaseItems.splice(index, 1); renderPurchaseItems(); });
      action.appendChild(remove); row.appendChild(action); purchaseBody.appendChild(row);
    });
    if (!purchaseItems.length) purchaseBody.innerHTML = '<tr><td colspan="6" class="text-muted">Agrega productos a la compra.</td></tr>';
    document.getElementById('compra-total').textContent = '$' + total.toFixed(2);
    document.getElementById('compra-items-json').value = JSON.stringify(purchaseItems.map(({id, quantity, cost, sale}) => ({id_producto: id, cantidad: quantity, precio_unit: cost, precio_venta: sale})));
  }
  document.getElementById('agregar-compra').addEventListener('click', () => {
    const option = purchaseSelect.selectedOptions[0];
    const quantity = Number(document.getElementById('cantidad-compra').value);
    if (!option.value || !Number.isFinite(quantity) || quantity <= 0) return;
    const item = purchaseItems.find(line => line.id === Number(option.value));
    const requested = quantity + (item?.quantity || 0);
    if (requested + Number(option.dataset.stock) > 99999999.99) { window.showToast('La cantidad excede el límite permitido.', 'error'); return; }
    if (item) item.quantity = requested;
    else purchaseItems.push({id: Number(option.value), name: option.textContent.split(' · Stock')[0], quantity, cost: Number(purchaseCost.value || option.dataset.cost || 0), sale: Number(purchaseSalePrice.value || option.dataset.sale || 0)});
    renderPurchaseItems();
  });
  purchaseSelect.addEventListener('change', () => {
    const option = purchaseSelect.selectedOptions[0];
    purchaseCost.value = option?.dataset?.cost || '';
    purchaseSalePrice.value = option?.dataset?.sale || '';
    updatePurchaseMargin();
    loadPurchaseHistory(purchaseSelect.value);
  });
  purchaseCost.addEventListener('input', updatePurchaseMargin);
  purchaseSalePrice.addEventListener('input', updatePurchaseMargin);
  if (purchaseSelect.value) purchaseSelect.dispatchEvent(new Event('change'));
  document.getElementById('compra-form').addEventListener('submit', event => {
    if (!purchaseItems.length) { event.preventDefault(); window.showToast('Agrega al menos un producto.', 'error'); }
  });
</script>
@endpush
