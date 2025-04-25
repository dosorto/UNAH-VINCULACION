<?php

namespace App\Mail;


use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Mail\Mailables\Address;

class Email extends Mailable
{
    use Queueable, SerializesModels;

    public $estadoNombre;
    public $empleadoNombre;
    public $nombreProyecto;
    public $logoUrl;
    public $appName;
    public $actionUrl;
    public $subject;
    public $mensaje;

    // Método constructor vacío para permitir la inicialización por encadenamiento.
    public function __construct()
    {
        // Puedes inicializar valores predeterminados si lo deseas
        $this->subject = 'Notificación de cambio de estado del proyecto';
    }


    /**
     * Establecer el nombre del estado.
     */
    public function setEstadoNombre($estadoNombre): self
    {
        $this->estadoNombre = $estadoNombre;
        return $this; // Permite encadenar
    }

    /**
     * Establecer el nombre del empleado.
     */
    public function setEmpleadoNombre($empleadoNombre): self
    {
        $this->empleadoNombre = $empleadoNombre;
        return $this; // Permite encadenar
    }

    /**
     * Establecer el nombre del proyecto.
     */
    public function setNombreProyecto($nombreProyecto): self
    {
        $this->nombreProyecto = $nombreProyecto;
        return $this; // Permite encadenar
    }

    /**
     * Establecer la URL de acción.
     */
    public function setActionUrl($actionUrl): self
    {
        $this->actionUrl = $actionUrl;
        return $this; // Permite encadenar
    }

    /**
     * Establecer la URL del logo.
     */
    public function setLogoUrl($logoUrl): self
    {
        $this->logoUrl = $logoUrl;
        return $this; // Permite encadenar
    }

    /**
     * Establecer el nombre de la aplicación.
     */
    public function setAppName($appName): self
    {
        $this->appName = $appName;
        return $this; // Permite encadenar
    }

    /**
     * Establecer el mensaje.
     */
    public function setMensaje($mensaje): self
    {
        $this->mensaje = $mensaje;
        return $this; // Permite encadenar
    }

    /**
     * Establecer el asunto.
     */
    public function setSubject($subject): self
    {
        $this->subject = $subject;
        return $this; // Permite encadenar
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            from: new Address(env('MAIL_FROM_ADDRESS')), // Usamos las variables de entorno
            subject: $this->subject,
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
                'empleadoNombre' => $this->empleadoNombre,
                'mensaje' => $this->mensaje,
                'actionUrl' => $this->actionUrl,
                'logoUrl' => $this->logoUrl,
                'appName' => $this->appName,
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


