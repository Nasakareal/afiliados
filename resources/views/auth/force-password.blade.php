@extends('layouts.app')

@section('title', 'Cambiar contraseña')

@section('content_header')
  <h1 class="text-center w-100">Actualiza tu contraseña</h1>
@endsection

@section('content')
<div class="container-sm" style="max-width: 560px;">
  @if(session('warning'))
    <div class="alert alert-warning">{{ session('warning') }}</div>
  @endif
  @if($errors->any())
    <div class="alert alert-danger">
      <ul class="mb-0">
        @foreach($errors->all() as $e)
          <li>{{ $e }}</li>
        @endforeach
      </ul>
    </div>
  @endif

  <div class="card">
    <div class="card-body">
      <form method="POST" action="{{ route('password.force.update') }}">
        @csrf

        <div class="mb-3">
          <label for="current_password" class="form-label">Contraseña actual</label>
          <input type="password" name="current_password" id="current_password" class="form-control" required autocomplete="current-password">
        </div>

        <div class="mb-3">
          <label for="password" class="form-label">Nueva contraseña</label>
          <input type="password" name="password" id="password" class="form-control" required autocomplete="new-password">
          <small class="text-muted">
            Mínimo 8 caracteres, mayúsculas, minúsculas y números. Debe ser distinta a la actual.
          </small>
        </div>

        <div class="mb-3">
          <label for="password_confirmation" class="form-label">Confirmar nueva contraseña</label>
          <input type="password" name="password_confirmation" id="password_confirmation" class="form-control" required autocomplete="new-password">
        </div>

        <button type="submit" class="btn btn-primary w-100">
          Guardar y continuar
        </button>
      </form>
    </div>
  </div>
</div>
@endsection
