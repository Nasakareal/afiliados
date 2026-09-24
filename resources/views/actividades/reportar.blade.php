@extends('layouts.app')

@section('title','Reportar mi actividad')

@push('styles')
<style>
  .report-shell{max-width:920px}.report-hero{background:linear-gradient(125deg,#34121f,#8b1538 58%,#d91785);color:#fff;border-radius:22px;padding:25px 28px;box-shadow:0 18px 45px rgba(73,12,37,.16)}
  .report-card{border:0;border-radius:20px;box-shadow:0 12px 38px rgba(27,37,54,.08)}.report-card .form-label{font-weight:700;font-size:.83rem;color:#384356}.report-card .form-control,.report-card .form-select{border-radius:11px;padding:.72rem .85rem;border-color:#dfe4eb}.report-card .form-control:focus,.report-card .form-select:focus{border-color:#d91785;box-shadow:0 0 0 .2rem rgba(217,23,133,.12)}
</style>
@endpush

@section('content')
<div class="container report-shell pb-5">
  <div class="report-hero mb-3">
    <div class="small text-uppercase fw-bold opacity-75 mb-1" style="letter-spacing:.13em"><i class="fa-solid fa-clipboard-check me-2"></i>Reporte del equipo</div>
    <h1 class="h3 fw-bold mb-2">Sube una actividad que tú realizaste</h1>
    <p class="mb-0 text-white-50">No se publicará como actividad oficial. Quedará identificada como reporte del equipo y pendiente de revisión administrativa.</p>
  </div>

  <div class="card report-card"><div class="card-body p-4 p-md-5">
    <form action="{{ route('actividades.reportes.store') }}" method="POST" enctype="multipart/form-data">
      @csrf
      <div class="row g-3">
        <div class="col-md-8"><label class="form-label">¿Qué actividad realizaste?</label><input name="titulo" value="{{ old('titulo') }}" class="form-control @error('titulo') is-invalid @enderror" placeholder="Ej. Reunión vecinal en la colonia Centro" required>@error('titulo')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
        <div class="col-md-4"><label class="form-label">Tipo</label><select name="tipo" class="form-select @error('tipo') is-invalid @enderror" required><option value="">Selecciona…</option>@foreach(['Recorrido','Reunión','Gestión','Brigada','Capacitación','Módulo','Jornada comunitaria','Otra'] as $tipo)<option {{ old('tipo')===$tipo?'selected':'' }}>{{ $tipo }}</option>@endforeach</select>@error('tipo')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
        <div class="col-md-6"><label class="form-label">Colonia</label><input name="colonia" value="{{ old('colonia') }}" class="form-control @error('colonia') is-invalid @enderror" required>@error('colonia')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
        <div class="col-md-6"><label class="form-label">Lugar o punto de referencia</label><input name="lugar" value="{{ old('lugar') }}" class="form-control @error('lugar') is-invalid @enderror">@error('lugar')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
        <div class="col-md-4"><label class="form-label">Inicio</label><input type="datetime-local" name="inicio" value="{{ old('inicio') }}" max="{{ now()->format('Y-m-d\TH:i') }}" class="form-control @error('inicio') is-invalid @enderror" required>@error('inicio')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
        <div class="col-md-4"><label class="form-label">Fin</label><input type="datetime-local" name="fin" value="{{ old('fin') }}" class="form-control @error('fin') is-invalid @enderror">@error('fin')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
        <div class="col-md-4"><label class="form-label">Asistentes aproximados</label><input type="number" min="1" name="asistentes" value="{{ old('asistentes',1) }}" class="form-control @error('asistentes') is-invalid @enderror" required>@error('asistentes')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
        <div class="col-12"><label class="form-label">¿Qué se hizo y qué resultado tuvo?</label><textarea name="descripcion" rows="4" class="form-control @error('descripcion') is-invalid @enderror" placeholder="Describe brevemente el trabajo realizado…">{{ old('descripcion') }}</textarea>@error('descripcion')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
        <div class="col-md-8"><label class="form-label">Evidencia</label><input type="file" name="evidencia" accept="image/jpeg,image/png,image/webp,application/pdf" class="form-control @error('evidencia') is-invalid @enderror" required><div class="form-text">Foto o PDF, máximo 10 MB. Sólo usuarios autorizados podrán verla.</div>@error('evidencia')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
        <div class="col-md-4 d-flex align-items-end"><div class="form-check border rounded-3 p-3 ps-5 w-100"><input class="form-check-input" type="checkbox" value="1" name="yo_asisti" id="yoAsisti" {{ old('yo_asisti',1)?'checked':'' }}><label class="form-check-label fw-bold" for="yoAsisti">Yo asistí a esta actividad</label></div></div>
      </div>
      <div class="d-flex flex-wrap gap-2 justify-content-end mt-4"><a href="{{ route('actividades.index') }}" class="btn btn-light px-4">Cancelar</a><button class="btn btn-granate px-4"><i class="fa-solid fa-paper-plane me-2"></i>Enviar reporte</button></div>
    </form>
  </div></div>
</div>
@endsection
