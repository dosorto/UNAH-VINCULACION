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
            // Hacer estudiante_id nullable ya que ahora manejaremos cantidades en lugar de estudiantes individuales
            $table->foreignId('estudiante_id')->nullable()->change();
            
            // Agregar campos de cantidad de estudiantes
            $table->integer('cantidad_estudiantes_hombres')->default(0)->after('tipo_participacion_estudiante');
            $table->integer('cantidad_estudiantes_mujeres')->default(0)->after('cantidad_estudiantes_hombres');
            $table->integer('total_estudiantes')->default(0)->after('cantidad_estudiantes_mujeres');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('estudiante_proyecto', function (Blueprint $table) {
            // Revertir cambios
            $table->foreignId('estudiante_id')->nullable(false)->change();
            $table->dropColumn([
                'cantidad_estudiantes_hombres',
                'cantidad_estudiantes_mujeres',
                'total_estudiantes'
            ]);
        });
    }
};
