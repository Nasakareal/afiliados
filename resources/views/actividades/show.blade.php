{{-- resources/views/actividades/show.blade.php --}}
@extends('layouts.app')

@section('title','Detalle de Actividad')

@section('content')
<div class="container-xl">
  <div class="card card-outline card-primary shadow-sm">
    <div class="card-header d-flex flex-wrap gap-2 justify-content-between align-items-center">
      <h3 class="card-title mb-0">
        <i class="fa fa-calendar-day me-1"></i> {{ $actividad->titulo }}
      </h3>
      <div class="d-flex gap-2">
        @if($actividad->origen === 'reporte_usuario' && $actividad->estado_revision === 'pendiente' && auth()->user()->hasAnyRole(['Admin','SuperAdmin']))
        <form action="{{ route('actividades.revision',$actividad) }}" method="POST">@csrf @method('PUT')<input type="hidden" name="estado_revision" value="aprobada"><button class="btn btn-success btn-sm"><i class="fa-solid fa-check"></i> Aprobar reporte</button></form>
        <form action="{{ route('actividades.revision',$actividad) }}" method="POST">@csrf @method('PUT')<input type="hidden" name="estado_revision" value="rechazada"><button class="btn btn-outline-danger btn-sm"><i class="fa-solid fa-xmark"></i> Rechazar</button></form>
        @endif
        @can('actividades.reportar')
        @unless($yoAsisti)
        <button type="button" class="btn btn-granate btn-sm" data-bs-toggle="modal" data-bs-target="#modalAsistencia"><i class="fa-solid fa-user-check"></i> Yo asistí</button>
        @endunless
        @endcan
        @can('actividades.editar')
        <a href="{{ route('actividades.edit',$actividad->id) }}" class="btn btn-success btn-sm">
          <i class="fa fa-pen"></i> Editar
        </a>
        @endcan
        @can('actividades.borrar')
        <form action="{{ route('actividades.destroy',$actividad->id) }}" method="POST" id="formDel-{{ $actividad->id }}">
          @csrf @method('DELETE')
          <button type="button" class="btn btn-danger btn-sm" onclick="confirmarEliminar('{{ $actividad->id }}', this)">
            <i class="fa fa-trash"></i> Eliminar
          </button>
        </form>
        @endcan
        <a href="{{ route('calendario.index') }}" class="btn btn-secondary btn-sm">
          <i class="fa fa-arrow-left"></i> Volver
        </a>
      </div>
    </div>
    <div class="card-body">
      <dl class="row mb-0">
        <dt class="col-sm-3">Descripción</dt>
        <dd class="col-sm-9">{{ $actividad->descripcion ?: '—' }}</dd>

        <dt class="col-sm-3">Lugar</dt>
        <dd class="col-sm-9">{{ $actividad->lugar ?: '—' }}</dd>

        <dt class="col-sm-3">Ubicación</dt>
        <dd class="col-sm-9">
          @if($actividad->ubicacion_google)
            <a href="{{ $actividad->ubicacion_google }}" target="_blank" rel="noopener" class="btn btn-outline-danger btn-sm"><i class="fa-solid fa-map-location-dot me-1"></i> Abrir en Google Maps</a>
          @else
            <span class="text-muted">Sin enlace de ubicación</span>
          @endif
        </dd>

        <dt class="col-sm-3">Tipo / colonia</dt>
        <dd class="col-sm-9">{{ $actividad->tipo ?: '—' }} / {{ $actividad->colonia ?: '—' }}</dd>

        <dt class="col-sm-3">Origen</dt>
        <dd class="col-sm-9">@if($actividad->origen === 'reporte_usuario')<span class="badge bg-warning text-dark">Reporte del equipo · {{ ucfirst($actividad->estado_revision) }}</span>@else<span class="badge bg-primary">Actividad oficial</span>@endif</dd>

        <dt class="col-sm-3">Responsable</dt>
        <dd class="col-sm-9">{{ $actividad->responsable?->name ?? 'Sin asignar' }}</dd>

        <dt class="col-sm-3">Asistencia / registros</dt>
        <dd class="col-sm-9">{{ number_format($actividad->asistentes ?? 0) }} asistentes reportados · {{ number_format($actividad->participantes->count()) }} integrantes confirmaron asistencia · {{ number_format($actividad->afiliados()->count()) }} registros vinculados</dd>

        @if($actividad->participantes->isNotEmpty())
        <dt class="col-sm-3">Evidencias de asistencia</dt>
        <dd class="col-sm-9">
          <div class="d-flex flex-wrap gap-2">
            @foreach($actividad->participantes as $participante)
              @if($participante->pivot->evidencia_path && (auth()->id() === $participante->id || auth()->user()->hasAnyRole(['Admin','SuperAdmin','Coordinador'])))
                <a class="btn btn-outline-secondary btn-sm" target="_blank" href="{{ route('actividades.asistencias.evidencia', [$actividad, $participante]) }}"><i class="fa-solid fa-camera me-1"></i> {{ auth()->id() === $participante->id ? 'Mi evidencia' : $participante->name }}</a>
              @endif
            @endforeach
          </div>
        </dd>
        @endif

        <dt class="col-sm-3">Evidencia</dt>
        <dd class="col-sm-9">
          @if($actividad->evidencia_path)
            <a href="{{ route('actividades.evidencia',$actividad) }}" target="_blank">Ver {{ $actividad->evidencia_nombre ?: 'archivo' }} <i class="fa-solid fa-paperclip"></i></a>
          @elseif($actividad->evidencia_url)
            <a href="{{ $actividad->evidencia_url }}" target="_blank" rel="noopener">Abrir evidencia <i class="fa-solid fa-arrow-up-right-from-square"></i></a>
          @else
            <span class="text-warning">Sin evidencia</span>
          @endif
        </dd>

        <dt class="col-sm-3">Inicio</dt>
        <dd class="col-sm-9">{{ $actividad->inicio->format('d/m/Y H:i') }}</dd>

        <dt class="col-sm-3">Fin</dt>
        <dd class="col-sm-9">{{ $actividad->fin?->format('d/m/Y H:i') ?: '—' }}</dd>

        <dt class="col-sm-3">Todo el día</dt>
        <dd class="col-sm-9">
          <span class="badge bg-{{ $actividad->all_day ? 'success' : 'secondary' }}">
            {{ $actividad->all_day ? 'Sí' : 'No' }}
          </span>
        </dd>

        <dt class="col-sm-3">Estado</dt>
        <dd class="col-sm-9">
          @php
            $colores = ['programada'=>'primary','cancelada'=>'danger','realizada'=>'success'];
          @endphp
          <span class="badge bg-{{ $colores[$actividad->estado] ?? 'secondary' }}">
            {{ ucfirst($actividad->estado) }}
          </span>
        </dd>

        <dt class="col-sm-3">Creado por</dt>
        <dd class="col-sm-9">
          {{ $actividad->creador?->name ?? 'Desconocido' }}
        </dd>
      </dl>
    </div>
  </div>
