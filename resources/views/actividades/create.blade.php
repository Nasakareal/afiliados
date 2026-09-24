@extends('layouts.app')

@section('title','Nueva Actividad Oficial')

@section('content')
<div class="container-xl">
  <div class="card card-outline card-primary shadow-sm">
    <div class="card-header">
      <h3 class="card-title mb-0"><i class="fa fa-plus-circle me-1"></i> Crear actividad oficial</h3>
    </div>
    <div class="card-body">
      <form action="{{ route('actividades.store') }}" method="POST" autocomplete="off">
        @csrf

        <div class="row g-3">
          <div class="col-md-6">
            <label class="form-label">Título</label>
            <input type="text" name="titulo" value="{{ old('titulo') }}" 
                   class="form-control @error('titulo') is-invalid @enderror" required>
            @error('titulo')<div class="invalid-feedback">{{ $message }}</div>@enderror
          </div>

          <div class="col-md-6">
            <label class="form-label">Lugar</label>
            <input type="text" name="lugar" value="{{ old('lugar') }}" 
                   class="form-control @error('lugar') is-invalid @enderror">
            @error('lugar')<div class="invalid-feedback">{{ $message }}</div>@enderror
          </div>

          <div class="col-md-4">
            <label class="form-label">Tipo de actividad</label>
            <input type="text" name="tipo" value="{{ old('tipo') }}" class="form-control @error('tipo') is-invalid @enderror" placeholder="Jornada, reunión, recorrido…">
            @error('tipo')<div class="invalid-feedback">{{ $message }}</div>@enderror
          </div>

          <div class="col-md-4">
            <label class="form-label">Colonia</label>
            <input type="text" name="colonia" value="{{ old('colonia') }}" class="form-control @error('colonia') is-invalid @enderror">
            @error('colonia')<div class="invalid-feedback">{{ $message }}</div>@enderror
          </div>

          <div class="col-md-4">
            <label class="form-label">Responsable</label>
            <select name="responsable_id" class="form-select @error('responsable_id') is-invalid @enderror">
              <option value="">Usuario actual</option>
              @foreach($responsables as $responsable)
                <option value="{{ $responsable->id }}" {{ (string) old('responsable_id') === (string) $responsable->id ? 'selected' : '' }}>{{ $responsable->name }}</option>
              @endforeach
            </select>
            @error('responsable_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
          </div>

          <div class="col-md-12">
            <label class="form-label">Descripción</label>
            <textarea name="descripcion" rows="3" 
                      class="form-control @error('descripcion') is-invalid @enderror">{{ old('descripcion') }}</textarea>
            @error('descripcion')<div class="invalid-feedback">{{ $message }}</div>@enderror
          </div>

          <div class="col-md-6">
            <label class="form-label">Inicio</label>
            <input type="datetime-local" name="inicio" value="{{ old('inicio') }}" 
                   class="form-control @error('inicio') is-invalid @enderror" required>
            @error('inicio')<div class="invalid-feedback">{{ $message }}</div>@enderror
          </div>

          <div class="col-md-6">
            <label class="form-label">Fin</label>
            <input type="datetime-local" name="fin" value="{{ old('fin') }}" 
                   class="form-control @error('fin') is-invalid @enderror">
            @error('fin')<div class="invalid-feedback">{{ $message }}</div>@enderror
          </div>

          <div class="col-md-4">
            <label class="form-label">¿Todo el día?</label>
            <select name="all_day" class="form-select @error('all_day') is-invalid @enderror">
              <option value="0" {{ old('all_day') == '0' ? 'selected' : '' }}>No</option>
              <option value="1" {{ old('all_day') == '1' ? 'selected' : '' }}>Sí</option>
            </select>
            @error('all_day')<div class="invalid-feedback">{{ $message }}</div>@enderror
          </div>

          <div class="col-md-4">
            <label class="form-label">Personas asistentes</label>
            <input type="number" min="0" name="asistentes" value="{{ old('asistentes') }}" class="form-control @error('asistentes') is-invalid @enderror">
            @error('asistentes')<div class="invalid-feedback">{{ $message }}</div>@enderror
          </div>

          <div class="col-md-8">
            <label class="form-label">Evidencia (URL)</label>
            <input type="url" name="evidencia_url" value="{{ old('evidencia_url') }}" class="form-control @error('evidencia_url') is-invalid @enderror" placeholder="https://…">
            @error('evidencia_url')<div class="invalid-feedback">{{ $message }}</div>@enderror
          </div>

          <div class="col-md-4">
            <label class="form-label">Fecha de captura</label>
            <input type="datetime-local" name="capturada_en" value="{{ old('capturada_en') }}" class="form-control @error('capturada_en') is-invalid @enderror">
            @error('capturada_en')<div class="invalid-feedback">{{ $message }}</div>@enderror
          </div>

          <div class="col-md-4">
            <label class="form-label">Estado</label>
            <select name="estado" class="form-select @error('estado') is-invalid @enderror" required>
              <option value="programada" {{ old('estado') == 'programada' ? 'selected' : '' }}>Programada</option>
              <option value="cancelada" {{ old('estado') == 'cancelada' ? 'selected' : '' }}>Cancelada</option>
              <option value="realizada" {{ old('estado') == 'realizada' ? 'selected' : '' }}>Realizada</option>
            </select>
            @error('estado')<div class="invalid-feedback">{{ $message }}</div>@enderror
          </div>
        </div>

        <div class="mt-4 d-flex gap-2">
          <a href="{{ route('calendario.index') }}" class="btn btn-secondary">
            <i class="fa fa-arrow-left"></i> Cancelar
          </a>
          <button type="submit" class="btn btn-primary">
            <i class="fa fa-save"></i> Guardar
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
