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

        $firma = $this->firmaDirectorVinculacion($documento, $ciclo)
            ?? $this->firmaUltimaEtapaDeCierre($documento, $ciclo);

        if (! $firma instanceof FirmaProyecto || ! $firma->empleado) {
            throw new RuntimeException('No existe una autoridad DVUS aprobadora configurada para emitir la constancia de finalización.');
        }

        return [
            'nombre' => $firma->empleado->nombre_completo ?: 'No registrado',
            'cargo' => $this->cargoLabel($firma),
            'firma_ruta' => $firma->firma?->ruta_storage,
            'sello_ruta' => $firma->sello?->ruta_storage,
        ];
    }

    /** Camino principal: firma aprobada de "Director Vinculacion" en el ciclo vigente. */
    private function firmaDirectorVinculacion(DocumentoProyecto $documento, int $ciclo): ?FirmaProyecto
    {
        return $documento->firma_documento()
            ->with(['empleado', 'firma', 'sello', 'cargo_firma.tipoCargoFirma'])
            ->where('revision_ciclo', $ciclo)
            ->where('estado_revision', 'Aprobado')
            ->whereHas('cargo_firma.tipoCargoFirma', fn ($query) => $query->where('nombre', 'Director Vinculacion'))
            ->orderByDesc('fecha_firma')
            ->first();
    }

    /**
     * Fallback: algunos flujos de cierre (p. ej. "Flujo FORM-DVUS-001 - Desarrollo local
     * y regional") no incluyen una etapa "Director Vinculacion" y finalizan el cierre en
     * "Revisor Vinculacion". En ese caso, la autoridad emisora es quien aprobó la última
     * etapa del flujo marcada `aplica_cierre_proyecto`.
     */
    private function firmaUltimaEtapaDeCierre(DocumentoProyecto $documento, int $ciclo): ?FirmaProyecto
    {
        return $documento->firma_documento()
            ->with(['empleado', 'firma', 'sello', 'cargo_firma.tipoCargoFirma'])
            ->where('revision_ciclo', $ciclo)
            ->where('estado_revision', 'Aprobado')
            ->whereHas('flujoEtapa', fn ($query) => $query->where('aplica_cierre_proyecto', true))
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