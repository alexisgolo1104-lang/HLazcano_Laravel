@extends('layouts.app')

@section('titulo', 'Egresos')

@section('contenido')
<form class="filter-bar" method="GET" action="{{ route('egresos') }}">
  <label class="form-label" for="mes">Periodo</label>
  <input class="form-control" id="mes" name="mes" type="month" value="{{ $month }}" required>
  <button class="btn" type="submit">Consultar</button>
</form>

<div class="grid-4">
  <article class="stat-card"><div class="stat-num">${{ number_format((float) $totals['servicios'], 2) }}</div><div class="stat-lbl">Servicios de luz y agua</div></article>
  <article class="stat-card"><div class="stat-num">${{ number_format((float) $totals['sueldos'], 2) }}</div><div class="stat-lbl">Sueldos de empleados</div></article>
  <article class="stat-card"><div class="stat-num">${{ number_format((float) $totals['compras'], 2) }}</div><div class="stat-lbl">Compras registradas automáticamente</div></article>
  <article class="stat-card"><div class="stat-num">${{ number_format((float) $totals['total'], 2) }}</div><div class="stat-lbl">Egresos totales del periodo</div><div class="stat-delta">Otros gastos: ${{ number_format((float) $totals['otros'], 2) }}</div></article>
</div>

<div class="grid-2">
  <section class="card">
    <div class="card-title">Registrar egreso</div>
    <form method="POST" action="{{ route('egresos.store') }}">
      @csrf
      <div class="form-row form-2">
        <div class="form-group">
          <label class="form-label" for="categoria">Tipo de egreso</label>
          <select class="form-control" id="categoria" name="categoria" required>
            <option value="">Selecciona tipo</option>
            <option value="servicio_luz" @selected(old('categoria') === 'servicio_luz')>Servicio de luz</option>
            <option value="servicio_agua" @selected(old('categoria') === 'servicio_agua')>Servicio de agua</option>
            <option value="sueldo" @selected(old('categoria') === 'sueldo')>Sueldo</option>
            <option value="otros_gastos" @selected(old('categoria') === 'otros_gastos')>Otros gastos</option>
          </select>
        </div>
        <div class="form-group" id="empleado-grupo" @if (old('categoria') !== 'sueldo') hidden @endif>
          <label class="form-label" for="destinatario">Empleado o integrante del equipo</label>
          <select class="form-control" id="destinatario" name="destinatario" disabled>
            <option value="">Selecciona persona</option>
            @foreach ($empleados as $empleado)
              <option value="{{ $empleado->destinatario }}" data-sueldo="{{ $empleado->sueldo !== null ? number_format((float) $empleado->sueldo, 2, '.', '') : '' }}" @selected(old('destinatario') === $empleado->destinatario)>{{ $empleado->nombre }} · {{ $empleado->puesto }} · {{ $empleado->cuenta ? 'Con cuenta' : 'Sin cuenta' }}</option>
            @endforeach
          </select>
        </div>
        <div class="form-group">
          <label class="form-label" for="concepto">Concepto</label>
          <input class="form-control" id="concepto" name="concepto" maxlength="150" value="{{ old('concepto') }}" placeholder="Ej. Recibo de luz / Nómina quincenal" required>
        </div>
        <div class="form-group">
          <label class="form-label" for="monto">Monto</label>
          <input class="form-control" id="monto" name="monto" type="number" min="0.01" max="9999999999.99" step="0.01" value="{{ old('monto') }}" required>
        </div>
        <div class="form-group">
          <label class="form-label" for="fecha">Fecha de pago</label>
          <input class="form-control" id="fecha" name="fecha" type="date" max="{{ now()->toDateString() }}" value="{{ old('fecha', now()->toDateString()) }}" required>
        </div>
      </div>
      <div class="form-group mb-12">
        <label class="form-label" for="notas">Notas</label>
        <textarea class="form-control" id="notas" name="notas" maxlength="150">{{ old('notas') }}</textarea>
      </div>
      <button class="btn btn-primary" type="submit">Registrar egreso</button>
    </form>
  </section>

  <section class="card">
    <div class="card-title">Egresos registrados · {{ \Illuminate\Support\Carbon::createFromFormat('Y-m', $month)->translatedFormat('F Y') }}</div>
    <div class="table-wrap"><table><thead><tr><th>Fecha</th><th>Tipo</th><th>Concepto</th><th>Empleado</th><th>Monto</th></tr></thead><tbody>
      @forelse ($expenses as $expense)
        <tr>
          <td>{{ \Illuminate\Support\Carbon::parse($expense->fecha)->format('d/m/Y') }}</td>
          <td>{{ $categories[$expense->categoria] ?? $expense->categoria }}</td>
          <td>{{ $expense->concepto }}</td>
          <td>{{ $expense->empleado ?? '—' }}</td>
          <td>${{ number_format((float) $expense->monto, 2) }}</td>
        </tr>
      @empty
        <tr><td colspan="5" class="text-muted">No hay egresos registrados manualmente en este periodo.</td></tr>
      @endforelse
    </tbody></table></div>
  </section>
</div>

<section class="card mt-16">
  <div class="card-title">Compras incluidas automáticamente · ${{ number_format((float) $totals['compras'], 2) }}</div>
  <div class="table-wrap"><table><thead><tr><th>Fecha</th><th>Folio</th><th>Proveedor</th><th>Total</th></tr></thead><tbody>
    @forelse ($purchases as $purchase)
      <tr>
        <td>{{ \Illuminate\Support\Carbon::parse($purchase->fecha)->format('d/m/Y') }}</td>
        <td>{{ $purchase->folio ?: 'Compra #'.$purchase->id_compra }}</td>
        <td>{{ $purchase->proveedor ?? '—' }}</td>
        <td>${{ number_format((float) $purchase->total, 2) }}</td>
      </tr>
    @empty
      <tr><td colspan="4" class="text-muted">No hay compras en este periodo.</td></tr>
    @endforelse
  </tbody></table></div>
</section>
@endsection

@push('scripts')
<script>
  const categorySelect = document.getElementById('categoria');
  const employeeGroup = document.getElementById('empleado-grupo');
  const employeeSelect = document.getElementById('destinatario');
  const amountInput = document.getElementById('monto');
  function updateEmployeeField() {
    const isSalary = categorySelect.value === 'sueldo';
    employeeGroup.hidden = !isSalary;
    employeeSelect.required = isSalary;
    employeeSelect.disabled = !isSalary;
  }
  employeeSelect.addEventListener('change', () => {
    amountInput.value = employeeSelect.selectedOptions[0]?.dataset.sueldo || '';
  });
  categorySelect.addEventListener('change', updateEmployeeField);
  updateEmployeeField();
</script>
@endpush