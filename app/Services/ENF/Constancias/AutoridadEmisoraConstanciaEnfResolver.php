<?php

namespace App\Services\ENF\Constancias;

use App\Models\ENF\EnfAccion;
use App\Models\ENF\EnfRevision;
use RuntimeException;

class AutoridadEmisoraConstanciaEnfResolver
{
    /** @return array{nombre:string,cargo:string,firma_ruta:?string,sello_ruta:?string} */
    public function resolver(EnfAccion $accion, string $proceso, string $columnaFlujo): array
    {
        $ciclo = (int) $accion->revisiones()
            ->where('proceso', $proceso)
            ->max('revision_ciclo');

        $revision = $this->revisionDirectorVinculacion($accion, $proceso, $ciclo)
            ?? $this->revisionUltimaEtapa($accion, $proceso, $ciclo, $columnaFlujo);

        $empleado = $revision?->decididoPorUsuario?->empleado;

        if (! $revision instanceof EnfRevision || ! $empleado) {
            throw new RuntimeException('No existe una autoridad DVUS aprobadora configurada para emitir la constancia ENF.');
        }

        return [
            'nombre' => $empleado->nombre_completo ?: 'No registrado',
            'cargo' => $this->cargoLabel($revision),
            'firma_ruta' => $empleado->firma?->ruta_storage,
            'sello_ruta' => $empleado->sello?->ruta_storage,
        ];
    }

    private function revisionDirectorVinculacion(EnfAccion $accion, string $proceso, int $ciclo): ?EnfRevision
    {
        return $accion->revisiones()
            ->with(['decididoPorUsuario.empleado.firma', 'decididoPorUsuario.empleado.sello', 'flujoEtapa.cargoFirma.tipoCargoFirma'])
            ->where('proceso', $proceso)
            ->where('revision_ciclo', $ciclo)
            ->where('estado', 'APROBADO')
            ->whereHas('flujoEtapa.cargoFirma.tipoCargoFirma', fn ($query) => $query->where('nombre', 'Director Vinculacion'))
            ->orderByDesc('firmado_en')
            ->first();
    }

    private function revisionUltimaEtapa(EnfAccion $accion, string $proceso, int $ciclo, string $columnaFlujo): ?EnfRevision
    {
        return $accion->revisiones()
            ->with(['decididoPorUsuario.empleado.firma', 'decididoPorUsuario.empleado.sello', 'flujoEtapa.cargoFirma.tipoCargoFirma'])
            ->where('proceso', $proceso)
            ->where('revision_ciclo', $ciclo)
            ->where('estado', 'APROBADO')
            ->whereHas('flujoEtapa', fn ($query) => $query->where($columnaFlujo, true))
            ->orderByDesc('orden')
            ->first();
    }

    private function cargoLabel(EnfRevision $revision): string
    {
        $etapaNombre = trim((string) $revision->etapa_nombre);

        if ($etapaNombre !== '' && ! ctype_digit($etapaNombre)) {
            return $etapaNombre;
        }

        return $revision->flujoEtapa?->cargoFirma?->tipoCargoFirma?->nombre
            ?: 'Director de Vinculacion Universidad-Sociedad';
    }
}
