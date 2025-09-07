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

  /* Etiquetas dentro de los municipios (base a 14px y luego escalamos por JS) */
  .leaflet-div-icon.mun-label { background: transparent; border: none; }
  .mun-label-text{
    font: 14px/1.1 system-ui, -apple-system, Segoe UI, Roboto, sans-serif;
    font-weight: 700;
    color: #111;
    text-shadow:
      0 0 3px #fff,
      0 0 6px #fff,
      0 1px 0 #fff;
    white-space: nowrap;
    pointer-events: none;                     /* no bloquea clics */
    transform: translate(-50%, -50%) scale(1);/* centrado + escala dinámica */
    transform-origin: 50% 50%;
  }
</style>
@endpush


@push('scripts')
<script src="https://unpkg.com/leaflet/dist/leaflet.js"></script>
<script src="https://unpkg.com/@turf/turf@6.5.0/turf.min.js"></script>

<script>
  // Datos
  const conteoPorCVE    = @json($conteo) || {};
  const conteoPorNombre = @json($conteoPorNombre) || {};
  const statsCVE        = @json($statsCVE) || {};
  const statsNombre     = @json($statsNombre) || {};

  function normalize(s){
    return (s || '').toString()
      .normalize('NFD').replace(/[\u0300-\u036f]/g,'')
      .replace(/[^A-Z0-9 ]/gi,'').trim().toUpperCase();
  }

  // Colores basados en TOTAL (todos)
  const breaks = [0,5,20,50,100,250,500,1000];
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

  const map = L.map('map', { zoomControl:true, doubleClickZoom:false, scrollWheelZoom:true, dragging:true });
  map.createPane('municipiosPane');  map.getPane('municipiosPane').style.zIndex = 650;
  map.createPane('overlaysPane');    map.getPane('overlaysPane').style.zIndex   = 700;

  const layersControl = L.control.layers(null, {}, { collapsed:false }).addTo(map);

  // Capa para poder ocultar/mostrar etiquetas
  const labelsGroup = L.layerGroup().addTo(map);
  layersControl.addOverlay(labelsGroup, 'Nombres de municipios');

  // Para que sigan existiendo eventos encima de overlays
  (function(){
    const s = document.createElement('style');
    s.innerHTML = `
      #map { position: relative; }
      .leaflet-overlay-pane svg path, .leaflet-interactive { pointer-events: auto !important; }
      .municipio { cursor:pointer; }
    `;
    document.head.appendChild(s);
  })();

  const legend = L.control({position:'bottomright'});
  legend.onAdd = function(){
    const div = L.DomUtil.create('div','info-legend');
    div.innerHTML = '<strong>Total de registros</strong><br>';
    for (let i=0;i<breaks.length;i++){
      const from = breaks[i], to = breaks[i+1];
      const label = to ? (from + '-' + (to-1)) : (from + '+');
      const sampleVal = to ? (to-1) : (from+1);
      div.innerHTML += '<div><i style="background:' + getColor(sampleVal) + '"></i>' + label + '</div>';
    }
    div.innerHTML += '<div style="margin-top:6px"><small>* El color usa el total (todos los estatus)</small></div>';
    return div;
  };
  legend.addTo(map);

  function pickStats(p){
    const cve = (p.CVEGEO || (String(p.CVE_ENT||'') + String(p.CVE_MUN||''))).toString();
    if (statsCVE && statsCVE[cve]) return statsCVE[cve];
    const nomN = normalize(p.NOMGEO || '');
    if (statsNombre && statsNombre[nomN]) return statsNombre[nomN];
    return { total:0, afiliados:0, no_afiliados:0, pendientes:0, convencidos:0 };
  }

  // ======= Ajuste dinámico de tamaño/visibilidad de etiquetas =======
  const MIN_ZOOM_FOR_LABELS = 8;   // no mostrar arriba (más alejado) de este zoom
  const MIN_SCALE_VISIBLE   = 0.55; // si queda más chico que esto, se oculta

  const munLabels = []; // { layer, label, textEl }

  function fitOne(item){
    const el = item.label.getElement();
    if (!el) return;
    const textEl = item.textEl || el.querySelector('.mun-label-text');
    if (!textEl) return;

    // Mostrar/ocultar por nivel de zoom
    if (map.getZoom() < MIN_ZOOM_FOR_LABELS){
      textEl.style.display = 'none';
      return;
    } else {
      textEl.style.display = 'block';
    }

    // Dimensiones del polígono en pixeles
    const b  = item.layer.getBounds();
    const nw = map.latLngToLayerPoint(b.getNorthWest());
    const se = map.latLngToLayerPoint(b.getSouthEast());
    const polyW = Math.abs(se.x - nw.x);
    const polyH = Math.abs(se.y - nw.y);

    const maxW = polyW * 0.80; // deja margen
    const maxH = polyH * 0.50;

    // Mide tamaño "natural" del texto (base 14px)
    textEl.style.transform = 'translate(-50%, -50%) scale(1)';
    textEl.style.fontSize  = ''; // usa CSS base (14px)
    const rect = textEl.getBoundingClientRect();
    const w0 = rect.width || 1, h0 = rect.height || 1;

    // Escala para que quepa en ancho y alto del polígono
    let scale = Math.min(maxW / w0, maxH / h0, 1);
    if (!isFinite(scale)) scale = 1;

    // Si la escala necesaria es muy chica, ocultamos
    if (scale < MIN_SCALE_VISIBLE){
      textEl.style.display = 'none';
      return;
    }

    textEl.style.display   = 'block';
    textEl.style.transform = 'translate(-50%, -50%) scale(' + scale.toFixed(3) + ')';
  }

  function fitAll(){ munLabels.forEach(fitOne); }
  map.on('zoomend viewreset', fitAll);

  // ======= Cargar municipios y crear etiquetas =======
  fetch("{{ asset('geo/michoacan.json') }}")
    .then(r => r.json())
    .then(function(geo){
      const capaMunicipios = L.geoJSON(geo, {
        pane: 'municipiosPane',
        style: function(f){ return styleFeature(pickStats(f.properties||{}).total); },
        onEachFeature: function(feature, layer){
          const p  = feature.properties || {};
          const st = pickStats(p);
          const cve    = (p.CVEGEO || (String(p.CVE_ENT||'') + String(p.CVE_MUN||''))).toString();
          const nombre = p.NOMGEO || 'Desconocido';

          layer.options.className = 'municipio';
          layer.on('click', function(){
            const html = `
              <div style="min-width:240px">
                <h5 style="margin:0 0 6px 0">${nombre}</h5>
                <div><strong>Afiliados (sí):</strong> ${st.afiliados}</div>
                <div><strong>No afiliados (no):</strong> ${st.no_afiliados}</div>
                <div><strong>Convencidos (sí + no):</strong> ${st.convencidos}</div>
                <div style="margin-top:6px"><small>Total (todos): ${st.total}${st.pendientes ? (' — Pendientes: ' + st.pendientes) : ''}</small></div>
                <div><small>CVEGEO: ${cve}</small></div>
              </div>`;
            this.bindPopup(html, { closeButton:true }).openPopup();
            this.setStyle({ weight:3, fillOpacity:1.0 }); this.bringToFront();
          });
          layer.on('mouseover', function(){ this.setStyle({ weight:2, fillOpacity:0.9 }); this.bringToFront(); });
          layer.on('mouseout',  function(){ this.setStyle(styleFeature(st.total)); });

          // Punto interno para la etiqueta (center of mass o centro del bounds)
          let latlng;
          try {
            const com = turf.centerOfMass(feature);
            const c   = com?.geometry?.coordinates;
            latlng = (c && c.length>=2) ? [c[1], c[0]] : layer.getBounds().getCenter();
          } catch(_) {
            latlng = layer.getBounds().getCenter();
          }

          const label = L.marker(latlng, {
            pane: 'overlaysPane',
            interactive: false,
            keyboard: false,
            bubblingMouseEvents: false,
            icon: L.divIcon({
              className: 'mun-label',
              html: `<span class="mun-label-text">${nombre}</span>`
            })
          }).addTo(labelsGroup);

          // Guardamos referencia para reajuste dinámico
          const item = { layer, label };
          item.label.on('add', () => {
            item.textEl = item.label.getElement().querySelector('.mun-label-text');
            fitOne(item);
          });
          munLabels.push(item);
        }
      }).addTo(map);

      layersControl.addOverlay(capaMunicipios, 'Municipios (total)');

      const bounds = capaMunicipios.getBounds();
      map.fitBounds(bounds);
      map.setMaxBounds(bounds.pad(0.05));

      // Ajuste inicial (por si el add aún no corrió)
      setTimeout(fitAll, 0);
    })
    .catch(err => console.error('Error cargando GeoJSON:', err));

  // Capas externas opcionales (se mantienen igual)
  @foreach ($layers as $l)
    fetch("{{ $l['url'] }}")
      .then(r => r.json())
      .then(geo => {
        const layer = (function(geo) {
          let tipo = null;
          if (geo?.features?.length) {
            const f0 = geo.features.find(f => f && f.geometry);
            tipo = f0?.geometry?.type || null;
          }
          const opts = { pane: 'overlaysPane' };
          if (/Point/i.test(tipo))      opts.pointToLayer = (f, latlng) => L.circleMarker(latlng, { radius: 3, weight: 1 });
          else if (/LineString/i.test(tipo)) opts.style = { weight: 2 };
          else if (/Polygon/i.test(tipo))    opts.style = { weight: 1, color:'#333', fill:false };
          return L.geoJSON(geo, opts);
        })(geo);
        layersControl.addOverlay(layer, "{{ $l['name'] }}");
      })
      .catch(err => console.error('Error capa {{ $l['name'] }}:', err));
  @endforeach
</script>
@endpush

