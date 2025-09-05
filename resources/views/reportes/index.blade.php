@extends('layouts.app')

@section('title','Reportes')

@section('content_header')
  <h1 class="text-center w-100">Reportes</h1>
@endsection

@section('content')
<div class="container-xl">
  <div class="card card-outline card-primary">
    <div class="card-header d-flex justify-content-between align-items-center">
      <h3 class="card-title">Centro de reportes</h3>
      <div class="btn-group">
        <a href="{{ route('reportes.afiliados') }}" class="btn btn-primary btn-sm">
          <i class="fa fa-users"></i> Afiliados
        </a>
        <a href="{{ route('reportes.secciones') }}" class="btn btn-primary btn-sm">
          <i class="fa fa-list-ol"></i> Secciones
        </a>
        <a href="{{ route('reportes.capturistas') }}" class="btn btn-primary btn-sm">
          <i class="fa fa-id-badge"></i> Capturistas
        </a>
      </div>
    </div>

    <div class="card-body">
      {{-- Filtros globales (se pasarán a cada reporte mediante querystring) --}}
      <form id="formFiltros" class="row g-2 mb-4" onsubmit="return false">
        <div class="col-12">
          <h5 class="mb-2"><i class="fa fa-filter"></i> Filtros</h5>
        </div>

        <div class="col-6 col-md-3">
          <input type="text" name="municipio" value="{{ request('municipio') }}" class="form-control form-control-sm" placeholder="Municipio">
        </div>
        <div class="col-6 col-md-2">
          <input type="text" name="cve_mun" value="{{ request('cve_mun') }}" class="form-control form-control-sm" placeholder="CVE MUN (e.g. 053)">
        </div>
        <div class="col-6 col-md-2">
          <input type="text" name="seccion" value="{{ request('seccion') }}" class="form-control form-control-sm" placeholder="Sección">
        </div>
        <div class="col-6 col-md-2">
          <select name="estatus" class="form-control form-control-sm">
            @php $est = request('estatus'); @endphp
            <option value="">Afiliado (todos)</option>
            <option value="pendiente"  {{ $est==='pendiente'  ? 'selected':'' }}>Pendiente</option>
            <option value="validado"   {{ $est==='validado'   ? 'selected':'' }}>Sí</option>
            <option value="descartado" {{ $est==='descartado' ? 'selected':'' }}>No</option>
          </select>
        </div>
        <div class="col-6 col-md-2">
          <input type="number" name="capturista_id" value="{{ request('capturista_id') }}" class="form-control form-control-sm" placeholder="ID Capturista">
        </div>

        <div class="col-12 col-md-6">
          <label class="small text-muted mb-1 d-block">Rango de captura (created_at)</label>
          <div class="d-flex gap-2">
            <input type="date" name="created_desde" value="{{ request('created_desde') }}" class="form-control form-control-sm">
            <input type="date" name="created_hasta" value="{{ request('created_hasta') }}" class="form-control form-control-sm">
          </div>
        </div>

        <div class="col-12 col-md-6">
          <label class="small text-muted mb-1 d-block">Rango de fecha de convencimiento</label>
          <div class="d-flex gap-2">
            <input type="date" name="fecha_convencimiento_desde" value="{{ request('fecha_convencimiento_desde') }}" class="form-control form-control-sm">
            <input type="date" name="fecha_convencimiento_hasta" value="{{ request('fecha_convencimiento_hasta') }}" class="form-control form-control-sm">
          </div>
        </div>

        <div class="col-12 d-flex gap-2 mt-2">
          <button type="button" class="btn btn-outline-primary btn-sm" onclick="irA('{{ route('reportes.afiliados') }}')">
            <i class="fa fa-table"></i> Ver afiliados
          </button>
          <button type="button" class="btn btn-outline-primary btn-sm" onclick="irA('{{ route('reportes.secciones') }}')">
            <i class="fa fa-table"></i> Ver secciones
          </button>
          <button type="button" class="btn btn-outline-primary btn-sm" onclick="irA('{{ route('reportes.capturistas') }}')">
            <i class="fa fa-table"></i> Ver capturistas
          </button>

          <div class="ms-auto"></div>

          <button type="button" class="btn btn-outline-success btn-sm" onclick="descargar('{{ route('reportes.afiliados.export.xlsx') }}')">
            <i class="fa fa-file-excel"></i> Exportar afiliados (XLSX)
          </button>
          <button type="button" class="btn btn-outline-success btn-sm" onclick="descargar('{{ route('reportes.secciones.export.xlsx') }}')">
            <i class="fa fa-file-excel"></i> Exportar secciones (XLSX)
          </button>
          <button type="button" class="btn btn-outline-success btn-sm" onclick="descargar('{{ route('reportes.capturistas.export.xlsx') }}')">
            <i class="fa fa-file-excel"></i> Exportar capturistas (XLSX)
          </button>

          <button type="button" class="btn btn-outline-secondary btn-sm" onclick="limpiarFiltros()">
            <i class="fa fa-eraser"></i> Limpiar
          </button>
        </div>
      </form>

      {{-- Tarjetas de acceso rápido --}}
      <div class="row g-3">
        <div class="col-md-4">
          <div class="card h-100 border">
            <div class="card-body">
              <h5 class="card-title mb-2"><i class="fa fa-users"></i> Afiliados</h5>
              <p class="text-muted small mb-3">Listado detallado con filtros por municipio, sección, estatus y capturista.</p>
              <div class="d-flex gap-2">
                <a class="btn btn-primary btn-sm" href="{{ route('reportes.afiliados') }}"><i class="fa fa-eye"></i> Ver</a>
                <button class="btn btn-success btn-sm" type="button" onclick="descargar('{{ route('reportes.afiliados.export.xlsx') }}')">
                  <i class="fa fa-file-excel"></i> Exportar
                </button>
              </div>
            </div>
          </div>
        </div>

        <div class="col-md-4">
          <div class="card h-100 border">
            <div class="card-body">
              <h5 class="card-title mb-2"><i class="fa fa-list-ol"></i> Secciones</h5>
              <p class="text-muted small mb-3">Agregados por sección y municipio con % de validados.</p>
              <div class="d-flex gap-2">
                <a class="btn btn-primary btn-sm" href="{{ route('reportes.secciones') }}"><i class="fa fa-eye"></i> Ver</a>
                <button class="btn btn-success btn-sm" type="button" onclick="descargar('{{ route('reportes.secciones.export.xlsx') }}')">
                  <i class="fa fa-file-excel"></i> Exportar
                </button>
              </div>
            </div>
          </div>
        </div>

        <div class="col-md-4">
          <div class="card h-100 border">
            <div class="card-body">
              <h5 class="card-title mb-2"><i class="fa fa-id-badge"></i> Capturistas</h5>
              <p class="text-muted small mb-3">Rendimiento por usuario: total, validados, descartados y última captura.</p>
              <div class="d-flex gap-2">
                <a class="btn btn-primary btn-sm" href="{{ route('reportes.capturistas') }}"><i class="fa fa-eye"></i> Ver</a>
                <button class="btn btn-success btn-sm" type="button" onclick="descargar('{{ route('reportes.capturistas.export.xlsx') }}')">
                  <i class="fa fa-file-excel"></i> Exportar
                </button>
              </div>
            </div>
          </div>
        </div>
      </div> {{-- row --}}
    </div>
  </div>
</div>
@endsection

@section('js')
<script>
  function qsFromForm(form) {
    const fd = new FormData(form);
    const p = new URLSearchParams();
    for (const [k, v] of fd.entries()) {
      if (v !== null && v !== '') p.append(k, v);
    }
    return p.toString();
  }

  function irA(urlBase) {
    const f = document.getElementById('formFiltros');
    const qs = qsFromForm(f);
    window.location.href = qs ? (urlBase + '?' + qs) : urlBase;
  }

  function descargar(urlBase) {
    const f = document.getElementById('formFiltros');
    const qs = qsFromForm(f);
    const url = qs ? (urlBase + '?' + qs) : urlBase;
    window.open(url, '_blank');
  }

  function limpiarFiltros() {
    const f = document.getElementById('formFiltros');
    f.reset();
  }
</script>
@endsection
