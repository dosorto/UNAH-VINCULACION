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
    use Queueable, SerializesModels;

    /** Acciones con las que termina un flujo por etapas: el correo no dice que «continúa». */
    public const ACCIONES_DE_CIERRE = [
        'aprobación de proyecto',
        'aprobación del informe intermedio',
        'cierre del proyecto',
    ];

    public $proyecto;
    public $usuario;
    public $nuevoEstado;
    public $comentario;
    public $accion;
    public string $actionUrl;
    public ?string $tipoDocumento;
    /** subsanacion | cierre | cambio: decide encabezado, color y próximos pasos. */
    public string $tipoAviso;

    /**
     * Create a new message instance.
     */
    public function __construct(
        Proyecto $proyecto,
        User $usuario,
        string $nuevoEstado,
        string $comentario = '',
        string $accion = 'cambio de estado',
        ?string $actionUrl = null,
        ?string $tipoDocumento = null,
    )
    {
        $this->proyecto = $proyecto;
        $this->usuario = $usuario;
        $this->nuevoEstado = $nuevoEstado;
        $this->comentario = $comentario;
        $this->accion = $accion;
        $this->actionUrl = $actionUrl ?: ($usuario->can('docente.proyectos')
            ? route('historialproyecto', $proyecto)
            : url('/'));
        $this->tipoDocumento = $tipoDocumento;
        $this->tipoAviso = match (true) {
            str_contains($accion, 'rechazo'), str_contains($accion, 'subsanación') => 'subsanacion',
            in_array($accion, self::ACCIONES_DE_CIERRE, true) => 'cierre',
            default => 'cambio',
        };
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            from: new Address(env('MAIL_FROM_ADDRESS', 'nexo@unah.edu.hn'), env('APP_NAME', 'NEXO')),
            subject: 'Notificación de ' . $this->accion
                . ($this->tipoDocumento ? ' - ' . $this->tipoDocumento : '')
                . ' - ' . $this->proyecto->nombre_proyecto,
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
                'actionUrl' => $this->actionUrl,
                'tipoDocumento' => $this->tipoDocumento,
                'tipoAviso' => $this->tipoAviso,
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
