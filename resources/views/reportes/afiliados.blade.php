@extends('layouts.app')

@section('title','Reporte: Afiliados')

@section('content_header')
  <h1 class="text-center w-100">Reporte · Afiliados</h1>
@endsection

@section('content')
<div class="container-xl">
  <div class="card card-outline card-primary">
    <div class="card-header d-flex flex-wrap gap-2 justify-content-between align-items-center">
      <h3 class="card-title mb-0">Afiliados (según filtros)</h3>

      <div class="d-flex flex-wrap gap-2 align-items-center">

        {{-- ===== Botones de FACETAS ===== --}}
        <div class="btn-group">
          <button class="btn btn-outline-primary btn-sm" data-facet="secciones">Secciones</button>
          <button class="btn btn-outline-primary btn-sm" data-facet="municipios">Municipios</button>
          <button class="btn btn-outline-primary btn-sm" data-facet="cve_mun">CVE</button>
          <button class="btn btn-outline-primary btn-sm" data-facet="distritos_locales">D. Local</button>
          <button class="btn btn-outline-primary btn-sm" data-facet="distritos_federales">D. Fed</button>
          <button class="btn btn-outline-primary btn-sm" data-facet="estatus">Estatus</button>
          <button class="btn btn-outline-primary btn-sm" data-facet="capturistas">Capturistas</button>
          <button class="btn btn-outline-primary btn-sm" data-facet="sexo">Sexo</button>
        </div>

        <button id="btnClearAll" class="btn btn-outline-danger btn-sm">
          <i class="fa fa-ban"></i> Quitar todos los filtros
        </button>

        {{-- Columnas --}}
        <div class="dropdown">
          <button class="btn btn-outline-secondary btn-sm dropdown-toggle" type="button" data-bs-toggle="dropdown">
            <i class="fa fa-columns"></i> Columnas
          </button>
          <div class="dropdown-menu p-2" style="min-width: 230px; max-height: 50vh; overflow:auto;">
            @php
              $cols = [
                'capturista' => 'Capturista',
                'nombre' => 'Nombre',
                'sexo' => 'Sexo',
                'telefono' => 'Teléfono',
                'email' => 'Email',
                'municipio' => 'Municipio',
                'cve_mun' => 'CVE',
                'seccion' => 'Sección',
                'distrito_federal' => 'D. Fed',
                'distrito_local' => 'D. Local',
                'estatus' => 'Estatus',
                'fecha_convencimiento' => 'Convencimiento',
                'created_at' => 'Creado',
              ];
            @endphp
            @foreach($cols as $key => $label)
              <label class="dropdown-item">
                <input type="checkbox" class="me-2 col-toggle" data-col="{{ $key }}" checked>
                {{ $label }}
              </label>
            @endforeach
          </div>
        </div>

        <a id="btnExport" href="#" class="btn btn-success btn-sm">
          <i class="fa fa-file-excel"></i> Exportar XLSX
        </a>

        <a href="{{ route('reportes.index') }}" class="btn btn-secondary btn-sm">
          <i class="fa fa-arrow-left"></i> Centro
        </a>
      </div>
    </div>

    <div class="card-body">
      {{-- chips de filtros activos --}}
      <div id="chips" class="mb-2 d-flex flex-wrap gap-2"></div>

      <div id="loading" class="text-center my-4">
        <i class="fa fa-spinner fa-spin"></i> Cargando…
      </div>

      <div class="table-responsive">
        <table id="tblData" class="table table-striped table-bordered table-hover table-sm" style="display:none">
          <thead>
            <tr>
              <th>#</th>
              <th data-col="capturista">Capturista</th>
              <th data-col="nombre">Nombre</th>
              <th data-col="sexo">Sexo</th>
              <th data-col="telefono">Teléfono</th>
              <th data-col="email">Email</th>
              <th data-col="municipio">Municipio</th>
              <th data-col="cve_mun">CVE</th>
              <th data-col="seccion">Sección</th>
              <th data-col="distrito_federal">D. Fed</th>
              <th data-col="distrito_local">D. Local</th>
              <th data-col="estatus">Estatus</th>
              <th data-col="fecha_convencimiento">Convencimiento</th>
              <th data-col="created_at">Creado</th>
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

