<?php

namespace App\Mail;

use App\Models\DAFT\ProgramaRevision;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ProgramaRevisionAsignada extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(public ProgramaRevision $revision) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Programa DAFT pendiente de revisión');
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.programa-revision-asignada',
            with: [
                'programa' => $this->revision->programa,
                'revision' => $this->revision,
                'url' => route('daft.bandeja-revision.show', $this->revision),
            ],
        );
    }
}
