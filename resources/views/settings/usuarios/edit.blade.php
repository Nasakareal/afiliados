@extends('layouts.app')

@section('title','Editar usuario')

@section('content_header')
  <h1 class="text-center w-100">Editar usuario</h1>
@endsection

@section('content')
<div class="container-xl">
  <div class="card card-outline card-primary">
    <div class="card-body">
      <form action="{{ route('settings.usuarios.update',$user->id) }}" method="POST" autocomplete="off">
        @csrf @method('PUT')
        <div class="row g-3">
          <div class="col-md-6">
            <label class="form-label">Nombre</label>
            <input type="text" name="name" value="{{ old('name',$user->name) }}" class="form-control @error('name') is-invalid @enderror" required>
            @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
          </div>
          <div class="col-md-6">
            <label class="form-label">Email</label>
            <input type="email" name="email" value="{{ old('email',$user->email) }}" class="form-control @error('email') is-invalid @enderror" required>
            @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
          </div>
          <div class="col-md-6">
            <label class="form-label">Password (opcional)</label>
            <input type="password" name="password" class="form-control @error('password') is-invalid @enderror" placeholder="Deja vacío para no cambiar">
            @error('password')<div class="invalid-feedback">{{ $message }}</div>@enderror
          </div>
          <div class="col-md-6">
            <label class="form-label">Confirmar password</label>
            <input type="password" name="password_confirmation" class="form-control" placeholder="Solo si cambias password">
          </div>
            <div class="col-md-12">
                <label class="form-label">Rol</label>
                <select name="role" class="form-select @error('role') is-invalid @enderror" required>
                <option value="">-- Selecciona un rol --</option>
                @foreach($roles as $role)
                    <option value="{{ $role->name }}"
                        {{ old('role', $selectedRole) == $role->name ? 'selected' : '' }}>
                        {{ $role->name }}
                    </option>
                @endforeach
                </select>
                @error('role')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
        </div>

        <div class="mt-4 d-flex gap-2">
          <a href="{{ route('settings.usuarios.index') }}" class="btn btn-secondary">Cancelar</a>
          <button class="btn btn-primary">Actualizar</button>
        </div>
      </form>
    </div>
  </div>
</div>
@endsection
