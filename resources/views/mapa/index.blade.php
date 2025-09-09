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

  .leaflet-div-icon.mun-label { background: transparent; border: none; }
  .mun-label-text{
    font: 14px/1.1 system-ui, -apple-system, Segoe UI, Roboto, sans-serif;
    font-weight: 700; color: #111;
    text-shadow: 0 0 3px #fff, 0 0 6px #fff, 0 1px 0 #fff;
    white-space: nowrap; pointer-events: none;
    transform: translate(-50%, -50%) scale(1);
    transform-origin: 50% 50%;
  }

  .map-label { pointer-events: none; }
  .map-label-text{
    display:inline-block;
    font: 600 12px/1 system-ui, -apple-system, Segoe UI, Roboto, sans-serif;
    color:#fff; text-shadow:0 1px 2px rgba(0,0,0,.7);
    background: rgba(0,0,0,.25);
    padding:2px 6px; border-radius:4px; white-space:nowrap;
    transform: translate(-50%, -50%) scale(1);
    transform-origin: 50% 50%;
  }
  .label-dl { background: rgba(0, 86, 179, .35); }
  .label-df { background: rgba(179, 86, 0, .35); }
  .label-sec{ background: rgba(0, 128, 64, .35); }
</style>
@endpush

@push('scripts')
<script src="https://unpkg.com/leaflet/dist/leaflet.js"></script>
<script src="https://unpkg.com/@turf/turf@6.5.0/turf.min.js"></script>

