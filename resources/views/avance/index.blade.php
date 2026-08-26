@extends('layouts.app')

@section('title', 'Avance distrital')

@push('css')
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
<style>
  .avance-page { --azul-reporte:#1d3376; --gris-reporte:#eef1eb; --rosa-reporte:#d91785; padding-bottom:2.5rem; }
  .avance-page .report-actions .btn { border-radius:.45rem; }
  .district-quick-filter { align-items:center; display:flex; gap:.45rem; }
  .district-quick-filter .form-select { min-width:175px; }
  .avance-periodo { color:#667085; font-size:.82rem; }

  .avance-summary {
    display:grid;
    grid-template-columns:.65fr .75fr repeat(6, 1fr);
    margin-bottom:1.15rem;
    min-width:920px;
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
    $dfTexto = $distritoFederal !== '' ? str_pad($distritoFederal, 2, '0', STR_PAD_LEFT) : 'Todos';
    $dlTexto = $distritoLocal !== '' ? str_pad($distritoLocal, 2, '0', STR_PAD_LEFT) : 'Todos';
    $tituloDistrito = $distritoFederal !== ''
      ? 'Distrito '.$dfTexto.($nombreDistritoFederal ? ' '.$nombreDistritoFederal : '')
      : 'Michoacán';
    if ($distritoLocal !== '') $tituloDistrito .= ' · Distrito local '.$dlTexto;
  @endphp

  <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
    <div>
      <h1 class="h5 fw-bold mb-1 text-uppercase">Avance distrital</h1>
      <div class="avance-periodo">{{ $tituloDistrito }} · Histórico completo</div>
    </div>
    <div class="report-actions d-flex gap-2">
      <form method="GET" action="{{ route('avance.index') }}" id="districtQuickFilter" class="district-quick-filter">
        @if($cveMun !== '')<input type="hidden" name="cve_mun" value="{{ $cveMun }}">@endif
        <label for="quickReferente" class="visually-hidden">Referente</label>
        <select name="referente" id="quickReferente" class="form-select form-select-sm" title="Cambiar referente" onchange="this.form.submit()">
            <option value="">Todos los referentes</option>

            @foreach($referentes as $nombreReferente)
                <option
                    value="{{ $nombreReferente }}"
                    {{ $referente === $nombreReferente ? 'selected' : '' }}
                >
                    {{ $nombreReferente }}
                </option>
            @endforeach
        </select>
        @if($capturistaId)<input type="hidden" name="capturista_id" value="{{ $capturistaId }}">@endif
        <label for="quickDistritoFederal" class="visually-hidden">Distrito federal</label>
        <select name="distrito_federal" id="quickDistritoFederal" class="form-select form-select-sm" title="Cambiar distrito federal" onchange="this.form.submit()">
          <option value="">Todos los distritos</option>
          @foreach($distritosFederales as $distrito)
            <option value="{{ $distrito }}" {{ (string)$distritoFederal === (string)$distrito ? 'selected' : '' }}>
              DFn {{ str_pad($distrito, 2, '0', STR_PAD_LEFT) }}
            </option>
          @endforeach
        </select>
        @if($distritoLocalRestringido)
          <input type="hidden" name="distrito_local" value="{{ $distritoLocal }}">
          <span class="form-control form-control-sm bg-light text-nowrap" title="Distrito local asignado">
            DL {{ str_pad($distritoLocal, 2, '0', STR_PAD_LEFT) }}
          </span>
        @else
          <label for="quickDistritoLocal" class="visually-hidden">Distrito local</label>
          <select name="distrito_local" id="quickDistritoLocal" class="form-select form-select-sm" title="Cambiar distrito local" onchange="this.form.submit()">
            <option value="">Todos los locales</option>
            @foreach($distritosLocales as $distrito)
              <option value="{{ $distrito }}" {{ (string)$distritoLocal === (string)$distrito ? 'selected' : '' }}>
                DL {{ str_pad($distrito, 2, '0', STR_PAD_LEFT) }}
              </option>
            @endforeach
          </select>
        @endif
        <button type="submit" class="btn btn-sm btn-outline-primary" title="Aplicar filtros">
          <i class="fa-solid fa-check"></i><span class="d-none d-xl-inline ms-1">Aplicar</span>
        </button>
      </form>
      <button class="btn btn-sm btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#modalFiltros">
        <i class="fa-solid fa-filter me-1"></i> Filtros
      </button>
      @can('avance.metas')
        <button class="btn btn-sm btn-granate btn-nueva-meta" data-bs-toggle="modal" data-bs-target="#modalMeta">
          <i class="fa-solid fa-bullseye me-1"></i> Asignar meta
        </button>
      @endcan
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
      @foreach(['DFn / DL','Secciones','Meta convencidos','Total convencidos','% convencidos','Meta lonas','Total lonas','% lonas'] as $encabezado)
        <div class="avance-summary-head">{{ $encabezado }}</div>
      @endforeach
      <div class="avance-summary-value">{{ $dfTexto }} / {{ $dlTexto }}</div>
      <div class="avance-summary-value">{{ number_format($totales['secciones']) }}</div>
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
          <label class="avance-map-label-toggle" for="toggleAvanceMunicipalityLabels">
            <input type="checkbox" id="toggleAvanceMunicipalityLabels">
            Nombres de municipios
          </label>
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
              <th>DL / DFn</th>
              <th>Municipio</th>
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
                  {{ str_pad($fila['distrito_local'], 2, '0', STR_PAD_LEFT) }} /
                  {{ $fila['distritos_federales'] ?: '—' }}
                </td>
                <td class="municipio-cell">
                  <span>{{ $fila['municipio'] }}</span>
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
                <td colspan="9" class="py-5 text-muted">Sin información para mostrar.</td>
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

<div class="modal fade" id="modalFiltros" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <div class="modal-content">
      <form method="GET" action="{{ route('avance.index') }}">
        <div class="modal-header">
          <h5 class="modal-title">
            <i class="fa-solid fa-filter me-1"></i> Filtros del reporte
          </h5>
          <button class="btn-close" type="button" data-bs-dismiss="modal"></button>
        </div>

        <div class="modal-body">
          <div class="row g-3">
            <div class="col-md-6">
              <label class="form-label">Distrito federal</label>
              <select name="distrito_federal" class="form-select">
                <option value="">Todos</option>
                @foreach($distritosFederales as $distrito)
                  <option value="{{ $distrito }}" {{ (string)$distritoFederal === (string)$distrito ? 'selected' : '' }}>
                    Distrito {{ str_pad($distrito, 2, '0', STR_PAD_LEFT) }}
                  </option>
                @endforeach
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
              <label class="form-label">Municipio</label>
              <select name="cve_mun" class="form-select">
                <option value="">Todos</option>
                @foreach($avance->unique('cve_mun') as $fila)
                  <option value="{{ $fila['cve_mun'] }}" {{ (string)$cveMun === (string)$fila['cve_mun'] ? 'selected' : '' }}>
                    {{ $fila['municipio'] }}
                  </option>
                @endforeach
              </select>
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
          <a href="{{ route('avance.index') }}" class="btn btn-outline-secondary">Mostrar todo</a>
          <button class="btn btn-granate">
            <i class="fa-solid fa-check me-1"></i> Aplicar
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

@can('avance.metas')
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
              <label for="meta_cve_mun" class="form-label">Municipio y distrito local</label>
              <select name="meta_scope" id="meta_cve_mun" class="form-select" required>
                <option value="">Selecciona un municipio y distrito</option>
                @foreach($avance as $fila)
                  <option
                    value="{{ $fila['cve_mun'] }}|{{ $fila['distrito_local'] }}"
                    data-cve-mun="{{ $fila['cve_mun'] }}"
                    data-distrito-local="{{ $fila['distrito_local'] }}"
                    data-secciones="{{ $fila['secciones'] }}"
                    {{ old('cve_mun').'|'.old('distrito_local') === $fila['cve_mun'].'|'.$fila['distrito_local'] ? 'selected' : '' }}
                  >
                    {{ $fila['municipio'] }} · DL {{ str_pad($fila['distrito_local'], 2, '0', STR_PAD_LEFT) }}
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
  ['quickDistritoFederal', 'quickDistritoLocal'].forEach(function (id) {
    document.getElementById(id)?.addEventListener('change', function () {
      this.form.submit();
    });
  });
});
</script>

@if($avance->isNotEmpty())
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
  const selectedDistrict = @json((string)$distritoFederal);
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
  const municipalityLabels = L.layerGroup();

  document.getElementById('toggleAvanceMunicipalityLabels')?.addEventListener('change', function () {
    this.checked ? municipalityLabels.addTo(map) : map.removeLayer(municipalityLabels);
  });

  fetch(@json(asset('maps/out/SECCION.geojson')))
    .then(response => response.json())
    .then(geojson => {
      const features = geojson.features.filter(
        feature => (!selectedDistrict || String(feature.properties?.DISTRITO_F ?? '') === selectedDistrict)
          && (!selectedLocalDistrict || String(feature.properties?.DISTRITO_L ?? '') === selectedLocalDistrict)
      );

      const municipalityLayers = {};

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

            if (municipality) {
              (
                municipalityLayers[municipality.cve_mun] ||= {
                  name:municipality.municipio,
                  layers:[]
                }
              ).layers.push(sectionLayer);
            }
          }
        }
      ).addTo(map);

      if (layer.getBounds().isValid()) {
        map.fitBounds(layer.getBounds(), {padding:[18,18]});
      }

      Object.values(municipalityLayers).forEach(municipality => {
        const bounds = L.featureGroup(municipality.layers).getBounds();

        if (!bounds.isValid()) return;

        L.marker(
          bounds.getCenter(),
          {
            interactive:false,
            icon:L.divIcon({
              className:'avance-map-label',
              html:'<span>'+municipality.name+'</span>',
              iconSize:null
            })
          }
        ).addTo(municipalityLabels);
      });
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
