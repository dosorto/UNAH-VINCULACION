<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class correoEstado extends Mailable
{
    use Queueable, SerializesModels;

    public $estadoNombre;
    //public $empleadoCorreo;
    public $empleadoNombre;
    public $nombreProyecto;
    public $logoUrl;
    public $appName;
    public $actionUrl;
    /**
     * Create a new message instance.
     *
     * @param string $empleadoNombre Nombre del empleado que recibe la notificación.
     * @param string $actionUrl     URL de la acción relacionada con el cambio de estado.
     * @param string $logoUrl       URL del logo de la aplicación.
     * @param string $appName       Nombre de la aplicación.
     * 
     */
    
     public function __construct($estadoNombre, /*$cambiadoPorCorreo,*/ $empleadoNombre, $nombreProyecto)
     {
        $this->nombreProyecto = $nombreProyecto;
        $this->empleadoNombre = $empleadoNombre;
        // $this->cambiadoPorCorreo = $cambiadoPorCorreo;
        $this->estadoNombre = $estadoNombre;
     }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            from: new Address('notificacionespoa@unah.edu.hn', 'Notificaciones NEXO-UNAH'),
            subject: 'Notificación de cambio de estado del proyecto',
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.correoEstado',
            with: [
                'estadoNombre' => $this->estadoNombre,
                'cambiadoPorNombre' => $this->empleadoNombre,
                'mensaje' => 'Su proyecto'. $this->nombreProyecto . 'ha sido actualizado a: ' . $this->estadoNombre,
                'actionUrl' => route('listarProyectosVinculacion'),
                'logoUrl' => asset('images/logo_nuevo.png'),
                'appName' => 'NEXO-UNAH',
                //'cambiadoPorCorreo' => $this->cambiadoPorCorreo,
            ],
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        return [];
    }
}
