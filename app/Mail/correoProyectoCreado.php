<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class correoProyectoCreado extends Mailable
{
    use Queueable, SerializesModels;
    public $nombreProyecto;
    public $empleadoNombre;
    public $logoUrl;
    public $appName;
    public $actionUrl;
    /**
     * Create a new message instance.
     */
    public function __construct($nombreProyecto)
    {
        $this->nombreProyecto = $nombreProyecto;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            from: new Address('notificacionespoa@unah.edu.hn', 'Notificaciones NEXO-UNAH'),
            subject: 'Nuevo Proyecto Creado: ' . $this->nombreProyecto,
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            with: [
                'empleadoNombre' => $this->empleadoNombre,
                'nombreProyecto' => $this->nombreProyecto,
                'mensaje' => 'Se ha creado un nuevo proyecto en el sistema, identificado como '. $this->nombreProyecto,
                'logoUrl' => asset('images/logo_nuevo.png'),
                'appName' => 'NEXO-UNAH',
                'actionUrl' =>  route('listarProyectosVinculacion'),
            ],
            view: 'emails.correoProyectoCreado',
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
