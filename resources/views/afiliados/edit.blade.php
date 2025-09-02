@extends('layouts.app')

@section('title','Editar afiliado')

@section('content_header')
  <h1 class="text-center w-100">Editar afiliado</h1>
@endsection

@section('content')
<div class="container-xl">
  <div class="card card-outline card-primary">
    <div class="card-body">

      {{-- Asterisco para obligatorios --}}
      <style>
        label.required::after { content:" *"; color:#dc3545; margin-left:.25rem; }
      </style>
      @php
        // Helpers que vienen del controller: $required y $fullNameField
        $req = fn($f) => !empty($required[$f] ?? false);
        $fullNameField = $fullNameField ?? 'nombre';

        // Sexo
        $sexoOld  = old('sexo', $afiliado->sexo ?? '');
        $sexoOpts = ['M'=>'Hombre','F'=>'Mujer','Otro'=>'Otro'];

        // Estatus
        $estatusOld = old('estatus', $afiliado->estatus ?? 'pendiente');
        $labelMap = ['pendiente'=>'Pendiente','validado'=>'Sí','descartado'=>'No'];
        $badgeMap = ['pendiente'=>'secondary','validado'=>'success','descartado'=>'danger'];
        $snMap    = ['pendiente'=>'Pendiente','validado'=>'SI','descartado'=>'NO'];

        // Municipios
        $munSel = old('municipio', $afiliado->municipio ?? '');

        // Fecha convencimiento preformateada
        $fechaConv = old('fecha_convencimiento');
        if (!$fechaConv && !empty($afiliado->fecha_convencimiento)) {
            try {
                $fechaConv = \Carbon\Carbon::parse($afiliado->fecha_convencimiento)->format('Y-m-d\TH:i');
            } catch (\Exception $e) { $fechaConv = ''; }
        }
      @endphp

      <form action="{{ route('afiliados.update', $afiliado->id) }}" method="POST" autocomplete="off">
        @csrf @method('PUT')

        <div class="row g-3">

          {{-- Datos personales --}}
          <div class="col-md-6">
            <label class="form-label {{ $req($fullNameField) ? 'required' : '' }}">Nombre completo</label>
            <input
              type="text"
              name="{{ $fullNameField }}"
              value="{{ old($fullNameField, $afiliado->{$fullNameField} ?? '') }}"
              class="form-control @error($fullNameField) is-invalid @enderror"
              {{ $req($fullNameField) ? 'required' : '' }}>
            @error($fullNameField)<div class="invalid-feedback">{{ $message }}</div>@enderror
          </div>

          <div class="col-md-2">
            <label class="form-label">Edad</label>
            <input type="number" name="edad" value="{{ old('edad',$afiliado->edad) }}" min="0" max="120"
                   class="form-control @error('edad') is-invalid @enderror">
            @error('edad')<div class="invalid-feedback">{{ $message }}</div>@enderror
          </div>

          <div class="col-md-4">
            <label class="form-label">Sexo</label>
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
            <input type="text" name="telefono" value="{{ old('telefono',$afiliado->telefono) }}"
                   class="form-control @error('telefono') is-invalid @enderror"
                   {{ $req('telefono') ? 'required' : '' }}>
            @error('telefono')<div class="invalid-feedback">{{ $message }}</div>@enderror
          </div>

          <div class="col-md-4">
            <label class="form-label {{ $req('email') ? 'required' : '' }}">Email</label>
            <input type="email" name="email" value="{{ old('email',$afiliado->email) }}"
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
                        {{ $munSel===$m->municipio?'selected':'' }}>
                  {{ $m->municipio }}
                </option>
              @endforeach
            </select>
            @error('municipio')<div class="invalid-feedback">{{ $message }}</div>@enderror
          </div>

          <div class="col-md-2">
            <label class="form-label {{ $req('cve_mun') ? 'required' : '' }}">CVE mun (3)</label>
            <input type="text" name="cve_mun" id="txtCveMun"
                   value="{{ old('cve_mun', str_pad((string)($afiliado->cve_mun ?? ''),3,'0',STR_PAD_LEFT)) }}"
                   maxlength="3" readonly
                   class="form-control @error('cve_mun') is-invalid @enderror"
                   {{ $req('cve_mun') ? 'required' : '' }}>
            @error('cve_mun')<div class="invalid-feedback">{{ $message }}</div>@enderror
          </div>

          <div class="col-md-2">
            <label class="form-label {{ $req('seccion') ? 'required' : '' }}">Sección</label>
            <input type="text" name="seccion" value="{{ old('seccion',$afiliado->seccion) }}" list="dlSecciones"
                   class="form-control @error('seccion') is-invalid @enderror"
                   {{ $req('seccion') ? 'required' : '' }}>
            <datalist id="dlSecciones">
              @foreach($secciones as $sec)
                <option value="{{ $sec }}">{{ $sec }}</option>
              @endforeach
            </datalist>
            @error('seccion')<div class="invalid-feedback">{{ $message }}</div>@enderror
          </div>

          <div class="col-md-2">
            <label class="form-label {{ $req('distrito_local') ? 'required' : '' }}">Distrito local</label>
            <input type="number" name="distrito_local" value="{{ old('distrito_local',$afiliado->distrito_local) }}"
                   min="1" max="100" step="1" inputmode="numeric" pattern="[0-9]*"
                   class="form-control @error('distrito_local') is-invalid @enderror"
                   {{ $req('distrito_local') ? 'required' : '' }}>
            @error('distrito_local')<div class="invalid-feedback">{{ $message }}</div>@enderror
          </div>

          <div class="col-md-2">
            <label class="form-label {{ $req('distrito_federal') ? 'required' : '' }}">Distrito federal</label>
            <input type="number" name="distrito_federal" value="{{ old('distrito_federal',$afiliado->distrito_federal) }}"
                   min="1" max="100" step="1" inputmode="numeric" pattern="[0-9]*"
                   class="form-control @error('distrito_federal') is-invalid @enderror"
                   {{ $req('distrito_federal') ? 'required' : '' }}>
            @error('distrito_federal')<div class="invalid-feedback">{{ $message }}</div>@enderror
          </div>

          <div class="col-md-4">
            <label class="form-label {{ $req('localidad') ? 'required' : '' }}">Localidad</label>
            <input type="text" name="localidad" value="{{ old('localidad',$afiliado->localidad) }}"
                   class="form-control @error('localidad') is-invalid @enderror"
                   {{ $req('localidad') ? 'required' : '' }}>
            @error('localidad')<div class="invalid-feedback">{{ $message }}</div>@enderror
          </div>

          <div class="col-md-4">
            <label class="form-label {{ $req('colonia') ? 'required' : '' }}">Colonia</label>
            <input type="text" name="colonia" value="{{ old('colonia',$afiliado->colonia) }}"
                   class="form-control @error('colonia') is-invalid @enderror"
                   {{ $req('colonia') ? 'required' : '' }}>
            @error('colonia')<div class="invalid-feedback">{{ $message }}</div>@enderror
          </div>

          {{-- Perfil / observaciones (no obligatorios si no están en reglas) --}}
          <div class="col-md-12">
            <label class="form-label {{ $req('perfil') ? 'required' : '' }}">Perfil</label>
            <textarea name="perfil" rows="2"
                      class="form-control @error('perfil') is-invalid @enderror"
                      {{ $req('perfil') ? 'required' : '' }}>{{ old('perfil',$afiliado->perfil) }}</textarea>
            @error('perfil')<div class="invalid-feedback">{{ $message }}</div>@enderror
          </div>

          <div class="col-md-12">
            <label class="form-label {{ $req('observaciones') ? 'required' : '' }}">Observaciones</label>
            <textarea name="observaciones" rows="2"
                      class="form-control @error('observaciones') is-invalid @enderror"
                      {{ $req('observaciones') ? 'required' : '' }}>{{ old('observaciones',$afiliado->observaciones) }}</textarea>
            @error('observaciones')<div class="invalid-feedback">{{ $message }}</div>@enderror
          </div>

          {{-- Estado --}}
          <div class="col-md-3">
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
                   value="{{ $fechaConv }}"
                   class="form-control @error('fecha_convencimiento') is-invalid @enderror">
            @error('fecha_convencimiento')<div class="invalid-feedback">{{ $message }}</div>@enderror
          </div>

        </div>

        <div class="mt-4 d-flex gap-2">
          <a href="{{ route('afiliados.index') }}" class="btn btn-secondary">Cancelar</a>
          <button class="btn btn-primary">Actualizar</button>
        </div>
      </form>
    </div>
  </div>
</div>
@endsection

@section('js')
<script>
function pad3(v){ v = (v||'').toString().trim(); return v ? v.padStart(3,'0') : ''; }

document.addEventListener('DOMContentLoaded', ()=>{
  const sel = document.getElementById('slMunicipio');
  const cve = document.getElementById('txtCveMun');

  function syncCve(){
    const opt = sel?.selectedOptions && sel.selectedOptions[0];
    const fromData = opt ? opt.getAttribute('data-cve') : '';
    if (cve) cve.value = pad3(fromData);
  }

  if (sel) {
    syncCve();
    sel.addEventListener('change', syncCve);
  }
});
</script>
@endsection
