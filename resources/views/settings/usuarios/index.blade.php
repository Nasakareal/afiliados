@extends('layouts.app')

@section('title', 'Usuarios')

@section('content_header')
  <h1 class="text-center w-100">Usuarios</h1>
@endsection

@section('content')
<div class="container-xl">
  <div class="card card-outline card-primary">
    <div class="card-header d-flex justify-content-between align-items-center">
      <h3 class="card-title mb-0">Usuarios registrados</h3>

      @can('usuarios.crear')
        <a href="{{ route('settings.usuarios.create') }}" class="btn btn-primary btn-sm">
          <i class="fa fa-plus"></i> Nuevo
        </a>
      @endcan
    </div>

    <div class="card-body">
      <form method="GET" action="{{ route('settings.usuarios.index') }}" class="mb-4">
        <div class="row g-2 align-items-end">
          <div class="col-12 col-md-5">
            <label for="buscar" class="form-label">Buscar usuario</label>

            <div class="input-group">
              <input
                type="search"
                name="buscar"
                id="buscar"
                class="form-control"
                value="{{ $busqueda }}"
                placeholder="Nombre o correo electrónico"
                autocomplete="off"
              >

              <button type="submit" class="btn btn-primary" title="Buscar">
                <i class="fa fa-search"></i>
              </button>
            </div>
          </div>

          <div class="col-12 col-md-3">
            <label for="rol" class="form-label">Rol</label>

            <select name="rol" id="rol" class="form-control">
              <option value="">Todos los roles</option>

              @foreach($roles as $rol)
                <option
                  value="{{ $rol->name }}"
                  @selected($rolSeleccionado === $rol->name)
                >
                  {{ $rol->name }}
                </option>
              @endforeach
            </select>
          </div>

          @if($distritosLocales->count() > 1)
            <div class="col-12 col-md-2">
              <label for="distrito_local" class="form-label">Distrito local</label>

              <select name="distrito_local" id="distrito_local" class="form-control">
                <option value="">Todos</option>

                @foreach($distritosLocales as $distrito)
                  <option
                    value="{{ $distrito }}"
                    @selected((string) $distritoSeleccionado === (string) $distrito)
                  >
                    DL {{ str_pad($distrito, 2, '0', STR_PAD_LEFT) }}
                  </option>
                @endforeach
              </select>
            </div>
          @endif

          <div class="col-12 col-md-auto">
            <div class="d-flex gap-2">
              <button type="submit" class="btn btn-primary">
                <i class="fa fa-filter"></i> Filtrar
              </button>

              @if($busqueda !== '' || $rolSeleccionado || $distritoSeleccionado)
                <a
                  href="{{ route('settings.usuarios.index') }}"
                  class="btn btn-secondary"
                  title="Limpiar filtros"
                >
                  <i class="fa fa-times"></i> Limpiar
                </a>
              @endif
            </div>
          </div>
        </div>
      </form>

      <div class="d-flex justify-content-between align-items-center mb-2">
        <span class="text-muted">
          {{ $usuarios->total() }}
          {{ $usuarios->total() === 1 ? 'usuario encontrado' : 'usuarios encontrados' }}
        </span>
      </div>

      <div class="table-responsive">
        <table id="tblUsuarios" class="table table-striped table-bordered table-hover table-sm">
          <thead>
            <tr>
              <th class="text-center" style="width: 60px">#</th>
              <th>Nombre</th>
              <th>Email</th>
              <th>Roles</th>
              <th>Distrito local</th>
              <th>Creación</th>
              <th class="text-center" style="width: 140px">Acciones</th>
            </tr>
          </thead>

          <tbody>
            @forelse($usuarios as $i => $u)
              <tr>
                <td class="text-center">
                  {{ $usuarios->firstItem() + $i }}
                </td>

                <td>{{ $u->name }}</td>

                <td>{{ $u->email }}</td>

                <td>
                  @forelse($u->roles as $rol)
                    <span class="badge bg-primary">
                      {{ $rol->name }}
                    </span>
                  @empty
                    <span class="text-muted">Sin rol</span>
                  @endforelse
                </td>

                <td>
                  @if($u->distrito_local)
                    <span class="badge bg-secondary">
                      DL {{ str_pad($u->distrito_local, 2, '0', STR_PAD_LEFT) }}
                    </span>
                  @else
                    <span class="text-muted">Todos</span>
                  @endif
                </td>

                <td>
                  {{ optional($u->created_at)->format('d/m/Y H:i') }}
                </td>

                <td class="text-center">
                  <div class="btn-group">
                    <a
                      href="{{ route('settings.usuarios.show', $u) }}"
                      class="btn btn-info btn-sm"
                      title="Ver usuario"
                    >
                      <i class="fa fa-eye"></i>
                    </a>

                    @can('usuarios.editar')
                      <a
                        href="{{ route('settings.usuarios.edit', $u) }}"
                        class="btn btn-success btn-sm"
                        title="Editar usuario"
                      >
                        <i class="fa fa-pen"></i>
                      </a>
                    @endcan

                    @can('usuarios.borrar')
                      <form
                        action="{{ route('settings.usuarios.destroy', $u) }}"
                        method="POST"
                        id="formDel-{{ $u->id }}"
                      >
                        @csrf
                        @method('DELETE')

                        <button
                          type="button"
                          class="btn btn-danger btn-sm"
                          title="Eliminar usuario"
                          onclick="confirmarEliminar('{{ $u->id }}', this)"
                        >
                          <i class="fa fa-trash"></i>
                        </button>
                      </form>
                    @endcan
                  </div>
                </td>
              </tr>
            @empty
              <tr>
                <td colspan="7" class="text-center text-muted py-4">
                  <i class="fa fa-search mb-2"></i>
                  <div>No se encontraron usuarios con los filtros seleccionados</div>
                </td>
              </tr>
            @endforelse
          </tbody>
        </table>
      </div>

      @if($usuarios->hasPages())
        <div class="mt-3 d-flex justify-content-center">
          {{ $usuarios->links() }}
        </div>
      @endif
    </div>
  </div>
