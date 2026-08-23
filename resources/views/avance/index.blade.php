@extends('layouts.app')

@section('title', 'Avance y metas')

@push('styles')
<style>
  .avance-panel { border: 0; border-radius: 1rem; overflow: hidden; }
  .avance-table { min-width: 1040px; margin-bottom: 0; }
  .avance-table thead th {
    background: #17275d; border-color: rgba(255,255,255,.22); color: #fff;
    font-size: .73rem; font-weight: 700; letter-spacing: .035em;
    padding: .85rem .55rem; text-align: center; text-transform: uppercase;
    vertical-align: middle; white-space: normal;
  }
  .avance-table tbody td { border-color: #eef0f5; font-size: .86rem; padding: .72rem .55rem; vertical-align: middle; }
  .avance-table tbody tr:hover td { background: #f8f9fc; }
  .avance-resumen td { background: #f0f2ed !important; font-weight: 700; }
  .avance-resumen td:first-child { color: #17275d; }
  .avance-porcentaje { color: #fff; font-weight: 800; min-width: 84px; text-align: center; }
  .avance-success { background: #198754 !important; }
  .avance-warning { background: #d7a900 !important; color: #332900 !important; }
  .avance-danger { background: #b3262e !important; }
  .avance-secondary { background: #6c757d !important; }
  .avance-periodo { color: #667085; font-size: .87rem; }
  .avance-editar { color: var(--granate); }
  .avance-editar:hover { color: var(--granate-osc); }
  @media (max-width: 767.98px) {
    .avance-table thead th, .avance-table tbody td { font-size: .72rem; padding: .58rem .4rem; }
  }
</style>
@endpush

@section('content')
<div class="container-xl">
  @php
    $clasePorcentaje = function ($porcentaje, $meta) {
      if ((int) $meta <= 0) return 'avance-secondary';
      if ($porcentaje >= 80) return 'avance-success';
      if ($porcentaje >= 30) return 'avance-warning';
      return 'avance-danger';
    };
    $distritoResumen = $distritoFederal !== ''
      ? $distritoFederal
      : $avance->pluck('distritos_federales')->filter()->unique()->implode(', ');
  @endphp

  <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-3">
    <div>
      <h1 class="h3 mb-1">Avance por municipio</h1>
      <div class="avance-periodo">
        <i class="fa-regular fa-calendar me-1"></i>
        {{ \Carbon\Carbon::parse($fechaInicio)->format('d/m/Y') }} al {{ \Carbon\Carbon::parse($fechaFin)->format('d/m/Y') }}
      </div>
    </div>
    @can('avance.metas')
      <button type="button" class="btn btn-granate btn-nueva-meta" data-bs-toggle="modal" data-bs-target="#modalMeta">
        <i class="fa-solid fa-bullseye me-1"></i> Asignar meta
      </button>
    @endcan
  </div>

  @if(session('status'))
    <div class="alert alert-success alert-dismissible fade show">
      <i class="fa-solid fa-circle-check me-1"></i>{{ session('status') }}
      <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
  @endif

  @if($errors->any())
    <div class="alert alert-danger">
      <strong>No se pudo guardar la meta.</strong>
      <ul class="mb-0 mt-2">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
    </div>
  @endif

  <div class="card shadow-sm border-0 mb-3">
    <div class="card-body py-3">
      <form method="GET" action="{{ route('avance.index') }}" class="row g-2 align-items-end">
        <div class="col-sm-6 col-lg-2">
          <label class="form-label small text-muted">Desde</label>
          <input type="date" name="fecha_inicio" value="{{ $fechaInicio }}" class="form-control">
        </div>
        <div class="col-sm-6 col-lg-2">
          <label class="form-label small text-muted">Hasta</label>
          <input type="date" name="fecha_fin" value="{{ $fechaFin }}" class="form-control">
        </div>
        <div class="col-sm-6 col-lg-2">
          <label class="form-label small text-muted">Distrito federal</label>
          <select name="distrito_federal" class="form-select">
            <option value="">Todos</option>
            @foreach($distritosFederales as $distrito)
              <option value="{{ $distrito }}" {{ (string)$distritoFederal === (string)$distrito ? 'selected' : '' }}>Distrito {{ $distrito }}</option>
            @endforeach
          </select>
        </div>
        <div class="col-sm-6 col-lg-2">
          <label class="form-label small text-muted">Distrito local</label>
          <select name="distrito_local" class="form-select">
            <option value="">Todos</option>
            @foreach($distritosLocales as $distrito)
              <option value="{{ $distrito }}" {{ (string)$distritoLocal === (string)$distrito ? 'selected' : '' }}>Distrito {{ $distrito }}</option>
            @endforeach
          </select>
        </div>
        <div class="col-sm-6 col-lg-2">
          <label class="form-label small text-muted">Municipio</label>
          <select name="cve_mun" class="form-select">
            <option value="">Todos</option>
            @foreach($avance as $fila)
              <option value="{{ $fila['cve_mun'] }}" {{ (string)$cveMun === (string)$fila['cve_mun'] ? 'selected' : '' }}>{{ $fila['municipio'] }}</option>
            @endforeach
          </select>
        </div>
        <div class="col-sm-6 col-lg-2 d-grid">
          <button class="btn btn-outline-primary"><i class="fa-solid fa-filter me-1"></i> Aplicar</button>
        </div>
        <div class="col-md-5">
          <label class="form-label small text-muted">Referente / Referencia</label>
          <select name="referente" class="form-select">
            <option value="">Todos los referentes</option>
            @foreach($referentes as $nombreReferente)
              <option value="{{ $nombreReferente }}" {{ $referente === $nombreReferente ? 'selected' : '' }}>{{ $nombreReferente }}</option>
            @endforeach
          </select>
        </div>
        <div class="col-md-5">
          <label class="form-label small text-muted">Capturista</label>
          <select name="capturista_id" class="form-select">
            <option value="">Todos los capturistas</option>
            @foreach($capturistas as $usuario)
              <option value="{{ $usuario->id }}" {{ (string)$capturistaId === (string)$usuario->id ? 'selected' : '' }}>{{ $usuario->name }}</option>
            @endforeach
          </select>
        </div>
        <div class="col-md-2 d-grid"><a href="{{ route('avance.index') }}" class="btn btn-outline-secondary">Limpiar</a></div>
      </form>
    </div>
  </div>

  <div class="card avance-panel shadow-sm mb-4">
    <div class="table-responsive">
      <table class="table avance-table">
        <thead>
          <tr>
            <th>DFn</th><th>Municipio</th><th>Secciones</th><th>Meta<br>nacional</th>
            <th>Avance<br>nacional</th><th>% avance<br>nacional</th><th>Meta<br>estatal</th>
            <th>Avance<br>estatal</th><th>% avance<br>estatal</th>
          </tr>
        </thead>
        <tbody>
          <tr class="avance-resumen">
            <td class="text-center">{{ $distritoResumen ?: '—' }}</td><td>Totales</td>
            <td class="text-center">{{ number_format($totales['secciones']) }}</td>
            <td class="text-center">{{ number_format($totales['meta_nacional']) }}</td>
            <td class="text-center">{{ number_format($totales['avance_nacional']) }}</td>
            <td class="avance-porcentaje {{ $clasePorcentaje($totales['porcentaje_nacional'], $totales['meta_nacional']) }}">{{ number_format($totales['porcentaje_nacional'], 2) }}%</td>
            <td class="text-center">{{ number_format($totales['meta_estatal']) }}</td>
            <td class="text-center">{{ number_format($totales['avance_estatal']) }}</td>
            <td class="avance-porcentaje {{ $clasePorcentaje($totales['porcentaje_estatal'], $totales['meta_estatal']) }}">{{ number_format($totales['porcentaje_estatal'], 2) }}%</td>
          </tr>

          @forelse($avance as $fila)
            <tr>
              <td class="text-center">{{ $fila['distritos_federales'] ?: '—' }}</td>
              <td>
                <div class="d-flex align-items-center justify-content-between gap-2">
                  <div><strong>{{ $fila['municipio'] }}</strong><div class="small text-muted">CVE {{ $fila['cve_mun'] }}</div></div>
                  @can('avance.metas')
                    <button type="button" class="btn btn-sm btn-link avance-editar btn-asignar-meta"
                      title="Asignar metas a {{ $fila['municipio'] }}" data-bs-toggle="modal" data-bs-target="#modalMeta"
                      data-cve-mun="{{ $fila['cve_mun'] }}" data-meta-nacional="{{ $fila['meta_nacional'] }}"
                      data-meta-estatal="{{ $fila['meta_estatal'] }}"><i class="fa-solid fa-pen-to-square"></i></button>
                  @endcan
                </div>
              </td>
              <td class="text-center">{{ number_format($fila['secciones']) }}</td>
              <td class="text-center fw-semibold">{{ $fila['meta_nacional'] > 0 ? number_format($fila['meta_nacional']) : '—' }}</td>
              <td class="text-center">{{ number_format($fila['avance_nacional']) }}</td>
              <td class="avance-porcentaje {{ $clasePorcentaje($fila['porcentaje_nacional'], $fila['meta_nacional']) }}">{{ $fila['meta_nacional'] > 0 ? number_format($fila['porcentaje_nacional'], 2).'%' : 'Sin meta' }}</td>
              <td class="text-center fw-semibold">{{ $fila['meta_estatal'] > 0 ? number_format($fila['meta_estatal']) : '—' }}</td>
              <td class="text-center">{{ number_format($fila['avance_estatal']) }}</td>
              <td class="avance-porcentaje {{ $clasePorcentaje($fila['porcentaje_estatal'], $fila['meta_estatal']) }}">{{ $fila['meta_estatal'] > 0 ? number_format($fila['porcentaje_estatal'], 2).'%' : 'Sin meta' }}</td>
            </tr>
          @empty
            <tr><td colspan="9" class="text-center text-muted py-5">No hay información para los filtros seleccionados.</td></tr>
          @endforelse
        </tbody>
      </table>
    </div>
  </div>
</div>

@can('avance.metas')
<div class="modal fade" id="modalMeta" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content border-0 shadow">
      <form method="POST" action="{{ route('avance.metas.store') }}">
        @csrf
        <div class="modal-header">
          <h5 class="modal-title"><i class="fa-solid fa-bullseye me-1"></i> Asignar metas</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <div class="mb-3">
            <label class="form-label">Municipio</label>
            <select name="cve_mun" id="meta_cve_mun" class="form-select" required>
              <option value="">Selecciona un municipio</option>
              @foreach($avance as $fila)
                <option value="{{ $fila['cve_mun'] }}" data-secciones="{{ $fila['secciones'] }}"
                  data-meta-nacional="{{ $fila['meta_nacional'] }}" data-meta-estatal="{{ $fila['meta_estatal'] }}"
                  {{ old('cve_mun') === $fila['cve_mun'] ? 'selected' : '' }}>{{ $fila['municipio'] }} · {{ $fila['cve_mun'] }}</option>
              @endforeach
            </select>
            <div class="form-text">Sugerencia de la referencia: nacional = 2 por sección y estatal = 5 por sección.</div>
          </div>
          <div class="row g-3 mb-3">
            <div class="col-sm-6">
              <label class="form-label">Meta nacional</label>
              <input type="number" name="meta_nacional" id="meta_nacional" value="{{ old('meta_nacional') }}" class="form-control" min="1" required>
            </div>
            <div class="col-sm-6">
              <label class="form-label">Meta estatal</label>
              <input type="number" name="meta_estatal" id="meta_estatal" value="{{ old('meta_estatal') }}" class="form-control" min="1" required>
            </div>
          </div>
          <div class="row g-2">
            <div class="col-sm-6">
              <label class="form-label">Fecha de inicio</label>
              <input type="date" name="fecha_inicio" value="{{ old('fecha_inicio', $fechaInicio) }}" class="form-control" required>
            </div>
            <div class="col-sm-6">
              <label class="form-label">Fecha de término</label>
              <input type="date" name="fecha_fin" value="{{ old('fecha_fin', $fechaFin) }}" class="form-control" required>
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
          <button type="submit" class="btn btn-granate"><i class="fa-solid fa-floppy-disk me-1"></i> Guardar metas</button>
        </div>
      </form>
    </div>
  </div>
</div>
@endcan
@endsection

@section('js')
@can('avance.metas')
<script>
document.addEventListener('DOMContentLoaded', function () {
  const municipio = document.getElementById('meta_cve_mun');
  const metaNacional = document.getElementById('meta_nacional');
  const metaEstatal = document.getElementById('meta_estatal');

  function cargarMetas(cveMun, nacional, estatal) {
    municipio.value = cveMun || '';
    const opcion = municipio.options[municipio.selectedIndex];
    const secciones = Number(opcion?.dataset.secciones || 0);
    metaNacional.value = Number(nacional) > 0 ? nacional : (secciones ? secciones * 2 : '');
    metaEstatal.value = Number(estatal) > 0 ? estatal : (secciones ? secciones * 5 : '');
  }

  document.querySelectorAll('.btn-asignar-meta').forEach(function (boton) {
    boton.addEventListener('click', function () {
      cargarMetas(boton.dataset.cveMun, boton.dataset.metaNacional, boton.dataset.metaEstatal);
    });
  });

  document.querySelectorAll('.btn-nueva-meta').forEach(function (boton) {
    boton.addEventListener('click', function () {
      cargarMetas('', '', '');
    });
  });

  municipio.addEventListener('change', function () {
    const opcion = municipio.options[municipio.selectedIndex];
    cargarMetas(municipio.value, opcion?.dataset.metaNacional, opcion?.dataset.metaEstatal);
  });

  @if($errors->any())
    bootstrap.Modal.getOrCreateInstance(document.getElementById('modalMeta')).show();
  @endif
});
</script>
@endcan
@endsection
