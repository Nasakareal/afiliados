@extends('layouts.app')

@section('title', 'Avance municipal')

@push('css')

<link
    rel="stylesheet"
    href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"
>

<style>
    .avance-municipal-page {
        --azul-reporte:#1d3376;
        --gris-reporte:#eef1eb;
        --rosa-reporte:#d91785;
        padding-bottom:2.5rem;
    }

    .avance-municipal-page .report-actions .btn {
        border-radius:.45rem;
    }

    .avance-periodo {
        color:#667085;
        font-size:.82rem;
    }

    .municipal-summary-wrap {
        border-radius:.35rem;
        box-shadow:0 8px 22px rgba(22,39,93,.08);
        overflow-x:auto;
        margin-bottom:1.15rem;
    }

    .municipal-summary {
        display:grid;
        grid-template-columns:repeat(6,1fr);
        min-width:900px;
    }

    .municipal-summary-head,
    .municipal-summary-value {
        align-items:center;
        display:flex;
        justify-content:center;
        text-align:center;
    }

    .municipal-summary-head {
        background:var(--azul-reporte);
        border-right:1px solid rgba(255,255,255,.28);
        color:#fff;
        font-size:.68rem;
        font-weight:700;
        line-height:1.15;
        min-height:54px;
        padding:.5rem;
        text-transform:uppercase;
    }

    .municipal-summary-value {
        background:var(--gris-reporte);
        border-right:1px solid #fff;
        color:#283044;
        font-size:.82rem;
        font-weight:700;
        min-height:38px;
        padding:.35rem .5rem;
    }

    .municipal-summary-value.coverage {
        color:#fff;
        font-weight:800;
    }

    .coverage-success {
        background:#2f9148 !important;
    }

    .coverage-warning {
        background:#d8ab00 !important;
        color:#3b3000 !important;
    }

    .coverage-danger {
        background:#b3262e !important;
    }

    .avance-report-grid {
        display:grid;
        gap:1.1rem;
        grid-template-columns:minmax(320px,38%) minmax(650px,62%);
        margin-bottom:1.15rem;
    }

    .municipal-map-card,
    .municipal-table-card {
        background:#fff;
        border:1px solid #dfe4ee;
        border-radius:1rem;
        box-shadow:0 8px 24px rgba(22,39,93,.08);
        overflow:hidden;
    }

    .municipal-ribbon {
        align-items:center;
        background:var(--azul-reporte);
        color:#fff;
        display:flex;
        font-size:.75rem;
        font-weight:800;
        letter-spacing:.025em;
        min-height:38px;
        padding:.55rem 1.1rem;
        text-transform:uppercase;
    }

    .municipal-map-body {
        position:relative;
    }

    #avanceMunicipalMap {
        background:#f8f9f4;
        height:590px;
        width:100%;
    }

    #avanceMunicipalMap .leaflet-control-container {
        display:none;
    }

    .municipal-map-empty {
        align-items:center;
        color:#667085;
        display:flex;
        height:590px;
        justify-content:center;
        padding:2rem;
        text-align:center;
    }

    .municipal-map-legend {
        align-items:center;
        background:#fff;
        border:1px solid #dfe4ee;
        border-radius:.45rem;
        bottom:.7rem;
        box-shadow:0 2px 8px rgba(22,39,93,.16);
        display:flex;
        flex-wrap:wrap;
        font-size:.66rem;
        gap:.45rem;
        left:.7rem;
        padding:.42rem .55rem;
        position:absolute;
        z-index:500;
    }

    .municipal-map-legend span {
        align-items:center;
        display:flex;
        gap:.22rem;
    }

    .municipal-map-legend i {
        border:1px solid rgba(0,0,0,.15);
        display:inline-block;
        height:10px;
        width:14px;
    }

    .municipal-map-label-toggle {
        align-items:center;
        background:#fff;
        border:1px solid #dfe4ee;
        border-radius:.45rem;
        box-shadow:0 2px 8px rgba(22,39,93,.16);
        cursor:pointer;
        display:flex;
        font-size:.7rem;
        gap:.35rem;
        padding:.42rem .55rem;
        position:absolute;
        right:.7rem;
        top:.7rem;
        z-index:500;
    }

    .municipal-map-label-toggle input {
        margin:0;
    }

    .municipal-map-label {
        background:transparent;
        border:0;
    }

    .municipal-map-label span {
        color:#344268;
        font-size:9px;
        font-weight:700;
        line-height:1;
        text-shadow:
            0 0 3px #fff,
            0 0 5px #fff;
        white-space:nowrap;
    }

    .municipal-table-scroll {
        max-height:590px;
        overflow:auto;
    }

    .municipal-table {
        margin:0;
        min-width:1100px;
    }

    .municipal-table thead {
        position:sticky;
        top:0;
        z-index:3;
    }

    .municipal-table thead th {
        background:var(--azul-reporte);
        border-color:rgba(255,255,255,.25);
        color:#fff;
        font-size:.62rem;
        font-weight:700;
        line-height:1.12;
        padding:.65rem .35rem;
        text-align:center;
        text-transform:uppercase;
        vertical-align:middle;
    }

    .municipal-table tbody td {
        border-color:#edf0f3;
        color:#303849;
        font-size:.69rem;
        line-height:1.15;
        padding:.5rem .38rem;
        text-align:center;
        vertical-align:middle;
    }

    .municipal-table tbody tr:nth-child(even) td {
        background:#f7f8f4;
    }

    .municipal-table .municipio-cell,
    .municipal-table .referente-cell {
        text-align:left;
    }

    .municipal-table .municipio-cell {
        font-weight:700;
    }

    .municipio-cve {
        color:#7c8493;
        display:block;
        font-size:.63rem;
        font-weight:400;
        margin-top:.15rem;
    }

    .referente-name {
        font-weight:700;
    }

    .referente-contact {
        color:#667085;
        font-size:.64rem;
        margin-top:.15rem;
    }

    .missing-reference {
        color:#b3262e;
        font-weight:700;
    }

    .filters-card {
        border:0;
        box-shadow:0 5px 16px rgba(22,39,93,.07);
    }

    @media (max-width:1199.98px) {
        .avance-report-grid {
            grid-template-columns:1fr;
        }

        #avanceMunicipalMap {
            height:430px;
        }

        .municipal-table-scroll {
            max-height:none;
        }
    }

    @media print {
        .report-actions,
        .navbar,
        .app-footer,
        .filters-card {
            display:none !important;
        }

        .content-wrap {
            padding-top:0 !important;
        }

        .avance-report-grid {
            grid-template-columns:38% 62%;
        }

        #avanceMunicipalMap {
            height:500px;
        }

        .municipal-table-scroll {
            max-height:none;
            overflow:visible;
        }
    }
