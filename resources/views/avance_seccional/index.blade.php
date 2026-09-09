@extends('layouts.app')

@section('title', 'Avance seccional')

@push('css')
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">

<style>
    .avance-seccional-page {
        --azul-reporte:#1d3376;
        --gris-reporte:#eef1eb;
        --rosa-reporte:#d91785;
        padding-bottom:2.5rem;
    }

    .avance-seccional-page .report-actions .btn {
        border-radius:.45rem;
    }

    .avance-periodo {
        color:#667085;
        font-size:.82rem;
    }

    .avance-summary-wrap {
        border-radius:.35rem;
        box-shadow:0 8px 22px rgba(22,39,93,.08);
        overflow-x:auto;
        margin-bottom:1.15rem;
    }

    .avance-summary {
        display:grid;
        grid-template-columns:repeat(9,1fr);
        min-width:1150px;
    }

    .avance-summary-head,
    .avance-summary-value {
        align-items:center;
        display:flex;
        justify-content:center;
        text-align:center;
    }

    .avance-summary-head {
        background:var(--azul-reporte);
        border-right:1px solid rgba(255,255,255,.28);
        color:#fff;
        font-size:.66rem;
        font-weight:700;
        line-height:1.15;
        min-height:54px;
        padding:.5rem;
        text-transform:uppercase;
    }

    .avance-summary-value {
        background:var(--gris-reporte);
        border-right:1px solid #fff;
        color:#283044;
        font-size:.78rem;
        font-weight:700;
        min-height:36px;
        padding:.35rem .5rem;
    }

    .avance-summary-value.pct {
        color:#fff;
        font-weight:800;
    }

    .pct-success {
        background:#2f9148 !important;
    }

    .pct-warning {
        background:#d8ab00 !important;
        color:#3b3000 !important;
    }

    .pct-danger {
        background:#b3262e !important;
    }

    .avance-report-grid {
        display:grid;
        gap:1.1rem;
        grid-template-columns:minmax(320px,38%) minmax(620px,62%);
        margin-bottom:1.15rem;
    }

    .district-map-card,
    .district-table-card,
    .section-table-card {
        background:#fff;
        border:1px solid #dfe4ee;
        border-radius:1rem;
        box-shadow:0 8px 24px rgba(22,39,93,.08);
        overflow:hidden;
    }

    .district-ribbon {
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

    .district-map-body {
        position:relative;
    }

    #avanceSeccionalMap {
        background:#f8f9f4;
        height:560px;
        width:100%;
    }

    #avanceSeccionalMap .leaflet-control-container {
        display:none;
    }

    .district-map-empty {
        align-items:center;
        color:#667085;
        display:flex;
        height:560px;
        justify-content:center;
        padding:2rem;
        text-align:center;
    }

    .avance-map-legend {
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

    .avance-map-legend span {
        align-items:center;
        display:flex;
        gap:.22rem;
    }

    .avance-map-legend i {
        border:1px solid rgba(0,0,0,.15);
        display:inline-block;
        height:10px;
        width:14px;
    }

    .avance-map-label-toggle {
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

    .avance-map-label-toggle input {
        margin:0;
    }

    .avance-map-label {
        background:transparent;
        border:0;
    }

    .avance-map-label span {
        color:#344268;
        font-size:9px;
        font-weight:700;
        line-height:1;
        text-shadow:
            0 0 3px #fff,
            0 0 5px #fff;
        white-space:nowrap;
    }

    .municipality-table-scroll {
        max-height:560px;
        overflow:auto;
    }

    .municipality-table {
        margin:0;
        min-width:650px;
    }

    .municipality-table thead {
        position:sticky;
        top:0;
        z-index:3;
    }

    .municipality-table thead th,
    .section-table thead th {
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

    .municipality-table tbody td,
    .section-table tbody td {
        border-color:#edf0f3;
        color:#303849;
        font-size:.7rem;
        line-height:1.15;
        padding:.5rem .38rem;
        text-align:center;
        vertical-align:middle;
    }

    .municipality-table tbody tr:nth-child(even) td,
    .section-table tbody tr:nth-child(even) td {
        background:#f7f8f4;
    }

    .municipality-table .municipio-cell,
    .section-table .municipio-cell,
    .section-table .referente-cell {
        text-align:left;
    }

    .municipality-table .municipio-cell {
        font-weight:700;
    }

    .municipality-progress {
        background:#e8ebf1;
        border-radius:999px;
        height:8px;
        margin-top:.25rem;
        overflow:hidden;
        width:100%;
    }

    .municipality-progress-bar {
        background:var(--rosa-reporte);
        height:100%;
    }

    .section-table-scroll {
        max-height:650px;
        overflow:auto;
    }

    .section-table {
        margin:0;
        min-width:1300px;
    }

    .section-number {
        font-size:.76rem;
        font-weight:800;
    }

    .referente-name {
        font-weight:700;
    }

    .missing-reference {
        color:#b3262e;
        font-weight:700;
    }

    .contact-small {
        color:#667085;
        font-size:.64rem;
    }

    .filters-card {
        border:0;
        box-shadow:0 5px 16px rgba(22,39,93,.07);
    }

    @media (max-width:1199.98px) {
        .avance-report-grid {
            grid-template-columns:1fr;
        }

        #avanceSeccionalMap {
            height:430px;
        }

        .municipality-table-scroll,
        .section-table-scroll {
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

        #avanceSeccionalMap {
            height:500px;
        }

        .municipality-table-scroll,
        .section-table-scroll {
            max-height:none;
            overflow:visible;
        }
    }
</style>
@endpush


@section('content')

<div class="container-fluid px-xl-4 avance-seccional-page">

    @php

        $dfTexto = $distritoFederal !== ''
            ? str_pad($distritoFederal, 2, '0', STR_PAD_LEFT)
            : 'Todos';

        $dlTexto = $distritoLocal !== ''
            ? str_pad($distritoLocal, 2, '0', STR_PAD_LEFT)
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
            $municipioSeleccionado = $municipios
                ->firstWhere('cve_mun', $cveMun);

            if ($municipioSeleccionado) {
                $tituloTerritorio .=
                    ' · '.$municipioSeleccionado->municipio;
            }
        }

        if ($seccion !== '') {
            $tituloTerritorio .=
                ' · Sección '.str_pad(
                    $seccion,
                    4,
                    '0',
                    STR_PAD_LEFT
                );
        }

        $porcentajeCobertura =
            (float)($totales['porcentaje_cobertura'] ?? 0);

        $porcentajeListaNominal =
            (float)(
                $totales[
                    'porcentaje_lista_nominal_cubierta'
                ] ?? 0
            );

        $pctClass = function ($porcentaje) {
            if ($porcentaje >= 80) {
                return 'pct-success';
            }

            if ($porcentaje >= 30) {
                return 'pct-warning';
            }

            return 'pct-danger';
        };

        $seccionesMapa = $avance
            ->mapWithKeys(function ($fila) {
                return [
                    (string)(int)$fila['seccion'] => [
                        'cve_mun' =>
                            $fila['cve_mun'],

                        'municipio' =>
                            $fila['municipio'],

                        'seccion' =>
                            $fila['seccion'],

                        'cubierta' =>
                            $fila['tiene_referente'],

                        'activo' =>
                            $fila['referente_activo'],

                        'referente' =>
                            $fila['nombre_completo'],

                        'lista_nominal' =>
                            $fila['lista_nominal'],
                    ],
                ];
            });

    @endphp


    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">

        <div>

            <h1 class="h5 fw-bold mb-1 text-uppercase">
                Avance seccional
            </h1>

            <div class="avance-periodo">
                {{ $tituloTerritorio }}
                · Cobertura de referentes seccionales
            </div>

        </div>


        <div class="report-actions d-flex flex-wrap gap-2">

            <button
                type="button"
                class="btn btn-sm btn-outline-secondary"
                data-bs-toggle="modal"
                data-bs-target="#modalFiltrosSeccionales"
            >
                <i class="fa-solid fa-filter me-1"></i>
                Filtros
            </button>


            @can('referentes_seccionales.ver')

                <a
                    href="{{ route('referentes_seccionales.index') }}"
                    class="btn btn-sm btn-outline-primary"
                >
                    <i class="fa-solid fa-map-location-dot me-1"></i>
                    Ver referentes
                </a>

            @endcan


            @can('referentes_seccionales.crear')

                <a
                    href="{{ route('referentes_seccionales.create') }}"
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


    <div class="avance-summary-wrap">

        <div class="avance-summary">

            @foreach([
                'Secciones',
                'Con referente',
                'Sin referente',
                'Activos',
                'Inactivos',
                '% cobertura',
                'Lista nominal',
                'Lista nominal cubierta',
                '% LN cubierta'
            ] as $encabezado)

                <div class="avance-summary-head">
                    {{ $encabezado }}
                </div>

            @endforeach


            <div class="avance-summary-value">
                {{ number_format($totales['secciones']) }}
            </div>

            <div class="avance-summary-value">
                {{ number_format($totales['con_referente']) }}
            </div>

            <div class="avance-summary-value">
                {{ number_format($totales['sin_referente']) }}
            </div>

            <div class="avance-summary-value">
                {{ number_format($totales['activos']) }}
            </div>

            <div class="avance-summary-value">
                {{ number_format($totales['inactivos']) }}
            </div>

            <div
                class="avance-summary-value pct {{ $pctClass($porcentajeCobertura) }}"
            >
                {{ number_format($porcentajeCobertura, 2) }}%
            </div>

            <div class="avance-summary-value">
                {{ number_format($totales['lista_nominal']) }}
            </div>

            <div class="avance-summary-value">
                {{ number_format($totales['lista_nominal_cubierta']) }}
            </div>

            <div
                class="avance-summary-value pct {{ $pctClass($porcentajeListaNominal) }}"
            >
                {{ number_format($porcentajeListaNominal, 2) }}%
            </div>

        </div>

    </div>


    <div class="card filters-card mb-3">

        <div class="card-body">

            <form
                method="GET"
                action="{{ route('avance_seccional.index') }}"
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
                        placeholder="Municipio, sección, referente..."
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

                        @foreach($municipios as $municipio)

                            <option
                                value="{{ $municipio->cve_mun }}"
                                {{ (string)$cveMun === (string)$municipio->cve_mun ? 'selected' : '' }}
                            >
                                {{ $municipio->municipio }}
                            </option>

                        @endforeach

                    </select>

                </div>


                <div class="col-6 col-lg-1">

                    <label class="form-label small fw-semibold">
                        Sección
                    </label>

                    <input
                        type="text"
                        name="seccion"
                        value="{{ $seccion }}"
                        class="form-control form-control-sm"
                        inputmode="numeric"
                        placeholder="0000"
                    >

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
                                DF {{ str_pad($distrito, 2, '0', STR_PAD_LEFT) }}
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
                            value="DL {{ str_pad($distritoLocal, 2, '0', STR_PAD_LEFT) }}"
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
                                    DL {{ str_pad($distrito, 2, '0', STR_PAD_LEFT) }}
                                </option>

                            @endforeach

                        </select>

                    @endif

                </div>


                <div class="col-6 col-lg-1">

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
                            Cubiertas
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
                        href="{{ route('avance_seccional.index') }}"
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


        <section class="district-map-card">

            <div class="district-ribbon">

                <i class="fa-solid fa-map me-2"></i>

                {{ $tituloTerritorio }}

            </div>


            @if($avance->isNotEmpty())

                <div class="district-map-body">

                    <div
                        id="avanceSeccionalMap"
                        aria-label="Mapa de avance seccional"
                    ></div>


                    <label
                        class="avance-map-label-toggle"
                        for="toggleMunicipalityLabels"
                    >

                        <input
                            type="checkbox"
                            id="toggleMunicipalityLabels"
                        >

                        Municipios

                    </label>


                    <div class="avance-map-legend">

                        <strong>
                            Cobertura:
                        </strong>

                        <span>
                            <i style="background:#2f9148"></i>
                            Con referente
                        </span>

                        <span>
                            <i style="background:#b3262e"></i>
                            Sin referente
                        </span>

                        <span>
                            <i style="background:#747c84"></i>
                            Inactivo
                        </span>

                    </div>

                </div>

            @else

                <div class="district-map-empty">
                    No hay secciones para los filtros seleccionados.
                </div>

            @endif

        </section>


        <section class="district-table-card">

            <div class="district-ribbon">

                <i class="fa-solid fa-city me-2"></i>

                Cobertura por municipio

            </div>


            <div class="municipality-table-scroll">

                <table class="table municipality-table">

                    <thead>

                        <tr>

                            <th>
                                Municipio
                            </th>

                            <th>
                                Secciones
                            </th>

                            <th>
                                Cubiertas
                            </th>

                            <th>
                                Pendientes
                            </th>

                            <th>
                                %
                            </th>

                        </tr>

                    </thead>


                    <tbody>

                        @forelse($avancePorMunicipio as $fila)

                            <tr>

                                <td class="municipio-cell">

                                    {{ $fila['municipio'] }}

                                    <div class="text-muted small">
                                        CVE {{ $fila['cve_mun'] }}
                                    </div>

                                </td>


                                <td>
                                    {{ number_format($fila['secciones']) }}
                                </td>


                                <td>
                                    <span class="badge bg-success">
                                        {{ number_format($fila['cubiertas']) }}
                                    </span>
                                </td>


                                <td>

                                    @if($fila['pendientes'] > 0)

                                        <span class="badge bg-danger">
                                            {{ number_format($fila['pendientes']) }}
                                        </span>

                                    @else

                                        <span class="badge bg-success">
                                            0
                                        </span>

                                    @endif

                                </td>


                                <td style="min-width:130px">

                                    <strong>
                                        {{ number_format($fila['porcentaje'], 2) }}%
                                    </strong>

                                    <div class="municipality-progress">

                                        <div
                                            class="municipality-progress-bar"
                                            style="width:{{ min(100, max(0, $fila['porcentaje'])) }}%"
                                        ></div>

                                    </div>

                                </td>

                            </tr>

                        @empty

                            <tr>

                                <td
                                    colspan="5"
                                    class="py-5 text-muted"
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


    <section class="section-table-card">

        <div class="district-ribbon">

            <i class="fa-solid fa-list-ol me-2"></i>

            Detalle por sección

        </div>


        <div class="section-table-scroll">

            <table class="table section-table">

                <thead>

                    <tr>

                        <th>
                            DL / DF
                        </th>

                        <th>
                            Municipio
                        </th>

                        <th>
                            Sección
                        </th>

                        <th>
                            Lista nominal
                        </th>

                        <th>
                            Cobertura
                        </th>

                        <th>
                            Referente seccional
                        </th>

                        <th>
                            Contacto
                        </th>

                        <th>
                            Cargo / Organización
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
                                substr($numeroWhatsApp, 0, 3) === '521'
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

                            <td>

                                {{ $fila['distrito_local'] !== null
                                    ? str_pad(
                                        $fila['distrito_local'],
                                        2,
                                        '0',
                                        STR_PAD_LEFT
                                    )
                                    : '—'
                                }}

                                /

                                {{ $fila['distrito_federal'] !== null
                                    ? str_pad(
                                        $fila['distrito_federal'],
                                        2,
                                        '0',
                                        STR_PAD_LEFT
                                    )
                                    : '—'
                                }}

                            </td>


                            <td class="municipio-cell">

                                <strong>
                                    {{ $fila['municipio'] }}
                                </strong>

                                <div class="text-muted small">
                                    CVE {{ $fila['cve_mun'] }}
                                </div>

                            </td>


                            <td>

                                <span class="badge bg-primary section-number">
                                    {{ $fila['seccion_formateada'] }}
                                </span>

                            </td>


                            <td>
                                {{ number_format($fila['lista_nominal']) }}
                            </td>


                            <td>

                                @if($fila['cupo_completo'])

                                    <span class="badge bg-success">

                                        <i class="fa-solid fa-check me-1"></i>

                                        Completa 2/2

                                    </span>

                                @elseif($fila['tiene_referente'])

                                    <span class="badge bg-warning text-dark">

                                        <i class="fa-solid fa-user-plus me-1"></i>

                                        Parcial 1/2

                                    </span>

                                @else

                                    <span class="badge bg-danger">

                                        <i class="fa-solid fa-xmark me-1"></i>

                                        Pendiente 0/2

                                    </span>

                                @endif

                            </td>


                            <td class="referente-cell">

                                @forelse($fila['referentes'] as $referenteFila)

                                    <div class="referente-name mb-1">
                                        {{ $referenteFila->posicion }}.
                                        {{ $referenteFila->nombre_completo }}
                                    </div>

                                @empty

                                    <span class="missing-reference">
                                        Sin referente seccional
                                    </span>

                                @endforelse

                            </td>


                            <td>

                                @forelse($fila['referentes'] as $referenteFila)

                                    @php
                                        $numeroReferente = preg_replace(
                                            '/\D+/',
                                            '',
                                            (string)($referenteFila->telefono ?? '')
                                        );

                                        if (
                                            strlen($numeroReferente) === 13 &&
                                            substr($numeroReferente, 0, 3) === '521'
                                        ) {
                                            $numeroReferente = '52'.substr($numeroReferente, 3);
                                        } elseif (strlen($numeroReferente) === 10) {
                                            $numeroReferente = '52'.$numeroReferente;
                                        }

                                        $whatsAppReferente = (
                                            $referenteFila->whatsapp &&
                                            $numeroReferente !== ''
                                        ) ? 'https://wa.me/'.$numeroReferente : null;
                                    @endphp

                                    <div class="mb-2">

                                    @if($referenteFila->telefono)

                                        @if($whatsAppReferente)

                                        <a
                                            href="{{ $whatsAppReferente }}"
                                            target="_blank"
                                            rel="noopener noreferrer"
                                            class="text-success text-decoration-none fw-semibold"
                                            title="Enviar mensaje por WhatsApp"
                                        >

                                            <i class="fa-brands fa-whatsapp me-1"></i>

                                            {{ $referenteFila->telefono }}

                                        </a>

                                    @else

                                        <span>

                                            <i class="fa-solid fa-phone me-1"></i>

                                            {{ $referenteFila->telefono }}

                                        </span>

                                    @endif

                                    @else

                                        —

                                    @endif


                                @if($referenteFila->correo)

                                    <div class="contact-small mt-1">

                                        <i class="fa-solid fa-envelope me-1"></i>

                                        {{ $referenteFila->correo }}

                                    </div>

                                @endif

                                    </div>

                                @empty

                                    —

                                @endforelse

                            </td>


                            <td>

                                @forelse($fila['referentes'] as $referenteFila)

                                    <div class="mb-2">

                                        @if($referenteFila->cargo)

                                            <div>{{ $referenteFila->cargo }}</div>

                                        @endif

                                        @if($referenteFila->organizacion)

                                            <div class="text-muted small">
                                                {{ $referenteFila->organizacion }}
                                            </div>

                                        @endif

                                        @if(
                                            !$referenteFila->cargo &&
                                            !$referenteFila->organizacion
                                        )

                                            —

                                        @endif

                                    </div>

                                @empty

                                    —

                                @endforelse

                            </td>


                            <td>

                                @forelse($fila['referentes'] as $referenteFila)

                                    <div class="mb-1">

                                        @if($referenteFila->activo)

                                            <span class="badge bg-success">
                                                {{ $referenteFila->posicion }}. Activo
                                            </span>

                                        @else

                                            <span class="badge bg-secondary">
                                                {{ $referenteFila->posicion }}. Inactivo
                                            </span>

                                        @endif

                                    </div>

                                @empty

                                    <span class="badge bg-danger">Pendiente</span>

                                @endforelse

                            </td>


                            <td>

                                @foreach($fila['referentes'] as $referenteFila)

                                    <div class="mb-1 text-nowrap">

                                    @can('referentes_seccionales.ver')

                                        <a
                                            href="{{ route(
                                                'referentes_seccionales.show',
                                                $referenteFila->id
                                            ) }}"
                                            class="btn btn-sm btn-info"
                                            title="Ver referente"
                                        >
                                            <i class="fa-solid fa-eye"></i>
                                        </a>

                                    @endcan


                                    @can('referentes_seccionales.editar')

                                        <a
                                            href="{{ route(
                                                'referentes_seccionales.edit',
                                                $referenteFila->id
                                            ) }}"
                                            class="btn btn-sm btn-success"
                                            title="Editar referente"
                                        >
                                            <i class="fa-solid fa-pen"></i>
                                        </a>

                                    @endcan

                                    </div>

                                @endforeach


                                @if($fila['cantidad_referentes'] < 2)

                                    @can('referentes_seccionales.crear')

                                        <a
                                            href="{{ route(
                                                'referentes_seccionales.create'
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
                                colspan="10"
                                class="py-5 text-center text-muted"
                            >
                                No hay secciones para los filtros seleccionados.
                            </td>

                        </tr>

                    @endforelse

                </tbody>

            </table>

        </div>

    </section>

