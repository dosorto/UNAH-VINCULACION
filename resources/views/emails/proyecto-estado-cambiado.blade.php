<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Notificación de {{ $accion }} - {{ $appName }}</title>
    <style>
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
            padding: 30px;
            text-align: center;
        }
        .header h1 {
            font-size: 28px;
            margin: 0;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        .content {
            padding: 40px;
        }
        .project-info {
            background-color: #f8f9fa;
            border-left: 5px solid #004080;
            padding: 25px;
            margin: 25px 0;
            border-radius: 0 8px 8px 0;
        }
        .project-info h3 {
            color: #004080;
            margin-top: 0;
            font-size: 20px;
            border-bottom: 2px solid #004080;
            padding-bottom: 8px;
        }
        .detail-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin: 15px 0;
            padding: 12px 0;
            border-bottom: 1px solid #ecf0f1;
        }
        .detail-label {
            font-weight: bold;
            color: #34495e;
            flex: 1;
        }
        .detail-value {
            flex: 2;
            text-align: right;
            color: #2c3e50;
        }
        .status-change {
            background: linear-gradient(135deg, #e8f5e8 0%, #d4edda 100%);
            border: 2px solid #28a745;
            border-radius: 8px;
            padding: 20px;
            margin: 25px 0;
            text-align: center;
        }
        .status-change.rejected {
            background: linear-gradient(135deg, #fdf2f2 0%, #f8d7da 100%);
            border-color: #dc3545;
        }
        .status-change h3 {
            margin: 0 0 10px 0;
            color: #28a745;
            font-size: 22px;
        }
        .status-change.rejected h3 {
            color: #dc3545;
        }
        .status-badge {
            background-color: #28a745;
            color: white;
            padding: 8px 16px;
            border-radius: 20px;
            font-weight: bold;
            display: inline-block;
            margin: 10px 0;
        }
        .status-badge.rejected {
            background-color: #dc3545;
        }
        .comment-section {
            background-color: #fff3cd;
            border: 1px solid #ffeaa7;
            border-radius: 8px;
            padding: 20px;
            margin: 25px 0;
        }
        .comment-section h4 {
            color: #856404;
            margin-top: 0;
        }
        .comment-text {
            font-style: italic;
            color: #6c757d;
            background-color: white;
            padding: 15px;
            border-radius: 5px;
            border-left: 4px solid #ffc107;
        }
        .action-button {
            display: inline-block;
            background: linear-gradient(135deg, #004080 0%, #0066cc 100%);
            color: white;
            padding: 15px 30px;
            text-decoration: none;
            border-radius: 5px;
            font-weight: bold;
            margin: 20px 0;
            text-align: center;
            transition: all 0.3s ease;
        }
        .action-button:hover {
            background: linear-gradient(135deg, #003366 0%, #0052a3 100%);
            transform: translateY(-2px);
        }
        .institutional-notice {
            background-color: #e8f4fd;
            border: 1px solid #b8daff;
            border-radius: 8px;
            padding: 25px;
            margin: 30px 0;
        }
        .institutional-notice h3 {
            color: #004085;
            margin-top: 0;
        }
        .institutional-notice ul {
            color: #004085;
            margin: 15px 0;
        }
        .footer {
            background-color: #2c3e50;
            color: white;
            padding: 30px;
            text-align: center;
        }
        .footer .contact-info {
            margin-top: 15px;
            font-size: 14px;
            color: #bdc3c7;
        }
        .footer .contact-info p {
            margin: 5px 0;
        }
        .logo-section {
            text-align: center;
            margin: 20px 0;
        }
        .logo-section img {
            max-width: 150px;
            height: auto;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>
                @if(str_contains($accion, 'rechazo'))
                    {{ str_contains($accion, 'ficha') ? 'FICHA DE ACTUALIZACIÓN RECHAZADA' : 'PROYECTO RECHAZADO' }}
                @elseif(str_contains($accion, 'aprobación de ficha'))
                    FICHA DE ACTUALIZACIÓN APROBADA
                @else
                    CAMBIO DE ESTADO
                @endif
            </h1>
            <p style="margin: 10px 0 0 0; font-size: 16px; opacity: 0.9;">
                Sistema de Gestión de Proyectos de Vinculación Universidad-Sociedad
            </p>
        </div>

        <div class="content">
            <p style="font-size: 18px; margin-bottom: 30px;">
                Estimado/a <strong>{{ $nombreCompleto }}</strong>,
            </p>

            <p style="text-align: justify; margin: 20px 0;">
                @if(str_contains($accion, 'ficha'))
                    Le informamos que su ficha de actualización de proyecto ha tenido una actualización importante en su estado de revisión.
                @else
                    Le informamos que su proyecto de vinculación ha tenido una actualización importante en su estado de revisión.
                @endif
                A continuación, encontrará los detalles específicos de esta notificación:
            </p>

            <div class="project-info">
                <h3>📋 Información del Proyecto</h3>
                <div class="detail-row">
                    <span class="detail-label">Nombre del proyecto:</span>
                    <span class="detail-value">{{ $proyecto->nombre_proyecto }}</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Fecha de registro:</span>
                    <span class="detail-value">{{ $proyecto->fecha_registro ? $proyecto->fecha_registro->format('d/m/Y H:i') : 'No especificada' }}</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Coordinador/a:</span>
                    <span class="detail-value">{{ $nombreCompleto }}</span>
                </div>
            </div>

            <div class="status-change {{ $accion === 'rechazo' ? 'rejected' : '' }}">
                <h3>
                    {{ $accion === 'rechazo' ? '❌ PROYECTO RECHAZADO' : '✅ ESTADO ACTUALIZADO' }}
                </h3>
                <p style="font-size: 16px; margin: 15px 0;">
                    {{ $accion === 'rechazo' ? 'Su proyecto ha sido rechazado y requiere subsanación.' : 'El estado de su proyecto ha sido actualizado exitosamente.' }}
                </p>
                <span class="status-badge {{ $accion === 'rechazo' ? 'rejected' : '' }}">
                    {{ $nuevoEstado }}
                </span>
            </div>

            @if($comentario && trim($comentario) !== '')
            <div class="comment-section">
                <h4>Comentarios del revisor</h4>
                <div class="comment-text">
                    {{ $comentario }}
                </div>
            </div>
            @endif

            <div style="text-align: center; margin: 30px 0;">
                <a href="{{ route('listarProyectosVinculacion') }}" class="action-button">
                    Ver Estado del Proyecto
                </a>
            </div>
            
            <div class="institutional-notice">
                <h3>📢 Próximos Pasos</h3>
                @if(str_contains($accion, 'rechazo'))
                    @if(str_contains($accion, 'ficha'))
                    <ul>
                        <li><strong>Revisión de observaciones:</strong> Analice cuidadosamente los comentarios del revisor sobre su ficha de actualización</li>
                        <li><strong>Corrección de la ficha:</strong> Realice las correcciones y mejoras solicitadas en la ficha</li>
                        <li><strong>Reenvío para revisión:</strong> Una vez corregida, reenvíe la ficha de actualización para nueva evaluación</li>
                        <li><strong>Seguimiento continuo:</strong> Manténgase atento a las notificaciones del sistema</li>
                    </ul>
                    @else
                    <ul>
                        <li><strong>Revisión de observaciones:</strong> Analice cuidadosamente los comentarios del revisor</li>
                        <li><strong>Subsanación de observaciones:</strong> Realice las correcciones y mejoras solicitadas</li>
                        <li><strong>Reenvío para revisión:</strong> Una vez corregido, reenvíe el proyecto para nueva evaluación</li>
                        <li><strong>Seguimiento continuo:</strong> Manténgase atento a las notificaciones del sistema</li>
                    </ul>
                    @endif
                @elseif(str_contains($accion, 'aprobación de ficha'))
                <ul>
                    <li><strong>Cambios aplicados:</strong> Los cambios solicitados en su ficha han sido aplicados automáticamente al proyecto</li>
                    <li><strong>Constancias generadas:</strong> Las constancias de actualización han sido generadas y estarán disponibles en su panel</li>
                    <li><strong>Continuar con el proyecto:</strong> Su proyecto puede continuar con las actividades programadas</li>
                    <li><strong>Consulta de cambios:</strong> Puede revisar los cambios aplicados desde su panel institucional</li>
                </ul>
                @else
                <ul>
                    <li><strong>Seguimiento del proceso:</strong> Su proyecto continúa en el flujo de revisión institucional</li>
                    <li><strong>Validación por etapas:</strong> Se realizarán las validaciones correspondientes a este estado</li>
                    <li><strong>Notificaciones automáticas:</strong> Recibirá actualizaciones sobre cualquier cambio de estado</li>
                    <li><strong>Consulta de avances:</strong> Puede consultar el progreso desde su panel institucional</li>
                </ul>
                @endif
            </div>

            <p style="text-align: justify; margin: 25px 0;">
                <strong>Importante:</strong> Esta notificación es generada automáticamente por el sistema institucional. 
                Para consultas específicas sobre el proceso de revisión, puede contactar a la Dirección de Vinculación 
                Universidad-Sociedad a través de los canales oficiales.
            </p>

            <div class="logo-section">
                <img src="{{ asset('images/logo_nuevo.png') }}" alt="Logo UNAH" />
            </div>
        </div>

        <div class="footer">
            <div>
                <p style="font-size: 16px; font-weight: bold; margin: 0;">
                    Universidad Nacional Autónoma de Honduras
                </p>
                <p style="margin: 5px 0;">Dirección de Vinculación Universidad-Sociedad</p>
                <p style="margin: 5px 0;">{{ $appName }} - Sistema de Gestión de Proyectos de Vinculación</p>
            </div>
            <p>&copy; {{ date('Y') }} UNAH - Todos los derechos reservados</p>
            <div class="contact-info">
                <p>Ciudad Universitaria José Trinidad Reyes | Tegucigalpa, Honduras</p>
                <p>Este es un mensaje automático del sistema institucional</p>
            </div>
        </div>
    </div>
</body>
</html>