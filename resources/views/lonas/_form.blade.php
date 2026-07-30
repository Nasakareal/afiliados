@php
  $editing = isset($lona);
  $currentLat = old('lat', $lona->lat ?? '');
  $currentLng = old('lng', $lona->lng ?? '');
@endphp

@if($errors->any())
  <div class="alert alert-danger">
    <div class="fw-semibold mb-1">Revisa los datos de la lona:</div>
    <ul class="mb-0">
      @foreach($errors->all() as $error)
        <li>{{ $error }}</li>
      @endforeach
    </ul>
  </div>
@endif

<div class="row g-3">
  <div class="col-md-3">
    <label for="seccion" class="form-label">Sección <span class="text-danger">*</span></label>
    <input type="text" inputmode="numeric" name="seccion" id="seccion"
           value="{{ old('seccion', $lona->seccion ?? '') }}"
           class="form-control @error('seccion') is-invalid @enderror"
           maxlength="10" required>
    @error('seccion')<div class="invalid-feedback">{{ $message }}</div>@enderror
  </div>

  <div class="col-md-9">
    <label for="responsable" class="form-label">Responsable <span class="text-danger">*</span></label>
    <input type="text" name="responsable" id="responsable"
           value="{{ old('responsable', $lona->responsable ?? auth()->user()->name) }}"
           class="form-control @error('responsable') is-invalid @enderror"
           maxlength="150" required>
    @error('responsable')<div class="invalid-feedback">{{ $message }}</div>@enderror
  </div>

  <div class="col-12">
    <label for="direccion" class="form-label">Dirección <span class="text-danger">*</span></label>
    <textarea name="direccion" id="direccion" rows="2"
              class="form-control @error('direccion') is-invalid @enderror"
              maxlength="500" required
              placeholder="Calle, número, colonia, localidad y referencias">{{ old('direccion', $lona->direccion ?? '') }}</textarea>
    @error('direccion')<div class="invalid-feedback">{{ $message }}</div>@enderror
  </div>

  <div class="col-12">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-2">
      <label class="form-label mb-0">Ubicación <span class="text-danger">*</span></label>
      <button type="button" class="btn btn-outline-primary btn-sm" id="useLocation">
        <i class="fa-solid fa-location-crosshairs me-1"></i> Usar mi ubicación
      </button>
    </div>
    <div id="captureMap" class="rounded border" style="height: 360px;"></div>
    <div class="form-text">Toca el punto exacto en el mapa o usa la ubicación del teléfono.</div>
    @error('lat')<div class="text-danger small">{{ $message }}</div>@enderror
    @error('lng')<div class="text-danger small">{{ $message }}</div>@enderror
    <input type="hidden" name="lat" id="lat" value="{{ $currentLat }}">
    <input type="hidden" name="lng" id="lng" value="{{ $currentLng }}">
  </div>

  <div class="col-lg-8">
    <label for="ubicacion_google" class="form-label">Enlace de Google Maps</label>
    <input type="text" inputmode="url" name="ubicacion_google" id="ubicacion_google"
           value="{{ old('ubicacion_google', $lona->ubicacion_google ?? '') }}"
           class="form-control @error('ubicacion_google') is-invalid @enderror"
           maxlength="2000" placeholder="Se genera automáticamente al seleccionar el punto">
    <div class="form-text">Puedes pegar el enlace completo, incluso si comienza directamente con google.com; el punto se ubicará automáticamente.</div>
    @error('ubicacion_google')<div class="invalid-feedback">{{ $message }}</div>@enderror
  </div>

  <div class="col-lg-4">
    <label class="form-label">Coordenadas</label>
    <div class="input-group">
      <input type="text" id="latDisplay" class="form-control" value="{{ $currentLat }}" readonly aria-label="Latitud">
      <input type="text" id="lngDisplay" class="form-control" value="{{ $currentLng }}" readonly aria-label="Longitud">
    </div>
  </div>

  <div class="col-12">
    <label for="foto" class="form-label">
      Foto de la lona puesta @if(!$editing)<span class="text-danger">*</span>@endif
    </label>
    <input type="file" name="foto" id="foto"
           class="form-control @error('foto') is-invalid @enderror"
           accept=".jpg,.jpeg,.png,.webp,.gif,.bmp,.heic,.heif,image/jpeg,image/png,image/webp,image/heic,image/heif"
           @if(!$editing) required @endif>
    @error('foto')<div class="invalid-feedback">{{ $message }}</div>@enderror
    <div class="form-text">
      Admite JPG, PNG, WebP, HEIC y HEIF. La imagen se convierte y comprime automáticamente antes de subir.
    </div>
    <div id="photoStatus" class="small mt-2" role="status"></div>
    <div class="mt-3">
      <img id="photoPreview"
           src="{{ $editing ? route('lonas.foto', $lona) : '' }}"
           alt="Vista previa de la lona"
           class="img-thumbnail {{ $editing ? '' : 'd-none' }}"
           style="max-height: 320px; max-width: 100%; object-fit: contain;">
    </div>
  </div>
