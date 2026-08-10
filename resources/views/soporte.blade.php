
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>Gladyadorez — Soporte</title>

    <meta name="description" content="Centro de soporte y contacto de Gladyadorez.">
    <meta name="robots" content="index, follow">

    <link rel="icon" href="{{ asset('favicon.ico') }}" sizes="any">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

    <style>
        :root {
            --bg-dark: rgba(0, 0, 0, .55);
            --card-bg: rgba(255, 255, 255, .92);
            --text: #0f0f10;
            --muted: #5c5c5c;
        }

        html,
        body {
            min-height: 100%;
        }

        body {
            margin: 0;
            color: var(--text);
            font-family: system-ui, -apple-system, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
            background: #000;
        }

        .hero {
            position: relative;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
            padding: 90px 16px 50px;
        }

        .hero::before {
            content: "";
            position: absolute;
            inset: 0;
            background-image: url('{{ asset('img/portada4.jpg') }}');
            background-size: cover;
            background-position: center;
            filter: blur(10px) saturate(1.1);
            transform: scale(1.06);
        }

        .hero::after {
            content: "";
            position: absolute;
            inset: 0;
            background: var(--bg-dark);
        }

        .topbar {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            height: 60px;
            display: flex;
            align-items: center;
            z-index: 10;
            background: rgba(255, 255, 255, .82);
            backdrop-filter: blur(12px);
            border-bottom: 1px solid rgba(0, 0, 0, .06);
        }

        .brand {
            font-weight: 800;
            letter-spacing: .5px;
            text-transform: uppercase;
        }

        .support-card {
            position: relative;
            z-index: 2;
            width: 100%;
            max-width: 720px;
            background: var(--card-bg);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            box-shadow: 0 25px 80px rgba(0, 0, 0, .30);
            padding: clamp(1.5rem, 4vw, 2.7rem);
        }

        .support-icon {
            width: 64px;
            height: 64px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 18px;
            background: #111;
            color: #fff;
            font-size: 30px;
            margin-bottom: 20px;
        }

        .muted {
            color: var(--muted);
        }

        .contact-item {
            border: 1px solid rgba(0, 0, 0, .08);
            border-radius: 14px;
            padding: 16px;
            background: rgba(255, 255, 255, .65);
        }

        .contact-label {
            font-size: .82rem;
            color: #727272;
            margin-bottom: 3px;
        }

        .contact-value {
            font-weight: 600;
            color: #111;
            text-decoration: none;
            word-break: break-word;
        }

        .contact-value:hover {
            text-decoration: underline;
        }

        .footer-links a {
            color: #555;
            text-decoration: none;
        }

        .footer-links a:hover {
            text-decoration: underline;
        }
    </style>
</head>

<body>

<div class="topbar">
    <div class="container d-flex justify-content-between align-items-center">
        <div class="brand">Gladyadorez</div>

        <a href="{{ route('welcome') }}" class="btn btn-outline-dark btn-sm">
            Inicio
        </a>
    </div>
</div>

<main class="hero">
    <section class="support-card">

        <div class="support-icon">
            ?
        </div>

        <h1 class="h2 fw-bold mb-2">Centro de soporte</h1>

        <p class="muted mb-4">
            ¿Necesitas ayuda con Gladyadorez? Estamos disponibles para ayudarte
            con dudas, problemas de acceso, funcionamiento de la aplicación o
            cualquier inconveniente relacionado con nuestros servicios.
        </p>

        <h2 class="h5 fw-bold mb-3">Contacto</h2>

        <div class="d-grid gap-3 mb-4">

            <div class="contact-item">
                <div class="contact-label">Correo electrónico</div>
                <a class="contact-value"
                   href="mailto:info@rrb-soluciones.com">
                    info@rrb-soluciones.com
                </a>
            </div>

            <div class="contact-item">
                <div class="contact-label">Teléfono</div>
                <a class="contact-value"
                   href="tel:+524434765057">
                    443 476 5057
                </a>
            </div>

        </div>

        <div class="d-grid d-sm-flex gap-2 mb-4">

            <a href="mailto:info@rrb-soluciones.com?subject=Soporte%20Gladyadorez"
               class="btn btn-dark btn-lg">
                Enviar correo
            </a>

            <a href="https://wa.me/524434765057"
               class="btn btn-outline-dark btn-lg"
               target="_blank"
               rel="noopener noreferrer">
                WhatsApp
            </a>

        </div>

        <hr>

        <p class="small muted mb-0">
            Al comunicarte con soporte, describe brevemente el problema que
            estás experimentando para que podamos ayudarte de manera más rápida.
        </p>

        <div class="footer-links small mt-4">
            <a href="{{ route('privacy') }}">Política de privacidad</a>
            <span class="mx-2">·</span>
            <span>© {{ date('Y') }} Gladyadorez</span>
        </div>

    </section>
</main>

</body>
</html>
