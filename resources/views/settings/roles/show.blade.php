@extends('layouts.app')

@section('title','Detalle de rol')

@section('content')
<div class="container-xl">
  <div class="card card-outline card-primary">
    <div class="card-header d-flex justify-content-between align-items-center">
      <h3 class="card-title mb-0">Detalle de Rol</h3>
      <div class="d-flex gap-2">
        @can('roles.editar')
        <a href="{{ route('settings.roles.edit',$role->id) }}" class="btn btn-success btn-sm">
          <i class="fa fa-pen"></i> Editar
        </a>
        @endcan
        @can('roles.borrar')
        <form action="{{ route('settings.roles.destroy',$role->id) }}" method="POST" id="formDel-{{ $role->id }}">
          @csrf @method('DELETE')
          <button type="button" class="btn btn-danger btn-sm" onclick="confirmarEliminar('{{ $role->id }}', this)">
            <i class="fa fa-trash"></i> Eliminar
          </button>
        </form>
        @endcan
      </div>
    </div>

    <div class="card-body">
      <dl class="row">
        <dt class="col-sm-3">Nombre</dt>
        <dd class="col-sm-9">{{ $role->name }}</dd>

        <dt class="col-sm-3">Guard</dt>
        <dd class="col-sm-9"><span class="badge bg-secondary">{{ $role->guard_name }}</span></dd>
      </dl>

      <hr>

      <h5>Permisos asignados</h5>
      @if(count($permisos))
        <ul class="list-group mb-3">
          @foreach($permisos as $perm)
            <li class="list-group-item py-1">
              <i class="fa fa-check text-success me-1"></i> {{ $perm }}
            </li>
          @endforeach
        </ul>
      @else
        <p class="text-muted">Este rol no tiene permisos asignados.</p>
      @endif

      @can('permisos.editar')
      <a href="{{ route('settings.roles.permisos.edit',$role->id) }}" class="btn btn-warning btn-sm">
        <i class="fa fa-key"></i> Gestionar permisos
      </a>
      @endcan
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
  if(typeof Swal === 'undefined'){
    if(confirm('¿Eliminar rol?')) form.submit(); else btn.disabled=false;
    return;
  }
  Swal.fire({
    title:'Eliminar rol', text:'¿Deseas eliminar este rol?',
    icon:'warning', showDenyButton:true,
    confirmButtonText:'Eliminar', denyButtonText:'Cancelar',
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
