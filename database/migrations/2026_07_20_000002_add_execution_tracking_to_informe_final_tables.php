<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        foreach (['informe_final_equipo_docente', 'informe_final_cooperacion', 'informe_final_estudiantes', 'informe_final_voluntarios'] as $tabla) {
            Schema::table($tabla, function (Blueprint $table) {
                $table->string('estado_participacion', 20)->default('activo');
                $table->text('observacion_no_participacion')->nullable();
                $table->timestamp('removido_en')->nullable();
                $table->foreignId('removido_por')->nullable()->constrained('users')->nullOnDelete();
            });
        }

        Schema::table('informe_final_anexos', function (Blueprint $table) {
            $table->foreignId('informe_final_contraparte_id')->nullable()->constrained('informe_final_contrapartes', indexName: 'inf_anexo_contraparte_fk')->nullOnDelete();
            $table->foreignId('instrumento_formalizacion_id')->nullable()->constrained('instrumento_formalizacion', indexName: 'inf_anexo_instrumento_fk')->nullOnDelete();
            $table->string('categoria', 40)->default('documento_general');
            $table->string('nombre_archivo')->nullable();
            $table->unsignedBigInteger('tamano_bytes')->nullable();
            $table->string('origen', 20)->default('INFORME');
            $table->unique(['informe_final_proyecto_id', 'instrumento_formalizacion_id'], 'inf_anexo_instrumento_unique');
        });
    }

    public function down(): void
    {
        Schema::table('informe_final_anexos', function (Blueprint $table) {
            $table->dropUnique('inf_anexo_instrumento_unique');
            $table->dropForeign('inf_anexo_contraparte_fk');
            $table->dropForeign('inf_anexo_instrumento_fk');
            $table->dropColumn(['informe_final_contraparte_id', 'instrumento_formalizacion_id', 'categoria', 'nombre_archivo', 'tamano_bytes', 'origen']);
        });

        foreach (['informe_final_equipo_docente', 'informe_final_cooperacion', 'informe_final_estudiantes', 'informe_final_voluntarios'] as $tabla) {
            Schema::table($tabla, function (Blueprint $table) use ($tabla) {
                $table->dropForeign($tabla.'_removido_por_foreign');
                $table->dropColumn(['estado_participacion', 'observacion_no_participacion', 'removido_en', 'removido_por']);
            });
        }
    }
};
