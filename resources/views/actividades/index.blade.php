@extends('layouts.app')

@section('title','Actividades')

@section('content')
<div class="container-xl">
  <div class="card card-outline card-primary shadow-sm">
    <div class="card-header d-flex justify-content-between align-items-center">
      <h3 class="card-title mb-0">
        <i class="fa fa-list-ul me-1"></i> Actividades
      </h3>
      <div class="d-flex gap-2">
        <a href="{{ route('actividades.create') }}" class="btn btn-primary btn-sm">
          <i class="fa fa-plus"></i> Nueva actividad
        </a>
        <a href="{{ route('calendario.index') }}" class="btn btn-outline-primary btn-sm">
          <i class="fa fa-calendar-alt"></i> Ver calendario
        </a>
      </div>
    </div>

    <div class="card-body p-0">
      @php
        $estadoColor = [
          'programada' => 'primary',
          'cancelada'  => 'danger',
          'realizada'  => 'success',
        ];
      @endphp

      @if($actividades->count() === 0)
        <div class="p-4 text-center text-muted">
          <i class="fa fa-info-circle me-1"></i> No hay actividades registradas.
        </div>
      @else
        <div class="table-responsive">
          <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
              <tr>
                <th style="width:32px">#</th>
                <th>Título</th>
                <th>Lugar</th>
                <th>Inicio</th>
                <th>Fin</th>
                <th>Todo el día</th>
                <th>Estado</th>
                <th style="width:160px">Acciones</th>
              </tr>
            </thead>
            <tbody>
              @foreach($actividades as $a)
                <tr>
                  <td>{{ $a->id }}</td>
                  <td>
                    <a href="{{ route('actividades.show', $a->id) }}" class="fw-semibold text-decoration-none">
                      {{ $a->titulo }}
                    </a>
                  </td>
                  <td>{{ $a->lugar ?: '—' }}</td>
                  <td>{{ optional($a->inicio)->format('d/m/Y, h:i a') }}</td>
                  <td>{{ $a->fin ? $a->fin->format('d/m/Y, h:i a') : '—' }}</td>
                  <td>
                    @if($a->all_day)
                      <span class="badge bg-dark">Sí</span>
                    @else
                      <span class="badge bg-secondary">No</span>
                    @endif
                  </td>
                  <td>
                    @php $cls = $estadoColor[$a->estado] ?? 'secondary'; @endphp
                    <span class="badge bg-{{ $cls }}">
                      {{ ucfirst($a->estado ?? 'N/D') }}
                    </span>
                  </td>
                  <td>
                    <div class="btn-group btn-group-sm" role="group">
                      <a href="{{ route('actividades.show', $a->id) }}" class="btn btn-info">
                        <i class="fa fa-eye"></i>
                      </a>
                      <a href="{{ route('actividades.edit', $a->id) }}" class="btn btn-success">
                        <i class="fa fa-pen"></i>
                      </a>
                      <form action="{{ route('actividades.destroy', $a->id) }}" method="POST"
                            onsubmit="return confirm('¿Eliminar la actividad \"{{ $a->titulo }}\"?');">
                        @csrf @method('DELETE')
                        <button class="btn btn-danger">
                          <i class="fa fa-trash"></i>
                        </button>
                      </form>
                    </div>
                  </td>
                </tr>
              @endforeach
            </tbody>
          </table>
        </div>

        <div class="p-3">
          {{ $actividades->links() }}
        </div>
      @endif
    </div>
  </div>
</div>
@endsection
