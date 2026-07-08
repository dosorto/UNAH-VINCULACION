<?php

namespace App\Mail;

use App\Models\Proyecto\FlujoAprobacionEtapa;
use App\Models\Proyecto\Proyecto;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class EtapaFlujoPendiente extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public string $nombreRevisor;
    public string $nombreProyecto;
    public string $nombreEtapa;
    public string $actionUrl;

    public function __construct(
        public Proyecto $proyecto,
        public User $revisor,
        public FlujoAprobacionEtapa $etapa,
    ) {
        $this->nombreRevisor = $revisor->empleado?->nombre_completo
            ?? ($revisor->nombre . ' ' . ($revisor->apellido ?? ''));
        $this->nombreProyecto = $proyecto->nombre_proyecto ?? 'Proyecto sin nombre';
        $this->nombreEtapa = $etapa->nombre;
        $this->actionUrl = url('/');
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            from: new Address(env('MAIL_FROM_ADDRESS', 'nexo@unah.edu.hn'), env('APP_NAME', 'NEXO')),
            subject: 'Revisión pendiente — ' . $this->nombreProyecto,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.etapa-flujo-pendiente',
            with: [
                'nombreRevisor' => $this->nombreRevisor,
                'nombreProyecto' => $this->nombreProyecto,
                'nombreEtapa' => $this->nombreEtapa,
                'actionUrl' => $this->actionUrl,
                'appName' => env('APP_NAME', 'NEXO'),
            ],
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
