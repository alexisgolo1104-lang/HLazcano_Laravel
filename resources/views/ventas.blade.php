@extends('layouts.app')

@section('titulo', 'Ventas')

@section('contenido')
<div class="grid-2">
  <section class="card">
    <div class="card-title">Registrar venta</div>
    <form id="venta-form" method="POST" action="{{ route('ventas.store') }}">
      @csrf
      <div class="form-row form-2">
        <div class="form-group">
          <label class="form-label" for="cliente">Cliente</label>
          <select class="form-control" name="id_cliente" id="cliente"><option value="">Venta directa</option>@foreach ($clientes as $cliente)<option value="{{ $cliente->id_cliente }}">{{ $cliente->nombre }}</option>@endforeach</select>
        </div>
      </div>
      <div class="form-group mb-12">
        <div class="form-label">Agregar producto del catálogo</div>
        <div class="form-row form-2 sale-product-filters">
          <div class="form-group">
            <label class="form-label" for="filtro-material">Filtrar por material</label>
            <select class="form-control" id="filtro-material">
              <option value="">Todos los materiales</option>
              @foreach ($materiales as $material)
                <option value="{{ $material->id_tipo }}">{{ $material->nombre }}</option>
              @endforeach
            </select>
          </div>
          <div class="form-group">
            <label class="form-label" for="producto">Producto</label>
            <select class="form-control" id="producto">
              <option value="">Selecciona producto</option>
              @foreach ($productos as $producto)
                <option value="{{ $producto->id_producto }}" data-material="{{ $producto->id_tipo }}" data-price="{{ number_format((float) $producto->precio_venta, 2, '.', '') }}" data-stock="{{ number_format((float) $producto->stock_actual, 2, '.', '') }}" data-unit="{{ $producto->unidad }}">{{ $producto->nombre }}</option>
              @endforeach
            </select>
          </div>
        </div>
        <div class="sale-product-details mb-12" aria-live="polite">
          <div class="sale-product-detail">
            <span class="form-label">Precio del producto</span>
            <strong id="producto-precio">—</strong>
          </div>
          <div class="sale-product-detail">
            <span class="form-label">Stock disponible</span>
            <strong id="producto-stock">—</strong>
          </div>
        </div>
        <div class="flex gap-8 sale-product-add">
          <input class="form-control" id="cantidad" type="number" min="0.01" step="0.01" value="1" style="max-width:110px" aria-label="Cantidad">
          <button class="btn" id="agregar-producto" type="button">Agregar</button>
        </div>
      </div>
      <div class="table-wrap mb-12"><table><thead><tr><th>Producto</th><th>Cantidad</th><th>Precio</th><th>Subtotal</th><th></th></tr></thead><tbody id="venta-items"><tr><td colspan="5" class="text-muted">Agrega productos a la venta.</td></tr></tbody></table></div>
      <div class="form-group mb-12"><label class="form-label">Total</label><div id="venta-total" class="stat-num">$0.00</div></div>
      <div class="form-group mb-12"><label class="form-label" for="notas">Notas</label><textarea class="form-control" id="notas" name="notas" maxlength="150"></textarea></div>
      <input type="hidden" name="items" id="items-json">
      <button class="btn btn-primary" type="submit">Registrar venta</button>
    </form>
  </section>
  <section class="card">
    <div class="card-title">Historial de ventas</div>
    <div class="table-wrap"><table><thead><tr><th>Folio</th><th>Fecha</th><th>Cliente</th><th>Vendedor</th><th>Total</th><th>Acciones</th></tr></thead><tbody>
      @forelse ($ventas as $venta)
        <tr>
          <td>{{ $venta->folio }}</td><td>{{ $venta->fecha }}</td><td>{{ $venta->cliente ?? 'Venta directa' }}</td><td>{{ $venta->vendedor }}</td><td>${{ number_format((float) $venta->total, 2) }}</td>
          <td>
            <a class="btn btn-sm" href="{{ route('ventas.ticket', $venta->id_venta) }}" target="_blank" rel="opener">Ticket</a>
            @if (!\Illuminate\Support\Str::startsWith(mb_strtoupper(trim((string) $venta->notas)), ['CANCELADA:', 'DEVOLUCION:']))
              <details>
                <summary class="btn btn-sm">Cancelar / devolver</summary>
                <form method="POST" action="{{ route('ventas.cancelar', $venta->id_venta) }}">
                  @csrf
                  <select class="form-control" name="tipo" required><option value="cancelacion">Cancelación</option><option value="devolucion">Devolución</option></select>
                  <input class="form-control" name="motivo" maxlength="100" placeholder="Motivo" required>
                  <button class="btn btn-sm btn-danger" type="submit">Confirmar</button>
                </form>
              </details>
            @else
              <span class="badge badge-gray">{{ \Illuminate\Support\Str::startsWith(mb_strtoupper(trim((string) $venta->notas)), 'DEVOLUCION:') ? 'Devuelta' : 'Cancelada' }}</span>
            @endif
          </td>
        </tr>
      @empty
        <tr><td colspan="6" class="text-muted">No hay ventas registradas.</td></tr>
      @endforelse
    </tbody></table></div>
  </section>
</div>
@endsection

