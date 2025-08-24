@extends('layouts.app')

@section('title','Detalle de usuario')

@section('content_header')
  <h1 class="text-center w-100">Detalle de usuario</h1>
@endsection

@section('content')
<div class="container-xl">
  <div class="card card-outline card-primary">
    <div class="card-body">
      <dl class="row">
        <dt class="col-sm-3">Nombre</dt><dd class="col-sm-9">{{ $user->name }}</dd>
        <dt class="col-sm-3">Email</dt><dd class="col-sm-9">{{ $user->email }}</dd>
        <dt class="col-sm-3">Roles</dt>
        <dd class="col-sm-9">
          @forelse($user->roles as $r)
            <span class="badge bg-primary">{{ $r->name }}</span>
          @empty
            <span class="text-muted">Sin rol</span>
          @endforelse
        </dd>
        <dt class="col-sm-3">Creado</dt><dd class="col-sm-9">{{ optional($user->created_at)->format('Y-m-d H:i') }}</dd>
      </dl>
      <div class="d-flex gap-2">
        <a href="{{ route('settings.usuarios.index') }}" class="btn btn-secondary">Volver</a>
        @can('usuarios.editar')
        <a href="{{ route('settings.usuarios.edit',$user->id) }}" class="btn btn-success">Editar</a>
        @endcan
      </div>
    </div>
  </div>
</div>
@endsection