</div>
@endsection

@section('js')
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
function confirmarEliminar(id, boton) {
  const formulario = document.getElementById('formDel-' + id);

  boton.disabled = true;

  if (typeof Swal === 'undefined') {
    if (confirm('¿Deseas eliminar este usuario?')) {
      formulario.submit();
    } else {
      boton.disabled = false;
    }

    return;
  }

  Swal.fire({
    title: 'Eliminar usuario',
    text: '¿Deseas eliminar este usuario?',
    icon: 'warning',
    showCancelButton: true,
    confirmButtonText: 'Eliminar',
    cancelButtonText: 'Cancelar',
    confirmButtonColor: '#dc3545',
    cancelButtonColor: '#6c757d'
  }).then(resultado => {
    if (resultado.isConfirmed) {
      formulario.submit();
    } else {
      boton.disabled = false;
    }
  });
}

document.addEventListener('DOMContentLoaded', function () {
  const selectorRol = document.getElementById('rol');
  const selectorDistrito = document.getElementById('distrito_local');

  selectorRol?.addEventListener('change', function () {
    this.form.submit();
  });

  selectorDistrito?.addEventListener('change', function () {
    this.form.submit();
  });

  if (
    window.jQuery &&
    $.fn.dataTable &&
    $.fn.dataTable.isDataTable('#tblUsuarios')
  ) {
    $('#tblUsuarios').DataTable().destroy();
    $('#tblUsuarios thead th').removeClass(
      'sorting sorting_asc sorting_desc'
    );
  }

  const tabla = document.getElementById('tblUsuarios');
  const contenedor = tabla?.closest('.dataTables_wrapper');

  if (contenedor) {
    contenedor
      .querySelectorAll(
        '.dataTables_paginate, .dataTables_info, .dataTables_length, .dataTables_filter'
      )
      .forEach(elemento => elemento.remove());
  }
});
</script>

@if(session('status'))
  <script>
    Swal.fire({
      icon: 'success',
      title: @json(session('status')),
      timer: 2500,
      showConfirmButton: false
    });
  </script>
@endif

@if($errors->has('delete'))
  <script>
    Swal.fire({
      icon: 'error',
      title: 'No se pudo eliminar',
      text: @json($errors->first('delete'))
    });
  </script>
@endif
@endsection
