@extends('layouts.app')

@section('title','Editar Sección')

@section('content_header')
  <h1 class="text-center w-100">Editar Sección</h1>
@endsection

@section('content')
<div class="container-xl">
  <div class="card card-outline card-primary">
    <div class="card-header d-flex justify-content-between align-items-center">
      <h3 class="card-title">Editar sección</h3>
      <div class="btn-group">
        <a href="{{ route('secciones.index') }}" class="btn btn-outline-secondary btn-sm">
          <i class="fa fa-arrow-left"></i> Regresar
        </a>
      </div>
    </div>

    <div class="card-body">
      {{-- Errores --}}
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

      <form method="POST" action="{{ route('secciones.update', $seccion->id ?? $seccion) }}" class="row g-3">
        @csrf @method('PUT')

        {{-- Siempre Michoacán --}}
        <input type="hidden" name="cve_ent" value="16">

        {{-- MUNICIPIO (bloqueado para mantener consistencia con cve_mun) --}}
        <div class="col-12 col-md-6">
          <label class="form-label mb-1">
            Municipio @if(($required['municipio'] ?? false))<span class="text-danger">*</span>@endif
          </label>
          <input type="text" class="form-control form-control-sm" value="{{ old('municipio', $seccion->municipio) }}" disabled>
          <input type="hidden" name="municipio" value="{{ old('municipio', $seccion->municipio) }}">
          <div class="form-text">Bloqueado en edición para coincidir con la clave municipal.</div>
          @error('municipio')<div class="text-danger small">{{ $message }}</div>@enderror
        </div>

        {{-- CVE MUN (BLOQUEADO) --}}
        <div class="col-6 col-md-2">
          <label class="form-label mb-1">
            CVE MUN @if(($required['cve_mun'] ?? false))<span class="text-danger">*</span>@endif
          </label>
          <input type="text" class="form-control form-control-sm" value="{{ old('cve_mun', $seccion->cve_mun) }}" disabled>
          <input type="hidden" name="cve_mun" value="{{ old('cve_mun', $seccion->cve_mun) }}">
          <div class="form-text">No editable.</div>
          @error('cve_mun')<div class="text-danger small">{{ $message }}</div>@enderror
        </div>

        {{-- SECCIÓN (editable) --}}
        <div class="col-6 col-md-2">
          <label class="form-label mb-1">
            Sección @if(($required['seccion'] ?? false))<span class="text-danger">*</span>@endif
          </label>
          <input type="text" name="seccion" id="seccion"
                 value="{{ old('seccion', $seccion->seccion) }}"
                 class="form-control form-control-sm" placeholder="p. ej. 2745">
          @error('seccion')<div class="text-danger small">{{ $message }}</div>@enderror
        </div>

        {{-- DISTRITO FEDERAL (opcional) --}}
        <div class="col-6 col-md-1">
          <label class="form-label mb-1">D. Fed.</label>
          <input type="number" name="distrito_federal" min="1" step="1"
                 value="{{ old('distrito_federal', $seccion->distrito_federal) }}"
                 class="form-control form-control-sm">
          @error('distrito_federal')<div class="text-danger small">{{ $message }}</div>@enderror
        </div>

        {{-- DISTRITO LOCAL (opcional) --}}
        <div class="col-6 col-md-1">
          <label class="form-label mb-1">D. Local</label>
          <input type="number" name="distrito_local" min="1" step="1"
                 value="{{ old('distrito_local', $seccion->distrito_local) }}"
                 class="form-control form-control-sm">
          @error('distrito_local')<div class="text-danger small">{{ $message }}</div>@enderror
        </div>

        {{-- (Opcionales: descomenta si los vas a usar)
        <div class="col-6 col-md-2">
          <label class="form-label mb-1">Lista nominal</label>
          <input type="number" name="lista_nominal" min="0" step="1"
                 value="{{ old('lista_nominal', $seccion->lista_nominal) }}"
                 class="form-control form-control-sm">
          @error('lista_nominal')<div class="text-danger small">{{ $message }}</div>@enderror
        </div>
        <div class="col-6 col-md-2">
          <label class="form-label mb-1">Centroide lat</label>
          <input type="number" name="centroid_lat" step="0.0000001"
                 value="{{ old('centroid_lat', $seccion->centroid_lat) }}"
                 class="form-control form-control-sm">
          @error('centroid_lat')<div class="text-danger small">{{ $message }}</div>@enderror
        </div>
        <div class="col-6 col-md-2">
          <label class="form-label mb-1">Centroide lng</label>
          <input type="number" name="centroid_lng" step="0.0000001"
                 value="{{ old('centroid_lng', $seccion->centroid_lng) }}"
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
@if (session('status'))
<script>
Swal.fire({icon:'success', title:@json(session('status')), timer:2500, showConfirmButton:false});
</script>
@endif
@endsection
