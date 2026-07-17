<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('informe_final_voluntarios', function (Blueprint $table) {
            $table->foreignId('empleado_id')->nullable()->after('informe_final_proyecto_id')
                ->constrained('empleado')->nullOnDelete();
        });

        Schema::create('informe_final_actividad_participantes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('informe_final_actividad_id')
                ->constrained('informe_final_actividades', indexName: 'inf_act_part_actividad_fk')
                ->cascadeOnDelete();
            $table->enum('tipo', ['docente', 'estudiante', 'voluntario', 'externo']);
            $table->foreignId('empleado_id')->nullable()->constrained('empleado')->nullOnDelete();
            $table->foreignId('informe_final_estudiante_id')->nullable()
                ->constrained('informe_final_estudiantes', indexName: 'inf_act_part_estudiante_fk')->nullOnDelete();
            $table->foreignId('informe_final_voluntario_id')->nullable()
                ->constrained('informe_final_voluntarios', indexName: 'inf_act_part_voluntario_fk')->nullOnDelete();
            $table->string('nombre');
            $table->string('rol')->nullable();
            $table->decimal('horas_dedicadas', 10, 2)->default(0);
            $table->boolean('es_responsable')->default(false);
            $table->unsignedInteger('orden')->default(0);
            $table->timestamps();
            $table->index(['informe_final_actividad_id', 'tipo'], 'inf_act_part_tipo_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('informe_final_actividad_participantes');
        Schema::table('informe_final_voluntarios', function (Blueprint $table) {
            $table->dropConstrainedForeignId('empleado_id');
        });
    }
};
