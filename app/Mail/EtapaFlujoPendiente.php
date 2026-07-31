<?php

namespace App\Mail;

use App\Models\PpsServicioSocial;
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
    public string $tipoRegistro;
    public ?string $nombreEstudiante = null;
    public ?string $tipoPpsSs = null;

    public function __construct(
        public Proyecto|PpsServicioSocial $firmable,
        public User $revisor,
        public FlujoAprobacionEtapa $etapa,
        ?string $tipoRegistro = null,
    ) {
        $this->nombreRevisor = $revisor->empleado?->nombre_completo
            ?? ($revisor->nombre . ' ' . ($revisor->apellido ?? ''));

        if ($firmable instanceof PpsServicioSocial) {
            $this->nombreProyecto = $firmable->codigo_registro ?: '#'.$firmable->id;
            $this->tipoRegistro = $tipoRegistro ?: 'pps-servicio-social';
            $this->actionUrl = route('pps-servicio-social.show', ['id' => $firmable->id]);
            $this->nombreEstudiante = $firmable->nombre_estudiante;
            $this->tipoPpsSs = $firmable->tipo_pps_ss;
        } else {
            $this->nombreProyecto = $firmable->nombre_proyecto ?? 'Proyecto sin nombre';
            $this->tipoRegistro = $tipoRegistro ?: 'proyecto';
            $this->actionUrl = $revisor->can('proyectos.informes')
                ? route('listarInformesSolicitado')
                : ($revisor->can('docente.proyectos')
                    ? route('SolicitudProyectosDocente')
                    : url('/'));
        }

        $this->nombreEtapa = $etapa->nombre;
    }

    public function envelope(): Envelope
    {
        $subject = $this->tipoRegistro === 'pps-servicio-social'
            ? 'Registro PPS/Servicio Social pendiente de revision - '.$this->nombreProyecto
            : 'Revisión pendiente de '.$this->tipoRegistro.' — '.$this->nombreProyecto;

        return new Envelope(
            from: new Address(env('MAIL_FROM_ADDRESS', 'nexo@unah.edu.hn'), env('APP_NAME', 'NEXO')),
            subject: $subject,
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
                'tipoRegistro' => $this->tipoRegistro,
                'actionUrl' => $this->actionUrl,
                'appName' => env('APP_NAME', 'NEXO'),
                'nombreEstudiante' => $this->nombreEstudiante,
                'tipoPpsSs' => $this->tipoPpsSs,
            ],
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
