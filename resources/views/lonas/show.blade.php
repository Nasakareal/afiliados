@extends('layouts.app')

@section('title', 'Lona #'.$lona->id)

@section('content')
<div class="container-xl">
  <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
    <div>
      <h1 class="h3 mb-1">Lona #{{ $lona->id }}</h1>
      <p class="text-muted mb-0">Sección {{ $lona->seccion }}</p>
    </div>
    <div class="d-flex gap-2">
      <a href="{{ route('lonas.index') }}" class="btn btn-outline-secondary">
        <i class="fa-solid fa-arrow-left me-1"></i> Listado
      </a>
      @can('lonas.editar')
      <a href="{{ route('lonas.edit', $lona) }}" class="btn btn-outline-success">
        <i class="fa-solid fa-pen me-1"></i> Editar
      </a>
      @endcan
    </div>
  </div>

  <div class="row g-3">
    <div class="col-lg-7">
      <div class="card shadow-sm border-0">
        <img src="{{ route('lonas.foto', $lona) }}" alt="Fotografía de la lona"
             class="card-img-top" style="max-height:650px;object-fit:contain;background:#202124;">
      </div>
    </div>
    <div class="col-lg-5">
      <div class="card shadow-sm border-0 mb-3">
        <div class="card-body">
          <dl class="row mb-0">
            <dt class="col-sm-4">Sección</dt>
            <dd class="col-sm-8">{{ $lona->seccion }}</dd>
            <dt class="col-sm-4">Dirección</dt>
            <dd class="col-sm-8">{{ $lona->direccion }}</dd>
            <dt class="col-sm-4">Responsable</dt>
            <dd class="col-sm-8">{{ $lona->responsable }}</dd>
            <dt class="col-sm-4">Capturó</dt>
            <dd class="col-sm-8">{{ optional($lona->capturista)->name ?? '—' }}</dd>
            <dt class="col-sm-4">Fecha</dt>
            <dd class="col-sm-8">{{ optional($lona->created_at)->format('d/m/Y H:i') }}</dd>
            <dt class="col-sm-4">Foto final</dt>
            <dd class="col-sm-8">
              {{ $lona->foto_bytes_final ? number_format($lona->foto_bytes_final / 1024, 0).' KB' : '—' }}
            </dd>
          </dl>
          <a href="{{ $lona->ubicacion_google ?: 'https://www.google.com/maps?q='.$lona->lat.','.$lona->lng }}"
             target="_blank" rel="noopener" class="btn btn-primary w-100 mt-3">
            <i class="fa-solid fa-map-location-dot me-1"></i> Abrir en Google Maps
          </a>
        </div>
      </div>
      <div id="detailMap" class="rounded border shadow-sm" style="height:330px;"></div>
    </div>
  </div>
</div>
@endsection

@push('css')
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
@endpush

@push('scripts')
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
  const point = [@json((float) $lona->lat), @json((float) $lona->lng)];
  const map = L.map('detailMap').setView(point, 17);
  L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
    maxZoom: 20,
    attribution: '&copy; OpenStreetMap'
  }).addTo(map);
  L.marker(point).addTo(map).bindPopup(@json('Lona · Sección '.$lona->seccion)).openPopup();
});
</script>
@endpush
