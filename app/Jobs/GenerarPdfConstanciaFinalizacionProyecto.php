<?php

namespace App\Jobs;

use App\Models\Constancias\ConstanciaFinalizacionProyecto;
use App\Services\Constancias\ConstanciaFinalizacionPdfGenerator;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Throwable;

class GenerarPdfConstanciaFinalizacionProyecto implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public function __construct(public readonly int $constanciaId)
    {
        $this->afterCommit();
    }

    public function handle(ConstanciaFinalizacionPdfGenerator $generator): void
    {
        $constancia = ConstanciaFinalizacionProyecto::query()->find($this->constanciaId);

        if (! $constancia || ! in_array($constancia->estado, [ConstanciaFinalizacionProyecto::ESTADO_PENDIENTE, ConstanciaFinalizacionProyecto::ESTADO_ERROR], true)) {
            return;
        }

        try {
            $contenido = $generator->content($constancia);
            $codigo = preg_replace('/[^A-Za-z0-9_-]+/', '-', (string) data_get($constancia->snapshot, 'proyecto.codigo', 'proyecto'));
            $ruta = sprintf('constancias/finalizacion/%d/%s/constancia-finalizacion-%s.pdf', $constancia->anio, trim($codigo, '-'), trim($codigo, '-'));

            if (! Storage::disk('local')->put($ruta, $contenido)) {
                throw new \RuntimeException('No se pudo almacenar el PDF de la constancia.');
            }

            $constancia->update([
                'ruta_archivo' => $ruta,
                'hash_archivo' => hash('sha256', $contenido),
                'estado' => ConstanciaFinalizacionProyecto::ESTADO_EMITIDA,
                'token_cifrado' => null,
            ]);
        } catch (Throwable $exception) {
            $constancia->update(['estado' => ConstanciaFinalizacionProyecto::ESTADO_ERROR]);
            Log::error('No se pudo generar la constancia de finalización.', ['constancia_id' => $constancia->id, 'exception' => $exception]);
            throw $exception;
        }
    }
}
