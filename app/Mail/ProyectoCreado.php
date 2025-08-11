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
use App\Models\User;

class ProyectoCreado extends Mailable implements ShouldQueue
{
    use SerializesModels;

    public $proyecto;
    public $usuario;

    /**
     * Create a new message instance.
     */
    public function __construct(Proyecto $proyecto, User $usuario)
    {
        $this->proyecto = $proyecto;
        $this->usuario = $usuario;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            from: new Address(env('MAIL_FROM_ADDRESS', 'nexo@unah.edu.hn'), env('APP_NAME', 'NEXO')),
            subject: 'Proyecto Creado Exitosamente - ' . $this->proyecto->nombre_proyecto,
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.proyecto-creado',
            with: [
                'proyecto' => $this->proyecto,
                'usuario' => $this->usuario,
                'nombreCompleto' => $this->usuario->empleado ? 
                    $this->usuario->empleado->primer_nombre . ' ' . $this->usuario->empleado->primer_apellido : 
                    $this->usuario->name,
                'fechaCreacion' => $this->proyecto->created_at->format('d/m/Y H:i'),
                'logoUrl' => asset('images/Image/logo_nexo.png'),
                'appName' => env('APP_NAME', 'NEXO'),
                'actionUrl' => route('inicio'), // O la ruta específica del proyecto
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
