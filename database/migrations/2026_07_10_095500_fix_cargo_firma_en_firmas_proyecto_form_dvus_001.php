<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Las firma_proyecto ya creadas para las etapas de FORM-DVUS-001 copiaron el
 * cargo_firma_id incorrecto (5) que tenía la etapa en el momento del envío
 * (ver migración 2026_07_10_094511). Al corregir la etapa, las firmas
 * "Pendiente" quedan desincronizadas: su cargo_firma_id ya no coincide con
 * el estado_proyecto actual, lo que rompe la comparación que usa
 * ResolvesFirmasPendientes::estadoActualCoincideConCargoDeFirma().
 */
return new class extends Migration
{
    private int $cargoFirmaIncorrecto = 5;

    public function up(): void
    {
        $flujo = DB::table('flujos_aprobacion')
            ->where('codigo_formulario', 'FORM-DVUS-001')
            ->where('proceso', 'PROYECTO')
            ->first();

        if (! $flujo) {
            return;
        }

        $etapas = DB::table('flujos_aprobacion_etapas')
            ->where('flujo_aprobacion_id', $flujo->id)
            ->get();

        foreach ($etapas as $etapa) {
            DB::table('firma_proyecto')
                ->where('flujo_aprobacion_etapa_id', $etapa->id)
                ->where('estado_revision', 'Pendiente')
                ->where('cargo_firma_id', $this->cargoFirmaIncorrecto)
                ->update(['cargo_firma_id' => $etapa->cargo_firma_id]);
        }
    }

    public function down(): void
    {
        $flujo = DB::table('flujos_aprobacion')
            ->where('codigo_formulario', 'FORM-DVUS-001')
            ->where('proceso', 'PROYECTO')
            ->first();

        if (! $flujo) {
            return;
        }

        $etapaIds = DB::table('flujos_aprobacion_etapas')
            ->where('flujo_aprobacion_id', $flujo->id)
            ->pluck('id');

        DB::table('firma_proyecto')
            ->whereIn('flujo_aprobacion_etapa_id', $etapaIds)
            ->where('estado_revision', 'Pendiente')
            ->update(['cargo_firma_id' => $this->cargoFirmaIncorrecto]);
    }
};
