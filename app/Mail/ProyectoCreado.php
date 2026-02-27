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
use Illuminate\Support\Facades\Route;

class ProyectoCreado extends Mailable implements ShouldQueue
{
    use SerializesModels;

    public $proyecto;
    public $usuario;
    public $nombreCompleto;
    public $fechaCreacion;

    /**
     * Create a new message instance.
     */
    public function __construct(Proyecto $proyecto, User $usuario)
    {
        $this->proyecto = $proyecto;
        $this->usuario = $usuario;
        // prepare derived values so they are serialized with the job
        $this->nombreCompleto = optional($usuario->empleado)->nombre_completo
            ?? ($usuario->nombre . ' ' . $usuario->apellido);
        $this->fechaCreacion = optional($proyecto->fecha_registro)->format('d/m/Y')
            ?? now()->format('d/m/Y');
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
                'nombreCompleto' => $this->nombreCompleto,
                'subject' => 'Proyecto Creado Exitosamente',
                'mensaje' => 'Su proyecto "' . $this->proyecto->nombre_proyecto . '" ha sido registrado exitosamente en el sistema NEXO.',
                'fechaCreacion' => $this->fechaCreacion,
                'logoUrl' => asset('images/Image/logo_nexo.png'),
                'appName' => env('APP_NAME', 'NEXO'),
                'actionUrl' => 'https://nexo.unah.edu.hn/',
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
