@extends('layouts.app')

@section('title', 'Mapa de brigadas')

@section('content')
<div class="container-fluid px-0">
  <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 px-3 mb-3">
    <div>
      <h1 class="h3 mb-1">Mapa de brigadas</h1>
      <p class="text-muted mb-0">Las secciones resaltadas tienen brigadas registradas. Selecciona un punto para consultar sus datos.</p>
    </div>
    <div class="d-flex gap-2">
      <a href="{{ route('brigadas.index') }}" class="btn btn-outline-secondary">
        <i class="fa-solid fa-list me-1"></i> Listado
      </a>
      @can('brigadas.crear')
      <a href="{{ route('brigadas.create') }}" class="btn btn-granate">
        <i class="fa-solid fa-plus me-1"></i> Registrar
      </a>
      @endcan
    </div>
  </div>
  <div id="brigadasMap" style="height:calc(100vh - 165px);min-height:520px;"></div>
</div>
@endsection

@push('css')
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
<link rel="stylesheet" href="https://unpkg.com/leaflet.markercluster@1.5.3/dist/MarkerCluster.css">
<link rel="stylesheet" href="https://unpkg.com/leaflet.markercluster@1.5.3/dist/MarkerCluster.Default.css">
<style>
  .brigada-popup{min-width:260px}
  .brigada-popup .status-dot{display:inline-block;width:.65rem;height:.65rem;border-radius:50%;margin-right:.35rem}
  .brigada-popup .status-active{background:#198754}
  .brigada-popup .status-closed{background:#6c757d}
  .section-map-legend{background:rgba(255,255,255,.94);padding:8px 10px;border-radius:8px;box-shadow:0 1px 5px rgba(0,0,0,.3);font-size:.82rem;font-weight:600}
  .section-map-legend i{display:inline-block;width:18px;height:12px;margin-right:6px;border:2px solid #075aa8;background:rgba(13,110,253,.28);vertical-align:-2px}
</style>
@endpush

@push('scripts')
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script src="https://unpkg.com/leaflet.markercluster@1.5.3/dist/leaflet.markercluster.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
  const map = L.map('brigadasMap').setView([19.7026, -101.1922], 12);
  L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
    maxZoom: 20,
    attribution: '&copy; OpenStreetMap'
  }).addTo(map);

  const markers = L.markerClusterGroup({showCoverageOnHover: false, maxClusterRadius: 45});
  const escapeHtml = value => {
    const node = document.createElement('div');
    node.textContent = value == null ? '' : String(value);
    return node.innerHTML;
  };

  Promise.all([
    fetch(@json(route('brigadas.map.data')), {
      headers: {'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest'}
    }).then(response => {
      if (!response.ok) throw new Error('No se pudo cargar la información de las brigadas.');
      return response.json();
    }),
    fetch(@json(asset('maps/electoral/morelia-secciones.geojson'))).then(response => {
      if (!response.ok) throw new Error('No se pudo cargar la cartografía de secciones.');
      return response.json();
    })
  ])
    .then(([brigadas, seccionesGeoJson]) => {
      const brigadasPorSeccion = brigadas.reduce((totales, brigada) => {
        const seccion = String(Number(brigada.seccion));
        totales[seccion] = (totales[seccion] || 0) + 1;
        return totales;
      }, {});
      const seccionesConBrigadas = seccionesGeoJson.features.filter(feature =>
        Boolean(brigadasPorSeccion[String(Number(feature.properties?.SECCION || 0))])
      );
      let sectionsLayer;
      sectionsLayer = L.geoJSON({type: 'FeatureCollection', features: seccionesConBrigadas}, {
        style: {
          color: '#075aa8',
          weight: 3,
          opacity: .95,
          fillColor: '#0d6efd',
          fillOpacity: .25
        },
        onEachFeature(feature, layer) {
          const seccion = String(Number(feature.properties.SECCION));
          const total = brigadasPorSeccion[seccion] || 0;
          layer.bindTooltip(`<strong>Sección ${escapeHtml(seccion)}</strong><br>${total} ${total === 1 ? 'brigada registrada' : 'brigadas registradas'}`, {sticky: true});
          layer.on('mouseover', function () {
            this.setStyle({weight: 5, fillOpacity: .4});
          });
          layer.on('mouseout', function () {
            sectionsLayer.resetStyle(this);
          });
        }
      }).addTo(map);

      const legend = L.control({position: 'bottomright'});
      legend.onAdd = function () {
        const box = L.DomUtil.create('div', 'section-map-legend');
        box.innerHTML = '<i></i> Sección con brigadas';
        return box;
      };
      legend.addTo(map);

      brigadas.forEach(brigada => {
        const estado = brigada.activo ? 'Activa' : 'Cerrada';
        const statusClass = brigada.activo ? 'status-active' : 'status-closed';
        const direccion = brigada.direccion || 'Ubicación registrada en el mapa';
        const telefono = brigada.telefono
          ? `<div class="small mb-1"><i class="fa-solid fa-phone me-1"></i>${escapeHtml(brigada.telefono)}</div>`
          : '';
        const popup = `
          <div class="brigada-popup">
            <div class="small text-muted mb-1">Distrito local ${escapeHtml(brigada.distrito_local)} · Sección ${escapeHtml(brigada.seccion)}</div>
            <div class="fw-bold mb-1">${escapeHtml(brigada.responsable)}</div>
            <div class="small mb-1"><i class="fa-solid fa-location-dot me-1"></i>${escapeHtml(direccion)}</div>
            ${telefono}
            <div class="small mb-2"><span class="status-dot ${statusClass}"></span>${estado} · ${escapeHtml(brigada.participantes_count)} asistencias</div>
            <div class="d-flex gap-2">
              <a href="${escapeHtml(brigada.detalle_url)}" class="btn btn-sm btn-primary">Ver brigada</a>
              <a href="${escapeHtml(brigada.google_url)}" target="_blank" rel="noopener" class="btn btn-sm btn-outline-secondary">Cómo llegar</a>
            </div>
          </div>`;
        markers.addLayer(L.marker([brigada.lat, brigada.lng]).bindPopup(popup, {maxWidth: 310}));
      });

      map.addLayer(markers);
      if (brigadas.length) {
        map.fitBounds(markers.getBounds().pad(0.12), {maxZoom: 16});
      }
    })
    .catch(error => {
      const errorControl = L.control({position: 'topright'});
      errorControl.onAdd = function () {
        const box = L.DomUtil.create('div', 'alert alert-danger shadow-sm');
        box.textContent = error.message;
        return box;
      };
      errorControl.addTo(map);
    });
});
</script>
@endpush
