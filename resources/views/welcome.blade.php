<!DOCTYPE html>
<html lang="es" data-theme="gladyz">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link rel="icon" href="{{ asset('favicon.ico') }}" sizes="any">
  <title>Gladyz Butanda Macías — Conócela</title>
  <meta name="description" content="Conoce a Gladyz Butanda Macías: su historia, trayectoria, visión y equipo. GLADYADOREZ.">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;600;800&family=Playfair+Display:wght@700;900&display=swap" rel="stylesheet">
  <!-- Bootstrap + Font Awesome -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" rel="stylesheet">
  <style>
    :root{
      --granate:#7a0019; --granate-osc:#5c0013; --granate-claro:#92192c;
      --crema:#fff9f2; --dorado:#f2c14e; --tinta:#141414; --humo:#f5f5f7;
      --glass-bg: rgba(255,255,255,.08); --border-glass: rgba(255,255,255,.2);
    }
    html,body{height:100%; background:#fff; color:var(--tinta); font-family:Montserrat,system-ui,-apple-system,Segoe UI,Roboto; scroll-behavior:smooth;}
    .btn-granate{background:var(--granate); color:#fff; border:none}
    .btn-granate:hover{background:var(--granate-osc); color:#fff}
    .nav-blur{ backdrop-filter: blur(8px); background:rgba(255,255,255,.6) }
    .brand{font-weight:900; letter-spacing:.5px; text-transform:uppercase}
    .hero{
      position:relative; min-height:86vh; color:#fff; overflow:hidden;
      background: radial-gradient(1200px 600px at 25% 10%, var(--granate-claro), var(--granate) 55%, var(--granate-osc) 100%);
    }
    .hero::after{
      content:""; position:absolute; inset:0;
      background: url('data:image/svg+xml;utf8,<svg width="1200" height="800" xmlns="http://www.w3.org/2000/svg"><defs><linearGradient id="g" x1="0" y1="0" x2="1" y2="1"><stop stop-color="%23ffffff" stop-opacity=".06"/><stop offset="1" stop-color="%23ffffff" stop-opacity="0"/></linearGradient></defs><g fill="url(%23g)"><circle cx="150" cy="100" r="120"/><circle cx="900" cy="220" r="180"/><circle cx="1100" cy="650" r="140"/><circle cx="300" cy="700" r="170"/></g></svg>') center/cover no-repeat;
      mix-blend-mode: screen; opacity:.35; pointer-events:none;
    }
    .display-title{ font-family:"Playfair Display",serif; font-weight:900; letter-spacing:-.3px; text-shadow:0 10px 30px rgba(0,0,0,.35) }
    .hero-photo{
      aspect-ratio: 4/5; width:100%; object-fit:cover; border-radius:24px; box-shadow:0 30px 80px rgba(0,0,0,.35);
      border:3px solid rgba(255,255,255,.35)
    }
    .badge-identity{ display:inline-flex; gap:.5rem; align-items:center; background:rgba(255,255,255,.14); padding:.5rem .9rem; border:1px solid var(--border-glass); border-radius:999px }
    .section-title{ color:var(--granate); font-weight:900; text-transform:uppercase; letter-spacing:.4px }
    .glass{ background:var(--glass-bg); border:1px solid var(--border-glass); border-radius:20px; backdrop-filter: blur(8px) }
    .card-soft{ border:0; border-radius:18px; box-shadow:0 20px 50px rgba(122,0,25,.08) }
    .chip{display:inline-block; padding:.35rem .7rem; border-radius:999px; font-size:.8rem; font-weight:700}
    .chip-gold{background:var(--dorado); color:#5a3b00}
    .chip-granate{background:var(--granate); color:#fff}
    .grid-gallery{ display:grid; grid-template-columns:repeat(12,1fr); gap:12px }
    .g-1{grid-column:span 4} .g-2{grid-column:span 8} .g-3{grid-column:span 6}
    .gal-img{ width:100%; aspect-ratio: 4/3; object-fit:cover; border-radius:14px; box-shadow:0 10px 30px rgba(0,0,0,.08) }
    .video-frame{ position:relative; padding-top:56.25%; border-radius:18px; overflow:hidden; box-shadow:0 20px 60px rgba(0,0,0,.12); background:#000 }
    .video-frame iframe{ position:absolute; inset:0; width:100%; height:100%; border:0 }
    .quote{ background:#fff; border-radius:18px; padding:1.2rem 1.4rem; border-left:6px solid var(--granate); box-shadow:0 12px 30px rgba(0,0,0,.06) }
    .footer{ background:#0f0f10; color:#c8c8c8 }
    .footer a{ color:#fff; text-decoration:none } .footer a:hover{ text-decoration:underline }
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
      <ul class="navbar-nav ms-auto">
        <li class="nav-item mx-1"><a class="nav-link fw-semibold" href="{{ route('login') }}">Login</a></li>
        <li class="nav-item mx-1"><a class="nav-link fw-semibold" href="#trayectoria">Trayectoria</a></li>
        <li class="nav-item mx-1"><a class="nav-link fw-semibold" href="#propuestas">Propuestas</a></li>
        <li class="nav-item mx-1"><a class="nav-link fw-semibold" href="#galeria">Galería</a></li>
        <li class="nav-item mx-1"><a class="nav-link fw-semibold" href="#video">Video</a></li>
        <li class="nav-item mx-1"><a class="nav-link fw-semibold" href="#contacto">Contacto</a></li>
      </ul>
    </div>
  </div>
</nav>


<!-- HERO: Presentación de la candidata -->
<header class="hero d-flex align-items-center">
  <div class="container position-relative">
    <div class="row align-items-center g-4">
      <div class="col-12 col-lg-6">
        <span class="badge-identity mb-3"><i class="fa-solid fa-award"></i> Conócela</span>
        <h1 class="display-4 display-title mb-3">Gladyz Butanda Macías</h1>
        <p class="lead">Michoacana, mujer de resultados, cercana y de palabra. Su historia de trabajo comunitario y gestión pública la respalda: escucha, construye y cumple.</p>
        <div class="mt-4 d-flex flex-wrap gap-2">
          <a href="#galeria" class="btn btn-light btn-lg fw-bold"><i class="fa-solid fa-image me-2"></i>Ver fotos</a>
          <a href="#trayectoria" class="btn btn-granate btn-lg fw-bold"><i class="fa-solid fa-user-tie me-2"></i>Su trayectoria</a>
        </div>
      </div>
      <div class="col-12 col-lg-6">
        <img class="hero-photo" src="{{ asset('img/portada4.jpg') }}" alt="Retrato de Gladyz Butanda Macías">
      </div>
    </div>
    <div class="row mt-4 text-white">
      <div class="col-6 col-md-3 mb-3">
        <div class="chip chip-gold">Cercanía</div>
        <div class="small mt-2">Barrio por barrio, escucha activa.</div>
      </div>
      <div class="col-6 col-md-3 mb-3">
        <div class="chip chip-granate">Transparencia</div>
        <div class="small mt-2">Resultados medibles, no discursos.</div>
      </div>
      <div class="col-6 col-md-3 mb-3">
        <div class="chip chip-gold">Trabajo</div>
        <div class="small mt-2">Gestión y solución de problemas.</div>
      </div>
      <div class="col-6 col-md-3 mb-3">
        <div class="chip chip-granate">Equipo</div>
        <div class="small mt-2">Red ciudadana que cumple.</div>
      </div>
    </div>
  </div>
</header>

<!-- BIO -->
<section id="gladyz" class="py-5">
  <div class="container">
    <h2 class="section-title mb-3">¿Quién es Gladyz?</h2>
    <div class="row g-4">
      <div class="col-lg-7">
        <div class="card card-soft p-4">
          <p class="mb-3"><strong>Gladyz Butanda</strong> es una líder con visión y cercanía, formada en el trabajo de territorio. Su compromiso es convertir la energía ciudadana en resultados concretos, con datos y metas claras.</p>
          <ul class="mb-3">
            <li>Vocación de servicio y escucha activa</li>
            <li>Experiencia en gestión y coordinación de equipos</li>
            <li>Transparencia y rendición de cuentas</li>
          </ul>
          <div class="d-flex gap-2 flex-wrap">
            <span class="chip chip-gold"><i class="fa-solid fa-heart me-1"></i>Compromiso</span>
            <span class="chip chip-granate"><i class="fa-solid fa-people-group me-1"></i>Comunidad</span>
            <span class="chip chip-gold"><i class="fa-solid fa-chart-line me-1"></i>Resultados</span>
          </div>
        </div>
      </div>
      <div class="col-lg-5">
        <img src="{{ asset('img/gente.jpg') }}" class="w-100 rounded-4" style="aspect-ratio:4/3;object-fit:cover" alt="Gladyz con ciudadanía">
      </div>
    </div>
  </div>
</section>

<!-- TRAYECTORIA -->
<section id="trayectoria" class="py-5" style="background:linear-gradient(180deg,#fff,#fff0f1)">
  <div class="container">
    <h2 class="section-title mb-4">Trayectoria</h2>
    <div class="row g-4">
      <div class="col-md-4">
        <div class="card card-soft h-100 p-4">
          <h5 class="fw-bold mb-1"><i class="fa-solid fa-seedling me-2" style="color:var(--granate)"></i>Origen</h5>
          <p>Formada en valores de trabajo y comunidad. Inició impulsando proyectos vecinales con impacto real.</p>
        </div>
      </div>
      <div class="col-md-4">
        <div class="card card-soft h-100 p-4">
          <h5 class="fw-bold mb-1"><i class="fa-solid fa-handshake-angle me-2" style="color:var(--granate)"></i>Gestión</h5>
          <p>Coordinó equipos diversos para resolver problemas públicos con eficiencia y transparencia.</p>
        </div>
      </div>
      <div class="col-md-4">
        <div class="card card-soft h-100 p-4">
          <h5 class="fw-bold mb-1"><i class="fa-solid fa-landmark me-2" style="color:var(--granate)"></i>Resultados</h5>
          <p>Proyectos medibles y entregables claros. Cercanía permanente con barrios y comunidades.</p>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- PROPUESTAS (resumen visual) -->
<section id="propuestas" class="py-5">
  <div class="container">
    <h2 class="section-title mb-3">Visión y prioridades</h2>
    <p class="mb-4">Ejes que guían el trabajo: cercanía, seguridad comunitaria, bienestar y crecimiento con oportunidades.</p>
    <div class="row g-4">
      <div class="col-md-6 col-lg-3">
        <div class="card card-soft h-100 p-4">
          <div class="d-flex align-items-center mb-2"><i class="fa-solid fa-house-chimney-user me-2" style="color:var(--granate)"></i><strong>Barrios vivos</strong></div>
          <p class="mb-0 small">Mejorar servicios, espacios y acompañamiento social donde más se necesita.</p>
        </div>
      </div>
      <div class="col-md-6 col-lg-3">
        <div class="card card-soft h-100 p-4">
          <div class="d-flex align-items-center mb-2"><i class="fa-solid fa-shield-heart me-2" style="color:var(--granate)"></i><strong>Seguridad cercana</strong></div>
          <p class="mb-0 small">Prevención, iluminación, rutas seguras y coordinación vecinal.</p>
        </div>
      </div>
      <div class="col-md-6 col-lg-3">
        <div class="card card-soft h-100 p-4">
          <div class="d-flex align-items-center mb-2"><i class="fa-solid fa-briefcase me-2" style="color:var(--granate)"></i><strong>Oportunidad</strong></div>
          <p class="mb-0 small">Empleo y capacitación para jóvenes, mujeres y personas cuidadoras.</p>
        </div>
      </div>
      <div class="col-md-6 col-lg-3">
        <div class="card card-soft h-100 p-4">
          <div class="d-flex align-items-center mb-2"><i class="fa-solid fa-leaf me-2" style="color:var(--granate)"></i><strong>Entornos dignos</strong></div>
          <p class="mb-0 small">Espacios verdes, movilidad humana y salud comunitaria.</p>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- GALERÍA -->
<section id="galeria" class="py-5" style="background:linear-gradient(180deg,#fff0f1,#fff)">
  <div class="container">
    <h2 class="section-title mb-4">Galería</h2>
    <div class="grid-gallery">
      <img src="{{ asset('img/gente2.jpg') }}" class="gal-img g-2" alt="Gladyz con ciudadanía">
      <img src="{{ asset('img/gente3.jpg') }}" class="gal-img g-1" alt="Encuentro comunitario">
      <img src="{{ asset('img/gente4.jpg') }}" class="gal-img g-3" alt="Trabajo en territorio">
      <img src="{{ asset('img/gente5.jpg') }}" class="gal-img g-1" alt="Reunión de trabajo">
      <img src="{{ asset('img/gente6.jpg') }}" class="gal-img g-2" alt="Visita a colonia">
      <img src="{{ asset('img/gente7.jpg') }}" class="gal-img g-3" alt="Actividad con jóvenes">
    </div>
    <div class="text-center mt-4">
      <a href="#contacto" class="btn btn-granate"><i class="fa-solid fa-camera me-2"></i>Compartir material</a>
    </div>
  </div>
</section>

<!-- VIDEO DESTACADO -->
<section id="video" class="py-5">
  <div class="container">
    <h2 class="section-title mb-3">Mensaje</h2>
    <div class="row g-4">
      <div class="col-lg-7">
        <div class="video-frame">
          <!-- Reemplaza por tu URL de YouTube/Vimeo -->
          <iframe width="560" height="315" src="https://www.youtube.com/embed/c5XmuYZ5kC8?si=TY88MCQEBcuCKEx6" title="YouTube video player" frameborder="0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share" referrerpolicy="strict-origin-when-cross-origin" allowfullscreen></iframe>
        </div>
      </div>
      <div class="col-lg-5">
        <div class="quote">
          <p class="mb-1"><i class="fa-solid fa-quote-left me-2" style="color:var(--granate)"></i>“No se trata de prometer, se trata de cumplir. Y cumplir empieza por escuchar.”</p>
          <small class="text-muted">— Gladyz Butanda</small>
        </div>
        <ul class="mt-3 small">
          <li>Encuentros abiertos con vecinas y vecinos</li>
          <li>Transparencia total en avances</li>
          <li>Resultados medibles por comunidad</li>
        </ul>
      </div>
    </div>
  </div>
</section>

<!-- CONTACTO / CTA -->
<section id="contacto" class="py-5" style="background:linear-gradient(180deg,#fff,#fff0f1)">
  <div class="container">
    <div class="card card-soft p-4 p-md-5">
      <div class="row g-4 align-items-center">
        <div class="col-lg-7">
          <h3 class="mb-2">Únete al equipo</h3>
          <p class="mb-3">¿Quieres sumar desde tu colonia, escuela o trabajo? Escríbenos y te contactamos.</p>
          <div class="d-flex gap-2 flex-wrap">
            <a href="{{ url('/registro') }}" class="btn btn-granate btn-lg"><i class="fa-solid fa-user-plus me-2"></i>Voluntariado</a>
            <a href="mailto:contacto@gladyadorez.mx" class="btn btn-outline-dark btn-lg"><i class="fa-solid fa-envelope me-2"></i>Contacto</a>
          </div>
        </div>
        <div class="col-lg-5">
          <img src="{{ asset('img/gladyz-cta.jpg') }}" class="w-100 rounded-4" style="aspect-ratio:4/3;object-fit:cover" alt="Gladyz con equipo">
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
        <p class="mb-2">Conoce a Gladyz y su visión para un Michoacán que cumple.</p>
        <div class="d-flex gap-3">
          <a href="#" aria-label="X / Twitter"><i class="fa-brands fa-x-twitter"></i></a>
          <a href="#" aria-label="Facebook"><i class="fa-brands fa-facebook"></i></a>
          <a href="#" aria-label="Instagram"><i class="fa-brands fa-instagram"></i></a>
          <a href="#" aria-label="YouTube"><i class="fa-brands fa-youtube"></i></a>
        </div>
      </div>
      <div class="col-md-3">
        <h6 class="text-white fw-bold">Explorar</h6>
        <ul class="list-unstyled small">
          <li><a href="#gladyz">Gladyz</a></li>
          <li><a href="#trayectoria">Trayectoria</a></li>
          <li><a href="#propuestas">Propuestas</a></li>
          <li><a href="#galeria">Galería</a></li>
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
      <span>Material informativo. Fotografías con autorización.</span>
    </div>
  </div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
