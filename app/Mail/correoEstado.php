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
    
     public function __construct($estadoNombre, /*$cambiadoPorCorreo,*/ $empleadoNombre)
     {
         $this->empleadoNombre = $empleadoNombre;
        // $this->cambiadoPorCorreo = $cambiadoPorCorreo;
         $this->estadoNombre = $estadoNombre;
         $this->actionUrl = config('app.url');
         $this->logoUrl = asset('images/LOGO_NX.png');
         $this->appName = config('app.name');
     }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            from: new Address('notificacionespoa@unah.edu.hn', 'Notificaciones POA'),
            subject: 'Notificación de cambio de estado del proyecto - Cambiado por: ' . $this->empleadoNombre,
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
                'actionUrl' => $this->actionUrl,
                'logoUrl' => $this->logoUrl,
                'appName' => $this->appName,
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
