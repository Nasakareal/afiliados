@extends('layouts.app')

@section('title','Reporte: Capturistas')

@section('content_header')
  <h1 class="text-center w-100">Reporte · Capturistas</h1>
@endsection

@section('content')
<div class="container-xl">
  <div class="card card-outline card-primary">
    <div class="card-header d-flex justify-content-between align-items-center">
      <h3 class="card-title mb-0">Rendimiento por capturista</h3>
      <div class="btn-group">
        <a href="{{ route('reportes.capturistas.export.xlsx', request()->query()) }}" class="btn btn-success btn-sm">
          <i class="fa fa-file-excel"></i> Exportar XLSX
        </a>
        <a href="{{ route('reportes.index') }}" class="btn btn-secondary btn-sm">
          <i class="fa fa-arrow-left"></i> Centro de reportes
        </a>
      </div>
    </div>

    <div class="card-body">
      <div id="loading" class="text-center my-4">
        <i class="fa fa-spinner fa-spin"></i> Cargando…
      </div>

      <div class="table-responsive">
        <table id="tblData" class="table table-striped table-bordered table-hover table-sm" style="display:none">
          <thead>
            <tr>
              <th>#</th>
              <th>Capturista</th>
              <th>ID</th>
              <th>Total</th>
              <th>Validados</th>
              <th>Descartados</th>
              <th>Otros</th>
              <th>% Validados</th>
              <th>Última captura</th>
            </tr>
          </thead>
          <tbody></tbody>
        </table>
        <div id="emptyState" class="alert alert-secondary my-3" style="display:none">
          Sin resultados con los filtros actuales.
        </div>
      </div>
    </div>
  </div>
</div>
@endsection

@section('js')
<script>
(function(){
  const dataUrl = @json(route('reportes.capturistas.data', request()->query()));
  const $tbl = document.getElementById('tblData');
  const $tbody = $tbl.querySelector('tbody');
  const $loading = document.getElementById('loading');
  const $empty = document.getElementById('emptyState');

  fetch(dataUrl, { headers: { 'X-Requested-With': 'XMLHttpRequest' }})
    .then(r => r.json())
    .then(json => {
      const rows = json && json.data ? json.data : [];
      if (!rows.length) { $loading.style.display='none'; $empty.style.display='block'; return; }
      let n = 0;
      const fmt = v => (v===null || v===undefined || v==='') ? '—' : v;
      const esc = (s) => String(s).replace(/[&<>"']/g, m=>({ '&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;' }[m]));
      $tbody.innerHTML = rows.map(r => {
        n++;
        return `<tr>
          <td>${n}</td>
          <td>${esc(fmt(r.capturista))}</td>
          <td>${esc(fmt(r.capturista_id))}</td>
          <td>${esc(fmt(r.total))}</td>
          <td>${esc(fmt(r.validado))}</td>
          <td>${esc(fmt(r.descartado))}</td>
          <td>${esc(fmt(r.otros))}</td>
          <td>${esc(fmt(r.porcentaje_validado ?? r.pct_validado))}</td>
          <td>${esc(fmt(r.ultima_captura))}</td>
        </tr>`;
      }).join('');
      $loading.style.display = 'none';
      $tbl.style.display = '';
    })
    .catch(err => { console.error(err); $loading.innerHTML = 'Error cargando datos.'; });
})();
</script>
@endsection
