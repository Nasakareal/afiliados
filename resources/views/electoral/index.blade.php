@extends('layouts.app')

@section('title', 'Elecciones de Morelia por sección')

@push('styles')
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
<style>
  .ei{--ink:#18212f;--muted:#6c7687;--line:#e7ebf1;--wine:#7a0019;--pink:#d91785;--green:#14806a;--blue:#2458a6;color:var(--ink)}
  .ei .hero{position:relative;overflow:hidden;border-radius:24px;padding:28px 30px;color:#fff;background:linear-gradient(125deg,#21131a 0%,#74122f 56%,#d91785 130%);box-shadow:0 22px 55px rgba(73,12,37,.18)}
  .ei .hero:after{content:"";position:absolute;width:340px;height:340px;border:64px solid rgba(255,255,255,.055);border-radius:50%;right:-90px;top:-175px}
  .ei .eyebrow{font-size:.72rem;font-weight:800;letter-spacing:.16em;text-transform:uppercase;opacity:.75}
  .ei .hero h1{font-size:clamp(1.75rem,3vw,2.65rem);font-weight:850;letter-spacing:-.035em;margin:.4rem 0}
  .ei .export-btn{position:relative;z-index:2;display:inline-flex;align-items:center;gap:7px;padding:10px 14px;border-radius:12px;background:#fff;color:#6f1835;text-decoration:none;font-size:.78rem;font-weight:850;box-shadow:0 8px 22px rgba(28,10,18,.16)}.ei .export-btn:hover{background:#fff4f8;color:#5c102b}
  .ei .card-panel,.ei .kpi{background:#fff;border:1px solid rgba(226,231,239,.95);border-radius:19px;box-shadow:0 10px 34px rgba(21,31,48,.055)}
  .ei .kpi{padding:19px;min-height:142px}.ei .kpi-value{font-size:1.9rem;line-height:1;font-weight:850;letter-spacing:-.04em;margin-top:18px}.ei .kpi-label{font-size:.72rem;color:var(--muted);font-weight:800;text-transform:uppercase;letter-spacing:.06em;margin-top:8px}.ei .kpi-note{font-size:.72rem;color:var(--muted)}
  .ei .card-panel{padding:22px}.ei .panel-title{font-size:1rem;font-weight:800;margin:0}.ei .panel-subtitle{font-size:.78rem;color:var(--muted);margin-top:3px}
  .ei #electoralMap{height:610px;border-radius:16px;background:#eef1f5}.ei .map-shell{position:relative}.ei .map-toolbar{position:absolute;left:12px;right:12px;top:12px;z-index:500;background:rgba(255,255,255,.96);backdrop-filter:blur(9px);border:1px solid rgba(222,227,235,.95);box-shadow:0 9px 24px rgba(27,38,55,.12);border-radius:14px;padding:10px}
  .ei .map-legend{position:absolute;left:13px;bottom:13px;z-index:500;background:rgba(255,255,255,.96);border-radius:12px;padding:10px 12px;box-shadow:0 7px 20px rgba(27,38,55,.14);font-size:.72rem;min-width:190px}.ei .legend-row{display:flex;align-items:center;gap:7px;margin-top:5px}.ei .legend-swatch{width:14px;height:14px;border-radius:4px;flex:0 0 auto}
  .ei .section-panel{min-height:610px;overflow:hidden}.ei .section-number{font-size:2.4rem;font-weight:850;letter-spacing:-.05em;color:var(--wine)}.ei .soft-pill{display:inline-flex;align-items:center;max-width:100%;padding:.34rem .64rem;border-radius:999px;background:#f4f6f9;color:#465267;font-size:.72rem;font-weight:750;white-space:normal}.ei .status-win{background:#e5f6ef;color:#0b6b54}.ei .status-loss{background:#fff0f2;color:#a42a43}
  .ei .history-table th{font-size:.66rem;text-transform:uppercase;letter-spacing:.07em;color:#7a8494;border-bottom-color:var(--line);white-space:nowrap}.ei .history-table td{vertical-align:middle;font-size:.82rem;border-bottom-color:#eef1f5}.ei .metric-bar{height:6px;background:#edf0f4;border-radius:20px;overflow:hidden}.ei .metric-bar span{display:block;height:100%;background:linear-gradient(90deg,#8b1538,#d91785);border-radius:inherit}
  .ei .year-grid{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:12px}.ei .year-card{padding:16px;border:1px solid var(--line);border-radius:15px;background:#fafbfc}.ei .year-card h3{font-size:1rem;font-weight:850;margin:0}.ei .year-total{font-size:1.35rem;font-weight:850;letter-spacing:-.03em}.ei .composition{height:10px;display:flex;overflow:hidden;border-radius:999px;background:#edf0f4}.ei .composition .morena{background:linear-gradient(90deg,#7a0019,#d91785)}.ei .composition .otros{background:#7da6cc}.ei .composition .invalidos{background:#c9ced6}.ei .composition-legend{display:flex;flex-wrap:wrap;gap:10px 16px;color:var(--muted);font-size:.7rem}.ei .composition-legend i{display:inline-block;width:9px;height:9px;border-radius:50%;margin-right:5px}
  .ei .section-summary{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:8px}.ei .section-stat{padding:11px;border-radius:12px;background:#f6f7f9}.ei .section-stat span{display:block;font-size:.66rem;text-transform:uppercase;letter-spacing:.05em;color:var(--muted);font-weight:800}.ei .section-stat strong{display:block;margin-top:3px;font-size:1rem}.ei .election-card{border:1px solid var(--line);border-radius:14px;padding:13px;margin-top:10px}.ei .election-card-head{display:flex;align-items:flex-start;justify-content:space-between;gap:8px}.ei .election-year{font-size:1rem;font-weight:850}.ei .election-row{display:flex;justify-content:space-between;gap:10px;margin-top:7px;font-size:.76rem}.ei .election-row span{color:var(--muted)}.ei .share-track{height:7px;border-radius:999px;background:#edf0f4;overflow:hidden;margin-top:8px}.ei .share-track span{display:block;height:100%;border-radius:inherit;background:linear-gradient(90deg,#7a0019,#d91785)}
  .ei .projection-panel{background:linear-gradient(135deg,#fff 0%,#fff8fb 100%);border-color:#f0d7e1}.ei .projection-grid{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:10px}.ei .projection-stat{padding:14px;border-radius:14px;background:#fff;border:1px solid #f0e4e9}.ei .projection-stat span{display:block;color:var(--muted);font-size:.68rem;font-weight:800;text-transform:uppercase;letter-spacing:.05em}.ei .projection-stat strong{display:block;font-size:1.35rem;letter-spacing:-.03em;margin-top:4px}.ei .projection-stat.accent{color:#fff;background:linear-gradient(135deg,#7a0019,#d91785);border:0}.ei .projection-stat.accent span{color:rgba(255,255,255,.76)}.ei .projection-note{border-left:3px solid #d91785;padding-left:11px;color:var(--muted);font-size:.76rem}.ei .range-value{min-width:54px;text-align:center;color:var(--wine);font-weight:850}.ei .operation-card{border:1px solid #efd5df;background:#fff8fb;border-radius:14px;padding:13px;margin-bottom:14px}.ei .scenario-box{padding:17px;border-radius:16px;background:#fff;border:1px solid #efdce4}.ei .scenario-buttons{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:7px}.ei .scenario-btn{border:1px solid #e4c5d1;background:#fff;color:#6f1835;border-radius:11px;padding:8px 5px;font-size:.73rem;font-weight:800}.ei .scenario-btn:hover,.ei .scenario-btn.active{background:#7a0019;color:#fff;border-color:#7a0019}.ei .projection-result{padding:11px 13px;border-radius:12px;background:#f9edf2;color:#561129;font-size:.82rem}.ei .projection-result strong{font-size:1.05rem}
  .ei .action-kpis{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:9px}.ei .action-kpi{padding:12px 14px;border-radius:13px;background:#f7f8fa}.ei .action-kpi strong{display:block;font-size:1.3rem}.ei .action-kpi span{display:block;font-size:.68rem;color:var(--muted);font-weight:800;text-transform:uppercase}.ei .action-filters{display:flex;gap:7px;overflow-x:auto;padding-bottom:3px}.ei .action-filter{white-space:nowrap;border:1px solid #dfe4ea;background:#fff;color:#4d5868;border-radius:999px;padding:7px 12px;font-size:.74rem;font-weight:800}.ei .action-filter.active{background:#222b38;color:#fff;border-color:#222b38}.ei .action-table th{font-size:.66rem;text-transform:uppercase;letter-spacing:.05em;color:#788395;white-space:nowrap}.ei .action-table td{font-size:.78rem;vertical-align:middle}.ei .action-badge{display:inline-flex;padding:5px 8px;border-radius:999px;font-size:.68rem;font-weight:850}.ei .action-open{background:#fff0f2;color:#a32b43}.ei .action-build{background:#fff4dd;color:#8a5a00}.ei .action-close{background:#e8f0fb;color:#23548a}.ei .action-activate{background:#e5f6ef;color:#0b6b54}.ei .action-maintain{background:#eef0f3;color:#505b6a}.ei .action-table tbody tr{cursor:pointer}.ei .action-table tbody tr:hover{background:#fff7fa}.ei .action-table tbody tr.is-selected{background:#fff0f5;box-shadow:inset 4px 0 #d91785}.ei .action-table-wrap{max-height:510px;overflow:auto}.ei .table-help{font-size:.7rem;color:var(--muted)}
  .ei .index-grid{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:10px}.ei .index-box{border:1px solid var(--line);border-radius:15px;padding:15px;background:#fafbfc}.ei .index-box strong{display:block;font-size:1.5rem;line-height:1}.ei .index-box span{display:block;margin-top:6px;font-size:.7rem;color:var(--muted);font-weight:800;text-transform:uppercase}.ei .index-formula{border-left:3px solid #d91785;padding-left:12px;font-size:.77rem;color:var(--muted)}.ei .index-table td,.ei .index-table th{font-size:.74rem;vertical-align:middle}.ei .index-table th{text-transform:uppercase;letter-spacing:.04em;color:#788395;white-space:nowrap}.ei .index-score{font-weight:850;color:var(--wine)}
  .ei .iode-chart-shell{border:1px solid var(--line);border-radius:16px;overflow-x:auto;background:#fff}.ei .iode-chart{display:block;min-width:920px;width:100%;height:330px}.ei .zone-summary{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:9px}.ei .zone-card{padding:12px 14px;border-radius:13px;border:1px solid rgba(0,0,0,.06)}.ei .zone-card strong{display:block;font-size:1.25rem}.ei .zone-card span{display:block;font-size:.68rem;font-weight:800;text-transform:uppercase;letter-spacing:.04em}.ei .zone-a{background:#fff2cc;color:#765b00}.ei .zone-b{background:#d9e8f5;color:#24577e}.ei .zone-c{background:#c9efdf;color:#116a53}.ei .iode-dot{cursor:pointer;transition:r .12s,opacity .12s}.ei .iode-dot:hover{r:7;opacity:1}.ei .iode-dot.is-selected{stroke:#111827;stroke-width:3;r:8;opacity:1}.ei .iode-chart-help{font-size:.72rem;color:var(--muted)}
  .ei .fullscreen-btn{border:1px solid #dfe4ea;background:#fff;color:#374151;border-radius:10px;padding:7px 10px;font-size:.74rem;font-weight:800}.ei .fullscreen-btn:hover{background:#f4f6f9}.ei .map-card:fullscreen,.ei .map-card.is-map-fullscreen{background:#f4f7fb;padding:18px;border-radius:0}.ei .map-card:fullscreen #electoralMap,.ei .map-card.is-map-fullscreen #electoralMap{height:calc(100vh - 105px)}.ei .map-card.is-map-fullscreen{position:fixed;inset:0;z-index:2000;width:100vw;height:100vh}.ei .map-card:fullscreen .map-heading,.ei .map-card.is-map-fullscreen .map-heading{margin-bottom:10px!important}
  .ei .socio-panel{background:linear-gradient(135deg,#fff 0%,#f6fbfa 100%);border-color:#dcece7}.ei .socio-kpis{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:9px}.ei .socio-kpi{padding:13px;border-radius:13px;background:#fff;border:1px solid #e0ece8}.ei .socio-kpi strong{display:block;font-size:1.25rem}.ei .socio-kpi span{display:block;font-size:.66rem;color:var(--muted);font-weight:800;text-transform:uppercase}.ei .coverage-chip{display:inline-flex;padding:4px 7px;border-radius:999px;background:#e9f4f0;color:#176b58;font-size:.68rem;font-weight:800}
  .ei .colony-card{border:1px solid #dfe7f0;background:#f8fbff;border-radius:14px;padding:13px;margin-bottom:14px}.ei .colony-list{display:flex;flex-wrap:wrap;gap:6px}.ei .colony-chip{display:inline-flex;align-items:center;padding:6px 9px;border-radius:999px;background:#e8f0fb;color:#264f7b;font-size:.72rem;font-weight:750}.ei .colony-source{font-size:.69rem;color:var(--muted)}
  .ei .source-list a{color:var(--wine);text-decoration:none}.ei .source-list a:hover{text-decoration:underline}.ei .quality-row{display:flex;justify-content:space-between;gap:12px;padding:10px 0;border-bottom:1px solid var(--line)}.ei .quality-row:last-child{border:0}.ei .quality-row span{color:var(--muted);font-size:.78rem}.ei .quality-row strong{font-size:.85rem}
  .ei .stable-table th{font-size:.68rem;text-transform:uppercase;letter-spacing:.06em;color:#7a8494;white-space:nowrap}.ei .stable-table td{font-size:.82rem;vertical-align:middle}.ei .section-link{border:0;background:none;color:var(--wine);font-weight:800;padding:0;text-decoration:underline;text-decoration-color:#e9bdca;text-underline-offset:3px}
  .leaflet-tooltip.ei-tooltip{border:0;border-radius:10px;box-shadow:0 8px 20px rgba(0,0,0,.16);padding:8px 10px;font:12px/1.35 system-ui,sans-serif}
  @media(max-width:991.98px){.ei #electoralMap{height:520px}.ei .section-panel{min-height:0}.ei .map-toolbar{position:relative;left:auto;right:auto;top:auto;margin-bottom:10px}.ei .map-shell{display:flex;flex-direction:column}.ei .map-toolbar{order:-1}.ei .map-legend{bottom:12px}.ei .hero{padding:23px 20px}.ei .year-grid{grid-template-columns:1fr}.ei .projection-grid,.ei .action-kpis,.ei .index-grid{grid-template-columns:repeat(2,minmax(0,1fr))}.ei .zone-summary{grid-template-columns:1fr}}
</style>
@endpush

@section('content')
<div class="container-xxl ei pb-5">
  <section class="hero mb-4">
    <div class="position-relative d-flex flex-wrap justify-content-between align-items-start gap-3" style="z-index:1">
      <div><div class="eyebrow">Elección municipal de Morelia</div><h1>Así ha votado cada sección</h1><p class="mb-0 text-white-50">Consulta y compara los resultados oficiales de 2018, 2021 y 2024.</p></div>
      <a id="exportExcel" class="export-btn" data-base-url="{{ route('electoral.export.xlsx') }}" href="{{ route('electoral.export.xlsx', ['tasa' => 75]) }}"><i class="fa-solid fa-file-excel"></i>Exportar todo a Excel</a>
    </div>
  </section>

  <div class="row row-cols-1 row-cols-sm-2 row-cols-xl-5 g-3 mb-3">
    <div class="col"><div class="kpi"><div class="kpi-note">Elección 2024</div><div class="kpi-value">{{ number_format($current['lista_nominal']) }}</div><div class="kpi-label">Personas que podían votar</div></div></div>
    <div class="col"><div class="kpi"><div class="kpi-note">Elección 2024</div><div class="kpi-value">{{ number_format($current['participacion'] * 100, 1) }}%</div><div class="kpi-label">Personas que votaron</div></div></div>
    <div class="col"><div class="kpi"><div class="kpi-note">De los votos válidos de 2024</div><div class="kpi-value">{{ number_format($current['porcentaje_morena'] * 100, 1) }}%</div><div class="kpi-label">Votos para Morena y aliados</div></div></div>
    <div class="col"><div class="kpi"><div class="kpi-note">De {{ number_format($current['secciones']) }} con resultados</div><div class="kpi-value">{{ number_format($current['secciones_ganadas_morena']) }}</div><div class="kpi-label">Secciones ganadas en 2024</div></div></div>
    <div class="col"><div class="kpi"><div class="kpi-note">Comparando las tres elecciones</div><div class="kpi-value">{{ number_format($consistentMorena->count()) }}</div><div class="kpi-label">Ganadas las tres veces</div></div></div>
  </div>

  <div class="card-panel mb-3">
    <div class="d-flex flex-wrap justify-content-between align-items-start gap-2 mb-3">
      <div><h2 class="panel-title">Resultados generales de Morelia</h2><div class="panel-subtitle">Aquí puedes ver cuánta gente votó y cómo se repartieron los votos en cada elección.</div></div>
      <div class="composition-legend">
        <span><i style="background:#a20b51"></i>Morena y coalición</span>
        <span><i style="background:#7da6cc"></i>Otros votos válidos</span>
        <span><i style="background:#c9ced6"></i>Votos nulos y no registrados</span>
      </div>
    </div>
    <div class="year-grid">
      @foreach($summary as $year => $result)
        @php
          $otherValid = max(0, $result['votos_validos'] - $result['votos_morena']);
          $invalid = max(0, $result['votos_totales'] - $result['votos_validos']);
          $abstention = max(0, $result['lista_nominal'] - $result['votos_totales']);
          $cast = max(1, $result['votos_totales']);
        @endphp
        <article class="year-card">
          <div class="d-flex justify-content-between align-items-start gap-2"><h3>{{ $year }}</h3><span class="soft-pill">{{ number_format($result['participacion'] * 100, 1) }}% participación</span></div>
          <div class="year-total mt-3">{{ number_format($result['votos_totales']) }}</div><div class="small text-muted">votos emitidos</div>
          <div class="composition mt-3" aria-label="Composición del voto {{ $year }}">
            <span class="morena" style="width:{{ ($result['votos_morena'] / $cast) * 100 }}%"></span>
            <span class="otros" style="width:{{ ($otherValid / $cast) * 100 }}%"></span>
            <span class="invalidos" style="width:{{ ($invalid / $cast) * 100 }}%"></span>
          </div>
          <div class="quality-row"><span>Morena y coalición</span><strong>{{ number_format($result['votos_morena']) }} · {{ number_format($result['porcentaje_morena'] * 100, 1) }}%</strong></div>
          <div class="quality-row"><span>Otros votos válidos</span><strong>{{ number_format($otherValid) }}</strong></div>
          <div class="quality-row"><span>Votos nulos y no registrados</span><strong>{{ number_format($invalid) }}</strong></div>
          <div class="quality-row"><span>Personas que no votaron (estimado)</span><strong>{{ number_format($abstention) }}</strong></div>
          <div class="quality-row"><span>Secciones ganadas por Morena y aliados</span><strong>{{ number_format($result['secciones_ganadas_morena']) }}</strong></div>
        </article>
      @endforeach
    </div>
  </div>

  <div class="card-panel projection-panel mb-3">
    <div class="row g-3 align-items-center">
      <div class="col-xl-7">
        <div class="d-flex flex-wrap justify-content-between align-items-start gap-2 mb-3">
          <div><h2 class="panel-title">¿Cuántos afiliados podrían acudir?</h2><div class="panel-subtitle">El cálculo usa solamente los registros de Morelia que tienen una sección válida.</div></div>
          @if($operation['alcance_restringido'])<span class="soft-pill"><i class="fa-solid fa-shield-halved me-1"></i>Solo aparecen los distritos que puedes consultar</span>@endif
        </div>
        <div class="projection-grid">
          <div class="projection-stat"><span>Registros que sí se pueden usar</span><strong>{{ number_format($operation['registrados']) }}</strong></div>
          <div class="projection-stat"><span>Afiliados confirmados</span><strong class="text-success">{{ number_format($operation['afiliados_si']) }}</strong></div>
          <div class="projection-stat"><span>Personas no afiliadas</span><strong class="text-danger">{{ number_format($operation['afiliados_no']) }}</strong></div>
          <div class="projection-stat"><span>Secciones con afiliados</span><strong>{{ number_format($operation['secciones_cubiertas']) }}</strong><small class="text-muted"> / {{ number_format($quality['current_sections']) }}</small></div>
          <div class="projection-stat accent"><span>Podrían acudir</span><strong id="projectedCity">{{ number_format(round($operation['afiliados_si'] * $operation['tasa_movilizacion_inicial'])) }}</strong></div>
        </div>
        @if($operation['registros_fuera_mapa'] > 0)<div class="small text-warning-emphasis mt-2"><i class="fa-solid fa-triangle-exclamation me-1"></i>{{ number_format($operation['registros_fuera_mapa']) }} registros no se incluyeron porque su sección no aparece en el mapa actual.</div>@endif
      </div>
      <div class="col-xl-5">
        <div class="scenario-box">
          <div class="fw-bold">De los {{ number_format($operation['afiliados_si']) }} afiliados confirmados, ¿qué porcentaje creemos que irá a votar?</div>
          <div class="small text-muted mt-1 mb-2">Elige una opción o mueve la barra para probar otro porcentaje.</div>
          <div class="scenario-buttons mb-3">
            <button type="button" class="scenario-btn" data-rate="60">Bajo · 60%</button>
            <button type="button" class="scenario-btn active" data-rate="75">Medio · 75%</button>
            <button type="button" class="scenario-btn" data-rate="90">Alto · 90%</button>
          </div>
          <label for="mobilizationRate" class="small fw-bold d-flex justify-content-between align-items-center"><span>Porcentaje que podría asistir</span><output id="mobilizationRateLabel" class="range-value">75%</output></label>
          <input id="mobilizationRate" type="range" class="form-range mt-2" min="50" max="100" step="5" value="75" aria-describedby="projectionSentence">
          <div id="projectionSentence" class="projection-result mt-2">Si asiste el 75%, podrían acudir <strong>{{ number_format(round($operation['afiliados_si'] * $operation['tasa_movilizacion_inicial'])) }} personas</strong>.</div>
          <p class="projection-note mt-3 mb-0">Este cálculo sirve para organizar el trabajo. No predice el resultado ni significa que sean votos nuevos.</p>
        </div>
      </div>
    </div>
  </div>

  <div class="card-panel mb-3" id="electoralIndex">
    <div class="row g-4 align-items-start">
      <div class="col-xl-5">
        <div class="eyebrow text-danger">Resumen por sección</div>
        <h2 class="panel-title mt-1">¿Qué tan favorable se ve cada sección?</h2>
        <div class="panel-subtitle mb-3">El IOE y el IDE conservan la ponderación original de 0 a 0.50. El IODE resulta de dividir IOE entre IDE.</div>
        <div class="index-grid mb-3">
          <div class="index-box"><strong>IOE</strong><span>Tamaño y participación</span></div>
          <div class="index-box"><strong>IDE</strong><span>Dificultad para competir</span></div>
          <div class="index-box"><strong>IODE</strong><span>Déficit o superávit</span></div>
        </div>
        <p class="index-formula mb-2"><strong>En pocas palabras:</strong> IOE muestra la oferta disponible e IDE la demanda de trabajo. Ambos llegan como máximo a 0.50 y el punto exacto de equilibrio es 1. Para organizar el trabajo se usa una franja de tolerancia: menos de 0.75 es déficit, de 0.75 a 1.25 es equilibrio y más de 1.25 es sobreoferta.</p>
        <p class="small text-muted mb-2">Hay cálculo para {{ number_format($electoralIndex['secciones_calculadas']) }} secciones. En {{ number_format($electoralIndex['secciones_sin_indice']) }} faltan datos y por eso aparece “Sin dato”.</p>
        <p class="small text-muted mb-0">Para saber cuántas personas pueden votar se usa el padrón del INE de diciembre de 2023. Este dato no es un conteo de población.</p>
      </div>
      <div class="col-xl-7">
        <div class="d-flex justify-content-between align-items-start gap-2 mb-2"><div><strong class="small">Secciones que requieren más atención</strong><div class="table-help">Primero aparecen las que tienen mayor déficit. Es una guía de trabajo, no una predicción.</div></div><span class="soft-pill">IODE menor primero</span></div>
        <div class="table-responsive">
          <table class="table index-table align-middle mb-0">
            <thead><tr><th>Sección</th><th class="text-end">Oferta</th><th class="text-end">Demanda</th><th class="text-end">IODE</th><th>Prioridad</th></tr></thead>
            <tbody>
              @foreach($topIode as $row)
              <tr><td><button class="section-link" data-section="{{ $row['seccion'] }}">{{ $row['seccion'] }}</button></td><td class="text-end">{{ number_format($row['indice_electoral']['ioe'], 3) }}</td><td class="text-end">{{ number_format($row['indice_electoral']['ide'], 3) }}</td><td class="text-end index-score">{{ $row['indice_electoral']['iode'] === null ? 'Sin dato' : number_format($row['indice_electoral']['iode'], 2) }}</td><td>{{ $row['indice_electoral']['estrategia'] }}</td></tr>
              @endforeach
            </tbody>
          </table>
        </div>
      </div>
    </div>
    <hr class="my-4">
    <div class="d-flex flex-wrap justify-content-between align-items-start gap-2 mb-3">
      <div><h3 class="panel-title">¿En qué zona está cada sección?</h3><div class="panel-subtitle">Cada punto representa una sección. Haz clic en un punto para verla en el mapa.</div></div>
      <span class="soft-pill">IODE = oferta ÷ demanda</span>
    </div>
    <div class="zone-summary mb-3" aria-label="Resumen de secciones por zona IODE">
      <div class="zone-card zone-a"><strong id="zoneCountA">—</strong><span>Zona A · déficit · menor de 0.75</span></div>
      <div class="zone-card zone-b"><strong id="zoneCountB">—</strong><span>Zona B · equilibrio · 0.75 a 1.25</span></div>
      <div class="zone-card zone-c"><strong id="zoneCountC">—</strong><span>Zona C · sobreoferta · mayor de 1.25</span></div>
    </div>
    <div class="iode-chart-shell"><svg id="iodeChart" class="iode-chart" role="img" aria-label="Distribución del IODE de las secciones de Morelia"></svg></div>
    <p class="iode-chart-help mt-2 mb-0">La altura de los puntos solo sirve para separarlos. El valor que importa es su posición horizontal. Verde significa sobreoferta y menor prioridad; no significa que electoralmente sea una sección favorable.</p>
  </div>

  <div class="card-panel socio-panel mb-3" id="socioeconomicLayer">
    <div class="row g-4 align-items-center">
      <div class="col-xl-7">
        <div class="eyebrow text-success">Datos de las zonas de Morelia</div>
        <h2 class="panel-title mt-1">¿Cómo son las condiciones de cada sección?</h2>
        <div class="panel-subtitle mb-3">Muestra datos del Censo 2020 sobre educación, salud, vivienda, servicios e internet.</div>
        <div class="socio-kpis">
          <div class="socio-kpi"><strong>{{ number_format($socioeconomic['secciones_con_datos']) }}</strong><span>Secciones con información</span></div>
          <div class="socio-kpi"><strong>{{ number_format($socioeconomic['cobertura_alta']) }}</strong><span>Secciones casi completas</span></div>
          <div class="socio-kpi"><strong>2020</strong><span>Año de los datos</span></div>
        </div>
      </div>
      <div class="col-xl-5">
        <p class="index-formula mb-2"><strong>Cómo usarla:</strong> elige un tema en el mapa y haz clic en una sección. A la derecha aparecerán sus datos.</p>
        <p class="small text-muted mb-0">Estos datos describen zonas completas. No muestran cuánto gana una persona, quién vive ahí ni por quién vota.</p>
      </div>
    </div>
  </div>

  <div class="card-panel mb-3" id="actionBoard">
    <div class="row g-3 align-items-start mb-3">
      <div class="col-xl-5">
        <div class="eyebrow text-danger">Lista para organizar el trabajo</div>
        <h2 class="panel-title mt-1">¿En qué secciones hace falta más trabajo?</h2>
        <div class="panel-subtitle">La lista toma en cuenta el IODE, los afiliados registrados y la diferencia de votos de 2024.</div>
      </div>
      <div class="col-xl-7">
        <div class="action-kpis">
          <div class="action-kpi"><strong class="text-danger">{{ number_format($operation['sin_base_si']) }}</strong><span>Sin afiliados confirmados</span></div>
          <div class="action-kpi"><strong class="text-warning-emphasis">{{ number_format($operation['estructura_minima']) }}</strong><span>Con 1 a 9 afiliados</span></div>
          <div class="action-kpi"><strong class="text-primary">{{ number_format($operation['derrotas_margen_corto']) }}</strong><span>Perdidas por 100 votos o menos</span></div>
          <div class="action-kpi"><strong class="text-success">{{ number_format($operation['estructura_fuerte']) }}</strong><span>Con 100 afiliados o más</span></div>
        </div>
      </div>
    </div>
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-2">
      <div class="action-filters" aria-label="Filtrar prioridades">
        <button type="button" class="action-filter active" data-action-filter="priority">Ver primero las más importantes</button>
        <button type="button" class="action-filter" data-action-filter="no-base">Sin afiliados</button>
        <button type="button" class="action-filter" data-action-filter="fragile">Con 1 a 9 afiliados</button>
        <button type="button" class="action-filter" data-action-filter="close-loss">Perdidas por 100 votos o menos</button>
        <button type="button" class="action-filter" data-action-filter="strong">Con 100 afiliados o más</button>
        <button type="button" class="action-filter" data-action-filter="all">Todas</button>
      </div>
      <div id="actionTableCount" class="table-help">Cargando secciones…</div>
    </div>
    <div class="table-responsive action-table-wrap">
      <table class="table action-table align-middle mb-0">
        <thead class="sticky-top bg-white"><tr><th>Sección</th><th>Resultado 2024</th><th class="text-end">Votos de diferencia</th><th class="text-end">IODE</th><th>Zona</th><th class="text-end">Afiliados</th><th class="text-end">Podrían asistir</th><th class="text-end">Lista nominal</th><th>Situación actual</th><th>Qué hace falta</th></tr></thead>
        <tbody id="actionTableBody"><tr><td colspan="10" class="text-center text-muted py-4">Preparando la lista…</td></tr></tbody>
      </table>
    </div>
    <div class="table-help mt-2"><i class="fa-solid fa-circle-info me-1"></i>“Podrían asistir” usa el porcentaje que elegiste arriba. No se suman como votos nuevos porque algunas de esas personas pudieron votar en 2024. Haz clic en una fila para ver la sección en el mapa.</div>
  </div>

  <div class="row g-3 mb-3">
    <div class="col-xl-8">
      <div class="card-panel h-100 map-card" id="mapCard">
        <div class="d-flex flex-wrap justify-content-between align-items-start gap-2 mb-3 map-heading">
          <div><h2 class="panel-title">Mapa de las secciones</h2><div class="panel-subtitle">Elige qué quieres ver y después haz clic en una sección.</div></div>
          <div class="d-flex flex-wrap gap-2"><span class="soft-pill"><i class="fa-solid fa-database me-1"></i>Información general por zona, no por persona</span><button type="button" class="fullscreen-btn" id="mapFullscreen"><i class="fa-solid fa-expand me-1"></i><span>Abrir mapa completo</span></button></div>
        </div>
        <div class="map-shell">
          <div class="map-toolbar">
            <div class="row g-2 align-items-center">
              <div class="col-md-8"><label for="metricSelect" class="visually-hidden">Qué quieres ver en el mapa</label><select id="metricSelect" class="form-select form-select-sm">
                <option value="victories">Cuántas veces ganó Morena y sus aliados</option>
                <option value="morena2024">Porcentaje de votos de Morena y sus aliados en 2024</option>
                <option value="turnout2024">Porcentaje de personas que votaron en 2024</option>
                <option value="invalid2024">Porcentaje de votos nulos y no registrados en 2024</option>
                <option value="abstention2024">Porcentaje estimado de personas que no votaron en 2024</option>
                <option value="margin2024">Votos de diferencia frente al principal competidor en 2024</option>
                <option value="trend">Si el apoyo a Morena subió o bajó de 2021 a 2024</option>
                <option value="nominal2024">Personas que podían votar en 2024</option>
                <option value="ioe">IOE · Tamaño y participación</option>
                <option value="ide">IDE · Dificultad para competir</option>
                <option value="iode">IODE · Déficit o superávit de la sección</option>
                <option value="socialLag">Condiciones de vida · Rezago social</option>
                <option value="noInternet">Condiciones de vida · Viviendas sin internet</option>
                <option value="basicEducation">Condiciones de vida · Educación básica incompleta</option>
                <option value="noHealth">Condiciones de vida · Personas sin acceso a servicios de salud</option>
                <option value="overcrowding">Condiciones de vida · Viviendas con demasiadas personas</option>
                <option value="affiliatesYes">Afiliados confirmados por sección</option>
                <option value="operationalCoverage">Afiliados como porcentaje de la lista nominal</option>
                <option value="mobilizedBase">Afiliados que podrían asistir</option>
                <option value="actionPriority">Secciones que necesitan atención primero</option>
              </select></div>
              <div class="col-md-4"><input id="sectionSearch" class="form-control form-control-sm" inputmode="numeric" placeholder="Escribe una sección" aria-describedby="sectionSearchFeedback"><div id="sectionSearchFeedback" class="invalid-feedback">Esa sección no aparece en el mapa vigente.</div></div>
            </div>
          </div>
          <div id="electoralMap" aria-label="Mapa electoral por sección de Morelia"></div>
          <div id="mapLegend" class="map-legend"></div>
        </div>
      </div>
    </div>
    <div class="col-xl-4">
      <div class="card-panel section-panel" id="sectionPanel">
        <div class="text-center py-5 text-muted" id="emptySection">
          <i class="fa-solid fa-arrow-pointer fa-2x mb-3"></i>
          <div class="fw-bold text-dark">Selecciona una sección</div>
          <div class="small mt-1">Aquí verás sus colonias, resultados, afiliados y condiciones de vida.</div>
        </div>
        <div id="sectionDetail" class="d-none"></div>
      </div>
    </div>
  </div>

  <div class="row g-3">
    <div class="col-xl-8">
      <div class="card-panel h-100">
        <div class="d-flex justify-content-between align-items-start mb-3"><div><h2 class="panel-title">Secciones que Morena y sus aliados ganaron las tres veces</h2><div class="panel-subtitle">Aparecen únicamente las secciones que tienen resultados en 2018, 2021 y 2024.</div></div><span class="soft-pill">{{ $consistentMorena->count() }} secciones</span></div>
        <div class="table-responsive"><table class="table stable-table align-middle mb-0"><thead><tr><th>Sección</th><th>Distrito local</th><th>Distrito federal</th><th class="text-end">2018</th><th class="text-end">2021</th><th class="text-end">2024</th><th class="text-end">Personas que votaron en 2024</th></tr></thead><tbody>
          @forelse($consistentMorena as $row)
          <tr><td><button class="section-link" data-section="{{ $row['seccion'] }}">{{ $row['seccion'] }}</button></td><td>{{ $row['distrito_local'] }}</td><td>{{ $row['distrito_federal'] }}</td><td class="text-end">{{ number_format($row['historial']['2018']['porcentaje_morena'] * 100, 1) }}%</td><td class="text-end">{{ number_format($row['historial']['2021']['porcentaje_morena'] * 100, 1) }}%</td><td class="text-end fw-bold">{{ number_format($row['historial']['2024']['porcentaje_morena'] * 100, 1) }}%</td><td class="text-end">{{ number_format($row['historial']['2024']['participacion'] * 100, 1) }}%</td></tr>
          @empty<tr><td colspan="7" class="text-center text-muted py-4">No hay secciones con resultados en los tres años.</td></tr>@endforelse
        </tbody></table></div>
      </div>
    </div>
    <div class="col-xl-4">
      <div class="card-panel mb-3">
        <h2 class="panel-title">¿Cuántas secciones tienen información?</h2><div class="panel-subtitle mb-2">Algunas secciones cambiaron o fueron creadas después de una elección.</div>
        <div class="quality-row"><span>Secciones que existen actualmente</span><strong>{{ number_format($quality['current_sections']) }}</strong></div>
        <div class="quality-row"><span>Con resultados de 2024</span><strong>{{ number_format($quality['with_2024_results']) }}</strong></div>
        <div class="quality-row"><span>Con resultados en los tres años</span><strong>{{ number_format($quality['three_cycle_sections']) }}</strong></div>
        <div class="quality-row"><span>Nuevas o que cambiaron de límites</span><strong>{{ number_format($quality['reconfigured_or_new']) }}</strong></div>
      </div>
      <div class="card-panel">
        <h2 class="panel-title">¿De dónde salen los datos?</h2><div class="panel-subtitle mb-3">Los resultados vienen de fuentes oficiales y se sumaron por sección.</div>
        <div class="source-list small">
          @foreach($metadata['fuentes'] as $source)
          <div class="mb-2"><strong>{{ $source['anio'] }} · {{ $source['institucion'] }}</strong><br><a href="{{ $source['url'] }}" target="_blank" rel="noopener">{{ $source['descripcion'] }}</a></div>
          @endforeach
          <div class="mb-2"><strong>2020 · CONEVAL</strong><br><a href="https://www.coneval.org.mx/Medicion/IRS/Paginas/Rezago_social_AGEB_2020.aspx" target="_blank" rel="noopener">Grado de Rezago Social por AGEB urbana</a></div>
          <div class="mb-2"><strong>2020 · INEGI</strong><br><a href="https://www.inegi.org.mx/app/biblioteca/ficha.html?upc=889463807469" target="_blank" rel="noopener">Marco Geoestadístico del Censo 2020</a></div>
        </div>
        <hr>
        <p class="small text-muted mb-0">Las secciones pueden cambiar con el tiempo. Por eso solo se comparan los tres años cuando la misma sección aparece en 2018, 2021 y 2024.</p>
      </div>
    </div>
  </div>
</div>
@endsection

@push('scripts')
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script>
(() => {
  const map = L.map('electoralMap', {zoomControl:true, scrollWheelZoom:true}).setView([19.7008,-101.1844],12);
  L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png',{maxZoom:19,attribution:'&copy; OpenStreetMap'}).addTo(map);
  const number = new Intl.NumberFormat('es-MX');
  const percent = value => value === null || value === undefined ? 'Sin dato' : `${(Number(value)*100).toFixed(1)}%`;
  const escapeHtml = value => String(value).replace(/[&<>'"]/g, character => ({'&':'&amp;','<':'&lt;','>':'&gt;',"'":'&#039;','"':'&quot;'}[character]));
  const winnerLabels = {morena:'Morena y coalición',pan_prd_mc:'PAN PRD MC',pan_prd:'PAN PRD',pan_pri_prd:'PAN PRI PRD',pri:'PRI',pvem:'PVEM',mc:'Movimiento Ciudadano',pes:'PES',pesm:'PES Michoacán',rsp:'RSP',fxm:'Fuerza por México',nueva_alianza:'Nueva Alianza',independiente:'Candidatura independiente',independientes:'Candidaturas independientes',mm:'Más Michoacán',mich1:'Michoacán Primero',tmx:'Tiempo por México'};
  const palettes = {
    victories:{title:'Veces que ganó Morena y sus aliados',items:[['Ninguna','#edf0f4'],['Una vez','#f4bfd2'],['Dos veces','#d95c8d'],['Las tres veces','#790019']]},
    morena2024:{title:'Votos de Morena y sus aliados en 2024',items:[['Menos de 25%','#f2f2f2'],['25% a 35%','#f5b8cf'],['35% a 45%','#db5f91'],['45% o más','#790019']]},
    turnout2024:{title:'Personas que votaron en 2024',items:[['Menos de 45%','#f0f1f3'],['45% a 55%','#b8d8ea'],['55% a 65%','#5695bd'],['65% o más','#174a73']]},
    invalid2024:{title:'Votos nulos y no registrados en 2024',items:[['Menos de 2%','#f0f1f3'],['2% a 4%','#d8c5da'],['4% a 6%','#a96b9c'],['6% o más','#6a285f']]},
    abstention2024:{title:'Personas que no votaron en 2024',items:[['Menos de 35%','#e5f3ef'],['35% a 45%','#a9d3c7'],['45% a 55%','#e3b7a5'],['55% o más','#a84d43']]},
    margin2024:{title:'Diferencia de Morena en 2024',items:[['Perdió por 20 puntos o más','#982e45'],['Perdió por menos de 20 puntos','#efb2bf'],['Ganó por menos de 20 puntos','#9fc9bc'],['Ganó por 20 puntos o más','#0e6f57']]},
    trend:{title:'Cambio de 2021 a 2024',items:[['Bajó más de 5 puntos','#a83a50'],['Bajó hasta 5 puntos','#f0b4c0'],['Subió hasta 5 puntos','#acd2c6'],['Subió más de 5 puntos','#14745d']]},
    nominal2024:{title:'Personas que podían votar en 2024',items:[['Menos de 1,000','#f0f1f3'],['1,000 a 2,499','#c7d5ea'],['2,500 a 4,999','#789bc8'],['5,000 o más','#284f87']]},
    ioe:{title:'IOE · Tamaño y participación',items:[['Bajo · 0 a 0.124','#f2f2f2'],['Medio bajo · 0.125 a 0.249','#c7d5ea'],['Medio alto · 0.250 a 0.374','#789bc8'],['Alto · 0.375 a 0.500','#284f87']]},
    ide:{title:'IDE · Dificultad para competir',items:[['Baja · 0 a 0.124','#e5f3ef'],['Media baja · 0.125 a 0.249','#a9d3c7'],['Media alta · 0.250 a 0.374','#e3b7a5'],['Alta · 0.375 a 0.500','#a84d43']]},
    iode:{title:'IODE · Tres zonas de trabajo',items:[['Zona A · menor de 0.75 · déficit','#f2c94c'],['Zona B · 0.75 a 1.25 · equilibrio','#8cb8da'],['Zona C · mayor de 1.25 · sobreoferta','#74c9ac']]},
    socialLag:{title:'Rezago social en 2020',items:[['Muy bajo','#1b7f69'],['Bajo','#79b99d'],['Medio','#f0c96a'],['Alto','#e17b4f'],['Muy alto','#9b2f43']]},
    noInternet:{title:'Viviendas sin internet 2020',items:[['Menos de 20%','#dff1e8'],['20% a 39.9%','#9bcdb5'],['40% a 59.9%','#efc66a'],['60% o más','#a8444f']]},
    basicEducation:{title:'Educación básica incompleta 2020',items:[['Menos de 15%','#dff1e8'],['15% a 24.9%','#9bcdb5'],['25% a 34.9%','#efc66a'],['35% o más','#a8444f']]},
    noHealth:{title:'Personas sin acceso a servicios de salud',items:[['Menos de 20%','#dff1e8'],['20% a 29.9%','#9bcdb5'],['30% a 39.9%','#efc66a'],['40% o más','#a8444f']]},
    overcrowding:{title:'Viviendas con demasiadas personas',items:[['Menos de 2%','#dff1e8'],['2% a 4.9%','#9bcdb5'],['5% a 9.9%','#efc66a'],['10% o más','#a8444f']]},
    affiliatesYes:{title:'Afiliados confirmados',items:[['Ninguno','#f0f1f3'],['1 a 9','#f6c2d6'],['10 a 29','#dd6997'],['30 o más','#790019']]},
    operationalCoverage:{title:'Afiliados frente a la lista nominal',items:[['Sin afiliados','#f0f1f3'],['Menos de 2%','#c8dbed'],['2% a 5%','#689ac8'],['5% o más','#214f82']]},
    mobilizedBase:{title:'Afiliados que podrían asistir',items:[['Ninguno','#f0f1f3'],['1 a 9','#c9e5dc'],['10 a 24','#66b29b'],['25 o más','#126b55']]},
    actionPriority:{title:'Secciones que necesitan atención',items:[['Sin afiliados','#b62f48'],['Muy pocos afiliados (1–9)','#e6a73b'],['Se perdió por 100 votos o menos','#3f79b5'],['100 afiliados o más','#16836a'],['Seguimiento normal','#c7cdd5']]}
  };
  let rows = new Map(), layers = new Map(), geoLayer, selectedSection = null, actionFilter = 'priority';
  const metricSelect = document.getElementById('metricSelect');
  const legend = document.getElementById('mapLegend');
  const mobilizationRate = document.getElementById('mobilizationRate');
  const yesAffiliatesCity = {{ (int) $operation['afiliados_si'] }};
  const rate = () => Number(mobilizationRate.value) / 100;
  const mobilized = row => Math.round((row?.operacion?.afiliados_si || 0) * rate());
  const colorFor = (row, metric) => {
    if (!row) return '#d9dde3';
    const y24=row.historial['2024'];
    if(metric==='victories') return ['#edf0f4','#f4bfd2','#d95c8d','#790019'][Math.max(0,Math.min(3,row.victorias_morena))];
    if(!y24 && !['trend','affiliatesYes','mobilizedBase','socialLag','noInternet','basicEducation','noHealth','overcrowding'].includes(metric)) return '#d9dde3';
    if(metric==='morena2024'){const v=y24.porcentaje_morena;return v>=.45?'#790019':v>=.35?'#db5f91':v>=.25?'#f5b8cf':'#f2f2f2'}
    if(metric==='turnout2024'){const v=y24.participacion;return v>=.65?'#174a73':v>=.55?'#5695bd':v>=.45?'#b8d8ea':'#f0f1f3'}
    if(metric==='invalid2024'){const v=y24.votos_totales>0?(y24.votos_totales-y24.votos_validos)/y24.votos_totales:0;return v>=.06?'#6a285f':v>=.04?'#a96b9c':v>=.02?'#d8c5da':'#f0f1f3'}
    if(metric==='abstention2024'){const v=1-y24.participacion;return v>=.55?'#a84d43':v>=.45?'#e3b7a5':v>=.35?'#a9d3c7':'#e5f3ef'}
    if(metric==='margin2024'){const v=y24.margen_morena_pct;return v>=.20?'#0e6f57':v>=0?'#9fc9bc':v>=-.20?'#efb2bf':'#982e45'}
    if(metric==='trend'){const v=row.tendencia_morena_2021_2024;if(v===null)return '#d9dde3';return v>.05?'#14745d':v>=0?'#acd2c6':v>=-.05?'#f0b4c0':'#a83a50'}
    if(metric==='nominal2024'){const v=y24.lista_nominal;return v>=5000?'#284f87':v>=2500?'#789bc8':v>=1000?'#c7d5ea':'#f0f1f3'}
    if(['ioe','ide','iode'].includes(metric)){const index=row.indice_electoral;if(!index)return '#d9dde3';const v=index[metric];if(v===null||v===undefined)return '#d9dde3';if(metric==='ide')return v>=.375?'#a84d43':v>=.25?'#e3b7a5':v>=.125?'#a9d3c7':'#e5f3ef';if(metric==='iode')return v>1.25?'#74c9ac':v>=.75?'#8cb8da':'#f2c94c';return v>=.375?'#284f87':v>=.25?'#789bc8':v>=.125?'#c7d5ea':'#f2f2f2'}
    if(['socialLag','noInternet','basicEducation','noHealth','overcrowding'].includes(metric)){
      const socio=row.socioeconomico;if(!socio)return '#d9dde3';
      if(metric==='socialLag'){const v=socio.grado_rezago_valor;return v>=4.5?'#9b2f43':v>=3.5?'#e17b4f':v>=2.5?'#f0c96a':v>=1.5?'#79b99d':'#1b7f69'}
      const key={noInternet:'sin_internet',basicEducation:'educacion_basica_incompleta',noHealth:'sin_derechohabiencia',overcrowding:'hacinamiento'}[metric];const v=socio.indicadores[key];
      const limits={noInternet:[20,40,60],basicEducation:[15,25,35],noHealth:[20,30,40],overcrowding:[2,5,10]}[metric];
      return v>=limits[2]?'#a8444f':v>=limits[1]?'#efc66a':v>=limits[0]?'#9bcdb5':'#dff1e8';
    }
    if(metric==='affiliatesYes'){const v=row.operacion?.afiliados_si||0;return v>=30?'#790019':v>=10?'#dd6997':v>=1?'#f6c2d6':'#f0f1f3'}
    if(metric==='operationalCoverage'){const v=y24?.lista_nominal>0?(row.operacion?.afiliados_si||0)/y24.lista_nominal:0;return v>=.05?'#214f82':v>=.02?'#689ac8':v>0?'#c8dbed':'#f0f1f3'}
    if(metric==='mobilizedBase'){const v=mobilized(row);return v>=25?'#126b55':v>=10?'#66b29b':v>=1?'#c9e5dc':'#f0f1f3'}
    if(metric==='actionPriority'){
      const yes=row.operacion?.afiliados_si||0;
      const closeLoss=y24&&!y24.morena_gano&&Math.abs(y24.margen_morena)<=100;
      return yes===0?'#b62f48':closeLoss?'#3f79b5':yes<=9?'#e6a73b':yes>=100?'#16836a':'#c7cdd5';
    }
    return '#d9dde3';
  };
  const style = feature => {const row=rows.get(Number(feature.properties.SECCION));return {color:'#fff',weight:.8,fillColor:colorFor(row,metricSelect.value),fillOpacity:.86};};
  const refreshSelectedLayer = () => {
    document.querySelectorAll('[data-action-section]').forEach(tableRow=>tableRow.classList.toggle('is-selected',Number(tableRow.dataset.actionSection)===selectedSection));
    document.querySelectorAll('[data-iode-section]').forEach(dot=>dot.classList.toggle('is-selected',Number(dot.dataset.iodeSection)===selectedSection));
    if(selectedSection===null||!geoLayer)return;
    const selectedLayer=layers.get(selectedSection);
    if(!selectedLayer)return;
    geoLayer.resetStyle(selectedLayer);
    selectedLayer.setStyle({color:'#ffd43b',weight:5,fillOpacity:.96,opacity:1});
    selectedLayer.bringToFront();
  };
  const renderLegend = () => {const p=palettes[metricSelect.value];legend.innerHTML=`<strong>${p.title}</strong>${p.items.map(([label,color])=>`<div class="legend-row"><span class="legend-swatch" style="background:${color}"></span><span>${label}</span></div>`).join('')}<div class="legend-row"><span class="legend-swatch" style="background:#d9dde3"></span><span>Falta información</span></div>`;};
  const renderIodeChart = () => {
    const svg=document.getElementById('iodeChart');
    const indexed=[...rows.values()].filter(row=>row.indice_electoral?.iode!==null&&row.indice_electoral?.iode!==undefined);
    const zones={a:indexed.filter(row=>row.indice_electoral.iode<.75),b:indexed.filter(row=>row.indice_electoral.iode>=.75&&row.indice_electoral.iode<=1.25),c:indexed.filter(row=>row.indice_electoral.iode>1.25)};
    document.getElementById('zoneCountA').textContent=number.format(zones.a.length);
    document.getElementById('zoneCountB').textContent=number.format(zones.b.length);
    document.getElementById('zoneCountC').textContent=number.format(zones.c.length);
    const width=1100,height=330,left=55,right=30,top=54,bottom=54,plotWidth=width-left-right,plotHeight=height-top-bottom;
    const observedMax=Math.max(1.5,...indexed.map(row=>row.indice_electoral.iode));
    const axisMax=Math.max(1.5,Math.ceil(observedMax*4)/4);
    const x=value=>left+(Math.min(value,axisMax)/axisMax)*plotWidth;
    const rect=(start,end,fill)=>`<rect x="${x(start)}" y="${top}" width="${Math.max(0,x(Math.min(end,axisMax))-x(start))}" height="${plotHeight}" fill="${fill}"/>`;
    const ticks=[];for(let value=0;value<=axisMax+.001;value+=.25){if(value<=1.5||Math.abs(value-Math.round(value))<.001||Math.abs(value-axisMax)<.01)ticks.push(Number(value.toFixed(2)))}
    const zoneLabels=`<text x="${(x(0)+x(.75))/2}" y="29" text-anchor="middle" font-size="14" font-weight="800" fill="#765b00">ZONA A · DÉFICIT</text><text x="${(x(.75)+x(1.25))/2}" y="29" text-anchor="middle" font-size="14" font-weight="800" fill="#24577e">ZONA B · EQUILIBRIO</text><text x="${(x(1.25)+x(axisMax))/2}" y="29" text-anchor="middle" font-size="14" font-weight="800" fill="#116a53">ZONA C · SOBREOFERTA</text>`;
    const backgrounds=rect(0,.75,'#fff2cc')+rect(.75,1.25,'#d9e8f5')+rect(1.25,axisMax,'#c9efdf');
    const guides=`<line x1="${x(.75)}" y1="${top}" x2="${x(.75)}" y2="${top+plotHeight}" stroke="#475569" stroke-dasharray="4 4"/><line x1="${x(1.25)}" y1="${top}" x2="${x(1.25)}" y2="${top+plotHeight}" stroke="#475569" stroke-dasharray="4 4"/>`;
    const axis=ticks.map(value=>`<line x1="${x(value)}" y1="${top+plotHeight}" x2="${x(value)}" y2="${top+plotHeight+8}" stroke="#111827"/><text x="${x(value)}" y="${height-20}" text-anchor="middle" font-size="12" fill="#374151">${value.toFixed(2)}</text>`).join('')+`<line x1="${left}" y1="${top+plotHeight}" x2="${width-right}" y2="${top+plotHeight}" stroke="#111827" stroke-width="2"/>`;
    const dots=indexed.map(row=>{const value=row.indice_electoral.iode;const zone=value<.75?'a':value<=1.25?'b':'c';const colors={a:'#9a7600',b:'#285f88',c:'#13765c'};const y=top+18+((Number(row.seccion)*37)%Math.max(1,plotHeight-36));return `<circle class="iode-dot" data-iode-section="${row.seccion}" cx="${x(value)}" cy="${y}" r="4.5" fill="${colors[zone]}" opacity=".74" tabindex="0" role="button" aria-label="Sección ${row.seccion}, IODE ${value.toFixed(2)}"><title>Sección ${row.seccion} · IODE ${value.toFixed(2)} · ${escapeHtml(row.indice_electoral.estrategia)}</title></circle>`}).join('');
    svg.setAttribute('viewBox',`0 0 ${width} ${height}`);svg.innerHTML=backgrounds+zoneLabels+guides+dots+axis;
    svg.querySelectorAll('[data-iode-section]').forEach(dot=>{const open=()=>{metricSelect.value='iode';if(geoLayer)geoLayer.setStyle(style);renderLegend();showSection(dot.dataset.iodeSection);document.getElementById('mapCard').scrollIntoView({behavior:'smooth',block:'center'})};dot.addEventListener('click',open);dot.addEventListener('keydown',event=>{if(event.key==='Enter'||event.key===' '){event.preventDefault();open()}})});
    refreshSelectedLayer();
  };
  const diagnose = row => {
    const h24=row.historial['2024'];
    const yes=row.operacion?.afiliados_si||0;
    const gap=h24&&!h24.morena_gano?Math.abs(h24.margen_morena):0;
    const closeLoss=Boolean(h24&&!h24.morena_gano&&gap<=100);
    let structure='En crecimiento', structureClass='action-build';
    if(yes===0){structure='Sin afiliados';structureClass='action-open'}
    else if(yes<=9){structure='Muy pocos afiliados';structureClass='action-build'}
    else if(yes>=100){structure='100 afiliados o más';structureClass='action-activate'}
    else if(yes>=30){structure='30 afiliados o más';structureClass='action-maintain'}
    let action='Sumar más afiliados', actionClass='action-build', rank=3;
    if(!h24){action='Revisar la información';actionClass='action-maintain';rank=6}
    else if(yes===0){action='Comenzar el registro';actionClass='action-open';rank=1}
    else if(closeLoss){action='Atender primero';actionClass='action-close';rank=0}
    else if(yes<=9){action='Sumar más afiliados';actionClass='action-build';rank=2}
    else if(yes>=100){action='Mantener el contacto';actionClass='action-activate';rank=h24.morena_gano?4:3}
    else if(h24.morena_gano){action='Cuidar la ventaja';actionClass='action-maintain';rank=5}
    const iode=row.indice_electoral?.iode??null;
    const zone=iode===null?'Sin dato':iode<.75?'Zona A':iode<=1.25?'Zona B':'Zona C';
    const zoneClass=iode===null?'':iode<.75?'zone-a':iode<=1.25?'zone-b':'zone-c';
    return {h24,yes,gap,closeLoss,structure,structureClass,action,actionClass,rank,iode,zone,zoneClass};
  };
  const renderActionTable = () => {
    let diagnosed=[...rows.values()].map(row=>({row,...diagnose(row)}));
    if(actionFilter==='no-base')diagnosed=diagnosed.filter(item=>item.yes===0);
    if(actionFilter==='fragile')diagnosed=diagnosed.filter(item=>item.yes>=1&&item.yes<=9);
    if(actionFilter==='close-loss')diagnosed=diagnosed.filter(item=>item.closeLoss);
    if(actionFilter==='strong')diagnosed=diagnosed.filter(item=>item.yes>=100);
    diagnosed.sort((a,b)=>actionFilter==='all'
      ? a.row.seccion-b.row.seccion
      : a.rank-b.rank||a.gap-b.gap||b.yes-a.yes||a.row.seccion-b.row.seccion);
    document.getElementById('actionTableCount').textContent=`${number.format(diagnosed.length)} secciones mostradas`;
    const body=document.getElementById('actionTableBody');
    body.innerHTML=diagnosed.map(item=>{
      const {row,h24,yes,gap,structure,structureClass,action,actionClass,iode,zone,zoneClass}=item;
      const result=h24?(h24.morena_gano?'Victoria':'Derrota'):'Sin resultado';
      const margin=h24?(h24.morena_gano?`+${number.format(h24.margen_morena)}`:`-${number.format(gap)}`):'—';
      return `<tr data-action-section="${row.seccion}">
        <td><strong>${row.seccion}</strong></td><td>${result}</td><td class="text-end ${h24?.morena_gano?'text-success':'text-danger'}">${margin}</td><td class="text-end fw-bold">${iode===null?'—':iode.toFixed(2)}</td><td><span class="action-badge ${zoneClass}">${zone}</span></td>
        <td class="text-end fw-bold">${number.format(yes)}</td><td class="text-end">${number.format(mobilized(row))}</td><td class="text-end">${h24?number.format(h24.lista_nominal):'—'}</td>
        <td><span class="action-badge ${structureClass}">${structure}</span></td><td><span class="action-badge ${actionClass}">${action}</span></td>
      </tr>`;
    }).join('')||'<tr><td colspan="10" class="text-center text-muted py-4">No hay secciones en este filtro.</td></tr>';
    body.querySelectorAll('[data-action-section]').forEach(tableRow=>tableRow.addEventListener('click',()=>{
      showSection(tableRow.dataset.actionSection);
      document.getElementById('electoralMap').scrollIntoView({behavior:'smooth',block:'center'});
    }));
    refreshSelectedLayer();
  };
  const showSection = (section, focus=true) => {
    const row=rows.get(Number(section)); if(!row)return;
    const sectionSearch=document.getElementById('sectionSearch');sectionSearch.value=row.seccion;sectionSearch.classList.remove('is-invalid');
    const previousLayer=selectedSection!==null?layers.get(selectedSection):null;
    if(previousLayer&&geoLayer)geoLayer.resetStyle(previousLayer);
    selectedSection=Number(section);
    const layer=layers.get(Number(section));
    refreshSelectedLayer();
    if(focus&&layer){map.fitBounds(layer.getBounds(),{padding:[45,45],maxZoom:15});layer.openTooltip();}
    document.getElementById('emptySection').classList.add('d-none');
    const detail=document.getElementById('sectionDetail'); detail.classList.remove('d-none');
    const h24=row.historial['2024'];
    const other24=h24?Math.max(0,h24.votos_validos-h24.votos_morena):0;
    const invalid24=h24?Math.max(0,h24.votos_totales-h24.votos_validos):0;
    const abstention24=h24?Math.max(0,h24.lista_nominal-h24.votos_totales):0;
    const op=row.operacion||{registrados:0,afiliados_si:0,afiliados_no:0};
    const index=row.indice_electoral;
    const socio=row.socioeconomico;
    const colonies=row.colonias||{nombres:[],total_encontradas:0,fuente:null};
    const projected=mobilized(row);
    const listCoverage=h24&&h24.lista_nominal>0?op.afiliados_si/h24.lista_nominal:null;
    const historicalReference=h24&&h24.votos_morena>0?projected/h24.votos_morena:null;
    const indexZone=index&&index.iode!==null?(index.iode<.75?{label:'Zona A · déficit',color:'#765b00',background:'#fff2cc'}:index.iode<=1.25?{label:'Zona B · equilibrio',color:'#24577e',background:'#d9e8f5'}:{label:'Zona C · sobreoferta',color:'#116a53',background:'#c9efdf'}):{label:'Sin dato',color:'#667085',background:'#eef0f3'};
    const elections=['2018','2021','2024'].map(year=>{
      const h=row.historial[year];
      if(!h)return `<article class="election-card"><div class="election-card-head"><span class="election-year">${year}</span><span class="soft-pill">No hay información para este año</span></div></article>`;
      const other=Math.max(0,h.votos_validos-h.votos_morena);
      const invalid=Math.max(0,h.votos_totales-h.votos_validos);
      return `<article class="election-card">
        <div class="election-card-head"><span class="election-year">${year}</span><span class="soft-pill ${h.morena_gano?'status-win':'status-loss'}">${winnerLabels[h.ganador]||h.ganador}</span></div>
        <div class="election-row"><span>Morena y coalición</span><strong>${number.format(h.votos_morena)} · ${percent(h.porcentaje_morena)}</strong></div>
        <div class="share-track"><span style="width:${Math.min(100,Math.max(0,h.porcentaje_morena*100))}%"></span></div>
        <div class="election-row"><span>Otros votos válidos</span><strong>${number.format(other)}</strong></div>
        <div class="election-row"><span>Votos nulos y no registrados</span><strong>${number.format(invalid)}</strong></div>
        <div class="election-row"><span>Personas que votaron</span><strong>${percent(h.participacion)}</strong></div>
        <div class="election-row"><span>Votos de diferencia para Morena</span><strong class="${h.margen_morena>=0?'text-success':'text-danger'}">${h.margen_morena>=0?'+':''}${number.format(h.margen_morena)}</strong></div>
      </article>`;
    }).join('');
    detail.innerHTML=`
      <div class="text-muted small text-uppercase fw-bold">Sección</div>
      <div class="section-number">${row.seccion}</div>
      <div class="d-flex flex-wrap gap-2 mb-3"><span class="soft-pill">Distrito local ${row.distrito_local??'Sin dato'}</span><span class="soft-pill">Distrito federal ${row.distrito_federal??'Sin dato'}</span><span class="soft-pill">Morena ganó ${row.victorias_morena} de ${row.elecciones_observadas}</span></div>
      <div class="colony-card">
        <div class="small fw-bold text-uppercase text-muted mb-2">Colonias relacionadas con la sección</div>
        ${colonies.nombres.length
          ? `<div class="colony-list">${colonies.nombres.map(name=>`<span class="colony-chip">${escapeHtml(name)}</span>`).join('')}</div>
             ${colonies.total_encontradas>colonies.nombres.length?`<div class="small text-muted mt-2">Hay ${number.format(colonies.total_encontradas-colonies.nombres.length)} nombres adicionales en la base.</div>`:''}
             <div class="colony-source mt-2">Fuente: ${escapeHtml(colonies.fuente)}. Son nombres de referencia; una colonia puede abarcar más de una sección.</div>`
          : '<div class="small text-muted">Todavía no hay nombres de colonia relacionados con esta sección.</div>'}
      </div>
      ${index?`<div class="operation-card">
        <div class="small fw-bold text-uppercase text-muted mb-2">Calificación de la sección</div>
        <div class="index-grid">
          <div class="section-stat"><span>IOE · oferta</span><strong>${index.ioe.toFixed(3)}</strong></div>
          <div class="section-stat"><span>IDE · demanda</span><strong>${index.ide.toFixed(3)}</strong></div>
          <div class="section-stat"><span>IODE · balance</span><strong style="color:${indexZone.color}">${index.iode===null?'Sin dato':index.iode.toFixed(2)}</strong></div>
        </div>
        <div class="soft-pill mb-1" style="color:${indexZone.color};background:${indexZone.background}">${indexZone.label}</div>
        <div class="election-row"><span>Qué indica</span><strong>${index.estrategia}</strong></div>
        <div class="election-row"><span>Personas registradas para votar en 2023</span><strong>${number.format(index.variables.padron_electoral_2023)}</strong></div>
        <div class="election-row"><span>Votos que habrían sido necesarios para ganar en 2024</span><strong>${number.format(index.variables.votos_para_ganar_2024)}</strong></div>
      </div>`:`<div class="alert alert-light border small">No se pudo calcular la oportunidad porque falta información del INE o de alguna elección.</div>`}
      ${socio?`<div class="operation-card" style="background:#f7fcfa;border-color:#d5e9e2">
        <div class="d-flex justify-content-between align-items-start gap-2 mb-2"><div class="small fw-bold text-uppercase text-muted">Condiciones de vida en 2020</div><span class="coverage-chip">Información para ${percent(socio.cobertura_territorial)} de la sección</span></div>
        <div class="election-row"><span>Grado de rezago social</span><strong>${socio.grado_rezago}</strong></div>
        <div class="election-row"><span>Educación básica incompleta</span><strong>${socio.indicadores.educacion_basica_incompleta.toFixed(1)}%</strong></div>
        <div class="election-row"><span>Personas sin acceso a servicios de salud</span><strong>${socio.indicadores.sin_derechohabiencia.toFixed(1)}%</strong></div>
        <div class="election-row"><span>Viviendas con demasiadas personas</span><strong>${socio.indicadores.hacinamiento.toFixed(1)}%</strong></div>
        <div class="election-row"><span>Viviendas sin internet</span><strong>${socio.indicadores.sin_internet.toFixed(1)}%</strong></div>
        <div class="election-row"><span>Viviendas sin computadora</span><strong>${socio.indicadores.sin_computadora.toFixed(1)}%</strong></div>
        <div class="election-row"><span>Personas incluidas en los datos del Censo</span><strong>${number.format(socio.poblacion_representada)}</strong></div>
        <p class="small text-muted mt-2 mb-0">El dato se calculó con ${number.format(socio.agebs)} zonas del Censo. No muestra ingresos ni preferencias de personas.</p>
      </div>`:`<div class="alert alert-light border small">No hay suficiente información del Censo para mostrar las condiciones de vida de esta sección.</div>`}
      ${h24?`<div class="section-summary mb-3">
        <div class="section-stat"><span>Morena 2024</span><strong>${number.format(h24.votos_morena)}</strong></div>
        <div class="section-stat"><span>Otros válidos</span><strong>${number.format(other24)}</strong></div>
        <div class="section-stat"><span>Nulos y no registrados</span><strong>${number.format(invalid24)}</strong></div>
        <div class="section-stat"><span>No votaron (estimado)</span><strong>${number.format(abstention24)}</strong></div>
      </div>`:''}
      <div class="operation-card">
        <div class="small fw-bold text-uppercase text-muted mb-2">Afiliados registrados actualmente</div>
        <div class="section-summary">
          <div class="section-stat"><span>Registros</span><strong>${number.format(op.registrados)}</strong></div>
          <div class="section-stat"><span>Afiliados confirmados</span><strong class="text-success">${number.format(op.afiliados_si)}</strong></div>
          <div class="section-stat"><span>Personas no afiliadas</span><strong class="text-danger">${number.format(op.afiliados_no)}</strong></div>
          <div class="section-stat"><span>Podrían asistir al ${mobilizationRate.value}%</span><strong>${number.format(projected)}</strong></div>
        </div>
        <div class="election-row"><span>Afiliados frente a las personas que podían votar</span><strong>${percent(listCoverage)}</strong></div>
        <div class="election-row"><span>Posibles asistentes frente a los votos de Morena en 2024</span><strong>${percent(historicalReference)}</strong></div>
        <p class="small text-muted mt-2 mb-0">Esta comparación solo ayuda a entender el tamaño de la base. No significa que sean votos nuevos ni dice por quién votó cada persona.</p>
      </div>
      <div class="small fw-bold text-uppercase text-muted">Resultados anteriores</div>
      ${elections}
      <p class="small text-muted mt-3 mb-0">Los resultados corresponden a toda la sección. No muestran por quién votó cada persona ni explican por sí solos por qué cambió el resultado.</p>`;
  };
  Promise.all([
    fetch(@json(route('electoral.data'))).then(response=>response.json()),
    fetch(@json(asset('maps/electoral/morelia-secciones.geojson'))).then(response=>response.json())
  ]).then(([dataset,geo])=>{
    rows=new Map(dataset.secciones.map(row=>[Number(row.seccion),row]));
    geoLayer=L.geoJSON(geo,{style,onEachFeature:(feature,layer)=>{const section=Number(feature.properties.SECCION);layers.set(section,layer);const row=rows.get(section);const colonyNames=row?.colonias?.nombres||[];const colonyLine=colonyNames.length?`<br><span class="text-muted">${colonyNames.slice(0,3).map(escapeHtml).join(' · ')}${colonyNames.length>3?` · +${colonyNames.length-3}`:''}</span>`:'<br><span class="text-muted">Sin colonias registradas</span>';layer.bindTooltip(`<strong>Sección ${section}</strong>${colonyLine}<br>${row?`Morena ganó ${row.victorias_morena} de ${row.elecciones_observadas}<br>${number.format(row.operacion?.afiliados_si||0)} afiliados confirmados`:'No hay resultados para esta sección'}`,{sticky:true,className:'ei-tooltip'});layer.on('click',()=>showSection(section));layer.on('mouseover',function(){this.setStyle(section===selectedSection?{weight:6,color:'#ffd43b'}:{weight:2,color:'#252b34'});this.bringToFront()});layer.on('mouseout',function(){if(section===selectedSection)refreshSelectedLayer();else geoLayer.resetStyle(this)})}}).addTo(map);
    map.fitBounds(geoLayer.getBounds(),{padding:[18,18]}); renderLegend(); renderActionTable(); renderIodeChart();
  }).catch(error=>{console.error(error);document.getElementById('electoralMap').innerHTML='<div class="alert alert-danger m-3">No fue posible cargar el mapa electoral.</div>';});
  metricSelect.addEventListener('change',()=>{if(geoLayer){geoLayer.setStyle(style);refreshSelectedLayer()}renderLegend()});
  const updateProjection = () => {
    const selectedRate=Number(mobilizationRate.value);
    const projected=Math.round(yesAffiliatesCity*rate());
    document.getElementById('mobilizationRateLabel').textContent=`${mobilizationRate.value}%`;
    document.getElementById('projectedCity').textContent=number.format(projected);
    document.getElementById('projectionSentence').innerHTML=`Si asiste el ${selectedRate}%, podrían acudir <strong>${number.format(projected)} personas</strong> de ${number.format(yesAffiliatesCity)} afiliados confirmados.`;
    const exportExcel=document.getElementById('exportExcel');exportExcel.href=`${exportExcel.dataset.baseUrl}?tasa=${selectedRate}`;
    document.querySelectorAll('[data-rate]').forEach(button=>button.classList.toggle('active',Number(button.dataset.rate)===selectedRate));
    if(geoLayer&&metricSelect.value==='mobilizedBase')geoLayer.setStyle(style);
    if(selectedSection!==null)showSection(selectedSection,false);
    if(rows.size)renderActionTable();
  };
  mobilizationRate.addEventListener('input',updateProjection);
  document.querySelectorAll('[data-rate]').forEach(button=>button.addEventListener('click',()=>{mobilizationRate.value=button.dataset.rate;updateProjection()}));
  document.querySelectorAll('[data-action-filter]').forEach(button=>button.addEventListener('click',()=>{
    actionFilter=button.dataset.actionFilter;
    document.querySelectorAll('[data-action-filter]').forEach(candidate=>candidate.classList.toggle('active',candidate===button));
    renderActionTable();
  }));
  document.getElementById('sectionSearch').addEventListener('change',event=>{const requested=Number(event.target.value);if(!rows.has(requested)){const previousLayer=selectedSection!==null?layers.get(selectedSection):null;if(previousLayer&&geoLayer)geoLayer.resetStyle(previousLayer);selectedSection=null;refreshSelectedLayer();document.getElementById('sectionDetail').classList.add('d-none');document.getElementById('emptySection').classList.remove('d-none');event.target.classList.add('is-invalid');document.getElementById('sectionSearchFeedback').textContent=`La sección ${event.target.value||'indicada'} no aparece en el mapa vigente; pudo cambiar de clave o de límites.`;return}showSection(requested)});
  const mapCard=document.getElementById('mapCard'),fullscreenButton=document.getElementById('mapFullscreen');
  const updateFullscreenButton=()=>{const active=document.fullscreenElement===mapCard||mapCard.classList.contains('is-map-fullscreen');fullscreenButton.querySelector('i').className=`fa-solid ${active?'fa-compress':'fa-expand'} me-1`;fullscreenButton.querySelector('span').textContent=active?'Cerrar pantalla completa':'Abrir mapa completo';setTimeout(()=>map.invalidateSize(),120)};
  fullscreenButton.addEventListener('click',async()=>{if(document.fullscreenEnabled&&mapCard.requestFullscreen){if(document.fullscreenElement===mapCard)await document.exitFullscreen();else await mapCard.requestFullscreen()}else{mapCard.classList.toggle('is-map-fullscreen');document.body.style.overflow=mapCard.classList.contains('is-map-fullscreen')?'hidden':'';updateFullscreenButton()}});
  document.addEventListener('fullscreenchange',updateFullscreenButton);
  document.addEventListener('keydown',event=>{if(event.key==='Escape'&&mapCard.classList.contains('is-map-fullscreen')){mapCard.classList.remove('is-map-fullscreen');document.body.style.overflow='';updateFullscreenButton()}});
  document.querySelectorAll('[data-section]').forEach(button=>button.addEventListener('click',()=>{showSection(button.dataset.section);document.getElementById('electoralMap').scrollIntoView({behavior:'smooth',block:'center'})}));
})();
</script>
@endpush
