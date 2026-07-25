@extends('layouts.app')

@section('title', 'Editar lona')

@section('content')
<div class="container-xl">
  <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
    <div>
      <h1 class="h3 mb-1">Editar lona #{{ $lona->id }}</h1>
      <p class="text-muted mb-0">La fotografía sólo se reemplaza si seleccionas una nueva.</p>
    </div>
    <a href="{{ route('lonas.show', $lona) }}" class="btn btn-outline-secondary">
      <i class="fa-solid fa-arrow-left me-1"></i> Volver
    </a>
  </div>

  <div class="card shadow-sm border-0">
    <div class="card-body">
      <form action="{{ route('lonas.update', $lona) }}" method="POST" enctype="multipart/form-data" id="lonaForm">
        @csrf
        @method('PUT')
        @include('lonas._form')
        <div class="d-flex justify-content-end gap-2 mt-4">
          <a href="{{ route('lonas.show', $lona) }}" class="btn btn-outline-secondary">Cancelar</a>
          <button type="submit" class="btn btn-granate">
            <i class="fa-solid fa-floppy-disk me-1"></i> Guardar cambios
          </button>
        </div>
      </form>
    </div>
  </div>
</div>
@endsection
