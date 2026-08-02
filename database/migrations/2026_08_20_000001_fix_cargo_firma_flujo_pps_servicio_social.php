<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $flujos = DB::table('flujos_aprobacion')
            ->where('proceso', 'PPS_SERVICIO_SOCIAL')
            ->get();

        if ($flujos->isEmpty()) {
            return;
        }

        $flujoIds = $flujos->pluck('id');

        $etapasSinCargo = DB::table('flujos_aprobacion_etapas')
            ->whereIn('flujo_aprobacion_id', $flujoIds)
            ->whereNull('cargo_firma_id')
            ->get();

        foreach ($etapasSinCargo as $etapa) {
            $cargoFirmaId = match ($etapa->tipo_etapa) {
                'FORMULACION' => 1,
                'APROBACION' => 6,
                default => 5,
            };

            DB::table('flujos_aprobacion_etapas')
                ->where('id', $etapa->id)
                ->update(['cargo_firma_id' => $cargoFirmaId]);
        }
    }

    public function down(): void
    {
        // No-op: una vez asignado cargo_firma_id no podemos distinguir
        // las etapas que el backfill tocó de las configuradas por UI.
    }
};
