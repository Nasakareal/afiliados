@extends('layouts.app')

@section('title', 'Nuevo referente seccional')

@section('content_header')
    <h1 class="text-center w-100">Nuevo referente seccional</h1>
@endsection

@section('content')

<div class="container-xl">

    <div class="card card-outline card-primary">

        <div class="card-header">
            <h3 class="card-title mb-0">
                Captura de referente seccional
            </h3>
        </div>

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

            @if($errors->any())

                <div class="alert alert-danger">

                    <strong>
                        Revisa la información capturada.
                    </strong>

                    <ul class="mb-0 mt-2">

                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach

                    </ul>

                </div>

            @endif

            @php

                $cvesDisponibles = collect($secciones)
                    ->pluck('cve_mun')
                    ->map(
                        fn ($cve) => str_pad(
                            (string)$cve,
                            3,
                            '0',
                            STR_PAD_LEFT
                        )
                    )
                    ->unique()
                    ->values()
                    ->all();

            @endphp

            <form
                action="{{ route('referentes_seccionales.store') }}"
                method="POST"
                autocomplete="off"
            >

                @csrf

                <div class="row g-3">

                    <div class="col-12">

                        <h5 class="mb-0">
                            Datos generales
                        </h5>

                        <hr class="mt-2">

                    </div>


                    <div class="col-md-6">

                        <label class="form-label required">
                            Nombre completo
                        </label>

                        <input
                            type="text"
                            name="nombre_completo"
                            value="{{ old('nombre_completo') }}"
                            maxlength="150"
                            required
                            autofocus
                            class="form-control text-uppercase @error('nombre_completo') is-invalid @enderror"
                            placeholder="EJEMPLO: MARIO DANTE BAUTISTA REBOLLAR"
                        >

                        @error('nombre_completo')
                            <div class="invalid-feedback">
                                {{ $message }}
                            </div>
                        @enderror

                    </div>


                    <div class="col-12 mt-4">

                        <h5 class="mb-0">
                            Ubicación electoral
                        </h5>

                        <hr class="mt-2">

                    </div>


                    <div class="col-md-4">

                        <label class="form-label required">
                            Municipio
                        </label>

                        <select
                            name="cve_mun"
                            id="slMunicipio"
                            class="form-select @error('cve_mun') is-invalid @enderror"
                            required
                        >

                            <option value="">
                                Seleccione un municipio...
                            </option>

                            @foreach($municipios as $municipio)

                                @php

                                    $cve = str_pad(
                                        (string)$municipio->cve_mun,
                                        3,
                                        '0',
                                        STR_PAD_LEFT
                                    );

                                @endphp

                                @if(in_array($cve, $cvesDisponibles, true))

                                    <option
                                        value="{{ $cve }}"
                                        {{ old('cve_mun') === $cve ? 'selected' : '' }}
                                    >
                                        {{ $municipio->municipio }}
                                        — CVE {{ $cve }}
                                    </option>

                                @endif

                            @endforeach

                        </select>

                        @error('cve_mun')
                            <div class="invalid-feedback">
                                {{ $message }}
                            </div>
                        @enderror

                    </div>


                    <div class="col-md-2">

                        <label class="form-label required">
                            Sección
                        </label>

                        <select
                            name="seccion"
                            id="slSeccion"
                            class="form-select @error('seccion') is-invalid @enderror"
                            required
                            disabled
                        >

                            <option value="">
                                Seleccione...
                            </option>

                        </select>

                        @error('seccion')
                            <div class="invalid-feedback">
                                {{ $message }}
                            </div>
                        @enderror

                        <div
                            id="sinSecciones"
                            class="form-text text-danger d-none"
                        >
                            Este municipio ya no tiene secciones disponibles.
                        </div>

                    </div>


                    <div class="col-md-3">

                        <label class="form-label">
                            Distrito local
                        </label>

                        <input
                            type="text"
                            id="txtDistritoLocal"
                            class="form-control"
                            readonly
                        >

                    </div>


                    <div class="col-md-3">

                        <label class="form-label">
                            Distrito federal
                        </label>

                        <input
                            type="text"
                            id="txtDistritoFederal"
                            class="form-control"
                            readonly
                        >

                    </div>


                    <div class="col-12 mt-4">

                        <h5 class="mb-0">
                            Contacto
                        </h5>

                        <hr class="mt-2">

                    </div>


                    <div class="col-md-4">

                        <label class="form-label">
                            Teléfono
                        </label>

                        <input
                            type="text"
                            name="telefono"
                            value="{{ old('telefono') }}"
                            maxlength="20"
                            inputmode="tel"
                            class="form-control @error('telefono') is-invalid @enderror"
                            placeholder="4431234567"
                        >

                        @error('telefono')
                            <div class="invalid-feedback">
                                {{ $message }}
                            </div>
                        @enderror

                    </div>


                    <div class="col-md-4">

                        <label class="form-label">
                            Teléfono alternativo
                        </label>

                        <input
                            type="text"
                            name="telefono_alternativo"
                            value="{{ old('telefono_alternativo') }}"
                            maxlength="20"
                            inputmode="tel"
                            class="form-control @error('telefono_alternativo') is-invalid @enderror"
                            placeholder="Opcional"
                        >

                        @error('telefono_alternativo')
                            <div class="invalid-feedback">
                                {{ $message }}
                            </div>
                        @enderror

                    </div>


                    <div class="col-md-4">

                        <label class="form-label">
                            Correo electrónico
                        </label>

                        <input
                            type="email"
                            name="correo"
                            value="{{ old('correo') }}"
                            maxlength="150"
                            class="form-control @error('correo') is-invalid @enderror"
                            placeholder="correo@ejemplo.com"
                        >

                        @error('correo')
                            <div class="invalid-feedback">
                                {{ $message }}
                            </div>
                        @enderror

                    </div>


                    <div class="col-md-3">

                        <input
                            type="hidden"
                            name="whatsapp"
                            value="0"
                        >

                        <div class="form-check form-switch mt-md-4 pt-md-2">

                            <input
                                class="form-check-input"
                                type="checkbox"
                                role="switch"
                                id="whatsapp"
                                name="whatsapp"
                                value="1"
                                {{ old('whatsapp', '1') == '1' ? 'checked' : '' }}
                            >

                            <label
                                class="form-check-label"
                                for="whatsapp"
                            >
                                <i class="fa-brands fa-whatsapp"></i>
                                Tiene WhatsApp
                            </label>

                        </div>

                    </div>


                    <div class="col-12 mt-4">

                        <h5 class="mb-0">
                            Responsabilidad
                        </h5>

                        <hr class="mt-2">

                    </div>


                    <div class="col-md-6">

                        <label class="form-label">
                            Cargo
                        </label>

                        <input
                            type="text"
                            name="cargo"
                            value="{{ old('cargo') }}"
                            maxlength="150"
                            class="form-control @error('cargo') is-invalid @enderror"
                            placeholder="Ej. Responsable seccional"
                        >

                        @error('cargo')
                            <div class="invalid-feedback">
                                {{ $message }}
                            </div>
                        @enderror

                    </div>


                    <div class="col-md-6">

                        <label class="form-label">
                            Organización
                        </label>

                        <input
                            type="text"
                            name="organizacion"
                            value="{{ old('organizacion') }}"
                            maxlength="150"
                            class="form-control @error('organizacion') is-invalid @enderror"
                            placeholder="Organización o grupo"
                        >

                        @error('organizacion')
                            <div class="invalid-feedback">
                                {{ $message }}
                            </div>
                        @enderror

                    </div>


                    <div class="col-12 mt-4">

                        <h5 class="mb-0">
                            Ubicación
                        </h5>

                        <hr class="mt-2">

                    </div>


                    <div class="col-md-4">

                        <label class="form-label">
                            Localidad
                        </label>

                        <input
                            type="text"
                            name="localidad"
                            value="{{ old('localidad') }}"
                            maxlength="150"
                            class="form-control @error('localidad') is-invalid @enderror"
                        >

                        @error('localidad')
                            <div class="invalid-feedback">
                                {{ $message }}
                            </div>
                        @enderror

                    </div>


                    <div class="col-md-4">

                        <label class="form-label">
                            Colonia
                        </label>

                        <input
                            type="text"
                            name="colonia"
                            value="{{ old('colonia') }}"
                            maxlength="150"
                            class="form-control @error('colonia') is-invalid @enderror"
                        >

                        @error('colonia')
                            <div class="invalid-feedback">
                                {{ $message }}
                            </div>
                        @enderror

                    </div>


                    <div class="col-md-4">

                        <label class="form-label">
                            Dirección
                        </label>

                        <input
                            type="text"
                            name="direccion"
                            value="{{ old('direccion') }}"
                            maxlength="500"
                            class="form-control @error('direccion') is-invalid @enderror"
                            placeholder="Calle, número y referencias"
                        >

                        @error('direccion')
                            <div class="invalid-feedback">
                                {{ $message }}
                            </div>
                        @enderror

                    </div>


                    <div class="col-12 mt-4">

                        <h5 class="mb-0">
                            Control
                        </h5>

                        <hr class="mt-2">

                    </div>


                    <div class="col-md-9">

                        <label class="form-label">
                            Observaciones
                        </label>

                        <textarea
                            name="observaciones"
                            rows="3"
                            maxlength="2000"
                            class="form-control @error('observaciones') is-invalid @enderror"
                            placeholder="Información adicional sobre el referente..."
                        >{{ old('observaciones') }}</textarea>

                        @error('observaciones')
                            <div class="invalid-feedback">
                                {{ $message }}
                            </div>
                        @enderror

                    </div>


                    <div class="col-md-3">

                        <input
                            type="hidden"
                            name="activo"
                            value="0"
                        >

                        <div class="form-check form-switch mt-md-4 pt-md-2">

                            <input
                                class="form-check-input"
                                type="checkbox"
                                role="switch"
                                id="activo"
                                name="activo"
                                value="1"
                                {{ old('activo', '1') == '1' ? 'checked' : '' }}
                            >

                            <label
                                class="form-check-label"
                                for="activo"
                            >
                                Referente activo
                            </label>

                        </div>

                    </div>

                </div>


                <div class="mt-4 d-flex gap-2">

                    <a
                        href="{{ route('referentes_seccionales.index') }}"
                        class="btn btn-secondary"
                    >
                        Cancelar
                    </a>

                    <button
                        type="submit"
                        class="btn btn-primary"
                    >
                        <i class="fa fa-save"></i>
                        Guardar referente seccional
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
    const seccion = document.getElementById('slSeccion');
    const distritoLocal = document.getElementById('txtDistritoLocal');
    const distritoFederal = document.getElementById('txtDistritoFederal');
    const sinSecciones = document.getElementById('sinSecciones');

    const secciones = @json($secciones);

    const oldMunicipio = @json(old('cve_mun'));
    const oldSeccion = @json(old('seccion'));

    const normalizarCve = function (valor) {
        return String(valor ?? '').padStart(3, '0');
    };

    const limpiarDistritos = function () {
        distritoLocal.value = '';
        distritoFederal.value = '';
    };

    const cargarSecciones = function (seleccionarAnterior = false) {

        const cveMunicipio = normalizarCve(
            municipio.value
        );

        seccion.innerHTML = '';

        const opcionInicial = document.createElement('option');
        opcionInicial.value = '';
        opcionInicial.textContent = 'Seleccione...';

        seccion.appendChild(opcionInicial);

        limpiarDistritos();

        if (!municipio.value) {

            seccion.disabled = true;
            sinSecciones.classList.add('d-none');

            return;
        }

        const disponibles = secciones.filter(function (item) {

            return normalizarCve(item.cve_mun) === cveMunicipio;

        });

        disponibles.forEach(function (item) {

            const option = document.createElement('option');

            option.value = item.seccion;

            option.textContent =
                'Sección ' +
                item.seccion;

            option.dataset.distritoLocal =
                item.distrito_local ?? '';

            option.dataset.distritoFederal =
                item.distrito_federal ?? '';

            seccion.appendChild(option);

        });

        seccion.disabled = disponibles.length === 0;

        sinSecciones.classList.toggle(
            'd-none',
            disponibles.length > 0
        );

        if (
            seleccionarAnterior &&
            oldSeccion
        ) {
            seccion.value = String(oldSeccion);
            actualizarDistritos();
        }

    };

    const actualizarDistritos = function () {

        const option =
            seccion.options[seccion.selectedIndex];

        if (
            !option ||
            !option.value
        ) {
            limpiarDistritos();
            return;
        }

        distritoLocal.value =
            option.dataset.distritoLocal ?? '';

        distritoFederal.value =
            option.dataset.distritoFederal ?? '';

    };

    municipio.addEventListener(
        'change',
        function () {
            cargarSecciones(false);
        }
    );

    seccion.addEventListener(
        'change',
        actualizarDistritos
    );

    if (oldMunicipio) {
        municipio.value = normalizarCve(oldMunicipio);
        cargarSecciones(true);
    }

});

</script>

@endpush
