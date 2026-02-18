<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="color-scheme" content="light dark">
    <meta name="supported-color-schemes" content="light dark">
    <title>Actualización de Firmas - {{ $appName }}</title>
    <style>
        :root {
            color-scheme: light dark;
            supported-color-schemes: light dark;
        }

        body {
            font-family: 'Times New Roman', Times, serif;
            line-height: 1.8;
            color: #2c3e50;
            max-width: 900px;
            margin: 0 auto;
            padding: 20px;
            background-color: #f8f9fa;
        }
        .container {
            background-color: #ffffff;
            border: 2px solid #004080;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.15);
        }
        .header {
            background: linear-gradient(135deg, #004080 0%, #0066cc 100%);
            color: #ffffff;
            padding: 40px 30px;
            text-align: center;
            position: relative;
        }
        .header::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, #ffd700 0%, #ffed4e 50%, #ffd700 100%);
        }
        .header img {
            max-width: 100px;
            height: auto;
            margin-bottom: 20px;
            filter: brightness(1.2);
        }
        .header h1 {
            margin: 0;
            font-size: 26px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: #ffffff;
        }
        .content {
            padding: 40px 30px;
            background-color: #ffffff;
        }
        .greeting {
            font-size: 18px;
            color: #004080;
            margin-bottom: 25px;
            font-weight: 600;
        }
        .greeting strong {
            color: #004080;
        }
        .official-notice {
            background-color: #fff3cd;
            border: 1px solid #ffc107;
            border-radius: 8px;
            padding: 25px;
            margin: 25px 0;
        }
        .official-notice h2 {
            color: #856404;
            font-size: 20px;
            margin-top: 0;
        }
        .official-notice p {
            color: #856404;
            margin: 0;
        }
        .project-info {
            background-color: #f8f9fa;
            border-left: 4px solid #004080;
            padding: 20px;
            margin: 25px 0;
            border-radius: 4px;
        }
        .project-info h3 {
            color: #004080;
            margin-top: 0;
            font-size: 18px;
        }
        .info-row {
            margin: 10px 0;
            padding: 10px 0;
            border-bottom: 1px solid #dee2e6;
        }
        .info-row:last-child {
            border-bottom: none;
        }
        .info-label {
            font-weight: 600;
            color: #495057;
            display: inline-block;
            min-width: 150px;
        }
        .info-value {
            color: #212529;
        }
        .changes-section {
            background-color: #e8f4fd;
            border: 1px solid #004080;
            border-radius: 8px;
            padding: 20px;
            margin: 25px 0;
        }
        .changes-section h3 {
            color: #004080;
            margin-top: 0;
        }
        .change-item {
            background-color: #ffffff;
            padding: 15px;
            margin: 10px 0;
            border-radius: 4px;
            border-left: 3px solid #28a745;
        }
        .change-item strong {
            color: #004080;
        }
        .footer {
            background-color: #f8f9fa;
            padding: 30px;
            text-align: center;
            font-size: 14px;
            color: #6c757d;
            border-top: 2px solid #dee2e6;
        }
        .footer p {
            margin: 5px 0;
            color: #6c757d;
        }
        .button {
            display: inline-block;
            padding: 12px 30px;
            background-color: #004080;
            color: #ffffff !important;
            text-decoration: none;
            border-radius: 5px;
            font-weight: 600;
            margin: 20px 0;
        }
        .button:hover {
            background-color: #003366;
        }

        /* Soporte para modo oscuro */
        @media (prefers-color-scheme: dark) {
            body {
                background-color: #1a1a1a;
                color: #e0e0e0;
            }

            .container {
                background-color: #2d2d2d;
                border-color: #3366ff;
            }

            .content {
                background-color: #2d2d2d;
                color: #e0e0e0;
            }

            .greeting {
                color: #66b3ff;
            }

            .greeting strong {
                color: #66b3ff;
            }

            .official-notice {
                background-color: #3d3520;
                border-color: #ffa500;
            }

            .official-notice h2 {
                color: #ffcc66;
            }

            .official-notice p {
                color: #ffcc66;
            }

            .project-info {
                background-color: #1a1a1a;
                border-left-color: #66b3ff;
            }

            .project-info h3 {
                color: #66b3ff;
            }

            .info-row {
                border-bottom-color: #404040;
            }

            .info-label {
                color: #a0a0a0;
            }

            .info-value {
                color: #e0e0e0;
            }

            .changes-section {
                background-color: #1a2d3d;
                border-color: #66b3ff;
            }

            .changes-section h3 {
                color: #66b3ff;
            }

            .change-item {
                background-color: #2d2d2d;
                border-left-color: #4dff4d;
            }

            .change-item strong {
                color: #66b3ff;
            }

            .footer {
                background-color: #1a1a1a;
                color: #999999;
                border-top-color: #404040;
            }

            .footer p {
                color: #999999;
            }

            .button {
                background-color: #3366ff;
                color: #ffffff !important;
            }

            .button:hover {
                background-color: #2952cc;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <img src="{{ $logoUrl }}" alt="Logo NEXO">
            <h1>Actualización de Firmas del Proyecto</h1>
        </div>

        <div class="content">
            <p class="greeting">
                Estimado/a <strong>{{ $destinatario->nombre_completo ?? 'Usuario' }}</strong>,
            </p>

            <div class="official-notice">
                <h2>Notificación Importante</h2>
                <p>
                    Le informamos que se ha registado <strong>un nuevo proyecto de vinculación</strong> bajo su coordinación o participación.
                </p>
            </div>

            <div class="project-info">
                <h3>Información del Proyecto</h3>
                <div class="info-row">
                    <span class="info-label">Proyecto:</span>
                    <span class="info-value">{{ $proyecto->nombre_proyecto }}</span>
                </div>
                @if($proyecto->codigo_proyecto)
                <div class="info-row">
                    <span class="info-label">Código:</span>
                    <span class="info-value">{{ $proyecto->codigo_proyecto }}</span>
                </div>
                @endif
                <div class="info-row">
                    <span class="info-label">Fecha de actualización:</span>
                    <span class="info-value">{{ now()->format('d/m/Y H:i') }}</span>
                </div>
            </div>

            <div style="text-align: center; margin: 30px 0;">
                <p style="margin-bottom: 15px;">
                    Para más detalles, puede ingresar al sistema NEXO:
                </p>
                <a href="https://nexo.unah.edu.hn" class="button">
                    Acceder a NEXO
                </a>
            </div>
        </div>

        <div class="footer">
            <p><strong>Universidad Nacional Autónoma de Honduras</strong></p>
            <p>Dirección de Vinculación Universidad-Sociedad</p>
            <p>{{ $appName }} - Sistema de Gestión de Proyectos de Vinculación</p>
            <p style="margin-top: 15px; font-size: 12px;">
                Este es un correo automático, por favor no responder a esta dirección.
            </p>
        </div>
    </div>
</body>
</html>
