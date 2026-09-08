@extends('layouts.app')

@section('title', 'Detalle del referente municipal')

@section('content_header')
    <h1 class="text-center w-100">Detalle del referente municipal</h1>
@endsection

@section('content')

<div class="container-xl">

    <div class="card card-outline card-primary">

        <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-2">

            <h3 class="card-title mb-0">
                {{ $referente->nombre_completo }}
            </h3>

            <div class="d-flex flex-wrap gap-2">

                @can('referentes_municipales.editar')
                    <a
                        href="{{ route('referentes_municipales.edit', $referente) }}"
                        class="btn btn-success btn-sm"
                    >
                        <i class="fa fa-pen"></i>
                        Editar
                    </a>
                @endcan

                <a
                    href="{{ route('referentes_municipales.index') }}"
                    class="btn btn-secondary btn-sm"
                >
                    <i class="fa fa-arrow-left"></i>
                    Volver
                </a>

            </div>

        </div>

        <div class="card-body">

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
                        Municipio
                    </div>

                    <div class="fw-semibold">
                        {{ $referente->municipio ?: '—' }}
                    </div>

                </div>

                <div class="col-md-3">

                    <div class="text-muted small">
                        CVE municipal
                    </div>

                    <div>
                        {{ $referente->cve_mun ?: '—' }}
                    </div>

                </div>

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
                            <i class="fa fa-phone me-1"></i>
                            {{ $referente->telefono }}
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

                <div class="col-md-4">

                    <div class="text-muted small">
                        WhatsApp
                    </div>

                    <div>
                        @if($referente->whatsapp)
                            <span class="badge bg-success">
                                <i class="fa-brands fa-whatsapp me-1"></i>
                                Sí
                            </span>
                        @else
                            <span class="badge bg-secondary">
                                No
                            </span>
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
                    href="{{ route('referentes_municipales.index') }}"
                    class="btn btn-secondary"
                >
                    <i class="fa fa-arrow-left"></i>
                    Volver al listado
                </a>

                @can('referentes_municipales.editar')
                    <a
                        href="{{ route('referentes_municipales.edit', $referente) }}"
                        class="btn btn-success"
                    >
                        <i class="fa fa-pen"></i>
                        Editar referente
                    </a>
                @endcan

                @can('referentes_municipales.borrar')

                    <form
                        action="{{ route('referentes_municipales.destroy', $referente) }}"
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
    const form = document.getElementById('formEliminarReferente');

    if (typeof Swal === 'undefined')
    {
        if (confirm('¿Eliminar este referente municipal?'))
        {
            form.submit();
        }

        return;
    }

    Swal.fire({
        title: 'Eliminar referente municipal',
        text: '¿Deseas eliminar este referente municipal?',
        icon: 'warning',
        showDenyButton: true,
        confirmButtonText: 'Eliminar',
        denyButtonText: 'Cancelar',
        confirmButtonColor: '#e3342f'
    }).then(function(result)
    {
        if (result.isConfirmed)
        {
            form.submit();
        }
    });
}
</script>

@endsection
