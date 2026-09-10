<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <link rel="icon" href="{{ asset('favicon.ico') }}" sizes="any">

    <title>Iniciar sesión — GLADYADOREZ</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>

    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700;800&family=Playfair+Display:wght@600;700;800&display=swap" rel="stylesheet">

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" rel="stylesheet">

    <style>
        :root {
            --rosa-1: #ff1c9d;
            --rosa-2: #d91785;
            --rosa-3: #ff99d3;
            --rosa-suave: #ffd8ed;
            --granate: #7a0019;
            --granate-osc: #5c0013;
            --blanco: #ffffff;
            --texto: #22141d;
            --texto-suave: #7a6873;
        }

        * {
            box-sizing: border-box;
        }

        html,
        body {
            min-height: 100%;
            margin: 0;
        }

        body {
            min-height: 100vh;
            font-family: "Montserrat", sans-serif;
            overflow-x: hidden;
            background: #1b0813;
        }

        .login-page {
            min-height: 100vh;
            position: relative;
            display: flex;
            align-items: center;
            overflow: hidden;
            isolation: isolate;
        }

        .login-background {
            position: absolute;
            inset: -24px;
            z-index: -4;
            background:
                url('{{ asset('img/portada2.jpeg') }}')
                center center / cover no-repeat;
            filter: blur(7px);
            transform: scale(1.04);
        }

        .login-overlay {
            position: absolute;
            inset: 0;
            z-index: -3;
            background:
                linear-gradient(
                    90deg,
                    rgba(26, 4, 17, .86) 0%,
                    rgba(70, 6, 43, .68) 38%,
                    rgba(40, 4, 25, .34) 65%,
                    rgba(20, 2, 13, .55) 100%
                );
        }

        .login-glow {
            position: absolute;
            inset: 0;
            z-index: -2;
            pointer-events: none;
            background:
                radial-gradient(
                    circle at 18% 22%,
                    rgba(255, 28, 157, .25),
                    transparent 32%
                ),
                radial-gradient(
                    circle at 76% 73%,
                    rgba(255, 153, 211, .24),
                    transparent 28%
                ),
                radial-gradient(
                    circle at 85% 12%,
                    rgba(217, 23, 133, .17),
                    transparent 24%
                );
        }

        .login-vignette {
            position: absolute;
            inset: 0;
            z-index: -1;
            pointer-events: none;
            box-shadow:
                inset 0 0 180px rgba(0, 0, 0, .44),
                inset 0 -120px 160px rgba(0, 0, 0, .28);
        }

        .page-content {
            width: 100%;
            min-height: 100vh;
            display: flex;
            align-items: center;
            padding: 45px 0;
        }

        .brand-top {
            position: absolute;
            top: 34px;
            left: 48px;
            display: flex;
            align-items: center;
            gap: 15px;
            color: #fff;
            text-decoration: none;
            z-index: 10;
        }

        .brand-symbol {
            width: 48px;
            height: 48px;
            border-radius: 15px;
            display: grid;
            place-items: center;
            background:
                linear-gradient(
                    145deg,
                    var(--rosa-1),
                    var(--rosa-2)
                );
            box-shadow:
                0 12px 35px rgba(217, 23, 133, .38),
                inset 0 1px rgba(255, 255, 255, .35);
            font-family: "Playfair Display", serif;
            font-weight: 800;
            font-size: 1.15rem;
        }

        .brand-name {
            font-weight: 800;
            letter-spacing: 5px;
            font-size: .92rem;
        }

        .brand-subtitle {
            display: block;
            margin-top: 2px;
            font-size: .61rem;
            letter-spacing: 2px;
            color: rgba(255, 255, 255, .62);
            font-weight: 500;
        }

        .presentation {
            color: white;
            max-width: 670px;
            padding-right: 45px;
        }

        .presentation-label {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 23px;
            padding: 8px 15px;
            border-radius: 999px;
            border: 1px solid rgba(255, 255, 255, .15);
            background: rgba(255, 255, 255, .07);
            backdrop-filter: blur(10px);
            font-size: .68rem;
            font-weight: 700;
            letter-spacing: 3px;
            text-transform: uppercase;
            color: rgba(255, 255, 255, .85);
        }

        .presentation-label span {
            width: 7px;
            height: 7px;
            background: var(--rosa-1);
            border-radius: 50%;
            box-shadow: 0 0 14px var(--rosa-1);
        }

        .presentation h1 {
            margin: 0;
            max-width: 650px;
            font-family: "Playfair Display", serif;
            font-size: clamp(3.4rem, 5vw, 6rem);
            font-weight: 700;
            line-height: .94;
            letter-spacing: -3px;
            text-shadow: 0 14px 40px rgba(0, 0, 0, .32);
        }

        .presentation h1 .pink {
            display: block;
            color: var(--rosa-3);
            text-shadow:
                0 10px 35px rgba(255, 28, 157, .22);
        }

        .presentation-description {
            margin-top: 28px;
            max-width: 540px;
            font-size: 1.02rem;
            line-height: 1.8;
            color: rgba(255, 255, 255, .76);
            font-weight: 400;
        }

        .presentation-line {
            width: 62px;
            height: 4px;
            margin-top: 31px;
            border-radius: 10px;
            background:
                linear-gradient(
                    90deg,
                    var(--rosa-1),
                    var(--rosa-3)
                );
            box-shadow: 0 0 24px rgba(255, 28, 157, .55);
        }

        .presentation-features {
            display: flex;
            gap: 34px;
            margin-top: 39px;
        }

        .feature {
            display: flex;
            align-items: center;
            gap: 10px;
            color: rgba(255, 255, 255, .75);
            font-size: .72rem;
            font-weight: 600;
            letter-spacing: .3px;
        }

        .feature i {
            color: var(--rosa-3);
            font-size: .95rem;
        }

        .login-panel {
            position: relative;
            max-width: 475px;
            margin-left: auto;
            padding: 2px;
            border-radius: 32px;
            background:
                linear-gradient(
                    145deg,
                    rgba(255, 255, 255, .7),
                    rgba(255, 153, 211, .24),
                    rgba(255, 255, 255, .18)
                );
            box-shadow:
                0 40px 100px rgba(0, 0, 0, .4),
                0 10px 50px rgba(217, 23, 133, .12);
        }

        .login-card {
            position: relative;
            padding: 45px 44px 39px;
            border-radius: 30px;
            overflow: hidden;
            background:
                linear-gradient(
                    145deg,
                    rgba(255, 255, 255, .91),
                    rgba(255, 245, 251, .83)
                );
            backdrop-filter: blur(30px) saturate(150%);
            -webkit-backdrop-filter: blur(30px) saturate(150%);
        }

        .login-card::before {
            content: "";
            position: absolute;
            width: 210px;
            height: 210px;
            border-radius: 50%;
            top: -110px;
            right: -70px;
            background: var(--rosa-3);
            filter: blur(60px);
            opacity: .35;
            pointer-events: none;
        }

        .login-card::after {
            content: "";
            position: absolute;
            width: 150px;
            height: 150px;
            border-radius: 50%;
            left: -80px;
            bottom: -80px;
            background: var(--rosa-1);
            filter: blur(65px);
            opacity: .18;
            pointer-events: none;
        }

        .card-content {
            position: relative;
            z-index: 2;
        }

        .login-logo {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 15px;
            margin-bottom: 31px;
        }

        .login-logo-line {
            height: 1px;
            width: 46px;
            background:
                linear-gradient(
                    90deg,
                    transparent,
                    rgba(217, 23, 133, .65)
                );
        }

        .login-logo-line:last-child {
            transform: rotate(180deg);
        }

        .login-logo-text {
            font-family: "Playfair Display", serif;
            font-size: 2.2rem;
            font-weight: 800;
            color: var(--granate);
            letter-spacing: -1px;
        }

        .login-logo-text span {
            color: var(--rosa-1);
        }

        .login-heading {
            text-align: center;
            margin-bottom: 34px;
        }

        .login-heading h2 {
            margin-bottom: 9px;
            color: var(--texto);
            font-family: "Playfair Display", serif;
            font-weight: 700;
            font-size: 2rem;
            letter-spacing: -.6px;
        }

        .login-heading p {
            margin: 0;
            color: var(--texto-suave);
            font-size: .82rem;
            line-height: 1.6;
        }

        .field-group {
            margin-bottom: 18px;
        }

        .field-label {
            display: block;
            margin-bottom: 8px;
            font-size: .7rem;
            font-weight: 700;
            color: #59424f;
            letter-spacing: .4px;
        }

        .input-shell {
            position: relative;
        }

        .input-shell .field-icon {
            position: absolute;
            top: 50%;
            left: 17px;
            transform: translateY(-50%);
            color: #aa7c97;
            font-size: .92rem;
            pointer-events: none;
            transition: .2s ease;
        }

        .form-control.login-input {
            height: 55px;
            padding: 0 48px;
            border-radius: 15px;
            border: 1px solid rgba(122, 0, 25, .11);
            background: rgba(255, 255, 255, .77);
            color: var(--texto);
            font-size: .84rem;
            font-weight: 500;
            outline: none;
            box-shadow:
                0 6px 18px rgba(90, 31, 66, .035);
            transition:
                border-color .2s ease,
                box-shadow .2s ease,
                background .2s ease,
                transform .2s ease;
        }

        .form-control.login-input::placeholder {
            color: #b5a2ad;
            font-weight: 400;
        }

        .form-control.login-input:focus {
            border-color: rgba(217, 23, 133, .65);
            background: #fff;
            box-shadow:
                0 0 0 4px rgba(217, 23, 133, .10),
                0 12px 28px rgba(217, 23, 133, .08);
        }

        .input-shell:focus-within .field-icon {
            color: var(--rosa-2);
        }

        .password-toggle {
            position: absolute;
            top: 50%;
            right: 16px;
            transform: translateY(-50%);
            border: 0;
            padding: 5px;
            background: transparent;
            color: #a78599;
            cursor: pointer;
            transition: color .2s ease;
        }

        .password-toggle:hover {
            color: var(--rosa-2);
        }

        .login-options {
            margin: 4px 0 27px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 15px;
        }

        .form-check {
            min-height: auto;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .form-check-input {
            margin: 0;
            width: 17px;
            height: 17px;
            cursor: pointer;
            border-color: #cba8bc;
            box-shadow: none !important;
        }

        .form-check-input:checked {
            border-color: var(--rosa-2);
            background-color: var(--rosa-2);
        }

        .form-check-label {
            font-size: .73rem;
            color: #755d6b;
            cursor: pointer;
        }

        .admin-help {
            font-size: .66rem;
            color: #9d8191;
            text-align: right;
            line-height: 1.35;
        }

        .btn-login {
            position: relative;
            width: 100%;
            height: 57px;
            border: 0;
            border-radius: 16px;
            overflow: hidden;
            color: #fff;
            font-size: .82rem;
            font-weight: 800;
            letter-spacing: .5px;
            background:
                linear-gradient(
                    100deg,
                    var(--rosa-2),
                    var(--rosa-1)
                );
            box-shadow:
                0 15px 35px rgba(217, 23, 133, .29);
            transition:
                transform .2s ease,
                box-shadow .2s ease;
        }

        .btn-login::before {
            content: "";
            position: absolute;
            top: 0;
            left: -100%;
            width: 70%;
            height: 100%;
            background:
                linear-gradient(
                    90deg,
                    transparent,
                    rgba(255, 255, 255, .27),
                    transparent
                );
            transform: skewX(-20deg);
            transition: left .55s ease;
        }

        .btn-login:hover {
            color: #fff;
            transform: translateY(-2px);
            box-shadow:
                0 20px 42px rgba(217, 23, 133, .4);
        }

        .btn-login:hover::before {
            left: 140%;
        }

        .btn-login i {
            margin-left: 8px;
            transition: transform .2s ease;
        }

        .btn-login:hover i {
            transform: translateX(4px);
        }

        .alert-login {
            position: relative;
            margin-bottom: 20px;
            padding: 13px 15px 13px 42px;
            border: 1px solid rgba(217, 23, 133, .16);
            border-radius: 13px;
            background: rgba(255, 239, 248, .86);
            color: #7b3159;
            font-size: .73rem;
        }

        .alert-login > i {
            position: absolute;
            left: 15px;
            top: 15px;
            color: var(--rosa-2);
        }

        .alert-login ul {
            margin: 0;
            padding-left: 16px;
        }

        .login-separator {
            display: flex;
            align-items: center;
            gap: 13px;
            margin: 28px 0 20px;
            color: #b49daa;
            font-size: .62rem;
            text-transform: uppercase;
            letter-spacing: 1.6px;
            white-space: nowrap;
        }

        .login-separator::before,
        .login-separator::after {
            content: "";
            width: 100%;
            height: 1px;
            background:
                linear-gradient(
                    90deg,
                    transparent,
                    rgba(122, 0, 25, .15)
                );
        }

        .login-separator::after {
            transform: rotate(180deg);
        }

        .back-home {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 8px;
            color: #866979;
            text-decoration: none;
            font-size: .73rem;
            font-weight: 600;
            transition: .2s ease;
        }

        .back-home:hover {
            color: var(--rosa-2);
        }

        .back-home i {
            font-size: .68rem;
        }

        .register-link {
            margin-top: 11px;
            text-align: center;
            font-size: .7rem;
            color: #957989;
        }

        .register-link a {
            color: var(--rosa-2);
            font-weight: 700;
            text-decoration: none;
        }

        .register-link a:hover {
            color: var(--granate);
        }

        .page-footer {
            position: absolute;
            bottom: 27px;
            left: 48px;
            right: 48px;
            z-index: 10;
            display: flex;
            align-items: center;
            justify-content: space-between;
            color: rgba(255, 255, 255, .48);
            font-size: .61rem;
            letter-spacing: .6px;
        }

        .page-footer strong {
            color: rgba(255, 255, 255, .73);
            font-weight: 600;
        }

        @media (max-width: 1199.98px) {
            .presentation h1 {
                font-size: 4.3rem;
            }

            .presentation-features {
                gap: 20px;
                flex-wrap: wrap;
            }

            .login-card {
                padding: 40px 35px 35px;
            }
        }

        @media (max-width: 991.98px) {
            body {
                overflow-y: auto;
            }

            .login-page {
                min-height: 100svh;
            }

            .page-content {
                padding: 110px 0 90px;
            }

            .presentation {
                display: none;
            }

            .login-panel {
                margin: 0 auto;
                max-width: 500px;
            }

            .brand-top {
                top: 25px;
                left: 50%;
                transform: translateX(-50%);
                white-space: nowrap;
            }

            .page-footer {
                left: 25px;
                right: 25px;
                justify-content: center;
            }

            .page-footer span:last-child {
                display: none;
            }

            .login-overlay {
                background:
                    linear-gradient(
                        rgba(38, 4, 24, .52),
                        rgba(30, 2, 19, .67)
                    );
            }
        }

        @media (max-width: 575.98px) {
            .page-content {
                padding: 96px 14px 74px;
                align-items: center;
            }

            .brand-symbol {
                width: 41px;
                height: 41px;
                border-radius: 12px;
            }

            .brand-name {
                font-size: .78rem;
                letter-spacing: 3px;
            }

            .brand-subtitle {
                font-size: .54rem;
                letter-spacing: 1.3px;
            }

            .login-panel {
                width: 100%;
                border-radius: 27px;
            }

            .login-card {
                padding: 34px 24px 29px;
                border-radius: 25px;
            }

            .login-logo {
                margin-bottom: 25px;
            }

            .login-logo-text {
                font-size: 1.9rem;
            }

            .login-heading {
                margin-bottom: 28px;
            }

            .login-heading h2 {
                font-size: 1.7rem;
            }

            .login-options {
                align-items: flex-start;
            }

            .admin-help {
                max-width: 160px;
            }

            .page-footer {
                bottom: 19px;
                font-size: .55rem;
            }
        }
    </style>
</head>

<body>

<div class="login-page">

    <div class="login-background"></div>
    <div class="login-overlay"></div>
    <div class="login-glow"></div>
    <div class="login-vignette"></div>

    <a href="{{ route('welcome') }}" class="brand-top">
        <div class="brand-symbol">GZ</div>

        <div>
            <div class="brand-name">GLADYADOREZ</div>
        </div>
    </a>

    <div class="page-content">
        <div class="container">
            <div class="row align-items-center g-5">

                <div class="col-lg-7">

                    <section class="presentation">

                        <h1>
                            Personas.
                            <span class="pink">Territorio.</span>
                            Resultados.
                        </h1>

                        <p class="presentation-description">
                            Una plataforma diseñada para organizar el trabajo territorial,
                            fortalecer nuestra red y convertir cada esfuerzo en resultados
                            medibles.
                        </p>

                        <div class="presentation-line"></div>

                        <div class="presentation-features">

                            <div class="feature">
                                <i class="fa-solid fa-user-group"></i>
                                <span>Personas</span>
                            </div>

                            <div class="feature">
                                <i class="fa-solid fa-location-dot"></i>
                                <span>Territorio</span>
                            </div>

                            <div class="feature">
                                <i class="fa-solid fa-chart-simple"></i>
                                <span>Resultados</span>
                            </div>

                        </div>

                    </section>

                </div>

                <div class="col-lg-5">

                    <div class="login-panel">

                        <div class="login-card">

                            <div class="card-content">

                                <div class="login-logo">
                                    <div class="login-logo-line"></div>

                                    <div class="login-logo-text">
                                        G<span>•</span>Z
                                    </div>

                                    <div class="login-logo-line"></div>
                                </div>

                                <div class="login-heading">
                                    <h2>Bienvenido de nuevo</h2>
                                    <p>Ingresa tus credenciales para acceder al sistema.</p>
                                </div>

                                @if (session('status'))
                                    <div class="alert-login">
                                        <i class="fa-solid fa-circle-info"></i>
                                        {{ session('status') }}
                                    </div>
                                @endif

                                @if ($errors->any())
                                    <div class="alert-login">
                                        <i class="fa-solid fa-circle-exclamation"></i>

                                        <ul>
                                            @foreach ($errors->all() as $error)
                                                <li>{{ $error }}</li>
                                            @endforeach
                                        </ul>
                                    </div>
                                @endif

                                <form method="POST" action="{{ route('login') }}">
                                    @csrf

                                    <div class="field-group">

                                        <label for="email" class="field-label">
                                            CORREO ELECTRÓNICO
                                        </label>

                                        <div class="input-shell">

                                            <i class="fa-regular fa-envelope field-icon"></i>

                                            <input
                                                id="email"
                                                type="email"
                                                name="email"
                                                class="form-control login-input"
                                                value="{{ old('email') }}"
                                                placeholder="Ingresa tu correo"
                                                autocomplete="email"
                                                required
                                                autofocus
                                            >

                                        </div>

                                    </div>

                                    <div class="field-group">

                                        <label for="password" class="field-label">
                                            CONTRASEÑA
                                        </label>

                                        <div class="input-shell">

                                            <i class="fa-solid fa-lock field-icon"></i>

                                            <input
                                                id="password"
                                                type="password"
                                                name="password"
                                                class="form-control login-input"
                                                placeholder="Ingresa tu contraseña"
                                                autocomplete="current-password"
                                                required
                                            >

                                            <button
                                                type="button"
                                                class="password-toggle"
                                                id="togglePassword"
                                                aria-label="Mostrar contraseña"
                                            >
                                                <i class="fa-regular fa-eye"></i>
                                            </button>

                                        </div>

                                    </div>

                                    <div class="login-options">

                                        <div class="form-check">

                                            <input
                                                class="form-check-input"
                                                type="checkbox"
                                                value="1"
                                                id="remember_me"
                                                name="remember"
                                            >

                                            <label class="form-check-label" for="remember_me">
                                                Recordarme
                                            </label>

                                        </div>

                                        <div class="admin-help">
                                            Restablecimiento de contraseña<br>
                                            mediante administrador
                                        </div>

                                    </div>

                                    <button type="submit" class="btn btn-login">
                                        Iniciar sesión
                                        <i class="fa-solid fa-arrow-right"></i>
                                    </button>

                                </form>

                                <div class="login-separator">
                                    Acceso restringido
                                </div>

                                <a href="{{ route('welcome') }}" class="back-home">
                                    <i class="fa-solid fa-arrow-left"></i>
                                    Volver al inicio
                                </a>

                            </div>

                        </div>

                    </div>

                </div>

            </div>
        </div>
    </div>

    <div class="page-footer">
        <span>
            © {{ date('Y') }} <strong>GLADYADOREZ</strong>
        </span>

        <span>
            Organización · Territorio · Resultados
        </span>
    </div>

</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<script>
    const passwordInput = document.getElementById('password');
    const togglePassword = document.getElementById('togglePassword');
    const toggleIcon = togglePassword.querySelector('i');

    togglePassword.addEventListener('click', function () {
        const hidden = passwordInput.type === 'password';

        passwordInput.type = hidden ? 'text' : 'password';

        toggleIcon.classList.toggle('fa-eye', !hidden);
        toggleIcon.classList.toggle('fa-eye-slash', hidden);

        togglePassword.setAttribute(
            'aria-label',
            hidden ? 'Ocultar contraseña' : 'Mostrar contraseña'
        );
    });
</script>

</body>
</html>
