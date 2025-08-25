@extends('layouts.app')

@section('title','Nuevo afiliado')

@section('content_header')
  <h1 class="text-center w-100">Crear afiliado</h1>
@endsection

@section('content')
<div class="container-xl">
  <div class="card card-outline card-primary">
    <div class="card-body">
      <form action="{{ route('afiliados.store') }}" method="POST" autocomplete="off">
        @csrf

        <div class="row g-3">

          {{-- Datos personales --}}
          <div class="col-md-4">
            <label class="form-label">Nombre</label>
            <input type="text" name="nombre" value="{{ old('nombre') }}" class="form-control @error('nombre') is-invalid @enderror" required>
            @error('nombre')<div class="invalid-feedback">{{ $message }}</div>@enderror
          </div>
          <div class="col-md-4">
            <label class="form-label">Apellido paterno</label>
            <input type="text" name="apellido_paterno" value="{{ old('apellido_paterno') }}" class="form-control @error('apellido_paterno') is-invalid @enderror">
            @error('apellido_paterno')<div class="invalid-feedback">{{ $message }}</div>@enderror
          </div>
          <div class="col-md-4">
            <label class="form-label">Apellido materno</label>
            <input type="text" name="apellido_materno" value="{{ old('apellido_materno') }}" class="form-control @error('apellido_materno') is-invalid @enderror">
            @error('apellido_materno')<div class="invalid-feedback">{{ $message }}</div>@enderror
          </div>

          <div class="col-md-2">
            <label class="form-label">Edad</label>
            <input type="number" name="edad" value="{{ old('edad') }}" min="0" max="120" class="form-control @error('edad') is-invalid @enderror">
            @error('edad')<div class="invalid-feedback">{{ $message }}</div>@enderror
          </div>
          <div class="col-md-2">
            <label class="form-label">Sexo</label>
            <select name="sexo" class="form-select @error('sexo') is-invalid @enderror">
              <option value="">--</option>
              <option value="M" {{ old('sexo')==='M'?'selected':'' }}>M</option>
              <option value="F" {{ old('sexo')==='F'?'selected':'' }}>F</option>
              <option value="Otro" {{ old('sexo')==='Otro'?'selected':'' }}>Otro</option>
            </select>
            @error('sexo')<div class="invalid-feedback">{{ $message }}</div>@enderror
          </div>

          {{-- Contacto --}}
          <div class="col-md-4">
            <label class="form-label">Teléfono</label>
            <input type="text" name="telefono" value="{{ old('telefono') }}" class="form-control @error('telefono') is-invalid @enderror">
            @error('telefono')<div class="invalid-feedback">{{ $message }}</div>@enderror
          </div>
          <div class="col-md-4">
            <label class="form-label">Email</label>
            <input type="email" name="email" value="{{ old('email') }}" class="form-control @error('email') is-invalid @enderror">
            @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
          </div>

          {{-- Ubicación --}}
          <div class="col-md-4">
            <label class="form-label">Municipio</label>
            <select name="municipio" id="slMunicipio"
                    class="form-select @error('municipio') is-invalid @enderror" required>
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
            <label class="form-label">CVE mun (3)</label>
            {{-- readonly: se ve y SÍ se envía al backend --}}
            <input type="text" name="cve_mun" id="txtCveMun"
                   value="{{ old('cve_mun') }}" maxlength="3" readonly
                   class="form-control @error('cve_mun') is-invalid @enderror" placeholder="053">
            @error('cve_mun')<div class="invalid-feedback">{{ $message }}</div>@enderror
          </div>

          <div class="col-md-2">
            <label class="form-label">Sección</label>
            <input type="text" name="seccion" value="{{ old('seccion') }}" list="dlSecciones"
                   class="form-control @error('seccion') is-invalid @enderror" placeholder="Ej. 1234">
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
            <label class="form-label">Distrito local</label>
            <input type="number" name="distrito_local" value="{{ old('distrito_local') }}"
                   min="1" max="100" step="1" inputmode="numeric" pattern="[0-9]*"
                   class="form-control @error('distrito_local') is-invalid @enderror">
            @error('distrito_local')<div class="invalid-feedback">{{ $message }}</div>@enderror
          </div>
          <div class="col-md-2">
            <label class="form-label">Distrito federal</label>
            <input type="number" name="distrito_federal" value="{{ old('distrito_federal') }}"
                   min="1" max="100" step="1" inputmode="numeric" pattern="[0-9]*"
                   class="form-control @error('distrito_federal') is-invalid @enderror">
            @error('distrito_federal')<div class="invalid-feedback">{{ $message }}</div>@enderror
          </div>

          <div class="col-md-4">
            <label class="form-label">Localidad</label>
            <input type="text" name="localidad" value="{{ old('localidad') }}" class="form-control @error('localidad') is-invalid @enderror">
            @error('localidad')<div class="invalid-feedback">{{ $message }}</div>@enderror
          </div>
          <div class="col-md-4">
            <label class="form-label">Colonia</label>
            <input type="text" name="colonia" value="{{ old('colonia') }}" class="form-control @error('colonia') is-invalid @enderror">
            @error('colonia')<div class="invalid-feedback">{{ $message }}</div>@enderror
          </div>
          <div class="col-md-4">
            <label class="form-label">Calle</label>
            <input type="text" name="calle" value="{{ old('calle') }}" class="form-control @error('calle') is-invalid @enderror">
            @error('calle')<div class="invalid-feedback">{{ $message }}</div>@enderror
          </div>

          <div class="col-md-2">
            <label class="form-label">No. ext</label>
            <input type="text" name="numero_ext" value="{{ old('numero_ext') }}" class="form-control @error('numero_ext') is-invalid @enderror">
            @error('numero_ext')<div class="invalid-feedback">{{ $message }}</div>@enderror
          </div>
          <div class="col-md-2">
            <label class="form-label">No. int</label>
            <input type="text" name="numero_int" value="{{ old('numero_int') }}" class="form-control @error('numero_int') is-invalid @enderror">
            @error('numero_int')<div class="invalid-feedback">{{ $message }}</div>@enderror
          </div>
          <div class="col-md-2">
            <label class="form-label">CP</label>
            <input type="text" name="cp" value="{{ old('cp') }}" class="form-control @error('cp') is-invalid @enderror">
            @error('cp')<div class="invalid-feedback">{{ $message }}</div>@enderror
          </div>

          <div class="col-md-2">
            <label class="form-label">Lat</label>
            <input type="text" name="lat" value="{{ old('lat') }}" class="form-control @error('lat') is-invalid @enderror">
            @error('lat')<div class="invalid-feedback">{{ $message }}</div>@enderror
          </div>
          <div class="col-md-2">
            <label class="form-label">Lng</label>
            <input type="text" name="lng" value="{{ old('lng') }}" class="form-control @error('lng') is-invalid @enderror">
            @error('lng')<div class="invalid-feedback">{{ $message }}</div>@enderror
          </div>

          {{-- Perfil / observaciones --}}
          <div class="col-md-12">
            <label class="form-label">Perfil</label>
            <textarea name="perfil" rows="2" class="form-control @error('perfil') is-invalid @enderror">{{ old('perfil') }}</textarea>
            @error('perfil')<div class="invalid-feedback">{{ $message }}</div>@enderror
          </div>
          <div class="col-md-12">
            <label class="form-label">Observaciones</label>
            <textarea name="observaciones" rows="2" class="form-control @error('observaciones') is-invalid @enderror">{{ old('observaciones') }}</textarea>
            @error('observaciones')<div class="invalid-feedback">{{ $message }}</div>@enderror
          </div>

          {{-- Estado --}}
          <div class="col-md-3">
            <label class="form-label">Estatus</label>
            @php $estatusOld = old('estatus','pendiente'); @endphp
            <select name="estatus" class="form-select @error('estatus') is-invalid @enderror">
              <option value="pendiente"  {{ $estatusOld==='pendiente'?'selected':'' }}>Pendiente</option>
              <option value="validado"   {{ $estatusOld==='validado'?'selected':'' }}>Validado</option>
              <option value="descartado" {{ $estatusOld==='descartado'?'selected':'' }}>Descartado</option>
            </select>
            @error('estatus')<div class="invalid-feedback">{{ $message }}</div>@enderror
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

@section('js')
<script>
// Normaliza a 3 dígitos (053, 088, etc.)
function pad3(v){ v = (v||'').toString().trim(); return v ? v.padStart(3,'0') : ''; }

document.addEventListener('DOMContentLoaded', ()=>{
  const sel = document.getElementById('slMunicipio');
  const cve = document.getElementById('txtCveMun');

  function syncCve(){
    const opt = sel.selectedOptions && sel.selectedOptions[0];
    const fromData = opt ? opt.getAttribute('data-cve') : '';
    cve.value = pad3(fromData);
  }

  // Si vienes de validación con old('cve_mun'), respétalo; si no, sincroniza
  if (!cve.value) syncCve();
  sel.addEventListener('change', syncCve);
});
</script>
@endsection
