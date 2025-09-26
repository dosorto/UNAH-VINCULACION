<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Confirmación de Registro de Proyecto - {{ $appName }}</title>
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
        }
        .header .subtitle {
            font-size: 16px;
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
            font-size: 20px;
            font-weight: 700;
            margin: 0 0 10px 0;
            text-transform: uppercase;
        }
        .institution-info p {
            color: #6c757d;
            font-size: 14px;
            margin: 5px 0;
            font-style: italic;
        }
        .greeting {
            font-size: 18px;
            color: #004080;
            margin-bottom: 25px;
            font-weight: 600;
            text-align: justify;
        }
        .official-notice {
            background-color: #e8f4fd;
            border: 1px solid #004080;
            border-radius: 8px;
            padding: 25px;
            margin: 25px 0;
            text-align: center;
        }
        .official-notice .stamp {
            display: inline-block;
            background-color: #004080;
            color: white;
            padding: 8px 16px;
            border-radius: 25px;
            font-size: 14px;
            font-weight: 700;
            margin-bottom: 15px;
            text-transform: uppercase;
            letter-spacing: 1px;
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
            font-size: 20px;
            font-weight: 700;
            color: #004080;
            margin-bottom: 20px;
            text-align: center;
            text-transform: uppercase;
            padding-bottom: 15px;
            border-bottom: 2px solid #004080;
        }
        .project-details {
            margin: 20px 0;
        }
        .detail-item {
            margin: 15px 0;
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 12px 0;
            border-bottom: 1px solid #dee2e6;
        }
        .detail-label {
            font-weight: 700;
            color: #495057;
            font-size: 15px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .detail-value {
            color: #212529;
            font-weight: 600;
            text-align: right;
            max-width: 60%;
        }
        .institutional-notice {
            background-color: #fff3cd;
            border: 1px solid #ffc107;
            border-radius: 8px;
            padding: 25px;
            margin: 25px 0;
        }
        .institutional-notice h3 {
            color: #856404;
            margin-top: 0;
            font-size: 18px;
            font-weight: 700;
            text-transform: uppercase;
        }
        .institutional-notice ul {
            margin: 15px 0;
            padding-left: 25px;
        }
        .institutional-notice li {
            margin: 10px 0;
            color: #856404;
            font-weight: 500;
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
            padding: 15px 35px;
            text-decoration: none;
            border-radius: 8px;
            font-weight: 700;
            margin: 15px 0;
            text-align: center;
            text-transform: uppercase;
            letter-spacing: 1px;
            box-shadow: 0 4px 8px rgba(0, 64, 128, 0.3);
            transition: all 0.3s ease;
        }
        .action-button:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 12px rgba(0, 64, 128, 0.4);
        }
        .footer {
            background-color: #2c3e50;
            color: #ecf0f1;
            padding: 30px;
            text-align: center;
            font-size: 13px;
        }
        .footer .university-seal {
            margin-bottom: 15px;
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
        .signature-section {
            margin-top: 30px;
            padding: 20px;
            background-color: #f8f9fa;
            border-radius: 8px;
            text-align: center;
        }
        .signature-section p {
            font-size: 14px;
            color: #6c757d;
            font-style: italic;
            margin: 5px 0;
        }
        @media (max-width: 900px) {
            body {
                padding: 10px;
            }
            .content {
                padding: 25px 20px;
            }
            .detail-item {
                flex-direction: column;
                align-items: flex-start;
            }
            .detail-label {
                margin-bottom: 5px;
            }
            .detail-value {
                text-align: left;
                max-width: 100%;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <img src="{{ $logoUrl }}" alt="{{ $appName }} Logo">
            <h1>Confirmación de Registro de Proyecto</h1>
            <div class="subtitle">Sistema de Gestión de Proyectos de Vinculación</div>
        </div>
        
        <div class="content">
            <div class="institution-info">
                <h2>Universidad Nacional Autónoma de Honduras</h2>
                <p>Vicerrectoría de Relaciones Externas</p>
                <p>Dirección de Vinculación Universidad-Sociedad</p>
            </div>

            <div class="greeting">
                Estimado/a {{ $nombreCompleto }},
            </div>
            
            <p style="text-align: justify; margin-bottom: 25px;">
                Por medio de la presente, nos dirigimos a usted para <strong>notificarle</strong> 
                que su solicitud de registro de proyecto ha sido enviada exitosamente por medio del Sistema NEXO.
            </p>
            
            <div class="project-info">
                <div class="project-title">{{ $proyecto->nombre_proyecto }}</div>
                
                <div class="project-details">
                    <div class="detail-item">
                        <span class="detail-label">Código de proyecto:</span>
                        <span class="detail-value">{{ $proyecto->codigo_proyecto ?: 'En proceso de asignación' }}</span>
                    </div>
                    <div class="detail-item">
                        <span class="detail-label">Fecha de Registro:</span>
                        <span class="detail-value">{{ $fechaCreacion }}</span>
                    </div>
                    <div class="detail-item">
                        <span class="detail-label">Coordinador/a:</span>
                        <span class="detail-value">{{ $nombreCompleto }}</span>
                    </div>
                </div>
            </div>

            <p style="text-align: justify; margin: 25px 0;">
                <strong>Importante:</strong> Este correo electrónico confirma la solicitud de registro de su proyecto en el sistema institucional NEXO.
            </p>
            
            <div class="action-section">
                <p style="margin-bottom: 15px; font-weight: 600; color: #004080;">
                    Acceda a su panel institucional para seguimiento:
                </p>
                <a href="{{ $actionUrl }}" class="action-button">
                    Acceder al Sistema Institucional
                </a>
            </div>
        </div>
        
        <div class="footer">
            <div class="university-seal">
                <p><strong>UNIVERSIDAD NACIONAL AUTÓNOMA DE HONDURAS</strong></p>
                <p>Vicerrectoría Académica - Dirección de Vinculación Universidad-Sociedad</p>
                <p>{{ $appName }} - Sistema de Gestión de Proyectos de Vinculación</p>
            </div>
            <p>&copy; {{ date('Y') }} Nexo - Todos los derechos reservados</p>
            <div class="contact-info">
                <p>Ciudad Universitaria José Trinidad Reyes | Tegucigalpa, Honduras</p>
                <p>Este es un mensaje automático del sistema NEXO</p>
            </div>
        </div>
    </div>
</body>
</html>
