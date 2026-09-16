<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('flujos_aprobacion') || ! Schema::hasTable('flujos_aprobacion_etapas')) {
            return;
        }

        $flujoIds = DB::table('flujos_aprobacion')
            ->where('codigo', 'PASANTIAS_FORM_DVUS_013')
            ->where('proceso', 'PASANTIAS_DEFAULT')
            ->pluck('id');

        if ($flujoIds->isEmpty()) {
            return;
        }

        // La primera migración creó esta etapa como fija, pero no podía
        // conocer un usuario porque los roles se cargan después de migrar.
        // Si sigue sin responsable ni rol, debe resolverse por el cargo de
        // firma; de lo contrario ningún registro nuevo puede entrar al flujo.
        DB::table('flujos_aprobacion_etapas')
            ->whereIn('flujo_aprobacion_id', $flujoIds)
            ->whereNull('usuario_responsable_id')
            ->whereNull('rol_revisor_id')
            ->update([
                'requiere_asignacion' => false,
                'updated_at' => now(),
            ]);
    }

    public function down(): void
    {
        // No se revierte: volver a exigir un responsable sin haber guardado
        // el valor anterior volvería a bloquear el envío de FORM-DVUS-013.
    }
};
