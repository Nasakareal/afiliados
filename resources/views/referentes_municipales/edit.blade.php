@extends('layouts.app')

@section('title', 'Editar referente municipal')

@section('content_header')
    <h1 class="text-center w-100">Editar referente municipal</h1>
@endsection

@section('content')

<div class="container-xl">

    <div class="card card-outline card-primary">

        <div class="card-header">
            <h3 class="card-title mb-0">
                Editar referente municipal
            </h3>
        </div>

        <div class="card-body">

            <style>
                label.required::after {
                    content: " *";
                    color: #dc3545;
                    margin-left: .25rem;
                }
            </style>

            @if($errors->any())
                <div class="alert alert-danger">
                    <strong>Revisa la información capturada.</strong>

                    <ul class="mb-0 mt-2">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form
                action="{{ route('referentes_municipales.update', $referente) }}"
                method="POST"
                autocomplete="off"
            >

                @csrf
                @method('PUT')

                <div class="row g-3">

                    <div class="col-12">
                        <h5 class="mb-0">Datos generales</h5>
                        <hr class="mt-2">
                    </div>

                    <div class="col-md-6">

                        <label class="form-label required">
                            Nombre completo
                        </label>

                        <input
                            type="text"
                            name="nombre_completo"
                            value="{{ old('nombre_completo', $referente->nombre_completo) }}"
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

                    <div class="col-md-6">

                        <label class="form-label required">
                            Municipio
                        </label>

                        <select
                            name="cve_mun"
                            class="form-select @error('cve_mun') is-invalid @enderror"
                            required
                        >

                            <option value="">
                                Seleccione un municipio...
                            </option>

                            @foreach($municipios as $municipio)

                                @php
                                    $cveMunicipio = str_pad(
                                        (string)$municipio->cve_mun,
                                        3,
                                        '0',
                                        STR_PAD_LEFT
                                    );

                                    $cveSeleccionada = old(
                                        'cve_mun',
                                        $referente->cve_mun
                                    );
                                @endphp

                                <option
                                    value="{{ $cveMunicipio }}"
                                    {{ $cveSeleccionada == $cveMunicipio ? 'selected' : '' }}
                                >
                                    {{ $municipio->municipio }}
                                    — CVE {{ $cveMunicipio }}
                                </option>

                            @endforeach

                        </select>

                        @error('cve_mun')
                            <div class="invalid-feedback">
                                {{ $message }}
                            </div>
                        @enderror

                    </div>

                    <div class="col-12 mt-4">
                        <h5 class="mb-0">Contacto</h5>
                        <hr class="mt-2">
                    </div>

                    <div class="col-md-4">

                        <label class="form-label">
                            Teléfono
                        </label>

                        <input
                            type="text"
                            name="telefono"
                            value="{{ old('telefono', $referente->telefono) }}"
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
                            value="{{ old('telefono_alternativo', $referente->telefono_alternativo) }}"
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
                            value="{{ old('correo', $referente->correo) }}"
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
                                {{ old('whatsapp', $referente->whatsapp ? '1' : '0') == '1' ? 'checked' : '' }}
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
                        <h5 class="mb-0">Responsabilidad</h5>
                        <hr class="mt-2">
                    </div>

                    <div class="col-md-6">

                        <label class="form-label">
                            Cargo
                        </label>

                        <input
                            type="text"
                            name="cargo"
                            value="{{ old('cargo', $referente->cargo) }}"
                            maxlength="150"
                            class="form-control @error('cargo') is-invalid @enderror"
                            placeholder="Ej. Coordinador municipal"
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
                            value="{{ old('organizacion', $referente->organizacion) }}"
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
                        <h5 class="mb-0">Ubicación</h5>
                        <hr class="mt-2">
                    </div>

                    <div class="col-md-4">

                        <label class="form-label">
                            Localidad
                        </label>

                        <input
                            type="text"
                            name="localidad"
                            value="{{ old('localidad', $referente->localidad) }}"
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
                            value="{{ old('colonia', $referente->colonia) }}"
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
                            value="{{ old('direccion', $referente->direccion) }}"
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
                        <h5 class="mb-0">Control</h5>
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
                        >{{ old('observaciones', $referente->observaciones) }}</textarea>

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
                                {{ old('activo', $referente->activo ? '1' : '0') == '1' ? 'checked' : '' }}
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
                        href="{{ route('referentes_municipales.index') }}"
                        class="btn btn-secondary"
                    >
                        Cancelar
                    </a>

                    <button
                        type="submit"
                        class="btn btn-primary"
                    >
                        <i class="fa fa-save"></i>
                        Guardar cambios
                    </button>

                </div>

            </form>

        </div>

    </div>

</div>

@endsection
