<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * La migración 2026_07_15_103344_fix_cargo_firma_desincronizado_en_etapas_y_firmas
 * ya corrigió este mismo problema una vez, pero el bug de origen estaba en
 * ConfiguracionFlujosProyectos (la pantalla de administración de flujos no
 * tenía ningún campo para elegir el "cargo de firma" por etapa, así que cada
 * etapa nueva o reguardada quedaba con el valor por defecto = "Revisor
 * Vinculacion"). Ese bug de UI ya se corrigió (ahora hay un select explícito
 * y validación), pero el flujo "Desarrollo local y regional" (FORM-DVUS-001)
 * se volvió a guardar desde esa pantalla rota el 2026-07-17, dañando otra vez
 * los mismos datos. Esta migración reaplica exactamente la misma corrección
 * genérica e idempotente de la migración anterior.
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
        // cual revertir.
    }
};
