@extends('layouts.app')

@section('title','Nueva Sección')

@section('content_header')
  <h1 class="text-center w-100">Nueva Sección</h1>
@endsection

@section('content')
<div class="container-xl">
  <div class="card card-outline card-primary">
    <div class="card-header d-flex justify-content-between align-items-center">
      <h3 class="card-title">Capturar sección</h3>
      <div class="btn-group">
        <a href="{{ route('secciones.index') }}" class="btn btn-outline-secondary btn-sm">
          <i class="fa fa-arrow-left"></i> Regresar
        </a>
      </div>
    </div>

    <div class="card-body">
      {{-- Mensajes de error globales --}}
      @if ($errors->any())
        <div class="alert alert-danger">
          <strong>Corrige los siguientes campos:</strong>
          <ul class="mb-0">
            @foreach ($errors->all() as $e)
              <li>{{ $e }}</li>
            @endforeach
          </ul>
        </div>
      @endif

      <form method="POST" action="{{ route('secciones.store') }}" class="row g-3">
        @csrf

        {{-- Siempre Michoacán --}}
        <input type="hidden" name="cve_ent" value="16">

        {{-- MUNICIPIO (obligatorio) --}}
        <div class="col-12 col-md-6">
          <label class="form-label mb-1">
            Municipio @if(($required['municipio'] ?? false))<span class="text-danger">*</span>@endif
          </label>
          <select name="municipio" id="municipio" class="form-select form-select-sm">
            <option value="">-- Selecciona municipio --</option>
            @foreach($municipios as $m)
              <option value="{{ $m->municipio }}"
                      data-cve="{{ $m->cve_mun }}"
                      {{ old('municipio')===$m->municipio ? 'selected' : '' }}>
                {{ $m->municipio }} ({{ $m->cve_mun }})
              </option>
            @endforeach
          </select>
          @error('municipio')<div class="text-danger small">{{ $message }}</div>@enderror
        </div>

        {{-- CVE MUN (BLOQUEADO; se autollenará) --}}
        <div class="col-6 col-md-2">
          <label class="form-label mb-1">
            CVE MUN @if(($required['cve_mun'] ?? false))<span class="text-danger">*</span>@endif
          </label>

          {{-- Visible para el usuario pero deshabilitado --}}
          <input type="text" id="cve_mun_show"
                 value="{{ old('cve_mun') }}"
                 class="form-control form-control-sm" placeholder="053" disabled>

          {{-- Hidden que viaja en el POST --}}
          <input type="hidden" name="cve_mun" id="cve_mun" value="{{ old('cve_mun') }}">

          @error('cve_mun')<div class="text-danger small">{{ $message }}</div>@enderror
        </div>

        {{-- SECCIÓN (obligatorio) --}}
        <div class="col-6 col-md-2">
          <label class="form-label mb-1">
            Sección @if(($required['seccion'] ?? false))<span class="text-danger">*</span>@endif
          </label>
          <input type="text" name="seccion" id="seccion"
                 value="{{ old('seccion') }}"
                 class="form-control form-control-sm" placeholder="p. ej. 2745">
          @error('seccion')<div class="text-danger small">{{ $message }}</div>@enderror
        </div>

        {{-- DISTRITO FEDERAL (opcional) --}}
        <div class="col-6 col-md-1">
          <label class="form-label mb-1">D. Fed.</label>
          <input type="number" name="distrito_federal" min="1" step="1"
                 value="{{ old('distrito_federal') }}"
                 class="form-control form-control-sm">
          @error('distrito_federal')<div class="text-danger small">{{ $message }}</div>@enderror
        </div>

        {{-- DISTRITO LOCAL (opcional) --}}
        <div class="col-6 col-md-1">
          <label class="form-label mb-1">D. Local</label>
          <input type="number" name="distrito_local" min="1" step="1"
                 value="{{ old('distrito_local') }}"
                 class="form-control form-control-sm">
          @error('distrito_local')<div class="text-danger small">{{ $message }}</div>@enderror
        </div>

        {{-- (Opcionales; deja comentado si no los usas)
        <div class="col-6 col-md-2">
          <label class="form-label mb-1">Lista nominal</label>
          <input type="number" name="lista_nominal" min="0" step="1"
                 value="{{ old('lista_nominal') }}"
                 class="form-control form-control-sm">
          @error('lista_nominal')<div class="text-danger small">{{ $message }}</div>@enderror
        </div>
        <div class="col-6 col-md-2">
          <label class="form-label mb-1">Centroide lat</label>
          <input type="number" name="centroid_lat" step="0.0000001"
                 value="{{ old('centroid_lat') }}"
                 class="form-control form-control-sm">
          @error('centroid_lat')<div class="text-danger small">{{ $message }}</div>@enderror
        </div>
        <div class="col-6 col-md-2">
          <label class="form-label mb-1">Centroide lng</label>
          <input type="number" name="centroid_lng" step="0.0000001"
                 value="{{ old('centroid_lng') }}"
                 class="form-control form-control-sm">
          @error('centroid_lng')<div class="text-danger small">{{ $message }}</div>@enderror
        </div>
        --}}

        <div class="col-12 d-flex gap-2">
          <button class="btn btn-primary">
            <i class="fa fa-save"></i> Guardar
          </button>
          <a href="{{ route('secciones.index') }}" class="btn btn-outline-secondary">
            Cancelar
          </a>
        </div>
      </form>
    </div>
  </div>
</div>
@endsection

@section('js')
<script>
(function(){
  const sel = document.getElementById('municipio');
  const cve = document.getElementById('cve_mun');          // hidden que se envía
  const cveShow = document.getElementById('cve_mun_show'); // visible disabled

  const pad3 = (s) => {
    s = (s || '').toString().replace(/\D/g,'').slice(0,3);
    return s ? s.padStart(3,'0') : '';
  };

  function syncCve(){
    const opt = sel.options[sel.selectedIndex];
    const v = pad3(opt?.dataset?.cve || '');
    cve.value = v;
    cveShow.value = v;
  }

  sel.addEventListener('change', syncCve);

  // Inicializa al cargar (si viene old('municipio') seleccionado)
  if (sel.value) syncCve();
})();
</script>

@if (session('status'))
<script>
Swal.fire({icon:'success', title:@json(session('status')), timer:2500, showConfirmButton:false});
</script>
@endif
@endsection
