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
          <select class="form-control" name="id_cliente" id="cliente"><option value="">Venta directa</option>@foreach ($clientes as $cliente)<option value="{{ $cliente->id_cliente }}" data-store="{{ $cliente->id_tienda }}">{{ $cliente->nombre }}</option>@endforeach</select>
        </div>
        @if (auth()->user()->rol === 'superadmin')
        <div class="form-group"><label class="form-label" for="tienda">Sucursal</label><select class="form-control" name="id_tienda" id="tienda" required>@foreach ($tiendas as $tienda)<option value="{{ $tienda->id_tienda }}">{{ $tienda->nombre }}</option>@endforeach</select></div>
        @endif
      </div>
      <div class="form-group mb-12">
        <label class="form-label" for="producto">Producto</label>
        <div class="flex gap-8">
          <select class="form-control" id="producto" style="flex:1"><option value="">Selecciona producto</option>@foreach ($productos as $producto)<option value="{{ $producto->id_producto }}" data-store="{{ $producto->id_tienda }}" data-price="{{ $producto->precio_venta }}" data-stock="{{ $producto->stock_actual }}">{{ $producto->nombre }} {{ $producto->presentacion }} · Stock {{ number_format((float) $producto->stock_actual, 2) }} {{ $producto->unidad }}</option>@endforeach</select>
          <input class="form-control" id="cantidad" type="number" min="0.01" step="0.01" value="1" style="max-width:110px" aria-label="Cantidad">
          <button class="btn" id="agregar-producto" type="button">Agregar</button>
        </div>
      </div>
      <div class="table-wrap mb-12"><table><thead><tr><th>Producto</th><th>Cantidad</th><th>Precio</th><th>Subtotal</th><th></th></tr></thead><tbody id="venta-items"><tr><td colspan="5" class="text-muted">Agrega productos a la venta.</td></tr></tbody></table></div>
      <div class="form-row form-2">
        <div class="form-group"><label class="form-label" for="descuento">Descuento (%)</label><input class="form-control" id="descuento" name="descuento" type="number" min="0" max="100" step="0.01" value="0"></div>
        <div class="form-group"><label class="form-label">Total</label><div id="venta-total" class="stat-num">$0.00</div></div>
      </div>
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
            <a class="btn btn-sm" href="{{ route('ventas.ticket', $venta->id_venta) }}" target="_blank" rel="noopener">Ticket</a>
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
  const saleBody = document.getElementById('venta-items');
  const saleDiscount = document.getElementById('descuento');
  function renderSaleItems() {
    saleBody.replaceChildren();
    let subtotal = 0;
    saleItems.forEach((item, index) => {
      const line = item.price * item.quantity;
      subtotal += line;
      const row = document.createElement('tr');
      [item.name, item.quantity.toFixed(2), '$' + item.price.toFixed(2), '$' + line.toFixed(2)].forEach(value => {
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
    document.getElementById('venta-total').textContent = '$' + (subtotal * (1 - Number(saleDiscount.value || 0) / 100)).toFixed(2);
    document.getElementById('items-json').value = JSON.stringify(saleItems.map(({id, quantity}) => ({id_producto: id, cantidad: quantity})));
  }
  document.getElementById('agregar-producto').addEventListener('click', () => {
    const option = productSelect.selectedOptions[0];
    const quantity = Number(document.getElementById('cantidad').value);
    if (!option.value || !Number.isFinite(quantity) || quantity <= 0) return;
    const existing = saleItems.find(item => item.id === Number(option.value));
    const requested = quantity + (existing?.quantity || 0);
    if (requested > Number(option.dataset.stock)) { alert('La cantidad excede el stock disponible.'); return; }
    if (existing) existing.quantity = requested;
    else saleItems.push({id: Number(option.value), name: option.textContent.split(' · Stock')[0], price: Number(option.dataset.price), quantity});
    renderSaleItems();
  });
  saleDiscount.addEventListener('input', renderSaleItems);
  const saleStore = document.getElementById('tienda');
  if (saleStore) {
    saleStore.addEventListener('change', () => {
    [productSelect, document.getElementById('cliente')].forEach(select => {
      Array.from(select.options).forEach(option => {
        if (option.value) option.hidden = option.dataset.store !== saleStore.value;
      });
      if (select.selectedOptions[0]?.hidden) select.value = '';
    });
    saleItems.splice(0);
    renderSaleItems();
  });
  saleStore.dispatchEvent(new Event('change'));
  }
  document.getElementById('venta-form').addEventListener('submit', event => {
    if (!saleItems.length) { event.preventDefault(); alert('Agrega al menos un producto.'); }
  });
</script>
@endpush
