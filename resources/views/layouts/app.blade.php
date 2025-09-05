{{-- resources/views/layouts/app.blade.php --}}
<!DOCTYPE html>
<html lang="es" data-theme="gladyz">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>@yield('title','Sistema Afiliados')</title>
  <meta name="csrf-token" content="{{ csrf_token() }}">
  <link rel="icon" href="{{ asset('favicon.ico') }}" sizes="any">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" rel="stylesheet">

  <style>
    :root{
      /* Paleta existente */
      --granate:#7a0019;
      --granate-osc:#5c0013;

      /* === Rosas del muestrario === */
      --rosa-1:#ff1c9d;  /* PANTONE P 75-8 U */
      --rosa-2:#d91785;  /* PANTONE P 75-16 U */
      --rosa-3:#ff99d3;  /* PANTONE P 75-5 U */

      /* === Grises del muestrario (para fondo de página) === */
      --gris-1:#464e59;  /* oscuro */
      --gris-2:#3c434d;  /* medio-oscuro */
      --gris-3:#cfd8e6;  /* claro */

      /* Fondo general DETRÁS del content */
      --humo: var(--gris-3);  /* <-- cámbialo a var(--gris-1) o var(--gris-2) si lo quieres más oscuro */
    }

    html,body{height:100%;background:var(--humo)}

    /* Navbar de vidrio (por si lo quieres en otras vistas) */
    .navbar-glass{backdrop-filter:blur(8px);background:rgba(255,255,255,.75)}

    /* Activo general (fuera del navbar rosa) */
    .nav-link.active,.dropdown-item.active{font-weight:700;color:var(--granate)!important}

    .btn-granate{background:var(--granate);color:#fff;border:none}
    .btn-granate:hover{background:var(--granate-osc);color:#fff}

    .dropdown-menu{border-radius:12px}
    .content-wrap{padding-top:84px}
    .app-footer{color:#666}
    .dropdown.keep-open:hover .dropdown-menu.show{display:block}

    /* --- Leaflet: asegurar popups SIEMPRE arriba de todo --- */
    .leaflet-pane.leaflet-popup-pane { z-index: 100000 !important; }
    .leaflet-popup { z-index: 100001 !important; }
    .leaflet-tooltip { z-index: 100002 !important; }

    /* ====== Utilidades de fondo rosa ====== */
    .bg-rosa-1{background-color:var(--rosa-1)!important}
    .bg-rosa-2{background-color:var(--rosa-2)!important}
    .bg-rosa-3{background-color:var(--rosa-3)!important}
    .bg-rosa-grad{background:linear-gradient(90deg,var(--rosa-2),var(--rosa-1))!important}

    /* ====== Ajustes de contraste para navbar en rosa ====== */
    .navbar-rosa .navbar-brand,
    .navbar-rosa .nav-link,
    .navbar-rosa .navbar-toggler { color:#fff !important; }

    .navbar-rosa .nav-link:hover{ opacity:.9; }

    /* Evitar que el activo se pinte granate dentro del navbar rosa */
    nav.navbar-rosa .nav-link.active,
    nav.navbar-rosa .dropdown-item.active { color:#fff !important; }
  </style>

  {{-- Acepta ambos stacks para CSS de vistas --}}
  @stack('styles')
  @stack('css')
</head>
<body>
@php
  // Helper "activo" (protegido para no redeclarar)
  if (!function_exists('is_active')) {
    function is_active($patterns){
      foreach((array)$patterns as $p){
        if(request()->routeIs($p)) return 'active';
      }
      return '';
    }
  }
@endphp

{{-- =========================================================
     NAVBAR
     Cambia la clase de color:
       bg-rosa-1  -> #ff1c9d (fuerte)
       bg-rosa-2  -> #d91785 (por defecto)
       bg-rosa-3  -> #ff99d3 (claro)
       bg-rosa-grad -> degradado
   ========================================================= --}}
<nav class="navbar navbar-expand-lg fixed-top navbar-dark navbar-rosa bg-rosa-2 border-bottom">
  <div class="container-fluid px-3">
    <a class="navbar-brand fw-bold" href="{{ route('dashboard') }}">
      GLADY<span class="text-white">•</span>ADOREZ
    </a>

    <button class="navbar-toggler" type="button" data-bs-toggle="offcanvas" data-bs-target="#mainNav" aria-label="Menú">
      <span class="navbar-toggler-icon"></span>
    </button>

    <div class="offcanvas offcanvas-end" tabindex="-1" id="mainNav" aria-labelledby="mainNavLabel">
      <div class="offcanvas-header">
        <h5 class="offcanvas-title" id="mainNavLabel">Menú</h5>
        <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Cerrar"></button>
      </div>

      <div class="offcanvas-body">
        <ul class="navbar-nav me-auto mb-2 mb-lg-0 align-items-lg-center">

          @can('afiliados.ver')
          <li class="nav-item">
            <a class="nav-link {{ is_active(['afiliados.*','registro']) }}" href="{{ route('afiliados.index') }}">
              <i class="fa-solid fa-user-check me-1"></i> Afiliados
            </a>
          </li>
          @endcan

          @can('secciones.ver')
          <li class="nav-item">
            <a class="nav-link {{ is_active(['secciones.*']) }}" href="{{ route('secciones.index') }}">
              <i class="fa-solid fa-layer-group me-1"></i> Secciones
            </a>
          </li>
          @endcan

          @can('actividades.ver')
          <li class="nav-item dropdown keep-open">
            <a class="nav-link dropdown-toggle {{ is_active(['calendario.index','actividades.*']) }}" href="#" data-bs-toggle="dropdown" role="button">
              <i class="fa-solid fa-calendar-days me-1"></i> Actividades
            </a>
            <ul class="dropdown-menu">
              <li><a class="dropdown-item {{ is_active('calendario.index') }}" href="{{ route('calendario.index') }}"><i class="fa-regular fa-calendar me-1"></i> Calendario</a></li>
              <li><a class="dropdown-item {{ is_active('actividades.index') }}" href="{{ route('actividades.index') }}"><i class="fa-solid fa-list-check me-1"></i> Listado</a></li>
              @can('actividades.crear')
              <li><a class="dropdown-item {{ is_active('actividades.create') }}" href="{{ route('actividades.create') }}"><i class="fa-solid fa-plus me-1"></i> Crear</a></li>
              @endcan
              <li><hr class="dropdown-divider"></li>
              <li><a class="dropdown-item" href="{{ route('actividades.feed') }}"><i class="fa-solid fa-rss me-1"></i> Feed</a></li>
            </ul>
          </li>
          @endcan

          @can('mapa.ver')
          <li class="nav-item">
            <a class="nav-link {{ is_active('mapa.index') }}" href="{{ route('mapa.index') }}">
              <i class="fa-solid fa-map-location-dot me-1"></i> Mapa
            </a>
          </li>
          @endcan

          @can('reportes.ver')
          <li class="nav-item dropdown keep-open">
            <a class="nav-link dropdown-toggle {{ is_active(['reportes.secciones','reportes.capturistas']) }}" href="#" data-bs-toggle="dropdown" role="button">
              <i class="fa-solid fa-chart-column me-1"></i> Reportes
            </a>
            <ul class="dropdown-menu">
              <li><a class="dropdown-item {{ is_active('reportes.index') }}" href="{{ route('reportes.index') }}"><i class="fa-solid fa-gauge-high me-1"></i>Panel</a></li>
              <li><a class="dropdown-item {{ is_active('reportes.secciones') }}" href="{{ route('reportes.secciones') }}"><i class="fa-solid fa-diagram-project me-1"></i> Por secciones</a></li>
              <li><a class="dropdown-item {{ is_active('reportes.capturistas') }}" href="{{ route('reportes.capturistas') }}"><i class="fa-solid fa-ranking-star me-1"></i> Capturistas</a></li>
            </ul>
          </li>
          @endcan

          @can('settings.ver')
          <li class="nav-item dropdown keep-open">
            <a class="nav-link dropdown-toggle {{ is_active(['settings.*']) }}" href="#" data-bs-toggle="dropdown" role="button">
              <i class="fa-solid fa-gear me-1"></i> Settings
            </a>
            <ul class="dropdown-menu">
              <li><a class="dropdown-item {{ is_active('settings.index') }}" href="{{ route('settings.index') }}"><i class="fa-solid fa-gauge-high me-1"></i> Panel</a></li>
              @can('usuarios.ver')
              <li><a class="dropdown-item {{ is_active('settings.usuarios.*') }}" href="{{ route('settings.usuarios.index') }}"><i class="fa-solid fa-users me-1"></i> Usuarios</a></li>
              @endcan
              @can('roles.ver')
              <li><a class="dropdown-item {{ is_active('settings.roles.*') }}" href="{{ route('settings.roles.index') }}"><i class="fa-solid fa-user-shield me-1"></i> Roles</a></li>
              @endcan
              @can('comunicados.ver')
              <li><a class="dropdown-item {{ is_active('settings.comunicados.*') }}" href="{{ route('settings.comunicados.index') }}"><i class="fa-solid fa-bell"></i> Comunicados</a></li>
              @endcan
              @can('settings.editar')
              <li><a class="dropdown-item {{ is_active('settings.app.edit') }}" href="{{ route('settings.app.edit') }}"><i class="fa-solid fa-sliders me-1"></i> App</a></li>
              @endcan
            </ul>
          </li>
          @endcan
        </ul>

        <div class="d-flex align-items-center gap-2">
          <span class="small text-white-50 d-none d-lg-inline">Hola, {{ Auth::user()->name ?? 'Usuario' }}</span>
          <form method="POST" action="{{ route('logout') }}">@csrf
            <button class="btn btn-outline-light btn-sm">
              <i class="fa-solid fa-right-from-bracket me-1"></i> Salir
            </button>
          </form>
        </div>
      </div>
    </div>
  </div>
</nav>

<main class="content-wrap container-fluid">
  @if(session('success'))
    <div class="alert alert-success alert-dismissible fade show" role="alert">
      {{ session('success') }}
      <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
  @endif

  @if(session('error'))
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
      {{ session('error') }}
      <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
  @endif

  @yield('content')
</main>

@hasSection('footer')
<footer class="app-footer border-top mt-5 py-4">
  <div class="container">
    @yield('footer')
  </div>
</footer>
@endif

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
  // Cerrar dropdowns al hacer click fuera
  document.addEventListener('click', (e)=>{
    const openMenus = document.querySelectorAll('.dropdown-menu.show');
    openMenus.forEach(menu=>{
      if(!menu.parentElement.contains(e.target)){
        const toggle = menu.parentElement.querySelector('[data-bs-toggle="dropdown"]');
        bootstrap.Dropdown.getInstance(toggle)?.hide();
      }
    });
  });

  // Cerrar dropdown al salir con el mouse (hover persistente)
  document.querySelectorAll('.dropdown.keep-open').forEach(dd=>{
    let to=null;
    dd.addEventListener('mouseleave',()=>{
      to=setTimeout(()=>{
        const toggle = dd.querySelector('[data-bs-toggle="dropdown"]');
        bootstrap.Dropdown.getInstance(toggle)?.hide();
      }, 150);
    });
    dd.addEventListener('mouseenter',()=>{ if(to){ clearTimeout(to); to=null; }});
  });

  // Cerrar offcanvas al navegar (móvil)
  document.querySelectorAll('#mainNav .nav-link, #mainNav .dropdown-item').forEach(a=>{
    a.addEventListener('click',()=>{
      const oc = bootstrap.Offcanvas.getInstance(document.getElementById('mainNav'));
      oc?.hide();
    });
  });
</script>

@yield('js')
@stack('scripts')
</body>
</html>
