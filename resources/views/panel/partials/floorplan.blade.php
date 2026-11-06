@php
  $zonas = $anaqueles->groupBy(fn ($anaquel) => str_contains($anaquel->codigo, '-')
      ? 'Zona '.strstr($anaquel->codigo, '-', true)
      : 'Otros');
@endphp
@push('estilos')
<style>
  .floorplan-wrap{display:grid;gap:14px}
  .floorplan-zone+.floorplan-zone{border-top:1px solid var(--border);padding-top:14px}
  .floorplan-zone h4{font-size:12px;color:var(--muted);margin-bottom:8px}
  .floorplan-shelves{display:grid;grid-template-columns:repeat(auto-fit,minmax(100px,1fr));gap:8px}
  .floorplan-block{min-height:84px;border:1px solid var(--border);border-radius:11px;background:var(--surf2);color:var(--text);display:flex;flex-direction:column;justify-content:center;align-items:center;gap:4px;text-align:center;text-decoration:none;transition:var(--transition)}
  .floorplan-block:hover,.floorplan-block.is-selected{background:var(--bluebg);border-color:#60A5FA;color:var(--blue2)}
  .floorplan-block.is-selected{outline:2px solid var(--accent);outline-offset:1px}
  .floorplan-block strong{font-size:18px}
  .floorplan-block span{font-size:12px;color:var(--muted)}
  .floorplan-block:hover span,.floorplan-block.is-selected span{color:inherit}
  .floorplan-empty{color:var(--muted);padding:16px 0}
  @media(max-width:600px){.floorplan-shelves{grid-template-columns:repeat(auto-fit,minmax(90px,1fr))}}
</style>
@endpush
<div class="floorplan-wrap" aria-label="Plano visual por zonas">
  @forelse ($zonas as $nombreZona => $anaquelesZona)
    <section class="floorplan-zone">
      <h4>{{ $nombreZona }}</h4>
      <div class="floorplan-shelves">
        @foreach ($anaquelesZona as $anaquel)
          @php($selected = isset($selectedShelfId) && (int) $selectedShelfId === (int) $anaquel->id_anaquel)
          <a
            class="floorplan-block {{ $selected ? 'is-selected' : '' }}"
            href="{{ route('localizador', ['anaquel' => $anaquel->id_anaquel]) }}"
            aria-label="Ver anaquel {{ $anaquel->codigo }}, {{ $conteos->get($anaquel->id_anaquel, 0) }} productos"
            @if ($selected) aria-current="location" @endif
          >
            <strong>{{ $anaquel->codigo }}</strong>
            <span>{{ $conteos->get($anaquel->id_anaquel, 0) }} prod.</span>
          </a>
        @endforeach
      </div>
    </section>
  @empty
    <p class="floorplan-empty">Registra anaqueles para completar el plano.</p>
  @endforelse
</div>
