<?php

namespace App\Jobs;

use App\Models\ENF\EnfConstanciaFinalizacion;
use App\Services\ENF\Constancias\EnfConstanciaFinalizacionPdfGenerator;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Throwable;

class GenerarPdfConstanciaFinalizacionEnf implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public function __construct(public readonly int $constanciaId)
    {
        $this->afterCommit();
    }

    public function handle(EnfConstanciaFinalizacionPdfGenerator $generator): void
    {
        $constancia = EnfConstanciaFinalizacion::query()->find($this->constanciaId);

        if (! $constancia || ! in_array($constancia->estado, [EnfConstanciaFinalizacion::ESTADO_PENDIENTE, EnfConstanciaFinalizacion::ESTADO_ERROR], true)) {
            return;
        }

        try {
            $contenido = $generator->content($constancia);
            $codigo = preg_replace('/[^A-Za-z0-9_-]+/', '-', (string) data_get($constancia->snapshot, 'accion.nombre', 'enf'));
            $slug = trim($codigo, '-').'-'.$constancia->id;
            $ruta = sprintf('constancias/enf/finalizacion/%d/%s/constancia-finalizacion-enf-%s.pdf', $constancia->anio, $slug, $slug);

            if (! Storage::disk('local')->put($ruta, $contenido)) {
                throw new \RuntimeException('No se pudo almacenar el PDF de la constancia ENF.');
            }

            $constancia->update([
                'ruta_archivo' => $ruta,
                'hash_archivo' => hash('sha256', $contenido),
                'estado' => EnfConstanciaFinalizacion::ESTADO_EMITIDA,
            ]);
        } catch (Throwable $exception) {
            $constancia->update(['estado' => EnfConstanciaFinalizacion::ESTADO_ERROR]);
            Log::error('No se pudo generar la constancia de finalizacion ENF.', ['constancia_id' => $constancia->id, 'exception' => $exception]);
            throw $exception;
        }
    }
}
