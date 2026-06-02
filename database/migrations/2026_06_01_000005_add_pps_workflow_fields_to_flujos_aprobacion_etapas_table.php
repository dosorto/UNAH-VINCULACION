<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('flujos_aprobacion_etapas', function (Blueprint $table) {
            $table->string('estado_resultante')->nullable()->after('activo');
            $table->boolean('permite_edicion')->default(false)->after('estado_resultante');
            $table->boolean('permite_rechazo')->default(true)->after('permite_edicion');
            $table->boolean('es_estado_final_aprobado')->default(false)->after('permite_rechazo');
        });
    }

    public function down(): void
    {
        Schema::table('flujos_aprobacion_etapas', function (Blueprint $table) {
            $table->dropColumn([
                'estado_resultante',
                'permite_edicion',
                'permite_rechazo',
                'es_estado_final_aprobado',
            ]);
        });
    }
};