<script>
  const conteoPorCVE    = @json($conteo) || {};
  const conteoPorNombre = @json($conteoPorNombre) || {};
  const statsCVE        = @json($statsCVE) || {};
  const statsNombre     = @json($statsNombre) || {};

  function normalize(s){
    return (s || '').toString().normalize('NFD').replace(/[\u0300-\u036f]/g,'').replace(/[^A-Z0-9 ]/gi,'').trim().toUpperCase();
  }

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
  map.createPane('labelsPane');      map.getPane('labelsPane').style.zIndex     = 800;
  map.getPane('labelsPane').style.pointerEvents = 'none';

  const layersControl = L.control.layers(null, {}, { collapsed:false }).addTo(map);
  const labelsGroup = L.layerGroup().addTo(map);
  layersControl.addOverlay(labelsGroup, 'Nombres de municipios');

  (function(){
    const s = document.createElement('style');
    s.innerHTML = '#map{position:relative}.leaflet-overlay-pane svg path,.leaflet-interactive{pointer-events:auto !important}.municipio{cursor:pointer}';
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

  const MIN_LABEL_SCALE = 0.2;
  const munLabels = [];

  function fitMunicipio(item){
    const el = item.label.getElement();
    if (!el) return;
    const textEl = item.textEl || el.querySelector('.mun-label-text');
    if (!textEl) return;
    const b  = item.layer.getBounds();
    const nw = map.latLngToLayerPoint(b.getNorthWest());
    const se = map.latLngToLayerPoint(b.getSouthEast());
    const polyW = Math.abs(se.x - nw.x), polyH = Math.abs(se.y - nw.y);
    const maxW = polyW * 0.80, maxH = polyH * 0.50;
    textEl.style.transform = 'translate(-50%, -50%) scale(1)';
    const rect = textEl.getBoundingClientRect();
    const w0 = rect.width || 1, h0 = rect.height || 1;
    let scale = Math.min(maxW / w0, maxH / h0, 1);
    if (!isFinite(scale)) scale = 1;
    scale = Math.max(scale, MIN_LABEL_SCALE);
    textEl.style.transform = 'translate(-50%, -50%) scale(' + scale.toFixed(3) + ')';
  }
  function fitMunicipios(){ munLabels.forEach(fitMunicipio); }
  map.on('zoomend viewreset', fitMunicipios);

  fetch("{{ asset('geo/michoacan.json') }}")
    .then(r => r.json())
    .then(function(geo){
      const capaMunicipios = L.geoJSON(geo, {
        pane: 'municipiosPane',
        style: f => styleFeature(pickStats(f.properties||{}).total),
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

          let latlng;
          try {
            const com = turf.centerOfMass(feature);
            const c   = com?.geometry?.coordinates;
            latlng = (c && c.length>=2) ? [c[1], c[0]] : layer.getBounds().getCenter();
          } catch(_) {
            latlng = layer.getBounds().getCenter();
          }

          const label = L.marker(latlng, {
            pane: 'labelsPane',
            interactive: false, keyboard: false, bubblingMouseEvents: false,
            icon: L.divIcon({ className: 'mun-label', html: `<span class="mun-label-text">${nombre}</span>` })
          }).addTo(labelsGroup);

          const item = { layer, label };
          item.label.on('add', () => {
            item.textEl = item.label.getElement().querySelector('.mun-label-text');
            fitMunicipio(item);
          });
          munLabels.push(item);
        }
      }).addTo(map);

      layersControl.addOverlay(capaMunicipios, 'Municipios (total)');

      const bounds = capaMunicipios.getBounds();
      map.fitBounds(bounds); map.setMaxBounds(bounds.pad(0.05));
      setTimeout(fitMunicipios, 0);
    })
    .catch(err => console.error('Error cargando GeoJSON:', err));

  function pickProp(props, keys){
    for (const k of keys) if (props && props[k] !== undefined && props[k] !== null && props[k] !== '') return props[k];
    return null;
  }
  function getLabelInfo(props){
    const sec = pickProp(props, ['SECCION','Seccion','seccion','SEC']);
    if (sec) return { text: `Sección ${sec}`, className: 'label-sec' };
    const df  = pickProp(props, ['DISTRITO_F','Distrito_F','DISTRITO_FEDERAL','DF']);
    if (df)  return { text: `DF ${df}`,  className: 'label-df'  };
    const dl  = pickProp(props, ['DISTRITO_L','Distrito_L','DISTRITO_LOCAL','DL']);
    if (dl)  return { text: `DL ${dl}`,  className: 'label-dl'  };
    return { text: 'Capa', className: '' };
  }
  function centerLatLng(feature, layer){
    try {
      const c = turf.centerOfMass(feature)?.geometry?.coordinates;
      if (c && c.length >= 2) return L.latLng(c[1], c[0]);
    } catch(e){}
    try { return layer.getBounds().getCenter(); } catch(e){}
    return null;
  }

  const overlayLabelItems = [];
  const MIN_LABEL_SCALE_OVER = 0.2;
  function fitOverlayOne(item){
    const root = item.marker.getElement();
    if (!root) return;
    const span = root.querySelector('.map-label-text');
    if (!span) return;
    const b  = item.layer.getBounds();
    const nw = map.latLngToLayerPoint(b.getNorthWest());
    const se = map.latLngToLayerPoint(b.getSouthEast());
    const polyW = Math.abs(se.x - nw.x), polyH = Math.abs(se.y - nw.y);
    const maxW = polyW * 0.80, maxH = polyH * 0.50;
    span.style.transform = 'translate(-50%, -50%) scale(1)';
    const rect = span.getBoundingClientRect();
    const w0 = rect.width || 1, h0 = rect.height || 1;
    let scale = Math.min(maxW / w0, maxH / h0, 1);
    if (!isFinite(scale)) scale = 1;
    scale = Math.max(scale, MIN_LABEL_SCALE_OVER);
    span.style.transform = 'translate(-50%, -50%) scale(' + scale.toFixed(3) + ')';
  }
  function fitOverlayAll(){ overlayLabelItems.forEach(fitOverlayOne); }
  map.on('zoomend viewreset', fitOverlayAll);

  function baseOverlayStyle(feature){
    const t = feature && feature.geometry && feature.geometry.type || '';
    if (/LineString/i.test(t)) return { weight: 2, color:'#333' };
    if (/Polygon/i.test(t))    return { weight: 1, color:'#333', fill:false };
    return {};
  }
  function overlayPopupHTML(props){
    const sec = pickProp(props, ['SECCION','Seccion','seccion','SEC']);
    const dl  = pickProp(props, ['DISTRITO_L','Distrito_L','DISTRITO_LOCAL','DL']);
    const df  = pickProp(props, ['DISTRITO_F','Distrito_F','DISTRITO_FEDERAL','DF']);
    const ent = pickProp(props, ['ENTIDAD','Entidad','CVE_ENT']);
    const title = sec ? `Sección ${sec}` : (dl ? `Distrito Local ${dl}` : (df ? `Distrito Federal ${df}` : 'Detalle'));
    const rows = [];
    if (sec) rows.push(`<div><strong>Sección:</strong> ${sec}</div>`);
    if (dl)  rows.push(`<div><strong>Distrito Local:</strong> ${dl}</div>`);
    if (df)  rows.push(`<div><strong>Distrito Federal:</strong> ${df}</div>`);
    if (ent) rows.push(`<div><small>Entidad: ${ent}</small></div>`);
    return `<div style="min-width:220px">
      <h5 style="margin:0 0 6px 0">${title}</h5>
      ${rows.join('')}
    </div>`;
  }

  @foreach ($layers as $l)
    fetch("{{ $l['url'] }}")
      .then(r => r.json())
      .then(geo => {
        const group = L.layerGroup();

        const geoLayer = L.geoJSON(geo, {
          pane: 'overlaysPane',
          style: baseOverlayStyle,
          onEachFeature: function(feature, layer){
            layer.on('click', function(e){
              const html = overlayPopupHTML(feature.properties || {});
              L.popup({ closeButton:true }).setLatLng(e.latlng).setContent(html).openOn(map);
              this.setStyle({ weight:3, color:'#000' }); this.bringToFront();
            });
            layer.on('mouseover', function(){ this.setStyle({ weight:2 }); this.bringToFront(); });
            layer.on('mouseout',  function(){ this.setStyle(baseOverlayStyle(feature)); });
          }
        }).addTo(group);

        L.geoJSON(geo, {
          onEachFeature: function(feature, lyr){
            const info = getLabelInfo(feature.properties || {});
            const latlng = centerLatLng(feature, lyr);
            if (!latlng) return;
            const marker = L.marker(latlng, {
              pane: 'labelsPane',
              icon: L.divIcon({
                className: 'map-label',
                html: `<span class="map-label-text ${info.className}">${info.text}</span>`,
                iconSize: null
              }),
              interactive: false
            }).addTo(group);

            const item = { layer: lyr, marker };
            marker.on('add', () => fitOverlayOne(item));
            overlayLabelItems.push(item);
          }
        });

        layersControl.addOverlay(group, "{{ $l['name'] }}");
        map.on('overlayadd', e => { if (e.layer === group) setTimeout(fitOverlayAll, 0); });
      })
      .catch(err => console.error('Error capa {{ $l['name'] }}:', err));
  @endforeach
</script>
@endpush
