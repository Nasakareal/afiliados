@extends('layouts.app')
@section('title', 'Brigada · Sección '.$brigada->seccion->seccion)
@section('content')
<div class="container-xl py-3">
  <div class="d-flex flex-wrap justify-content-between align-items-start gap-2 mb-3"><div><a href="{{ route('brigadas.index') }}" class="text-decoration-none"><i class="fa-solid fa-arrow-left me-1"></i> Todas las brigadas</a><h1 class="h3 mt-2 mb-1">Brigada · Sección {{ $brigada->seccion->seccion }}</h1><p class="text-muted mb-0">Distrito local {{ $brigada->seccion->distrito_local }} · Morelia</p></div><span class="badge fs-6 {{ $brigada->activo ? 'text-bg-success' : 'text-bg-secondary' }}">{{ $brigada->activo ? 'Activa' : 'Cerrada' }}</span></div>
  @if(session('success'))<div class="alert alert-success"><i class="fa-solid fa-circle-check me-1"></i> {{ session('success') }}</div>@endif
  @if($errors->any())<div class="alert alert-danger"><strong>No se pudo registrar:</strong><ul class="mb-0 mt-1">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
  <div class="row g-3">
    <div class="col-lg-7">
      <div class="card shadow-sm border-0 mb-3"><div class="card-body p-4"><h2 class="h5 mb-3">Información</h2><dl class="row mb-0"><dt class="col-sm-4">Responsable</dt><dd class="col-sm-8">{{ $brigada->responsable }}</dd><dt class="col-sm-4">Teléfono</dt><dd class="col-sm-8">{{ $brigada->telefono ?: 'No registrado' }}</dd><dt class="col-sm-4">Punto de reunión</dt><dd class="col-sm-8">{{ $brigada->direccion ?: 'Consulta el mapa' }}</dd><dt class="col-sm-4">Indicaciones</dt><dd class="col-sm-8">{{ $brigada->observaciones ?: 'Sin indicaciones adicionales' }}</dd><dt class="col-sm-4">Asistencias</dt><dd class="col-sm-8"><strong>{{ $brigada->participantes_count }}</strong></dd></dl><a class="btn btn-outline-primary mt-3" target="_blank" rel="noopener" href="{{ $brigada->google_maps_url }}"><i class="fa-solid fa-diamond-turn-right me-1"></i> Abrir Google Maps</a>@can('brigadas.editar')<a class="btn btn-outline-secondary mt-3" href="{{ route('brigadas.edit', $brigada) }}">Editar</a>@endcan</div></div>
      @can('brigadas.participantes.ver')
        <div class="card shadow-sm border-0"><div class="card-body p-4"><h2 class="h5 mb-3">Personas que asistieron</h2><div class="table-responsive"><table class="table align-middle mb-0"><thead><tr><th>Persona</th><th>Fecha</th><th>Evidencia</th></tr></thead><tbody>@forelse($participantes as $participante)<tr><td>{{ optional($participante->usuario)->name ?: 'Usuario' }}@if($participante->observaciones)<div class="small text-muted">{{ $participante->observaciones }}</div>@endif</td><td>{{ optional($participante->asistio_en)->format('d/m/Y H:i') }}</td><td><a class="btn btn-sm btn-outline-primary" target="_blank" href="{{ route('brigadas.participantes.evidencia', [$brigada, $participante]) }}">Ver foto</a></td></tr>@empty<tr><td colspan="3" class="text-center text-muted py-3">Todavía no hay asistencias.</td></tr>@endforelse</tbody></table></div></div></div>
      @endcan
    </div>
    <div class="col-lg-5">
      @if($miParticipacion)
        <div class="card shadow-sm border-success"><div class="card-body p-4 text-center"><i class="fa-solid fa-circle-check fa-3x text-success mb-3"></i><h2 class="h5">Tu asistencia ya está registrada</h2><p class="text-muted">{{ optional($miParticipacion->asistio_en)->format('d/m/Y H:i') }}</p><a class="btn btn-outline-success" target="_blank" href="{{ route('brigadas.participantes.evidencia', [$brigada, $miParticipacion]) }}">Ver mi foto</a></div></div>
      @elseif(!$brigada->activo)
        <div class="card shadow-sm border-0"><div class="card-body p-4 text-center"><i class="fa-solid fa-lock fa-2x text-muted mb-3"></i><h2 class="h5">Registro cerrado</h2><p class="text-muted mb-0">Esta brigada ya no acepta nuevas asistencias.</p></div></div>
      @else
        @can('brigadas.participar')
          <div class="card shadow-sm border-0"><div class="card-body p-4"><h2 class="h5">Confirmar mi asistencia</h2><p class="text-muted">Toma una foto en la brigada. Sólo se admite una asistencia por persona.</p><form method="POST" action="{{ route('brigadas.participar', $brigada) }}" enctype="multipart/form-data">@csrf<div class="mb-3"><label class="form-label fw-bold" for="foto">Foto de evidencia</label><input class="form-control" type="file" id="foto" name="foto" accept="image/jpeg,image/png,image/webp" capture="environment" required><div class="form-text">Máximo 9 MB.</div></div><div class="mb-3"><label class="form-label" for="observaciones_asistencia">Comentario (opcional)</label><textarea class="form-control" id="observaciones_asistencia" name="observaciones" rows="3" maxlength="2000">{{ old('observaciones') }}</textarea></div><button class="btn btn-granate w-100"><i class="fa-solid fa-camera me-1"></i> Subir foto y confirmar</button></form></div></div>
        @else
          <div class="alert alert-info">Puedes consultar la brigada, pero tu usuario no tiene habilitado el registro de asistencia.</div>
        @endcan
      @endif
    </div>
  </div>
</div>
@endsection
