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
            margin: 0 auto;
            background-color: #ffffff;
            border: 1px solid #e0e0e0;
            border-radius: 8px;
            overflow: hidden;
        }
        .header {
            background-color: #003366; /* Color corporativo de la universidad */
            color: #ffffff;
            text-align: center;
            padding: 20px;
        }
        .header img {
            max-width: 150px;
        }
        .content {
            padding: 20px;
            line-height: 1.6;
            color: #333333;
        }
        .footer {
            background-color: #f4f4f4;
            text-align: center;
            padding: 10px;
            font-size: 12px;
            color: #777777;
        }
        .button {
            display: inline-block;
            background-color: #003366;
            color: #ffffff;
            text-decoration: none;
            padding: 10px 20px;
            border-radius: 5px;
            margin-top: 20px;
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
            <p>Hola {{ $empleadoNombre }},</p>
            <p>{{ $messageBody }}</p>
            <p>Para más detalles, haz clic en el siguiente botón:</p>
            <a href="{{ $actionUrl }}" class="button">Ir al apartado</a>
        </div>

        <!-- Footer -->
        <div class="footer">
            <p>&copy; {{ date('Y') }} {{ $appName }}. Todos los derechos reservados.</p>
        </div>
    </div>
</body>
</html>