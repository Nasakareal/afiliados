@extends('layouts.app')

@section('title', 'Personas convencidas')

@section('content')
<div class="container-fluid px-xl-4 py-3">
  <div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-3">
    <div>
      <h1 class="h5 fw-bold mb-1 text-uppercase">Personas convencidas</h1>
      <p class="text-muted small mb-2">Detalle de las personas incluidas en el avance filtrado y su alcance electoral.</p>
      <div class="d-flex flex-wrap gap-2">
        @foreach($filtros as $nombre => $valor)
          <span class="badge bg-light text-dark border">{{ $nombre }}: {{ $valor }}</span>
        @endforeach
      </div>
    </div>

    <div class="d-flex flex-wrap gap-2">
      <a href="{{ route('avance.index', $backQuery) }}" class="btn btn-sm btn-outline-secondary">
        <i class="fa-solid fa-arrow-left me-1"></i> Volver al avance
      </a>
      <a href="{{ route('avance.export.xlsx', $backQuery) }}" class="btn btn-sm btn-success">
        <i class="fa-solid fa-file-excel me-1"></i> Descargar Excel
      </a>
    </div>
  </div>

  <div class="card border-0 shadow-sm">
    <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
      <span class="fw-bold">Convencidos y distritos</span>
      <span class="small">{{ number_format($personas->total()) }} registro(s)</span>
    </div>
    <div class="table-responsive">
      <table class="table table-striped table-hover align-middle mb-0">
        <thead class="table-light">
          <tr>
            <th>Persona</th>
            <th>Referente</th>
            <th>Capturista</th>
            <th>Sección</th>
            <th>Municipio</th>
            <th class="text-center">Distrito local</th>
            <th class="text-center">Distrito federal</th>
          </tr>
        </thead>
        <tbody>
          @forelse($personas as $persona)
            <tr>
              <td class="fw-semibold">
                {{ trim($persona->nombre.' '.$persona->apellido_paterno.' '.$persona->apellido_materno) }}
              </td>
              <td>{{ trim((string)$persona->referente) ?: '—' }}</td>
              <td>{{ $persona->capturista ?: '—' }}</td>
              <td>{{ str_pad($persona->seccion, 4, '0', STR_PAD_LEFT) }}</td>
              <td>{{ $persona->municipio }}</td>
              <td class="text-center">
                <span class="badge bg-primary">DL {{ str_pad($persona->distrito_local, 2, '0', STR_PAD_LEFT) }}</span>
              </td>
              <td class="text-center">
                <span class="badge bg-secondary">DFn {{ str_pad($persona->distrito_federal, 2, '0', STR_PAD_LEFT) }}</span>
              </td>
            </tr>
          @empty
            <tr>
              <td colspan="7" class="py-5 text-center text-muted">No hay personas para los filtros seleccionados.</td>
            </tr>
          @endforelse
        </tbody>
      </table>
    </div>
    @if($personas->hasPages())
      <div class="card-footer bg-white">
        {{ $personas->links() }}
      </div>
    @endif
  </div>
</div>
@endsection
