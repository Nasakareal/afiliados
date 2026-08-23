@extends('layouts.app')

@section('title', 'Avance y metas')

@section('content')
<div class="container-xl">

  @php
    $colorAvance = function ($porcentaje, $meta) {
      if ((int)$meta <= 0) {
        return 'secondary';
      }

      if ($porcentaje >= 80) {
        return 'success';
      }

      if ($porcentaje >= 30) {
        return 'warning';
      }

      return 'danger';
    };
  @endphp

  <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
    <div>
      <h1 class="h3 mb-1">Avance y asignación de metas</h1>
      <p class="text-muted mb-0">
        Seguimiento de convencidos y lonas por municipio
      </p>
    </div>

    @can('avance.metas')
      <button
        type="button"
        class="btn btn-granate"
        data-bs-toggle="modal"
        data-bs-target="#modalMeta"
      >
        <i class="fa-solid fa-bullseye me-1"></i> Asignar meta
      </button>
    @endcan
  </div>

  @if(session('status'))
    <div class="alert alert-success alert-dismissible fade show">
      <i class="fa-solid fa-circle-check me-1"></i>
      {{ session('status') }}
      <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
  @endif

  @if($errors->any())
    <div class="alert alert-danger">
      <strong>No se pudo guardar la información.</strong>
      <ul class="mb-0 mt-2">
        @foreach($errors->all() as $error)
          <li>{{ $error }}</li>
        @endforeach
      </ul>
    </div>
  @endif

  <div class="card shadow-sm border-0 mb-4">
    <div class="card-body">
      <form method="GET" action="{{ route('avance.index') }}">
        <div class="row g-2">

          <div class="col-md-2">
            <label class="form-label small text-muted">Desde</label>
            <input
              type="date"
              name="fecha_inicio"
              value="{{ $fechaInicio }}"
              class="form-control"
            >
          </div>

          <div class="col-md-2">
            <label class="form-label small text-muted">Hasta</label>
            <input
              type="date"
              name="fecha_fin"
              value="{{ $fechaFin }}"
              class="form-control"
            >
          </div>

          <div class="col-md-2">
            <label class="form-label small text-muted">Distrito local</label>
            <select name="distrito_local" class="form-select">
              <option value="">Todos</option>
              @foreach($distritosLocales as $distrito)
                <option
                  value="{{ $distrito }}"
                  @selected((string)$distritoLocal === (string)$distrito)
                >
                  Distrito {{ $distrito }}
                </option>
              @endforeach
            </select>
          </div>

          <div class="col-md-2">
            <label class="form-label small text-muted">Distrito federal</label>
            <select name="distrito_federal" class="form-select">
              <option value="">Todos</option>
              @foreach($distritosFederales as $distrito)
                <option
                  value="{{ $distrito }}"
                  @selected((string)$distritoFederal === (string)$distrito)
                >
                  Distrito {{ $distrito }}
                </option>
              @endforeach
            </select>
          </div>

          <div class="col-md-4">
            <label class="form-label small text-muted">Municipio</label>
            <select name="cve_mun" class="form-select">
              <option value="">Todos los municipios</option>
              @foreach($avance as $fila)
                <option
                  value="{{ $fila['cve_mun'] }}"
                  @selected((string)$cveMun === (string)$fila['cve_mun'])
                >
                  {{ $fila['municipio'] }} · {{ $fila['cve_mun'] }}
                </option>
              @endforeach
            </select>
          </div>

          <div class="col-md-6">
            <label class="form-label small text-muted">Referente / Referencia</label>
            <select name="referente" class="form-select">
              <option value="">Todos los referentes</option>
              @foreach($referentes as $nombreReferente)
                <option
                  value="{{ $nombreReferente }}"
                  @selected($referente === $nombreReferente)
                >
                  {{ $nombreReferente }}
                </option>
              @endforeach
            </select>
          </div>

          <div class="col-md-6">
            <label class="form-label small text-muted">Capturista</label>
            <select name="capturista_id" class="form-select">
              <option value="">Todos los capturistas</option>
              @foreach($capturistas as $usuario)
                <option
                  value="{{ $usuario->id }}"
                  @selected((string)$capturistaId === (string)$usuario->id)
                >
                  {{ $usuario->name }}
                </option>
              @endforeach
            </select>
          </div>

          <div class="col-md-3 d-grid">
            <button class="btn btn-outline-primary">
              <i class="fa-solid fa-magnifying-glass me-1"></i> Aplicar filtros
            </button>
          </div>

          <div class="col-md-3 d-grid">
            <a href="{{ route('avance.index') }}" class="btn btn-outline-secondary">
              <i class="fa-solid fa-xmark me-1"></i> Limpiar filtros
            </a>
          </div>

        </div>
      </form>
    </div>
  </div>

  <div class="row g-3 mb-4">

    <div class="col-md-6 col-xl-3">
      <div class="card shadow-sm border-0 h-100">
        <div class="card-body">
          <div class="text-muted small mb-1">Meta convencidos</div>
          <div class="display-6 fw-bold">
            {{ number_format($totales['meta_convencidos']) }}
          </div>
        </div>
      </div>
    </div>

    <div class="col-md-6 col-xl-3">
      <div class="card shadow-sm border-0 h-100">
        <div class="card-body">
          <div class="text-muted small mb-1">Avance convencidos</div>
          <div class="d-flex justify-content-between align-items-end">
            <div class="display-6 fw-bold">
              {{ number_format($totales['convencidos']) }}
            </div>
            <span class="badge bg-{{ $colorAvance($totales['porcentaje_convencidos'], $totales['meta_convencidos']) }} fs-6">
              {{ number_format($totales['porcentaje_convencidos'], 2) }}%
            </span>
          </div>
        </div>
      </div>
    </div>

    <div class="col-md-6 col-xl-3">
      <div class="card shadow-sm border-0 h-100">
        <div class="card-body">
          <div class="text-muted small mb-1">Meta lonas</div>
          <div class="display-6 fw-bold">
            {{ number_format($totales['meta_lonas']) }}
          </div>
        </div>
      </div>
    </div>

    <div class="col-md-6 col-xl-3">
      <div class="card shadow-sm border-0 h-100">
        <div class="card-body">
          <div class="text-muted small mb-1">Avance lonas</div>
          <div class="d-flex justify-content-between align-items-end">
            <div class="display-6 fw-bold">
              {{ number_format($totales['lonas']) }}
            </div>
            <span class="badge bg-{{ $colorAvance($totales['porcentaje_lonas'], $totales['meta_lonas']) }} fs-6">
              {{ number_format($totales['porcentaje_lonas'], 2) }}%
            </span>
          </div>
        </div>
      </div>
    </div>

  </div>

  <div class="card shadow-sm border-0 mb-4">
    <div class="card-header bg-white border-0 pt-3">
      <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
        <div>
          <h2 class="h5 mb-1">Avance de convencidos</h2>
          <div class="text-muted small">
            {{ \Carbon\Carbon::parse($fechaInicio)->format('d/m/Y') }}
            al
            {{ \Carbon\Carbon::parse($fechaFin)->format('d/m/Y') }}
          </div>
        </div>

        <span class="badge bg-{{ $colorAvance($totales['porcentaje_convencidos'], $totales['meta_convencidos']) }} fs-6">
          {{ number_format($totales['porcentaje_convencidos'], 2) }}% general
        </span>
      </div>
    </div>

    <div class="card-body">
      <div class="table-responsive">
        <table class="table table-hover align-middle">
          <thead>
            <tr>
              <th>Municipio</th>
              <th>Distrito</th>
              <th class="text-center">Secciones</th>
              <th class="text-end">Meta</th>
              <th class="text-end">Convencidos</th>
              <th style="min-width:190px;">Avance</th>
              @can('avance.metas')
                <th class="text-end">Meta</th>
              @endcan
            </tr>
          </thead>

          <tbody>
            @forelse($avance as $fila)
              @php
                $clase = $colorAvance(
                  $fila['porcentaje_convencidos'],
                  $fila['meta_convencidos']
                );

                $barra = min(100, $fila['porcentaje_convencidos']);
              @endphp

              <tr>
                <td>
                  <strong>{{ $fila['municipio'] }}</strong>
                  <div class="small text-muted">
                    CVE {{ $fila['cve_mun'] }}
                  </div>
                </td>

                <td>
                  <div>
                    Local: {{ $fila['distritos_locales'] ?: '—' }}
                  </div>
                  <div class="small text-muted">
                    Federal: {{ $fila['distritos_federales'] ?: '—' }}
                  </div>
                </td>

                <td class="text-center">
                  {{ number_format($fila['secciones']) }}
                </td>

                <td class="text-end fw-semibold">
                  {{ number_format($fila['meta_convencidos']) }}
                </td>

                <td class="text-end fw-semibold">
                  {{ number_format($fila['convencidos']) }}
                </td>

                <td>
                  @if($fila['meta_convencidos'] > 0)
                    <div class="d-flex justify-content-between mb-1">
                      <span class="small">
                        {{ number_format($fila['porcentaje_convencidos'], 2) }}%
                      </span>
                      <span class="badge bg-{{ $clase }}">
                        {{ $fila['convencidos'] }}/{{ $fila['meta_convencidos'] }}
                      </span>
                    </div>

                    <div class="progress" style="height:8px;">
                      <div
                        class="progress-bar bg-{{ $clase }}"
                        style="width:{{ $barra }}%"
                      ></div>
                    </div>
                  @else
                    <span class="badge bg-secondary">Sin meta</span>
                  @endif
                </td>

                @can('avance.metas')
                  <td class="text-end">
                    <button
                      type="button"
                      class="btn btn-sm btn-outline-primary btn-asignar-meta"
                      data-bs-toggle="modal"
                      data-bs-target="#modalMeta"
                      data-cve-mun="{{ $fila['cve_mun'] }}"
                      data-tipo="convencidos"
                      data-meta="{{ $fila['meta_convencidos'] }}"
                    >
                      <i class="fa-solid fa-bullseye"></i>
                    </button>
                  </td>
                @endcan
              </tr>
            @empty
              <tr>
                <td colspan="7" class="text-center text-muted py-5">
                  No hay información para los filtros seleccionados.
                </td>
              </tr>
            @endforelse
          </tbody>
        </table>
      </div>
    </div>
  </div>

  <div class="card shadow-sm border-0 mb-4">
    <div class="card-header bg-white border-0 pt-3">
      <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
        <div>
          <h2 class="h5 mb-1">Avance de lonas</h2>
          <div class="text-muted small">
            {{ \Carbon\Carbon::parse($fechaInicio)->format('d/m/Y') }}
            al
            {{ \Carbon\Carbon::parse($fechaFin)->format('d/m/Y') }}
          </div>
        </div>

        <span class="badge bg-{{ $colorAvance($totales['porcentaje_lonas'], $totales['meta_lonas']) }} fs-6">
          {{ number_format($totales['porcentaje_lonas'], 2) }}% general
        </span>
      </div>
    </div>

    <div class="card-body">
      <div class="table-responsive">
        <table class="table table-hover align-middle">
          <thead>
            <tr>
              <th>Municipio</th>
              <th>Distrito</th>
              <th class="text-center">Secciones</th>
              <th class="text-end">Meta</th>
              <th class="text-end">Lonas</th>
              <th style="min-width:190px;">Avance</th>
              @can('avance.metas')
                <th class="text-end">Meta</th>
              @endcan
            </tr>
          </thead>

          <tbody>
            @forelse($avance as $fila)
              @php
                $clase = $colorAvance(
                  $fila['porcentaje_lonas'],
                  $fila['meta_lonas']
                );

                $barra = min(100, $fila['porcentaje_lonas']);
              @endphp

              <tr>
                <td>
                  <strong>{{ $fila['municipio'] }}</strong>
                  <div class="small text-muted">
                    CVE {{ $fila['cve_mun'] }}
                  </div>
                </td>

                <td>
                  <div>
                    Local: {{ $fila['distritos_locales'] ?: '—' }}
                  </div>
                  <div class="small text-muted">
                    Federal: {{ $fila['distritos_federales'] ?: '—' }}
                  </div>
                </td>

                <td class="text-center">
                  {{ number_format($fila['secciones']) }}
                </td>

                <td class="text-end fw-semibold">
                  {{ number_format($fila['meta_lonas']) }}
                </td>

                <td class="text-end fw-semibold">
                  {{ number_format($fila['lonas']) }}
                </td>

                <td>
                  @if($fila['meta_lonas'] > 0)
                    <div class="d-flex justify-content-between mb-1">
                      <span class="small">
                        {{ number_format($fila['porcentaje_lonas'], 2) }}%
                      </span>
                      <span class="badge bg-{{ $clase }}">
                        {{ $fila['lonas'] }}/{{ $fila['meta_lonas'] }}
                      </span>
                    </div>

                    <div class="progress" style="height:8px;">
                      <div
                        class="progress-bar bg-{{ $clase }}"
                        style="width:{{ $barra }}%"
                      ></div>
                    </div>
                  @else
                    <span class="badge bg-secondary">Sin meta</span>
                  @endif
                </td>

                @can('avance.metas')
                  <td class="text-end">
                    <button
                      type="button"
                      class="btn btn-sm btn-outline-primary btn-asignar-meta"
                      data-bs-toggle="modal"
                      data-bs-target="#modalMeta"
                      data-cve-mun="{{ $fila['cve_mun'] }}"
                      data-tipo="lonas"
                      data-meta="{{ $fila['meta_lonas'] }}"
                    >
                      <i class="fa-solid fa-bullseye"></i>
                    </button>
                  </td>
                @endcan
              </tr>
            @empty
              <tr>
                <td colspan="7" class="text-center text-muted py-5">
                  No hay información para los filtros seleccionados.
                </td>
              </tr>
            @endforelse
          </tbody>
        </table>
      </div>
    </div>
  </div>

