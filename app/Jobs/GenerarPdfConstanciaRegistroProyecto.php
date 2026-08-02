<?php

namespace App\Jobs;

use App\Models\Constancias\ConstanciaRegistroProyecto;
use App\Services\Constancias\ConstanciaRegistroPdfGenerator;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Throwable;

class GenerarPdfConstanciaRegistroProyecto implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public function __construct(public readonly int $constanciaId)
    {
        $this->afterCommit();
    }

    public function handle(ConstanciaRegistroPdfGenerator $generator): void
    {
        $constancia = ConstanciaRegistroProyecto::query()->find($this->constanciaId);

        if (! $constancia || ! in_array($constancia->estado, [ConstanciaRegistroProyecto::ESTADO_PENDIENTE, ConstanciaRegistroProyecto::ESTADO_ERROR], true)) {
            return;
        }

        try {
            $contenido = $generator->content($constancia);
            $codigo = preg_replace('/[^A-Za-z0-9_-]+/', '-', (string) data_get($constancia->snapshot, 'proyecto.codigo', 'proyecto'));
            $ruta = sprintf('constancias/registro/%d/%s/constancia-registro-%s.pdf', $constancia->anio, trim($codigo, '-'), trim($codigo, '-'));

            if (! Storage::disk('local')->put($ruta, $contenido)) {
                throw new \RuntimeException('No se pudo almacenar el PDF de la constancia de registro.');
            }

            $constancia->update([
                'ruta_archivo' => $ruta,
                'hash_archivo' => hash('sha256', $contenido),
                'estado' => ConstanciaRegistroProyecto::ESTADO_EMITIDA,
            ]);
        } catch (Throwable $exception) {
            $constancia->update(['estado' => ConstanciaRegistroProyecto::ESTADO_ERROR]);
            Log::error('No se pudo generar la constancia de registro.', ['constancia_id' => $constancia->id, 'exception' => $exception]);
            throw $exception;
        }
    }
}
