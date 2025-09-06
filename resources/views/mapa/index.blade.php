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

  var map = L.map('map', { zoomControl:true, doubleClickZoom:false, scrollWheelZoom:true, dragging:true, preferCanvas:false });
  map.createPane('municipiosPane');
  map.getPane('municipiosPane').style.zIndex = 650;
  map.getPane('municipiosPane').style.pointerEvents = 'auto';
  map.createPane('overlaysPane');
  map.getPane('overlaysPane').style.zIndex = 700;

  const layersControl = L.control.layers(null, {}, { collapsed:false }).addTo(map);

  (function(){
    var s = document.createElement('style');
    s.innerHTML = `
      #map { position: relative; }
      .leaflet-overlay-pane svg path, .leaflet-interactive { pointer-events: auto !important; }
      .municipio { cursor:pointer; }
    `;
    document.head.appendChild(s);
  })();

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

  map.on('click', e => console.log('[MAP CLICK]', e.latlng));

  let capaMunicipios = null;
  fetch("{{ asset('geo/michoacan.json') }}")
    .then(r => r.json())
    .then(function(geo){
      var featuresCount = 0;
      capaMunicipios = L.geoJSON(geo, {
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
            var html = ''
              + '<div style="min-width:220px">'
              +   '<h5 style="margin:0 0 6px 0">' + nombre + '</h5>'
              +   '<div><strong>Afiliados:</strong> ' + tot + '</div>'
              +   '<div><small>CVEGEO: ' + cve + '</small></div>'
              + '</div>';
            this.bindPopup(html, { closeButton:true }).openPopup();
            this.setStyle({ weight:3, fillOpacity:1.0 }); this.bringToFront();
          });
          layer.on('mouseover', function(){ this.setStyle({ weight:2, fillOpacity:0.9 }); this.bringToFront(); });
          layer.on('mouseout',  function(){ this.setStyle(styleFeature(tot)); });
        }
      }).addTo(map);

      layersControl.addOverlay(capaMunicipios, 'Municipios (afiliados)');

      var bounds = capaMunicipios.getBounds();
      map.fitBounds(bounds);
      map.setMaxBounds(bounds.pad(0.05));
    })
    .catch(err => console.error('Error cargando GeoJSON:', err));

  function crearOverlay(geo) {
    let tipo = null;
    if (geo && geo.features && geo.features.length) {
      const f0 = geo.features.find(f => f && f.geometry);
      tipo = f0 && f0.geometry && f0.geometry.type || null;
    }
    const opts = { pane: 'overlaysPane' };

    if (tipo && /Point/i.test(tipo)) {
      opts.pointToLayer = (f, latlng) => L.circleMarker(latlng, { radius: 3, weight: 1 });
    } else if (tipo && /LineString/i.test(tipo)) {
      opts.style = { weight: 2 };
    } else if (tipo && /Polygon/i.test(tipo)) {
      opts.style = { weight: 1, color:'#333', fill:false };
    }
    return L.geoJSON(geo, opts);
  }

  @foreach ($layers as $l)
    fetch("{{ $l['url'] }}")
      .then(r => r.json())
      .then(geo => {
        const layer = crearOverlay(geo);
        layersControl.addOverlay(layer, "{{ $l['name'] }}");
      })
      .catch(err => console.error('Error capa {{ $l['name'] }}:', err));
  @endforeach
</script>
@endpush
