@extends('layouts.app')
@section('title', $brigada->exists ? 'Editar brigada' : 'Nueva brigada')
@section('content')
<div class="container-xl py-3">
  <div class="mb-3">
    <a href="{{ route('brigadas.index') }}" class="text-decoration-none"><i class="fa-solid fa-arrow-left me-1"></i> Brigadas</a>
    <h1 class="h3 mt-2 mb-1">{{ $brigada->exists ? 'Editar brigada' : 'Nueva brigada' }}</h1>
    <p class="text-muted mb-0">Sólo aparecen las secciones electorales de Morelia.</p>
  </div>

  @if($errors->any())
    <div class="alert alert-danger"><strong>Revisa estos datos:</strong><ul class="mb-0 mt-1">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
  @endif

  <form method="POST" action="{{ $brigada->exists ? route('brigadas.update', $brigada) : route('brigadas.store') }}">
    @csrf
    @if($brigada->exists) @method('PUT') @endif
    <div class="card shadow-sm border-0 mb-3"><div class="card-body p-4">
      <h2 class="h5 mb-3">1. Datos de la brigada</h2>
      <div class="row g-3">
        <div class="col-lg-8">
          <label class="form-label" for="section">Sección de Morelia</label>
          <select class="form-select" name="seccion_id" id="section" required>
            <option value="">Selecciona una sección</option>
            @foreach($secciones as $s)
              <option value="{{ $s->id }}" data-district="{{ $s->distrito_local }}" {{ (string) old('seccion_id', $brigada->seccion_id) === (string) $s->id ? 'selected' : '' }}>
                DL {{ str_pad($s->distrito_local, 2, '0', STR_PAD_LEFT) }} · Sección {{ str_pad($s->seccion, 4, '0', STR_PAD_LEFT) }}
              </option>
            @endforeach
          </select>
        </div>
        <div class="col-lg-4"><label class="form-label" for="district">Distrito local</label><input class="form-control" id="district" readonly></div>
        <div class="col-md-6"><label class="form-label" for="responsable">Responsable</label><input class="form-control" id="responsable" name="responsable" value="{{ old('responsable', $brigada->responsable) }}" required maxlength="150"></div>
        <div class="col-md-6"><label class="form-label" for="telefono">Teléfono</label><input class="form-control" id="telefono" name="telefono" value="{{ old('telefono', $brigada->telefono) }}" maxlength="30" inputmode="tel"></div>
        <div class="col-12"><label class="form-label" for="direccion">Dirección o punto de reunión</label><input class="form-control" id="direccion" name="direccion" value="{{ old('direccion', $brigada->direccion) }}" maxlength="500"></div>
        <div class="col-md-4"><label class="form-label" for="activo">Estado</label><select class="form-select" name="activo" id="activo"><option value="1" {{ (string) old('activo', (int) $brigada->activo) === '1' ? 'selected' : '' }}>Activa: acepta asistencias</option><option value="0" {{ (string) old('activo', (int) $brigada->activo) === '0' ? 'selected' : '' }}>Cerrada</option></select></div>
        <div class="col-md-8"><label class="form-label" for="observaciones">Indicaciones</label><textarea class="form-control" id="observaciones" name="observaciones" rows="2" maxlength="2000">{{ old('observaciones', $brigada->observaciones) }}</textarea></div>
      </div>
    </div></div>

    <div class="card shadow-sm border-0 mb-3"><div class="card-body p-4">
      <h2 class="h5 mb-1">2. Ubicación</h2><p class="text-muted">Marca el punto en el mapa o usa la ubicación del teléfono.</p>
      <div class="row g-3 mb-3">
        <div class="col-12"><label class="form-label" for="ubicacion_google">Enlace de Google Maps (opcional)</label><input class="form-control" id="ubicacion_google" name="ubicacion_google" value="{{ old('ubicacion_google', $brigada->ubicacion_google) }}"></div>
        <div class="col-md-6"><label class="form-label" for="lat">Latitud</label><input class="form-control" id="lat" name="lat" type="number" step="any" value="{{ old('lat', $brigada->lat) }}" required></div>
        <div class="col-md-6"><label class="form-label" for="lng">Longitud</label><input class="form-control" id="lng" name="lng" type="number" step="any" value="{{ old('lng', $brigada->lng) }}" required></div>
      </div>
      <button type="button" id="gps" class="btn btn-outline-primary mb-2"><i class="fa-solid fa-location-crosshairs me-1"></i> Usar mi ubicación</button>
      <div id="captureMap" style="height:360px" class="border rounded"></div>
    </div></div>
    <div class="d-flex justify-content-end gap-2"><a href="{{ route('brigadas.index') }}" class="btn btn-outline-secondary">Cancelar</a><button class="btn btn-granate px-4"><i class="fa-solid fa-floppy-disk me-1"></i> Guardar brigada</button></div>
  </form>
</div>
@endsection
@push('css')<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">@endpush
@push('scripts')
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
  const section = document.getElementById('section');
  const district = document.getElementById('district');
  const updateDistrict = () => district.value = section.selectedOptions[0]?.dataset.district || '';
  section.addEventListener('change', updateDistrict); updateDistrict();
  const lat = document.getElementById('lat'), lng = document.getElementById('lng'), url = document.getElementById('ubicacion_google');
  if (typeof L === 'undefined') return;
  const map = L.map('captureMap').setView([19.7026, -101.1922], 12);
  L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {maxZoom:19, attribution:'&copy; OpenStreetMap'}).addTo(map);
  let marker;
  function point(a, b, zoom) { if (!Number.isFinite(a) || !Number.isFinite(b)) return; lat.value=a.toFixed(7); lng.value=b.toFixed(7); url.value='https://www.google.com/maps?q='+lat.value+','+lng.value; marker ? marker.setLatLng([a,b]) : marker=L.marker([a,b]).addTo(map); if (zoom) map.setView([a,b],17); }
  if (lat.value !== '' && lng.value !== '') point(Number(lat.value), Number(lng.value), true);
  map.on('click', e => point(e.latlng.lat, e.latlng.lng, false));
  url.addEventListener('change', () => { let value; try { value=decodeURIComponent(url.value); } catch(e) { return; } const match=value.match(/!3d(-?\d+(?:\.\d+)?)!4d(-?\d+(?:\.\d+)?)/)||value.match(/[?&](?:q|query|ll)=(-?\d+(?:\.\d+)?),\s*(-?\d+(?:\.\d+)?)/)||value.match(/@(-?\d+(?:\.\d+)?),\s*(-?\d+(?:\.\d+)?)/); if(match) point(Number(match[1]),Number(match[2]),true); });
  document.getElementById('gps').addEventListener('click', () => navigator.geolocation ? navigator.geolocation.getCurrentPosition(p => point(p.coords.latitude,p.coords.longitude,true), () => alert('No se pudo obtener tu ubicación.')) : alert('Ubicación no disponible.'));
});
</script>
@endpush
