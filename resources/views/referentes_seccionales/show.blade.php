@extends('layouts.app')

@section('title', 'Detalle del referente seccional')

@section('content_header')
    <h1 class="text-center w-100">Detalle del referente seccional</h1>
@endsection

@section('content')

<div class="container-xl">

    <div class="card card-outline card-primary">

        <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-2">

            <h3 class="card-title mb-0">
                {{ $referente->nombre_completo }}
            </h3>

            <div class="d-flex flex-wrap gap-2">

                @can('referentes_seccionales.editar')
                    <a
                        href="{{ route('referentes_seccionales.edit', $referente) }}"
                        class="btn btn-success btn-sm"
                    >
                        <i class="fa fa-pen"></i>
                        Editar
                    </a>
                @endcan

                <a
                    href="{{ route('referentes_seccionales.index') }}"
                    class="btn btn-secondary btn-sm"
                >
                    <i class="fa fa-arrow-left"></i>
                    Volver
                </a>

            </div>

        </div>


        <div class="card-body">

            @php
                $numeroWhatsApp = preg_replace(
                    '/\D+/',
                    '',
                    (string) $referente->telefono
                );

                if (
                    strlen($numeroWhatsApp) === 13 &&
                    substr($numeroWhatsApp, 0, 3) === '521'
                ) {
                    $numeroWhatsApp = '52' . substr(
                        $numeroWhatsApp,
                        3
                    );
                } elseif (
                    strlen($numeroWhatsApp) === 10
                ) {
                    $numeroWhatsApp = '52' . $numeroWhatsApp;
                }

                $urlWhatsApp = (
                    $referente->whatsapp &&
                    $numeroWhatsApp !== ''
                )
                    ? 'https://wa.me/' . $numeroWhatsApp
                    : null;
            @endphp


            <div class="row g-4">


                <div class="col-12">
                    <h5 class="mb-0">Datos generales</h5>
                    <hr class="mt-2">
                </div>


                <div class="col-md-6">

                    <div class="text-muted small">
                        Nombre completo
                    </div>

                    <div class="fw-bold fs-5">
                        {{ $referente->nombre_completo }}
                    </div>

                </div>


                <div class="col-md-3">

                    <div class="text-muted small">
                        Estado
                    </div>

                    <div>

                        @if($referente->activo)

                            <span class="badge bg-success">
                                Activo
                            </span>

                        @else

                            <span class="badge bg-secondary">
                                Inactivo
                            </span>

                        @endif

                    </div>

                </div>


                <div class="col-12 mt-4">
                    <h5 class="mb-0">Ubicación electoral</h5>
                    <hr class="mt-2">
                </div>


                <div class="col-md-3">

                    <div class="text-muted small">
                        Municipio
                    </div>

                    <div class="fw-semibold">
                        {{ $referente->municipio ?: '—' }}
                    </div>

                </div>


                <div class="col-md-2">

                    <div class="text-muted small">
                        CVE municipal
                    </div>

                    <div>
                        {{ $referente->cve_mun ?: '—' }}
                    </div>

                </div>


                <div class="col-md-2">

                    <div class="text-muted small">
                        Sección
                    </div>

                    <div>

                        <span class="badge bg-primary fs-6">
                            {{ $referente->seccion }}
                        </span>

                    </div>

                </div>


                <div class="col-md-2">

                    <div class="text-muted small">
                        Distrito local
                    </div>

                    <div class="fw-semibold">
                        {{ $referente->distrito_local ?? '—' }}
                    </div>

                </div>


                <div class="col-md-3">

                    <div class="text-muted small">
                        Distrito federal
                    </div>

                    <div class="fw-semibold">
                        {{ $referente->distrito_federal ?? '—' }}
                    </div>

                </div>


                @if($seccionInfo)

                    <div class="col-md-3">

                        <div class="text-muted small">
                            Lista nominal
                        </div>

                        <div>
                            {{ number_format($seccionInfo->lista_nominal ?? 0) }}
                        </div>

                    </div>

                @endif


                <div class="col-12 mt-4">
                    <h5 class="mb-0">Contacto</h5>
                    <hr class="mt-2">
                </div>


                <div class="col-md-4">

                    <div class="text-muted small">
                        Teléfono
                    </div>

                    <div>

                        @if($referente->telefono)

                            @if($urlWhatsApp)

                                <a
                                    href="{{ $urlWhatsApp }}"
                                    target="_blank"
                                    rel="noopener noreferrer"
                                    class="text-success text-decoration-none fw-semibold"
                                    title="Enviar mensaje por WhatsApp"
                                >
                                    <i class="fa-brands fa-whatsapp me-1"></i>
                                    {{ $referente->telefono }}
                                </a>

                            @else

                                <i class="fa fa-phone me-1"></i>
                                {{ $referente->telefono }}

                            @endif

                        @else

                            —

                        @endif

                    </div>

                </div>


                <div class="col-md-4">

                    <div class="text-muted small">
                        Teléfono alternativo
                    </div>

                    <div>

                        @if($referente->telefono_alternativo)

                            <i class="fa fa-phone me-1"></i>
                            {{ $referente->telefono_alternativo }}

                        @else

                            —

                        @endif

                    </div>

                </div>


                <div class="col-md-4">

                    <div class="text-muted small">
                        Correo electrónico
                    </div>

                    <div>

                        @if($referente->correo)

                            <i class="fa fa-envelope me-1"></i>
                            {{ $referente->correo }}

                        @else

                            —

                        @endif

                    </div>

                </div>


                <div class="col-12 mt-4">
                    <h5 class="mb-0">Responsabilidad</h5>
                    <hr class="mt-2">
                </div>


                <div class="col-md-6">

                    <div class="text-muted small">
                        Cargo
                    </div>

                    <div>
                        {{ $referente->cargo ?: '—' }}
                    </div>

                </div>


                <div class="col-md-6">

                    <div class="text-muted small">
                        Organización
                    </div>

                    <div>
                        {{ $referente->organizacion ?: '—' }}
                    </div>

                </div>


                <div class="col-12 mt-4">
                    <h5 class="mb-0">Ubicación</h5>
                    <hr class="mt-2">
                </div>


                <div class="col-md-4">

                    <div class="text-muted small">
                        Localidad
                    </div>

                    <div>
                        {{ $referente->localidad ?: '—' }}
                    </div>

                </div>


                <div class="col-md-4">

                    <div class="text-muted small">
                        Colonia
                    </div>

                    <div>
                        {{ $referente->colonia ?: '—' }}
                    </div>

                </div>


                <div class="col-md-4">

                    <div class="text-muted small">
                        Dirección
                    </div>

                    <div>
                        {{ $referente->direccion ?: '—' }}
                    </div>

                </div>


                <div class="col-12 mt-4">
                    <h5 class="mb-0">Control</h5>
                    <hr class="mt-2">
                </div>


                <div class="col-md-3">

                    <div class="text-muted small">
                        Fecha de creación
                    </div>

                    <div>
                        {{ optional($referente->created_at)->format('Y-m-d H:i') ?: '—' }}
                    </div>

                </div>


                <div class="col-md-3">

                    <div class="text-muted small">
                        Última actualización
                    </div>

                    <div>
                        {{ optional($referente->updated_at)->format('Y-m-d H:i') ?: '—' }}
                    </div>

                </div>


                <div class="col-12">

                    <div class="text-muted small">
                        Observaciones
                    </div>

                    <div class="border rounded p-3 bg-light mt-1">
                        {!! nl2br(e($referente->observaciones ?: 'Sin observaciones.')) !!}
                    </div>

                </div>

            </div>


            <div class="mt-4 d-flex flex-wrap gap-2">

                <a
                    href="{{ route('referentes_seccionales.index') }}"
                    class="btn btn-secondary"
                >
                    <i class="fa fa-arrow-left"></i>
                    Volver al listado
                </a>


                @can('referentes_seccionales.editar')

                    <a
                        href="{{ route('referentes_seccionales.edit', $referente) }}"
                        class="btn btn-success"
                    >
                        <i class="fa fa-pen"></i>
                        Editar referente
                    </a>

                @endcan


                @can('referentes_seccionales.borrar')

                    <form
                        action="{{ route('referentes_seccionales.destroy', $referente) }}"
                        method="POST"
                        id="formEliminarReferente"
                    >

                        @csrf
                        @method('DELETE')

                        <button
                            type="button"
                            class="btn btn-danger"
                            onclick="confirmarEliminar()"
                        >
                            <i class="fa fa-trash"></i>
                            Eliminar
                        </button>

                    </form>

                @endcan

            </div>

        </div>

    </div>

</div>

@endsection


@section('js')

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>

function confirmarEliminar()
{
    const form =
        document.getElementById('formEliminarReferente');

    if (typeof Swal === 'undefined')
    {
        if (
            confirm(
                '¿Eliminar este referente seccional?'
            )
        ) {
            form.submit();
        }

        return;
    }

    Swal.fire({
        title: 'Eliminar referente seccional',
        text: '¿Deseas eliminar este referente seccional?',
        icon: 'warning',
        showDenyButton: true,
        confirmButtonText: 'Eliminar',
        denyButtonText: 'Cancelar',
        confirmButtonColor: '#e3342f'
    })
    .then(function(result)
    {
        if (result.isConfirmed)
        {
            form.submit();
        }
    });
}

</script>

@endsection
