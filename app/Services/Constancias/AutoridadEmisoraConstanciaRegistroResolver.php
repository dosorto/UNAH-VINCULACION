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

        $firma = $this->firmaDirectorVinculacion($proyecto, $ciclo)
            ?? $this->firmaUltimaEtapaDeInscripcion($proyecto, $ciclo);

        if (! $firma instanceof FirmaProyecto || ! $firma->empleado) {
            throw new RuntimeException('No existe una autoridad DVUS aprobadora configurada para emitir la constancia de registro.');
        }

        return [
            'nombre' => $firma->empleado->nombre_completo ?: 'No registrado',
            'cargo' => $this->cargoLabel($firma),
            'firma_ruta' => $firma->firma?->ruta_storage,
            'sello_ruta' => $firma->sello?->ruta_storage,
        ];
    }

    /** Camino principal: firma aprobada de "Director Vinculacion" en el ciclo vigente. */
    private function firmaDirectorVinculacion(Proyecto $proyecto, int $ciclo): ?FirmaProyecto
    {
        return $proyecto->firma_proyecto()
            ->with(['empleado', 'firma', 'sello', 'cargo_firma.tipoCargoFirma'])
            ->where('firmable_type', Proyecto::class)
            ->where('revision_ciclo', $ciclo)
            ->where('estado_revision', 'Aprobado')
            ->whereHas('cargo_firma.tipoCargoFirma', fn ($query) => $query->where('nombre', 'Director Vinculacion'))
            ->orderByDesc('fecha_firma')
            ->first();
    }

    /**
     * Fallback: algunos flujos por tipo de proyecto (p. ej. Desarrollo local y regional,
     * PPS/Servicio Social, Voluntariado) no incluyen una etapa "Director Vinculacion" y
     * terminan su inscripción en "Revisor Vinculacion". En ese caso, la autoridad emisora
     * es quien aprobó la última etapa del flujo marcada `aplica_inscripcion`.
     */
    private function firmaUltimaEtapaDeInscripcion(Proyecto $proyecto, int $ciclo): ?FirmaProyecto
    {
        return $proyecto->firma_proyecto()
            ->with(['empleado', 'firma', 'sello', 'cargo_firma.tipoCargoFirma'])
            ->where('firmable_type', Proyecto::class)
            ->where('revision_ciclo', $ciclo)
            ->where('estado_revision', 'Aprobado')
            ->whereHas('flujoEtapa', fn ($query) => $query->where('aplica_inscripcion', true))
            ->orderByDesc('orden_revision')
            ->first();
    }

    private function cargoLabel(FirmaProyecto $firma): string
    {
        $etapaNombre = trim((string) $firma->etapa_nombre);

        // Algunos flujos guardaron el número de orden como "nombre" de la etapa
        // (p. ej. "2"); en ese caso no sirve como título para el certificado.
        if ($etapaNombre !== '' && ! ctype_digit($etapaNombre)) {
            return $etapaNombre;
        }

        return $firma->cargo_firma?->tipoCargoFirma?->nombre ?: 'Director de Vinculación Universidad-Sociedad';
    }
}
