<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * La migración 2026_07_10_094511_fix_cargo_firma_flujo_form_dvus_001 intentó
 * corregir las etapas del flujo FORM-DVUS-001 que quedaron todas apuntando al
 * mismo cargo_firma_id (5 = "Revisor Vinculacion"), pero comparaba el nombre
 * de la etapa contra claves en minúsculas ('coordinador proyecto',
 * 'director vinculacion') que no coinciden con los nombres reales guardados
 * ('Coordinador proyecto', 'Director centro'), así que nunca corrigió nada.
 * El mismo problema (todas las etapas con cargo_firma_id=5) existe también en
 * el flujo FORM-DVUS-014 (PPS/Servicio Social), que ni siquiera estaba en el
 * alcance de esa migración.
 *
 * Esta migración corrige, de forma genérica, cualquier etapa cuyo nombre
 * coincida (sin distinguir mayúsculas/espacios) con el nombre de un cargo de
 * firma "Proyecto" pero cuyo cargo_firma_id no sea el correcto; propaga la
 * corrección a las firmas ya creadas para esa etapa (que copian cargo_firma_id
 * en el momento de creación); y recalcula el estado actual de los
 * proyectos/documentos con una firma Pendiente en esa etapa, para que quede
 * consistente con el cargo corregido.
 */
return new class extends Migration
{
    public function up(): void
    {
        $cargosPorNombre = DB::table('cargo_firma')
            ->join('tipo_cargo_firma', 'cargo_firma.tipo_cargo_firma_id', '=', 'tipo_cargo_firma.id')
            ->where('cargo_firma.descripcion', 'Proyecto')
            ->select('cargo_firma.id', 'cargo_firma.tipo_estado_id', 'tipo_cargo_firma.nombre')
            ->get()
            ->keyBy(fn ($cargo) => mb_strtolower(trim($cargo->nombre)));

        $etapas = DB::table('flujos_aprobacion_etapas')->get();

        foreach ($etapas as $etapa) {
            $clave = mb_strtolower(trim($etapa->nombre));
            $cargoCorrecto = $cargosPorNombre->get($clave);

            if (! $cargoCorrecto || (int) $cargoCorrecto->id === (int) $etapa->cargo_firma_id) {
                continue;
            }

            DB::table('flujos_aprobacion_etapas')
                ->where('id', $etapa->id)
                ->update(['cargo_firma_id' => $cargoCorrecto->id]);

            DB::table('firma_proyecto')
                ->where('flujo_aprobacion_etapa_id', $etapa->id)
                ->whereNull('deleted_at')
                ->update(['cargo_firma_id' => $cargoCorrecto->id]);
        }

        // Todas las etapas de un ciclo se crean "Pendiente" a la vez (no solo
        // la actual), así que el estado_proyecto/estado_documento hay que
        // recalcularlo una sola vez por (firmable, flujo, ciclo) usando la
        // MISMA regla que Proyecto::firmaActualDeEtapasDelFlujo (la primera
        // firma en orden que no esté Aprobada/Anulada) — nunca por etapa
        // aislada, o una etapa posterior pisaría el estado de la vigente.
        // Se corre siempre (no solo si hubo corrección de cargo arriba) porque
        // es idempotente y así corrige también desajustes que ya existieran.
        $this->corregirEstadosActualesDesdeFirmaVigente();
    }

    private function corregirEstadosActualesDesdeFirmaVigente(): void
    {
        $grupos = DB::table('firma_proyecto')
            ->whereNotNull('flujo_aprobacion_etapa_id')
            ->whereNotNull('flujo_aprobacion_id')
            ->whereNotNull('revision_ciclo')
            ->whereNull('deleted_at')
            ->select('firmable_type', 'firmable_id', 'flujo_aprobacion_id', 'revision_ciclo')
            ->distinct()
            ->get();

        foreach ($grupos as $grupo) {
            $firmasCiclo = DB::table('firma_proyecto')
                ->where('firmable_type', $grupo->firmable_type)
                ->where('firmable_id', $grupo->firmable_id)
                ->where('flujo_aprobacion_id', $grupo->flujo_aprobacion_id)
                ->where('revision_ciclo', $grupo->revision_ciclo)
                ->whereNotNull('flujo_aprobacion_etapa_id')
                ->whereNull('deleted_at')
                ->orderBy('orden_revision')
                ->orderBy('id')
                ->get();

            $firmaActual = null;

            foreach ($firmasCiclo as $firma) {
                if (in_array($firma->estado_revision, ['Aprobado', 'Anulado'], true)) {
                    continue;
                }

                if ($firma->estado_revision === 'Pendiente') {
                    $firmaActual = $firma;
                }

                break;
            }

            if (! $firmaActual) {
                continue;
            }

            $tipoEstadoId = DB::table('cargo_firma')->where('id', $firmaActual->cargo_firma_id)->value('tipo_estado_id');

            if (! $tipoEstadoId) {
                continue;
            }

            $estadoActual = DB::table('estado_proyecto')
                ->where('estadoable_type', $grupo->firmable_type)
                ->where('estadoable_id', $grupo->firmable_id)
                ->where('es_actual', true)
                ->first();

            if (! $estadoActual || (int) $estadoActual->tipo_estado_id === (int) $tipoEstadoId) {
                continue;
            }

            DB::table('estado_proyecto')
                ->where('id', $estadoActual->id)
                ->update(['tipo_estado_id' => $tipoEstadoId]);
        }
    }

    public function down(): void
    {
        // Corrección de datos: no hay un estado "anterior" único y seguro al
        // cual revertir de forma genérica (a diferencia del fix puntual de
        // FORM-DVUS-001, este cubre cualquier etapa/flujo desincronizado).
    }
};
