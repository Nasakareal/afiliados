@extends('layouts.app')

@section('title','Editar comunicado')

@section('content_header')
  <h1 class="text-center w-100">Editar comunicado</h1>
@endsection

@section('content')
<div class="container-xl">
  <div class="card card-outline card-primary">
    <div class="card-body">
      <form action="{{ route('settings.comunicados.update',$comunicado->id) }}" method="POST" autocomplete="off">
        @csrf @method('PUT')
        <div class="row g-3">
          <div class="col-md-8">
            <label class="form-label">Título</label>
            <input type="text" name="titulo" value="{{ old('titulo',$comunicado->titulo) }}" class="form-control @error('titulo') is-invalid @enderror" required>
            @error('titulo')<div class="invalid-feedback">{{ $message }}</div>@enderror
          </div>
          <div class="col-md-4">
            <label class="form-label">Estado</label>
            @php $est = old('estado',$comunicado->estado); @endphp
            <select name="estado" class="form-select @error('estado') is-invalid @enderror" required>
              <option value="borrador"  {{ $est==='borrador'?'selected':'' }}>Borrador</option>
              <option value="publicado" {{ $est==='publicado'?'selected':'' }}>Publicado</option>
              <option value="archivado" {{ $est==='archivado'?'selected':'' }}>Archivado</option>
            </select>
            @error('estado')<div class="invalid-feedback">{{ $message }}</div>@enderror
          </div>

          <div class="col-md-6">
            <label class="form-label">Visible desde</label>
            <input type="datetime-local" name="visible_desde"
                   value="{{ old('visible_desde', optional($comunicado->visible_desde)->format('Y-m-d\TH:i')) }}"
                   class="form-control @error('visible_desde') is-invalid @enderror">
            @error('visible_desde')<div class="invalid-feedback">{{ $message }}</div>@enderror
          </div>
          <div class="col-md-6">
            <label class="form-label">Visible hasta</label>
            <input type="datetime-local" name="visible_hasta"
                   value="{{ old('visible_hasta', optional($comunicado->visible_hasta)->format('Y-m-d\TH:i')) }}"
                   class="form-control @error('visible_hasta') is-invalid @enderror">
            @error('visible_hasta')<div class="invalid-feedback">{{ $message }}</div>@enderror
          </div>

          <div class="col-12">
            <label class="form-label">Contenido</label>
            <textarea name="contenido" rows="8" class="form-control @error('contenido') is-invalid @enderror" required>{{ old('contenido',$comunicado->contenido) }}</textarea>
            @error('contenido')<div class="invalid-feedback">{{ $message }}</div>@enderror
          </div>
        </div>

        <div class="mt-4 d-flex gap-2">
          <a href="{{ route('settings.comunicados.index') }}" class="btn btn-secondary">Cancelar</a>
          <button class="btn btn-primary">Actualizar</button>
        </div>
      </form>
    </div>
  </div>
</div>
@endsection
