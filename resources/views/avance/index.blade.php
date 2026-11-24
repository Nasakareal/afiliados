@extends('layouts.app')

@section('title', 'Avance Morelia')

@push('css')
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
<style>
  .avance-page { --azul-reporte:#1d3376; --gris-reporte:#eef1eb; --rosa-reporte:#d91785; padding-bottom:2.5rem; }
  .avance-page .report-actions .btn { border-radius:.45rem; }
  .district-quick-filter { align-items:center; display:flex; gap:.45rem; }
  .district-quick-filter .form-select { min-width:175px; }
  .avance-periodo { color:#667085; font-size:.82rem; }
  .morelia-hero { background:linear-gradient(135deg,#1d3376 0%,#314e9b 100%); border-radius:1rem; color:#fff; margin-bottom:1rem; padding:1.1rem 1.25rem; }
  .morelia-hero h1 { font-size:1.25rem; }
  .morelia-hero p { color:rgba(255,255,255,.82); font-size:.86rem; margin:0; }
  .priority-overview { display:grid; gap:.65rem; grid-template-columns:repeat(4,minmax(110px,1fr)); margin-top:.9rem; }
  .priority-stat { background:rgba(255,255,255,.12); border:1px solid rgba(255,255,255,.18); border-radius:.7rem; padding:.65rem .8rem; }
  .priority-stat strong { display:block; font-size:1.25rem; line-height:1; }
  .priority-stat span { color:rgba(255,255,255,.78); font-size:.7rem; }
  .simple-filter { align-items:end; background:#fff; border:1px solid #dfe4ee; border-radius:.8rem; display:flex; flex-wrap:wrap; gap:.65rem; margin-bottom:1rem; padding:.8rem; }
  .simple-filter label { color:#475467; display:block; font-size:.7rem; font-weight:700; margin-bottom:.2rem; }

  .avance-summary {
    display:grid;
    grid-template-columns:.65fr .75fr 1.1fr repeat(6, 1fr);
    margin-bottom:1.15rem;
    min-width:1050px;
  }
  .avance-summary-wrap { border-radius:.35rem; box-shadow:0 8px 22px rgba(22,39,93,.08); overflow-x:auto; }
  .avance-summary-head,
  .avance-summary-value { align-items:center; display:flex; justify-content:center; text-align:center; }
  .avance-summary-head {
    background:var(--azul-reporte); border-right:1px solid rgba(255,255,255,.28);
    color:#fff; font-size:.68rem; font-weight:700; line-height:1.15; min-height:54px;
    padding:.5rem; text-transform:uppercase;
  }
  .avance-summary-value {
    background:var(--gris-reporte); border-right:1px solid #fff; color:#283044;
    font-size:.78rem; min-height:32px; padding:.35rem .5rem;
  }
  .avance-summary-value.pct { color:#fff; font-weight:800; }

  .avance-report-grid { display:grid; gap:1.1rem; grid-template-columns:minmax(300px, 36%) minmax(650px, 64%); }
  .district-map-card,
  .district-table-card { background:#fff; border:1px solid #dfe4ee; border-radius:1rem; box-shadow:0 8px 24px rgba(22,39,93,.08); overflow:hidden; }
  .district-ribbon {
    align-items:center; background:var(--azul-reporte); color:#fff; display:flex;
    font-size:.75rem; font-weight:800; letter-spacing:.025em; min-height:38px;
    padding:.55rem 1.1rem; text-transform:uppercase;
  }
  #avanceDistrictMap { background:#f8f9f4; height:560px; width:100%; }
  #avanceDistrictMap .leaflet-control-container { display:none; }
  .avance-map-legend { align-items:center; background:#fff; border:1px solid #dfe4ee; border-radius:.45rem; bottom:.7rem; box-shadow:0 2px 8px rgba(22,39,93,.16); display:flex; flex-wrap:wrap; font-size:.66rem; gap:.45rem; left:.7rem; padding:.42rem .55rem; position:absolute; z-index:500; }
  .avance-map-legend span { align-items:center; display:flex; gap:.22rem; }
  .avance-map-legend i { border:1px solid rgba(0,0,0,.15); display:inline-block; height:10px; width:14px; }
  .avance-map-label-toggle { align-items:center; background:#fff; border:1px solid #dfe4ee; border-radius:.45rem; box-shadow:0 2px 8px rgba(22,39,93,.16); cursor:pointer; display:flex; font-size:.7rem; gap:.35rem; padding:.42rem .55rem; position:absolute; right:.7rem; top:.7rem; z-index:500; }
  .avance-map-label-toggle input { margin:0; }
  .district-map-body { position:relative; }
  .district-map-empty { align-items:center; color:#667085; display:flex; height:560px; justify-content:center; padding:2rem; text-align:center; }
  .avance-map-label { background:transparent; border:0; }
  .avance-map-label span {
    color:#344268; font-size:9px; font-weight:700; line-height:1; text-shadow:0 0 3px #fff,0 0 5px #fff;
    white-space:nowrap;
  }

  .district-table-scroll { max-height:560px; overflow:auto; }
  .district-table { margin:0; min-width:980px; }
  .district-table thead { position:sticky; top:0; z-index:3; }
  .district-table thead th {
    background:var(--azul-reporte); border-color:rgba(255,255,255,.25); color:#fff;
    font-size:.62rem; font-weight:700; line-height:1.12; padding:.65rem .35rem;
    text-align:center; text-transform:uppercase; vertical-align:middle;
  }
  .district-table tbody td {
    border-color:#edf0f3; color:#303849; font-size:.69rem; line-height:1.12;
    padding:.5rem .34rem; text-align:center; vertical-align:middle;
  }
  .district-table tbody tr:nth-child(even) td:not(.pct-cell) { background:#f7f8f4; }
  .district-table .municipio-cell { font-weight:700; text-align:left; }
  .district-table .municipio-cell-wrap { align-items:center; display:flex; gap:.4rem; justify-content:space-between; }
  .district-table .btn-secciones-municipio { font-size:.6rem; padding:.2rem .38rem; white-space:nowrap; }
  .district-table .edit-meta { color:#9b001f; padding:0 .2rem; }
  .district-table .define-meta { font-size:.64rem; padding:.18rem .38rem; white-space:nowrap; }
  .district-table .pct-cell { color:#fff; font-weight:800; min-width:76px; }
  .pct-success { background:#2f9148 !important; }
  .pct-warning { background:#d8ab00 !important; color:#3b3000 !important; }
  .pct-danger { background:#b3262e !important; }
  .pct-empty { background:#747c84 !important; }
  .ranking-grid { display:grid; gap:1rem; grid-template-columns:repeat(2,minmax(0,1fr)); margin:1rem 0 2rem; }
  .ranking-card { background:#fff; border:1px solid #dfe4ee; border-radius:.8rem; box-shadow:0 8px 24px rgba(22,39,93,.08); overflow:hidden; }
  .ranking-list { list-style:none; margin:0; padding:0; }
  .ranking-list li { align-items:center; border-bottom:1px solid #edf0f3; display:grid; gap:.7rem; grid-template-columns:30px 1fr auto; padding:.65rem .9rem; }
  .ranking-list li:last-child { border-bottom:0; }
  .ranking-list .ranking-empty { grid-column:1 / -1; }
  .ranking-position { align-items:center; background:#eef1f8; border-radius:50%; color:var(--azul-reporte); display:flex; font-size:.72rem; font-weight:800; height:26px; justify-content:center; width:26px; }
  .ranking-name { color:#303849; font-size:.82rem; font-weight:700; }
  .ranking-total { color:var(--rosa-reporte); font-size:.82rem; font-weight:800; }

  @media (max-width:1199.98px) {
    .avance-report-grid { grid-template-columns:1fr; }
    #avanceDistrictMap { height:420px; }
    .district-table-scroll { max-height:none; }
  }
  @media (max-width:767.98px) {
    .district-quick-filter { width:100%; }
    .district-quick-filter .form-select { min-width:0; }
    .ranking-grid { grid-template-columns:1fr; }
    .priority-overview { grid-template-columns:repeat(2,1fr); }
  }
  @media print {
    .report-actions,.navbar,.app-footer { display:none !important; }
    .content-wrap { padding-top:0 !important; }
    .avance-report-grid { grid-template-columns:36% 64%; }
    #avanceDistrictMap,.district-map-empty,.district-table-scroll { height:500px; max-height:500px; }
  }
</style>
@endpush

@section('content')
<div class="container-fluid px-xl-4 avance-page">
  @php
    $pctClass = function ($porcentaje, $meta) {
      if ((int)$meta <= 0) return 'pct-empty';
      if ($porcentaje >= 80) return 'pct-success';
      if ($porcentaje >= 30) return 'pct-warning';
      return 'pct-danger';
    };
    $dlTexto = $distritoLocal !== '' ? str_pad($distritoLocal, 2, '0', STR_PAD_LEFT) : 'Todos';
    $tituloDistrito = $distritoLocal !== '' ? 'Morelia · Distrito local '.$dlTexto : 'Morelia · Todos los distritos locales';
    $reportQuery = request()->except(['cve_mun', 'distrito_federal']);

    $tipoCargaTexto = match($tipoCarga) {
      'distritales' => 'Distrito local',
      'politicos' => 'Políticos',
      default => 'Todos',
    };
  @endphp

  <section class="morelia-hero">
    <div class="d-flex flex-wrap justify-content-between align-items-start gap-3">
      <div>
        <h1 class="fw-bold mb-1">Campaña municipal de Morelia</h1>
        <p>Avance de convencidos y lonas para la Presidencia Municipal. Aquí solamente aparecen secciones de Morelia.</p>
      </div>
      <a href="{{ route('avance.prioritarias.export.xlsx', $reportQuery) }}" class="btn btn-light fw-bold">
        <i class="fa-solid fa-file-excel text-success me-1"></i> Descargar reporte de prioritarias
      </a>
    </div>
    <div class="priority-overview">
      <div class="priority-stat"><strong>{{ $prioridadConteos['total'] }}</strong><span>Prioritarias · {{ $prioridadConteos['con_meta'] }} con meta</span></div>
      <div class="priority-stat"><strong>{{ $prioridadConteos['alta'] }}</strong><span>Prioridad alta</span></div>
      <div class="priority-stat"><strong>{{ $prioridadConteos['media'] }}</strong><span>Prioridad media</span></div>
      <div class="priority-stat"><strong>{{ $prioridadConteos['baja'] }}</strong><span>Prioridad baja</span></div>
    </div>
  </section>

  <div class="simple-filter report-actions">
    <form method="GET" action="{{ route('avance.index') }}" id="districtQuickFilter" class="district-quick-filter flex-grow-1">
      @if($tipoCarga !== 'todos')<input type="hidden" name="tipo_carga" value="{{ $tipoCarga }}">@endif
      @if($referente !== '')<input type="hidden" name="referente" value="{{ $referente }}">@endif
      @if($capturistaId)<input type="hidden" name="capturista_id" value="{{ $capturistaId }}">@endif
      <div>
        <label for="quickDistritoLocal">¿Qué distrito quieres ver?</label>
        @if($distritoLocalRestringido && count($distritosLocalesAsignados) === 1)
          <input type="hidden" name="distrito_local" value="{{ $distritoLocal }}">
          <span class="form-control form-control-sm bg-light text-nowrap">Distrito local {{ str_pad($distritoLocal, 2, '0', STR_PAD_LEFT) }}</span>
        @else
          <select name="distrito_local" id="quickDistritoLocal" class="form-select form-select-sm" onchange="this.form.submit()">
            @unless($distritoLocalRestringido)<option value="">Todos los distritos de Morelia</option>@endunless
            @foreach($distritosLocales as $distrito)
              <option value="{{ $distrito }}" {{ (string)$distritoLocal === (string)$distrito ? 'selected' : '' }}>Distrito local {{ str_pad($distrito, 2, '0', STR_PAD_LEFT) }}</option>
            @endforeach
          </select>
        @endif
      </div>
    </form>
    <button class="btn btn-sm btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#modalFiltros"><i class="fa-solid fa-filter me-1"></i> Más filtros</button>
    <a href="{{ route('avance.convencidos', $reportQuery) }}" class="btn btn-sm btn-outline-primary"><i class="fa-solid fa-users me-1"></i> Ver personas</a>
    @can('avance.metas')
      <button class="btn btn-sm btn-outline-dark" data-bs-toggle="modal" data-bs-target="#modalSeccionesPrioritarias"><i class="fa-solid fa-bullseye me-1"></i> Metas por sección</button>
      <button class="btn btn-sm btn-granate btn-nueva-meta" data-bs-toggle="modal" data-bs-target="#modalMeta"><i class="fa-solid fa-bullseye me-1"></i> Cambiar metas</button>
    @endcan
    <div class="dropdown">
      <button class="btn btn-sm btn-outline-success dropdown-toggle" data-bs-toggle="dropdown"><i class="fa-solid fa-file-excel me-1"></i> Otras descargas</button>
      <ul class="dropdown-menu dropdown-menu-end">
        <li><a class="dropdown-item" href="{{ route('avance.export.xlsx', $reportQuery) }}">Reporte completo de Morelia</a></li>
        <li><a class="dropdown-item" href="{{ route('avance.secciones.export.xlsx', $reportQuery) }}">Todas las secciones de Morelia</a></li>
      </ul>
    </div>
  </div>

  @if(session('status'))
    <div class="alert alert-success alert-dismissible fade show py-2">
      <i class="fa-solid fa-circle-check me-1"></i>{{ session('status') }}
      <button type="button" class="btn-close py-2" data-bs-dismiss="alert"></button>
    </div>
  @endif
  @if($errors->any())
    <div class="alert alert-danger py-2"><strong>No se pudo guardar la meta.</strong> {{ $errors->first() }}</div>
  @endif

  <div class="avance-summary-wrap">
    <div class="avance-summary">
      @foreach(['Distrito local','Secciones','Secciones con avance','Meta convencidos','Convencidos','Avance de meta','Meta lonas','Lonas colocadas','Avance de lonas'] as $encabezado)
        <div class="avance-summary-head">{{ $encabezado }}</div>
      @endforeach
      <div class="avance-summary-value">{{ $dlTexto }}</div>
      <div class="avance-summary-value">{{ number_format($totales['secciones']) }}</div>
      <div class="avance-summary-value">
        {{ number_format($totales['secciones_cubiertas']) }}
        ({{ number_format($totales['porcentaje_secciones_cubiertas'], 2) }}%)
      </div>
      <div class="avance-summary-value">{{ number_format($totales['meta_convencidos']) }}</div>
      <div class="avance-summary-value">{{ number_format($totales['total_convencidos']) }}</div>
      <div class="avance-summary-value pct {{ $pctClass($totales['porcentaje_convencidos'], $totales['meta_convencidos']) }}">
        {{ $totales['meta_convencidos'] > 0 ? number_format($totales['porcentaje_convencidos'], 2).'%' : 'Sin meta' }}
      </div>
      <div class="avance-summary-value">{{ number_format($totales['meta_lonas']) }}</div>
      <div class="avance-summary-value">{{ number_format($totales['total_lonas']) }}</div>
      <div class="avance-summary-value pct {{ $pctClass($totales['porcentaje_lonas'], $totales['meta_lonas']) }}">
        {{ $totales['meta_lonas'] > 0 ? number_format($totales['porcentaje_lonas'], 2).'%' : 'Sin meta' }}
      </div>
    </div>
  </div>

  <div class="avance-report-grid">
    <section class="district-map-card">
      <div class="district-ribbon">{{ $tituloDistrito }}</div>
      @if($avance->isNotEmpty())
        <div class="district-map-body">
          <div id="avanceDistrictMap" aria-label="Mapa de {{ $tituloDistrito }}"></div>
          <div class="avance-map-legend" aria-label="Escala de convencidos">
            <strong>Convencidos:</strong>
            <span><i style="background:#f8cbd7"></i>0–4</span>
            <span><i style="background:#f08aa7"></i>5–19</span>
            <span><i style="background:#e34b6a"></i>20–49</span>
            <span><i style="background:#d61a3c"></i>50–99</span>
            <span><i style="background:#b80027"></i>100+</span>
          </div>
        </div>
      @else
        <div class="district-map-empty">No hay secciones para los filtros seleccionados.</div>
      @endif
    </section>

    <section class="district-table-card">
      <div class="district-table-scroll">
        <table class="table district-table">
          <thead>
            <tr>
              <th>Distrito local</th>
              <th>Secciones</th>
              <th>Meta<br>convencidos</th>
              <th>Total<br>convencidos</th>
              <th>%<br>convencidos</th>
              <th>Meta<br>lonas</th>
              <th>Total<br>lonas</th>
              <th>%<br>lonas</th>
            </tr>
          </thead>
          <tbody>
            @forelse($avance as $fila)
              <tr>
                <td>
                  <strong>DL {{ str_pad($fila['distrito_local'], 2, '0', STR_PAD_LEFT) }}</strong>
                  <div class="mt-1">
                    <button
                      type="button"
                      class="btn btn-success btn-secciones-municipio"
                      title="Ver convencidos por sección"
                      data-bs-toggle="modal"
                      data-bs-target="#modalSeccionesMunicipio"
                      data-scope="{{ $fila['cve_mun'] }}|{{ $fila['distrito_local'] }}"
                      data-municipio="{{ $fila['municipio'] }}"
                      data-distrito-local="{{ $fila['distrito_local'] }}"
                    >
                      <i class="fa-solid fa-list-ol me-1"></i>Ver secciones
                    </button>
                  </div>
                </td>
                <td>{{ number_format($fila['secciones']) }}</td>
                <td>
                  @can('avance.metas')
                    <button
                      type="button"
                      class="btn btn-outline-danger define-meta btn-asignar-meta"
                      title="Definir metas de {{ $fila['municipio'] }} · DL {{ str_pad($fila['distrito_local'], 2, '0', STR_PAD_LEFT) }}"
                      data-bs-toggle="modal"
                      data-bs-target="#modalMeta"
                      data-cve-mun="{{ $fila['cve_mun'] }}"
                      data-distrito-local="{{ $fila['distrito_local'] }}"
                      data-meta-convencidos="{{ $fila['meta_convencidos'] }}"
                      data-meta-lonas="{{ $fila['meta_lonas'] }}"
                    >
                      <i class="fa-solid {{ $fila['meta_convencidos'] > 0 ? 'fa-pen' : 'fa-plus' }} me-1"></i>
                      {{ $fila['meta_convencidos'] > 0 ? number_format($fila['meta_convencidos']) : 'Definir' }}
                    </button>
                  @else
                    {{ $fila['meta_convencidos'] > 0 ? number_format($fila['meta_convencidos']) : '—' }}
                  @endcan
                </td>
                <td>{{ number_format($fila['total_convencidos']) }}</td>
                <td class="pct-cell {{ $pctClass($fila['porcentaje_convencidos'], $fila['meta_convencidos']) }}">
                  {{ $fila['meta_convencidos'] > 0 ? number_format($fila['porcentaje_convencidos'], 2).'%' : 'Sin meta' }}
                </td>
                <td>{{ $fila['meta_lonas'] > 0 ? number_format($fila['meta_lonas']) : '—' }}</td>
                <td>{{ number_format($fila['total_lonas']) }}</td>
                <td class="pct-cell {{ $pctClass($fila['porcentaje_lonas'], $fila['meta_lonas']) }}">
                  {{ $fila['meta_lonas'] > 0 ? number_format($fila['porcentaje_lonas'], 2).'%' : 'Sin meta' }}
                </td>
              </tr>
            @empty
              <tr>
                <td colspan="8" class="py-5 text-muted">No hay información de Morelia para este filtro.</td>
              </tr>
            @endforelse
          </tbody>
        </table>
      </div>
    </section>
  </div>

  <div class="ranking-grid">
    <section class="ranking-card">
      <div class="district-ribbon"><i class="fa-solid fa-trophy me-2"></i>Top capturistas por convencidos</div>
      <ol class="ranking-list">
        @forelse($topCapturistas as $posicion => $item)
          <li>
            <span class="ranking-position">{{ $posicion + 1 }}</span>
            <span class="ranking-name">{{ $item->name }}</span>
            <span class="ranking-total">{{ number_format($item->total) }}</span>
          </li>
        @empty
          <li><span class="ranking-empty text-muted small">Sin registros en el periodo seleccionado.</span></li>
        @endforelse
      </ol>
    </section>

    <section class="ranking-card">
      <div class="district-ribbon">
        <i class="fa-solid fa-ranking-star me-2"></i>
        Referentes por convencidos
      </div>

      <ol class="ranking-list referentes-list">
        @forelse($topReferentes as $posicion => $item)
          <li class="{{ $posicion < 5 ? 'ranking-top-five' : '' }}">
            <span class="ranking-position">{{ $posicion + 1 }}</span>

            <span class="ranking-name">
              {{ $item->name }}

              @if($posicion < 5)
                <span class="top-five-label">TOP 5</span>
              @endif
            </span>

            <span class="ranking-total">{{ number_format($item->total) }}</span>
          </li>
        @empty
          <li>
            <span class="ranking-empty text-muted small">
              Sin registros en el periodo seleccionado.
            </span>
          </li>
        @endforelse
      </ol>
    </section>
  </div>
</div>

<div class="modal fade" id="modalSeccionesMunicipio" tabindex="-1" aria-labelledby="tituloSeccionesMunicipio" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
    <div class="modal-content">
      <div class="modal-header bg-success text-white">
        <div>
          <h5 class="modal-title" id="tituloSeccionesMunicipio">Convencidos por sección</h5>
          <div class="small opacity-75" id="subtituloSeccionesMunicipio"></div>
        </div>
        <button class="btn-close btn-close-white" type="button" data-bs-dismiss="modal" aria-label="Cerrar"></button>
      </div>
      <div class="modal-body p-0">
        <table class="table table-striped align-middle mb-0">
          <thead class="table-light position-sticky top-0">
            <tr>
              <th class="ps-4">Sección</th>
              <th class="text-end pe-4">Personas convencidas</th>
            </tr>
          </thead>
          <tbody id="tablaSeccionesMunicipio"></tbody>
          <tfoot>
            <tr class="table-success fw-bold">
              <td class="ps-4">Total del distrito</td>
              <td class="text-end pe-4" id="totalSeccionesMunicipio">0</td>
            </tr>
          </tfoot>
        </table>
      </div>
    </div>
  </div>
</div>

<div class="modal fade" id="modalFiltros" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <div class="modal-content">
      <form method="GET" action="{{ route('avance.index') }}">
        <div class="modal-header">
          <h5 class="modal-title"><i class="fa-solid fa-filter me-1"></i> Filtrar información de Morelia</h5>
          <button class="btn-close" type="button" data-bs-dismiss="modal"></button>
        </div>

        <div class="modal-body">
          <div class="row g-3">
            <div class="col-md-6">
              <label class="form-label">Tipo de carga</label>
              <select name="tipo_carga" class="form-select">
                <option value="todos" {{ $tipoCarga === 'todos' ? 'selected' : '' }}>Todos</option>
                <option value="distritales" {{ $tipoCarga === 'distritales' ? 'selected' : '' }}>Distrito local</option>
                <option value="politicos" {{ $tipoCarga === 'politicos' ? 'selected' : '' }}>Políticos</option>
              </select>
            </div>

            <div class="col-md-6">
              <label class="form-label">Distrito local</label>
              @if($distritoLocalRestringido)
                <input type="hidden" name="distrito_local" value="{{ $distritoLocal }}">
                <input type="text" class="form-control" value="Distrito local {{ str_pad($distritoLocal, 2, '0', STR_PAD_LEFT) }} (asignado)" readonly>
              @else
                <select name="distrito_local" class="form-select">
                  <option value="">Todos</option>
                  @foreach($distritosLocales as $distrito)
                    <option value="{{ $distrito }}" {{ (string)$distritoLocal === (string)$distrito ? 'selected' : '' }}>
                      Distrito {{ $distrito }}
                    </option>
                  @endforeach
                </select>
              @endif
            </div>

            <div class="col-md-6">
              <label class="form-label">Referente / Referencia</label>
              <select name="referente" class="form-select">
                <option value="">Todos</option>
                @foreach($referentes as $nombreReferente)
                  <option value="{{ $nombreReferente }}" {{ $referente === $nombreReferente ? 'selected' : '' }}>
                    {{ $nombreReferente }}
                  </option>
                @endforeach
              </select>
            </div>

            <div class="col-md-6">
              <label class="form-label">Capturista</label>
              <select name="capturista_id" class="form-select">
                <option value="">Todos</option>
                @foreach($capturistas as $usuario)
                  <option value="{{ $usuario->id }}" {{ (string)$capturistaId === (string)$usuario->id ? 'selected' : '' }}>
                    {{ $usuario->name }}
                  </option>
                @endforeach
              </select>
            </div>
          </div>
        </div>

        <div class="modal-footer">
          <a href="{{ route('avance.index') }}" class="btn btn-outline-secondary">Quitar filtros</a>
          <button class="btn btn-granate">
            <i class="fa-solid fa-check me-1"></i> Aplicar
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

@can('avance.metas')
<div class="modal fade" id="modalSeccionesPrioritarias" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title"><i class="fa-solid fa-bullseye me-1"></i> Metas por sección de Morelia</h5>
        <button class="btn-close" type="button" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <div class="alert alert-light border small mb-3">
          <strong>Para asignar una meta:</strong> 1. Elige la sección. 2. Indica la prioridad. 3. Escribe la meta de convencidos (y de lonas, si aplica). 4. Presiona Guardar. Si la sección ya estaba configurada, sus datos se actualizan.
        </div>
        <form method="POST" action="{{ route('avance.prioritarias.store') }}" class="row g-2 align-items-end mb-4">
          @csrf
          <div class="col-lg-4 col-md-6">
            <label for="prioritaria_seccion" class="form-label">Sección de Morelia</label>
            <select name="seccion" id="prioritaria_seccion" class="form-select" required>
              <option value="">Selecciona una sección</option>
              @foreach($seccionesMoreliaDisponibles as $seccionMorelia)
                <option value="{{ str_pad($seccionMorelia->seccion, 4, '0', STR_PAD_LEFT) }}">DL {{ str_pad($seccionMorelia->distrito_local, 2, '0', STR_PAD_LEFT) }} · Sección {{ str_pad($seccionMorelia->seccion, 4, '0', STR_PAD_LEFT) }}</option>
              @endforeach
            </select>
          </div>
          <div class="col-lg-2 col-md-3">
            <label for="prioritaria_prioridad" class="form-label">Prioridad</label>
            <select name="prioridad" id="prioritaria_prioridad" class="form-select" required>
              <option value="3">Alta</option>
              <option value="2" selected>Media</option>
              <option value="1">Baja</option>
            </select>
          </div>
          <div class="col-lg-2 col-md-3">
            <label for="prioritaria_meta_convencidos" class="form-label">Meta convencidos</label>
            <input type="number" name="meta_convencidos" id="prioritaria_meta_convencidos" class="form-control" min="0" step="1" value="0" required>
          </div>
          <div class="col-lg-2 col-md-3">
            <label for="prioritaria_meta_lonas" class="form-label">Meta lonas</label>
            <input type="number" name="meta_lonas" id="prioritaria_meta_lonas" class="form-control" min="0" step="1" value="0" required>
          </div>
          <div class="col-lg-2 col-md-3 d-grid">
            <button class="btn btn-granate"><i class="fa-solid fa-floppy-disk me-1"></i> Guardar</button>
          </div>
        </form>

        <div class="table-responsive">
          <table class="table table-sm table-hover align-middle mb-0">
            <thead><tr><th>Distrito</th><th>Sección</th><th>Prioridad</th><th>Meta convencidos</th><th>Meta lonas</th><th class="text-end">Acción</th></tr></thead>
            <tbody>
              @forelse($seccionesPrioritarias as $prioritaria)
                <tr>
                  <td>DL {{ str_pad(optional($seccionesMoreliaPorNumero->get((string)(int)$prioritaria->seccion))->distrito_local, 2, '0', STR_PAD_LEFT) }}</td>
                  <td class="fw-bold">{{ str_pad($prioritaria->seccion, 4, '0', STR_PAD_LEFT) }}</td>
                  <td>
                    <span class="badge {{ $prioritaria->prioridad === 3 ? 'text-bg-danger' : ($prioritaria->prioridad === 2 ? 'text-bg-warning' : 'text-bg-success') }}">
                      {{ [3 => 'Alta', 2 => 'Media', 1 => 'Baja'][$prioritaria->prioridad] }}
                    </span>
                  </td>
                  <td>{{ $prioritaria->meta_convencidos > 0 ? number_format($prioritaria->meta_convencidos) : 'Sin meta' }}</td>
                  <td>{{ $prioritaria->meta_lonas > 0 ? number_format($prioritaria->meta_lonas) : 'Sin meta' }}</td>
                  <td class="text-end">
                    <button type="button" class="btn btn-sm btn-outline-primary btn-editar-prioritaria me-1" data-seccion="{{ $prioritaria->seccion }}" data-prioridad="{{ $prioritaria->prioridad }}" data-meta-convencidos="{{ $prioritaria->meta_convencidos }}" data-meta-lonas="{{ $prioritaria->meta_lonas }}"><i class="fa-solid fa-pen"></i> Cambiar</button>
                    <form method="POST" action="{{ route('avance.prioritarias.destroy', $prioritaria) }}" onsubmit="return confirm('¿Retirar esta sección de las prioritarias?')">
                      @csrf @method('DELETE')
                      <button class="btn btn-sm btn-outline-danger"><i class="fa-solid fa-trash"></i> Retirar</button>
                    </form>
                  </td>
                </tr>
              @empty
                <tr><td colspan="6" class="text-center text-muted py-4">No hay secciones prioritarias configuradas.</td></tr>
              @endforelse
            </tbody>
          </table>
        </div>
      </div>
      <div class="modal-footer">
        <span class="me-auto text-muted small">{{ $seccionesPrioritarias->count() }} secciones configuradas</span>
        <button class="btn btn-outline-secondary" type="button" data-bs-dismiss="modal">Cerrar</button>
      </div>
    </div>
  </div>
</div>

<div class="modal fade" id="modalMeta" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <form method="POST" action="{{ route('avance.metas.store') }}">
        @csrf

        <div class="modal-header">
          <h5 class="modal-title">
            <i class="fa-solid fa-bullseye me-1"></i> Asignar metas
          </h5>
          <button class="btn-close" type="button" data-bs-dismiss="modal"></button>
        </div>

        <div class="modal-body">
          <div class="row g-3">
            <div class="col-12">
              <label for="meta_cve_mun" class="form-label">Distrito local de Morelia</label>
              <select name="meta_scope" id="meta_cve_mun" class="form-select" required>
                <option value="">Selecciona un distrito</option>
                @foreach($avance as $fila)
                  <option
                    value="{{ $fila['cve_mun'] }}|{{ $fila['distrito_local'] }}"
                    data-cve-mun="{{ $fila['cve_mun'] }}"
                    data-distrito-local="{{ $fila['distrito_local'] }}"
                    data-secciones="{{ $fila['secciones'] }}"
                    {{ old('cve_mun').'|'.old('distrito_local') === $fila['cve_mun'].'|'.$fila['distrito_local'] ? 'selected' : '' }}
                  >
                    Distrito local {{ str_pad($fila['distrito_local'], 2, '0', STR_PAD_LEFT) }}
                  </option>
                @endforeach
              </select>
              <input type="hidden" name="cve_mun" id="meta_cve_mun_value" value="{{ old('cve_mun') }}">
              <input type="hidden" name="distrito_local" id="meta_distrito_local" value="{{ old('distrito_local') }}">
            </div>

            <div class="col-md-6">
              <label for="meta_convencidos" class="form-label">Meta de convencidos</label>
              <input
                type="number"
                name="meta_convencidos"
                id="meta_convencidos"
                value="{{ old('meta_convencidos') }}"
                class="form-control"
                min="1"
                step="1"
                required
              >
            </div>

            <div class="col-md-6">
              <label for="meta_lonas" class="form-label">Meta de lonas</label>
              <input
                type="number"
                name="meta_lonas"
                id="meta_lonas"
                value="{{ old('meta_lonas') }}"
                class="form-control"
                min="0"
                step="1"
                placeholder="Sin meta oficial"
              >
            </div>

          </div>
        </div>

        <div class="modal-footer">
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
          <button class="btn btn-granate">
            <i class="fa-solid fa-floppy-disk me-1"></i> Guardar meta
          </button>
        </div>
      </form>
    </div>
  </div>
</div>
@endcan
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
  ['quickDistritoLocal'].forEach(function (id) {
    document.getElementById(id)?.addEventListener('change', function () {
      this.form.submit();
    });
  });

  const seccionesPorMunicipio = @json($seccionesPorMunicipio);
  const modalSecciones = document.getElementById('modalSeccionesMunicipio');

  modalSecciones?.addEventListener('show.bs.modal', function (event) {
    const boton = event.relatedTarget;
    const scope = boton?.dataset.scope || '';
    const distritoLocal = boton?.dataset.distritoLocal || '';
    const secciones = seccionesPorMunicipio[scope] || [];
    const cuerpo = document.getElementById('tablaSeccionesMunicipio');
    const total = secciones.reduce((suma, fila) => suma + Number(fila.total || 0), 0);

    document.getElementById('subtituloSeccionesMunicipio').textContent =
      'Morelia · Distrito local '+String(distritoLocal).padStart(2, '0');
    document.getElementById('totalSeccionesMunicipio').textContent =
      total.toLocaleString('es-MX');
    cuerpo.replaceChildren();

    if (!secciones.length) {
      const fila = cuerpo.insertRow();
      const celda = fila.insertCell();
      celda.colSpan = 2;
      celda.className = 'py-4 text-center text-muted';
      celda.textContent = 'No hay información para este distrito con los filtros actuales.';
      return;
    }

    secciones.forEach(function (detalle) {
      const fila = cuerpo.insertRow();
      const seccion = fila.insertCell();
      const votos = fila.insertCell();

      seccion.className = 'ps-4 fw-bold';
      seccion.textContent = detalle.seccion;
      votos.className = 'text-end pe-4 fw-bold';
      votos.textContent = Number(detalle.total || 0).toLocaleString('es-MX');
    });
  });

  document.querySelectorAll('.btn-editar-prioritaria').forEach(function (button) {
    button.addEventListener('click', function () {
      document.getElementById('prioritaria_seccion').value = button.dataset.seccion;
      document.getElementById('prioritaria_prioridad').value = button.dataset.prioridad;
      document.getElementById('prioritaria_meta_convencidos').value = button.dataset.metaConvencidos || 0;
      document.getElementById('prioritaria_meta_lonas').value = button.dataset.metaLonas || 0;
      document.getElementById('prioritaria_seccion').focus();
      document.querySelector('#modalSeccionesPrioritarias .modal-body')?.scrollTo({top:0, behavior:'smooth'});
    });
  });
});
</script>

@if($avance->isNotEmpty())
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
  const selectedLocalDistrict = @json((string)$distritoLocal);
  const sectionMunicipalities = @json($municipioPorSeccion);
  const sectionCounts = @json($convencidosPorSeccion);

  function mapColor(total) {
    return total >= 100 ? '#b80027'
      : total >= 50 ? '#d61a3c'
      : total >= 20 ? '#e34b6a'
      : total >= 5 ? '#f08aa7'
      : '#f8cbd7';
  }

  const map = L.map('avanceDistrictMap', {
    attributionControl:false,
    zoomControl:false,
    dragging:false,
    scrollWheelZoom:false,
    doubleClickZoom:false,
    boxZoom:false,
    keyboard:false,
    tap:false
  });
  fetch(@json(asset('maps/out/SECCION.geojson')))
    .then(response => response.json())
    .then(geojson => {
      const features = geojson.features.filter(feature => {
        const section = String(Number(feature.properties?.SECCION ?? 0));
        return Boolean(sectionMunicipalities[section])
          && (!selectedLocalDistrict || String(feature.properties?.DISTRITO_L ?? '') === selectedLocalDistrict);
      });

      const layer = L.geoJSON(
        {
          type:'FeatureCollection',
          features
        },
        {
          interactive:true,
          style(feature) {
            const section = String(Number(feature.properties?.SECCION ?? 0));
            return {
              color:'#6f7896',
              weight:.7,
              fillColor:mapColor(Number(sectionCounts[section] || 0)),
              fillOpacity:.84
            };
          },
          onEachFeature(feature, sectionLayer) {
            const section = String(Number(feature.properties?.SECCION ?? 0));
            const municipality = sectionMunicipalities[section];
            const total = Number(sectionCounts[section] || 0);

            sectionLayer.bindPopup(
              '<strong>Sección '+section+'</strong><br>'+
              (municipality ? municipality.municipio+'<br>' : '')+
              '<strong>Convencidos:</strong> '+total
            );
            sectionLayer.on('mouseover', function () {
              this.setStyle({weight:1.6, fillOpacity:1});
              this.bringToFront();
            });
            sectionLayer.on('mouseout', function () {
              layer.resetStyle(this);
            });

          }
        }
      ).addTo(map);

      if (layer.getBounds().isValid()) {
        map.fitBounds(layer.getBounds(), {padding:[18,18]});
      }

    })
    .catch(() => {
      document.getElementById('avanceDistrictMap').innerHTML =
        '<div class="district-map-empty">No fue posible cargar el mapa electoral.</div>';
    });
});
</script>
@endif

@can('avance.metas')
<script>
document.addEventListener('DOMContentLoaded', function () {
  const municipio = document.getElementById('meta_cve_mun');
  const cveMun = document.getElementById('meta_cve_mun_value');
  const distritoLocal = document.getElementById('meta_distrito_local');
  const convencidos = document.getElementById('meta_convencidos');
  const lonas = document.getElementById('meta_lonas');

  function metasSugeridas() {
    const option = municipio.options[municipio.selectedIndex];
    const secciones = Number(option?.dataset.secciones || 0);

    return {
      convencidos: secciones ? secciones * 180 : '',
      lonas: ''
    };
  }

  function actualizarAlcance() {
    const option = municipio.options[municipio.selectedIndex];
    cveMun.value = option?.dataset.cveMun || '';
    distritoLocal.value = option?.dataset.distritoLocal || '';
  }

  function cargar(cve, distrito, metaConvencidos, metaLonas) {
    municipio.value = cve && distrito ? cve+'|'+distrito : '';
    actualizarAlcance();
    const sugeridas = metasSugeridas();
    convencidos.value = Number(metaConvencidos) > 0 ? metaConvencidos : sugeridas.convencidos;
    lonas.value = Number(metaLonas) > 0 ? metaLonas : sugeridas.lonas;
  }

  document.querySelectorAll('.btn-nueva-meta').forEach(button => {
    button.addEventListener('click', () => cargar('', '', '', ''));
  });

  document.querySelectorAll('.btn-asignar-meta').forEach(button => {
    button.addEventListener('click', () => {
      cargar(
        button.dataset.cveMun,
        button.dataset.distritoLocal,
        button.dataset.metaConvencidos,
        button.dataset.metaLonas
      );
    });
  });

  municipio?.addEventListener('change', function () {
    actualizarAlcance();
    const sugeridas = metasSugeridas();
    convencidos.value = sugeridas.convencidos;
    lonas.value = sugeridas.lonas;
  });

  actualizarAlcance();

  @if($errors->any())
    bootstrap.Modal.getOrCreateInstance(
      document.getElementById('modalMeta')
    ).show();
  @endif
});
</script>
@endcan
@endpush