</style>

@endpush


@section('content')

<div class="container-fluid px-xl-4 avance-municipal-page">

    @php

        $dfTexto = $distritoFederal !== ''
            ? str_pad(
                $distritoFederal,
                2,
                '0',
                STR_PAD_LEFT
            )
            : 'Todos';

        $dlTexto = $distritoLocal !== ''
            ? str_pad(
                $distritoLocal,
                2,
                '0',
                STR_PAD_LEFT
            )
            : 'Todos';

        $tituloTerritorio = $distritoFederal !== ''
            ? 'Distrito Federal '.$dfTexto.(
                $nombreDistritoFederal
                    ? ' · '.$nombreDistritoFederal
                    : ''
            )
            : 'Michoacán';

        if ($distritoLocal !== '') {
            $tituloTerritorio .=
                ' · Distrito Local '.$dlTexto;
        }

        if ($cveMun !== '') {
            $municipioSeleccionado =
                $municipiosFiltro->firstWhere(
                    'cve_mun',
                    $cveMun
                );

            if ($municipioSeleccionado) {
                $tituloTerritorio .=
                    ' · '.$municipioSeleccionado->municipio;
            }
        }

        $porcentajeCobertura =
            (float)(
                $totales['porcentaje_cobertura'] ?? 0
            );

        $coverageClass =
            $porcentajeCobertura >= 80
                ? 'coverage-success'
                : (
                    $porcentajeCobertura >= 30
                        ? 'coverage-warning'
                        : 'coverage-danger'
                );

        $coberturaMunicipios = $avance
            ->mapWithKeys(function ($fila) {
                return [
                    $fila['cve_mun'] => [
                        'cve_mun' =>
                            $fila['cve_mun'],

                        'municipio' =>
                            $fila['municipio'],

                        'cubierto' =>
                            $fila['tiene_referente'],

                        'activo' =>
                            $fila['referente_activo'],

                        'referente' =>
                            $fila['nombre_completo'],

                        'telefono' =>
                            $fila['telefono'],

                        'secciones' =>
                            $fila['secciones'],
                    ],
                ];
            });

    @endphp


    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">

        <div>

            <h1 class="h5 fw-bold mb-1 text-uppercase">
                Avance municipal
            </h1>

            <div class="avance-periodo">
                {{ $tituloTerritorio }}
                · Cobertura de referentes municipales
            </div>

        </div>


        <div class="report-actions d-flex flex-wrap gap-2">

            <button
                type="button"
                class="btn btn-sm btn-outline-secondary"
                data-bs-toggle="modal"
                data-bs-target="#modalFiltrosMunicipales"
            >
                <i class="fa-solid fa-filter me-1"></i>
                Filtros
            </button>


            @can('referentes_municipales.ver')

                <a
                    href="{{ route('referentes_municipales.index') }}"
                    class="btn btn-sm btn-outline-primary"
                >
                    <i class="fa-solid fa-building-user me-1"></i>
                    Ver referentes
                </a>

            @endcan


            @can('referentes_municipales.crear')

                <a
                    href="{{ route('referentes_municipales.create') }}"
                    class="btn btn-sm btn-granate"
                >
                    <i class="fa-solid fa-plus me-1"></i>
                    Nuevo referente
                </a>

            @endcan

        </div>

    </div>


    @if(session('status'))

        <div class="alert alert-success alert-dismissible fade show py-2">

            <i class="fa-solid fa-circle-check me-1"></i>

            {{ session('status') }}

            <button
                type="button"
                class="btn-close py-2"
                data-bs-dismiss="alert"
            ></button>

        </div>

    @endif


    <div class="municipal-summary-wrap">

        <div class="municipal-summary">

            @foreach([
                'Municipios',
                'Con referente',
                'Sin referente',
                'Activos',
                'Inactivos',
                'Cobertura'
            ] as $encabezado)

                <div class="municipal-summary-head">
                    {{ $encabezado }}
                </div>

            @endforeach


            <div class="municipal-summary-value">
                {{ number_format($totales['municipios']) }}
            </div>

            <div class="municipal-summary-value">
                {{ number_format($totales['con_referente']) }}
            </div>

            <div class="municipal-summary-value">
                {{ number_format($totales['sin_referente']) }}
            </div>

            <div class="municipal-summary-value">
                {{ number_format($totales['activos']) }}
            </div>

            <div class="municipal-summary-value">
                {{ number_format($totales['inactivos']) }}
            </div>

            <div
                class="municipal-summary-value coverage {{ $coverageClass }}"
            >
                {{ number_format($porcentajeCobertura, 2) }}%
            </div>

        </div>

    </div>


    <div class="card filters-card mb-3">

        <div class="card-body">

            <form
                method="GET"
                action="{{ route('avance_municipal.index') }}"
                class="row g-2 align-items-end"
            >

                <div class="col-12 col-lg-3">

                    <label class="form-label small fw-semibold">
                        Buscar
                    </label>

                    <input
                        type="text"
                        name="q"
                        value="{{ $q }}"
                        class="form-control form-control-sm"
                        placeholder="Municipio, referente, teléfono..."
                    >

                </div>


                <div class="col-6 col-lg-2">

                    <label class="form-label small fw-semibold">
                        Municipio
                    </label>

                    <select
                        name="cve_mun"
                        class="form-select form-select-sm"
                    >

                        <option value="">
                            Todos
                        </option>

                        @foreach($municipiosFiltro as $municipio)

                            <option
                                value="{{ $municipio->cve_mun }}"
                                {{ (string)$cveMun === (string)$municipio->cve_mun ? 'selected' : '' }}
                            >
                                {{ $municipio->municipio }}
                            </option>

                        @endforeach

                    </select>

                </div>


                <div class="col-6 col-lg-2">

                    <label class="form-label small fw-semibold">
                        Distrito federal
                    </label>

                    <select
                        name="distrito_federal"
                        class="form-select form-select-sm"
                    >

                        <option value="">
                            Todos
                        </option>

                        @foreach($distritosFederales as $distrito)

                            <option
                                value="{{ $distrito }}"
                                {{ (string)$distritoFederal === (string)$distrito ? 'selected' : '' }}
                            >
                                DF {{ str_pad(
                                    $distrito,
                                    2,
                                    '0',
                                    STR_PAD_LEFT
                                ) }}
                            </option>

                        @endforeach

                    </select>

                </div>


                <div class="col-6 col-lg-2">

                    <label class="form-label small fw-semibold">
                        Distrito local
                    </label>

                    @if(
                        $distritoLocalRestringido &&
                        count($distritosLocalesAsignados) === 1
                    )

                        <input
                            type="hidden"
                            name="distrito_local"
                            value="{{ $distritoLocal }}"
                        >

                        <input
                            type="text"
                            class="form-control form-control-sm"
                            value="DL {{ str_pad(
                                $distritoLocal,
                                2,
                                '0',
                                STR_PAD_LEFT
                            ) }}"
                            readonly
                        >

                    @else

                        <select
                            name="distrito_local"
                            class="form-select form-select-sm"
                        >

                            @unless($distritoLocalRestringido)

                                <option value="">
                                    Todos
                                </option>

                            @endunless

                            @foreach($distritosLocales as $distrito)

                                <option
                                    value="{{ $distrito }}"
                                    {{ (string)$distritoLocal === (string)$distrito ? 'selected' : '' }}
                                >
                                    DL {{ str_pad(
                                        $distrito,
                                        2,
                                        '0',
                                        STR_PAD_LEFT
                                    ) }}
                                </option>

                            @endforeach

                        </select>

                    @endif

                </div>


                <div class="col-6 col-lg-2">

                    <label class="form-label small fw-semibold">
                        Estado
                    </label>

                    <select
                        name="estado"
                        class="form-select form-select-sm"
                    >

                        <option value="">
                            Todos
                        </option>

                        <option
                            value="con_referente"
                            {{ $estado === 'con_referente' ? 'selected' : '' }}
                        >
                            Cubiertos
                        </option>

                        <option
                            value="sin_referente"
                            {{ $estado === 'sin_referente' ? 'selected' : '' }}
                        >
                            Pendientes
                        </option>

                        <option
                            value="activo"
                            {{ $estado === 'activo' ? 'selected' : '' }}
                        >
                            Activos
                        </option>

                        <option
                            value="inactivo"
                            {{ $estado === 'inactivo' ? 'selected' : '' }}
                        >
                            Inactivos
                        </option>

                    </select>

                </div>


                <div class="col-6 col-lg-1">

                    <button
                        type="submit"
                        class="btn btn-sm btn-primary w-100"
                    >
                        <i class="fa-solid fa-search"></i>
                    </button>

                </div>


                <div class="col-12">

                    <a
                        href="{{ route('avance_municipal.index') }}"
                        class="btn btn-sm btn-outline-secondary"
                    >
                        <i class="fa-solid fa-eraser me-1"></i>
                        Limpiar filtros
                    </a>

                </div>

            </form>

        </div>

    </div>


    <div class="avance-report-grid">


        <section class="municipal-map-card">

            <div class="municipal-ribbon">

                <i class="fa-solid fa-map me-2"></i>

                {{ $tituloTerritorio }}

            </div>


            @if($avance->isNotEmpty())

                <div class="municipal-map-body">

                    <div
                        id="avanceMunicipalMap"
                        aria-label="Mapa de avance municipal"
                    ></div>


                    <label
                        class="municipal-map-label-toggle"
                        for="toggleMunicipalityLabels"
                    >

                        <input
                            type="checkbox"
                            id="toggleMunicipalityLabels"
                        >

                        Municipios

                    </label>


                    <div class="municipal-map-legend">

                        <strong>
                            Cobertura:
                        </strong>

                        <span>
                            <i style="background:#2f9148"></i>
                            Referente activo
                        </span>

                        <span>
                            <i style="background:#b3262e"></i>
                            Sin referente
                        </span>

                        <span>
                            <i style="background:#747c84"></i>
                            Referente inactivo
                        </span>

                    </div>

                </div>

            @else

                <div class="municipal-map-empty">
                    No hay municipios para los filtros seleccionados.
                </div>

            @endif

        </section>


        <section class="municipal-table-card">

            <div class="municipal-ribbon">

                <i class="fa-solid fa-building-user me-2"></i>

                Cobertura municipal

            </div>


            <div class="municipal-table-scroll">

                <table class="table municipal-table">

                    <thead>

                        <tr>

                            <th>
                                Municipio
                            </th>

                            <th>
                                DL
                            </th>

                            <th>
                                DF
                            </th>

                            <th>
                                Secciones
                            </th>

                            <th>
                                Cobertura
                            </th>

                            <th>
                                Referente
                            </th>

                            <th>
                                Contacto
                            </th>

                            <th>
                                Estado
                            </th>

                            <th>
                                Acciones
                            </th>

                        </tr>

                    </thead>


                    <tbody>

                        @forelse($avance as $fila)

                            @php

                                $numeroWhatsApp = preg_replace(
                                    '/\D+/',
                                    '',
                                    (string)($fila['telefono'] ?? '')
                                );

                                if (
                                    strlen($numeroWhatsApp) === 13 &&
                                    substr(
                                        $numeroWhatsApp,
                                        0,
                                        3
                                    ) === '521'
                                ) {
                                    $numeroWhatsApp =
                                        '52'.substr(
                                            $numeroWhatsApp,
                                            3
                                        );
                                } elseif (
                                    strlen($numeroWhatsApp) === 10
                                ) {
                                    $numeroWhatsApp =
                                        '52'.$numeroWhatsApp;
                                }

                                $urlWhatsApp = (
                                    !empty($fila['whatsapp']) &&
                                    $numeroWhatsApp !== ''
                                )
                                    ? 'https://wa.me/'.$numeroWhatsApp
                                    : null;

                            @endphp


                            <tr>

                                <td class="municipio-cell">

                                    {{ $fila['municipio'] }}

                                    <span class="municipio-cve">
                                        CVE {{ $fila['cve_mun'] }}
                                    </span>

                                </td>


                                <td>
                                    {{ $fila['distritos_locales'] ?: '—' }}
                                </td>


                                <td>
                                    {{ $fila['distritos_federales'] ?: '—' }}
                                </td>


                                <td>
                                    {{ number_format($fila['secciones']) }}
                                </td>


                                <td>

                                    @if($fila['tiene_referente'])

                                        <span class="badge bg-success">
                                            <i class="fa-solid fa-check me-1"></i>
                                            Cubierto
                                        </span>

                                    @else

                                        <span class="badge bg-danger">
                                            <i class="fa-solid fa-xmark me-1"></i>
                                            Pendiente
                                        </span>

                                    @endif

                                </td>


                                <td class="referente-cell">

                                    @if($fila['tiene_referente'])

                                        <span class="referente-name">
                                            {{ $fila['nombre_completo'] }}
                                        </span>

                                        @if($fila['cargo'])

                                            <div class="referente-contact">
                                                {{ $fila['cargo'] }}
                                            </div>

                                        @endif

                                    @else

                                        <span class="missing-reference">
                                            Sin referente municipal
                                        </span>

                                    @endif

                                </td>


                                <td>

                                    @if($fila['telefono'])

                                        @if($urlWhatsApp)

                                            <a
                                                href="{{ $urlWhatsApp }}"
                                                target="_blank"
                                                rel="noopener noreferrer"
                                                class="text-success text-decoration-none fw-semibold"
                                            >
                                                <i class="fa-brands fa-whatsapp me-1"></i>
                                                {{ $fila['telefono'] }}
                                            </a>

                                        @else

                                            <span>
                                                <i class="fa-solid fa-phone me-1"></i>
                                                {{ $fila['telefono'] }}
                                            </span>

                                        @endif

                                    @else

                                        —

                                    @endif


                                    @if($fila['correo'])

                                        <div class="referente-contact">
                                            <i class="fa-solid fa-envelope me-1"></i>
                                            {{ $fila['correo'] }}
                                        </div>

                                    @endif

                                </td>


                                <td>

                                    @if(!$fila['tiene_referente'])

                                        <span class="badge bg-danger">
                                            Pendiente
                                        </span>

                                    @elseif($fila['activo'])

                                        <span class="badge bg-success">
                                            Activo
                                        </span>

                                    @else

                                        <span class="badge bg-secondary">
                                            Inactivo
                                        </span>

                                    @endif

                                </td>


                                <td>

                                    @if($fila['tiene_referente'])

                                        @can('referentes_municipales.ver')

                                            <a
                                                href="{{ route(
                                                    'referentes_municipales.show',
                                                    $fila['referente_id']
                                                ) }}"
                                                class="btn btn-sm btn-info"
                                                title="Ver referente"
                                            >
                                                <i class="fa-solid fa-eye"></i>
                                            </a>

                                        @endcan


                                        @can('referentes_municipales.editar')

                                            <a
                                                href="{{ route(
                                                    'referentes_municipales.edit',
                                                    $fila['referente_id']
                                                ) }}"
                                                class="btn btn-sm btn-success"
                                                title="Editar referente"
                                            >
                                                <i class="fa-solid fa-pen"></i>
                                            </a>

                                        @endcan

                                    @else

                                        @can('referentes_municipales.crear')

                                            <a
                                                href="{{ route(
                                                    'referentes_municipales.create'
                                                ) }}"
                                                class="btn btn-sm btn-outline-primary"
                                                title="Capturar referente"
                                            >
                                                <i class="fa-solid fa-plus"></i>
                                            </a>

                                        @endcan

                                    @endif

                                </td>

                            </tr>

                        @empty

                            <tr>

                                <td
                                    colspan="9"
                                    class="py-5 text-center text-muted"
                                >
                                    Sin información para mostrar.
                                </td>

                            </tr>

                        @endforelse

                    </tbody>

                </table>

            </div>

        </section>

    </div>

