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
        @if (auth()->user()->rol === 'superadmin')
        <div class="form-group"><label class="form-label" for="tienda-compra">Sucursal</label><select class="form-control" id="tienda-compra" name="id_tienda" required>@foreach ($tiendas as $tienda)<option value="{{ $tienda->id_tienda }}">{{ $tienda->nombre }}</option>@endforeach</select></div>
        @endif
      </div>
      <div class="form-row form-2">
        <div class="form-group"><label class="form-label" for="fecha">Fecha</label><input class="form-control" id="fecha" name="fecha" type="date" value="{{ now()->toDateString() }}" required></div>
        <div class="form-group"><label class="form-label" for="folio">Folio / referencia</label><input class="form-control" id="folio" name="folio" maxlength="30"></div>
      </div>
      <div class="form-group mb-12">
        <label class="form-label" for="producto-compra">Agregar producto</label>
        <div class="flex gap-8">
          <select class="form-control" id="producto-compra" style="flex:1"><option value="">Selecciona producto</option>@foreach ($productos as $producto)<option value="{{ $producto->id_producto }}" data-store="{{ $producto->id_tienda }}" data-stock="{{ $producto->stock_actual }}" data-cost="{{ $producto->precio_compra }}" data-sale="{{ $producto->precio_venta }}">{{ $producto->nombre }} {{ $producto->presentacion }} · Stock {{ number_format((float) $producto->stock_actual, 2) }} {{ $producto->unidad }}</option>@endforeach</select>
          <input class="form-control" id="cantidad-compra" type="number" min="0.01" step="0.01" value="1" style="max-width:95px" aria-label="Cantidad">
          <input class="form-control" id="costo-compra" type="number" min="0.01" step="0.01" style="max-width:110px" placeholder="P. compra" aria-label="Precio de compra">
          <input class="form-control" id="precio-venta-compra" type="number" min="0.01" step="0.01" style="max-width:110px" placeholder="P. venta" aria-label="Precio de venta">
          <button class="btn" id="agregar-compra" type="button">Agregar</button>
        </div>
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
    <div class="table-wrap"><table><thead><tr><th>Fecha</th><th>Folio</th><th>Proveedor</th><th>Sucursal</th><th>Total</th></tr></thead><tbody>
      @forelse ($compras as $compra)
        <tr><td>{{ $compra->fecha }}</td><td>{{ $compra->folio ?? '—' }}</td><td>{{ $compra->proveedor }}</td><td>{{ $compra->tienda }}</td><td>${{ number_format((float) $compra->total, 2) }}</td></tr>
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
    if (requested + Number(option.dataset.stock) > 99999999.99) { alert('La cantidad excede el límite permitido.'); return; }
    if (item) item.quantity = requested;
    else purchaseItems.push({id: Number(option.value), name: option.textContent.split(' · Stock')[0], quantity, cost: Number(purchaseCost.value || option.dataset.cost || 0), sale: Number(purchaseSalePrice.value || option.dataset.sale || 0)});
    renderPurchaseItems();
  });
  purchaseSelect.addEventListener('change', () => {
    const option = purchaseSelect.selectedOptions[0];
    purchaseCost.value = option?.dataset?.cost || '';
    purchaseSalePrice.value = option?.dataset?.sale || '';
  });
  const purchaseStore = document.getElementById('tienda-compra');
  if (purchaseStore) {
    purchaseStore.addEventListener('change', () => {
      Array.from(purchaseSelect.options).forEach(option => {
        if (option.value) option.hidden = option.dataset.store !== purchaseStore.value;
      });
      if (purchaseSelect.selectedOptions[0]?.hidden) purchaseSelect.value = '';
      purchaseItems.splice(0);
      renderPurchaseItems();
    });
    purchaseStore.dispatchEvent(new Event('change'));
  }
  document.getElementById('compra-form').addEventListener('submit', event => {
    if (!purchaseItems.length) { event.preventDefault(); alert('Agrega al menos un producto.'); }
  });
</script>
@endpush
