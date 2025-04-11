<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Notificación - {{ $subject }}</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f9f9f9;
            margin: 0;
            padding: 0;
        }

        .email-container {
            max-width: 600px;
            margin: 20px auto;
            background-color: #ffffff;
            border: 1px solid #e0e0e0;
            border-radius: 12px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            overflow: hidden;
        }

        .header {
            background: linear-gradient(135deg, #003366, #0074d9);
            /* Gradiente azul */
            color: #ffffff;
            text-align: center;
            padding: 20px;
        }

        .header img {
            max-width: 150px;
            margin-bottom: 10px;
        }

        .header h1 {
            font-size: 24px;
            font-weight: bold;
            margin: 0;
        }

        .content {
            padding: 20px;
            line-height: 1.6;
            color: #333333;
        }

        .content p {
            margin: 10px 0;
        }

        .button {
            display: inline-block;
            background: linear-gradient(135deg, #ffcc00, #facc15);
            /* Gradiente amarillo */
            color: #003366;
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
        }
    </style>
</head>

<body>
    <div class="email-container">
        <!-- Header -->
        <div class="header">
            <img src="{{ $logoUrl }}" alt="NEXO">
            <h1>{{ $appName }}</h1>
        </div>

        <!-- Contenido -->
        <div class="content">
            <p>Estimado/a <strong>{{ $empleadoNombre }},</strong>,</p>
            <p>{{ $mensaje }}</p>
            <p>Este proyecto ya está disponible para su revisión y seguimiento. 
                Si desea más detalles, puede acceder al apartado correspondiente haciendo clic en el siguiente enlace:</p>
            <a href="{{ $actionUrl }}" class="button">Ir a NEXO</a>
        </div>

        <!-- Footer -->
        <div class="footer">
            <p>&copy; {{ date('Y') }} {{ $appName }}. Todos los derechos reservados.</p>
        </div>
    </div>
</body>

</html>