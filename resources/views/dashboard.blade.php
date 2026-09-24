@extends('layouts.app')

@section('title', $operational ? 'Centro de control' : 'Inicio')

@if($operational)
@push('styles')
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
<style>
  .ops-dashboard{--ink:#18212f;--muted:#697386;--line:#e9edf3;--wine:#8b1538;--pink:#d91785;--green:#16886b;color:var(--ink)}
  .ops-dashboard .ops-hero{position:relative;overflow:hidden;background:linear-gradient(125deg,#20111a 0%,#74122f 55%,#d91785 130%);border-radius:24px;padding:28px 30px;color:#fff;box-shadow:0 22px 55px rgba(73,12,37,.18)}
  .ops-dashboard .ops-hero:after{content:"";position:absolute;width:320px;height:320px;border:60px solid rgba(255,255,255,.06);border-radius:50%;right:-80px;top:-150px}
  .ops-dashboard .eyebrow{font-size:.72rem;letter-spacing:.16em;text-transform:uppercase;font-weight:800;opacity:.72}
  .ops-dashboard .hero-title{font-size:clamp(1.7rem,3vw,2.7rem);font-weight:800;letter-spacing:-.035em;margin:.35rem 0}
  .ops-dashboard .live-dot{display:inline-block;width:8px;height:8px;background:#61e7bd;border-radius:50%;box-shadow:0 0 0 6px rgba(97,231,189,.15);margin-right:9px}
  .ops-dashboard .kpi-card,.ops-dashboard .panel{background:#fff;border:1px solid rgba(227,232,240,.9);border-radius:19px;box-shadow:0 10px 35px rgba(21,31,48,.055)}
  .ops-dashboard .kpi-card{padding:20px;min-height:145px;position:relative;overflow:hidden;transition:transform .2s ease,box-shadow .2s ease}
  .ops-dashboard .kpi-card:hover{transform:translateY(-3px);box-shadow:0 18px 42px rgba(21,31,48,.1)}
  .ops-dashboard .kpi-icon{width:42px;height:42px;display:grid;place-items:center;border-radius:13px;background:#f7edf2;color:var(--wine);font-size:1.05rem}
  .ops-dashboard .kpi-value{font-size:1.9rem;line-height:1;font-weight:850;letter-spacing:-.04em;margin-top:20px}.ops-dashboard .kpi-label{font-size:.78rem;color:var(--muted);font-weight:700;margin-top:7px;text-transform:uppercase;letter-spacing:.05em}.ops-dashboard .mini-delta{font-size:.7rem;color:var(--green);font-weight:700}
  .ops-dashboard .panel{padding:22px;height:100%}.ops-dashboard .panel-title{font-size:1rem;font-weight:800;margin:0}.ops-dashboard .panel-subtitle{font-size:.78rem;color:var(--muted);margin-top:3px}
  .ops-dashboard .chart-wrap{height:260px}.ops-dashboard #territoryMap{height:340px;border-radius:15px;background:#f1f4f7}.ops-dashboard .soft-pill{padding:.35rem .65rem;border-radius:999px;background:#f5f7fa;color:#465267;font-size:.72rem;font-weight:750}
  .ops-dashboard .status-dot{display:inline-block;width:9px;height:9px;border-radius:50%;margin-right:7px}.status-green{background:#19a67e}.status-yellow{background:#e8a83e}.status-red{background:#dc4c64}
  .ops-dashboard .quality-bar{height:6px;background:#edf0f4;border-radius:20px;overflow:hidden;margin-top:6px}.ops-dashboard .quality-bar span{display:block;height:100%;border-radius:inherit;background:linear-gradient(90deg,#8b1538,#d91785)}
  .ops-dashboard .table{--bs-table-bg:transparent}.ops-dashboard .table th{font-size:.69rem;text-transform:uppercase;letter-spacing:.07em;color:#7a8494;border-bottom-color:var(--line);white-space:nowrap}.ops-dashboard .table td{vertical-align:middle;border-bottom-color:#f0f2f6;font-size:.84rem}
  .ops-dashboard .person-avatar{width:36px;height:36px;display:grid;place-items:center;border-radius:11px;background:linear-gradient(145deg,#f8dfe9,#f3eef4);color:var(--wine);font-weight:800}.ops-dashboard .alert-row{display:flex;gap:13px;padding:13px 0;border-bottom:1px solid var(--line)}.ops-dashboard .alert-row:last-child{border:0}.ops-dashboard .alert-icon{width:36px;height:36px;flex:0 0 auto;display:grid;place-items:center;border-radius:11px}.ops-dashboard .tone-warning{background:#fff5da;color:#946500}.ops-dashboard .tone-danger{background:#ffebef;color:#b32240}.ops-dashboard .tone-info{background:#e9f3ff;color:#246ca6}
  .ops-dashboard .audit-line{position:relative;padding:0 0 19px 24px;border-left:2px solid #edf0f4}.ops-dashboard .audit-line:before{content:"";position:absolute;width:10px;height:10px;left:-6px;top:4px;border:2px solid #fff;border-radius:50%;background:var(--pink);box-shadow:0 0 0 2px #f1c8dc}.ops-dashboard .audit-line:last-child{padding-bottom:0}.ops-dashboard .metric-tile{border:1px solid var(--line);border-radius:14px;padding:13px;background:#fbfcfd}.ops-dashboard .metric-tile strong{display:block;font-size:1.25rem}.ops-dashboard .metric-tile span{font-size:.72rem;color:var(--muted)}
  @media(max-width:767px){.ops-dashboard .ops-hero{padding:23px 20px}.ops-dashboard .panel{padding:17px}.ops-dashboard .table-responsive{margin:0 -17px;padding:0 17px}}
</style>
@endpush
@endif

@section('content')
@if($operational)
@php
  $todayLabel = \Illuminate\Support\Str::ucfirst($operational['generatedAt']->locale('es')->translatedFormat('l d \d\e F \d\e Y'));
  $statusLabel = ['green'=>'Óptimo','yellow'=>'Atención','red'=>'Crítico'];
@endphp
<div class="container-xxl ops-dashboard pb-5">
  <section class="ops-hero mb-4"><div class="position-relative" style="z-index:1"><div class="d-flex flex-wrap justify-content-between align-items-start gap-3"><div><div class="eyebrow"><span class="live-dot"></span>Centro de control operativo</div><h1 class="hero-title">El pulso de la operación, en una mirada.</h1><p class="mb-0 text-white-50">{{ $todayLabel }} · Información actualizada a las {{ $operational['generatedAt']->format('H:i') }}</p></div><div class="text-md-end"><div class="soft-pill bg-white text-dark mb-2"><i class="fa-solid fa-shield-halved me-1 text-success"></i> Acceso administrativo</div>@if($operational['hasDemoData'])<div class="small text-white-50"><i class="fa-solid fa-flask me-1"></i> Incluye datos de demostración</div>@endif</div></div></div></section>

  <div class="row row-cols-1 row-cols-sm-2 row-cols-xl-5 g-3 mb-4">
    @foreach([
      ['Reportes del equipo',$operational['summary']['team_reports'],'clipboard-check','Trabajo subido por usuarios'],['Actividades oficiales',$operational['summary']['official_activities'],'calendar-check','Calendario institucional'],['Participaciones',$operational['summary']['participations'],'user-check','Integrantes que asistieron'],['Asistencia reportada',$operational['summary']['reported_attendance'],'people-group','Suma declarada'],['Reportes con evidencia',$operational['summary']['evidence_rate'].'%','camera','Foto o documento'],
    ] as [$label,$value,$icon,$hint])
    <div class="col"><div class="kpi-card"><div class="d-flex justify-content-between align-items-start"><div class="kpi-icon"><i class="fa-solid fa-{{ $icon }}"></i></div><span class="mini-delta">{{ $hint }}</span></div><div class="kpi-value">{{ is_numeric($value) ? number_format((float)$value, str_contains((string)$value,'.') ? 1 : 0) : $value }}</div><div class="kpi-label">{{ $label }}</div></div></div>
    @endforeach
  </div>

  <div class="row g-3 mb-3">
    <div class="col-xl-8"><div class="panel"><div class="d-flex justify-content-between align-items-start mb-3"><div><h2 class="panel-title">Registros de los últimos 14 días</h2><div class="panel-subtitle">Comportamiento diario de nuevas capturas</div></div><span class="soft-pill"><i class="fa-solid fa-wave-square me-1"></i>Tendencia</span></div><div class="chart-wrap"><canvas id="dailyChart"></canvas></div></div></div>
    <div class="col-xl-4"><div class="panel"><h2 class="panel-title">Atención inmediata</h2><div class="panel-subtitle mb-2">Hallazgos automáticos para revisión</div>
      @forelse($operational['alerts'] as $alert)<div class="alert-row"><div class="alert-icon tone-{{ $alert['tone'] }}"><i class="fa-solid fa-{{ $alert['icon'] }}"></i></div><div><div class="fw-bold small">{{ $alert['title'] }}</div><div class="small text-muted">{{ $alert['text'] }}</div></div></div>@empty<div class="text-center py-5 text-success"><i class="fa-solid fa-circle-check fa-2x mb-2"></i><div class="fw-bold">Sin alertas críticas</div></div>@endforelse
      <div class="row g-2 mt-2"><div class="col-6"><div class="metric-tile"><strong>{{ $operational['summary']['traceability'] }}%</strong><span>Trazabilidad</span></div></div><div class="col-6"><div class="metric-tile"><strong>{{ number_format($operational['summary']['duplicates']) }}</strong><span>Duplicados</span></div></div></div>
    </div></div>
  </div>

  <div class="row g-3 mb-3">
    <div class="col-xl-7"><div class="panel"><div class="d-flex justify-content-between align-items-start mb-3"><div><h2 class="panel-title">Mapa de actividad territorial</h2><div class="panel-subtitle">El color indica qué tan reciente es la información</div></div><span class="soft-pill">🟢 reciente · 🟡 15–30 días · 🔴 +30 días</span></div><div id="territoryMap"></div></div></div>
    <div class="col-xl-5"><div class="panel"><h2 class="panel-title">Cobertura por colonia</h2><div class="panel-subtitle mb-3">Actividad, verificación y calidad territorial</div><div class="table-responsive" style="max-height:356px"><table class="table table-sm align-middle mb-0"><thead class="sticky-top bg-white"><tr><th>Colonia</th><th class="text-end">Personas</th><th class="text-end">Act.</th><th>Estado</th></tr></thead><tbody>
      @forelse($operational['territories'] as $territory)<tr><td><div class="fw-semibold">{{ $territory->colony }}</div><small class="text-muted">{{ $territory->verified }} verificadas · {{ $territory->incomplete }} incompletas</small></td><td class="text-end fw-bold">{{ number_format($territory->people) }}</td><td class="text-end">{{ $territory->activities }}</td><td><span class="status-dot status-{{ $territory->status }}"></span><span class="small">{{ $statusLabel[$territory->status] }}</span></td></tr>@empty<tr><td colspan="4" class="text-center text-muted py-4">Sin información territorial.</td></tr>@endforelse
    </tbody></table></div></div></div>
  </div>

  <div class="panel mb-3"><div class="d-flex flex-wrap justify-content-between align-items-start gap-2 mb-3"><div><h2 class="panel-title">Desempeño por responsable</h2><div class="panel-subtitle">Cumplimiento y calidad pesan más que el volumen aislado</div></div><span class="soft-pill"><i class="fa-solid fa-scale-balanced me-1"></i>Indicadores operativos, no políticos</span></div><div class="table-responsive"><table class="table align-middle mb-0"><thead><tr><th>Responsable</th><th>Actividades</th><th>Cumplimiento</th><th>Registros</th><th>Válidos</th><th>Duplicados</th><th>Calidad</th><th>Colonias</th><th>Semáforo</th></tr></thead><tbody>
    @forelse($operational['responsibles'] as $person)<tr><td><div class="d-flex align-items-center gap-2"><div class="person-avatar">{{ \Illuminate\Support\Str::upper(\Illuminate\Support\Str::substr($person->name,0,1)) }}</div><div><div class="fw-semibold">{{ $person->name }}</div><small class="text-muted">{{ $person->last_activity ? 'Última: '.\Carbon\Carbon::parse($person->last_activity)->locale('es')->diffForHumans() : 'Sin actividad' }}</small></div></div></td><td>{{ $person->done }} / {{ $person->assigned }}</td><td><strong>{{ $person->compliance }}%</strong></td><td>{{ number_format($person->captured) }}</td><td>{{ number_format($person->valid) }}</td><td>{{ number_format($person->duplicates) }}</td><td style="min-width:110px"><strong>{{ $person->quality }}%</strong><div class="quality-bar"><span style="width:{{ min(100,$person->quality) }}%"></span></div></td><td>{{ $person->colonies }}</td><td><span class="status-dot status-{{ $person->status }}"></span>{{ $statusLabel[$person->status] }}</td></tr>@empty<tr><td colspan="9" class="text-center text-muted py-4">Aún no hay responsables con actividad o capturas.</td></tr>@endforelse
  </tbody></table></div></div>

  <div class="row g-3 mb-3">
    <div class="col-xl-8"><div class="panel"><div class="d-flex justify-content-between mb-3"><div><h2 class="panel-title">Control de actividades</h2><div class="panel-subtitle">Asistencia, registros válidos, evidencia y consistencia</div></div><a href="{{ route('actividades.index') }}" class="btn btn-sm btn-outline-dark">Ver todas</a></div>
      <div class="row g-2 mb-3">@foreach([['Realizadas',$operational['activityKpis']['done']],['Canceladas',$operational['activityKpis']['cancelled']],['Cumplimiento',$operational['activityKpis']['calendar_compliance'].'%'],['Asistencia media',$operational['activityKpis']['average_attendance']],['Sin evidencia',$operational['activityKpis']['without_evidence']],['Carga tardía',$operational['activityKpis']['late_captures']]] as [$label,$value])<div class="col-6 col-md-4 col-xl-2"><div class="metric-tile"><strong>{{ $value }}</strong><span>{{ $label }}</span></div></div>@endforeach</div>
      <div class="table-responsive"><table class="table table-sm mb-0"><thead><tr><th>Actividad</th><th>Responsable</th><th class="text-end">Asist.</th><th class="text-end">Reg.</th><th class="text-end">Válidos</th><th>Control</th></tr></thead><tbody>
        @forelse($operational['activityReview'] as $activity)<tr><td><div class="fw-semibold">{{ $activity->titulo }}</div><small class="text-muted">{{ \Carbon\Carbon::parse($activity->inicio)->format('d/m/Y') }} · {{ $activity->colonia ?: 'Sin colonia' }}</small><div>@if($activity->origen==='reporte_usuario')<span class="badge bg-warning text-dark">Equipo · {{ ucfirst($activity->estado_revision) }}</span>@else<span class="badge bg-primary">Oficial</span>@endif</div></td><td>{{ $activity->responsable ?: 'Sin asignar' }}<div class="small text-muted">{{ $activity->participants }} confirmaron</div></td><td class="text-end">{{ number_format($activity->asistentes ?? 0) }}</td><td class="text-end">{{ number_format($activity->records) }}</td><td class="text-end">{{ number_format($activity->valid_records) }}</td><td>@if($activity->anomaly)<span class="badge rounded-pill text-bg-warning">Revisar</span>@else<span class="badge rounded-pill text-bg-success">Consistente</span>@endif</td></tr>@empty<tr><td colspan="6" class="text-center text-muted py-4">Sin actividades registradas.</td></tr>@endforelse
      </tbody></table></div>
    </div></div>
    <div class="col-xl-4"><div class="panel"><h2 class="panel-title">Auditoría reciente</h2><div class="panel-subtitle mb-4">Quién cambió qué y cuándo</div>
      @forelse($operational['recentAudit'] as $audit)<div class="audit-line"><div class="fw-semibold small">{{ $audit->resumen }}</div><div class="small text-muted">{{ $audit->user_name ?: 'Sistema' }} · {{ \Carbon\Carbon::parse($audit->created_at)->locale('es')->diffForHumans() }}</div></div>@empty<div class="text-center text-muted py-5"><i class="fa-solid fa-clock-rotate-left fa-2x mb-2"></i><div>Sin cambios registrados todavía.</div></div>@endforelse
    </div></div>
  </div>
  <div class="text-center small text-muted mt-4"><i class="fa-solid fa-lock me-1"></i>Panel visible únicamente para Admin y SuperAdmin · Los semáforos señalan calidad operativa y recencia de datos.</div>
</div>
@else
<div class="container-xxl"><div class="row g-3">@foreach([['Convencidos totales',$stats['total'],'dark'],['Afiliados',$stats['validado'],'success'],['No afiliados',$stats['descartado'],'danger']] as [$label,$value,$color])<div class="col-md-4"><div class="card shadow-sm border-0"><div class="card-body"><div class="text-muted small">{{ $label }}</div><div class="display-6 fw-bold text-{{ $color }}">{{ number_format($value) }}</div></div></div></div>@endforeach</div>
  <div class="card shadow-sm border-0 mt-3"><div class="card-header bg-white border-0"><h6 class="mb-0"><i class="fa-solid fa-bullhorn me-1"></i> Comunicados recientes</h6></div><div class="list-group list-group-flush">@forelse($comunicadosRecientes as $c)<a href="{{ route('settings.comunicados.show',$c->id) }}" class="list-group-item list-group-item-action"><div class="fw-semibold">{{ $c->titulo }}</div><small class="text-muted">{{ optional($c->created_at)->format('d/m/Y H:i') }}</small></a>@empty<div class="list-group-item text-muted">Sin comunicados</div>@endforelse</div></div>
</div>
@endif
@endsection

@if($operational)
@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.4/dist/chart.umd.min.js"></script>
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script>
(() => {
  const canvas=document.getElementById('dailyChart');
  if(canvas&&window.Chart)new Chart(canvas,{type:'line',data:{labels:@json($operational['daily']['labels']),datasets:[{data:@json($operational['daily']['values']),borderColor:'#d91785',backgroundColor:'rgba(217,23,133,.10)',fill:true,tension:.42,borderWidth:3,pointRadius:3,pointBackgroundColor:'#fff',pointBorderColor:'#d91785',pointBorderWidth:2}]},options:{responsive:true,maintainAspectRatio:false,plugins:{legend:{display:false},tooltip:{displayColors:false,callbacks:{label:c=>`${c.parsed.y.toLocaleString('es-MX')} registros`}}},scales:{x:{grid:{display:false},ticks:{color:'#778193'}},y:{beginAtZero:true,grid:{color:'#eef0f4'},ticks:{precision:0,color:'#778193'}}}}});
  const points=@json($operational['territories']->filter(fn($row) => $row->lat !== null && $row->lng !== null)->values()); const node=document.getElementById('territoryMap');
  if(node&&window.L){const map=L.map(node,{scrollWheelZoom:false}).setView([19.7008,-101.1844],12);L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png',{maxZoom:19,attribution:'&copy; OpenStreetMap'}).addTo(map);const colors={green:'#19a67e',yellow:'#e8a83e',red:'#dc4c64'},bounds=[];points.forEach(p=>{const marker=L.circleMarker([p.lat,p.lng],{radius:Math.min(18,7+Math.sqrt(p.people)),fillColor:colors[p.status],color:'#fff',weight:2,fillOpacity:.86}).addTo(map);marker.bindPopup(`<strong>${p.colony}</strong><br>${Number(p.people).toLocaleString('es-MX')} personas<br>${p.activities} actividades`);bounds.push([p.lat,p.lng]);});if(bounds.length)map.fitBounds(bounds,{padding:[32,32],maxZoom:14});}
})();
</script>
@endpush
@endif
