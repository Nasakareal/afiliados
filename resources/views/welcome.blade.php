<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>GLADYADOREZ — Portal</title>
  <meta name="description" content="Portal informativo. Acceso al sistema.">
  <link rel="icon" href="{{ asset('favicon.ico') }}" sizes="any">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

  <style>
    :root{
      --bg-dark: rgba(0,0,0,.45);
      --card-bg: rgba(255,255,255,.85);
      --text: #0f0f10;
    }
    html, body { height: 100%; }
    body { margin:0; color: var(--text); font-family: system-ui, -apple-system, "Segoe UI", Roboto, "Helvetica Neue", Arial, "Noto Sans", "Liberation Sans", sans-serif; }

    /* HERO de pantalla completa con foto difuminada */
    .hero {
      position: relative; min-height: 100vh; display: grid; place-items: center; overflow: hidden;
      background: #000;
    }
    .hero::before{
      content:""; position:absolute; inset:0;
      background-image: url('{{ asset('img/portada4.jpg') }}'); /* <- cambia aquí la foto si quieres */
      background-size: cover; background-position: center;
      filter: blur(10px) saturate(1.1);
      transform: scale(1.05); /* evita bordes al aplicar blur */
    }
    .hero::after{
      content:""; position:absolute; inset:0; background: var(--bg-dark);
    }

    /* Tarjeta central minimalista */
    .welcome-card{
      position: relative; z-index: 1;
      max-width: 720px; width: 92%;
      background: var(--card-bg);
      backdrop-filter: blur(6px);
      border-radius: 16px;
      box-shadow: 0 20px 60px rgba(0,0,0,.20);
      padding: clamp(1.25rem, 2.5vw, 2rem);
    }
    .brand{
      font-weight: 800; letter-spacing: .5px; text-transform: uppercase;
    }
    .muted{
      color: #4b4b4b; font-size: .95rem;
    }

    /* Navbar súper simple */
    .topbar{
      position: fixed; inset: 0 0 auto 0; height: 56px; display:flex; align-items:center;
      padding: 0 1rem; z-index: 2;
      background: rgba(255,255,255,.6); backdrop-filter: blur(8px); border-bottom: 1px solid rgba(0,0,0,.06);
    }
    .topbar .right a{ text-decoration:none; }
  </style>
</head>
<body>

  <!-- Barra superior minimal -->
  <div class="topbar">
    <div class="container d-flex justify-content-between align-items-center">
      <div class="brand">GLADYADOREZ</div>
      <div class="right">
        <a class="btn btn-dark btn-sm" href="{{ route('login') }}">Iniciar sesión</a>
      </div>
    </div>
  </div>

  <!-- Sección principal -->
  <main class="hero">
    <section class="welcome-card">
      <h1 class="h3 mb-2">Bienvenido</h1>
      <p class="muted mb-3">Portal informativo de acceso al sistema.</p>
      <a href="{{ route('login') }}" class="btn btn-dark btn-lg">Entrar al sistema</a>
    </section>
  </main>

  <footer class="text-center py-4" style="position:relative; z-index:1;">
    <small class="text-muted">© {{ date('Y') }}. Uso interno.</small>
  </footer>

</body>
</html>
