<?php

namespace App\Support\Dashboard\Tramites;

use App\Models\ENF\EnfAccion;
use App\Support\Dashboard\AmbitoPanel;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

/**
 * Educación no formal (FORM-DVUS-016 y FORM-DVUS-018).
 *
 * Es el caso que no encaja en el patrón del resto del sistema, y por eso tiene
 * clase propia en vez de declararse con FamiliaPorEstados:
 *
 *  - No usa `estado_proyecto`: lleva su estado en `enf_acciones.estado_flujo`.
 *  - Tiene flujos anidados. Cada proceso —inscripción, informe intermedio,
 *    informe final— repite el ciclo completo de etapas en `enf_revisiones`, y
 *    dentro de cada proceso los ciclos de subsanación se numeran con
 *    `revision_ciclo`. Una acción puede estar en la segunda vuelta de revisión
 *    de su informe final mientras su inscripción quedó aprobada hace meses.
 *
 * La fase que muestra el panel es el proceso más avanzado que ya tiene
 * revisiones, que es lo que sitúa la acción en su recorrido.
 */
class FamiliaEnf implements FamiliaTramite
{
    private const FORMULARIOS = ['FORM-DVUS-016', 'FORM-DVUS-018'];

    public function clave(): string
    {
        return 'enf';
    }

    public function etiqueta(): string
    {
        return 'Educación no formal';
    }

    public function formularios(): array
    {
        return self::FORMULARIOS;
    }

    public function itinerario(): array
    {
        return [
            ['clave' => 'inscripcion', 'etiqueta' => 'Inscripción', 'tono' => 'info'],
            ['clave' => 'aprobada', 'etiqueta' => 'Aprobada', 'tono' => 'exito'],
            ['clave' => 'intermedio', 'etiqueta' => 'Informe intermedio', 'tono' => 'acento'],
            ['clave' => 'final', 'etiqueta' => 'Informe final', 'tono' => 'acento'],
            ['clave' => 'cerrada', 'etiqueta' => 'Cerrada', 'tono' => 'neutro'],
        ];
    }

    public function conteos(AmbitoPanel $ambito): array
    {
        $conteos = ['inscripcion' => 0, 'aprobada' => 0, 'intermedio' => 0, 'final' => 0, 'cerrada' => 0];

        $filas = $this->consultaBase($ambito)
            ->selectRaw(
                'enf_acciones.id,
                 enf_acciones.estado_flujo,
                 EXISTS (SELECT 1 FROM enf_revisiones r
                         WHERE r.enf_accion_id = enf_acciones.id AND r.proceso = ?) as tiene_intermedio,
                 EXISTS (SELECT 1 FROM enf_revisiones r
                         WHERE r.enf_accion_id = enf_acciones.id AND r.proceso = ?) as tiene_final',
                [EnfAccion::PROCESO_INFORME_INTERMEDIO, EnfAccion::PROCESO_INFORME_FINAL]
            )
            ->get();

        foreach ($filas as $fila) {
            $estado = strtoupper((string) $fila->estado_flujo);

            if ($estado === 'BORRADOR') {
                continue;   // va en sinIniciar()
            }

            // El proceso más avanzado con revisiones manda sobre el estado.
            if ($fila->tiene_final) {
                $conteos[$estado === 'FINALIZADO' ? 'cerrada' : 'final']++;
            } elseif ($fila->tiene_intermedio) {
                $conteos['intermedio']++;
            } elseif ($estado === 'FINALIZADO') {
                $conteos['cerrada']++;
            } elseif ($estado === 'APROBADO') {
                $conteos['aprobada']++;
            } else {
                $conteos['inscripcion']++;
            }
        }

        return $conteos;
    }

    public function total(AmbitoPanel $ambito): int
    {
        return $this->consultaBase($ambito)->count();
    }

    public function sinIniciar(AmbitoPanel $ambito): int
    {
        return $this->consultaBase($ambito)->where('estado_flujo', 'BORRADOR')->count();
    }

    public function admiteAmbito(AmbitoPanel $ambito): bool
    {
        // enf_acciones sí tiene centro_facultad_id y departamento_academico_id.
        return true;
    }

    /** @return Builder<EnfAccion> */
    private function consultaBase(AmbitoPanel $ambito): Builder
    {
        $query = EnfAccion::query()->whereIn('codigo_formulario', self::FORMULARIOS);

        if ($ambito->tipo->value === 'ninguno') {
            return $query->whereRaw('1 = 0');
        }

        if ($ambito->departamentoAcademicoId !== null && $ambito->tipo->value === 'departamento') {
            return $query->where('departamento_academico_id', $ambito->departamentoAcademicoId);
        }

        if ($ambito->centroFacultadId !== null && $ambito->tipo->value === 'centro') {
            return $query->where('centro_facultad_id', $ambito->centroFacultadId);
        }

        if ($ambito->tipo->value === 'personal' && $ambito->userId !== null) {
            return $query->where('creado_por_usuario_id', $ambito->userId);
        }

        return $query;
    }

    /**
     * Ciclos de subsanación abiertos: lo que distingue a ENF del resto.
     *
     * Una acción en su tercera vuelta de revisión lleva dos rechazos encima, y
     * eso no se ve en el estado.
     */
    public function revisionesRepetidas(AmbitoPanel $ambito): int
    {
        return (int) DB::table('enf_revisiones')
            ->whereIn('enf_accion_id', $this->consultaBase($ambito)->select('enf_acciones.id'))
            ->where('revision_ciclo', '>', 1)
            ->distinct()
            ->count('enf_accion_id');
    }
}
