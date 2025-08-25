@extends('layouts.app')

@section('title','Comunicados')

@section('content_header')
  <h1 class="text-center w-100">Comunicados</h1>
@endsection

@section('content')
<div class="container-xl">
  <div class="card card-outline card-primary">
    <div class="card-header d-flex flex-column flex-md-row gap-2 justify-content-between align-items-md-center">
      <h3 class="card-title m-0">Listado</h3>
      <form method="GET" class="d-flex flex-wrap gap-2">
        <input type="text" name="q" value="{{ $q ?? '' }}" class="form-control form-control-sm" placeholder="Buscar título o contenido…" style="min-width:220px">
        <select name="estado" class="form-select form-select-sm" style="min-width:160px">
          <option value="">— Estado —</option>
          @php $estSel = $estado ?? ''; @endphp
          <option value="borrador"  {{ $estSel==='borrador'?'selected':'' }}>Borrador</option>
          <option value="publicado" {{ $estSel==='publicado'?'selected':'' }}>Publicado</option>
          <option value="archivado" {{ $estSel==='archivado'?'selected':'' }}>Archivado</option>
        </select>
        <div class="form-check form-switch align-self-center">
          <input class="form-check-input" type="checkbox" id="swVig" name="vigentes" value="1" {{ !empty($vigentes) ? 'checked' : '' }}>
          <label class="form-check-label" for="swVig">Solo vigentes</label>
        </div>
        <button class="btn btn-sm btn-outline-secondary"><i class="fa-solid fa-magnifying-glass me-1"></i> Filtrar</button>
        @can('comunicados.crear')
        <a href="{{ route('settings.comunicados.create') }}" class="btn btn-sm btn-primary">
          <i class="fa fa-plus me-1"></i> Nuevo
        </a>
        @endcan
      </form>
    </div>

    <div class="card-body">
      <div class="table-responsive">
        <table class="table table-sm table-bordered align-middle">
          <thead class="table-light">
            <tr>
              <th style="width:60px" class="text-center">#</th>
              <th>Título</th>
              <th style="width:130px">Estado</th>
              <th style="width:180px">Visible</th>
              <th style="width:180px">Creado</th>
              <th style="width:160px">Autor</th>
              <th style="width:140px" class="text-center">Acciones</th>
            </tr>
          </thead>
          <tbody>
          @forelse($comunicados as $i => $c)
            <tr>
              <td class="text-center">{{ ($comunicados->currentPage()-1)*$comunicados->perPage() + $i + 1 }}</td>
              <td class="fw-semibold">
                <a href="{{ route('settings.comunicados.show',$c->id) }}">{{ $c->titulo }}</a>
                @if(method_exists($c,'getAttribute') && ($c->esta_vigente ?? false))
                  <span class="badge bg-success ms-1">vigente</span>
                @endif
              </td>
              <td>
                @php
                  $badge = ['borrador'=>'secondary','publicado'=>'primary','archivado'=>'dark'][$c->estado] ?? 'secondary';
                @endphp
                <span class="badge bg-{{ $badge }}">{{ $c->estado }}</span>
              </td>
              <td class="small">
                {{ optional($c->visible_desde)->format('Y-m-d H:i') ?? '—' }}
                &rarr;
                {{ optional($c->visible_hasta)->format('Y-m-d H:i') ?? '—' }}
              </td>
              <td class="small">{{ optional($c->created_at)->format('Y-m-d H:i') }}</td>
              <td class="small">{{ optional($c->creador)->name ?? '—' }}</td>
              <td class="text-center">
                <div class="btn-group btn-group-sm">
                  <a href="{{ route('settings.comunicados.show',$c->id) }}" class="btn btn-info"><i class="fa fa-eye"></i></a>
                  @can('comunicados.editar')
                  <a href="{{ route('settings.comunicados.edit',$c->id) }}" class="btn btn-success"><i class="fa fa-pen"></i></a>
                  @endcan
                  @can('comunicados.borrar')
                  <form action="{{ route('settings.comunicados.destroy',$c->id) }}" method="POST" id="fDel-{{ $c->id }}">
                    @csrf @method('DELETE')
                    <button type="button" class="btn btn-danger" onclick="delCom({{ $c->id }}, this)"><i class="fa fa-trash"></i></button>
                  </form>
                  @endcan
                </div>
              </td>
            </tr>
          @empty
            <tr><td colspan="7" class="text-center text-muted">Sin resultados</td></tr>
          @endforelse
          </tbody>
        </table>
      </div>

      <div class="mt-2">
        {{ $comunicados->links() }}
      </div>
    </div>
  </div>
</div>
@endsection

@section('js')
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
function delCom(id, btn){
  const f = document.getElementById('fDel-'+id);
  btn.disabled = true;
  if (typeof Swal === 'undefined') {
    if (confirm('¿Eliminar comunicado?')) f.submit(); else btn.disabled=false;
    return;
  }
  Swal.fire({title:'Eliminar comunicado', text:'¿Deseas eliminarlo?', icon:'warning',
    showDenyButton:true, confirmButtonText:'Eliminar', denyButtonText:'Cancelar',
    confirmButtonColor:'#e3342f'
  }).then(r=>{ if(r.isConfirmed) f.submit(); else btn.disabled=false; });
}
</script>
@endsection
