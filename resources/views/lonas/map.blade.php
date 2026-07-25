@extends('layouts.app')

@section('title', 'Mapa de lonas')

@section('content')
<div class="container-fluid px-0">
  <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 px-3 mb-3">
    <div>
      <h1 class="h3 mb-1">Mapa de lonas</h1>
      <p class="text-muted mb-0">Selecciona un punto para ver la foto y los datos.</p>
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

  fetch(@json(route('lonas.map.data')), {
    headers: {'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest'}
  })
    .then(response => {
      if (!response.ok) throw new Error('No se pudo cargar la información.');
      return response.json();
    })
    .then(lonas => {
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
