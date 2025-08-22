<!DOCTYPE html>
<html lang="es" data-theme="gladyz">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Gladyz Butanda Macías — GLADYADOREZ</title>
  <meta name="description" content="Gladyz Butanda Macías — GLADYADOREZ. Organización ciudadana para sumar convencidos, coordinar actividades y transformar Michoacán.">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;600;800&family=Playfair+Display:wght@700;900&display=swap" rel="stylesheet">
  <!-- Bootstrap + Font Awesome -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" rel="stylesheet">

  <style>
    :root{
      --granate:#7a0019;     /* Morena vibes */
      --granate-osc:#5c0013;
      --granate-claro:#92192c;
      --crema:#fff9f2;
      --dorado:#f2c14e;
      --tinta:#141414;
      --humo:#f5f5f7;
      --card-bg: rgba(255,255,255,.12);
      --glass-bg: rgba(255,255,255,.08);
      --border-glass: rgba(255,255,255,.2);
    }
    html,body{height:100%; background:var(--humo); color:var(--tinta); font-family:Montserrat,system-ui,-apple-system,Segoe UI,Roboto; scroll-behavior:smooth;}
    .brand{font-weight:900; letter-spacing:.5px; text-transform:uppercase}
    .brand .dot{color:var(--dorado)}
    .btn-granate{background:var(--granate); color:#fff; border:none}
    .btn-granate:hover{background:var(--granate-osc); color:#fff}
    .btn-outline-crema{border:2px solid #fff; color:#fff}
    .btn-outline-crema:hover{background:#fff;color:var(--granate)}
    .hero{
      min-height:86vh;
      position:relative;
      background: radial-gradient(1200px 600px at 25% 10%, var(--granate-claro), var(--granate) 55%, var(--granate-osc) 100%);
      color:#fff; overflow:hidden;
    }
    .hero::after{
      content:"";
      position:absolute; inset:0;
      background: url('data:image/svg+xml;utf8,<svg width="1200" height="800" xmlns="http://www.w3.org/2000/svg"><defs><linearGradient id="g" x1="0" y1="0" x2="1" y2="1"><stop stop-color="%23ffffff" stop-opacity=".06"/><stop offset="1" stop-color="%23ffffff" stop-opacity="0"/></linearGradient></defs><g fill="url(%23g)"><circle cx="150" cy="100" r="120"/><circle cx="900" cy="220" r="180"/><circle cx="1100" cy="650" r="140"/><circle cx="300" cy="700" r="170"/></g></svg>') center/cover no-repeat;
      mix-blend-mode: screen; opacity:.35; pointer-events:none;
    }
    .hero-badge{
      display:inline-flex; align-items:center; gap:.5rem;
      background:rgba(255,255,255,.12); padding:.5rem .9rem;
      border:1px solid var(--border-glass); border-radius:999px; backdrop-filter: blur(6px);
      font-weight:600; letter-spacing:.3px;
    }
    .display-title{
      font-family:"Playfair Display",serif; font-weight:900; line-height:1.05; letter-spacing:-.5px;
      text-shadow:0 10px 30px rgba(0,0,0,.35);
    }
    .sub{
      font-size:1.1rem; opacity:.95
    }
    .glass{
      background:var(--glass-bg); border:1px solid var(--border-glass); border-radius:20px; backdrop-filter: blur(8px);
    }
    .metric{
      background:var(--card-bg); border:1px solid var(--border-glass); border-radius:18px;
      padding:1rem 1.2rem; color:#fff;
    }
    .metric .num{font-size:2.1rem; font-weight:800; line-height:1}
    .metric .lbl{opacity:.9; font-weight:600}
    .wave{
      position:relative; height:82px; margin-top:-60px;
      background:linear-gradient(180deg, transparent 0, var(--humo) 60%);
      clip-path: path("M0,32 C 300,140 900,0 1200,56 L1200,120 L0,120 Z");
    }
    .section-title{
      font-weight:900; letter-spacing:.3px; text-transform:uppercase; color:var(--granate);
    }
    .card-eje{
      border:0; border-radius:18px; overflow:hidden; background:#fff;
      box-shadow:0 20px 50px rgba(122,0,25,.12);
      transition:.25s transform ease, .25s box-shadow ease;
    }
    .card-eje:hover{ transform:translateY(-6px); box-shadow:0 25px 60px rgba(122,0,25,.18) }
    .chip{display:inline-block; padding:.35rem .7rem; border-radius:999px; font-size:.8rem; font-weight:700; letter-spacing:.3px}
    .chip-gold{ background:var(--dorado); color:#5a3b00 }
    .chip-granate{ background:var(--granate); color:#fff }

    .cta-band{ background:linear-gradient(90deg, var(--granate-osc), var(--granate)); color:#fff }
    .progress-gold{ --bs-progress-height: .9rem; --bs-progress-bar-bg: var(--dorado); background:rgba(255,255,255,.35) }

    .footer{ background:#0f0f10; color:#c8c8c8 }
    .footer a{ color:#fff; text-decoration:none }
    .footer a:hover{ text-decoration:underline }
    .floating-whatsapp{
      position:fixed; right:18px; bottom:18px; z-index:50;
      background:#25D366; color:#fff; width:56px; height:56px; border-radius:50%;
      display:grid; place-items:center; box-shadow:0 10px 25px rgba(0,0,0,.35);
    }
    .scrolltop{
      position:fixed; right:18px; bottom:86px; z-index:50;
      width:44px; height:44px; border-radius:50%;
      display:none; place-items:center; color:#fff; background:var(--granate);
      box-shadow:0 10px 25px rgba(0,0,0,.35);
    }
    .nav-blur{ backdrop-filter: blur(8px); background:rgba(255,255,255,.55) }
    .logo-text{ letter-spacing:.8px; font-weight:900 }
    .badge-gladyadorez{ background:#fff; color:var(--granate); border:2px solid var(--granate); border-radius:999px; padding:.2rem .6rem; font-weight:800 }
    .kicker{ text-transform:uppercase; letter-spacing:.18em; font-weight:800; color:#fff; opacity:.85 }
  </style>
</head>
<body>

  <!-- NAV -->
  <nav class="navbar navbar-expand-lg sticky-top nav-blur py-2">
    <div class="container">
      <a class="navbar-brand brand d-flex align-items-center gap-2" href="#">
        <span class="logo-text">GLADY<span class="dot">•</span>ADOREZ</span>
      </a>
      <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#nav">
        <span class="navbar-toggler-icon"></span>
      </button>
      <div id="nav" class="collapse navbar-collapse">
        <ul class="navbar-nav ms-auto align-items-lg-center">
          <li class="nav-item mx-1"><a class="nav-link fw-semibold" href="#convencidos">Convencidos</a></li>
          <li class="nav-item mx-1"><a class="nav-link fw-semibold" href="#mapa">Mapa</a></li>
          <li class="nav-item mx-1"><a class="nav-link fw-semibold" href="#calendario">Calendario</a></li>
          <li class="nav-item mx-1"><a class="nav-link fw-semibold" href="#ejes">Ejes</a></li>
          <li class="nav-item ms-lg-3">
            <a class="btn btn-granate btn-sm px-3" href="#sumate"><i class="fa-solid fa-heart me-1"></i> Súmate</a>
          </li>
        </ul>
      </div>
    </div>
  </nav>

  <!-- HERO -->
  <header class="hero d-flex align-items-center">
    <div class="container position-relative">
      <div class="row align-items-center g-4">
        <div class="col-12 col-lg-7">
          <span class="hero-badge mb-3">
            <span class="badge-gladyadorez">GLADYADOREZ</span> <span class="kicker">Michoacán que cumple</span>
          </span>
          <h1 class="display-3 display-title mb-3">Gladyz Butanda Macías</h1>
          <p class="sub mb-4">Convocamos a construir, desde Morelia hacia todo Michoacán, una red que escucha, suma y **cumple**. Convencidos reales, actividades claras, resultados medibles.</p>

          <div class="d-flex flex-wrap gap-2">
            <a href="{{ url('/afiliados/create') }}" class="btn btn-lg btn-light fw-bold"><i class="fa-solid fa-user-check me-2"></i>Registrar convencidos</a>
            <a href="{{ url('/calendario') }}" class="btn btn-lg btn-outline-crema fw-bold"><i class="fa-solid fa-calendar-days me-2"></i>Ver calendario</a>
            <a href="#mapa" class="btn btn-lg btn-granate fw-bold"><i class="fa-solid fa-map-location-dot me-2"></i>Mapa</a>
          </div>

          <div class="d-flex gap-3 mt-4">
            <div class="metric">
              <div class="num" data-counter="18532">0</div>
              <div class="lbl">Convencidos</div>
            </div>
            <div class="metric">
              <div class="num" data-counter="113">0</div>
              <div class="lbl">Secciones</div>
            </div>
            <div class="metric">
              <div class="num" data-counter="23">0</div>
              <div class="lbl">Municipios</div>
            </div>
            <div class="metric">
              <div class="num" data-counter="624">0</div>
              <div class="lbl">Voluntariado</div>
            </div>
          </div>
        </div>

        <div class="col-12 col-lg-5">
          <div class="glass p-4 p-md-5">
            <h3 class="mb-3">Objetivo estatal</h3>
            <p class="mb-2">Meta de convencidos vs lista nominal (proyección).</p>
            <div class="d-flex justify-content-between small">
              <span>Progreso</span><span><strong id="pct">0%</strong></span>
            </div>
            <div class="progress progress-gold my-2">
              <div id="bar" class="progress-bar" role="progressbar" style="width:0%"></div>
            </div>
            <div class="d-flex justify-content-between small">
              <span><i class="fa-solid fa-bullseye me-1"></i><span id="curr">0</span> de <span id="goal">0</span></span>
              <span class="text-muted">*Datos ilustrativos</span>
            </div>
            <hr class="border-white-50 my-4">
            <div class="d-flex gap-2 flex-wrap">
              <span class="chip chip-gold">Sección</span>
              <span class="chip chip-granate">Municipio</span>
              <span class="chip chip-gold">Distrito</span>
              <span class="chip chip-granate">Filtros múltiples</span>
            </div>
          </div>
        </div>
      </div>
    </div>
  </header>
  <div class="wave"></div>

  <!-- CTA STRIP -->
  <section class="cta-band py-3">
    <div class="container text-center">
      <div class="row align-items-center">
        <div class="col-12 col-md">
          <strong class="me-2">GLADYADOREZ</strong> | Juntas y juntos cumplimos.
        </div>
        <div class="col-12 col-md-auto mt-2 mt-md-0">
          <a href="#sumate" class="btn btn-light btn-sm fw-bold"><i class="fa-solid fa-handshake-angle me-1"></i> Súmate</a>
        </div>
      </div>
    </div>
  </section>

  <!-- CONVENCIDOS / FILTROS -->
  <section id="convencidos" class="py-6 py-5">
    <div class="container">
      <h2 class="section-title mb-4">Convencidos y filtros</h2>
      <p class="mb-4">Captura ágil y bloqueo centralizado cuando se requiera. Filtros por <strong>sección</strong>, <strong>municipio</strong>, <strong>distrito</strong> para análisis fino.</p>
      <div class="row g-4">
        <div class="col-md-4">
          <div class="card-eje p-4 h-100">
            <span class="chip chip-gold mb-2">Bloqueo</span>
            <h5 class="fw-bold">Control de captura</h5>
            <p class="mb-3">Podemos pausar altas en todo el estado con un switch maestro y mostrar motivo.</p>
            <a href="{{ url('/admin/configuracion') }}" class="btn btn-granate btn-sm">Configurar</a>
          </div>
        </div>
        <div class="col-md-4">
          <div class="card-eje p-4 h-100">
            <span class="chip chip-granate mb-2">Estadística</span>
            <h5 class="fw-bold">% por sección</h5>
            <p class="mb-3">Cruce contra la <strong>lista nominal</strong> por sección: avance, rezagos y priorización.</p>
            <a href="{{ url('/reportes/secciones') }}" class="btn btn-outline-dark btn-sm"><i class="fa-solid fa-chart-column me-1"></i> Reportes</a>
          </div>
        </div>
        <div class="col-md-4">
          <div class="card-eje p-4 h-100">
            <span class="chip chip-gold mb-2">Rendición</span>
            <h5 class="fw-bold">Quién carga y cuánto</h5>
            <p class="mb-3">Ranking por capturista: trazabilidad y metas diarias.</p>
            <a href="{{ url('/reportes/capturistas') }}" class="btn btn-outline-dark btn-sm"><i class="fa-solid fa-ranking-star me-1"></i> Top</a>
          </div>
        </div>
      </div>
    </div>
  </section>

  <!-- MAPA -->
  <section id="mapa" class="py-5" style="background:linear-gradient(180deg,#fff, #fff0f1);">
    <div class="container">
      <div class="row align-items-center g-4">
        <div class="col-lg-6">
          <h2 class="section-title">Mapa interactivo</h2>
          <p class="mb-3">Empezamos por <strong>Morelia</strong> y escalamos a todo <strong>Michoacán</strong>. Visualización por municipio y sección, con intensidad según convencidos.</p>
          <ul class="mb-4">
            <li>Capa municipal / secciones</li>
            <li>Heatmap de convencidos</li>
            <li>Click en polígono → detalle</li>
          </ul>
          <a href="{{ url('/mapa') }}" class="btn btn-granate"><i class="fa-solid fa-map me-2"></i>Abrir mapa</a>
        </div>
        <div class="col-lg-6">
          <div class="glass p-3">
            <div style="height:320px; background:repeating-linear-gradient(45deg, rgba(122,0,25,.08) 0 8px, rgba(0,0,0,.03) 8px 16px); border-radius:14px; border:1px dashed rgba(122,0,25,.4); display:grid; place-items:center;">
              <div class="text-center px-3">
                <i class="fa-solid fa-location-dot fa-2x mb-2" style="color:var(--granate)"></i>
                <div class="fw-bold">Aquí irá tu mapa (Leaflet/Mapbox)</div>
                <small class="text-muted">Placeholder temporal</small>
              </div>
            </div>
          </div>
        </div>
      </div>      
    </div>
  </section>

  <!-- EJES / MENSAJE -->
  <section id="ejes" class="py-5">
    <div class="container">
      <h2 class="section-title mb-4">Ejes que sí cumplen</h2>
      <div class="row g-4">
        <div class="col-md-4"><div class="card-eje p-4 h-100">
          <h5 class="fw-bold mb-2">Escucha activa</h5>
          <p>Puertas abiertas: diagnóstico real barrio por barrio para no prometer, sino cumplir.</p>
          <span class="chip chip-gold">Territorio</span>
        </div></div>
        <div class="col-md-4"><div class="card-eje p-4 h-100">
          <h5 class="fw-bold mb-2">Datos, no cuentos</h5>
          <p>Tableros con avance por sección, metas y responsables visibles.</p>
          <span class="chip chip-granate">Transparencia</span>
        </div></div>
        <div class="col-md-4"><div class="card-eje p-4 h-100">
          <h5 class="fw-bold mb-2">Cadenas de acción</h5>
          <p>Actividades coordinadas con calendario claro y roles definidos.</p>
          <span class="chip chip-gold">Organización</span>
        </div></div>
      </div>
    </div>
  </section>

  <!-- CALENDARIO -->
  <section id="calendario" class="py-5" style="background:linear-gradient(180deg,#fff0f1, #fff);">
    <div class="container">
      <div class="row align-items-center g-4">
        <div class="col-lg-7">
          <h2 class="section-title">Calendario</h2>
          <p>Un encargado publica, la red consulta. Agenda tipo Google Calendar.</p>
          <a href="{{ url('/calendario') }}" class="btn btn-granate"><i class="fa-solid fa-calendar-days me-2"></i>Ver calendario</a>
        </div>
        <div class="col-lg-5">
          <div class="glass p-4">
            <div class="d-flex align-items-center mb-2">
              <i class="fa-solid fa-bell me-2" style="color:var(--dorado)"></i>
              <strong>Próxima actividad</strong>
            </div>
            <div class="small text-muted">Aparecerá aquí el siguiente evento destacado.</div>
          </div>
        </div>
      </div>
    </div>
  </section>

  <!-- SÚMATE -->
  <section id="sumate" class="py-5">
    <div class="container">
      <div class="glass p-4 p-md-5">
        <div class="row align-items-center g-4">
          <div class="col-lg-8">
            <h3 class="mb-2">Súmate a GLADYADOREZ</h3>
            <p class="mb-0">Tu experiencia y tu voz cuentan. Desde tu colonia podemos mover la aguja (y medirlo).</p>
          </div>
          <div class="col-lg-4 text-lg-end">
            <a href="{{ url('/registro') }}" class="btn btn-granate btn-lg"><i class="fa-solid fa-user-plus me-2"></i>Registrarme</a>
          </div>
        </div>
      </div>
    </div>
  </section>

  <!-- FOOTER -->
  <footer class="footer pt-5">
    <div class="container">
      <div class="row g-4">
        <div class="col-md-6">
          <h5 class="text-white fw-bold mb-2">GLADYADOREZ</h5>
          <p class="mb-2">Plataforma ciudadana para coordinar convencidos, actividades y resultados.</p>
          <div class="d-flex gap-3">
            <a href="#" aria-label="X / Twitter"><i class="fa-brands fa-x-twitter"></i></a>
            <a href="#" aria-label="Facebook"><i class="fa-brands fa-facebook"></i></a>
            <a href="#" aria-label="Instagram"><i class="fa-brands fa-instagram"></i></a>
            <a href="#" aria-label="YouTube"><i class="fa-brands fa-youtube"></i></a>
          </div>
        </div>
        <div class="col-md-3">
          <h6 class="text-white fw-bold">Módulos</h6>
          <ul class="list-unstyled small">
            <li><a href="#convencidos">Convencidos</a></li>
            <li><a href="#mapa">Mapa</a></li>
            <li><a href="#calendario">Calendario</a></li>
            <li><a href="#ejes">Ejes</a></li>
          </ul>
        </div>
        <div class="col-md-3">
          <h6 class="text-white fw-bold">Contacto</h6>
          <ul class="list-unstyled small">
            <li><a href="mailto:contacto@gladyadorez.mx">contacto@gladyadorez.mx</a></li>
            <li><a href="https://gladyadorez.mx" target="_blank" rel="noopener">gladyadorez.mx</a></li>
          </ul>
        </div>
      </div>
      <hr class="border-secondary my-4">
      <div class="d-flex flex-wrap justify-content-between small pb-4">
        <span>© {{ date('Y') }} GLADYADOREZ. Todos los derechos reservados.</span>
        <span>Sitio administrado por el equipo de campaña. Transparencia y datos abiertos próximamente.</span>
      </div>
    </div>
  </footer>

  <!-- Floating buttons -->
  <a href="#" class="scrolltop" id="scrolltop"><i class="fa-solid fa-arrow-up"></i></a>
  <a href="#" class="floating-whatsapp" title="Escríbenos por WhatsApp"><i class="fa-brands fa-whatsapp fa-lg"></i></a>

  <!-- JS -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
  <script>
    // Contadores animados
    const animateCounter = el => {
      const target = +el.getAttribute('data-counter');
      let curr = 0;
      const step = Math.max(1, Math.floor(target/120));
      const tick = () => {
        curr += step;
        if (curr >= target) curr = target;
        el.textContent = curr.toLocaleString('es-MX');
        if (curr < target) requestAnimationFrame(tick);
      };
      requestAnimationFrame(tick);
    };
    document.querySelectorAll('[data-counter]').forEach(animateCounter);

    // Barra de meta ilustrativa
    const goal = 250000; // <-- ajusta meta estatal
    const current = 18532; // <-- actual (ejemplo)
    const pct = Math.min(100, Math.round((current/goal)*100));
    document.getElementById('goal').textContent = goal.toLocaleString('es-MX');
    document.getElementById('curr').textContent = current.toLocaleString('es-MX');
    document.getElementById('pct').textContent = pct + '%';
    document.getElementById('bar').style.width = pct + '%';

    // Scroll top
    const st = document.getElementById('scrolltop');
    window.addEventListener('scroll', ()=> {
      if(window.scrollY>400){ st.style.display='grid'; } else { st.style.display='none'; }
    });
    st?.addEventListener('click', e=>{ e.preventDefault(); window.scrollTo({top:0,behavior:'smooth'}); });
  </script>
</body>
</html>
