<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('estudiante_proyecto', function (Blueprint $table) {
            $table->foreignId('asignatura_id')->nullable()->constrained('asignaturas');
            $table->foreignId('periodo_academico_id')->nullable()->constrained('periodos_academicos');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('estudiante_proyecto', function (Blueprint $table) {
            $table->dropForeign(['asignatura_id']);
            $table->dropColumn('asignatura_id');
            $table->dropForeign(['periodo_academico_id']);
            $table->dropColumn('periodo_academico_id');
        });
    }
};