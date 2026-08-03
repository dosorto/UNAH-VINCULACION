<?php

namespace App\Jobs;

use App\Models\ENF\EnfConstanciaRegistro;
use App\Services\ENF\Constancias\EnfConstanciaRegistroPdfGenerator;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Throwable;

class GenerarPdfConstanciaRegistroEnf implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public function __construct(public readonly int $constanciaId)
    {
        $this->afterCommit();
    }

    public function handle(EnfConstanciaRegistroPdfGenerator $generator): void
    {
        $constancia = EnfConstanciaRegistro::query()->find($this->constanciaId);

        if (! $constancia || ! in_array($constancia->estado, [EnfConstanciaRegistro::ESTADO_PENDIENTE, EnfConstanciaRegistro::ESTADO_ERROR], true)) {
            return;
        }

        try {
            $contenido = $generator->content($constancia);
            $codigo = preg_replace('/[^A-Za-z0-9_-]+/', '-', (string) data_get($constancia->snapshot, 'accion.nombre', 'enf'));
            $ruta = sprintf('constancias/enf/registro/%d/%s/constancia-registro-enf-%s.pdf', $constancia->anio, trim($codigo, '-'), trim($codigo, '-'));

            if (! Storage::disk('local')->put($ruta, $contenido)) {
                throw new \RuntimeException('No se pudo almacenar el PDF de la constancia ENF.');
            }

            $constancia->update([
                'ruta_archivo' => $ruta,
                'hash_archivo' => hash('sha256', $contenido),
                'estado' => EnfConstanciaRegistro::ESTADO_EMITIDA,
            ]);
        } catch (Throwable $exception) {
            $constancia->update(['estado' => EnfConstanciaRegistro::ESTADO_ERROR]);
            Log::error('No se pudo generar la constancia de registro ENF.', ['constancia_id' => $constancia->id, 'exception' => $exception]);
            throw $exception;
        }
    }
}
