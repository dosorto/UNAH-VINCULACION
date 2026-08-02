<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('flujos_aprobacion_etapas', function (Blueprint $table) {
            $table->dropColumn(['alcance_academico', 'multiplicidad_revision']);
        });
    }

    public function down(): void
    {
        Schema::table('flujos_aprobacion_etapas', function (Blueprint $table) {
            $table->string('alcance_academico', 40)->default('SIN_FILTRO')->after('es_estado_final_aprobado');
            $table->string('multiplicidad_revision', 40)->default('UNICO')->after('alcance_academico');
        });
    }
};