</div>


<div
    class="modal fade"
    id="modalFiltrosMunicipales"
    tabindex="-1"
    aria-hidden="true"
>

    <div class="modal-dialog modal-lg modal-dialog-centered">

        <div class="modal-content">

            <form
                method="GET"
                action="{{ route('avance_municipal.index') }}"
            >

                <div class="modal-header">

                    <h5 class="modal-title">

                        <i class="fa-solid fa-filter me-1"></i>

                        Filtros de avance municipal

                    </h5>

                    <button
                        class="btn-close"
                        type="button"
                        data-bs-dismiss="modal"
                    ></button>

                </div>


                <div class="modal-body">

                    <div class="row g-3">


                        <div class="col-md-6">

                            <label class="form-label">
                                Municipio
                            </label>

                            <select
                                name="cve_mun"
                                class="form-select"
                            >

                                <option value="">
                                    Todos
                                </option>

                                @foreach($municipiosFiltro as $municipio)

                                    <option
                                        value="{{ $municipio->cve_mun }}"
                                        {{ (string)$cveMun === (string)$municipio->cve_mun ? 'selected' : '' }}
                                    >
                                        {{ $municipio->municipio }}
                                    </option>

                                @endforeach

                            </select>

                        </div>


                        <div class="col-md-6">

                            <label class="form-label">
                                Distrito federal
                            </label>

                            <select
                                name="distrito_federal"
                                class="form-select"
                            >

                                <option value="">
                                    Todos
                                </option>

                                @foreach($distritosFederales as $distrito)

                                    <option
                                        value="{{ $distrito }}"
                                        {{ (string)$distritoFederal === (string)$distrito ? 'selected' : '' }}
                                    >
                                        Distrito Federal
                                        {{ str_pad(
                                            $distrito,
                                            2,
                                            '0',
                                            STR_PAD_LEFT
                                        ) }}
                                    </option>

                                @endforeach

                            </select>

                        </div>


                        <div class="col-md-6">

                            <label class="form-label">
                                Distrito local
                            </label>

                            @if(
                                $distritoLocalRestringido &&
                                count($distritosLocalesAsignados) === 1
                            )

                                <input
                                    type="hidden"
                                    name="distrito_local"
                                    value="{{ $distritoLocal }}"
                                >

                                <input
                                    type="text"
                                    class="form-control"
                                    value="Distrito local {{ str_pad(
                                        $distritoLocal,
                                        2,
                                        '0',
                                        STR_PAD_LEFT
                                    ) }} (asignado)"
                                    readonly
                                >

                            @else

                                <select
                                    name="distrito_local"
                                    class="form-select"
                                >

                                    @unless($distritoLocalRestringido)

                                        <option value="">
                                            Todos
                                        </option>

                                    @endunless

                                    @foreach($distritosLocales as $distrito)

                                        <option
                                            value="{{ $distrito }}"
                                            {{ (string)$distritoLocal === (string)$distrito ? 'selected' : '' }}
                                        >
                                            Distrito Local {{ $distrito }}
                                        </option>

                                    @endforeach

                                </select>

                            @endif

                        </div>


                        <div class="col-md-6">

                            <label class="form-label">
                                Cobertura
                            </label>

                            <select
                                name="estado"
                                class="form-select"
                            >

                                <option value="">
                                    Todos
                                </option>

                                <option
                                    value="con_referente"
                                    {{ $estado === 'con_referente' ? 'selected' : '' }}
                                >
                                    Con referente
                                </option>

                                <option
                                    value="sin_referente"
                                    {{ $estado === 'sin_referente' ? 'selected' : '' }}
                                >
                                    Sin referente
                                </option>

                                <option
                                    value="activo"
                                    {{ $estado === 'activo' ? 'selected' : '' }}
                                >
                                    Referente activo
                                </option>

                                <option
                                    value="inactivo"
                                    {{ $estado === 'inactivo' ? 'selected' : '' }}
                                >
                                    Referente inactivo
                                </option>

                            </select>

                        </div>


                        <div class="col-12">

                            <label class="form-label">
                                Buscar
                            </label>

                            <input
                                type="text"
                                name="q"
                                value="{{ $q }}"
                                class="form-control"
                                placeholder="Municipio, referente, teléfono..."
                            >

                        </div>

                    </div>

                </div>


                <div class="modal-footer">

                    <a
                        href="{{ route('avance_municipal.index') }}"
                        class="btn btn-outline-secondary"
                    >
                        Mostrar todo
                    </a>

                    <button
                        type="submit"
                        class="btn btn-granate"
                    >
                        <i class="fa-solid fa-check me-1"></i>
                        Aplicar
                    </button>

                </div>

            </form>

        </div>

    </div>

