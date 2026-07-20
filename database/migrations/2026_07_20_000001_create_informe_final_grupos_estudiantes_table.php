<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('informe_final_grupos_estudiantes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('informe_final_proyecto_id')->constrained('informe_final_proyectos', indexName: 'inf_grupo_est_informe_fk')->cascadeOnDelete();
            $table->foreignId('estudiante_proyecto_id')->nullable()->constrained('estudiante_proyecto', indexName: 'inf_grupo_est_plan_fk')->nullOnDelete();
            $table->string('tipo_participacion', 40);
            $table->foreignId('asignatura_id')->nullable()->constrained('asignaturas', indexName: 'inf_grupo_est_asignatura_fk')->nullOnDelete();
            $table->string('periodo_academico', 50)->nullable();
            $table->unsignedInteger('hombres_planificados')->default(0);
            $table->unsignedInteger('mujeres_planificadas')->default(0);
            $table->timestamps();
            $table->unique(['informe_final_proyecto_id', 'estudiante_proyecto_id'], 'inf_grupo_est_plan_unique');
        });

        Schema::table('informe_final_estudiantes', function (Blueprint $table) {
            $table->foreignId('informe_final_grupo_estudiante_id')
                ->nullable()
                ->after('informe_final_proyecto_id')
                ->constrained('informe_final_grupos_estudiantes', indexName: 'inf_estudiantes_grupo_fk')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('informe_final_estudiantes', function (Blueprint $table) {
            $table->dropForeign('inf_estudiantes_grupo_fk');
            $table->dropColumn('informe_final_grupo_estudiante_id');
        });
        Schema::dropIfExists('informe_final_grupos_estudiantes');
    }
};
