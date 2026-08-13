<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

return new class extends Migration
{
    /**
     * La etapa "FORMULACION" (Coordinador Proyecto) deja de configurarse en el
     * flujo: se auto-firma en el momento del envío usando la firma de quien
     * inscribió el proyecto. Aquí solo desactivamos las etapas existentes de
     * ese tipo (sin borrarlas) para no romper las FKs de `firma_proyecto`
     * ligadas a proyectos con historial.
     */
    public function up(): void
    {
        $etapas = DB::table('flujos_aprobacion_etapas')
            ->where('tipo_etapa', 'FORMULACION')
            ->get();

        if ($etapas->isEmpty()) {
            return;
        }

        $etapaIds = $etapas->pluck('id');

        $firmasPendientesColgadas = DB::table('firma_proyecto')
            ->whereIn('flujo_aprobacion_etapa_id', $etapaIds)
            ->where('estado_revision', 'Pendiente')
            ->whereNull('deleted_at')
            ->count();

        if ($firmasPendientesColgadas > 0) {
            Log::warning(sprintf(
                'Se desactivaron %d etapas FORMULACION con %d firma(s) Pendiente asociadas. '
                .'Revisar manualmente antes/después del despliegue: estas firmas quedarán '
                .'sin siguiente acción automática.',
                $etapaIds->count(),
                $firmasPendientesColgadas
            ));
        }

        DB::table('flujos_aprobacion_etapas')
            ->whereIn('id', $etapaIds)
            ->update(['activo' => false]);
    }

    public function down(): void
    {
        // No-op: no podemos distinguir de forma segura las etapas que esta
        // migración desactivó de las que un administrador desactivó manualmente.
    }
};
