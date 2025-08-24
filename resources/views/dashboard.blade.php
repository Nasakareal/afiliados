{{-- ejemplo: resources/views/afiliados/index.blade.php --}}
@extends('layouts.app')
@section('title','Afiliados')
@section('content')
<div class="container">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <h1 class="h4 mb-0">Afiliados</h1>
    @can('afiliados.crear')
      <a href="{{ route('afiliados.create') }}" class="btn btn-granate btn-sm"><i class="fa-solid fa-user-plus me-1"></i> Nuevo</a>
    @endcan
  </div>
  {{-- tu tabla/listado aquí --}}
</div>
@endsection
