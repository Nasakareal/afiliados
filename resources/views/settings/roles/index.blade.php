@extends('layouts.app')

@section('title','Roles')

@section('content')
<div class="container-xl">
  <div class="card card-outline card-primary">
    <div class="card-header d-flex justify-content-between align-items-center">
      <h3 class="card-title mb-0">Roles</h3>
      @can('roles.crear')
      <a href="{{ route('settings.roles.create') }}" class="btn btn-primary btn-sm">
        <i class="fa fa-plus"></i> Nuevo
      </a>
      @endcan
    </div>

    <div class="card-body">
      <table class="table table-striped table-bordered table-hover table-sm">
        <thead>
          <tr>
            <th style="width:60px" class="text-center">#</th>
            <th>Nombre</th>
            <th style="width:200px" class="text-center">Acciones</th>
          </tr>
        </thead>
        <tbody>
          @foreach($roles as $i => $role)
          <tr>
            <td class="text-center">{{ ($roles->currentPage()-1)*$roles->perPage() + $i + 1 }}</td>
            <td class="text-center">{{ $role->name }}</td>
            <td class="text-center">
              <div class="btn-group">
                <a href="{{ route('settings.roles.show',$role->id) }}" class="btn btn-info btn-sm" title="Ver">
                  <i class="fa fa-eye"></i>
                </a>
                @can('roles.editar')
                <a href="{{ route('settings.roles.edit',$role->id) }}" class="btn btn-success btn-sm" title="Editar">
                  <i class="fa fa-pen"></i>
                </a>
                @endcan
                @can('roles.borrar')
                <form action="{{ route('settings.roles.destroy',$role->id) }}" method="POST" class="d-inline" id="formDel-{{ $role->id }}">
                  @csrf @method('DELETE')
                  <button type="button" class="btn btn-danger btn-sm" onclick="confirmarEliminar('{{ $role->id }}', this)" title="Eliminar">
                    <i class="fa fa-trash"></i>
                  </button>
                </form>
                @endcan
                @can('permisos.ver')
                <a href="{{ route('settings.roles.permisos.edit',$role->id) }}" 
                   class="btn btn-warning btn-sm" title="Permisos">
                   <i class="fa fa-key"></i>
                </a>
                @endcan
              </div>
            </td>
          </tr>
          @endforeach
        </tbody>
      </table>

      <div class="mt-2">
        {{ $roles->links() }}
      </div>
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