</div>

@can('avance.metas')
<div
  class="modal fade"
  id="modalMeta"
  tabindex="-1"
  aria-hidden="true"
>
  <div class="modal-dialog">
    <div class="modal-content">

      <form method="POST" action="{{ route('avance.metas.store') }}">
        @csrf

        <div class="modal-header">
          <h5 class="modal-title">
            <i class="fa-solid fa-bullseye me-1"></i>
            Asignar meta
          </h5>

          <button
            type="button"
            class="btn-close"
            data-bs-dismiss="modal"
          ></button>
        </div>

        <div class="modal-body">

          <div class="mb-3">
            <label class="form-label">Tipo de meta</label>
            <select
              name="tipo"
              id="meta_tipo"
              class="form-select"
              required
            >
              <option value="convencidos">Convencidos</option>
              <option value="lonas">Lonas</option>
            </select>
          </div>

          <div class="mb-3">
            <label class="form-label">Municipio</label>
            <select
              name="cve_mun"
              id="meta_cve_mun"
              class="form-select"
              required
            >
              <option value="">Selecciona un municipio</option>

              @foreach($avance as $fila)
                <option value="{{ $fila['cve_mun'] }}">
                  {{ $fila['municipio'] }} · {{ $fila['cve_mun'] }}
                </option>
              @endforeach
            </select>
          </div>

          <div class="mb-3">
            <label class="form-label">Meta</label>
            <input
              type="number"
              name="meta"
              id="meta_cantidad"
              class="form-control"
              min="0"
              required
            >
          </div>

          <div class="row g-2">
            <div class="col-md-6">
              <label class="form-label">Fecha de inicio</label>
              <input
                type="date"
                name="fecha_inicio"
                value="{{ $fechaInicio }}"
                class="form-control"
                required
              >
            </div>

            <div class="col-md-6">
              <label class="form-label">Fecha de término</label>
              <input
                type="date"
                name="fecha_fin"
                value="{{ $fechaFin }}"
                class="form-control"
                required
              >
            </div>
          </div>

        </div>

        <div class="modal-footer">
          <button
            type="button"
            class="btn btn-outline-secondary"
            data-bs-dismiss="modal"
          >
            Cancelar
          </button>

          <button type="submit" class="btn btn-granate">
            <i class="fa-solid fa-floppy-disk me-1"></i>
            Guardar meta
          </button>
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
  const botones = document.querySelectorAll('.btn-asignar-meta');
  const tipo = document.getElementById('meta_tipo');
  const municipio = document.getElementById('meta_cve_mun');
  const meta = document.getElementById('meta_cantidad');

  botones.forEach(function (boton) {
    boton.addEventListener('click', function () {
      tipo.value = boton.dataset.tipo || 'convencidos';
      municipio.value = boton.dataset.cveMun || '';
      meta.value = boton.dataset.meta || '';
    });
  });
});
</script>
@endcan
@endsection
