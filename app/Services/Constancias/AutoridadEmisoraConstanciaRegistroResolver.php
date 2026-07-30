<?php

namespace App\Services\Constancias;

use App\Models\Proyecto\FirmaProyecto;
use App\Models\Proyecto\Proyecto;
use RuntimeException;

class AutoridadEmisoraConstanciaRegistroResolver
{
    /** @return array{nombre:string,cargo:string,firma_ruta:?string,sello_ruta:?string} */
    public function resolver(Proyecto $proyecto): array
    {
        $ciclo = (int) $proyecto->firma_proyecto()
            ->whereNotNull('revision_ciclo')
            ->max('revision_ciclo');

        $firma = $proyecto->firma_proyecto()
            ->with(['empleado', 'firma', 'sello', 'cargo_firma.tipoCargoFirma'])
            ->where('firmable_type', Proyecto::class)
            ->where('revision_ciclo', $ciclo)
            ->where('estado_revision', 'Aprobado')
            ->whereHas('cargo_firma.tipoCargoFirma', fn ($query) => $query->where('nombre', 'Director Vinculacion'))
            ->orderByDesc('fecha_firma')
            ->first();

        if (! $firma instanceof FirmaProyecto || ! $firma->empleado) {
            throw new RuntimeException('No existe una autoridad DVUS aprobadora configurada para emitir la constancia de registro.');
        }

        return [
            'nombre' => $firma->empleado->nombre_completo ?: 'No registrado',
            'cargo' => $firma->etapa_nombre ?: ($firma->cargo_firma?->tipoCargoFirma?->nombre ?: 'Director de Vinculación Universidad-Sociedad'),
            'firma_ruta' => $firma->firma?->ruta_storage,
            'sello_ruta' => $firma->sello?->ruta_storage,
        ];
    }
}
