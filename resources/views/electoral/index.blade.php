@extends('layouts.app')

@section('title', 'Inteligencia electoral de Morelia')

@push('styles')
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
<style>
  .ei{--ink:#18212f;--muted:#6c7687;--line:#e7ebf1;--wine:#7a0019;--pink:#d91785;--green:#14806a;--blue:#2458a6;color:var(--ink)}
  .ei .hero{position:relative;overflow:hidden;border-radius:24px;padding:28px 30px;color:#fff;background:linear-gradient(125deg,#21131a 0%,#74122f 56%,#d91785 130%);box-shadow:0 22px 55px rgba(73,12,37,.18)}
  .ei .hero:after{content:"";position:absolute;width:340px;height:340px;border:64px solid rgba(255,255,255,.055);border-radius:50%;right:-90px;top:-175px}
  .ei .eyebrow{font-size:.72rem;font-weight:800;letter-spacing:.16em;text-transform:uppercase;opacity:.75}
  .ei .hero h1{font-size:clamp(1.75rem,3vw,2.65rem);font-weight:850;letter-spacing:-.035em;margin:.4rem 0}
  .ei .card-panel,.ei .kpi{background:#fff;border:1px solid rgba(226,231,239,.95);border-radius:19px;box-shadow:0 10px 34px rgba(21,31,48,.055)}
  .ei .kpi{padding:19px;min-height:142px}.ei .kpi-value{font-size:1.9rem;line-height:1;font-weight:850;letter-spacing:-.04em;margin-top:18px}.ei .kpi-label{font-size:.72rem;color:var(--muted);font-weight:800;text-transform:uppercase;letter-spacing:.06em;margin-top:8px}.ei .kpi-note{font-size:.72rem;color:var(--muted)}
  .ei .card-panel{padding:22px}.ei .panel-title{font-size:1rem;font-weight:800;margin:0}.ei .panel-subtitle{font-size:.78rem;color:var(--muted);margin-top:3px}
  .ei #electoralMap{height:610px;border-radius:16px;background:#eef1f5}.ei .map-shell{position:relative}.ei .map-toolbar{position:absolute;left:12px;right:12px;top:12px;z-index:500;background:rgba(255,255,255,.96);backdrop-filter:blur(9px);border:1px solid rgba(222,227,235,.95);box-shadow:0 9px 24px rgba(27,38,55,.12);border-radius:14px;padding:10px}
  .ei .map-legend{position:absolute;left:13px;bottom:13px;z-index:500;background:rgba(255,255,255,.96);border-radius:12px;padding:10px 12px;box-shadow:0 7px 20px rgba(27,38,55,.14);font-size:.72rem;min-width:190px}.ei .legend-row{display:flex;align-items:center;gap:7px;margin-top:5px}.ei .legend-swatch{width:14px;height:14px;border-radius:4px;flex:0 0 auto}
  .ei .section-panel{min-height:610px;overflow:hidden}.ei .section-number{font-size:2.4rem;font-weight:850;letter-spacing:-.05em;color:var(--wine)}.ei .soft-pill{display:inline-flex;align-items:center;max-width:100%;padding:.34rem .64rem;border-radius:999px;background:#f4f6f9;color:#465267;font-size:.72rem;font-weight:750;white-space:normal}.ei .status-win{background:#e5f6ef;color:#0b6b54}.ei .status-loss{background:#fff0f2;color:#a42a43}
  .ei .history-table th{font-size:.66rem;text-transform:uppercase;letter-spacing:.07em;color:#7a8494;border-bottom-color:var(--line);white-space:nowrap}.ei .history-table td{vertical-align:middle;font-size:.82rem;border-bottom-color:#eef1f5}.ei .metric-bar{height:6px;background:#edf0f4;border-radius:20px;overflow:hidden}.ei .metric-bar span{display:block;height:100%;background:linear-gradient(90deg,#8b1538,#d91785);border-radius:inherit}
  .ei .year-grid{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:12px}.ei .year-card{padding:16px;border:1px solid var(--line);border-radius:15px;background:#fafbfc}.ei .year-card h3{font-size:1rem;font-weight:850;margin:0}.ei .year-total{font-size:1.35rem;font-weight:850;letter-spacing:-.03em}.ei .composition{height:10px;display:flex;overflow:hidden;border-radius:999px;background:#edf0f4}.ei .composition .morena{background:linear-gradient(90deg,#7a0019,#d91785)}.ei .composition .otros{background:#7da6cc}.ei .composition .invalidos{background:#c9ced6}.ei .composition-legend{display:flex;flex-wrap:wrap;gap:10px 16px;color:var(--muted);font-size:.7rem}.ei .composition-legend i{display:inline-block;width:9px;height:9px;border-radius:50%;margin-right:5px}
  .ei .section-summary{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:8px}.ei .section-stat{padding:11px;border-radius:12px;background:#f6f7f9}.ei .section-stat span{display:block;font-size:.66rem;text-transform:uppercase;letter-spacing:.05em;color:var(--muted);font-weight:800}.ei .section-stat strong{display:block;margin-top:3px;font-size:1rem}.ei .election-card{border:1px solid var(--line);border-radius:14px;padding:13px;margin-top:10px}.ei .election-card-head{display:flex;align-items:flex-start;justify-content:space-between;gap:8px}.ei .election-year{font-size:1rem;font-weight:850}.ei .election-row{display:flex;justify-content:space-between;gap:10px;margin-top:7px;font-size:.76rem}.ei .election-row span{color:var(--muted)}.ei .share-track{height:7px;border-radius:999px;background:#edf0f4;overflow:hidden;margin-top:8px}.ei .share-track span{display:block;height:100%;border-radius:inherit;background:linear-gradient(90deg,#7a0019,#d91785)}
  .ei .projection-panel{background:linear-gradient(135deg,#fff 0%,#fff8fb 100%);border-color:#f0d7e1}.ei .projection-grid{display:grid;grid-template-columns:repeat(5,minmax(0,1fr));gap:10px}.ei .projection-stat{padding:14px;border-radius:14px;background:#fff;border:1px solid #f0e4e9}.ei .projection-stat span{display:block;color:var(--muted);font-size:.68rem;font-weight:800;text-transform:uppercase;letter-spacing:.05em}.ei .projection-stat strong{display:block;font-size:1.35rem;letter-spacing:-.03em;margin-top:4px}.ei .projection-stat.accent{color:#fff;background:linear-gradient(135deg,#7a0019,#d91785);border:0}.ei .projection-stat.accent span{color:rgba(255,255,255,.76)}.ei .projection-note{border-left:3px solid #d91785;padding-left:11px;color:var(--muted);font-size:.76rem}.ei .range-value{min-width:54px;text-align:center;color:var(--wine);font-weight:850}.ei .operation-card{border:1px solid #efd5df;background:#fff8fb;border-radius:14px;padding:13px;margin-bottom:14px}
  .ei .source-list a{color:var(--wine);text-decoration:none}.ei .source-list a:hover{text-decoration:underline}.ei .quality-row{display:flex;justify-content:space-between;gap:12px;padding:10px 0;border-bottom:1px solid var(--line)}.ei .quality-row:last-child{border:0}.ei .quality-row span{color:var(--muted);font-size:.78rem}.ei .quality-row strong{font-size:.85rem}
  .ei .stable-table th{font-size:.68rem;text-transform:uppercase;letter-spacing:.06em;color:#7a8494;white-space:nowrap}.ei .stable-table td{font-size:.82rem;vertical-align:middle}.ei .section-link{border:0;background:none;color:var(--wine);font-weight:800;padding:0;text-decoration:underline;text-decoration-color:#e9bdca;text-underline-offset:3px}
  .leaflet-tooltip.ei-tooltip{border:0;border-radius:10px;box-shadow:0 8px 20px rgba(0,0,0,.16);padding:8px 10px;font:12px/1.35 system-ui,sans-serif}
  @media(max-width:991.98px){.ei #electoralMap{height:520px}.ei .section-panel{min-height:0}.ei .map-toolbar{position:relative;left:auto;right:auto;top:auto;margin-bottom:10px}.ei .map-shell{display:flex;flex-direction:column}.ei .map-toolbar{order:-1}.ei .map-legend{bottom:12px}.ei .hero{padding:23px 20px}.ei .year-grid{grid-template-columns:1fr}.ei .projection-grid{grid-template-columns:repeat(2,minmax(0,1fr))}}
</style>
@endpush

@section('content')
<div class="container-xxl ei pb-5">
  <section class="hero mb-4">
    <div class="position-relative" style="z-index:1">
      <div class="eyebrow">Presidencia municipal de Morelia</div>
      <h1>Resultados históricos por sección</h1>
      <p class="mb-0 text-white-50">Comparación homologable de las elecciones de Ayuntamiento 2018, 2021 y 2024 con cómputos oficiales del IEM.</p>
    </div>
  </section>

  <div class="row row-cols-1 row-cols-sm-2 row-cols-xl-5 g-3 mb-3">
    <div class="col"><div class="kpi"><div class="kpi-note">Elección 2024</div><div class="kpi-value">{{ number_format($current['lista_nominal']) }}</div><div class="kpi-label">Lista nominal</div></div></div>
    <div class="col"><div class="kpi"><div class="kpi-note">Elección 2024</div><div class="kpi-value">{{ number_format($current['participacion'] * 100, 1) }}%</div><div class="kpi-label">Participación</div></div></div>
    <div class="col"><div class="kpi"><div class="kpi-note">Voto válido 2024</div><div class="kpi-value">{{ number_format($current['porcentaje_morena'] * 100, 1) }}%</div><div class="kpi-label">Morena y coalición</div></div></div>
    <div class="col"><div class="kpi"><div class="kpi-note">De {{ number_format($current['secciones']) }} con resultados</div><div class="kpi-value">{{ number_format($current['secciones_ganadas_morena']) }}</div><div class="kpi-label">Secciones ganadas 2024</div></div></div>
    <div class="col"><div class="kpi"><div class="kpi-note">Con clave comparable en tres procesos</div><div class="kpi-value">{{ number_format($consistentMorena->count()) }}</div><div class="kpi-label">Ganadas en 2018 2021 y 2024</div></div></div>
  </div>

  <div class="card-panel mb-3">
    <div class="d-flex flex-wrap justify-content-between align-items-start gap-2 mb-3">
      <div><h2 class="panel-title">Comparativo municipal</h2><div class="panel-subtitle">Composición del voto y participación en elecciones equivalentes de Ayuntamiento.</div></div>
      <div class="composition-legend">
        <span><i style="background:#a20b51"></i>Morena y coalición</span>
        <span><i style="background:#7da6cc"></i>Otros votos válidos</span>
        <span><i style="background:#c9ced6"></i>Votos no válidos</span>
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
          <div class="quality-row"><span>Votos no válidos</span><strong>{{ number_format($invalid) }}</strong></div>
          <div class="quality-row"><span>Abstención estimada</span><strong>{{ number_format($abstention) }}</strong></div>
          <div class="quality-row"><span>Secciones ganadas</span><strong>{{ number_format($result['secciones_ganadas_morena']) }}</strong></div>
        </article>
      @endforeach
    </div>
  </div>

  <div class="card-panel projection-panel mb-3">
    <div class="row g-3 align-items-center">
      <div class="col-xl-8">
        <div class="d-flex flex-wrap justify-content-between align-items-start gap-2 mb-3">
          <div><h2 class="panel-title">Proyección operativa con la base actual</h2><div class="panel-subtitle">Personas registradas en <code>afiliados</code>, agregadas por sección y sin exponer datos individuales.</div></div>
          @if($operation['alcance_restringido'])<span class="soft-pill"><i class="fa-solid fa-shield-halved me-1"></i>Solo distritos autorizados</span>@endif
        </div>
        <div class="projection-grid">
          <div class="projection-stat"><span>Registros totales</span><strong>{{ number_format($operation['registrados']) }}</strong></div>
          <div class="projection-stat"><span>Sí afiliados</span><strong class="text-success">{{ number_format($operation['afiliados_si']) }}</strong></div>
          <div class="projection-stat"><span>No afiliados</span><strong class="text-danger">{{ number_format($operation['afiliados_no']) }}</strong></div>
          <div class="projection-stat"><span>Secciones cubiertas</span><strong>{{ number_format($operation['secciones_cubiertas']) }}</strong><small class="text-muted"> / {{ number_format($quality['current_sections']) }}</small></div>
          <div class="projection-stat accent"><span>Base movilizada estimada</span><strong id="projectedCity">{{ number_format(round($operation['afiliados_si'] * $operation['tasa_movilizacion_inicial'])) }}</strong></div>
        </div>
      </div>
      <div class="col-xl-4">
        <label for="mobilizationRate" class="small fw-bold d-flex justify-content-between align-items-center"><span>Supuesto de movilización de “sí afiliados”</span><output id="mobilizationRateLabel" class="range-value">75%</output></label>
        <input id="mobilizationRate" type="range" class="form-range mt-2" min="50" max="100" step="5" value="75">
        <div class="d-flex justify-content-between small text-muted"><span>50%</span><span>Escenario ajustable</span><span>100%</span></div>
        <p class="projection-note mt-3 mb-0">Es un escenario de movilización, no un pronóstico del resultado. No se suma automáticamente al voto de Morena de 2024 porque ambas bases pueden contener a las mismas personas.</p>
      </div>
    </div>
  </div>

  <div class="row g-3 mb-3">
    <div class="col-xl-8">
      <div class="card-panel h-100">
        <div class="d-flex flex-wrap justify-content-between align-items-start gap-2 mb-3">
          <div><h2 class="panel-title">Mapa seccional</h2><div class="panel-subtitle">Selecciona una métrica y haz clic en una sección para revisar su serie histórica.</div></div>
          <span class="soft-pill"><i class="fa-solid fa-database me-1"></i>Datos agregados sin perfiles individuales</span>
        </div>
        <div class="map-shell">
          <div class="map-toolbar">
            <div class="row g-2 align-items-center">
              <div class="col-md-8"><label for="metricSelect" class="visually-hidden">Métrica</label><select id="metricSelect" class="form-select form-select-sm">
                <option value="victories">Victorias de Morena y coalición en 2018 2021 y 2024</option>
                <option value="morena2024">Porcentaje de voto de Morena y coalición en 2024</option>
                <option value="turnout2024">Participación ciudadana en 2024</option>
                <option value="invalid2024">Porcentaje de votos no válidos en 2024</option>
                <option value="abstention2024">Abstención estimada en 2024</option>
                <option value="margin2024">Margen de Morena frente al principal competidor en 2024</option>
                <option value="trend">Cambio del porcentaje de Morena entre 2021 y 2024</option>
                <option value="nominal2024">Lista nominal en 2024</option>
                <option value="affiliatesYes">Sí afiliados registrados por sección</option>
                <option value="operationalCoverage">Cobertura de sí afiliados sobre lista nominal 2024</option>
                <option value="mobilizedBase">Base movilizada según el escenario</option>
              </select></div>
              <div class="col-md-4"><input id="sectionSearch" class="form-control form-control-sm" inputmode="numeric" placeholder="Ir a sección"></div>
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
          <div class="small mt-1">Aquí aparecerán sus resultados y cambios entre elecciones.</div>
        </div>
        <div id="sectionDetail" class="d-none"></div>
      </div>
    </div>
  </div>

  <div class="row g-3">
    <div class="col-xl-8">
      <div class="card-panel h-100">
        <div class="d-flex justify-content-between align-items-start mb-3"><div><h2 class="panel-title">Secciones con victoria en los tres procesos</h2><div class="panel-subtitle">Solo se incluyen claves observadas sin interrupción en 2018, 2021 y 2024.</div></div><span class="soft-pill">{{ $consistentMorena->count() }} secciones</span></div>
        <div class="table-responsive"><table class="table stable-table align-middle mb-0"><thead><tr><th>Sección</th><th>DL</th><th>DF</th><th class="text-end">2018</th><th class="text-end">2021</th><th class="text-end">2024</th><th class="text-end">Participación 2024</th></tr></thead><tbody>
          @forelse($consistentMorena as $row)
          <tr><td><button class="section-link" data-section="{{ $row['seccion'] }}">{{ $row['seccion'] }}</button></td><td>{{ $row['distrito_local'] }}</td><td>{{ $row['distrito_federal'] }}</td><td class="text-end">{{ number_format($row['historial']['2018']['porcentaje_morena'] * 100, 1) }}%</td><td class="text-end">{{ number_format($row['historial']['2021']['porcentaje_morena'] * 100, 1) }}%</td><td class="text-end fw-bold">{{ number_format($row['historial']['2024']['porcentaje_morena'] * 100, 1) }}%</td><td class="text-end">{{ number_format($row['historial']['2024']['participacion'] * 100, 1) }}%</td></tr>
          @empty<tr><td colspan="7" class="text-center text-muted py-4">No hay secciones con serie completa.</td></tr>@endforelse
        </tbody></table></div>
      </div>
    </div>
    <div class="col-xl-4">
      <div class="card-panel mb-3">
        <h2 class="panel-title">Cobertura y comparabilidad</h2><div class="panel-subtitle mb-2">La cartografía vigente puede contener secciones creadas después de una elección.</div>
        <div class="quality-row"><span>Polígonos seccionales vigentes</span><strong>{{ number_format($quality['current_sections']) }}</strong></div>
        <div class="quality-row"><span>Con resultado municipal 2024</span><strong>{{ number_format($quality['with_2024_results']) }}</strong></div>
        <div class="quality-row"><span>Comparables en tres elecciones</span><strong>{{ number_format($quality['three_cycle_sections']) }}</strong></div>
        <div class="quality-row"><span>Nuevas o reconfiguradas</span><strong>{{ number_format($quality['reconfigured_or_new']) }}</strong></div>
      </div>
      <div class="card-panel">
        <h2 class="panel-title">Fuentes oficiales</h2><div class="panel-subtitle mb-3">Los resultados proceden de cómputos por casilla y se agregan por sección.</div>
        <div class="source-list small">
          @foreach($metadata['fuentes'] as $source)
          <div class="mb-2"><strong>{{ $source['anio'] }} · {{ $source['institucion'] }}</strong><br><a href="{{ $source['url'] }}" target="_blank" rel="noopener">{{ $source['descripcion'] }}</a></div>
          @endforeach
        </div>
        <hr>
        <p class="small text-muted mb-0">{{ $metadata['advertencia_cartografica'] }}</p>
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
  const winnerLabels = {morena:'Morena y coalición',pan_prd_mc:'PAN PRD MC',pan_prd:'PAN PRD',pan_pri_prd:'PAN PRI PRD',pri:'PRI',pvem:'PVEM',mc:'Movimiento Ciudadano',pes:'PES',pesm:'PES Michoacán',rsp:'RSP',fxm:'Fuerza por México',nueva_alianza:'Nueva Alianza',independiente:'Candidatura independiente',independientes:'Candidaturas independientes',mm:'Más Michoacán',mich1:'Michoacán Primero',tmx:'Tiempo por México'};
  const palettes = {
    victories:{title:'Victorias en tres elecciones',items:[['0','#edf0f4'],['1','#f4bfd2'],['2','#d95c8d'],['3','#790019']]},
    morena2024:{title:'Porcentaje de voto 2024',items:[['Menos de 25%','#f2f2f2'],['25% a 35%','#f5b8cf'],['35% a 45%','#db5f91'],['45% o más','#790019']]},
    turnout2024:{title:'Participación 2024',items:[['Menos de 45%','#f0f1f3'],['45% a 55%','#b8d8ea'],['55% a 65%','#5695bd'],['65% o más','#174a73']]},
    invalid2024:{title:'Votos no válidos 2024',items:[['Menos de 2%','#f0f1f3'],['2% a 4%','#d8c5da'],['4% a 6%','#a96b9c'],['6% o más','#6a285f']]},
    abstention2024:{title:'Abstención estimada 2024',items:[['Menos de 35%','#e5f3ef'],['35% a 45%','#a9d3c7'],['45% a 55%','#e3b7a5'],['55% o más','#a84d43']]},
    margin2024:{title:'Margen de Morena 2024',items:[['Pierde por 20 puntos o más','#982e45'],['Pierde por menos de 20 puntos','#efb2bf'],['Gana por menos de 20 puntos','#9fc9bc'],['Gana por 20 puntos o más','#0e6f57']]},
    trend:{title:'Cambio 2021 a 2024',items:[['Baja más de 5 puntos','#a83a50'],['Entre -5 y 0 puntos','#f0b4c0'],['Entre 0 y 5 puntos','#acd2c6'],['Sube más de 5 puntos','#14745d']]},
    nominal2024:{title:'Lista nominal 2024',items:[['Menos de 1,000','#f0f1f3'],['1,000 a 2,499','#c7d5ea'],['2,500 a 4,999','#789bc8'],['5,000 o más','#284f87']]},
    affiliatesYes:{title:'Sí afiliados registrados',items:[['Sin registros','#f0f1f3'],['1 a 9','#f6c2d6'],['10 a 29','#dd6997'],['30 o más','#790019']]},
    operationalCoverage:{title:'Cobertura sobre lista nominal',items:[['Sin cobertura','#f0f1f3'],['Menos de 2%','#c8dbed'],['2% a 5%','#689ac8'],['5% o más','#214f82']]},
    mobilizedBase:{title:'Base movilizada estimada',items:[['Sin base','#f0f1f3'],['1 a 9','#c9e5dc'],['10 a 24','#66b29b'],['25 o más','#126b55']]}
  };
  let rows = new Map(), layers = new Map(), geoLayer, selectedSection = null;
  const metricSelect = document.getElementById('metricSelect');
  const legend = document.getElementById('mapLegend');
  const mobilizationRate = document.getElementById('mobilizationRate');
  const rate = () => Number(mobilizationRate.value) / 100;
  const mobilized = row => Math.round((row?.operacion?.afiliados_si || 0) * rate());
  const colorFor = (row, metric) => {
    if (!row) return '#d9dde3';
    const y24=row.historial['2024'];
    if(metric==='victories') return ['#edf0f4','#f4bfd2','#d95c8d','#790019'][Math.max(0,Math.min(3,row.victorias_morena))];
    if(!y24 && !['trend','affiliatesYes','mobilizedBase'].includes(metric)) return '#d9dde3';
    if(metric==='morena2024'){const v=y24.porcentaje_morena;return v>=.45?'#790019':v>=.35?'#db5f91':v>=.25?'#f5b8cf':'#f2f2f2'}
    if(metric==='turnout2024'){const v=y24.participacion;return v>=.65?'#174a73':v>=.55?'#5695bd':v>=.45?'#b8d8ea':'#f0f1f3'}
    if(metric==='invalid2024'){const v=y24.votos_totales>0?(y24.votos_totales-y24.votos_validos)/y24.votos_totales:0;return v>=.06?'#6a285f':v>=.04?'#a96b9c':v>=.02?'#d8c5da':'#f0f1f3'}
    if(metric==='abstention2024'){const v=1-y24.participacion;return v>=.55?'#a84d43':v>=.45?'#e3b7a5':v>=.35?'#a9d3c7':'#e5f3ef'}
    if(metric==='margin2024'){const v=y24.margen_morena_pct;return v>=.20?'#0e6f57':v>=0?'#9fc9bc':v>=-.20?'#efb2bf':'#982e45'}
    if(metric==='trend'){const v=row.tendencia_morena_2021_2024;if(v===null)return '#d9dde3';return v>.05?'#14745d':v>=0?'#acd2c6':v>=-.05?'#f0b4c0':'#a83a50'}
    if(metric==='nominal2024'){const v=y24.lista_nominal;return v>=5000?'#284f87':v>=2500?'#789bc8':v>=1000?'#c7d5ea':'#f0f1f3'}
    if(metric==='affiliatesYes'){const v=row.operacion?.afiliados_si||0;return v>=30?'#790019':v>=10?'#dd6997':v>=1?'#f6c2d6':'#f0f1f3'}
    if(metric==='operationalCoverage'){const v=y24?.lista_nominal>0?(row.operacion?.afiliados_si||0)/y24.lista_nominal:0;return v>=.05?'#214f82':v>=.02?'#689ac8':v>0?'#c8dbed':'#f0f1f3'}
    if(metric==='mobilizedBase'){const v=mobilized(row);return v>=25?'#126b55':v>=10?'#66b29b':v>=1?'#c9e5dc':'#f0f1f3'}
    return '#d9dde3';
  };
  const style = feature => {const row=rows.get(Number(feature.properties.SECCION));return {color:'#fff',weight:.8,fillColor:colorFor(row,metricSelect.value),fillOpacity:.86};};
  const renderLegend = () => {const p=palettes[metricSelect.value];legend.innerHTML=`<strong>${p.title}</strong>${p.items.map(([label,color])=>`<div class="legend-row"><span class="legend-swatch" style="background:${color}"></span><span>${label}</span></div>`).join('')}<div class="legend-row"><span class="legend-swatch" style="background:#d9dde3"></span><span>Sin dato comparable</span></div>`;};
  const showSection = (section, focus=true) => {
    const row=rows.get(Number(section)); if(!row)return;
    selectedSection=Number(section);
    const layer=layers.get(Number(section)); if(focus&&layer){map.fitBounds(layer.getBounds(),{padding:[45,45],maxZoom:15});layer.openTooltip();}
    document.getElementById('emptySection').classList.add('d-none');
    const detail=document.getElementById('sectionDetail'); detail.classList.remove('d-none');
    const h24=row.historial['2024'];
    const other24=h24?Math.max(0,h24.votos_validos-h24.votos_morena):0;
    const invalid24=h24?Math.max(0,h24.votos_totales-h24.votos_validos):0;
    const abstention24=h24?Math.max(0,h24.lista_nominal-h24.votos_totales):0;
    const op=row.operacion||{registrados:0,afiliados_si:0,afiliados_no:0};
    const projected=mobilized(row);
    const listCoverage=h24&&h24.lista_nominal>0?op.afiliados_si/h24.lista_nominal:null;
    const historicalReference=h24&&h24.votos_morena>0?projected/h24.votos_morena:null;
    const elections=['2018','2021','2024'].map(year=>{
      const h=row.historial[year];
      if(!h)return `<article class="election-card"><div class="election-card-head"><span class="election-year">${year}</span><span class="soft-pill">Sin clave comparable</span></div></article>`;
      const other=Math.max(0,h.votos_validos-h.votos_morena);
      const invalid=Math.max(0,h.votos_totales-h.votos_validos);
      return `<article class="election-card">
        <div class="election-card-head"><span class="election-year">${year}</span><span class="soft-pill ${h.morena_gano?'status-win':'status-loss'}">${winnerLabels[h.ganador]||h.ganador}</span></div>
        <div class="election-row"><span>Morena y coalición</span><strong>${number.format(h.votos_morena)} · ${percent(h.porcentaje_morena)}</strong></div>
        <div class="share-track"><span style="width:${Math.min(100,Math.max(0,h.porcentaje_morena*100))}%"></span></div>
        <div class="election-row"><span>Otros votos válidos</span><strong>${number.format(other)}</strong></div>
        <div class="election-row"><span>Votos no válidos</span><strong>${number.format(invalid)}</strong></div>
        <div class="election-row"><span>Participación</span><strong>${percent(h.participacion)}</strong></div>
        <div class="election-row"><span>Margen de Morena</span><strong class="${h.margen_morena>=0?'text-success':'text-danger'}">${h.margen_morena>=0?'+':''}${number.format(h.margen_morena)}</strong></div>
      </article>`;
    }).join('');
    detail.innerHTML=`
      <div class="text-muted small text-uppercase fw-bold">Sección</div>
      <div class="section-number">${row.seccion}</div>
      <div class="d-flex flex-wrap gap-2 mb-3"><span class="soft-pill">DL ${row.distrito_local??'n.d.'}</span><span class="soft-pill">DF ${row.distrito_federal??'n.d.'}</span><span class="soft-pill">${row.victorias_morena} de ${row.elecciones_observadas} victorias</span></div>
      ${h24?`<div class="section-summary mb-3">
        <div class="section-stat"><span>Morena 2024</span><strong>${number.format(h24.votos_morena)}</strong></div>
        <div class="section-stat"><span>Otros válidos</span><strong>${number.format(other24)}</strong></div>
        <div class="section-stat"><span>No válidos</span><strong>${number.format(invalid24)}</strong></div>
        <div class="section-stat"><span>Abstención est.</span><strong>${number.format(abstention24)}</strong></div>
      </div>`:''}
      <div class="operation-card">
        <div class="small fw-bold text-uppercase text-muted mb-2">Base operativa actual</div>
        <div class="section-summary">
          <div class="section-stat"><span>Registros</span><strong>${number.format(op.registrados)}</strong></div>
          <div class="section-stat"><span>Sí afiliados</span><strong class="text-success">${number.format(op.afiliados_si)}</strong></div>
          <div class="section-stat"><span>No afiliados</span><strong class="text-danger">${number.format(op.afiliados_no)}</strong></div>
          <div class="section-stat"><span>Movilizados al ${mobilizationRate.value}%</span><strong>${number.format(projected)}</strong></div>
        </div>
        <div class="election-row"><span>Cobertura sobre lista nominal 2024</span><strong>${percent(listCoverage)}</strong></div>
        <div class="election-row"><span>Base movilizada / voto Morena 2024</span><strong>${percent(historicalReference)}</strong></div>
        <p class="small text-muted mt-2 mb-0">La segunda razón es una referencia de escala; no afirma que sean votos adicionales ni identifica cómo votó una persona.</p>
      </div>
      <div class="small fw-bold text-uppercase text-muted">Histórico municipal</div>
      ${elections}
      <p class="small text-muted mt-3 mb-0">Los resultados son agregados. No identifican preferencias individuales ni explican por sí solos la causa de un cambio.</p>`;
  };
  Promise.all([
    fetch(@json(route('electoral.data'))).then(response=>response.json()),
    fetch(@json(asset('maps/electoral/morelia-secciones.geojson'))).then(response=>response.json())
  ]).then(([dataset,geo])=>{
    rows=new Map(dataset.secciones.map(row=>[Number(row.seccion),row]));
    geoLayer=L.geoJSON(geo,{style,onEachFeature:(feature,layer)=>{const section=Number(feature.properties.SECCION);layers.set(section,layer);const row=rows.get(section);layer.bindTooltip(`<strong>Sección ${section}</strong><br>${row?`${row.victorias_morena} de ${row.elecciones_observadas} victorias observadas<br>${number.format(row.operacion?.afiliados_si||0)} sí afiliados`:'Sin resultados asociados'}`,{sticky:true,className:'ei-tooltip'});layer.on('click',()=>showSection(section));layer.on('mouseover',function(){this.setStyle({weight:2,color:'#252b34'});this.bringToFront()});layer.on('mouseout',function(){geoLayer.resetStyle(this)})}}).addTo(map);
    map.fitBounds(geoLayer.getBounds(),{padding:[18,18]}); renderLegend();
  }).catch(error=>{console.error(error);document.getElementById('electoralMap').innerHTML='<div class="alert alert-danger m-3">No fue posible cargar el mapa electoral.</div>';});
  metricSelect.addEventListener('change',()=>{if(geoLayer)geoLayer.setStyle(style);renderLegend()});
  mobilizationRate.addEventListener('input',()=>{
    document.getElementById('mobilizationRateLabel').textContent=`${mobilizationRate.value}%`;
    document.getElementById('projectedCity').textContent=number.format(Math.round({{ (int) $operation['afiliados_si'] }}*rate()));
    if(geoLayer&&metricSelect.value==='mobilizedBase')geoLayer.setStyle(style);
    if(selectedSection!==null)showSection(selectedSection,false);
  });
  document.getElementById('sectionSearch').addEventListener('change',event=>showSection(event.target.value));
  document.querySelectorAll('[data-section]').forEach(button=>button.addEventListener('click',()=>{showSection(button.dataset.section);document.getElementById('electoralMap').scrollIntoView({behavior:'smooth',block:'center'})}));
})();
</script>
@endpush