</div>

@push('css')
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
@endpush

@push('scripts')
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script src="https://cdn.jsdelivr.net/npm/heic2any@0.0.4/dist/heic2any.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/browser-image-compression@2.0.2/dist/browser-image-compression.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
  const form = document.getElementById('lonaForm');
  const latInput = document.getElementById('lat');
  const lngInput = document.getElementById('lng');
  const latDisplay = document.getElementById('latDisplay');
  const lngDisplay = document.getElementById('lngDisplay');
  const googleInput = document.getElementById('ubicacion_google');
  const locationButton = document.getElementById('useLocation');
  const photoInput = document.getElementById('foto');
  const photoPreview = document.getElementById('photoPreview');
  const photoStatus = document.getElementById('photoStatus');
  const submitButton = form.querySelector('button[type="submit"]');
  const initialLat = Number(latInput.value);
  const initialLng = Number(lngInput.value);
  const hasInitialPoint = Number.isFinite(initialLat) && Number.isFinite(initialLng) &&
    latInput.value !== '' && lngInput.value !== '';
  const defaultPoint = [19.7026, -101.1922];

  const map = L.map('captureMap').setView(
    hasInitialPoint ? [initialLat, initialLng] : defaultPoint,
    hasInitialPoint ? 17 : 12
  );
  L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
    maxZoom: 20,
    attribution: '&copy; OpenStreetMap'
  }).addTo(map);

  let marker = null;
  function setPoint(lat, lng, zoom, updateGoogleLink = true) {
    const latitude = Number(lat);
    const longitude = Number(lng);
    if (!Number.isFinite(latitude) || !Number.isFinite(longitude)) return;

    if (!marker) {
      marker = L.marker([latitude, longitude], {draggable: true}).addTo(map);
      marker.on('dragend', function () {
        const point = marker.getLatLng();
        setPoint(point.lat, point.lng, false);
      });
    } else {
      marker.setLatLng([latitude, longitude]);
    }

    const latValue = latitude.toFixed(7);
    const lngValue = longitude.toFixed(7);
    latInput.value = latValue;
    lngInput.value = lngValue;
    latDisplay.value = latValue;
    lngDisplay.value = lngValue;
    if (updateGoogleLink) {
      googleInput.value = 'https://www.google.com/maps?q=' + latValue + ',' + lngValue;
    }
    if (zoom) map.setView([latitude, longitude], 18);
  }

  if (hasInitialPoint) setPoint(initialLat, initialLng, false, !googleInput.value);
  map.on('click', event => setPoint(event.latlng.lat, event.latlng.lng, false));

  locationButton.addEventListener('click', function () {
    if (!navigator.geolocation) {
      alert('Este dispositivo no permite obtener la ubicación.');
      return;
    }

    locationButton.disabled = true;
    locationButton.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Ubicando…';
    navigator.geolocation.getCurrentPosition(
      position => {
        setPoint(position.coords.latitude, position.coords.longitude, true);
        locationButton.disabled = false;
        locationButton.innerHTML = '<i class="fa-solid fa-location-crosshairs me-1"></i> Usar mi ubicación';
      },
      () => {
        alert('No se pudo obtener la ubicación. Revisa el permiso del navegador o toca el punto en el mapa.');
        locationButton.disabled = false;
        locationButton.innerHTML = '<i class="fa-solid fa-location-crosshairs me-1"></i> Usar mi ubicación';
      },
      {enableHighAccuracy: true, timeout: 15000, maximumAge: 0}
    );
  });

  function coordinatesFromGoogleUrl(value) {
    let decoded = String(value || '');
    try {
      decoded = decodeURIComponent(decoded);
    } catch (error) {
      // Keep the original text when a copied URL contains a stray % character.
    }
    const patterns = [
      /!3d(-?\d{1,2}(?:\.\d+)?)!4d(-?\d{1,3}(?:\.\d+)?)/,
      /[?&](?:q|query|ll)=(-?\d{1,2}(?:\.\d+)?),\s*(-?\d{1,3}(?:\.\d+)?)/,
      /\/place\/(-?\d{1,2}(?:\.\d+)?),\s*(-?\d{1,3}(?:\.\d+)?)(?:\/|$)/,
      /@(-?\d{1,2}(?:\.\d+)?),\s*(-?\d{1,3}(?:\.\d+)?)(?:,|\/|$)/
    ];
    for (const pattern of patterns) {
      const match = decoded.match(pattern);
      if (match) return [Number(match[1]), Number(match[2])];
    }
    return null;
  }

  function normalizeGoogleUrl(value) {
    const url = String(value || '').trim();
    if (/^(?:www\.)?(?:google\.[a-z.]+|maps\.google\.[a-z.]+|maps\.app\.goo\.gl|goo\.gl)(?:\/|$)/i.test(url)) {
      return 'https://' + url;
    }
    if (url.startsWith('//')) return 'https:' + url;
    return url;
  }

  function applyGoogleUrl() {
    googleInput.value = normalizeGoogleUrl(googleInput.value);
    const point = coordinatesFromGoogleUrl(googleInput.value);
    if (point) setPoint(point[0], point[1], true, false);
  }

  googleInput.addEventListener('change', applyGoogleUrl);

  let previewUrl = null;
  photoInput.addEventListener('change', function () {
    const file = photoInput.files && photoInput.files[0];
    if (!file) return;
    if (previewUrl) URL.revokeObjectURL(previewUrl);
    previewUrl = URL.createObjectURL(file);
    photoPreview.src = previewUrl;
    photoPreview.classList.remove('d-none');
    photoStatus.className = 'small mt-2 text-muted';
    photoStatus.textContent = 'Seleccionada: ' + file.name + ' (' + (file.size / 1024 / 1024).toFixed(1) + ' MB)';
  });

  function isHeic(file) {
    return /\.(heic|heif)$/i.test(file.name) || /image\/hei[cf]/i.test(file.type);
  }

  async function preparePhoto(file) {
    let workingFile = file;

    if (isHeic(workingFile)) {
      if (typeof heic2any !== 'function') {
        throw new Error('No se cargó el convertidor HEIC. Revisa tu conexión e inténtalo nuevamente.');
      }
      photoStatus.textContent = 'Convirtiendo HEIC/HEIF a JPEG…';
      let converted = await heic2any({blob: workingFile, toType: 'image/jpeg', quality: 0.86});
      if (Array.isArray(converted)) converted = converted[0];
      workingFile = new File(
        [converted],
        workingFile.name.replace(/\.(heic|heif)$/i, '') + '.jpg',
        {type: 'image/jpeg', lastModified: Date.now()}
      );
    }

    if (typeof imageCompression !== 'function') {
      if (workingFile.size > 9 * 1024 * 1024) {
        throw new Error('No se cargó el compresor y la foto supera 9 MB. Revisa tu conexión e inténtalo de nuevo.');
      }
      return workingFile;
    }

    photoStatus.textContent = 'Comprimiendo la fotografía…';
    return imageCompression(workingFile, {
      maxSizeMB: 1.8,
      maxWidthOrHeight: 1920,
      useWebWorker: true,
      fileType: 'image/jpeg',
      initialQuality: 0.82
    });
  }

  form.addEventListener('submit', async function (event) {
    if (form.dataset.photoReady === '1') return;
    applyGoogleUrl();
    if (!latInput.value || !lngInput.value) {
      event.preventDefault();
      alert('Selecciona la ubicación exacta de la lona en el mapa.');
      return;
    }

    const file = photoInput.files && photoInput.files[0];
    if (!file) {
      form.dataset.photoReady = '1';
      return;
    }

    event.preventDefault();
    submitButton.disabled = true;
    try {
      const processed = await preparePhoto(file);
      const transfer = new DataTransfer();
      transfer.items.add(new File(
        [processed],
        processed.name.replace(/\.[^.]+$/, '') + '.jpg',
        {type: 'image/jpeg', lastModified: Date.now()}
      ));
      photoInput.files = transfer.files;
      photoStatus.className = 'small mt-2 text-success';
      photoStatus.textContent = 'Foto lista: ' + (processed.size / 1024 / 1024).toFixed(1) + ' MB. Guardando…';
      form.dataset.photoReady = '1';
      form.submit();
    } catch (error) {
      submitButton.disabled = false;
      photoStatus.className = 'small mt-2 text-danger';
      photoStatus.textContent = error && error.message ? error.message : 'No se pudo procesar la fotografía.';
    }
  });
});
</script>
@endpush