{{-- ===== Modal reutilizable para FACETAS ===== --}}
<div class="modal fade" id="facetModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-scrollable">
    <div class="modal-content">
      <div class="modal-header">
        <h5 id="facetTitle" class="modal-title">Seleccionar</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
      </div>
      <div class="modal-body">
        <div class="d-flex gap-2 align-items-center mb-2">
          <input id="facetSearch" class="form-control form-control-sm" placeholder="Buscar en la lista…">
          <div class="btn-group btn-group-sm">
            <button id="facetIncAll" class="btn btn-outline-success" type="button">Incluir todos</button>
            <button id="facetExcAll" class="btn btn-outline-warning" type="button">Excluir todos</button>
            <button id="facetClear"  class="btn btn-outline-secondary" type="button">Deseleccionar todo</button>
          </div>
        </div>

        <div class="table-responsive" style="max-height:55vh;">
          <table class="table table-sm table-hover align-middle">
            <thead>
              <tr>
                <th style="width:60%;">Valor</th>
                <th class="text-end" style="width:10%;">Total</th>
                <th class="text-center" style="width:15%;">Incluir</th>
                <th class="text-center" style="width:15%;">Excluir</th>
              </tr>
            </thead>
            <tbody id="facetTbody"></tbody>
          </table>
        </div>
      </div>
      <div class="modal-footer">
        <small class="text-muted me-auto">Tip: “Incluir” y “Excluir” son mutuamente excluyentes por fila.</small>
        <button class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
        <button id="facetApply" class="btn btn-primary">Aplicar</button>
      </div>
    </div>
  </div>
</div>
@endsection

