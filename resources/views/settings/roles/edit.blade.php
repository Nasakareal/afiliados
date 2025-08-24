@extends('layouts.app')

@section('title','Editar rol')

@section('content')
<div class="container-xl">
  <div class="card card-outline card-primary">
    <div class="card-header">
      <h3 class="card-title mb-0">Editar rol</h3>
    </div>
    <div class="card-body">
      <form action="{{ route('settings.roles.update',$role->id) }}" method="POST" autocomplete="off">
        @csrf @method('PUT')
        <div class="row g-3">
          <div class="col-md-6">
            <label class="form-label">Nombre del rol</label>
            <input type="text" name="name" value="{{ old('name',$role->name) }}" class="form-control @error('name') is-invalid @enderror" required>
            @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
          </div>
          <div class="col-md-6">
            <label class="form-label">Guard</label>
            <input type="text" class="form-control" value="{{ $role->guard_name }}" disabled>
          </div>
        </div>

        <div class="mt-4 d-flex gap-2">
          <a href="{{ route('settings.roles.index') }}" class="btn btn-secondary">Cancelar</a>
          <button class="btn btn-primary">Actualizar</button>
        </div>
      </form>
    </div>
  </div>
</div>
@endsection
