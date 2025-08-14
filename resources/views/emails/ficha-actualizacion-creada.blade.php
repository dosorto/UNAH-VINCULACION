<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ficha de Actualización Enviada a Firmar</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            margin: 0;
            padding: 0;
            background-color: #f8f9fa;
            line-height: 1.6;
        }
        .container {
            max-width: 700px;
            margin: 0 auto;
            background-color: white;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            overflow: hidden;
        }
        .header {
            background: linear-gradient(135deg, #1e40af, #3b82f6);
            color: white;
            padding: 30px 40px;
            text-align: center;
        }
        .header h1 {
            margin: 0;
            font-size: 28px;
            font-weight: bold;
        }
        .header p {
            margin: 10px 0 0 0;
            font-size: 16px;
            opacity: 0.9;
        }
        .content {
            padding: 40px;
        }
        .success-badge {
            display: inline-block;
            background-color: #10b981;
            color: white;
            padding: 8px 16px;
            border-radius: 20px;
            font-size: 14px;
            font-weight: bold;
            margin-bottom: 20px;
        }
        .project-details {
            background-color: #f8fafc;
            border-left: 4px solid #3b82f6;
            padding: 20px;
            margin: 20px 0;
            border-radius: 0 8px 8px 0;
        }
        .detail-row {
            display: flex;
            margin: 10px 0;
            align-items: center;
        }
        .detail-label {
            font-weight: bold;
            color: #374151;
            min-width: 140px;
        }
        .detail-value {
            color: #1f2937;
            font-weight: 500;
        }
        .institutional-notice {
            background-color: #fef3c7;
            border: 1px solid #f59e0b;
            border-radius: 8px;
            padding: 20px;
            margin: 25px 0;
        }
        .institutional-notice h3 {
            color: #92400e;
            margin: 0 0 15px 0;
            font-size: 18px;
        }
        .institutional-notice ul {
            margin: 0;
            padding-left: 20px;
        }
        .institutional-notice li {
            color: #78350f;
            margin: 8px 0;
        }
        .footer {
            background-color: #f1f5f9;
            padding: 30px 40px;
            text-align: center;
            border-top: 1px solid #e2e8f0;
        }
        .footer p {
            margin: 5px 0;
            color: #64748b;
            font-size: 14px;
        }
        .footer .university-name {
            font-weight: bold;
            color: #1e40af;
            font-size: 16px;
        }
        .logo {
            max-width: 80px;
            height: auto;
            margin-bottom: 15px;
        }
        .btn {
            display: inline-block;
            background-color: #10b981;
            color: white;
            padding: 12px 24px;
            text-decoration: none;
            border-radius: 6px;
            font-weight: bold;
            margin: 20px 0;
        }
        .btn:hover {
            background-color: #059669;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <img src="{{ asset('images/logo_nuevo.png') }}" alt="UNAH Logo" class="logo">
            <h1>🔄 Ficha de Actualización Enviada</h1>
            <p>Su ficha de actualización ha sido enviada exitosamente al proceso de firmas</p>
        </div>

        <div class="content">
            <div class="success-badge">
                ✅ FICHA ENVIADA A FIRMAR
            </div>

            <p style="font-size: 16px; color: #374151; margin-bottom: 25px;">
                Estimado/a <strong>{{ $nombreCompleto }}</strong>,
            </p>

            <p style="text-align: justify; margin: 20px 0;">
                Le informamos que su <strong>ficha de actualización del proyecto</strong> ha sido registrada exitosamente 
                en el sistema NEXO-UNAH y ha iniciado el proceso institucional de revisión y firmas correspondientes.
            </p>

            <div class="project-details">
                <h3 style="color: #1e40af; margin: 0 0 15px 0; font-size: 18px;">📋 Detalles de la Actualización</h3>
                
                <div class="detail-row">
                    <span class="detail-label">Proyecto Original:</span>
                    <span class="detail-value">{{ $proyecto->nombre_proyecto }}</span>
                </div>
                
                <div class="detail-row">
                    <span class="detail-label">Código del Proyecto:</span>
                    <span class="detail-value">{{ $proyecto->codigo_proyecto ?? 'En proceso' }}</span>
                </div>
                
                <div class="detail-row">
                    <span class="detail-label">Fecha de Actualización:</span>
                    <span class="detail-value">{{ $fichaActualizacion->fecha_registro->format('d/m/Y H:i') }}</span>
                </div>
                
                <div class="detail-row">
                    <span class="detail-label">Coordinador/a:</span>
                    <span class="detail-value">{{ $nombreCompleto }}</span>
                </div>

                <div class="detail-row">
                    <span class="detail-label">Tipo de Actualización:</span>
                    <span class="detail-value">Modificación del Equipo Ejecutor</span>
                </div>
            </div>
            
            <div class="institutional-notice">
                <h3>⚡ Proceso de Revisión de Actualización</h3>
                <ul>
                    <li><strong>Revisión académica:</strong> Su ficha será evaluada por las autoridades correspondientes</li>
                    <li><strong>Validación institucional:</strong> Se verificará el cumplimiento de normativas de actualización</li>
                    <li><strong>Notificaciones oficiales:</strong> Recibirá comunicaciones sobre el avance del proceso</li>
                    <li><strong>Seguimiento continuo:</strong> Podrá consultar el estado desde su panel de proyectos</li>
                    <li><strong>Proyecto original:</strong> El proyecto original no se ve afectado durante el proceso</li>
                </ul>
            </div>

            <p style="text-align: justify; margin: 25px 0;">
                <strong>Importante:</strong> Esta ficha de actualización constituye un proceso separado del proyecto original. 
                El proyecto mantiene su estado actual mientras se procesa la actualización solicitada. Una vez aprobada, 
                los cambios se aplicarán oficialmente al proyecto.
            </p>

            <p style="text-align: center; margin: 30px 0;">
                <a href="{{ route('proyectosDocente') }}" class="btn">
                    📊 Ver Mis Proyectos
                </a>
            </p>
        </div>

        <div class="footer">
            <p class="university-name">UNIVERSIDAD NACIONAL AUTÓNOMA DE HONDURAS</p>
            <p><strong>Dirección de Vinculación Universidad-Sociedad</strong></p>
            <p>Sistema NEXO-UNAH - Gestión de Proyectos de Vinculación</p>
            <p style="font-size: 12px; margin-top: 15px;">
                Este es un correo automático. Por favor, no responder a esta dirección.
            </p>
        </div>
    </div>
</body>
</html>
