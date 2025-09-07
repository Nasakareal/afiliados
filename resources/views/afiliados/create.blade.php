@extends('layouts.app')

@section('title','Nuevo afiliado')

@section('content_header')
  <h1 class="text-center w-100">Crear afiliado</h1>
@endsection

@extends('layouts.app')

@section('title','Nuevo afiliado')

@section('content_header')
  <h1 class="text-center w-100">Crear afiliado</h1>
@endsection

@section('content')
<div class="container-xl">
  <div class="card card-outline card-primary">
    <div class="card-body">

      {{-- Asterisco para obligatorios --}}
      <style>
        label.required::after { content:" *"; color:#dc3545; margin-left:.25rem; }
        .form-control[readonly] { background-color:#f8f9fa; }
      </style>
      @php
        $req = fn($f) => !empty($required[$f] ?? false);
        $fullNameField = $fullNameField ?? 'nombre';
      @endphp

      <form action="{{ route('afiliados.store') }}" method="POST" autocomplete="off">
        @csrf

        <div class="row g-3">

          {{-- Datos personales --}}
          <div class="col-md-6">
            <label class="form-label {{ $req($fullNameField) ? 'required' : '' }}">Nombre completo</label>
            <input
              type="text"
              name="{{ $fullNameField }}"
              value="{{ old($fullNameField) }}"
              class="form-control @error($fullNameField) is-invalid @enderror"
              {{ $req($fullNameField) ? 'required' : '' }}
              placeholder="EJEMPLO: MARIO DANTE BAUTISTA REBOLLAR">
            @error($fullNameField)<div class="invalid-feedback">{{ $message }}</div>@enderror
          </div>

          <div class="col-md-2">
            <label class="form-label">Edad</label>
            <input type="number" name="edad" value="{{ old('edad') }}" min="0" max="120"
                   class="form-control @error('edad') is-invalid @enderror">
            @error('edad')<div class="invalid-feedback">{{ $message }}</div>@enderror
          </div>

          <div class="col-md-4">
            <label class="form-label">Sexo</label>
            @php
              $sexoOld  = old('sexo','');
              $sexoOpts = ['M'=>'Hombre','F'=>'Mujer','Otro'=>'Otro'];
            @endphp
            <select name="sexo" class="form-select @error('sexo') is-invalid @enderror">
              <option value="" {{ $sexoOld===''?'selected':'' }}>Seleccione…</option>
              @foreach($sexoOpts as $val => $label)
                <option value="{{ $val }}" {{ $sexoOld===$val ? 'selected' : '' }}>{{ $label }}</option>
              @endforeach
            </select>
            @error('sexo')<div class="invalid-feedback">{{ $message }}</div>@enderror
          </div>

          {{-- Contacto --}}
          <div class="col-md-4">
            <label class="form-label {{ $req('telefono') ? 'required' : '' }}">Teléfono</label>
            <input type="text" name="telefono" value="{{ old('telefono') }}"
                   class="form-control @error('telefono') is-invalid @enderror"
                   {{ $req('telefono') ? 'required' : '' }}>
            @error('telefono')<div class="invalid-feedback">{{ $message }}</div>@enderror
          </div>

          <div class="col-md-4">
            <label class="form-label {{ $req('email') ? 'required' : '' }}">Email</label>
            <input type="email" name="email" value="{{ old('email') }}"
                   class="form-control @error('email') is-invalid @enderror"
                   {{ $req('email') ? 'required' : '' }}>
            @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
          </div>

          {{-- Ubicación --}}
          <div class="col-md-4">
            <label class="form-label {{ $req('municipio') ? 'required' : '' }}">Municipio</label>
            <select name="municipio" id="slMunicipio"
                    class="form-select @error('municipio') is-invalid @enderror"
                    {{ $req('municipio') ? 'required' : '' }}>
              <option value="">-- Selecciona --</option>
              @foreach($municipios as $m)
                <option value="{{ $m->municipio }}"
                        data-cve="{{ str_pad($m->cve_mun,3,'0',STR_PAD_LEFT) }}"
                        {{ old('municipio')===$m->municipio?'selected':'' }}>
                  {{ $m->municipio }}
                </option>
              @endforeach
            </select>
            @error('municipio')<div class="invalid-feedback">{{ $message }}</div>@enderror
          </div>

          <div class="col-md-2">
            <label class="form-label {{ $req('cve_mun') ? 'required' : '' }}">CVE mun (3)</label>
            {{-- readonly: se ve y SÍ se envía al backend --}}
            <input type="text" name="cve_mun" id="txtCveMun"
                   value="{{ old('cve_mun') }}" maxlength="3" readonly
                   class="form-control @error('cve_mun') is-invalid @enderror"
                   {{ $req('cve_mun') ? 'required' : '' }}
                   placeholder="053">
            @error('cve_mun')<div class="invalid-feedback">{{ $message }}</div>@enderror
          </div>

          <div class="col-md-2">
            <label class="form-label {{ $req('seccion') ? 'required' : '' }}">Sección</label>
            <input type="text" name="seccion" value="{{ old('seccion') }}" list="dlSecciones"
                   class="form-control @error('seccion') is-invalid @enderror"
                   {{ $req('seccion') ? 'required' : '' }}
                   placeholder="Ej. 1234">
            <datalist id="dlSecciones">
              @if(isset($secciones))
                @foreach($secciones as $sec)
                  <option value="{{ $sec }}">{{ $sec }}</option>
                @endforeach
              @endif
            </datalist>
            @error('seccion')<div class="invalid-feedback">{{ $message }}</div>@enderror
          </div>

          <div class="col-md-2">
            <label class="form-label {{ $req('distrito_local') ? 'required' : '' }}">Distrito local</label>
            <input type="number" name="distrito_local" value="{{ old('distrito_local') }}"
                   min="1" max="100" step="1" inputmode="numeric" pattern="[0-9]*"
                   class="form-control @error('distrito_local') is-invalid @enderror"
                   {{ $req('distrito_local') ? 'required' : '' }} readonly>
            @error('distrito_local')<div class="invalid-feedback">{{ $message }}</div>@enderror
          </div>

          <div class="col-md-2">
            <label class="form-label {{ $req('distrito_federal') ? 'required' : '' }}">Distrito federal</label>
            <input type="number" name="distrito_federal" value="{{ old('distrito_federal') }}"
                   min="1" max="100" step="1" inputmode="numeric" pattern="[0-9]*"
                   class="form-control @error('distrito_federal') is-invalid @enderror"
                   {{ $req('distrito_federal') ? 'required' : '' }} readonly>
            @error('distrito_federal')<div class="invalid-feedback">{{ $message }}</div>@enderror
          </div>

          <div class="col-md-4">
            <label class="form-label {{ $req('localidad') ? 'required' : '' }}">Localidad</label>
            <input type="text" name="localidad" value="{{ old('localidad') }}"
                   class="form-control @error('localidad') is-invalid @enderror"
                   {{ $req('localidad') ? 'required' : '' }}>
            @error('localidad')<div class="invalid-feedback">{{ $message }}</div>@enderror
          </div>

          <div class="col-md-4">
            <label class="form-label {{ $req('colonia') ? 'required' : '' }}">Colonia</label>
            <input type="text" name="colonia" value="{{ old('colonia') }}"
                   class="form-control @error('colonia') is-invalid @enderror"
                   {{ $req('colonia') ? 'required' : '' }}>
            @error('colonia')<div class="invalid-feedback">{{ $message }}</div>@enderror
          </div>

          {{-- Perfil / observaciones --}}
          <div class="col-md-12">
            <label class="form-label {{ $req('perfil') ? 'required' : '' }}">Referente</label>
            <textarea name="perfil" rows="2"
                      class="form-control @error('perfil') is-invalid @enderror"
                      {{ $req('perfil') ? 'required' : '' }}>{{ old('perfil') }}</textarea>
            @error('perfil')<div class="invalid-feedback">{{ $message }}</div>@enderror
          </div>

          <div class="col-md-12">
            <label class="form-label {{ $req('observaciones') ? 'required' : '' }}">Observaciones</label>
            <textarea name="observaciones" rows="2"
                      class="form-control @error('observaciones') is-invalid @enderror"
                      {{ $req('observaciones') ? 'required' : '' }}>{{ old('observaciones') }}</textarea>
            @error('observaciones')<div class="invalid-feedback">{{ $message }}</div>@enderror
          </div>

          {{-- Estado --}}
          <div class="col-md-3">
            @php
              $estatusOld = old('estatus','pendiente');
              $labelMap = ['pendiente'=>'Pendiente','validado'=>'Sí','descartado'=>'No'];
              $badgeMap = ['pendiente'=>'secondary','validado'=>'success','descartado'=>'danger'];
              $snMap    = ['pendiente'=>'Pendiente','validado'=>'SI','descartado'=>'NO'];
            @endphp

            <label class="form-label {{ $req('estatus') ? 'required' : '' }}">Afiliado</label>
            <select name="estatus" class="form-select @error('estatus') is-invalid @enderror"
                    {{ $req('estatus') ? 'required' : '' }}>
              <option value="pendiente"  {{ $estatusOld==='pendiente'?'selected':'' }}>{{ $labelMap['pendiente'] }}</option>
              <option value="validado"   {{ $estatusOld==='validado'?'selected':'' }}>{{ $labelMap['validado'] }}</option>
              <option value="descartado" {{ $estatusOld==='descartado'?'selected':'' }}>{{ $labelMap['descartado'] }}</option>
            </select>
            @error('estatus')<div class="invalid-feedback">{{ $message }}</div>@enderror

            <small class="form-text mt-1 d-block">
              <span class="badge bg-{{ $badgeMap[$estatusOld] }}">{{ $snMap[$estatusOld] }}</span>
            </small>
          </div>

          <div class="col-md-4">
            <label class="form-label">Fecha de convencimiento</label>
            <input type="datetime-local" name="fecha_convencimiento"
                   value="{{ old('fecha_convencimiento') }}"
                   class="form-control @error('fecha_convencimiento') is-invalid @enderror">
            @error('fecha_convencimiento')<div class="invalid-feedback">{{ $message }}</div>@enderror
          </div>

        </div>

        <div class="mt-4 d-flex gap-2">
          <a href="{{ route('afiliados.index') }}" class="btn btn-secondary">Cancelar</a>
          <button class="btn btn-primary">Guardar</button>
        </div>
      </form>
    </div>
  </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function(){
  const $sec = document.querySelector('input[name="seccion"], select[name="seccion"]');
  if (!$sec) return;

  const $cve = document.querySelector('#txtCveMun');
  const $mun = document.querySelector('#slMunicipio');
  const $dl  = document.querySelector('input[name="distrito_local"]');
  const $df  = document.querySelector('input[name="distrito_federal"]');

  [$cve,$dl,$df].forEach(el=>{
    if(!el) return;
    el.readOnly = true;
    ['keydown','paste','drop'].forEach(evt=>{
      el.addEventListener(evt, e=>{ if(el.readOnly) e.preventDefault(); });
    });
  });

  function syncCveFromMunicipio(){
    if(!$mun || !$cve) return;
    const opt = $mun.options[$mun.selectedIndex];
    const cve = (opt && opt.dataset && opt.dataset.cve) ? String(opt.dataset.cve).padStart(3,'0') : '';
    $cve.value = cve;
  }
  if ($mun) {
    $mun.addEventListener('change', () => { syncCveFromMunicipio(); if ($sec.value) debouncedLookup(); });
    syncCveFromMunicipio();
  }

  const endpoint = "{{ route('secciones.lookup') }}";
  const pad3   = v => (v==null ? '' : String(v).trim().padStart(3,'0'));
  const squish = v => String(v||'').trim().replace(/\s+/g,' ');
  let t=null;

  async function fetchLookup(params){
    const url = endpoint + '?' + (new URLSearchParams(params)).toString();
    const r = await fetch(url, { headers: { 'X-Requested-With':'XMLHttpRequest', 'Accept':'application/json' } });
    if (!r.ok) throw r;
    return r.json();
  }

  function fillFields(j, forceCanon){
    if ($dl) $dl.value = j.distrito_local ?? '';
    if ($df) $df.value = j.distrito_federal ?? '';
    if ($cve && (forceCanon || !squish($cve.value)) && j.cve_mun) $cve.value = j.cve_mun;
    if ($mun && (forceCanon || !squish($mun.value)) && j.municipio) {
      const val = j.municipio;
      const opt = Array.from($mun.options).find(o=>o.value===val);
      if (opt) $mun.value = val, syncCveFromMunicipio();
    }
    [$dl,$df,$cve,$mun].forEach(el=>{
      if(!el) return;
      el.classList.remove('is-invalid');
      el.classList.add('is-valid');
      setTimeout(()=>el.classList.remove('is-valid'), 600);
    });
  }

  async function lookup(){
    const seccion = squish($sec.value);
    if (!seccion) return;

    const strict = { seccion };
    if ($cve && squish($cve.value)) strict.cve_mun = pad3($cve.value);
    else if ($mun && squish($mun.value)) strict.municipio = squish($mun.value);

    try { const j = await fetchLookup(strict); fillFields(j,false); return; }
    catch(e){  }

    try { const j = await fetchLookup({ seccion }); fillFields(j,true); }
    catch(e){
      if ($dl) $dl.value = '';
      if ($df) $df.value = '';
      [$dl,$df,$cve,$mun].forEach(el=>{
        if(!el) return;
        el.classList.remove('is-valid');
        el.classList.add('is-invalid');
        setTimeout(()=>el.classList.remove('is-invalid'), 800);
      });
    }
  }

  function debouncedLookup(){ if (t) clearTimeout(t); t = setTimeout(lookup, 200); }

  $sec.addEventListener('input', debouncedLookup);
  ['change','blur'].forEach(ev => $sec.addEventListener(ev, lookup));

  if (squish($sec.value)) lookup();
});
</script>
@endpush


<script>
document.addEventListener('DOMContentLoaded', function(){
  const $sec = document.querySelector('input[name="seccion"], select[name="seccion"]');
  if (!$sec) return;

  const $cve = document.querySelector('input[name="cve_mun"]');
  const $mun = document.querySelector('input[name="municipio"], select[name="municipio"]');
  const $dl  = document.querySelector('input[name="distrito_local"], select[name="distrito_local"]');
  const $df  = document.querySelector('input[name="distrito_federal"], select[name="distrito_federal"]');

  const endpoint = "{{ route('secciones.lookup') }}";
  const pad3   = v => (v==null ? '' : String(v).trim().padStart(3,'0'));
  const squish = v => String(v||'').trim().replace(/\s+/g,' ');

  let t=null;

  async function fetchLookup(params){
    const url = endpoint + '?' + (new URLSearchParams(params)).toString();
    const r = await fetch(url, { headers: { 'X-Requested-With':'XMLHttpRequest', 'Accept':'application/json' } });
    if (!r.ok) throw r;
    return r.json();
  }

  function fillFields(j, forceCanon){
    if ($dl) $dl.value = j.distrito_local ?? '';
    if ($df) $df.value = j.distrito_federal ?? '';
    if ($cve && (forceCanon || !squish($cve.value)) && j.cve_mun) $cve.value = j.cve_mun;
    if ($mun && (forceCanon || !squish($mun.value)) && j.municipio) $mun.value = j.municipio;

    [$dl,$df,$cve,$mun].forEach(el=>{
      if(!el) return;
      el.classList.remove('is-invalid');
      el.classList.add('is-valid');
      setTimeout(()=>el.classList.remove('is-valid'), 600);
    });
  }

  async function lookup(){
    const seccion = squish($sec.value);
    if (!seccion) return;

    const strict = { seccion };
    if ($cve && squish($cve.value)) strict.cve_mun = pad3($cve.value);
    else if ($mun && squish($mun.value)) strict.municipio = squish($mun.value);

    try {
      const j = await fetchLookup(strict);
      fillFields(j, false);
      return;
    } catch(e){ }

    try {
      const j = await fetchLookup({ seccion });
      fillFields(j, true);
    } catch(e){
      if ($dl) $dl.value = '';
      if ($df) $df.value = '';
      [$dl,$df,$cve,$mun].forEach(el=>{
        if(!el) return;
        el.classList.remove('is-valid');
        el.classList.add('is-invalid');
        setTimeout(()=>el.classList.remove('is-invalid'), 800);
      });
    }
  }

  function debounced(){ if (t) clearTimeout(t); t = setTimeout(lookup, 200); }

  $sec.addEventListener('input', debounced);
  ['change','blur'].forEach(ev => $sec.addEventListener(ev, lookup));

  [$cve,$mun].forEach(el=>{
    if(!el) return;
    el.addEventListener('input', debounced);
    ['change','blur'].forEach(ev => el.addEventListener(ev, lookup));
  });

  if (squish($sec.value)) lookup();
});
</script>

