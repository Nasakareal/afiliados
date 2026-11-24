@extends('layouts.app')
@section('title', 'Brigadas')
@section('content')
<div class="container-xl py-3">
  <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
    <div><h1 class="h3 mb-1">Brigadas de Morelia</h1><p class="text-muted mb-0">Consulta una brigada y sube tu foto para confirmar que asististe.</p></div>
    <div class="d-flex gap-2"><a class="btn btn-outline-success" href="{{ route('brigadas.export', request()->only('q','seccion','distrito')) }}"><i class="fa-solid fa-file-excel me-1"></i> Excel</a>@can('brigadas.crear')<a class="btn btn-granate" href="{{ route('brigadas.create') }}"><i class="fa-solid fa-plus me-1"></i> Nueva brigada</a>@endcan</div>
  </div>
  @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
  @if(session('error'))<div class="alert alert-danger">{{ session('error') }}</div>@endif
  <div class="card shadow-sm border-0 mb-3"><div class="card-body"><form class="row g-2" method="GET"><div class="col-lg-4"><label class="visually-hidden" for="q">Buscar</label><input class="form-control" id="q" name="q" placeholder="Responsable o dirección" value="{{ request('q') }}"></div><div class="col-lg-3"><input class="form-control" name="seccion" placeholder="Número de sección" value="{{ request('seccion') }}"></div><div class="col-lg-3"><select class="form-select" name="distrito"><option value="">Todos los distritos</option>@foreach([10,11,16,17] as $district)<option value="{{ $district }}" {{ (string) request('distrito') === (string) $district ? 'selected' : '' }}>Distrito local {{ $district }}</option>@endforeach</select></div><div class="col-lg-2 d-grid"><button class="btn btn-outline-primary"><i class="fa-solid fa-magnifying-glass me-1"></i> Buscar</button></div></form></div></div>
  <div class="row g-3">
    @forelse($brigadas as $brigada)
      @php($yaParticipo = $brigada->participantes->isNotEmpty())
      <div class="col-lg-6"><div class="card shadow-sm border-0 h-100"><div class="card-body p-4">
        <div class="d-flex justify-content-between gap-2"><div><span class="badge text-bg-light mb-2">DL {{ $brigada->seccion->distrito_local }} · Sección {{ $brigada->seccion->seccion }}</span><h2 class="h5 mb-1">{{ $brigada->responsable }}</h2></div><span class="badge {{ $brigada->activo ? 'text-bg-success' : 'text-bg-secondary' }} align-self-start">{{ $brigada->activo ? 'Activa' : 'Cerrada' }}</span></div>
        <p class="text-muted mb-2"><i class="fa-solid fa-location-dot me-1"></i> {{ $brigada->direccion ?: 'Ubicación en el mapa' }}</p>
        <div class="d-flex flex-wrap gap-3 small mb-3"><span><strong>{{ $brigada->participantes_count }}</strong> asistencias</span>@if($yaParticipo)<span class="text-success"><i class="fa-solid fa-circle-check me-1"></i> Ya registraste la tuya</span>@endif</div>
        <div class="d-flex flex-wrap gap-2"><a class="btn {{ $brigada->activo && !$yaParticipo ? 'btn-granate' : 'btn-outline-primary' }}" href="{{ route('brigadas.show', $brigada) }}">{{ $brigada->activo && !$yaParticipo ? 'Registrar mi asistencia' : 'Ver brigada' }}</a><a class="btn btn-outline-secondary" target="_blank" rel="noopener" href="{{ $brigada->google_maps_url }}">Cómo llegar</a>@can('brigadas.editar')<a class="btn btn-outline-secondary" href="{{ route('brigadas.edit', $brigada) }}">Editar</a>@endcan</div>
      </div></div></div>
    @empty
      <div class="col-12"><div class="card border-0 shadow-sm"><div class="card-body text-center py-5"><i class="fa-solid fa-people-group fa-3x text-muted mb-3"></i><h2 class="h5">No hay brigadas con esos filtros</h2><p class="text-muted mb-0">Prueba otra búsqueda o registra la primera brigada.</p></div></div></div>
    @endforelse
  </div>
  <div class="mt-3">{{ $brigadas->links() }}</div>
</div>
@endsection
