@extends('layouts.app')

@section('title', 'Respaldos SQL')

@section('content_header')
  <h1 class="text-center w-100">Respaldos SQL</h1>
@endsection

@section('content')
<div class="container-xl">
  <div class="alert alert-info">
    <i class="fa-solid fa-shield-halved me-1"></i>
    Los archivos se guardan de forma privada en <strong>storage/app/backups_sql</strong>.
    Descarga el respaldo que necesites si debes restaurarlo con MySQL o phpMyAdmin.
  </div>

  @if (session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
  @endif

  @if (session('error'))
    <div class="alert alert-danger">{{ session('error') }}</div>
  @endif

  @if ($errors->any())
    <div class="alert alert-danger">Revisa el archivo seleccionado. Solo se aceptan respaldos .sql o .sql.gz.</div>
  @endif

  <div class="card card-outline card-primary mb-4">
    <div class="card-header">
      <h3 class="card-title m-0">Guardar un respaldo</h3>
    </div>
    <div class="card-body">
      <form action="{{ route('settings.backups_sql.upload') }}" method="POST" enctype="multipart/form-data">
        @csrf
        <div class="row g-3 align-items-end">
          <div class="col-md-9">
            <label for="backup" class="form-label">Archivo SQL</label>
            <input type="file" name="backup" id="backup"
                   class="form-control @error('backup') is-invalid @enderror"
                   accept=".sql,.gz,application/sql,application/gzip" required>
            @error('backup')
              <span class="invalid-feedback">{{ $message }}</span>
            @enderror
            <div class="form-text">Se admiten archivos con terminación .sql o .sql.gz.</div>
          </div>
          <div class="col-md-3 d-grid">
            <button type="submit" class="btn btn-success">
              <i class="fa-solid fa-upload me-1"></i> Subir respaldo
            </button>
          </div>
        </div>
      </form>
    </div>
  </div>

  <div class="card card-outline card-primary">
    <div class="card-header">
      <h3 class="card-title m-0">Respaldos almacenados</h3>
    </div>
    <div class="card-body p-0">
      @if ($files->isEmpty())
        <div class="alert alert-warning m-3 mb-3">
          Aún no hay respaldos. Sube el archivo SQL antes de borrar los afiliados temporales.
        </div>
      @else
        <div class="table-responsive">
          <table class="table table-striped table-hover align-middle mb-0">
            <thead class="table-light">
              <tr>
                <th>Archivo</th>
                <th class="text-end">Tamaño</th>
                <th>Última modificación</th>
                <th class="text-end">Acción</th>
              </tr>
            </thead>
            <tbody>
              @foreach ($files as $file)
                <tr>
                  <td class="fw-semibold">{{ $file['name'] }}</td>
                  <td class="text-end">{{ number_format($file['size'] / 1024 / 1024, 2) }} MB</td>
                  <td>{{ \Carbon\Carbon::createFromTimestamp($file['last_modified'], 'America/Mexico_City')->format('d/m/Y H:i:s') }}</td>
                  <td class="text-end">
                    <a class="btn btn-primary btn-sm"
                       href="{{ route('settings.backups_sql.download', ['file' => $file['name']]) }}">
                      <i class="fa-solid fa-download me-1"></i> Descargar
                    </a>
                  </td>
                </tr>
              @endforeach
            </tbody>
          </table>
        </div>
      @endif
    </div>
  </div>
</div>
@endsection
