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
        ];

        $type = $estado_proyecto->estadoable_type;

        if (isset($handlers[$type])) {
            call_user_func($handlers[$type], $estado_proyecto);
        }
    }

    public function enviarCorreoProyecto(EstadoProyecto $estado_proyecto): void
    {
        $coordinador = $estado_proyecto->proyecto->coordinador;

        // Usamos el EmailBuilder y el mensaje dinámico
        $mensaje =  'MESAJE';//$this->mensajeDinamico->obtenerMensaje($estado_proyecto->tipoestado);
      
        $correo = (new EmailBuilder())
            ->setEstadoNombre($estado_proyecto->tipoestado->nombre)
            ->setEmpleadoNombre($coordinador->nombre_completo)
            ->setNombreProyecto($estado_proyecto->proyecto->nombre_proyecto)
            ->setActionUrl(route('listarProyectosVinculacion'))
            ->setLogoUrl(asset('images/logo_nuevo.png'))
            ->setAppName('NEXO-UNAH')
            ->setMensaje($mensaje)
            ->setSubject('Notificación de actualización de estado del proyecto')
            ->build();

        Mail::to($coordinador->user->email)->queue($correo);
    
    }




    public function enviarCorreoDocumento(EstadoProyecto $estado_proyecto): void
    {
        // Aquí podrías implementar el envío relacionado al DocumentoProyecto
    }
}
