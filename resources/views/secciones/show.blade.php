@extends('layouts.app')

@section('title', 'Sección '.$seccion->seccion.' · '.$seccion->municipio)

@section('content_header')
  <h1 class="text-center w-100">Sección: {{ $seccion->seccion }} — {{ $seccion->municipio }}</h1>
@endsection

@section('content')
<div class="container-xl">
  <div class="card card-outline card-primary">
    <div class="card-header d-flex justify-content-between align-items-center">
      <h3 class="card-title">Detalle de la sección</h3>
      <div class="btn-group">
        <a href="{{ route('secciones.index') }}" class="btn btn-outline-secondary btn-sm">
          <i class="fa fa-arrow-left"></i> Regresar
        </a>

        @can('secciones.editar')
        <a href="{{ route('secciones.edit', $seccion->id ?? $seccion) }}" class="btn btn-success btn-sm">
          <i class="fa fa-pen"></i> Editar
        </a>
        @endcan

        @can('secciones.borrar')
        <form action="{{ route('secciones.destroy', $seccion->id ?? $seccion) }}" method="POST" id="formDel-{{ $seccion->id ?? $seccion->getKey() }}" class="d-inline">
          @csrf @method('DELETE')
          <button type="button" class="btn btn-danger btn-sm" onclick="confirmarEliminar('{{ $seccion->id ?? $seccion->getKey() }}', this)">
            <i class="fa fa-trash"></i> Eliminar
          </button>
        </form>
        @endcan
      </div>
    </div>

    <div class="card-body">
      <div class="row g-3">
        <div class="col-12 col-lg-8">
          <table class="table table-sm table-striped table-bordered">
            <tbody>
              <tr>
                <th style="width:220px">Municipio</th>
                <td><strong>{{ $seccion->municipio }}</strong></td>
              </tr>
              <tr>
                <th>Sección</th>
                <td><strong>{{ $seccion->seccion }}</strong></td>
              </tr>
              <tr>
                <th>Distrito Federal</th>
                <td>
                  @if(!is_null($seccion->distrito_federal))
                    <span class="badge bg-secondary">{{ $seccion->distrito_federal }}</span>
                  @else
                    <span class="text-muted">—</span>
                  @endif
                </td>
              </tr>
              <tr>
                <th>Distrito Local</th>
                <td>
                  @if(!is_null($seccion->distrito_local))
                    <span class="badge bg-secondary">{{ $seccion->distrito_local }}</span>
                  @else
                    <span class="text-muted">—</span>
                  @endif
                </td>
              </tr>

              {{-- Extras opcionales --}}
              @if(!is_null($seccion->lista_nominal))
              <tr>
                <th>Lista nominal</th>
                <td>{{ number_format($seccion->lista_nominal) }}</td>
              </tr>
              @endif

              @if(!is_null($seccion->centroid_lat) && !is_null($seccion->centroid_lng))
              <tr>
                <th>Centroide</th>
                <td>
                  <code>{{ $seccion->centroid_lat }}, {{ $seccion->centroid_lng }}</code>
                  <a class="ms-2" target="_blank"
                     href="https://maps.google.com/?q={{ $seccion->centroid_lat }},{{ $seccion->centroid_lng }}">
                    Ver en Maps <i class="fa fa-external-link-alt"></i>
                  </a>
                </td>
              </tr>
              @endif

              <tr>
                <th>Entidad</th>
                <td>
                  {{ $seccion->cve_ent ?? '16' }}
                  <span class="text-muted small ms-2">Michoacán</span>
                </td>
              </tr>

              {{-- Fechas --}}
              <tr>
                <th>Creación</th>
                <td>{{ optional($seccion->created_at)->format('Y-m-d H:i') ?? '—' }}</td>
              </tr>
              <tr>
                <th>Última actualización</th>
                <td>{{ optional($seccion->updated_at)->format('Y-m-d H:i') ?? '—' }}</td>
              </tr>
            </tbody>
          </table>
        </div>

        <div class="col-12 col-lg-4">
          <div class="p-3 border rounded">
            <h5 class="mb-3">Acciones rápidas</h5>

            {{-- Ir a Afiliados filtrados por esta sección --}}
            <div class="d-grid gap-2">
              <a class="btn btn-outline-primary btn-sm"
                 href="{{ route('afiliados.index', ['seccion' => $seccion->seccion, 'cve_mun' => $seccion->cve_mun]) }}">
                <i class="fa fa-users"></i> Ver afiliados de esta sección
              </a>

              @if(!is_null($seccion->centroid_lat) && !is_null($seccion->centroid_lng))
              <a class="btn btn-outline-secondary btn-sm" target="_blank"
                 href="https://www.openstreetmap.org/?mlat={{ $seccion->centroid_lat }}&mlon={{ $seccion->centroid_lng }}#map=12/{{ $seccion->centroid_lat }}/{{ $seccion->centroid_lng }}">
                <i class="fa fa-map-marker-alt"></i> Ver centroide en OSM
              </a>
              @endif
            </div>

            {{-- Info pequeña --}}
            <div class="mt-3 small text-muted">
              <div><strong>CVE MUN:</strong> {{ $seccion->cve_mun ?? '—' }}</div>
              <div><strong>Clave compuesta:</strong> 16{{ $seccion->cve_mun ?? '—' }}-{{ $seccion->seccion }}</div>
            </div>
          </div>
        </div>
      </div> {{-- row --}}
    </div>
  </div>
</div>
@endsection

@section('js')
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
function confirmarEliminar(id, btn){
  const form = document.getElementById('formDel-'+id);
  btn.disabled = true;
  if(typeof Swal==='undefined'){
    if(confirm('¿Eliminar sección?')) form.submit(); else btn.disabled=false;
    return;
  }
  Swal.fire({
    title:'Eliminar sección', text:'¿Deseas eliminarla?', icon:'warning',
    showDenyButton:true, confirmButtonText:'Eliminar', denyButtonText:'Cancelar',
    confirmButtonColor:'#e3342f'
  }).then(r=>{ if(r.isConfirmed) form.submit(); else btn.disabled=false; });
}
</script>

@if (session('status'))
<script>
Swal.fire({icon:'success', title:@json(session('status')), timer:2500, showConfirmButton:false});
</script>
@endif
@if (session('error'))
<script>
Swal.fire({icon:'error', title:'Error', text:@json(session('error'))});
</script>
@endif
@endsection
