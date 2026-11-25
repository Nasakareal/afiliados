@extends('layouts.app')

@section('title', 'Mapa de lonas')

@section('content')
<div class="container-fluid px-0">
  <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 px-3 mb-3">
    <div>
      <h1 class="h3 mb-1">Mapa de lonas</h1>
      <p class="text-muted mb-0">Las secciones resaltadas tienen lonas registradas. Selecciona un punto para ver sus datos.</p>
    </div>
    <div class="d-flex gap-2">
      <a href="{{ route('lonas.index') }}" class="btn btn-outline-secondary">
        <i class="fa-solid fa-list me-1"></i> Listado
      </a>
      @can('lonas.crear')
      <a href="{{ route('lonas.create') }}" class="btn btn-granate">
        <i class="fa-solid fa-plus me-1"></i> Capturar
      </a>
      @endcan
    </div>
  </div>
  <div id="lonasMap" style="height:calc(100vh - 165px);min-height:520px;"></div>
</div>
@endsection

@push('css')
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
<link rel="stylesheet" href="https://unpkg.com/leaflet.markercluster@1.5.3/dist/MarkerCluster.css">
<link rel="stylesheet" href="https://unpkg.com/leaflet.markercluster@1.5.3/dist/MarkerCluster.Default.css">
<style>
  .lona-popup img{width:260px;max-width:100%;height:175px;object-fit:cover;border-radius:8px;margin-bottom:8px}
  .lona-popup{min-width:270px}
  .section-map-legend{background:rgba(255,255,255,.94);padding:8px 10px;border-radius:8px;box-shadow:0 1px 5px rgba(0,0,0,.3);font-size:.82rem;font-weight:600}
  .section-map-legend i{display:inline-block;width:18px;height:12px;margin-right:6px;border:2px solid #b00055;background:rgba(217,23,133,.3);vertical-align:-2px}
</style>
@endpush

@push('scripts')
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script src="https://unpkg.com/leaflet.markercluster@1.5.3/dist/leaflet.markercluster.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
  const map = L.map('lonasMap').setView([19.7026, -101.1922], 8);
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
    fetch(@json(route('lonas.map.data')), {
      headers: {'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest'}
    }).then(response => {
      if (!response.ok) throw new Error('No se pudo cargar la información de las lonas.');
      return response.json();
    }),
    fetch(@json(asset('maps/electoral/morelia-secciones.geojson'))).then(response => {
      if (!response.ok) throw new Error('No se pudo cargar la cartografía de secciones.');
      return response.json();
    })
  ])
    .then(([lonas, seccionesGeoJson]) => {
      const lonasPorSeccion = lonas.reduce((totales, lona) => {
        const seccion = String(Number(lona.seccion));
        totales[seccion] = (totales[seccion] || 0) + 1;
        return totales;
      }, {});
      const seccionesConLonas = seccionesGeoJson.features.filter(feature =>
        Boolean(lonasPorSeccion[String(Number(feature.properties?.SECCION || 0))])
      );
      let sectionsLayer;
      sectionsLayer = L.geoJSON({type: 'FeatureCollection', features: seccionesConLonas}, {
        style: {
          color: '#b00055',
          weight: 3,
          opacity: .95,
          fillColor: '#d91785',
          fillOpacity: .28
        },
        onEachFeature(feature, layer) {
          const seccion = String(Number(feature.properties.SECCION));
          const total = lonasPorSeccion[seccion] || 0;
          layer.bindTooltip(`<strong>Sección ${escapeHtml(seccion)}</strong><br>${total} ${total === 1 ? 'lona registrada' : 'lonas registradas'}`, {sticky: true});
          layer.on('mouseover', function () {
            this.setStyle({weight: 5, fillOpacity: .42});
          });
          layer.on('mouseout', function () {
            sectionsLayer.resetStyle(this);
          });
        }
      }).addTo(map);

      const legend = L.control({position: 'bottomright'});
      legend.onAdd = function () {
        const box = L.DomUtil.create('div', 'section-map-legend');
        box.innerHTML = '<i></i> Sección con lonas';
        return box;
      };
      legend.addTo(map);

      lonas.forEach(lona => {
        const popup = `
          <div class="lona-popup">
            <img src="${escapeHtml(lona.foto_url)}" alt="Foto de la lona" loading="lazy">
            <div class="fw-bold mb-1">Sección ${escapeHtml(lona.seccion)}</div>
            <div class="small mb-1">${escapeHtml(lona.direccion)}</div>
            <div class="small text-muted mb-2">Responsable: ${escapeHtml(lona.responsable)}</div>
            <div class="d-flex gap-2">
              <a href="${escapeHtml(lona.detalle_url)}" class="btn btn-sm btn-primary">Ver detalle</a>
              <a href="${escapeHtml(lona.google_url)}" target="_blank" rel="noopener" class="btn btn-sm btn-outline-secondary">Google Maps</a>
            </div>
          </div>`;
        markers.addLayer(L.marker([lona.lat, lona.lng]).bindPopup(popup, {maxWidth: 310}));
      });

      map.addLayer(markers);
      if (lonas.length) {
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
