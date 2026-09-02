@extends('layouts.app')

@section('title','Referente/Referencia')

@section('content_header')
  <h1 class="text-center w-100">Referente/Referencia</h1>
@endsection

@section('content')
<div class="container-xl">
  <div class="card card-outline card-primary">
    <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-2">
      <h3 class="card-title mb-0">Referentes / Referencias registradas</h3>

      <div class="d-flex flex-wrap gap-2">
        @php
          $exportParams = request()->query();
          $exportParams['page'] = $afiliados->currentPage();
          $excelParams = request()->except('page');
        @endphp

        <a href="{{ route('afiliados.exportar_pagina', $exportParams) }}" class="btn btn-outline-danger btn-sm">
          <i class="fa fa-file-pdf"></i> Exportar esta página
        </a>

        <a href="{{ route('afiliados.exportar_excel', $excelParams) }}" class="btn btn-outline-success btn-sm">
          <i class="fa fa-file-excel"></i> Exportar Excel
        </a>

        @can('afiliados.crear')
        <a href="{{ route('registro') }}" class="btn btn-primary btn-sm">
          <i class="fa fa-plus"></i> Nuevo
        </a>
        @endcan
      </div>
    </div>

    <div class="card-body">

      <form method="GET" action="{{ route('afiliados.index') }}" class="row g-2 mb-4">

        <div class="col-12 col-md-4">
          <input
            type="text"
            name="q"
            value="{{ $q ?? '' }}"
            class="form-control form-control-sm"
            placeholder="Buscar: nombre, teléfono, email o clave"
          >
        </div>

        <div class="col-6 col-md-2">
          <input
            type="text"
            name="seccion"
            value="{{ $seccion ?? '' }}"
            class="form-control form-control-sm"
            placeholder="Sección"
          >
        </div>

        <div class="col-6 col-md-2">
          <input
            type="text"
            name="cve_mun"
            value="{{ $cveMun ?? '' }}"
            class="form-control form-control-sm"
            placeholder="CVE MUN"
          >
        </div>

        <div class="col-6 col-md-2">
          <input
            type="text"
            name="municipio"
            value="{{ $municipio ?? '' }}"
            class="form-control form-control-sm"
            placeholder="Municipio"
          >
        </div>

        <div class="col-6 col-md-2">
          <select name="estatus" class="form-select form-select-sm">
            <option value="">Afiliado</option>
            <option value="validado" {{ ($estatus ?? '') === 'validado' ? 'selected' : '' }}>Sí</option>
            <option value="descartado" {{ ($estatus ?? '') === 'descartado' ? 'selected' : '' }}>No</option>
          </select>
        </div>

        <div class="col-12 col-md-3">
          <input
            type="text"
            name="referente"
            value="{{ $referente ?? '' }}"
            class="form-control form-control-sm"
            placeholder="Referente / Referencia"
          >
        </div>

        <div class="col-12 col-md-3">
          <input
            type="text"
            name="capturista"
            value="{{ $capturista ?? '' }}"
            class="form-control form-control-sm"
            placeholder="Nombre del capturista"
          >
        </div>

        <div class="col-6 col-md-2">
          <select
            name="per_page"
            class="form-select form-select-sm"
            onchange="this.form.submit()"
            aria-label="Registros por página"
          >
            @foreach($perPageOptions as $option)
              <option
                value="{{ $option }}"
                {{ (int)request('per_page', 25) === $option ? 'selected' : '' }}
              >
                {{ $option }} por página
              </option>
            @endforeach
          </select>
        </div>

        <div class="col-6 col-md-2">
          <button class="btn btn-outline-primary btn-sm w-100">
            <i class="fa fa-search"></i> Filtrar
          </button>
        </div>

        <div class="col-6 col-md-2">
          <a href="{{ route('afiliados.index') }}" class="btn btn-outline-secondary btn-sm w-100">
            <i class="fa fa-eraser"></i> Limpiar
          </a>
        </div>

      </form>

      @php
        $cls = function($st){
          $st = strtolower((string)$st);

          return match($st){
            'validado'   => 'badge bg-success',
            'pendiente'  => 'badge bg-warning text-dark',
            'descartado' => 'badge bg-danger',
            default      => 'badge bg-secondary'
          };
        };

        $txt = function($st){
          $st = strtolower((string)$st);

          return match($st){
            'validado'   => 'Sí',
            'descartado' => 'No',
            'pendiente'  => 'Pendiente',
            default      => '—'
          };
        };
      @endphp

      <div class="table-responsive">
        <table id="tblAfiliados" class="table table-striped table-bordered table-hover table-sm align-middle">
          <thead>
            <tr>
              <th class="text-center" style="width:60px">#</th>
              <th>Nombre</th>
              <th>Contacto</th>
              <th>Clave / indicador</th>
              <th>Municipio</th>
              <th>Sección</th>
              <th>Referente/Referencia</th>
              <th>Capturista</th>
              <th>Afiliado</th>
              <th>Creación</th>
              <th class="text-center" style="width:140px">Acciones</th>
            </tr>
          </thead>

          <tbody>
            @forelse($afiliados as $i => $a)
            <tr>
              <td class="text-center">
                {{ ($afiliados->currentPage() - 1) * $afiliados->perPage() + $i + 1 }}
              </td>

              <td style="min-width:220px">
                <strong>
                  {{ $a->nombre }}
                  {{ $a->apellido_paterno }}
                  {{ $a->apellido_materno }}
                </strong>

                <div class="text-muted small">
                  @if($a->edad)
                    {{ $a->edad }} años
                  @endif

                  @if($a->edad && $a->sexo)
                    ·
                  @endif

                  @if($a->sexo)
                    {{ $a->sexo }}
                  @endif
                </div>
              </td>

              <td style="min-width:180px">
                @if($a->telefono)
                  <div>
                    <i class="fa fa-phone"></i> {{ $a->telefono }}
                  </div>
                @endif

                @if($a->email)
                  <div class="small text-muted">
                    <i class="fa fa-envelope"></i> {{ $a->email }}
                  </div>
                @endif
              </td>

              <td style="min-width:170px">
                <code>{{ $a->clave_elector ?: '—' }}</code>

                @if($a->tipo_vinculo)
                  <div class="small text-muted">
                    {{ $tiposVinculo[$a->tipo_vinculo] ?? strtoupper($a->tipo_vinculo) }}

                    @if($a->tipo_vinculo === 'mov' && $a->numero_mov)
                      #{{ $a->numero_mov }}
                    @endif
                  </div>
                @endif
              </td>

              <td style="min-width:160px">
                {{ $a->s_municipio ?? $a->municipio }}

                @if(!empty($a->s_cve_mun) || !empty($a->cve_mun))
                  <div class="small text-muted">
                    CVE: {{ $a->s_cve_mun ?? $a->cve_mun }}
                  </div>
                @endif
              </td>

              <td class="text-nowrap">
                {{ $a->seccion }}

                <div class="small text-muted">
                  @if(isset($a->s_distrito_local))
                    D. Local: {{ $a->s_distrito_local }}
                  @endif

                  @if(isset($a->s_distrito_federal))
                    · D. Fed: {{ $a->s_distrito_federal }}
                  @endif
                </div>
              </td>

              <td style="min-width:200px">
                {{ $a->perfil ?? '—' }}
              </td>

              <td style="min-width:160px">
                {{ $a->capturista_nombre ?? optional($a->capturista)->name ?? '—' }}
              </td>

              <td class="text-center">
                <span class="{{ $cls($a->estatus) }}">
                  {{ $txt($a->estatus) }}
                </span>
              </td>

              <td class="text-nowrap">
                {{ optional($a->created_at)->format('Y-m-d H:i') }}
              </td>

              <td class="text-center text-nowrap">
                <div class="btn-group">
                  @can('afiliados.ver')
                  <a
                    href="{{ route('afiliados.show', $a->id) }}"
                    class="btn btn-info btn-sm"
                    title="Ver"
                  >
                    <i class="fa fa-eye"></i>
                  </a>
                  @endcan

                  @can('afiliados.editar')
                  <a
                    href="{{ route('afiliados.edit', $a->id) }}"
                    class="btn btn-success btn-sm"
                    title="Editar"
                  >
                    <i class="fa fa-pen"></i>
                  </a>
                  @endcan

                  @can('afiliados.borrar')
                  <form
                    action="{{ route('afiliados.destroy', $a->id) }}"
                    method="POST"
                    id="formDel-{{ $a->id }}"
                  >
                    @csrf
                    @method('DELETE')

                    <button
                      type="button"
                      class="btn btn-danger btn-sm"
                      onclick="confirmarEliminar('{{ $a->id }}', this)"
                      title="Eliminar"
                    >
                      <i class="fa fa-trash"></i>
                    </button>
                  </form>
                  @endcan
                </div>
              </td>
            </tr>
            @empty
            <tr>
              <td colspan="11" class="text-center text-muted py-4">
                No hay registros con los filtros seleccionados.
              </td>
            </tr>
            @endforelse
          </tbody>
        </table>
      </div>

      <div class="mt-3 d-flex flex-wrap justify-content-between align-items-center gap-2">
        <div class="small text-muted">
          Registros {{ $afiliados->firstItem() ?: 0 }}–{{ $afiliados->lastItem() ?: 0 }}
          · Página {{ $afiliados->currentPage() }}.
          Se omite el conteo total para mantener buen rendimiento con millones de registros.
        </div>

        <div>
          {{ $afiliados->links() }}
        </div>
      </div>

    </div>
  </div>
</div>
@endsection

@section('js')
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
function confirmarEliminar(id, btn){
  const form = document.getElementById('formDel-' + id);
  btn.disabled = true;

  if(typeof Swal === 'undefined'){
    if(confirm('¿Eliminar afiliado?')){
      form.submit();
    }else{
      btn.disabled = false;
    }

    return;
  }

  Swal.fire({
    title:'Eliminar afiliado',
    text:'¿Deseas eliminarlo?',
    icon:'warning',
    showDenyButton:true,
    confirmButtonText:'Eliminar',
    denyButtonText:'Cancelar',
    confirmButtonColor:'#e3342f'
  }).then(r => {
    if(r.isConfirmed){
      form.submit();
    }else{
      btn.disabled = false;
    }
  });
}
</script>

@if(session('status'))
<script>
Swal.fire({
  icon:'success',
  title:@json(session('status')),
  timer:2500,
  showConfirmButton:false
});
</script>
@endif
@endsection
