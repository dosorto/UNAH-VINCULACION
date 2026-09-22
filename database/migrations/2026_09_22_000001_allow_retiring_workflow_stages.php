<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('flujos_aprobacion_etapas', 'configuracion_vigente')) {
            Schema::table('flujos_aprobacion_etapas', function (Blueprint $table) {
                // true: configuración actual; null: retirada, conservada para historial.
                $table->boolean('configuracion_vigente')->nullable()->default(true);
            });
        }

        // Crear primero los sustitutos: el FK del flujo necesita un índice de soporte.
        foreach (['codigo', 'orden'] as $campo) {
            $nuevo = 'uq_flujo_etapa_vigente_'.$campo;
            if (! Schema::hasIndex('flujos_aprobacion_etapas', $nuevo)) {
                Schema::table('flujos_aprobacion_etapas', fn (Blueprint $table) =>
                    $table->unique(['flujo_aprobacion_id', $campo, 'configuracion_vigente'], $nuevo));
            }
            $anterior = 'uq_flujo_aprobacion_etapa_'.$campo;
            if (Schema::hasIndex('flujos_aprobacion_etapas', $anterior)) {
                Schema::table('flujos_aprobacion_etapas', fn (Blueprint $table) => $table->dropUnique($anterior));
            }
        }
    }

    public function down(): void
    {
        if (DB::table('flujos_aprobacion_etapas')->whereNull('configuracion_vigente')->exists()) {
            throw new RuntimeException('No se puede revertir mientras existan etapas retiradas con historial.');
        }

        Schema::table('flujos_aprobacion_etapas', function (Blueprint $table) {
            $table->unique(['flujo_aprobacion_id', 'codigo'], 'uq_flujo_aprobacion_etapa_codigo');
            $table->unique(['flujo_aprobacion_id', 'orden'], 'uq_flujo_aprobacion_etapa_orden');
            $table->dropUnique('uq_flujo_etapa_vigente_codigo');
            $table->dropUnique('uq_flujo_etapa_vigente_orden');
            $table->dropColumn('configuracion_vigente');
        });
    }
};
