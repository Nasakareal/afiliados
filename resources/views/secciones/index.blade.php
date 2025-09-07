@extends('layouts.app')

@section('title','Secciones')

@section('content_header')
  <h1 class="text-center w-100">Secciones</h1>
@endsection

@section('content')
<div class="container-xl">
  <div class="card card-outline card-primary">
    <div class="card-header d-flex justify-content-between align-items-center">
      <h3 class="card-title">Secciones registradas</h3>
      @can('secciones.crear')
      <div class="btn-group">
        <form method="POST" action="{{ route('secciones.import') }}" enctype="multipart/form-data" class="d-flex gap-2">
          @csrf
          <input type="file" name="archivo" accept=".xlsx,.xls,.csv,.ods" required class="form-control form-control-sm" />
          <button class="btn btn-success btn-sm">
            <i class="fa fa-file-excel"></i> Importar secciones
          </button>
        </form>

      </div>
      <div class="btn-group">
        <a href="{{ route('secciones.create') }}" class="btn btn-primary btn-sm">
          <i class="fa fa-plus"></i> Nueva
        </a>
      </div>
      @endcan
    </div>

    <div class="card-body">
      {{-- Filtros --}}
      <form method="GET" action="{{ route('secciones.index') }}" class="row g-2 mb-3">
        <div class="col-12 col-md-5">
          <input type="text" name="q" value="{{ $q ?? '' }}" class="form-control form-control-sm"
                 placeholder="Buscar: sección, municipio o distrito">
        </div>
        <div class="col-8 col-md-5">
          <input list="dlMunicipios" type="text" name="municipio" value="{{ $municipio ?? '' }}"
                 class="form-control form-control-sm" placeholder="Municipio">
          @if(!empty($municipios) && count($municipios))
          <datalist id="dlMunicipios">
            @foreach($municipios as $m)
              <option value="{{ $m->municipio }}">{{ $m->municipio }}</option>
            @endforeach
          </datalist>
          @endif
        </div>
        <div class="col-4 col-md-1">
          <button class="btn btn-outline-primary btn-sm w-100">
            <i class="fa fa-search"></i>
          </button>
        </div>
        <div class="col-12 col-md-1">
          <a href="{{ route('secciones.index') }}" class="btn btn-outline-secondary btn-sm w-100">
            <i class="fa fa-eraser"></i>
          </a>
        </div>
      </form>

      <table id="tblSecciones" class="table table-striped table-bordered table-hover table-sm">
        <thead>
          <tr>
            <th class="text-center" style="width:60px">#</th>
            <th style="width:120px">Distrito Fed.</th>
            <th style="width:120px">Distrito Local</th>
            <th>Municipio</th>
            <th style="width:120px">Sección</th>
            <th class="text-center" style="width:140px">Acciones</th>
          </tr>
        </thead>
        <tbody>
          @foreach($secciones as $i => $s)
          @php
            $rownum = ($secciones->currentPage()-1)*$secciones->perPage() + $i + 1;
            $pk = $s->id ?? $s->getKey();
          @endphp
          <tr>
            <td class="text-center">{{ $rownum }}</td>
            <td>
              @if(!is_null($s->distrito_federal))
                <span class="badge bg-secondary">{{ $s->distrito_federal }}</span>
              @else
                <span class="text-muted">—</span>
              @endif
            </td>
            <td>
              @if(!is_null($s->distrito_local))
                <span class="badge bg-secondary">{{ $s->distrito_local }}</span>
              @else
                <span class="text-muted">—</span>
              @endif
            </td>
            <td><strong>{{ $s->municipio }}</strong></td>
            <td><strong>{{ $s->seccion }}</strong></td>
            <td class="text-center">
              <div class="btn-group">
                @can('secciones.ver')
                <a href="{{ route('secciones.show', $pk) }}" class="btn btn-info btn-sm" title="Ver">
                  <i class="fa fa-eye"></i>
                </a>
                @endcan
                @can('secciones.editar')
                <a href="{{ route('secciones.edit', $pk) }}" class="btn btn-success btn-sm" title="Editar">
                  <i class="fa fa-pen"></i>
                </a>
                @endcan
                @can('secciones.borrar')
                <form action="{{ route('secciones.destroy', $pk) }}" method="POST" id="formDel-{{ $pk }}">
                  @csrf @method('DELETE')
                  <button type="button" class="btn btn-danger btn-sm"
                          onclick="confirmarEliminar('{{ $pk }}', this)" title="Eliminar">
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

      <div class="mt-2">
        {{ $secciones->links() }}
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
  if(typeof Swal==='undefined'){
    if(confirm('¿Eliminar sección?')) form.submit(); else btn.disabled=false;
    return;
  }
  Swal.fire({
    title:'Eliminar sección', text:'¿Deseas eliminarla?', icon:'warning',
    showDenyButton:true, confirmButtonText:'Eliminar', denyButtonText:'Cancelar',
    confirmButtonColor:'#e3342f'
  }).then(r=>{ if(r.isConfirmed) form.submit(); else btn.disabled=false; });
}
</script>

@if (session('status'))
<script>
Swal.fire({icon:'success', title:@json(session('status')), timer:2500, showConfirmButton:false});
</script>
@endif
@if (session('error'))
<script>
Swal.fire({icon:'error', title:'Error', text:@json(session('error'))});
</script>
@endif
@endsection
