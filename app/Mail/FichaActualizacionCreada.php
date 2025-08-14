<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Mail\Mailables\Address;
use App\Models\Proyecto\FichaActualizacion;
use App\Models\User;

class FichaActualizacionCreada extends Mailable implements ShouldQueue
{
    use SerializesModels;

    public $fichaActualizacion;
    public $usuario;

    /**
     * Create a new message instance.
     */
    public function __construct(FichaActualizacion $fichaActualizacion, User $usuario)
    {
        $this->fichaActualizacion = $fichaActualizacion;
        $this->usuario = $usuario;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            from: new Address(env('MAIL_FROM_ADDRESS', 'nexo@unah.edu.hn'), env('APP_NAME', 'NEXO')),
            subject: 'Ficha de Actualización Enviada a Firmar - ' . $this->fichaActualizacion->proyecto->nombre_proyecto,
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.ficha-actualizacion-creada',
            with: [
                'fichaActualizacion' => $this->fichaActualizacion,
                'proyecto' => $this->fichaActualizacion->proyecto,
                'nombreCompleto' => $this->usuario->empleado->nombre_completo,
                'emailCoordinador' => $this->usuario->email,
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
