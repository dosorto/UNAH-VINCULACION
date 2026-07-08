<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Revisión pendiente - {{ $appName }}</title>
    <style>
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
            background-color: white;
            border: 2px solid #004080;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.15);
        }
        .header {
            background: linear-gradient(135deg, #004080 0%, #0066cc 100%);
            color: white;
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
        .header h1 {
            margin: 0;
            font-size: 24px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        .header .subtitle {
            font-size: 15px;
            margin-top: 10px;
            opacity: 0.9;
            font-weight: 300;
        }
        .content {
            padding: 40px 30px;
        }
        .institution-info {
            text-align: center;
            margin-bottom: 30px;
            padding: 20px;
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            border-radius: 8px;
            border-left: 4px solid #004080;
        }
        .institution-info h2 {
            color: #004080;
            font-size: 18px;
            font-weight: 700;
            margin: 0 0 8px 0;
            text-transform: uppercase;
        }
        .institution-info p {
            color: #6c757d;
            font-size: 13px;
            margin: 4px 0;
            font-style: italic;
        }
        .greeting {
            font-size: 17px;
            color: #004080;
            margin-bottom: 20px;
            font-weight: 600;
        }
        .alert-box {
            background-color: #fff8e1;
            border: 1px solid #f59e0b;
            border-left: 5px solid #f59e0b;
            border-radius: 8px;
            padding: 20px 25px;
            margin: 20px 0;
        }
        .alert-box p {
            margin: 0;
            color: #78350f;
            font-size: 15px;
            font-weight: 600;
        }
        .project-info {
            background-color: #ffffff;
            border: 2px solid #e9ecef;
            border-radius: 8px;
            padding: 25px;
            margin: 25px 0;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
        }
        .project-title {
            font-size: 18px;
            font-weight: 700;
            color: #004080;
            margin-bottom: 16px;
            padding-bottom: 12px;
            border-bottom: 2px solid #004080;
        }
        .detail-item {
            display: flex;
            justify-content: space-between;
            padding: 10px 0;
            border-bottom: 1px solid #dee2e6;
        }
        .detail-item:last-child {
            border-bottom: none;
        }
        .detail-label {
            font-weight: 700;
            color: #495057;
            font-size: 14px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .detail-value {
            color: #212529;
            font-weight: 600;
            text-align: right;
            max-width: 60%;
        }
        .badge-etapa {
            display: inline-block;
            background-color: #004080;
            color: white;
            padding: 4px 14px;
            border-radius: 20px;
            font-size: 13px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .action-section {
            text-align: center;
            margin: 30px 0;
            padding: 25px;
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            border-radius: 8px;
        }
        .action-button {
            display: inline-block;
            background: linear-gradient(135deg, #004080 0%, #0066cc 100%);
            color: white;
            padding: 14px 32px;
            text-decoration: none;
            border-radius: 8px;
            font-weight: 700;
            margin: 12px 0;
            text-transform: uppercase;
            letter-spacing: 1px;
            box-shadow: 0 4px 8px rgba(0, 64, 128, 0.3);
        }
        .footer {
            background-color: #2c3e50;
            color: #ecf0f1;
            padding: 30px;
            text-align: center;
            font-size: 13px;
        }
        .footer p {
            margin: 8px 0;
            line-height: 1.6;
        }
        .footer .contact-info {
            font-size: 12px;
            opacity: 0.8;
            margin-top: 15px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Revisión pendiente de proyecto</h1>
            <div class="subtitle">Sistema de Gestión de Proyectos de Vinculación</div>
        </div>

        <div class="content">
            <div class="institution-info">
                <h2>Universidad Nacional Autónoma de Honduras</h2>
                <p>Dirección de Vinculación Universidad-Sociedad</p>
            </div>

            <div class="greeting">
                Estimado/a {{ $nombreRevisor }},
            </div>

            <p style="text-align: justify; margin-bottom: 20px;">
                Le informamos que tiene un proyecto asignado pendiente de revisión en el sistema <strong>{{ $appName }}</strong>.
                Le correspondre actuar en la siguiente etapa del flujo de aprobación:
            </p>

            <div class="alert-box">
                <p>Etapa asignada: <span class="badge-etapa">{{ $nombreEtapa }}</span></p>
            </div>

            <div class="project-info">
                <div class="project-title">{{ $nombreProyecto }}</div>
                <div class="detail-item">
                    <span class="detail-label">Etapa a revisar</span>
                    <span class="detail-value">{{ $nombreEtapa }}</span>
                </div>
                <div class="detail-item">
                    <span class="detail-label">Fecha de notificación</span>
                    <span class="detail-value">{{ now()->format('d/m/Y H:i') }}</span>
                </div>
            </div>

            <div class="action-section">
                <p style="margin-bottom: 15px; font-weight: 600; color: #004080;">
                    Acceda al sistema para revisar el proyecto:
                </p>
                <a href="{{ $actionUrl }}" class="action-button">
                    Ir al sistema NEXO
                </a>
                <p style="margin-top: 12px; font-size: 13px; color: #6c757d;">
                    Si el botón no funciona, copie esta dirección en su navegador: {{ $actionUrl }}
                </p>
            </div>
        </div>

        <div class="footer">
            <p><strong>UNIVERSIDAD NACIONAL AUTÓNOMA DE HONDURAS</strong></p>
            <p>Vicerrectoría Académica - Dirección de Vinculación Universidad-Sociedad</p>
            <p>{{ $appName }} - Sistema de Gestión de Proyectos de Vinculación</p>
            <p>&copy; {{ date('Y') }} Nexo - Todos los derechos reservados</p>
            <div class="contact-info">
                <p>Ciudad Universitaria José Trinidad Reyes | Tegucigalpa, Honduras</p>
                <p>Este es un mensaje automático del sistema NEXO — no responda a este correo</p>
            </div>
        </div>
    </div>
</body>
</html>
