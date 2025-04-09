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
    //public $cambiadoPorCorreo;
    public $cambiadoPorNombre;
    /**
     * Create a new message instance.
     *
     * @param string $estadoNombre Nombre del nuevo estado del proyecto.
     * @param string $cambiadoPor   Nombre del usuario que realizó el cambio.
     */
    
     public function __construct($estadoNombre, /*$cambiadoPorCorreo,*/ $cambiadoPorNombre)
     {
         $this->cambiadoPorNombre = $cambiadoPorNombre;
        // $this->cambiadoPorCorreo = $cambiadoPorCorreo;
         $this->estadoNombre = $estadoNombre;
     }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            from: new Address('notificacionespoa@unah.edu.hn', 'Notificaciones POA'),
            subject: 'Notificación de cambio de estado del proyecto - Cambiado por: ' . $this->cambiadoPorNombre,
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
                'cambiadoPorNombre' => $this->cambiadoPorNombre,
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
