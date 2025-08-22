<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <link rel="icon" href="{{ asset('favicon.ico') }}" sizes="any">
  <title>Iniciar sesión — GLADYADOREZ</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <!-- Fonts + Bootstrap + Icons -->
  <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;600;800&display=swap" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" rel="stylesheet">
  <style>
    :root{
      --granate:#7a0019; --granate-osc:#5c0013; --granate-claro:#92192c;
      --dorado:#f2c14e; --humo:#f5f5f7; --glass: rgba(255,255,255,.10); --bglass: rgba(255,255,255,.28);
    }
    html,body{height:100%; font-family:Montserrat, system-ui, -apple-system, Segoe UI, Roboto}
    .auth-wrap{ position:relative; min-height:100vh; overflow:hidden; }
    .bg-blur, .bg-overlay{ position:absolute; inset:0 }
    .bg-blur{
      background: url('{{ asset('img/portada2.jpeg') }}') center/cover no-repeat;
      filter: blur(6px) saturate(1.0);
      transform: scale(1.03);
    }
    .bg-overlay{
      background:
        radial-gradient(1200px 600px at 25% 10%,
          rgba(146,25,44,.25),          /* antes .55 */
          rgba(122,0,25,.35) 55%,       /* antes .75 */
          rgba(92,0,25,.55) 100%        /* antes .92 */
        ),
        linear-gradient(180deg,
          rgba(0,0,0,.08),              /* antes .15 */
          rgba(0,0,0,.20)               /* antes .35 */
        );
    }
    .brand{ color:#fff; font-weight:900; letter-spacing:.6px; text-transform:uppercase; text-shadow:0 6px 20px rgba(0,0,0,.35) }
    .brand .dot{ color:var(--dorado) }
    .card-glass{
      background: var(--glass);
      border:1px solid var(--bglass);
      border-radius:22px;
      backdrop-filter: blur(8px);
      box-shadow: 0 25px 70px rgba(0,0,0,.30);
    }
    .form-label{ font-weight:700 }
    .form-control{
      border-radius:12px; padding:.8rem .95rem;
      border:1px solid rgba(255,255,255,.45);
      background: rgba(255,255,255,.85);
    }
    .btn-granate{ background:var(--granate); color:#fff; border:none; font-weight:800 }
    .btn-granate:hover{ background:var(--granate-osc); color:#fff }
    .text-link{ color:#fff; opacity:.9 }
    .text-link:hover{ color:#fff; opacity:1 }
    .helper{ color:#fff; opacity:.85 }
    .footer-mini{ color:#fff; opacity:.7; font-size:.85rem }
    .alert-soft{
      background: rgba(255,255,255,.85);
      border:1px solid rgba(0,0,0,.08);
      border-radius:12px;
    }
  </style>
</head>
<body>
  <div class="auth-wrap">
    <div class="bg-blur"></div>
    <div class="bg-overlay"></div>

    <div class="container position-relative" style="z-index:1;">
      <!-- Header -->
      <div class="row">
        <div class="col-12 text-center pt-5">
          <a href="{{ route('welcome') }}" class="text-decoration-none">
            <div class="brand h3 mb-0">GLADY<span class="dot">•</span>ADOREZ</div>
            <div class="helper small">Acceso para el equipo</div>
          </a>
        </div>
      </div>

      <!-- Form card -->
      <div class="row justify-content-center align-items-center" style="min-height:70vh">
        <div class="col-12 col-md-8 col-lg-5">
          <div class="card card-glass p-4 p-md-5 text-dark">
            <h1 class="h4 fw-800 mb-3"><i class="fa-solid fa-right-to-bracket me-2" style="color:var(--granate)"></i>Iniciar sesión</h1>

            {{-- Session status --}}
            @if (session('status'))
              <div class="alert alert-soft py-2 px-3 mb-3">
                {{ session('status') }}
              </div>
            @endif

            {{-- Validation errors --}}
            @if ($errors->any())
              <div class="alert alert-soft py-2 px-3 mb-3">
                <ul class="mb-0 ps-3">
                  @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                  @endforeach
                </ul>
              </div>
            @endif

            <form method="POST" action="{{ route('login') }}" novalidate>
              @csrf

              <div class="mb-3">
                <label for="email" class="form-label">Correo electrónico</label>
                <input id="email" type="email" name="email" class="form-control" placeholder="tucorreo@dominio.com"
                       value="{{ old('email') }}" required autofocus>
              </div>

              <div class="mb-2">
                <label for="password" class="form-label">Contraseña</label>
                <input id="password" type="password" name="password" class="form-control" placeholder="••••••••"
                       required autocomplete="current-password">
              </div>

              <div class="d-flex justify-content-between align-items-center mb-4">
                <div class="form-check">
                  <input class="form-check-input" type="checkbox" value="1" id="remember_me" name="remember">
                  <label class="form-check-label" for="remember_me">Recordarme</label>
                </div>

                @if (Route::has('password.request'))
                  <a class="small text-decoration-none" href="{{ route('password.request') }}">¿Olvidaste tu contraseña?</a>
                @endif
              </div>

              <button type="submit" class="btn btn-granate w-100 py-2">
                <i class="fa-solid fa-unlock-keyhole me-2"></i> Entrar
              </button>
            </form>

            <div class="d-flex justify-content-between align-items-center mt-4">
              <a href="{{ route('welcome') }}" class="text-link text-decoration-none">
                <i class="fa-solid fa-chevron-left me-1"></i> Volver al inicio
              </a>
              {{-- Si no quieres registro público, oculta esto --}}
              @if (Route::has('register'))
                <a href="{{ route('register') }}" class="text-link text-decoration-none">
                  Crear cuenta
                </a>
              @endif
            </div>
          </div>

          <p class="text-center mt-3 footer-mini">© {{ date('Y') }} GLADYADOREZ · Acceso restringido</p>
        </div>
      </div>
    </div>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
