@extends('layouts.app')

@section('title','Usuarios')

@section('content_header')
  <h1 class="text-center w-100">Usuarios</h1>
@endsection

@section('content')
<div class="container-xl">
  <div class="card card-outline card-primary">
    <div class="card-header d-flex justify-content-between align-items-center">
      <h3 class="card-title">Usuarios registrados</h3>
      @can('usuarios.crear')
      <a href="{{ route('settings.usuarios.create') }}" class="btn btn-primary btn-sm">
        <i class="fa fa-plus"></i> Nuevo
      </a>
      @endcan
    </div>

    <div class="card-body">
      <table id="tblUsuarios" class="table table-striped table-bordered table-hover table-sm">
        <thead>
          <tr>
            <th class="text-center" style="width:60px">#</th>
            <th>Nombre</th>
            <th>Email</th>
            <th>Roles</th>
            <th>Creación</th>
            <th class="text-center" style="width:140px">Acciones</th>
          </tr>
        </thead>
        <tbody>
          @foreach($usuarios as $i => $u)
          <tr>
            <td class="text-center">{{ ($usuarios->currentPage()-1)*$usuarios->perPage() + $i + 1 }}</td>
            <td>{{ $u->name }}</td>
            <td>{{ $u->email }}</td>
            <td>
              @forelse($u->roles as $r)
                <span class="badge bg-primary">{{ $r->name }}</span>
              @empty
                <span class="text-muted">Sin rol</span>
              @endforelse
            </td>
            <td>{{ optional($u->created_at)->format('Y-m-d H:i') }}</td>
            <td class="text-center">
              <div class="btn-group">
                <a href="{{ route('settings.usuarios.show',$u->id) }}" class="btn btn-info btn-sm"><i class="fa fa-eye"></i></a>
                @can('usuarios.editar')
                <a href="{{ route('settings.usuarios.edit',$u->id) }}" class="btn btn-success btn-sm"><i class="fa fa-pen"></i></a>
                @endcan
                @can('usuarios.borrar')
                <form action="{{ route('settings.usuarios.destroy',$u->id) }}" method="POST" id="formDel-{{ $u->id }}">
                  @csrf @method('DELETE')
                  <button type="button" class="btn btn-danger btn-sm" onclick="confirmarEliminar('{{ $u->id }}', this)">
                    <i class="fa fa-trash"></i>
                  </button>
                </form>
                @endcan
              </div>
            </td>
          </tr>
          @endforeach
        </tbody>
      </table>

      <div class="mt-2 d-flex justify-content-center">
        {{ $usuarios->links() }}
      </div>
    </div>
  </div>
</div>
@endsection

@section('js')
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
function confirmarEliminar(id, btn){
  const form = document.getElementById('formDel-'+id);
  btn.disabled = true;
  if(typeof Swal === 'undefined'){
    if(confirm('¿Eliminar usuario?')) form.submit(); else btn.disabled=false;
    return;
  }
  Swal.fire({
    title:'Eliminar usuario',
    text:'¿Deseas eliminarlo?',
    icon:'warning',
    showDenyButton:true,
    confirmButtonText:'Eliminar',
    denyButtonText:'Cancelar',
    confirmButtonColor:'#e3342f'
  }).then(r=>{ if(r.isConfirmed) form.submit(); else btn.disabled=false; });
}

// --- Evitar doble paginación (quitar DataTables SOLO aquí) ---
document.addEventListener('DOMContentLoaded',()=>{
  if (window.jQuery && $.fn.dataTable && $.fn.dataTable.isDataTable('#tblUsuarios')) {
    $('#tblUsuarios').DataTable().destroy();
    $('#tblUsuarios thead th').removeClass('sorting sorting_asc sorting_desc');
  }
  const wrp = document.getElementById('tblUsuarios')?.closest('.dataTables_wrapper');
  if (wrp) {
    wrp.querySelectorAll('.dataTables_paginate,.dataTables_info,.dataTables_length,.dataTables_filter')
       .forEach(el=>el.remove());
  }
});
</script>

@if (session('status'))
<script>
Swal.fire({icon:'success', title:@json(session('status')), timer:2500, showConfirmButton:false});
</script>
@endif
@endsection
