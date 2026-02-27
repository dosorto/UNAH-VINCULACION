<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="color-scheme" content="light dark">
    <meta name="supported-color-schemes" content="light dark">
    <title>Notificación - {{ $subject }}</title>
    <style>
        :root {
            color-scheme: light dark;
            supported-color-schemes: light dark;
        }

        body {
            margin: 0;
            font-family: 'Arial', sans-serif;
            background-color: #ddb206;
            padding: 20px;
        }

        .container {
            max-width: 600px;
            margin: auto;
            background-color: #ffffff;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
        }

        .header {
            background: linear-gradient(to right, #1c1c38, #0b0b8b);
            color: #ffffff;
            padding: 30px 20px;
            text-align: left;
        }

        .header img {
            max-width: 150px;
            margin-bottom: 10px;
        }

        .header h1 {
            margin: 0;
            font-size: 22px;
            color: #ffffff;
        }

        .header p {
            margin: 5px 0 0;
            font-size: 14px;
            opacity: 0.9;
            color: #ffffff;
        }

        .content {
            padding: 30px 20px;
            font-size: 15px;
            color: #333333;
            background-color: #ffffff;
        }

        .content strong {
            color: #1c1c38;
        }

        .content a {
            color: #0b0b8b;
        }

        .button {
            display: inline-block;
            background: linear-gradient(135deg, #ffcc00, #facc15);
            color: #003366 !important;
            text-decoration: none;
            padding: 12px 24px;
            border-radius: 8px;
            font-weight: bold;
            font-size: 16px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            transition: transform 0.2s ease-in-out, box-shadow 0.2s ease-in-out;
        }

        .button:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 8px rgba(0, 0, 0, 0.15);
        }

        .footer {
            background-color: #f4f4f4;
            text-align: center;
            padding: 15px;
            font-size: 12px;
            color: #777777;
            border-top: 1px solid #e0e0e0;
        }

        .footer p {
            margin: 0;
            color: #777777;
        }

        /* Soporte para modo oscuro */
        @media (prefers-color-scheme: dark) {
            body {
                background-color: #1a1a1a;
            }

            .container {
                background-color: #2d2d2d;
                border: 1px solid #404040;
            }

            .content {
                background-color: #2d2d2d;
                color: #e0e0e0;
            }

            .content strong {
                color: #ffcc00;
            }

            .content a {
                color: #66b3ff;
            }

            .footer {
                background-color: #1a1a1a;
                color: #999999;
                border-top: 1px solid #404040;
            }

            .footer p {
                color: #999999;
            }
        }
    </style>
</head>

<body>
    <div class="container">
        <div class="header">
            <h1>{{ $appName }}</h1>
            <p>Conectando la academia con la sociedad para el desarrollo integral de Honduras a través de proyectos de impacto social, investigación aplicada y transferencia de conocimiento.</p>
        </div>
        <div class="content">
            <p>Estimado/a <strong>{{ $empleadoNombre }}</strong>,</p>
            <p>{{ $mensaje }}</p>
            <p>Este proyecto ya está disponible para su revisión y seguimiento. 
                Si desea más detalles, puede acceder al apartado correspondiente haciendo clic en el siguiente enlace:</p>
            <a href="https://nexo.unah.edu.hn/" class="button">Ir al apartado</a>

            <p>UNAH-NEXO<br><a href="notificacionespoa@unah.edu.hn">notificacionespoa@unah.edu.hn</a></p>
        </div>
        <div class="footer">
            <p>&copy; {{ date('Y') }} {{ $appName }}.</p>
        </div>
    </div>
</body>

</html>