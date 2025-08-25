@extends('layouts.app')

@section('title', $comunicado->titulo)

@section('content_header')
  <h1 class="text-center w-100">Comunicado</h1>
@endsection

@section('content')
<div class="container-lg">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-white border-0 d-flex justify-content-between align-items-start flex-wrap gap-2">
      <div>
        <h4 class="m-0">{{ $comunicado->titulo }}</h4>
        <div class="small text-muted">
          Creado por: {{ optional($comunicado->creador)->name ?? '—' }}
          &middot; {{ optional($comunicado->created_at)->format('Y-m-d H:i') }}
        </div>
        <div class="mt-1">
          @php
            $badge = ['borrador'=>'secondary','publicado'=>'primary','archivado'=>'dark'][$comunicado->estado] ?? 'secondary';
          @endphp
          <span class="badge bg-{{ $badge }}">{{ $comunicado->estado }}</span>
          @if($comunicado->esta_vigente ?? false)
            <span class="badge bg-success">vigente</span>
          @endif
        </div>
      </div>
      <div class="d-flex gap-2">
        <a href="{{ route('settings.comunicados.index') }}" class="btn btn-outline-secondary btn-sm">
          <i class="fa-solid fa-arrow-left"></i> Volver
        </a>
        @can('comunicados.editar')
        <a href="{{ route('settings.comunicados.edit',$comunicado->id) }}" class="btn btn-success btn-sm">
          <i class="fa fa-pen"></i> Editar
        </a>
        @endcan
        <form action="{{ route('settings.comunicados.leido',$comunicado->id) }}" method="POST">
          @csrf
          <button class="btn btn-outline-primary btn-sm"><i class="fa-solid fa-check"></i> Marcar leído</button>
        </form>
        @can('comunicados.borrar')
        <form action="{{ route('settings.comunicados.destroy',$comunicado->id) }}" method="POST" id="fDel">
          @csrf @method('DELETE')
          <button type="button" class="btn btn-danger btn-sm" onclick="delShow(this)"><i class="fa fa-trash"></i> Eliminar</button>
        </form>
        @endcan
      </div>
    </div>

    <div class="card-body">
      <div class="mb-3 small text-muted">
        <strong>Visibilidad:</strong>
        {{ optional($comunicado->visible_desde)->format('Y-m-d H:i') ?? '—' }} &rarr;
        {{ optional($comunicado->visible_hasta)->format('Y-m-d H:i') ?? '—' }}
      </div>

      <div class="mb-4">
        <h6 class="text-muted">Contenido</h6>
        <div class="border rounded p-3">
          {!! nl2br(e($comunicado->contenido)) !!}
        </div>
      </div>
    </div>
  </div>
</div>
@endsection

@section('js')
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
function delShow(btn){
  const f = document.getElementById('fDel');
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
