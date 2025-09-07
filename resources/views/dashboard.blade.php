@extends('layouts.app')

@section('title','Dashboard')

@section('content')
<div class="container-xxl">

  {{-- Tarjetas KPI --}}
  <div class="row g-3">
    <div class="col-md-3">
      <div class="card shadow-sm border-0">
        <div class="card-body">
          <div class="text-muted small">Convencidos totales</div>
          <div class="display-6 fw-bold">{{ number_format($stats['total']) }}</div>
        </div>
      </div>
    </div>
    <div class="col-md-2">
      <div class="card shadow-sm border-0">
        <div class="card-body">
          <div class="text-muted small">Afiliados</div>
          <div class="h3 fw-bold text-success mb-0">{{ number_format($stats['validado']) }}</div>
        </div>
      </div>
    </div>
    <div class="col-md-2">
      <div class="card shadow-sm border-0">
        <div class="card-body">
          <div class="text-muted small">Pendientes</div>
          <div class="h3 fw-bold text-warning mb-0">{{ number_format($stats['pendiente']) }}</div>
        </div>
      </div>
    </div>
    <div class="col-md-2">
      <div class="card shadow-sm border-0">
        <div class="card-body">
          <div class="text-muted small">No afiliados</div>
          <div class="h3 fw-bold text-danger mb-0">{{ number_format($stats['descartado']) }}</div>
        </div>
      </div>
    </div>
    <div class="col-md-3">
      <div class="card shadow-sm border-0">
        <div class="card-body">
          <div class="text-muted small">Nuevos hoy</div>
          <div class="display-6 fw-bold">{{ number_format($stats['hoy']) }}</div>
        </div>
      </div>
    </div>
  </div>

  {{-- Gráfico últimos 7 días + Comunicados recientes --}}
  <div class="row g-3 mt-1">
    <div class="col-lg-8">
      <div class="card shadow-sm border-0 h-100">
        <div class="card-header bg-white border-0">
          <h5 class="card-title mb-0">
            <i class="fa-solid fa-chart-line me-1"></i> Altas últimos 7 días
          </h5>
        </div>
        <div class="card-body">
          <canvas id="chart7d" height="96"></canvas>
        </div>
      </div>
    </div>

    <div class="col-lg-4">
      <div class="card shadow-sm border-0 h-100">
        <div class="card-header bg-white border-0 d-flex justify-content-between align-items-center">
          <h6 class="card-title mb-0">
            <i class="fa-solid fa-bullhorn me-1"></i> Comunicados recientes
          </h6>
        </div>

        <div class="list-group list-group-flush">
          @forelse($comunicadosRecientes as $c)
            @php $unread = ($c->leido_por_mi ?? 0) == 0; @endphp
            <a href="{{ route('settings.comunicados.show', $c->id) }}"
               class="list-group-item list-group-item-action d-flex justify-content-between align-items-start
                      @if($unread) border-start border-4 border-primary bg-light @endif">
              <div class="me-2">
                <div class="fw-semibold @if($unread) text-primary @endif">{{ $c->titulo }}</div>
                <div class="small text-muted">
                  {{ optional($c->created_at)->format('Y-m-d H:i') }} &middot; {{ $c->estado }}
                </div>
              </div>
              @if($unread)
                <span class="badge bg-primary rounded-pill align-self-center">Nuevo</span>
              @endif
            </a>
          @empty
            <div class="list-group-item text-muted">Sin comunicados</div>
          @endforelse
        </div>
      </div>
    </div>
  </div>

  {{-- Top tablas + Próximas actividades --}}
  <div class="row g-3 mt-1">
    <div class="col-lg-4">
      <div class="card shadow-sm border-0 h-100">
        <div class="card-header bg-white border-0">
          <h6 class="card-title mb-0"><i class="fa-solid fa-city me-1"></i> Top municipios</h6>
        </div>
        <div class="table-responsive">
          <table class="table table-sm mb-0">
            <thead><tr><th>Municipio</th><th class="text-end">Convencidos</th></tr></thead>
            <tbody>
            @forelse($porMunicipio as $row)
              <tr>
                <td>{{ $row->municipio }}</td>
                <td class="text-end">{{ number_format($row->total) }}</td>
              </tr>
            @empty
              <tr><td colspan="2" class="text-muted">Sin datos</td></tr>
            @endforelse
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <div class="col-lg-4">
      <div class="card shadow-sm border-0 h-100">
        <div class="card-header bg-white border-0">
          <h6 class="card-title mb-0"><i class="fa-solid fa-layer-group me-1"></i> Top secciones</h6>
        </div>
        <div class="table-responsive">
          <table class="table table-sm mb-0">
            <thead><tr><th>Sección</th><th class="text-end">Convencidos</th></tr></thead>
            <tbody>
            @forelse($porSeccion as $row)
              <tr>
                <td>{{ $row->seccion }}</td>
                <td class="text-end">{{ number_format($row->total) }}</td>
              </tr>
            @empty
              <tr><td colspan="2" class="text-muted">Sin datos</td></tr>
            @endforelse
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <div class="col-lg-4">
      <div class="card shadow-sm border-0 h-100">
        <div class="card-header bg-white border-0">
          <h6 class="card-title mb-0"><i class="fa-regular fa-calendar me-1"></i> Próximas actividades (7 días)</h6>
        </div>
        <ul class="list-group list-group-flush">
          @forelse($actividades as $a)
            <li class="list-group-item">
              <div class="fw-semibold">{{ $a->titulo ?? 'Sin título' }}</div>
              <div class="small text-muted">
                {{ \Carbon\Carbon::parse($a->inicio)->format('Y-m-d H:i') }}
                @if(!empty($a->lugar)) &middot; {{ $a->lugar }} @endif
                @if(!empty($a->estado)) &middot; {{ $a->estado }} @endif
              </div>
            </li>
          @empty
            <li class="list-group-item text-muted">No hay actividades próximas.</li>
          @endforelse
        </ul>
        <div class="card-footer bg-white border-0 text-end">
          <a href="{{ route('calendario.index') }}" class="btn btn-sm btn-outline-primary">
            <i class="fa-regular fa-calendar me-1"></i> Ver calendario
          </a>
        </div>
      </div>
    </div>
  </div>

</div>
@endsection

@section('js')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', ()=>{
  const ctx = document.getElementById('chart7d');
  if (!ctx) return;

  const labels = @json($labels7);
  const data   = @json($series7);

  new Chart(ctx, {
    type: 'line',
    data: { labels, datasets: [{ label: 'Altas', data, tension: .25, fill: false, pointRadius: 3 }] },
    options: {
      responsive: true,
      plugins: { legend: { display: false } },
      scales: { x: { grid: { display:false } }, y: { beginAtZero: true, ticks: { precision:0 } } }
    }
  });
});
</script>
@endsection
