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
