@extends('layouts.app')

@section('title','Calendario de Actividades')

@section('content')
<div class="container-fluid py-3">
  <div class="card card-outline card-primary shadow-sm">
    <div class="card-header d-flex justify-content-between align-items-center">
      <h3 class="card-title mb-0">
        <i class="fa fa-calendar-alt me-1"></i> Calendario de actividades
      </h3>
      @can('actividades.crear')
      <a href="{{ route('actividades.create') }}" class="btn btn-primary btn-sm">
        <i class="fa fa-plus"></i> Nueva actividad
      </a>
      @endcan
    </div>
    <div class="card-body p-0">
      <div id="calendar"></div>
    </div>
  </div>
</div>

<!-- Modal para detalle -->
<div class="modal fade" id="eventoModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header bg-primary text-white">
        <h5 class="modal-title"><i class="fa fa-info-circle me-1"></i> Detalle de actividad</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <dl class="row mb-0">
          <dt class="col-sm-3">Título</dt>
          <dd class="col-sm-9" id="evt-titulo"></dd>

          <dt class="col-sm-3">Descripción</dt>
          <dd class="col-sm-9" id="evt-descripcion"></dd>

          <dt class="col-sm-3">Lugar</dt>
          <dd class="col-sm-9" id="evt-lugar"></dd>

          <dt class="col-sm-3">Inicio</dt>
          <dd class="col-sm-9" id="evt-inicio"></dd>

          <dt class="col-sm-3">Fin</dt>
          <dd class="col-sm-9" id="evt-fin"></dd>

          <dt class="col-sm-3">Estado</dt>
          <dd class="col-sm-9" id="evt-estado"></dd>
        </dl>
      </div>
      <div class="modal-footer">
        <a href="#" id="evt-ver" class="btn btn-info btn-sm"><i class="fa fa-eye"></i> Ver</a>
        <a href="#" id="evt-editar" class="btn btn-success btn-sm"><i class="fa fa-pen"></i> Editar</a>
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
      </div>
    </div>
  </div>
</div>
@endsection

@push('styles')
<link href="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.11/index.global.min.css" rel="stylesheet">
<style>
  #calendar { min-height: 85vh; padding:1rem; }
  .fc-toolbar-title { font-weight:700; font-size:1.4rem; }
  .fc-day-today { background: rgba(25,118,210,0.08)!important; }
  .fc-event { cursor:pointer; border-radius:6px; font-weight:600; }
  .fc-event-time { font-weight:700; }
</style>
@endpush

@push('styles')
<link href="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.11/index.global.min.css" rel="stylesheet">
<style>
  #calendar { min-height: 85vh; padding:1rem; }
  .fc-toolbar-title { font-weight:700; font-size:1.4rem; }
  .fc-day-today { background: rgba(25,118,210,0.08)!important; }
  .fc-event { cursor:pointer; border-radius:6px; font-weight:600; }
  .fc-event-time { font-weight:700; }
</style>
@endpush

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.11/index.global.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
  const calendarEl = document.getElementById('calendar');
  const calendar = new FullCalendar.Calendar(calendarEl, {
    locale: 'es',
    initialView: 'dayGridMonth',
    height: 'auto',
    selectable: true,
    editable: false,
    headerToolbar: {
      left: 'prev,next today',
      center: 'title',
      right: 'dayGridMonth,timeGridWeek,timeGridDay,listMonth'
    },
    buttonText: {
      today: 'Hoy', month: 'Mes', week: 'Semana', day: 'Día', list: 'Lista'
    },

    events: '{{ route("actividades.feed") }}',

    eventClick: function(info) {
      info.jsEvent.preventDefault();
      const e = info.event;

      // Texto principal
      document.getElementById('evt-titulo').textContent      = e.title || '(sin título)';
      document.getElementById('evt-descripcion').textContent = e.extendedProps?.descripcion || '(sin descripción)';
      document.getElementById('evt-lugar').textContent       = e.extendedProps?.lugar || '(no especificado)';

      // Fechas
      const inicio = e.start ? e.start.toLocaleString('es-MX') : '';
      const fin    = e.end ? e.end.toLocaleString('es-MX') : '(no definido)';
      document.getElementById('evt-inicio').textContent = inicio;
      document.getElementById('evt-fin').textContent    = fin;

      // Estado (con default para evitar "undefined")
      const estado = e.extendedProps?.estado || 'programada';
      document.getElementById('evt-estado').innerHTML = estadoBadge(estado);

      // Botones
      document.getElementById('evt-ver').href    = e.url || '#';
      document.getElementById('evt-editar').href = e.extendedProps?.editUrl || '#';

      new bootstrap.Modal(document.getElementById('eventoModal')).show();
    },

    eventDidMount: function(info) {
      new bootstrap.Tooltip(info.el, {
        title: info.event.title,
        placement: 'top',
        trigger: 'hover',
        container: 'body'
      });
    }
  });

  calendar.render();

  function estadoBadge(estado){
    switch(estado){
      case 'programada': return '<span class="badge bg-primary">Programada</span>';
      case 'cancelada':  return '<span class="badge bg-danger">Cancelada</span>';
      case 'realizada':  return '<span class="badge bg-success">Realizada</span>';
      default:           return '<span class="badge bg-secondary">'+ (estado ?? 'N/D') +'</span>';
    }
  }
});
</script>
@endpush
