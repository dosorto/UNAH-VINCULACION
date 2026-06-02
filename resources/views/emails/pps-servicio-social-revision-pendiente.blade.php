<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PPS/Servicio Social pendiente - {{ $appName }}</title>
    <style>
        body {
            background: #f3f4f6;
            color: #1f2937;
            font-family: Arial, sans-serif;
            line-height: 1.6;
            margin: 0;
            padding: 24px;
        }
        .container {
            background: #ffffff;
            border: 1px solid #d1d5db;
            border-radius: 8px;
            margin: 0 auto;
            max-width: 720px;
            overflow: hidden;
        }
        .header {
            background: #004080;
            color: #ffffff;
            padding: 24px;
        }
        .header h1 {
            font-size: 22px;
            margin: 0;
        }
        .content {
            padding: 24px;
        }
        .details {
            background: #f9fafb;
            border-left: 4px solid #004080;
            margin: 20px 0;
            padding: 16px;
        }
        .details p {
            margin: 8px 0;
        }
        .label {
            color: #374151;
            font-weight: bold;
        }
        .button {
            background: #004080;
            border-radius: 6px;
            color: #ffffff;
            display: inline-block;
            font-weight: bold;
            margin-top: 16px;
            padding: 12px 18px;
            text-decoration: none;
        }
        .footer {
            background: #111827;
            color: #d1d5db;
            font-size: 13px;
            padding: 16px 24px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Registro PPS/Servicio Social pendiente de revision</h1>
        </div>
        <div class="content">
            <p>Hola {{ $destinatario->name }},</p>
            <p>Hay un registro FORM-DVUS-015/016 pendiente de revision en la etapa que tienes asignada.</p>

            <div class="details">
                <p><span class="label">Registro:</span> {{ $registro->codigo_registro ?: '#' . $registro->id }}</p>
                <p><span class="label">Estudiante:</span> {{ $registro->nombre_estudiante }}</p>
                <p><span class="label">Estado actual:</span> {{ ucfirst((string) $registro->estado) }}</p>
                <p><span class="label">Etapa actual:</span> {{ $etapa->nombre }}</p>
                <p><span class="label">Tipo:</span> {{ $registro->tipo_pps_ss }}</p>
            </div>

            <a class="button" href="{{ $detalleUrl }}">Ver detalle</a>
        </div>
        <div class="footer">
            Este mensaje fue generado automaticamente por {{ $appName }}.
        </div>
    </div>
</body>
</html>
