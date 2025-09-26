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

class ProyectoEstadoCambiado extends Mailable implements ShouldQueue
{
    use SerializesModels;

    public $proyecto;
    public $usuario;
    public $nuevoEstado;
    public $comentario;
    public $accion;

    /**
     * Create a new message instance.
     */
    public function __construct(Proyecto $proyecto, User $usuario, string $nuevoEstado, string $comentario = '', string $accion = 'cambio de estado')
    {
        $this->proyecto = $proyecto;
        $this->usuario = $usuario;
        $this->nuevoEstado = $nuevoEstado;
        $this->comentario = $comentario;
        $this->accion = $accion;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            from: new Address(env('MAIL_FROM_ADDRESS', 'nexo@unah.edu.hn'), env('APP_NAME', 'NEXO')),
            subject: 'Notificación de ' . $this->accion . ' - ' . $this->proyecto->nombre_proyecto,
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.proyecto-estado-cambiado',
            with: [
                'proyecto' => $this->proyecto,
                'nombreCompleto' => $this->usuario->empleado->nombre_completo,
                'emailCoordinador' => $this->usuario->email,
                'nuevoEstado' => $this->nuevoEstado,
                'comentario' => $this->comentario,
                'accion' => $this->accion,
                'appName' => config('app.name', 'NEXO'),
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
