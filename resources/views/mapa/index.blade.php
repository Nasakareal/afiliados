@extends('layouts.app')

@section('title','Mapa de Afiliados')

@section('content')
<div class="container-fluid h-100">
  <div class="row h-100">
    <div class="col-12 h-100">
      <div id="map" style="height: calc(100vh - 100px);"></div>
    </div>
  </div>
</div>
@endsection

@push('css')
<link rel="stylesheet" href="https://unpkg.com/leaflet/dist/leaflet.css"/>
<style>
  html, body { margin:0; padding:0; height:100%; overflow:hidden; }
  #map { width:100%; }
</style>
@endpush

@push('scripts')
<script src="https://unpkg.com/leaflet/dist/leaflet.js"></script>
<script>
  var map = L.map('map', {
    zoomControl: true,
    doubleClickZoom: false,
    scrollWheelZoom: true,
    dragging: true
  });

  fetch("{{ asset('geo/michoacan.json') }}")
    .then(r => r.json())
    .then(data => {
      const conteo = @json($conteo);

      const capa = L.geoJSON(data, {
        style: {
          color: '#111',
          weight: 1,
          fillColor: '#4CAF50',
          fillOpacity: 0.4
        },
        onEachFeature: function (feature, layer) {
          let nombre = feature.properties.NOMGEO || "Desconocido";
          let total = conteo[nombre] ?? 0;

          // evento click -> abre popup
          layer.on('click', function() {
            layer.bindPopup(
              `<h5>${nombre}</h5>
               <p>Afiliados registrados: <strong>${total}</strong></p>`
            ).openPopup();
          });

          // highlight hover
          layer.on('mouseover', function() {
            this.setStyle({ fillOpacity: 0.7, weight: 2 });
            this.bringToFront();
          });
          layer.on('mouseout', function() {
            this.setStyle({ fillOpacity: 0.4, weight: 1 });
          });
        }
      }).addTo(map);

      // centrar solo en Michoacán
      map.fitBounds(capa.getBounds());
      map.setMaxBounds(capa.getBounds().pad(0.05));
    })
    .catch(err => console.error("Error cargando GeoJSON:", err));
</script>
@endpush
