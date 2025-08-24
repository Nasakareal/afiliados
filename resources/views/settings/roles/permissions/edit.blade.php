@extends('layouts.app')

@section('title','Asignar permisos al rol')

@section('content')
<div class="container-xl">
  <div class="card card-outline card-primary">
    <div class="card-header d-flex justify-content-between align-items-center">
      <h3 class="card-title mb-0">
        <i class="fa fa-key me-1"></i> Permisos del rol: <strong>{{ $role->name }}</strong>
      </h3>
      <a href="{{ route('settings.roles.index') }}" class="btn btn-secondary btn-sm">
        <i class="fa fa-arrow-left"></i> Volver
      </a>
    </div>

    <div class="card-body">
      <form action="{{ route('settings.roles.permisos.update',$role->id) }}" method="POST">
        @csrf @method('PUT')

        @error('permissions')
          <div class="alert alert-danger">{{ $message }}</div>
        @enderror

        {{-- Filtro rápido --}}
        <div class="row mb-3">
          <div class="col-md-6">
            <input id="permSearch" type="text" class="form-control" placeholder="Filtrar permisos...">
          </div>
          <div class="col-md-6 text-md-end mt-2 mt-md-0">
            <button type="button" class="btn btn-outline-secondary btn-sm" id="btnToggleAll">Marcar/Desmarcar todos</button>
          </div>
        </div>

        <div class="row g-2" id="permList">
          @foreach($permissions as $perm)
            @php
              $checked = in_array($perm->name, old('permissions',$rolePermNames));
            @endphp
            <div class="col-md-4 perm-item" data-name="{{ Str::lower($perm->name) }}">
              <div class="form-check border rounded p-2 h-100">
                <input class="form-check-input perm-check" type="checkbox"
                       name="permissions[]" value="{{ $perm->name }}"
                       id="p{{ $perm->id }}" {{ $checked ? 'checked' : '' }}>
                <label class="form-check-label" for="p{{ $perm->id }}">
                  {{ $perm->name }}
                </label>
              </div>
            </div>
          @endforeach
        </div>

        <div class="mt-4 d-flex gap-2">
          <a href="{{ route('settings.roles.index') }}" class="btn btn-secondary">Cancelar</a>
          <button class="btn btn-primary">
            <i class="fa fa-save me-1"></i> Guardar
          </button>
        </div>
      </form>
    </div>
  </div>
</div>
@endsection

@push('scripts')
<script>
  // filtro por texto
  const input = document.getElementById('permSearch');
  const items = document.querySelectorAll('#permList .perm-item');
  input?.addEventListener('input', e=>{
    const q = (e.target.value || '').toLowerCase().trim();
    items.forEach(it=>{
      const name = it.dataset.name || '';
      it.style.display = name.includes(q) ? '' : 'none';
    });
  });

  // marcar / desmarcar todos
  document.getElementById('btnToggleAll')?.addEventListener('click', ()=>{
    const checks = document.querySelectorAll('.perm-check');
    const allChecked = Array.from(checks).every(c=>c.checked);
    checks.forEach(c=> c.checked = !allChecked);
  });
</script>

@if (session('status'))
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
Swal.fire({icon:'success', title:@json(session('status')), timer:2200, showConfirmButton:false});
</script>
@endif

@if ($errors->any())
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
Swal.fire({icon:'error', title:'Ups', html:`{!! implode('<br>', $errors->all()) !!}`});
</script>
@endif
@endpush
