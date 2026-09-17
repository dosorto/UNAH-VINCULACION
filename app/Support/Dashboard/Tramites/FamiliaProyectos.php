<?php

namespace App\Support\Dashboard\Tramites;

use App\Models\Proyecto\DocumentoProyecto;
use App\Models\Proyecto\Proyecto;
use App\Support\Dashboard\AmbitoPanel;
use App\Support\Dashboard\EstadosProyecto;

/**
 * Proyectos de vinculación (FORM-DVUS-001 y FORM-DVUS-015).
 *
 * Su itinerario no termina en la aprobación: sigue con el informe intermedio
 * —solo si el flujo asignado lo define— y con el informe final que cierra el
 * proyecto.
 *
 * Necesita clase propia porque la fase no se deduce del estado: un proyecto con
 * el informe final en revisión conserva el estado "En curso", y lo que de
 * verdad marca dónde está es el documento más avanzado que ya entró al flujo de
 * firmas. Es el mismo criterio de Proyecto::procesoActivoParaStepper().
 */
class FamiliaProyectos extends FamiliaPorEstados
{
    public function __construct()
    {
        parent::__construct(
            clave: 'proyectos',
            etiqueta: 'Proyectos de vinculación',
            modelo: Proyecto::class,
            formularios: ['FORM-DVUS-001', 'FORM-DVUS-015'],
            // Las fases reales se calculan en conteos(); esta lista solo declara
            // el itinerario y su orden.
            fases: [
                ['clave' => 'inscripcion', 'etiqueta' => 'Inscripción', 'tono' => 'info', 'estados' => EstadosProyecto::EN_REVISION_ACTIVA],
                ['clave' => 'ejecucion', 'etiqueta' => 'En ejecución', 'tono' => 'exito', 'estados' => [EstadosProyecto::EN_CURSO]],
                ['clave' => 'intermedio', 'etiqueta' => 'Informe intermedio', 'tono' => 'acento', 'estados' => []],
                ['clave' => 'final', 'etiqueta' => 'Informe final', 'tono' => 'acento', 'estados' => []],
                ['clave' => 'cerrado', 'etiqueta' => 'Cerrado', 'tono' => 'neutro', 'estados' => [EstadosProyecto::FINALIZADO]],
            ],
            pivoteCentro: 'proyecto_centro_facultad',
        );
    }

    public function conteos(AmbitoPanel $ambito): array
    {
        $idsBorrador = EstadosProyecto::ids(EstadosProyecto::SIN_ENVIAR);
        $idsCurso = EstadosProyecto::ids(EstadosProyecto::EN_CURSO);
        $idsFinal = EstadosProyecto::ids(EstadosProyecto::FINALIZADO);

        $filas = $this->consultaBase($ambito)
            ->leftJoin('estado_proyecto as ep', function ($join): void {
                $join->on('ep.estadoable_id', '=', 'proyecto.id')
                    ->where('ep.estadoable_type', '=', Proyecto::class)
                    ->where('ep.es_actual', '=', true);
            })
            ->selectRaw(
                'proyecto.id,
                 ep.tipo_estado_id,
                 EXISTS (
                    SELECT 1 FROM proyecto_documento pd
                    JOIN firma_proyecto fp ON fp.firmable_id = pd.id
                     AND fp.firmable_type = ? AND fp.deleted_at IS NULL
                    WHERE pd.proyecto_id = proyecto.id AND pd.tipo_documento = ?
                 ) as tiene_intermedio,
                 EXISTS (
                    SELECT 1 FROM proyecto_documento pd
                    JOIN firma_proyecto fp ON fp.firmable_id = pd.id
                     AND fp.firmable_type = ? AND fp.deleted_at IS NULL
                    WHERE pd.proyecto_id = proyecto.id AND pd.tipo_documento = ?
                 ) as tiene_final',
                [
                    DocumentoProyecto::class, 'Informe Intermedio',
                    DocumentoProyecto::class, 'Informe Final',
                ]
            )
            ->get();

        $conteos = ['inscripcion' => 0, 'ejecucion' => 0, 'intermedio' => 0, 'final' => 0, 'cerrado' => 0];

        foreach ($filas as $fila) {
            $estado = $fila->tipo_estado_id !== null ? (int) $fila->tipo_estado_id : null;

            // El documento manda sobre el estado.
            if ($fila->tiene_final) {
                $conteos['final']++;
            } elseif ($fila->tiene_intermedio) {
                $conteos['intermedio']++;
            } elseif ($estado !== null && in_array($estado, $idsFinal, true)) {
                $conteos['cerrado']++;
            } elseif ($estado !== null && in_array($estado, $idsCurso, true)) {
                $conteos['ejecucion']++;
            } elseif ($estado === null || in_array($estado, $idsBorrador, true)) {
                // Borrador: se cuenta en sinIniciar(), no aquí.
                continue;
            } else {
                $conteos['inscripcion']++;
            }
        }

        return $conteos;
    }
}
