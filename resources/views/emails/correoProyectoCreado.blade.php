<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Notificación - {{ $subject }}</title>
    <style>
        body {
            margin: 0;
            font-family: 'Arial', sans-serif;
            background-color: #ddb206;
            padding: 20px;
        }

        .container {
            max-width: 600px;
            margin: auto;
            background-color: #fff;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
        }

        .header {
            background: linear-gradient(to right, #1c1c38, #0b0b8b);
            color: white;
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
        }

        .header p {
            margin: 5px 0 0;
            font-size: 14px;
            opacity: 0.8;
        }

        .content {
            padding: 30px 20px;
            font-size: 15px;
            color: #333;
        }

        .download-link {
            color: #f6a400;
            font-size: 14px;
            text-decoration: none;
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
    <div class="container">
        <div class="header">
            <img src="{{ $logoUrl }}" alt="NEXO">
            <h1>{{ $appName }}</h1>
            <p>Conectando la academia con la sociedad para el desarrollo integral de Honduras a través de proyectos de impacto social, investigación aplicada y transferencia de conocimiento.</p>
        </div>
        <div class="content">
            <p>Estimado/a <strong>{{ $empleadoNombre }}</strong>,</p>
            <p>{{ $mensaje }}</p>
            <p>Este proyecto ya está disponible para su revisión y seguimiento. 
                Si desea más detalles, puede acceder al apartado correspondiente haciendo clic en el siguiente enlace:</p>
            <a href="{{ $actionUrl }}" class="button">Ir al apartado</a>

            <p>UNAH-NEXO<br><a href="notificacionespoa@unah.edu.hn">notificacionespoa@unah.edu.hn</a></p>
        </div>
        <div class="footer">
            <p>&copy; {{ date('Y') }} {{ $appName }}.</p>
        </div>
    </div>
</body>

</html>