@section('js')
<script>
(function(){
  const baseData    = @json(route('reportes.afiliados.data'));          // sin query
  const baseExport  = @json(route('reportes.afiliados.export.xlsx'));    // sin query
  const baseFacets  = @json(route('reportes.afiliados.facets'));         // sin query
  let   qs          = new URLSearchParams(@json(request()->getQueryString() ?? ''));

  const FACET_MAP = {
    secciones:           { label:'Secciones',           inc:'secciones',            exc:'excluir_secciones' },
    municipios:          { label:'Municipios',          inc:'municipios',           exc:'excluir_municipios' },
    cve_mun:             { label:'CVE MUN',             inc:'cve_mun',              exc:'excluir_cve_mun' },
    distritos_locales:   { label:'Distritos Locales',   inc:'distritos_locales',    exc:'excluir_distritos_locales' },
    distritos_federales: { label:'Distritos Federales', inc:'distritos_federales',  exc:'excluir_distritos_federales' },
    estatus:             { label:'Estatus',             inc:'estatus',              exc:'excluir_estatus' },
    capturistas:         { label:'Capturistas',         inc:'capturistas',          exc:'excluir_capturistas' },
    sexo:                { label:'Sexo',                inc:'sexo',                 exc:'excluir_sexo' },
  };

  // ===== DOM =====
  const $tbl     = document.getElementById('tblData');
  const $tbody   = $tbl.querySelector('tbody');
  const $loading = document.getElementById('loading');
  const $empty   = document.getElementById('emptyState');
  const $export  = document.getElementById('btnExport');
  const $chips   = document.getElementById('chips');
  const $btnClearAll = document.getElementById('btnClearAll');
  const toggles  = [...document.querySelectorAll('.col-toggle')];

  const $facetModal = document.getElementById('facetModal');
  const facetModal  = new bootstrap.Modal($facetModal);
  const $facetTitle = document.getElementById('facetTitle');
  const $facetSearch= document.getElementById('facetSearch');
  const $facetTbody = document.getElementById('facetTbody');
  const $facetApply = document.getElementById('facetApply');
  const $facetClear = document.getElementById('facetClear');
  const $facetIncAll= document.getElementById('facetIncAll');
  const $facetExcAll= document.getElementById('facetExcAll');
  let   currentFacetKey = null;
  let   currentFacetRows = []; // cache de items del modal

  // ===== helpers =====
  const fmt = v => (v===null || v===undefined || v==='') ? '—' : v;
  const esc = s => String(s).replace(/[&<>"']/g, m=>({ '&':'&amp;','<':'&gt;','>':'&gt;','"':'&quot;',"'":'&#39;' }[m]));
  function visibleCols(){ return toggles.filter(t=>t.checked).map(t=>t.dataset.col); }
  function setColVisible(key, visible){
    const th = $tbl.querySelector('thead th[data-col="'+key+'"]');
    const tds = $tbl.querySelectorAll('tbody td[data-col="'+key+'"]');
    const d = visible ? '' : 'none';
    if (th) th.style.display = d;
    tds.forEach(td=> td.style.display = d);
  }
  function buildExportUrl(){
    const cols = visibleCols();
    const p = new URLSearchParams(qs.toString());
    p.delete('columns');
    if (cols.length) p.append('columns', cols.join(','));
    return baseExport + (p.toString() ? ('?'+p.toString()) : '');
  }
  function parseListParam(key){
    const v = qs.get(key);
    if (!v) return [];
    return v.split(',').map(s=>s.trim()).filter(Boolean);
  }
  function setListParam(key, arr){
    if (!key) return;
    if (!arr || !arr.length) qs.delete(key);
    else qs.set(key, arr.join(','));
  }

  function renderChips(){
    $chips.innerHTML = '';
    Object.entries(FACET_MAP).forEach(([k,meta])=>{
      const inc = parseListParam(meta.inc);
      const exc = parseListParam(meta.exc);
      if (!inc.length && !exc.length) return;

      const mk = (arr, cls) => arr.map(v=>{
        const span = document.createElement('span');
        span.className = 'badge '+cls+' me-1';
        span.textContent = v;
        span.style.cursor = 'pointer';
        span.title = 'Quitar';
        span.addEventListener('click', ()=>{
          // quitar ese valor del filtro correspondiente
          let a = parseListParam(cls.includes('bg-success') ? meta.inc : meta.exc);
          a = a.filter(x=>x!==v);
          setListParam(cls.includes('bg-success') ? meta.inc : meta.exc, a);
          loadData();
        });
        return span;
      });

      const group = document.createElement('div');
      group.className = 'd-flex align-items-center gap-2';
      const title = document.createElement('strong');
      title.textContent = meta.label+':';
      group.appendChild(title);
      mk(inc,'bg-success').forEach(el=>group.appendChild(el));
      mk(exc,'bg-warning text-dark').forEach(el=>group.appendChild(el));
      $chips.appendChild(group);
    });
  }

  // ===== columnas (persistencia local) =====
  const LS_KEY = 'rep_afiliados_cols';
  const preset = (function(){ try { return JSON.parse(localStorage.getItem(LS_KEY)||'[]'); } catch(e){ return []; }})();
  if (preset.length){ toggles.forEach(t=> t.checked = preset.includes(t.dataset.col)); }
  toggles.forEach(t=>{
    t.addEventListener('change', ()=>{
      setColVisible(t.dataset.col, t.checked);
      localStorage.setItem(LS_KEY, JSON.stringify(visibleCols()));
      $export.href = buildExportUrl();
    });
  });

  // ===== abrir FACET modal =====
  document.querySelectorAll('[data-facet]').forEach(btn=>{
    btn.addEventListener('click', async ()=>{
      currentFacetKey = btn.getAttribute('data-facet');
      const meta = FACET_MAP[currentFacetKey];
      $facetTitle.textContent = 'Filtrar · ' + meta.label;
      $facetSearch.value = '';

      // Trae facetas frescas (respetando filtros actuales en otros campos)
      const url = baseFacets + (qs.toString() ? ('?'+qs.toString()) : '');
      const json = await fetch(url, { headers:{'X-Requested-With':'XMLHttpRequest'}}).then(r=>r.json());
      const rows = json[currentFacetKey] || [];
      currentFacetRows = rows;
      renderFacetRows(rows);
      facetModal.show();
    });
  });

  function renderFacetRows(rows){
    const html = rows.map((r,idx)=>{
      const idI = `fi_${idx}`, idE = `fe_${idx}`;
      const label = esc(r.label ?? r.value);
      const val   = esc(r.value);
      return `<tr data-value="${val}">
        <td><span class="fw-semibold">${label}</span></td>
        <td class="text-end">${r.total ?? 0}</td>
        <td class="text-center">
          <input class="form-check-input facet-inc" type="checkbox" id="${idI}" ${r.selected?'checked':''}>
        </td>
        <td class="text-center">
          <input class="form-check-input facet-exc" type="checkbox" id="${idE}" ${r.excluded?'checked':''}>
        </td>
      </tr>`;
    }).join('');
    $facetTbody.innerHTML = html;

    // mutual-exclusion
    $facetTbody.querySelectorAll('.facet-inc').forEach((inc, i)=>{
      inc.addEventListener('change', e=>{
        const row = inc.closest('tr');
        const exc = row.querySelector('.facet-exc');
        if (inc.checked) exc.checked = false;
      });
    });
    $facetTbody.querySelectorAll('.facet-exc').forEach((exc, i)=>{
      exc.addEventListener('change', e=>{
        const row = exc.closest('tr');
        const inc = row.querySelector('.facet-inc');
        if (exc.checked) inc.checked = false;
      });
    });
  }

  // buscar dentro del modal
  $facetSearch.addEventListener('input', ()=>{
    const q = $facetSearch.value.trim().toLowerCase();
    const rows = currentFacetRows.filter(r=>{
      const label = String(r.label ?? r.value).toLowerCase();
      return !q || label.includes(q);
    });
    renderFacetRows(rows);
  });

  // seleccionar / excluir / limpiar todos
  $facetIncAll.addEventListener('click', ()=>{
    $facetTbody.querySelectorAll('.facet-inc').forEach(i=>i.checked=true);
    $facetTbody.querySelectorAll('.facet-exc').forEach(e=>e.checked=false);
  });
  $facetExcAll.addEventListener('click', ()=>{
    $facetTbody.querySelectorAll('.facet-inc').forEach(i=>i.checked=false);
    $facetTbody.querySelectorAll('.facet-exc').forEach(e=>e.checked=true);
  });
  $facetClear.addEventListener('click', ()=>{
    $facetTbody.querySelectorAll('.facet-inc, .facet-exc').forEach(x=>x.checked=false);
  });

  // aplicar del modal
  $facetApply.addEventListener('click', ()=>{
    if (!currentFacetKey) return;
    const meta = FACET_MAP[currentFacetKey];
    const inc = [], exc = [];
    $facetTbody.querySelectorAll('tr').forEach(tr=>{
      const val = tr.getAttribute('data-value');
      const i = tr.querySelector('.facet-inc').checked;
      const e = tr.querySelector('.facet-exc').checked;
      if (i) inc.push(val);
      if (e) exc.push(val);
    });
    setListParam(meta.inc, inc);
    setListParam(meta.exc, exc);
    facetModal.hide();
    loadData();
  });

  // quitar todos los filtros
  $btnClearAll.addEventListener('click', ()=>{
    Object.values(FACET_MAP).forEach(meta=>{
      qs.delete(meta.inc);
      qs.delete(meta.exc);
    });
    loadData();
  });

  // ===== carga datos =====
  function loadData(){
    const url = baseData + (qs.toString() ? ('?'+qs.toString()) : '');
    $loading.style.display = '';
    $empty.style.display = 'none';
    $tbl.style.display = 'none';
    $tbody.innerHTML = '';

    fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' }})
      .then(r => r.json())
      .then(json => {
        const rows = json && json.data ? json.data : [];
        renderChips();

        if (!rows.length) {
          $loading.style.display = 'none';
          $empty.style.display = 'block';
          $export.href = buildExportUrl();
          return;
        }

        let n = 0;
        const html = rows.map(r=>{
          n++;
          const nombre = [r.nombre, r.apellido_paterno, r.apellido_materno].filter(Boolean).join(' ');
          return `<tr>
            <td>${n}</td>
            <td data-col="capturista">${esc(fmt(r.capturista))}</td>
            <td data-col="nombre">${esc(fmt(nombre))}</td>
            <td data-col="sexo">${esc(fmt(r.sexo))}</td>
            <td data-col="telefono">${esc(fmt(r.telefono))}</td>
            <td data-col="email">${esc(fmt(r.email))}</td>
            <td data-col="municipio">${esc(fmt(r.municipio))}</td>
            <td data-col="cve_mun">${esc(fmt(r.cve_mun))}</td>
            <td data-col="seccion">${esc(fmt(r.seccion))}</td>
            <td data-col="distrito_federal">${esc(fmt(r.distrito_federal))}</td>
            <td data-col="distrito_local">${esc(fmt(r.distrito_local))}</td>
            <td data-col="estatus">${esc(fmt(r.estatus))}</td>
            <td data-col="fecha_convencimiento">${esc(fmt(r.fecha_convencimiento))}</td>
            <td data-col="created_at">${esc(fmt(r.created_at))}</td>
          </tr>`;
        }).join('');

        $tbody.innerHTML = html;
        $loading.style.display = 'none';
        $tbl.style.display = '';

        // visibilidad de columnas + export
        toggles.forEach(t => setColVisible(t.dataset.col, t.checked));
        $export.href = buildExportUrl();
      })
      .catch(err=>{
        console.error(err);
        $loading.innerHTML = 'Error cargando datos.';
        $export.href = buildExportUrl();
      });
  }

  // primera carga
  loadData();
})();
</script>
@endsection
