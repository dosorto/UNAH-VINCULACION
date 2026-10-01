<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Fecha de registro del FORM-DVUS-014: la del primer envío a revisión, cuando el formulario se
 * termina (no la de creación del borrador). No cambia en los reenvíos tras una subsanación.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pps_servicio_social', function (Blueprint $table): void {
            $table->timestamp('fecha_registro')->nullable()->after('fecha_envio');
        });

        DB::table('pps_servicio_social')
            ->whereNull('fecha_registro')
            ->whereNotNull('fecha_envio')
            ->update(['fecha_registro' => DB::raw('fecha_envio')]);
    }

    public function down(): void
    {
        Schema::table('pps_servicio_social', function (Blueprint $table): void {
            $table->dropColumn('fecha_registro');
        });
    }
};