</div>


<div
    class="modal fade"
    id="modalFiltrosSeccionales"
    tabindex="-1"
    aria-hidden="true"
>

    <div class="modal-dialog modal-lg modal-dialog-centered">

        <div class="modal-content">

            <form
                method="GET"
                action="{{ route('avance_seccional.index') }}"
            >

                <div class="modal-header">

                    <h5 class="modal-title">

                        <i class="fa-solid fa-filter me-1"></i>

                        Filtros de avance seccional

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

                                @foreach($municipios as $municipio)

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
                                Sección
                            </label>

                            <input
                                type="text"
                                name="seccion"
                                value="{{ $seccion }}"
                                class="form-control"
                                inputmode="numeric"
                                placeholder="Número de sección"
                            >

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


                        <div class="col-md-6">

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
                        href="{{ route('avance_seccional.index') }}"
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

<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>

<script>

document.addEventListener('DOMContentLoaded', function () {

    const sectionStatus = @json($seccionesMapa);

    const allowedSections = new Set(
        Object.keys(sectionStatus)
    );

    const map = L.map(
        'avanceSeccionalMap',
        {
            attributionControl: false,
            zoomControl: false,
            dragging: false,
            scrollWheelZoom: false,
            doubleClickZoom: false,
            boxZoom: false,
            keyboard: false,
            tap: false
        }
    );

    const municipalityLabels =
        L.layerGroup();


    document
        .getElementById('toggleMunicipalityLabels')
        ?.addEventListener(
            'change',
            function () {

                if (this.checked) {
                    municipalityLabels.addTo(map);
                } else {
                    map.removeLayer(
                        municipalityLabels
                    );
                }

            }
        );


    fetch(
        @json(asset('maps/out/SECCION.geojson'))
    )
        .then(
            response => response.json()
        )
        .then(function (geojson) {

            const features =
                geojson.features.filter(
                    function (feature) {

                        const section =
                            String(
                                Number(
                                    feature.properties
                                        ?.SECCION ?? 0
                                )
                            );

                        return allowedSections.has(
                            section
                        );

                    }
                );


            const municipalityLayers = {};


            const layer = L.geoJSON(
                {
                    type: 'FeatureCollection',
                    features: features
                },
                {
                    interactive: true,

                    style: function (feature) {

                        const section =
                            String(
                                Number(
                                    feature.properties
                                        ?.SECCION ?? 0
                                )
                            );

                        const info =
                            sectionStatus[section];

                        let fillColor =
                            '#b3262e';

                        if (
                            info &&
                            info.cubierta &&
                            info.activo
                        ) {
                            fillColor =
                                '#2f9148';
                        } else if (
                            info &&
                            info.cubierta &&
                            !info.activo
                        ) {
                            fillColor =
                                '#747c84';
                        }

                        return {
                            color: '#6f7896',
                            weight: .7,
                            fillColor: fillColor,
                            fillOpacity: .84
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
                                        feature.properties
                                            ?.SECCION ?? 0
                                    )
                                );

                            const info =
                                sectionStatus[
                                    section
                                ];

                            if (!info) {
                                return;
                            }


                            let estado =
                                'Sin referente';

                            if (
                                info.cubierta &&
                                info.activo
                            ) {
                                estado =
                                    'Con referente activo';
                            } else if (
                                info.cubierta
                            ) {
                                estado =
                                    'Referente inactivo';
                            }


                            sectionLayer.bindPopup(
                                '<strong>Sección ' +
                                String(section)
                                    .padStart(
                                        4,
                                        '0'
                                    ) +
                                '</strong><br>' +
                                info.municipio +
                                '<br>' +
                                '<strong>Estado:</strong> ' +
                                estado +
                                '<br>' +
                                (
                                    info.referente
                                        ? '<strong>Referente:</strong> ' +
                                          info.referente +
                                          '<br>'
                                        : ''
                                ) +
                                '<strong>Lista nominal:</strong> ' +
                                Number(
                                    info.lista_nominal || 0
                                ).toLocaleString(
                                    'es-MX'
                                )
                            );


                            sectionLayer.on(
                                'mouseover',
                                function () {

                                    this.setStyle({
                                        weight: 1.6,
                                        fillOpacity: 1
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


                            const municipioKey =
                                info.cve_mun;

                            if (
                                !municipalityLayers[
                                    municipioKey
                                ]
                            ) {
                                municipalityLayers[
                                    municipioKey
                                ] = {
                                    name:
                                        info.municipio,
                                    layers: []
                                };
                            }

                            municipalityLayers[
                                municipioKey
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
                        padding: [18, 18]
                    }
                );
            }


            Object
                .values(
                    municipalityLayers
                )
                .forEach(
                    function (municipality) {

                        const bounds =
                            L.featureGroup(
                                municipality.layers
                            ).getBounds();

                        if (
                            !bounds.isValid()
                        ) {
                            return;
                        }

                        L.marker(
                            bounds.getCenter(),
                            {
                                interactive:
                                    false,

                                icon:
                                    L.divIcon({
                                        className:
                                            'avance-map-label',

                                        html:
                                            '<span>' +
                                            municipality.name +
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

        })
        .catch(function () {

            document
                .getElementById(
                    'avanceSeccionalMap'
                )
                .innerHTML =
                '<div class="district-map-empty">' +
                'No fue posible cargar el mapa electoral.' +
                '</div>';

        });

});

</script>

@endif

@endpush
