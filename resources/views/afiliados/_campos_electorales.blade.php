@php
  $registroElectoral = $afiliado ?? null;
  $tipoVinculoSeleccionado = old('tipo_vinculo', optional($registroElectoral)->tipo_vinculo);
@endphp

<div class="col-md-4">
  <label class="form-label" for="clave_elector">Clave de elector</label>
  <input id="clave_elector" type="text" name="clave_elector" maxlength="30" autocomplete="off"
         value="{{ old('clave_elector', optional($registroElectoral)->clave_elector) }}"
         class="form-control text-uppercase @error('clave_elector') is-invalid @enderror">
  <div class="form-text">Opcional. Si se captura, debe ser única.</div>
  @error('clave_elector')<div class="invalid-feedback">{{ $message }}</div>@enderror
</div>

<div class="col-12">
  <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-2">
    <div>
      <label class="form-label mb-0">Indicador</label>
      <div class="form-text">Opcional. Para cambiarlo primero quita la selección actual.</div>
    </div>
    <button type="button" class="btn btn-sm btn-outline-secondary" data-clear-vinculo>
      <i class="fa fa-times"></i> Quitar selección
    </button>
  </div>

  <div class="afiliado-vinculos" role="radiogroup" aria-label="Indicador de vinculación">
    @foreach (\App\Models\Afiliado::TIPOS_VINCULO as $value => $label)
      <label class="afiliado-vinculo" data-vinculo-option>
        <input type="radio" name="tipo_vinculo" value="{{ $value }}" data-vinculo-radio
               {{ $tipoVinculoSeleccionado === $value ? 'checked' : '' }}>
        <span>{{ $label }}</span>
      </label>
    @endforeach

    <div class="afiliado-mov-number">
      <label for="numero_mov" class="mb-0 text-nowrap">MOV #</label>
      <input id="numero_mov" type="text" name="numero_mov" maxlength="50"
             value="{{ old('numero_mov', optional($registroElectoral)->numero_mov) }}"
             class="form-control form-control-sm @error('numero_mov') is-invalid @enderror"
             placeholder="Número opcional">
    </div>
  </div>
</div>

@push('styles')
<style>
.afiliado-vinculos { display:flex; flex-wrap:wrap; align-items:center; gap:12px; padding:14px; border:1px solid #d9e1ea; border-radius:10px; background:#f8fafc; }
.afiliado-vinculo { display:inline-flex; align-items:center; gap:8px; min-width:110px; margin:0; padding:10px 14px; border:1px solid #cbd5e1; border-radius:999px; background:#fff; cursor:pointer; transition:.15s ease; }
.afiliado-vinculo.is-selected { color:#fff; border-color:#8b1e2d; background:#8b1e2d; box-shadow:0 4px 12px rgba(139,30,45,.18); }
.afiliado-vinculo.is-locked { opacity:.42; cursor:not-allowed; }
.afiliado-vinculo input { width:18px; height:18px; margin:0; accent-color:#8b1e2d; }
.afiliado-mov-number { display:flex; align-items:center; gap:8px; min-width:210px; }
@media (max-width:767px) { .afiliado-vinculo { flex:1 1 100%; } .afiliado-mov-number { width:100%; } }
</style>
@endpush

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
  const radios = Array.from(document.querySelectorAll('[data-vinculo-radio]'));
  const clearButton = document.querySelector('[data-clear-vinculo]');
  const movNumber = document.getElementById('numero_mov');
  if (!radios.length || !clearButton || !movNumber) return;

  function syncVinculos() {
    const selected = radios.find(radio => radio.checked);
    radios.forEach(function (radio) {
      const locked = Boolean(selected && radio !== selected);
      radio.disabled = locked;
      const option = radio.closest('[data-vinculo-option]');
      if (option) {
        option.classList.toggle('is-selected', radio.checked);
        option.classList.toggle('is-locked', locked);
      }
    });
    clearButton.disabled = !selected;
    movNumber.disabled = !selected || selected.value !== 'mov';
  }

  radios.forEach(radio => radio.addEventListener('change', syncVinculos));
  clearButton.addEventListener('click', function () {
    radios.forEach(function (radio) {
      radio.checked = false;
      radio.disabled = false;
    });
    syncVinculos();
  });
  syncVinculos();
});
</script>
@endpush
