<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Mail\Mailables\Address;
use App\Models\Proyecto\Proyecto;

class FirmasActualizadas extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public $proyecto;
    public $cambios;
    public $destinatario;

    /**
     * Create a new message instance.
     */
    public function __construct(Proyecto $proyecto, array $cambios, $destinatario)
    {
        $this->proyecto = $proyecto;
        $this->cambios = $cambios;
        $this->destinatario = $destinatario;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            from: new Address(env('MAIL_FROM_ADDRESS', 'nexo@unah.edu.hn'), env('APP_NAME', 'NEXO')),
            subject: 'Actualización de Firmas - ' . $this->proyecto->nombre_proyecto,
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.firmas-actualizadas',
            with: [
                'proyecto' => $this->proyecto,
                'cambios' => $this->cambios,
                'destinatario' => $this->destinatario,
                'logoUrl' => asset('images/Image/logo_nexo.png'),
                'appName' => env('APP_NAME', 'NEXO'),
            ]
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