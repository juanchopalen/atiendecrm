<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>AtiendeCRM</title>
    <style>
        :root {
            --bg: #F8F6FC;
            --ink-soft: #544C74;
            --violet: #6B46E8;
        }

        @media (prefers-color-scheme: dark) {
            :root {
                --bg: #141021;
                --ink-soft: #BDB4DC;
                --violet: #A78BFF;
            }
        }

        html, body {
            height: 100%;
            margin: 0;
        }

        body {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: 24px;
            background: var(--bg);
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
        }

        .logo {
            max-width: 90%;
            width: 360px;
            height: auto;
        }

        .login-link {
            color: var(--ink-soft);
            font-size: 14px;
            font-weight: 600;
            text-decoration: none;
        }

        .login-link:hover {
            color: var(--violet);
        }
    </style>
</head>
<body>
    <img class="logo" src="{{ asset('img/brand/atiendecrm-horizontal-logo.png') }}" alt="AtiendeCRM">
    <a class="login-link" href="{{ url('/admin/login') }}">Iniciar sesión</a>
</body>
</html>
