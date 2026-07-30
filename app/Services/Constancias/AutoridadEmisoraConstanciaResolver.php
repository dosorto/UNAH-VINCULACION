<?php

namespace App\Services\Constancias;

use App\Models\Proyecto\DocumentoProyecto;
use App\Models\Proyecto\FirmaProyecto;
use RuntimeException;

class AutoridadEmisoraConstanciaResolver
{
    /** @return array{nombre:string,cargo:string,firma_ruta:?string,sello_ruta:?string} */
    public function resolver(DocumentoProyecto $documento): array
    {
        $ciclo = (int) $documento->firma_documento()->max('revision_ciclo');
        $firma = $documento->firma_documento()
            ->with(['empleado', 'firma', 'sello', 'cargo_firma.tipoCargoFirma'])
            ->where('revision_ciclo', $ciclo)
            ->where('estado_revision', 'Aprobado')
            ->whereHas('cargo_firma.tipoCargoFirma', fn ($query) => $query->where('nombre', 'Director Vinculacion'))
            ->orderByDesc('fecha_firma')
            ->first();

        if (! $firma instanceof FirmaProyecto || ! $firma->empleado) {
            throw new RuntimeException('No existe una autoridad DVUS aprobadora configurada para emitir la constancia de finalización.');
        }

        return [
            'nombre' => $firma->empleado->nombre_completo ?: 'No registrado',
            'cargo' => $firma->etapa_nombre ?: ($firma->cargo_firma?->tipoCargoFirma?->nombre ?: 'Director de Vinculación Universidad-Sociedad'),
            'firma_ruta' => $firma->firma?->ruta_storage,
            'sello_ruta' => $firma->sello?->ruta_storage,
        ];
    }
}
