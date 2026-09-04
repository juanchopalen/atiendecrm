<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Política de Privacidad — AtiendeCRM</title>
    <link rel="icon" type="image/png" href="{{ asset('img/brand/icon-solid.png') }}">
    <link rel="shortcut icon" type="image/png" href="{{ asset('img/brand/icon-solid.png') }}">
    <style>
        :root {
            --bg: #F8F6FC;
            --ink: #1E1B2E;
            --ink-soft: #544C74;
            --violet: #6B46E8;
            --card: #FFFFFF;
            --border: #E7E2F5;
        }

        @media (prefers-color-scheme: dark) {
            :root {
                --bg: #141021;
                --ink: #F1EEFB;
                --ink-soft: #BDB4DC;
                --violet: #A78BFF;
                --card: #1C1830;
                --border: #2C2645;
            }
        }

        html, body {
            margin: 0;
            padding: 0;
            background: var(--bg);
            color: var(--ink);
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            line-height: 1.6;
            display: flex;
            justify-content: center;
            padding: 48px 20px 96px;
        }

        main {
            max-width: 720px;
            width: 100%;
        }

        .logo {
            width: 220px;
            max-width: 60%;
            height: auto;
            margin-bottom: 32px;
        }

        h1 {
            font-size: 28px;
            margin: 0 0 4px;
        }

        .updated {
            color: var(--ink-soft);
            font-size: 14px;
            margin: 0 0 32px;
        }

        section {
            background: var(--card);
            border: 1px solid var(--border);
            border-radius: 12px;
            padding: 24px 28px;
            margin-bottom: 20px;
        }

        h2 {
            font-size: 18px;
            color: var(--violet);
            margin: 0 0 12px;
        }

        p, li {
            font-size: 15px;
            color: var(--ink);
        }

        ul {
            padding-left: 20px;
            margin: 8px 0;
        }

        a {
            color: var(--violet);
        }

        .placeholder {
            background: rgba(107, 70, 232, 0.12);
            border-radius: 4px;
            padding: 0 4px;
        }
    </style>
</head>
<body>
    <main>
        <img class="logo" src="{{ asset('img/brand/atiendecrm-horizontal-logo.png') }}" alt="AtiendeCRM">
        <h1>Política de Privacidad</h1>
        <p class="updated">Última actualización: {{ now()->translatedFormat('d \d\e F \d\e Y') }}</p>

        <section>
            <h2>1. Quiénes somos</h2>
            <p>
                AtiendeCRM es una plataforma operada por <span class="placeholder">ADEMIA ADMINISTRACION & IT , C.A</span>
                ("nosotros", "Ademia") que corredurías de seguros ("el corretaje", "el cliente") utilizan para
                gestionar sus clientes, pólizas, casos de servicio y comunicación por WhatsApp. Esta política
                describe cómo recopilamos, usamos y protegemos los datos personales que se procesan a través de
                AtiendeCRM, incluyendo los que se obtienen mediante la API de WhatsApp Business de Meta.
            </p>
        </section>

        <section>
            <h2>2. Qué datos recopilamos</h2>
            <ul>
                <li>
                    <strong>Datos de contacto de WhatsApp:</strong> número de teléfono, nombre de perfil, y el
                    contenido de los mensajes enviados y recibidos a través de los números de WhatsApp Business
                    conectados a la plataforma.
                </li>
                <li>
                    <strong>Datos del cliente del corretaje:</strong> nombre, cédula/identificación, correo
                    electrónico, dirección, y la información de pólizas, pagos y casos de servicio que el
                    corretaje registra en AtiendeCRM.
                </li>
                <li>
                    <strong>Datos de uso:</strong> registros técnicos de los mensajes y eventos entregados por la
                    API de WhatsApp Business (estado de entrega, metadatos de la conversación).
                </li>
            </ul>
        </section>

        <section>
            <h2>3. Para qué usamos estos datos</h2>
            <ul>
                <li>Permitir que el corretaje se comunique con sus clientes por WhatsApp (confirmaciones,
                    recordatorios de pago, respuestas a consultas y soporte).</li>
                <li>Registrar el historial de interacciones dentro de los casos de servicio del corretaje.</li>
                <li>Generar respuestas automáticas mediante un agente de atención cuando el corretaje lo habilita.</li>
                <li>Mejorar la calidad y confiabilidad del servicio (monitoreo técnico y prevención de errores).</li>
            </ul>
        </section>

        <section>
            <h2>4. Con quién compartimos los datos</h2>
            <p>
                Los mensajes de WhatsApp se procesan a través de la API de WhatsApp Business de Meta Platforms,
                Inc., conforme a la
                <a href="https://www.whatsapp.com/legal/business-data-processing-terms" target="_blank" rel="noopener">Política de Datos de WhatsApp Business</a>.
                No vendemos datos personales a terceros. Los datos de cada corretaje son visibles únicamente para
                los usuarios autorizados de ese corretaje dentro de la plataforma.
            </p>
        </section>

        <section>
            <h2>5. Conservación y seguridad</h2>
            <p>
                Conservamos los datos mientras el corretaje mantenga una cuenta activa en AtiendeCRM, o según lo
                requiera la relación contractual y la normativa aplicable. Aplicamos medidas técnicas y
                organizativas razonables (control de acceso por corretaje, cifrado en tránsito) para proteger la
                información contra acceso no autorizado.
            </p>
        </section>

        <section>
            <h2>6. Tus derechos</h2>
            <p>
                Si eres cliente de un corretaje que usa AtiendeCRM y deseas acceder, corregir o solicitar la
                eliminación de tus datos, contacta directamente al corretaje con el que tienes tu póliza — es
                quien controla esos datos. Si tienes dudas sobre cómo Ademia procesa los datos como proveedor de
                la plataforma, escríbenos a
                <a href="mailto:{{ config('mail.from.address') }}">{{ config('mail.from.address') }}</a>.
            </p>
        </section>

        <section>
            <h2>7. Cambios a esta política</h2>
            <p>
                Podemos actualizar esta política ocasionalmente. Publicaremos cualquier cambio en esta misma
                página con la fecha de actualización correspondiente.
            </p>
        </section>
    </main>
</body>
</html>