@push('scripts')
<script>
  const saleItems = [];
  const productSelect = document.getElementById('producto');
  const materialFilter = document.getElementById('filtro-material');
  const saleBody = document.getElementById('venta-items');
  const saleForm = document.getElementById('venta-form');
  const saleDraftKey = @json('hlazcano:ventas:borrador:'.auth()->id());
  const saleDraftCompleted = @json((bool) session('sale_completed', false));

  if (saleDraftCompleted) sessionStorage.removeItem(saleDraftKey);

  function saveSaleDraft() {
    try {
      sessionStorage.setItem(saleDraftKey, JSON.stringify({
        client: document.getElementById('cliente').value,
        material: materialFilter.value,
        product: productSelect.value,
        quantity: document.getElementById('cantidad').value,
        notes: document.getElementById('notas').value,
        items: saleItems.map(({id, name, price, quantity, unavailable}) => ({
          id, name, price, quantity, unavailable: Boolean(unavailable),
        })),
      }));
    } catch (error) {
      window.showToast('No se pudo guardar el borrador de venta en esta pestaña.', 'error');
    }
  }

  function restoreSaleDraft() {
    let draft;
    try {
      const saved = sessionStorage.getItem(saleDraftKey);
      if (!saved) return;
      draft = JSON.parse(saved);
    } catch (error) {
      sessionStorage.removeItem(saleDraftKey);
      window.showToast('El borrador de venta estaba dañado y se inició uno nuevo.', 'error');
      return;
    }

    document.getElementById('cliente').value = draft.client || '';
    materialFilter.value = draft.material || '';
    document.getElementById('cantidad').value = draft.quantity || '1';
    document.getElementById('notas').value = draft.notes || '';
    filterProductsByMaterial();

    const savedItems = Array.isArray(draft.items) ? draft.items : [];
    savedItems.forEach(item => {
      const option = [...productSelect.options].find(candidate => Number(candidate.value) === Number(item.id));
      saleItems.push(option
        ? {
          id: Number(option.value),
          name: option.textContent,
          price: Number(option.dataset.price),
          quantity: Number(item.quantity),
          unavailable: false,
        }
        : {
          id: Number(item.id),
          name: String(item.name || 'Producto'),
          price: Number(item.price) || 0,
          quantity: Number(item.quantity),
          unavailable: true,
        });
    });

    const savedProduct = [...productSelect.options].find(option => option.value === String(draft.product || ''));
    if (savedProduct && !savedProduct.hidden) productSelect.value = savedProduct.value;
    updateProductDetails();
    renderSaleItems();
  }

  function updateProductDetails() {
    const option = productSelect.selectedOptions[0];
    document.getElementById('producto-precio').textContent = option.value
      ? '$' + Number(option.dataset.price).toFixed(2)
      : '—';
    document.getElementById('producto-stock').textContent = option.value
      ? Number(option.dataset.stock).toFixed(2) + ' ' + option.dataset.unit
      : '—';
  }
  function filterProductsByMaterial() {
    const material = materialFilter.value;
    [...productSelect.options].forEach((option, index) => {
      if (index > 0) option.hidden = Boolean(material) && option.dataset.material !== material;
    });
    const selected = productSelect.selectedOptions[0];
    if (selected && selected.value && selected.hidden) productSelect.value = '';
    updateProductDetails();
  }
  function renderSaleItems() {
    saleBody.replaceChildren();
    let subtotal = 0;
    saleItems.forEach((item, index) => {
      const line = item.price * item.quantity;
      subtotal += line;
      const row = document.createElement('tr');
      const productName = item.unavailable ? `${item.name} (sin disponibilidad actual)` : item.name;
      [productName, item.quantity.toFixed(2), '$' + item.price.toFixed(2), '$' + line.toFixed(2)].forEach(value => {
        const cell = document.createElement('td');
        cell.textContent = value;
        row.appendChild(cell);
      });
      const action = document.createElement('td');
      const remove = document.createElement('button');
      remove.type = 'button';
      remove.className = 'btn btn-sm btn-danger';
      remove.textContent = 'Quitar';
      remove.addEventListener('click', () => { saleItems.splice(index, 1); renderSaleItems(); });
      action.appendChild(remove);
      row.appendChild(action);
      saleBody.appendChild(row);
    });
    if (!saleItems.length) saleBody.innerHTML = '<tr><td colspan="5" class="text-muted">Agrega productos a la venta.</td></tr>';
    document.getElementById('venta-total').textContent = '$' + subtotal.toFixed(2);
    document.getElementById('items-json').value = JSON.stringify(saleItems.map(({id, quantity}) => ({id_producto: id, cantidad: quantity})));
    saveSaleDraft();
  }
  document.getElementById('agregar-producto').addEventListener('click', () => {
    const option = productSelect.selectedOptions[0];
    const quantity = Number(document.getElementById('cantidad').value);
    if (!option.value || !Number.isFinite(quantity) || quantity <= 0) return;
    const existing = saleItems.find(item => item.id === Number(option.value));
    const requested = quantity + (existing?.quantity || 0);
    if (requested > Number(option.dataset.stock)) { window.showToast('La cantidad excede el stock disponible.', 'error'); return; }
    if (existing) existing.quantity = requested;
    else saleItems.push({id: Number(option.value), name: option.textContent, price: Number(option.dataset.price), quantity});
    renderSaleItems();
  });
  materialFilter.addEventListener('change', filterProductsByMaterial);
  productSelect.addEventListener('change', updateProductDetails);
  saleForm.addEventListener('input', saveSaleDraft);
  saleForm.addEventListener('change', saveSaleDraft);
  restoreSaleDraft();
  updateProductDetails();
  saleForm.addEventListener('submit', event => {
    if (!saleItems.length) { event.preventDefault(); window.showToast('Agrega al menos un producto.', 'error'); }
  });
</script>
@endpush
