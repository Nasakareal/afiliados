{{-- resources/views/actividades/show.blade.php --}}
@extends('layouts.app')

@section('title','Detalle de Actividad')

@section('content')
<div class="container-xl">
  <div class="card card-outline card-primary shadow-sm">
    <div class="card-header d-flex justify-content-between align-items-center">
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
        <form action="{{ route('actividades.asistencia',$actividad) }}" method="POST">@csrf<button class="btn btn-granate btn-sm"><i class="fa-solid fa-user-check"></i> Yo asistí</button></form>
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

        <dt class="col-sm-3">Tipo / colonia</dt>
        <dd class="col-sm-9">{{ $actividad->tipo ?: '—' }} / {{ $actividad->colonia ?: '—' }}</dd>

        <dt class="col-sm-3">Origen</dt>
        <dd class="col-sm-9">@if($actividad->origen === 'reporte_usuario')<span class="badge bg-warning text-dark">Reporte del equipo · {{ ucfirst($actividad->estado_revision) }}</span>@else<span class="badge bg-primary">Actividad oficial</span>@endif</dd>

        <dt class="col-sm-3">Responsable</dt>
        <dd class="col-sm-9">{{ $actividad->responsable?->name ?? 'Sin asignar' }}</dd>

        <dt class="col-sm-3">Asistencia / registros</dt>
        <dd class="col-sm-9">{{ number_format($actividad->asistentes ?? 0) }} asistentes reportados · {{ number_format($actividad->participantes->count()) }} integrantes confirmaron asistencia · {{ number_format($actividad->afiliados()->count()) }} registros vinculados</dd>

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