</div>

@endsection


@push('scripts')

@if($avance->isNotEmpty())

<script
    src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"
></script>

<script>

document.addEventListener(
    'DOMContentLoaded',
    function () {

        const municipalityCoverage =
            @json($coberturaMunicipios);

        const sectionMunicipalities =
            @json($municipioPorSeccion);

        const allowedMunicipalities =
            new Set(
                Object.keys(
                    municipalityCoverage
                )
            );

        const map = L.map(
            'avanceMunicipalMap',
            {
                attributionControl:false,
                zoomControl:false,
                dragging:false,
                scrollWheelZoom:false,
                doubleClickZoom:false,
                boxZoom:false,
                keyboard:false,
                tap:false
            }
        );

        const municipalityLabels =
            L.layerGroup();


        document
            .getElementById(
                'toggleMunicipalityLabels'
            )
            ?.addEventListener(
                'change',
                function () {

                    if (this.checked) {
                        municipalityLabels.addTo(
                            map
                        );
                    } else {
                        map.removeLayer(
                            municipalityLabels
                        );
                    }

                }
            );


        fetch(
            @json(
                asset(
                    'maps/out/SECCION.geojson'
                )
            )
        )
            .then(
                response =>
                    response.json()
            )
            .then(
                function (geojson) {

                    const features =
                        geojson.features.filter(
                            function (
                                feature
                            ) {

                                const section =
                                    String(
                                        Number(
                                            feature
                                                .properties
                                                ?.SECCION ?? 0
                                        )
                                    );

                                const municipio =
                                    sectionMunicipalities[
                                        section
                                    ];

                                return (
                                    municipio &&
                                    allowedMunicipalities
                                        .has(
                                            municipio
                                                .cve_mun
                                        )
                                );

                            }
                        );


                    const municipalityLayers =
                        {};


                    const layer = L.geoJSON(
                        {
                            type:
                                'FeatureCollection',

                            features:
                                features
                        },
                        {
                            interactive:
                                true,

                            style:
                                function (
                                    feature
                                ) {

                                    const section =
                                        String(
                                            Number(
                                                feature
                                                    .properties
                                                    ?.SECCION ?? 0
                                            )
                                        );

                                    const municipio =
                                        sectionMunicipalities[
                                            section
                                        ];

                                    const info =
                                        municipio
                                            ? municipalityCoverage[
                                                municipio
                                                    .cve_mun
                                            ]
                                            : null;

                                    let fillColor =
                                        '#b3262e';

                                    if (
                                        info &&
                                        info.cubierto &&
                                        info.activo
                                    ) {
                                        fillColor =
                                            '#2f9148';
                                    } else if (
                                        info &&
                                        info.cubierto &&
                                        !info.activo
                                    ) {
                                        fillColor =
                                            '#747c84';
                                    }

                                    return {
                                        color:
                                            '#6f7896',

                                        weight:
                                            .7,

                                        fillColor:
                                            fillColor,

                                        fillOpacity:
                                            .84
                                    };

                                },

                            onEachFeature:
                                function (
                                    feature,
                                    sectionLayer
                                ) {

                                    const section =
                                        String(
                                            Number(
                                                feature
                                                    .properties
                                                    ?.SECCION ?? 0
                                            )
                                        );

                                    const municipio =
                                        sectionMunicipalities[
                                            section
                                        ];

                                    if (
                                        !municipio
                                    ) {
                                        return;
                                    }

                                    const info =
                                        municipalityCoverage[
                                            municipio
                                                .cve_mun
                                        ];

                                    if (!info) {
                                        return;
                                    }


                                    let estado =
                                        'Sin referente';

                                    if (
                                        info.cubierto &&
                                        info.activo
                                    ) {
                                        estado =
                                            'Referente activo';
                                    } else if (
                                        info.cubierto
                                    ) {
                                        estado =
                                            'Referente inactivo';
                                    }


                                    let popup =
                                        '<strong>' +
                                        info.municipio +
                                        '</strong><br>' +
                                        'CVE ' +
                                        info.cve_mun +
                                        '<br>' +
                                        '<strong>Estado:</strong> ' +
                                        estado +
                                        '<br>' +
                                        '<strong>Secciones:</strong> ' +
                                        Number(
                                            info.secciones ||
                                            0
                                        ).toLocaleString(
                                            'es-MX'
                                        );

                                    if (
                                        info.referente
                                    ) {
                                        popup +=
                                            '<br><strong>Referente:</strong> ' +
                                            info.referente;
                                    }

                                    if (
                                        info.telefono
                                    ) {
                                        popup +=
                                            '<br><strong>Teléfono:</strong> ' +
                                            info.telefono;
                                    }


                                    sectionLayer
                                        .bindPopup(
                                            popup
                                        );


                                    sectionLayer.on(
                                        'mouseover',
                                        function () {

                                            this.setStyle({
                                                weight:
                                                    1.6,

                                                fillOpacity:
                                                    1
                                            });

                                            this.bringToFront();

                                        }
                                    );


                                    sectionLayer.on(
                                        'mouseout',
                                        function () {

                                            layer.resetStyle(
                                                this
                                            );

                                        }
                                    );


                                    const key =
                                        municipio.cve_mun;

                                    if (
                                        !municipalityLayers[
                                            key
                                        ]
                                    ) {
                                        municipalityLayers[
                                            key
                                        ] = {
                                            name:
                                                municipio
                                                    .municipio,

                                            layers:
                                                []
                                        };
                                    }

                                    municipalityLayers[
                                        key
                                    ].layers.push(
                                        sectionLayer
                                    );

                                }
                        }
                    ).addTo(map);


                    if (
                        layer
                            .getBounds()
                            .isValid()
                    ) {
                        map.fitBounds(
                            layer.getBounds(),
                            {
                                padding:
                                    [18,18]
                            }
                        );
                    }


                    Object
                        .values(
                            municipalityLayers
                        )
                        .forEach(
                            function (
                                municipality
                            ) {

                                const bounds =
                                    L.featureGroup(
                                        municipality
                                            .layers
                                    ).getBounds();

                                if (
                                    !bounds
                                        .isValid()
                                ) {
                                    return;
                                }

                                L.marker(
                                    bounds
                                        .getCenter(),
                                    {
                                        interactive:
                                            false,

                                        icon:
                                            L.divIcon({
                                                className:
                                                    'municipal-map-label',

                                                html:
                                                    '<span>' +
                                                    municipality
                                                        .name +
                                                    '</span>',

                                                iconSize:
                                                    null
                                            })
                                    }
                                ).addTo(
                                    municipalityLabels
                                );

                            }
                        );

                }
            )
            .catch(
                function () {

                    document
                        .getElementById(
                            'avanceMunicipalMap'
                        )
                        .innerHTML =
                        '<div class="municipal-map-empty">' +
                        'No fue posible cargar el mapa electoral.' +
                        '</div>';

                }
            );

    }
);

</script>

@endif

@endpush
