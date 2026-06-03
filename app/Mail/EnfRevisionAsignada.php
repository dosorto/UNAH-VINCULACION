<?php

namespace App\Mail;

use App\Models\ENF\EnfAccion;
use App\Models\ENF\EnfRevision;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class EnfRevisionAsignada extends Mailable implements ShouldQueue
{
    use Queueable;
    use SerializesModels;

    public function __construct(
        public EnfAccion $accion,
        public EnfRevision $revision,
    ) {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Revisión ENF pendiente - '.$this->accion->nombre_accion,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.enf-revision-asignada',
            with: [
                'accion' => $this->accion,
                'revision' => $this->revision,
                'url' => route('enf.acciones.show', $this->accion),
            ],
        );
    }
}
