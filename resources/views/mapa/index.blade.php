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
  .leaflet-interactive { cursor: pointer; }
  .info-legend { background:#fff; padding:8px 10px; border-radius:6px; box-shadow:0 1px 5px rgba(0,0,0,.3); font:14px/1.2 system-ui, sans-serif; }
  .info-legend i { width:14px; height:14px; display:inline-block; margin-right:6px; vertical-align:middle; }
</style>
@endpush

@push('scripts')
<script src="https://unpkg.com/leaflet/dist/leaflet.js"></script>
<script>
  var _conteoBlade = @json($conteo);
  var _conteoNombreBlade = @json($conteoPorNombre);
  var conteoPorCVE = _conteoBlade || {};
  var conteoPorNombre = _conteoNombreBlade || {};

  function normalize(s){
    return (s || '').toString()
      .normalize('NFD').replace(/[\u0300-\u036f]/g,'')
      .replace(/[^A-Z0-9 ]/gi,'').trim().toUpperCase();
  }

  var breaks = [0,5,20,50,100,250,500,1000];
  function getColor(v){
    return v >= breaks[7] ? '#5B0013' :
           v >= breaks[6] ? '#7A001A' :
           v >= breaks[5] ? '#990021' :
           v >= breaks[4] ? '#B80027' :
           v >= breaks[3] ? '#D61A3C' :
           v >= breaks[2] ? '#E34B6A' :
           v >= breaks[1] ? '#F08AA7' : '#F8CBD7';
  }
  function styleFeature(total){
    return { color:'#111', weight:1, fillColor:getColor(total), fillOpacity:0.75, interactive:true };
  }

  // 1) Mapa + pane dedicado por si algo tapa eventos
  var map = L.map('map', { zoomControl:true, doubleClickZoom:false, scrollWheelZoom:true, dragging:true, preferCanvas:false });
  map.createPane('municipiosPane');
  map.getPane('municipiosPane').style.zIndex = 650;
  map.getPane('municipiosPane').style.pointerEvents = 'auto';

  // 2) CSS de refuerzo (pointer-events)
  (function(){
    var s = document.createElement('style');
    s.innerHTML = `
      #map { position: relative; }
      .leaflet-overlay-pane svg path, .leaflet-interactive { pointer-events: auto !important; }
      .municipio { cursor:pointer; }
    `;
    document.head.appendChild(s);
  })();

  // 3) Leyenda simple
  var legend = L.control({position:'bottomright'});
  legend.onAdd = function(){
    var div = L.DomUtil.create('div','info-legend');
    div.innerHTML = '<strong>Afiliados</strong><br>';
    for (var i=0;i<breaks.length;i++){
      var from = breaks[i], to = breaks[i+1];
      var label = to ? (from + '-' + (to-1)) : (from + '+');
      var sampleVal = to ? (to-1) : (from+1);
      div.innerHTML += '<div><i style="background:' + getColor(sampleVal) + '"></i>' + label + '</div>';
    }
    return div;
  };
  legend.addTo(map);

  // 4) Logs de depuración: ¿llegan clicks al mapa?
  map.on('click', function(e){ console.log('[MAP CLICK]', e.latlng); });

  // 5) Cargar GeoJSON y asegurar interactividad
  fetch("{{ asset('geo/michoacan.json') }}")
    .then(function(r){ return r.json(); })
    .then(function(geo){
      var featuresCount = 0;
      var capa = L.geoJSON(geo, {
        pane: 'municipiosPane',
        style: function(f){
          var p = f.properties || {};
          var cve = (p.CVEGEO || (String(p.CVE_ENT||'') + String(p.CVE_MUN||''))).toString();
          var nomN = normalize(p.NOMGEO || '');
          var totCVE = (conteoPorCVE.hasOwnProperty(cve) ? conteoPorCVE[cve] : null);
          var tot = (totCVE !== null && totCVE !== undefined) ? totCVE :
                    (conteoPorNombre.hasOwnProperty(nomN) ? conteoPorNombre[nomN] : 0);
          return styleFeature(tot);
        },
        onEachFeature: function(feature, layer){
          featuresCount++;
          var p = feature.properties || {};
          var cve = (p.CVEGEO || (String(p.CVE_ENT||'') + String(p.CVE_MUN||''))).toString();
          var nombre = p.NOMGEO || 'Desconocido';
          var nomN = normalize(nombre);
          var totCVE = (conteoPorCVE.hasOwnProperty(cve) ? conteoPorCVE[cve] : null);
          var tot = (totCVE !== null && totCVE !== undefined) ? totCVE :
                    (conteoPorNombre.hasOwnProperty(nomN) ? conteoPorNombre[nomN] : 0);

          layer.options.className = 'municipio';
          layer.options.bubblingMouseEvents = true;


          layer.on('click', function(e){
            console.log('[POLY CLICK]', nombre, cve, e.latlng);
            var html = ''
              + '<div style="min-width:220px">'
              +   '<h5 style="margin:0 0 6px 0">' + nombre + '</h5>'
              +   '<div><strong>Afiliados:</strong> ' + tot + '</div>'
              +   '<div><small>CVEGEO: ' + cve + '</small></div>'
              + '</div>';
            this.bindPopup(html, { closeButton:true }).openPopup();
            this.setStyle({ weight:3, fillOpacity:1.0 }); this.bringToFront();
          });
          layer.on('mouseover', function(){
            this.setStyle({ weight:2, fillOpacity:0.9 }); this.bringToFront();
          });
          layer.on('mouseout', function(){
            this.setStyle(styleFeature(tot));
          });
        }
      }).addTo(map);

      console.log('[GEOJSON CARGADO] features=', featuresCount);
      var bounds = capa.getBounds();
      map.fitBounds(bounds);
      map.setMaxBounds(bounds.pad(0.05));
    })
    .catch(function(err){ console.error('Error cargando GeoJSON:', err); });
</script>

@endpush
