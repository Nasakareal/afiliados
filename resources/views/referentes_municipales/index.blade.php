@extends('layouts.app')

@section('title','Referentes Municipales')

@section('content_header')
    <h1 class="text-center w-100">Referentes Municipales</h1>
@endsection

@section('content')

<div class="container-xl">

    <div class="card card-outline card-primary">

        <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-2">

            <div>
                <h3 class="card-title mb-0">
                    Referentes municipales registrados
                </h3>
            </div>

            <div class="d-flex flex-wrap gap-2">

                @can('referentes_municipales.crear')
                    <a
                        href="{{ route('referentes_municipales.create') }}"
                        class="btn btn-primary btn-sm"
                    >
                        <i class="fa fa-plus"></i>
                        Nuevo referente municipal
                    </a>
                @endcan

            </div>

        </div>

        <div class="card-body">

            <form
                method="GET"
                action="{{ route('referentes_municipales.index') }}"
                class="row g-2 mb-4"
            >

                <div class="col-12 col-md-4">

                    <input
                        type="text"
                        name="q"
                        value="{{ $q ?? '' }}"
                        class="form-control form-control-sm"
                        placeholder="Buscar nombre, teléfono, correo, localidad..."
                    >

                </div>

                <div class="col-12 col-md-3">

                    <select
                        name="cve_mun"
                        class="form-select form-select-sm"
                    >

                        <option value="">
                            Todos los municipios
                        </option>

                        @foreach($municipios as $municipio)

                            <option
                                value="{{ $municipio->cve_mun }}"
                                {{ ($cveMun ?? '') == $municipio->cve_mun ? 'selected' : '' }}
                            >
                                {{ $municipio->municipio }}
                            </option>

                        @endforeach

                    </select>

                </div>

                <div class="col-6 col-md-2">

                    <select
                        name="activo"
                        class="form-select form-select-sm"
                    >

                        <option value="">
                            Todos
                        </option>

                        <option
                            value="1"
                            {{ ($activo ?? '') !== '' && (string)$activo === '1' ? 'selected' : '' }}
                        >
                            Activos
                        </option>

                        <option
                            value="0"
                            {{ ($activo ?? '') !== '' && (string)$activo === '0' ? 'selected' : '' }}
                        >
                            Inactivos
                        </option>

                    </select>

                </div>

                <div class="col-6 col-md-2">

                    <select
                        name="per_page"
                        class="form-select form-select-sm"
                        onchange="this.form.submit()"
                    >

                        @foreach($perPageOptions as $option)

                            <option
                                value="{{ $option }}"
                                {{ (int)request('per_page', 25) === $option ? 'selected' : '' }}
                            >
                                {{ $option }} por página
                            </option>

                        @endforeach

                    </select>

                </div>

                <div class="col-6 col-md-1">

                    <button
                        type="submit"
                        class="btn btn-outline-primary btn-sm w-100"
                    >
                        <i class="fa fa-search"></i>
                    </button>

                </div>

                <div class="col-6 col-md-2">

                    <a
                        href="{{ route('referentes_municipales.index') }}"
                        class="btn btn-outline-secondary btn-sm w-100"
                    >
                        <i class="fa fa-eraser"></i>
                        Limpiar
                    </a>

                </div>

            </form>


            <div class="table-responsive">

                <table
                    class="table table-striped table-bordered table-hover table-sm align-middle"
                >

                    <thead>

                        <tr>

                            <th
                                class="text-center"
                                style="width:60px"
                            >
                                #
                            </th>

                            <th>
                                Nombre
                            </th>

                            <th>
                                Contacto
                            </th>

                            <th>
                                Municipio
                            </th>

                            <th>
                                Cargo / Organización
                            </th>

                            <th>
                                Ubicación
                            </th>

                            <th class="text-center">
                                Estado
                            </th>

                            <th>
                                Creación
                            </th>

                            <th
                                class="text-center"
                                style="width:140px"
                            >
                                Acciones
                            </th>

                        </tr>

                    </thead>

                    <tbody>

                        @forelse($referentes as $i => $referente)

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

                            <tr>

                                <td class="text-center">

                                    {{
                                        ($referentes->currentPage() - 1)
                                        * $referentes->perPage()
                                        + $i
                                        + 1
                                    }}

                                </td>


                                <td style="min-width:220px">

                                    <strong>
                                        {{ $referente->nombre_completo }}
                                    </strong>

                                </td>


                                <td style="min-width:190px">

                                    @if($referente->telefono)

                                        <div>

                                            @if($urlWhatsApp)

                                                <a
                                                    href="{{ $urlWhatsApp }}"
                                                    target="_blank"
                                                    rel="noopener noreferrer"
                                                    class="text-success text-decoration-none fw-semibold"
                                                    title="Enviar mensaje por WhatsApp"
                                                >
                                                    <i class="fa-brands fa-whatsapp"></i>
                                                    {{ $referente->telefono }}
                                                </a>

                                            @else

                                                <span>
                                                    <i class="fa fa-phone"></i>
                                                    {{ $referente->telefono }}
                                                </span>

                                            @endif

                                        </div>

                                    @endif


                                    @if($referente->telefono_alternativo)

                                        <div class="small text-muted">

                                            <i class="fa fa-phone"></i>
                                            {{ $referente->telefono_alternativo }}

                                        </div>

                                    @endif


                                    @if($referente->correo)

                                        <div class="small text-muted">

                                            <i class="fa fa-envelope"></i>
                                            {{ $referente->correo }}

                                        </div>

                                    @endif


                                    @if(
                                        !$referente->telefono &&
                                        !$referente->telefono_alternativo &&
                                        !$referente->correo
                                    )

                                        <span class="text-muted">
                                            —
                                        </span>

                                    @endif

                                </td>


                                <td style="min-width:170px">

                                    <strong>
                                        {{ $referente->municipio ?: '—' }}
                                    </strong>

                                    @if($referente->cve_mun)

                                        <div class="small text-muted">

                                            CVE:
                                            {{ $referente->cve_mun }}

                                        </div>

                                    @endif

                                </td>


                                <td style="min-width:190px">

                                    @if($referente->cargo)

                                        <div>
                                            {{ $referente->cargo }}
                                        </div>

                                    @endif


                                    @if($referente->organizacion)

                                        <div class="small text-muted">

                                            <i class="fa fa-building"></i>
                                            {{ $referente->organizacion }}

                                        </div>

                                    @endif


                                    @if(
                                        !$referente->cargo &&
                                        !$referente->organizacion
                                    )

                                        <span class="text-muted">
                                            —
                                        </span>

                                    @endif

                                </td>


                                <td style="min-width:190px">

                                    @if($referente->localidad)

                                        <div>
                                            {{ $referente->localidad }}
                                        </div>

                                    @endif


                                    @if($referente->colonia)

                                        <div class="small text-muted">
                                            {{ $referente->colonia }}
                                        </div>

                                    @endif


                                    @if($referente->direccion)

                                        <div class="small text-muted">
                                            {{ $referente->direccion }}
                                        </div>

                                    @endif


                                    @if(
                                        !$referente->localidad &&
                                        !$referente->colonia &&
                                        !$referente->direccion
                                    )

                                        <span class="text-muted">
                                            —
                                        </span>

                                    @endif

                                </td>


                                <td class="text-center">

                                    @if($referente->activo)

                                        <span class="badge bg-success">
                                            Activo
                                        </span>

                                    @else

                                        <span class="badge bg-secondary">
                                            Inactivo
                                        </span>

                                    @endif

                                </td>


                                <td class="text-nowrap">

                                    {{ optional($referente->created_at)->format('Y-m-d H:i') }}

                                </td>


                                <td class="text-center text-nowrap">

                                    <div class="btn-group">

                                        @can('referentes_municipales.ver')

                                            <a
                                                href="{{ route('referentes_municipales.show', $referente) }}"
                                                class="btn btn-info btn-sm"
                                                title="Ver"
                                            >
                                                <i class="fa fa-eye"></i>
                                            </a>

                                        @endcan


                                        @can('referentes_municipales.editar')

                                            <a
                                                href="{{ route('referentes_municipales.edit', $referente) }}"
                                                class="btn btn-success btn-sm"
                                                title="Editar"
                                            >
                                                <i class="fa fa-pen"></i>
                                            </a>

                                        @endcan


                                        @can('referentes_municipales.borrar')

                                            <form
                                                action="{{ route('referentes_municipales.destroy', $referente) }}"
                                                method="POST"
                                                id="formDel-{{ $referente->id }}"
                                            >

                                                @csrf
                                                @method('DELETE')

                                                <button
                                                    type="button"
                                                    class="btn btn-danger btn-sm"
                                                    onclick="confirmarEliminar('{{ $referente->id }}', this)"
                                                    title="Eliminar"
                                                >
                                                    <i class="fa fa-trash"></i>
                                                </button>

                                            </form>

                                        @endcan

                                    </div>

                                </td>

                            </tr>

                        @empty

                            <tr>

                                <td
                                    colspan="9"
                                    class="text-center text-muted py-4"
                                >
                                    No hay referentes municipales con los filtros seleccionados.
                                </td>

                            </tr>

                        @endforelse

                    </tbody>

                </table>

            </div>


            <div class="mt-3 d-flex flex-wrap justify-content-between align-items-center gap-2">

                <div class="small text-muted">

                    Registros
                    {{ $referentes->firstItem() ?: 0 }}
                    –
                    {{ $referentes->lastItem() ?: 0 }}

                    · Página
                    {{ $referentes->currentPage() }}

                </div>

                <div>
                    {{ $referentes->links() }}
                </div>

            </div>

        </div>

    </div>

</div>

@endsection


@section('js')

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>

function confirmarEliminar(id, btn)
{
    const form = document.getElementById('formDel-' + id);

    btn.disabled = true;

    if (typeof Swal === 'undefined')
    {
        if (confirm('¿Eliminar referente municipal?'))
        {
            form.submit();
        }
        else
        {
            btn.disabled = false;
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
    })
    .then(function(result)
    {
        if (result.isConfirmed)
        {
            form.submit();
        }
        else
        {
            btn.disabled = false;
        }
    });
}

</script>


@if(session('status'))

<script>

Swal.fire({
    icon: 'success',
    title: @json(session('status')),
    timer: 2500,
    showConfirmButton: false
});

</script>

@endif

@endsection
