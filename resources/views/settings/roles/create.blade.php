@extends('layouts.app')

@section('title','Nuevo rol')

@section('content')
<div class="container-xl">
  <div class="card card-outline card-primary">
    <div class="card-header">
      <h3 class="card-title mb-0">Crear rol</h3>
    </div>
    <div class="card-body">
      <form action="{{ route('settings.roles.store') }}" method="POST" autocomplete="off">
        @csrf
        <div class="row g-3">
          <div class="col-md-6">
            <label class="form-label">Nombre del rol</label>
            <input type="text" name="name" value="{{ old('name') }}" class="form-control @error('name') is-invalid @enderror" required>
            @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
            <small class="text-muted">Ej.: Capturista, Supervisor, Administrador</small>
          </div>
          <div class="col-md-6">
            <label class="form-label">Guard</label>
            <input type="text" class="form-control" value="web" disabled>
            <small class="text-muted">Se usa el guard <strong>web</strong> por defecto</small>
          </div>
        </div>

        <div class="mt-4 d-flex gap-2">
          <a href="{{ route('settings.roles.index') }}" class="btn btn-secondary">Cancelar</a>
          <button class="btn btn-primary">Guardar</button>
        </div>
      </form>
    </div>
  </div>
</div>
@endsection
