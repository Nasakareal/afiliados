@extends('layouts.app')

@section('title', 'Nuevo afiliado')

@section('content_header')
  <h1 class="text-center w-100">Crear afiliado</h1>
@endsection

@section('content')
<div class="container-xl">
  <div class="card card-outline card-primary">
    <div class="card-body">
      <style>
        label.required::after {
          content: " *";
          color: #dc3545;
          margin-left: .25rem;
        }

        .form-control[readonly] {
          background-color: #f8f9fa;
        }
      </style>

      @php
        $req = fn($campo) => !empty($required[$campo] ?? false);
        $fullNameField = $fullNameField ?? 'nombre';
        $esDistritoLocal = $esDistritoLocal ?? false;
        $sexoOld = old('sexo', '');
        $sexoOpciones = [
          'M' => 'Hombre',
          'F' => 'Mujer',
          'Otro' => 'Otro',
        ];
      @endphp

      @if($esDistritoLocal)
        <div class="alert alert-info">
          <i class="fa fa-info-circle"></i>
          Captura simplificada para Distrito Local
        </div>
      @endif

      <form
        action="{{ route('afiliados.store') }}"
        method="POST"
        autocomplete="off"
      >
        @csrf

        <div class="row g-3">
          <div class="{{ $esDistritoLocal ? 'col-md-6' : 'col-md-6' }}">
            <label class="form-label {{ $req($fullNameField) ? 'required' : '' }}">
              Nombre completo
            </label>

            <input
              type="text"
              name="{{ $fullNameField }}"
              value="{{ old($fullNameField) }}"
              class="form-control @error($fullNameField) is-invalid @enderror"
              {{ $req($fullNameField) ? 'required' : '' }}
              maxlength="120"
              placeholder="EJEMPLO: MARIO DANTE BAUTISTA REBOLLAR"
            >

            @error($fullNameField)
              <div class="invalid-feedback">{{ $message }}</div>
            @enderror
          </div>

          @unless($esDistritoLocal)
            <div class="col-md-2">
              <label class="form-label {{ $req('edad') ? 'required' : '' }}">
                Edad
              </label>

              <input
                type="number"
                name="edad"
                value="{{ old('edad') }}"
                min="0"
                max="120"
                class="form-control @error('edad') is-invalid @enderror"
                {{ $req('edad') ? 'required' : '' }}
              >

              @error('edad')
                <div class="invalid-feedback">{{ $message }}</div>
              @enderror
            </div>
          @endunless

          <div class="{{ $esDistritoLocal ? 'col-md-3' : 'col-md-4' }}">
            <label class="form-label {{ $req('sexo') ? 'required' : '' }}">
              Sexo
            </label>

            <select
              name="sexo"
              class="form-select @error('sexo') is-invalid @enderror"
              {{ $req('sexo') ? 'required' : '' }}
            >
              <option value="">Seleccione...</option>

              @foreach($sexoOpciones as $valor => $texto)
                <option
                  value="{{ $valor }}"
                  {{ $sexoOld === $valor ? 'selected' : '' }}
                >
                  {{ $texto }}
                </option>
              @endforeach
            </select>

            @error('sexo')
              <div class="invalid-feedback">{{ $message }}</div>
            @enderror
          </div>

          <div class="{{ $esDistritoLocal ? 'col-md-3' : 'col-md-4' }}">
            <label class="form-label {{ $req('telefono') ? 'required' : '' }}">
              Teléfono
            </label>

            <input
              type="text"
              name="telefono"
              value="{{ old('telefono') }}"
              maxlength="30"
              inputmode="tel"
              class="form-control @error('telefono') is-invalid @enderror"
              {{ $req('telefono') ? 'required' : '' }}
            >

            @error('telefono')
              <div class="invalid-feedback">{{ $message }}</div>
            @enderror
          </div>

          @unless($esDistritoLocal)
            <div class="col-md-4">
              <label class="form-label {{ $req('email') ? 'required' : '' }}">
                Email
              </label>

              <input
                type="email"
                name="email"
                value="{{ old('email') }}"
                maxlength="150"
                class="form-control @error('email') is-invalid @enderror"
                {{ $req('email') ? 'required' : '' }}
              >

              @error('email')
                <div class="invalid-feedback">{{ $message }}</div>
              @enderror
            </div>

            @include('afiliados._campos_electorales')
          @endunless

          <div class="col-12 mt-4">
            <h5 class="mb-0">Ubicación electoral</h5>
            <hr class="mt-2">
          </div>

          <div class="col-md-4">
            <label class="form-label {{ $req('municipio') ? 'required' : '' }}">
              Municipio
            </label>

            <select
              name="municipio"
              id="slMunicipio"
              class="form-select @error('municipio') is-invalid @enderror"
              {{ $req('municipio') ? 'required' : '' }}
            >
              <option value="">-- Selecciona --</option>

              @foreach($municipios as $municipio)
                <option
                  value="{{ $municipio->municipio }}"
                  data-cve="{{ str_pad($municipio->cve_mun, 3, '0', STR_PAD_LEFT) }}"
                  {{ old('municipio') === $municipio->municipio ? 'selected' : '' }}
                >
                  {{ $municipio->municipio }}
                </option>
              @endforeach
            </select>

            @error('municipio')
              <div class="invalid-feedback">{{ $message }}</div>
            @enderror
          </div>

          <div class="col-md-2">
            <label class="form-label {{ $req('cve_mun') ? 'required' : '' }}">
              CVE municipal
            </label>

            <input
              type="text"
              name="cve_mun"
              id="txtCveMun"
              value="{{ old('cve_mun') }}"
              maxlength="3"
              readonly
              class="form-control @error('cve_mun') is-invalid @enderror"
              {{ $req('cve_mun') ? 'required' : '' }}
            >

            @error('cve_mun')
              <div class="invalid-feedback">{{ $message }}</div>
            @enderror
          </div>

          <div class="col-md-2">
            <label class="form-label {{ $req('seccion') ? 'required' : '' }}">
              Sección
            </label>

            <input
              type="text"
              name="seccion"
              id="txtSeccion"
              value="{{ old('seccion') }}"
              list="dlSecciones"
              maxlength="6"
              inputmode="numeric"
              class="form-control @error('seccion') is-invalid @enderror"
              {{ $req('seccion') ? 'required' : '' }}
              placeholder="Ej. 1234"
            >

            <datalist id="dlSecciones">
              @foreach($secciones ?? [] as $seccion)
                <option value="{{ $seccion }}"></option>
              @endforeach
            </datalist>

            @error('seccion')
              <div class="invalid-feedback">{{ $message }}</div>
            @enderror
          </div>

          <div class="col-md-2">
            <label class="form-label">Distrito local</label>

            <input
              type="number"
              name="distrito_local"
              id="txtDistritoLocal"
              value="{{ old('distrito_local') }}"
              readonly
              class="form-control @error('distrito_local') is-invalid @enderror"
            >

            @error('distrito_local')
              <div class="invalid-feedback">{{ $message }}</div>
            @enderror
          </div>

          <div class="col-md-2">
            <label class="form-label">Distrito federal</label>

            <input
              type="number"
              name="distrito_federal"
              id="txtDistritoFederal"
              value="{{ old('distrito_federal') }}"
              readonly
              class="form-control @error('distrito_federal') is-invalid @enderror"
            >

            @error('distrito_federal')
              <div class="invalid-feedback">{{ $message }}</div>
            @enderror
          </div>

          <div class="col-12 mt-4">
            <h5 class="mb-0">Domicilio</h5>
            <hr class="mt-2">
          </div>

          <div class="col-md-4">
            <label class="form-label {{ $req('localidad') ? 'required' : '' }}">
              Localidad
            </label>

            <input
              type="text"
              name="localidad"
              value="{{ old('localidad') }}"
              maxlength="150"
              class="form-control @error('localidad') is-invalid @enderror"
              {{ $req('localidad') ? 'required' : '' }}
            >

            @error('localidad')
              <div class="invalid-feedback">{{ $message }}</div>
            @enderror
          </div>

          <div class="col-md-4">
            <label class="form-label {{ $req('colonia') ? 'required' : '' }}">
              Colonia
            </label>

            <input
              type="text"
              name="colonia"
              value="{{ old('colonia') }}"
              maxlength="150"
              class="form-control @error('colonia') is-invalid @enderror"
              {{ $req('colonia') ? 'required' : '' }}
            >

            @error('colonia')
              <div class="invalid-feedback">{{ $message }}</div>
            @enderror
          </div>

          <div class="col-md-4">
            <label class="form-label {{ $req('calle') ? 'required' : '' }}">
              Calle
            </label>

            <input
              type="text"
              name="calle"
              value="{{ old('calle') }}"
              maxlength="150"
              class="form-control @error('calle') is-invalid @enderror"
              {{ $req('calle') ? 'required' : '' }}
            >

            @error('calle')
              <div class="invalid-feedback">{{ $message }}</div>
            @enderror
          </div>

          <div class="col-md-3">
            <label class="form-label {{ $req('numero_ext') ? 'required' : '' }}">
              Número exterior
            </label>

            <input
              type="text"
              name="numero_ext"
              value="{{ old('numero_ext') }}"
              maxlength="20"
              class="form-control @error('numero_ext') is-invalid @enderror"
              {{ $req('numero_ext') ? 'required' : '' }}
            >

            @error('numero_ext')
              <div class="invalid-feedback">{{ $message }}</div>
            @enderror
          </div>

          <div class="col-md-3">
            <label class="form-label {{ $req('numero_int') ? 'required' : '' }}">
              Número interior
            </label>

            <input
              type="text"
              name="numero_int"
              value="{{ old('numero_int') }}"
              maxlength="20"
              class="form-control @error('numero_int') is-invalid @enderror"
              {{ $req('numero_int') ? 'required' : '' }}
            >

            @error('numero_int')
              <div class="invalid-feedback">{{ $message }}</div>
            @enderror
          </div>

          <div class="col-md-3">
            <label class="form-label {{ $req('cp') ? 'required' : '' }}">
              Código postal
            </label>

            <input
              type="text"
              name="cp"
              value="{{ old('cp') }}"
              maxlength="10"
              inputmode="numeric"
              class="form-control @error('cp') is-invalid @enderror"
              {{ $req('cp') ? 'required' : '' }}
            >

            @error('cp')
              <div class="invalid-feedback">{{ $message }}</div>
            @enderror
          </div>

          <div class="col-md-12">
            <label class="form-label {{ $req('perfil') ? 'required' : '' }}">
              Referente
            </label>

            <textarea
              name="perfil"
              rows="2"
              maxlength="120"
              class="form-control @error('perfil') is-invalid @enderror"
              {{ $req('perfil') ? 'required' : '' }}
            >{{ old('perfil') }}</textarea>

            @error('perfil')
              <div class="invalid-feedback">{{ $message }}</div>
            @enderror
          </div>

          @unless($esDistritoLocal)
            <div class="col-md-12">
              <label class="form-label {{ $req('observaciones') ? 'required' : '' }}">
                Observaciones
              </label>

              <textarea
                name="observaciones"
                rows="2"
                class="form-control @error('observaciones') is-invalid @enderror"
                {{ $req('observaciones') ? 'required' : '' }}
              >{{ old('observaciones') }}</textarea>

              @error('observaciones')
                <div class="invalid-feedback">{{ $message }}</div>
              @enderror
            </div>

            <div class="col-md-3">
              @php
                $estatusOld = old('estatus', 'pendiente');
              @endphp

              <label class="form-label {{ $req('estatus') ? 'required' : '' }}">
                Afiliado
              </label>

              <select
                name="estatus"
                class="form-select @error('estatus') is-invalid @enderror"
                {{ $req('estatus') ? 'required' : '' }}
              >
                <option
                  value="pendiente"
                  {{ $estatusOld === 'pendiente' ? 'selected' : '' }}
                >
                  Pendiente
                </option>

                <option
                  value="validado"
                  {{ $estatusOld === 'validado' ? 'selected' : '' }}
                >
                  Sí
                </option>

                <option
                  value="descartado"
                  {{ $estatusOld === 'descartado' ? 'selected' : '' }}
                >
                  No
                </option>
              </select>

              @error('estatus')
                <div class="invalid-feedback">{{ $message }}</div>
              @enderror
            </div>

            <div class="col-md-4">
              <label class="form-label {{ $req('fecha_convencimiento') ? 'required' : '' }}">
                Fecha de convencimiento
              </label>

              <input
                type="datetime-local"
                name="fecha_convencimiento"
                value="{{ old('fecha_convencimiento') }}"
                class="form-control @error('fecha_convencimiento') is-invalid @enderror"
                {{ $req('fecha_convencimiento') ? 'required' : '' }}
              >

              @error('fecha_convencimiento')
                <div class="invalid-feedback">{{ $message }}</div>
              @enderror
            </div>
          @endunless
        </div>

        <div class="mt-4 d-flex gap-2">
          <a href="{{ route('afiliados.index') }}" class="btn btn-secondary">
            Cancelar
          </a>

          <button type="submit" class="btn btn-primary">
            <i class="fa fa-save"></i> Guardar
          </button>
        </div>
      </form>
    </div>
  </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
  const municipio = document.getElementById('slMunicipio');
  const cveMunicipal = document.getElementById('txtCveMun');
  const seccion = document.getElementById('txtSeccion');
  const distritoLocal = document.getElementById('txtDistritoLocal');
  const distritoFederal = document.getElementById('txtDistritoFederal');
  const endpoint = @json(route('secciones.lookup'));

  const limpiarUbicacion = function () {
    municipio.value = '';
    cveMunicipal.value = '';
    distritoLocal.value = '';
    distritoFederal.value = '';
  };

  const sincronizarCve = function () {
    const opcion = municipio.options[municipio.selectedIndex];

    cveMunicipal.value = opcion?.dataset?.cve
      ? String(opcion.dataset.cve).padStart(3, '0')
      : '';
  };

  const consultarSeccion = async function () {
    const numeroSeccion = seccion.value.trim();

    if (!numeroSeccion) {
      limpiarUbicacion();
      return;
    }

    const parametros = new URLSearchParams({
      seccion: numeroSeccion
    });

    try {
      const respuesta = await fetch(`${endpoint}?${parametros.toString()}`, {
        headers: {
          'X-Requested-With': 'XMLHttpRequest',
          'Accept': 'application/json'
        }
      });

      if (!respuesta.ok) {
        throw new Error();
      }

      const datos = await respuesta.json();

      municipio.value = datos.municipio ?? '';
      cveMunicipal.value = datos.cve_mun
        ? String(datos.cve_mun).padStart(3, '0')
        : '';
      distritoLocal.value = datos.distrito_local ?? '';
      distritoFederal.value = datos.distrito_federal ?? '';

      seccion.classList.remove('is-invalid');
      seccion.classList.add('is-valid');
    } catch {
      limpiarUbicacion();
      seccion.classList.remove('is-valid');
      seccion.classList.add('is-invalid');
    }
  };

  let temporizador;

  municipio.addEventListener('change', function () {
    sincronizarCve();
    seccion.value = '';
    distritoLocal.value = '';
    distritoFederal.value = '';
    seccion.classList.remove('is-valid', 'is-invalid');
  });

  seccion.addEventListener('input', function () {
    clearTimeout(temporizador);
    temporizador = setTimeout(consultarSeccion, 250);
  });

  seccion.addEventListener('change', consultarSeccion);
  seccion.addEventListener('blur', consultarSeccion);

  sincronizarCve();

  if (seccion.value.trim()) {
    consultarSeccion();
  }
});
</script>
@endpush
