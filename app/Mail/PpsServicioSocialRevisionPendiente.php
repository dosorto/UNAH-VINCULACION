<?php

namespace App\Mail;

use App\Models\PpsServicioSocial;
use App\Models\Proyecto\FlujoAprobacionEtapa;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class PpsServicioSocialRevisionPendiente extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public string $detalleUrl;

    public function __construct(
        public PpsServicioSocial $registro,
        public FlujoAprobacionEtapa $etapa,
        public User $destinatario,
    ) {
        $this->detalleUrl = route('pps-servicio-social.show', ['id' => $registro->id]);
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            from: new Address(env('MAIL_FROM_ADDRESS', 'nexo@unah.edu.hn'), env('APP_NAME', 'NEXO')),
            subject: 'Registro PPS/Servicio Social pendiente de revision - '.($this->registro->codigo_registro ?: '#'.$this->registro->id),
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.pps-servicio-social-revision-pendiente',
            with: [
                'registro' => $this->registro,
                'etapa' => $this->etapa,
                'destinatario' => $this->destinatario,
                'detalleUrl' => $this->detalleUrl,
                'appName' => config('app.name', 'NEXO'),
            ],
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
