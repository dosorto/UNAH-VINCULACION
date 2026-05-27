<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('flujos_aprobacion_etapas', function (Blueprint $table) {
            $table->boolean('aplica_inscripcion')
                ->default(true)
                ->after('codigo');
            $table->boolean('aplica_informe_intermedio')
                ->default(false)
                ->after('aplica_inscripcion');
            $table->boolean('aplica_cierre_proyecto')
                ->default(false)
                ->after('aplica_informe_intermedio');
        });
    }

    public function down(): void
    {
        Schema::table('flujos_aprobacion_etapas', function (Blueprint $table) {
            $table->dropColumn([
                'aplica_inscripcion',
                'aplica_informe_intermedio',
                'aplica_cierre_proyecto',
            ]);
        });
    }
};