</div>

@can('actividades.reportar')
@unless($yoAsisti)
<div class="modal fade" id="modalAsistencia" tabindex="-1" aria-labelledby="modalAsistenciaLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered"><div class="modal-content">
    <form action="{{ route('actividades.asistencia',$actividad) }}" method="POST" enctype="multipart/form-data">
      @csrf
      <div class="modal-header"><h5 class="modal-title" id="modalAsistenciaLabel"><i class="fa-solid fa-user-check text-success me-2"></i>Confirmar que asistí</h5><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button></div>
      <div class="modal-body">
        <p>Para registrar tu asistencia a <strong>{{ $actividad->titulo }}</strong>, sube una foto tomada durante la actividad.</p>
        <label for="evidencia_asistencia" class="form-label fw-bold">Foto de evidencia <span class="text-danger">*</span></label>
        <input type="file" name="evidencia_asistencia" id="evidencia_asistencia" accept="image/jpeg,image/png,image/webp" capture="environment" class="form-control @error('evidencia_asistencia') is-invalid @enderror" required>
        <div class="form-text">Puedes tomarla ahora o elegirla de tu galería. Máximo 10 MB.</div>
        @error('evidencia_asistencia')<div class="invalid-feedback">{{ $message }}</div>@enderror
      </div>
      <div class="modal-footer"><button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancelar</button><button class="btn btn-granate"><i class="fa-solid fa-camera me-2"></i>Subir foto y confirmar</button></div>
    </form>
  </div></div>
</div>
@endunless
@endcan
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
function confirmarEliminar(id, btn){
  const form = document.getElementById('formDel-'+id);
  btn.disabled = true;
  Swal.fire({
    title:'Eliminar actividad',
    text:'¿Deseas eliminar esta actividad?',
    icon:'warning',
    showDenyButton:true,
    confirmButtonText:'Eliminar',
    denyButtonText:'Cancelar',
    confirmButtonColor:'#e3342f'
  }).then(r=>{ if(r.isConfirmed) form.submit(); else btn.disabled=false; });
}
@if (session('status'))
Swal.fire({icon:'success', title:@json(session('status')), timer:2200, showConfirmButton:false});
@endif
@if ($errors->any())
Swal.fire({icon:'error', title:'Ups', html:`{!! implode('<br>', $errors->all()) !!}`});
@endif
</script>
@endpush
