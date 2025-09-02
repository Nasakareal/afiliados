@extends('layouts.app')

@section('title','Editar Actividad')

@section('content')
<div class="container-xl">
  <div class="card card-outline card-primary shadow-sm">
    <div class="card-header d-flex justify-content-between align-items-center">
      <h3 class="card-title mb-0">
        <i class="fa fa-pen me-1"></i> Editar actividad
      </h3>
      <a href="{{ route('actividades.show', $actividad->id) }}" class="btn btn-info btn-sm">
        <i class="fa fa-eye"></i> Ver
      </a>
    </div>

    <div class="card-body">
      <form action="{{ route('actividades.update', $actividad->id) }}" method="POST" autocomplete="off">
        @csrf
        @method('PUT')

        <div class="row g-3">
          <div class="col-md-6">
            <label class="form-label">Título</label>
            <input type="text" name="titulo"
                   value="{{ old('titulo', $actividad->titulo) }}"
                   class="form-control @error('titulo') is-invalid @enderror"
                   required>
            @error('titulo')<div class="invalid-feedback">{{ $message }}</div>@enderror
          </div>

          <div class="col-md-6">
            <label class="form-label">Lugar</label>
            <input type="text" name="lugar"
                   value="{{ old('lugar', $actividad->lugar) }}"
                   class="form-control @error('lugar') is-invalid @enderror">
            @error('lugar')<div class="invalid-feedback">{{ $message }}</div>@enderror
          </div>

          <div class="col-md-12">
            <label class="form-label">Descripción</label>
            <textarea name="descripcion" rows="3"
                      class="form-control @error('descripcion') is-invalid @enderror">{{ old('descripcion', $actividad->descripcion) }}</textarea>
            @error('descripcion')<div class="invalid-feedback">{{ $message }}</div>@enderror
          </div>

          <div class="col-md-6">
            <label class="form-label">Inicio</label>
            <input type="datetime-local" name="inicio"
                   value="{{ old('inicio', optional($actividad->inicio)->format('Y-m-d\TH:i')) }}"
                   class="form-control @error('inicio') is-invalid @enderror"
                   required>
            @error('inicio')<div class="invalid-feedback">{{ $message }}</div>@enderror
          </div>

          <div class="col-md-6">
            <label class="form-label">Fin</label>
            <input type="datetime-local" name="fin"
                   value="{{ old('fin', optional($actividad->fin)->format('Y-m-d\TH:i')) }}"
                   class="form-control @error('fin') is-invalid @enderror">
            @error('fin')<div class="invalid-feedback">{{ $message }}</div>@enderror
          </div>

          @php
            $allDayOld = old('all_day', $actividad->all_day ? '1' : '0');
            $estadoOld = old('estado', $actividad->estado ?? 'programada');
          @endphp

          <div class="col-md-4">
            <label class="form-label">¿Todo el día?</label>
            <select name="all_day" class="form-select @error('all_day') is-invalid @enderror">
              <option value="0" {{ $allDayOld == '0' ? 'selected' : '' }}>No</option>
              <option value="1" {{ $allDayOld == '1' ? 'selected' : '' }}>Sí</option>
            </select>
            @error('all_day')<div class="invalid-feedback">{{ $message }}</div>@enderror
          </div>

          <div class="col-md-4">
            <label class="form-label">Estado</label>
            <select name="estado" class="form-select @error('estado') is-invalid @enderror" required>
              <option value="programada" {{ $estadoOld == 'programada' ? 'selected' : '' }}>Programada</option>
              <option value="cancelada"  {{ $estadoOld == 'cancelada'  ? 'selected' : '' }}>Cancelada</option>
              <option value="realizada"  {{ $estadoOld == 'realizada'  ? 'selected' : '' }}>Realizada</option>
            </select>
            @error('estado')<div class="invalid-feedback">{{ $message }}</div>@enderror
          </div>
        </div>

        <div class="mt-4 d-flex gap-2">
          <a href="{{ route('calendario.index') }}" class="btn btn-secondary">
            <i class="fa fa-arrow-left"></i> Cancelar
          </a>
          <button type="submit" class="btn btn-primary">
            <i class="fa fa-save"></i> Actualizar
          </button>
        </div>
      </form>
    </div>
  </div>
</div>
@endsection

@push('scripts')
@if ($errors->any())
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
Swal.fire({
  icon: 'error',
  title: 'Revisa los datos',
  html: `{!! implode('<br>', $errors->all()) !!}`,
  confirmButtonColor: '#d33'
});
</script>
@endif
@endpush
