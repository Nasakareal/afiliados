@extends('layouts.app')

@section('title', 'Lonas')

@section('content')
<div class="container-xl">
  <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
    <div>
      <h1 class="h3 mb-1">Lonas registradas</h1>
      <p class="text-muted mb-0">
        {{ $lonas->total() }} {{ $lonas->total() === 1 ? 'lona encontrada' : 'lonas encontradas' }}
      </p>
    </div>

    <div class="d-flex flex-wrap gap-2">
      <a href="{{ route('lonas.export.xlsx', array_filter([
          'q' => $q,
          'seccion' => $seccion,
          'capturista' => $capturista
      ], fn ($value) => $value !== '')) }}"
         class="btn btn-outline-success">
        <i class="fa-solid fa-file-excel me-1"></i> Descargar Excel
      </a>

      <a href="{{ route('lonas.map') }}" class="btn btn-outline-primary">
        <i class="fa-solid fa-map-location-dot me-1"></i> Ver mapa
      </a>

      @can('lonas.crear')
      <a href="{{ route('lonas.create') }}" class="btn btn-granate">
        <i class="fa-solid fa-plus me-1"></i> Capturar lona
      </a>
      @endcan
    </div>
  </div>

  <div class="card shadow-sm border-0">
    <div class="card-body">
      <form method="GET" action="{{ route('lonas.index') }}" class="row g-2 mb-3">
        <div class="col-md-4">
          <input
            type="search"
            name="q"
            value="{{ $q }}"
            class="form-control"
            placeholder="Buscar por dirección, responsable o capturista"
          >
        </div>

        <div class="col-md-2">
          <input
            type="text"
            name="seccion"
            value="{{ $seccion }}"
            class="form-control"
            placeholder="Sección"
          >
        </div>

        <div class="col-md-4">
          <select name="capturista" class="form-select">
            <option value="">Todos los usuarios</option>

            @foreach($capturistas as $usuario)
              <option
                value="{{ $usuario->id }}"
                @selected((string) $capturista === (string) $usuario->id)
              >
                {{ $usuario->name }} ({{ $usuario->lonas_capturadas_count }})
              </option>
            @endforeach
          </select>
        </div>

        <div class="col-md-2 d-grid">
          <button class="btn btn-outline-primary">
            <i class="fa-solid fa-magnifying-glass me-1"></i> Buscar
          </button>
        </div>
      </form>

      @if($q !== '' || $seccion !== '' || $capturista !== '')
        <div class="d-flex justify-content-between align-items-center mb-3">
          <span class="text-muted">
            Resultado del filtro: <strong>{{ $lonas->total() }}</strong>
          </span>

          <a href="{{ route('lonas.index') }}" class="btn btn-sm btn-outline-secondary">
            <i class="fa-solid fa-xmark me-1"></i> Limpiar filtros
          </a>
        </div>
      @endif

      <div class="table-responsive">
        <table class="table table-hover align-middle">
          <thead>
            <tr>
              <th>Foto</th>
              <th>Sección</th>
              <th>Dirección</th>
              <th>Responsable</th>
              <th>Capturó</th>
              <th>Fecha</th>
              <th class="text-end">Acciones</th>
            </tr>
          </thead>

          <tbody>
            @forelse($lonas as $lona)
              <tr>
                <td>
                  <a href="{{ route('lonas.show', $lona) }}">
                    <img
                      src="{{ route('lonas.foto', $lona) }}"
                      alt="Lona en sección {{ $lona->seccion }}"
                      class="rounded"
                      loading="lazy"
                      style="width:76px;height:58px;object-fit:cover;"
                    >
                  </a>
                </td>

                <td>
                  <span class="badge bg-secondary">{{ $lona->seccion }}</span>
                </td>

                <td style="min-width:240px;">
                  {{ $lona->direccion }}
                </td>

                <td>
                  {{ $lona->responsable }}
                </td>

                <td>
                  {{ optional($lona->capturista)->name ?? '—' }}
                </td>

                <td class="text-nowrap">
                  {{ optional($lona->created_at)->format('d/m/Y H:i') }}
                </td>

                <td class="text-end text-nowrap">
                  <a
                    href="{{ route('lonas.show', $lona) }}"
                    class="btn btn-sm btn-outline-primary"
                    title="Ver"
                  >
                    <i class="fa-solid fa-eye"></i>
                  </a>

                  @can('lonas.editar')
                  <a
                    href="{{ route('lonas.edit', $lona) }}"
                    class="btn btn-sm btn-outline-success"
                    title="Editar"
                  >
                    <i class="fa-solid fa-pen"></i>
                  </a>
                  @endcan

                  @can('lonas.borrar')
                  <form
                    action="{{ route('lonas.destroy', $lona) }}"
                    method="POST"
                    class="d-inline"
                    onsubmit="return confirm('¿Eliminar esta lona y su fotografía?')"
                  >
                    @csrf
                    @method('DELETE')

                    <button class="btn btn-sm btn-outline-danger" title="Eliminar">
                      <i class="fa-solid fa-trash"></i>
                    </button>
                  </form>
                  @endcan
                </td>
              </tr>
            @empty
              <tr>
                <td colspan="7" class="text-center text-muted py-5">
                  <i class="fa-regular fa-image fa-2x mb-2 d-block"></i>
                  No hay lonas registradas con estos filtros.
                </td>
              </tr>
            @endforelse
          </tbody>
        </table>
      </div>

      {{ $lonas->links() }}
    </div>
  </div>
</div>
@endsection
