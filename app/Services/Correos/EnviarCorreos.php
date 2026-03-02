<?php

namespace App\Services\Correos;

use App\Models\Estado\EstadoProyecto;
use Illuminate\Support\Facades\Mail;
use App\Models\Proyecto\Proyecto;
use App\Models\Proyecto\DocumentoProyecto;

class EnviarCorreos
{
    protected $mensajeDinamico;

    public function __construct()
    {
        $this->mensajeDinamico = new Mensajes();
    }

    public function enviar(EstadoProyecto $estado_proyecto): void
    {
        $handlers = [
            Proyecto::class => [$this, 'enviarCorreoProyecto'],
            DocumentoProyecto::class => [$this, 'enviarCorreoDocumento'],
            \App\Models\Proyecto\FichaActualizacion::class => [$this, 'enviarCorreoFichaActualizacion'],
        ];

        $type = $estado_proyecto->estadoable_type;

        if (isset($handlers[$type])) {
            call_user_func($handlers[$type], $estado_proyecto);
        }
    }

    public function enviarCorreoProyecto(EstadoProyecto $estado_proyecto): void
    {
        // El correo de cambio de estado del proyecto se gestiona directamente
        // desde cada flujo (CreateProyectoVinculacion, ListProyectosSolicitado, etc.)
        // usando mailables específicos (ProyectoCreado, FirmasActualizadas, etc.)
        // No se envía Email::class aquí porque su vista está vacía.
    }




    public function enviarCorreoDocumento(EstadoProyecto $estado_proyecto): void
    {
        // Aquí podrías implementar el envío relacionado al DocumentoProyecto
    }

    public function enviarCorreoFichaActualizacion(EstadoProyecto $estado_proyecto): void
    {
        $fichaActualizacion = $estado_proyecto->estadoable;
        $coordinador = $fichaActualizacion->proyecto->coordinador;

        // Usamos el EmailBuilder y el mensaje dinámico para fichas de actualización
        $mensaje = 'Su ficha de actualización de proyecto ha cambiado de estado.';
      
        $correo = (new EmailBuilder())
            ->setEstadoNombre($estado_proyecto->tipoestado->nombre)
            ->setEmpleadoNombre($coordinador->nombre_completo)
            ->setNombreProyecto($fichaActualizacion->proyecto->nombre_proyecto . ' (Actualización)')
            ->setActionUrl(route('listarProyectosVinculacion'))
            ->setLogoUrl(asset('images/logo_nuevo.png'))
            ->setAppName('NEXO-UNAH')
            ->setMensaje($mensaje)
            ->setSubject('Notificación de actualización del estado de ficha de actualización')
            ->build();

        Mail::to($coordinador->user->email)->queue($correo);
    }
}
