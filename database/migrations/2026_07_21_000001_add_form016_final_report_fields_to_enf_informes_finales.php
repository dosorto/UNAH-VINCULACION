<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('enf_informes_finales', function (Blueprint $table) {
            $table->unsignedInteger('inscritos_hombres')->default(0)->after('resultados_obtenidos');
            $table->unsignedInteger('inscritos_mujeres')->default(0)->after('inscritos_hombres');
            $table->unsignedInteger('no_presentaron_hombres')->default(0)->after('inscritos_mujeres');
            $table->unsignedInteger('no_presentaron_mujeres')->default(0)->after('no_presentaron_hombres');
            $table->unsignedInteger('abandonaron_hombres')->default(0)->after('no_presentaron_mujeres');
            $table->unsignedInteger('abandonaron_mujeres')->default(0)->after('abandonaron_hombres');
            $table->unsignedInteger('reprobaron_hombres')->default(0)->after('abandonaron_mujeres');
            $table->unsignedInteger('reprobaron_mujeres')->default(0)->after('reprobaron_hombres');
            $table->unsignedInteger('aprobaron_hombres')->default(0)->after('reprobaron_mujeres');
            $table->unsignedInteger('aprobaron_mujeres')->default(0)->after('aprobaron_hombres');
            $table->unsignedInteger('graduados_unah_hombres')->default(0)->after('aprobaron_mujeres');
            $table->unsignedInteger('graduados_unah_mujeres')->default(0)->after('graduados_unah_hombres');
            $table->text('contenido_curricular_cambios')->nullable()->after('graduados_unah_mujeres');
            $table->text('cronograma_cambios')->nullable()->after('contenido_curricular_cambios');
            $table->string('modalidad_acreditacion', 80)->nullable()->after('cronograma_cambios');
            $table->text('seguimiento_sistematizacion')->nullable()->after('modalidad_acreditacion');
            $table->text('dificultades')->nullable()->after('seguimiento_sistematizacion');
            $table->text('lecciones_aprendidas')->nullable()->after('dificultades');
            $table->text('buenas_practicas')->nullable()->after('lecciones_aprendidas');
            $table->text('transformacion_lograda')->nullable()->after('buenas_practicas');
            $table->text('desafios')->nullable()->after('transformacion_lograda');
            $table->text('respuesta_reforma_universitaria')->nullable()->after('desafios');
            $table->unsignedInteger('valoracion_total_beneficiarios')->default(0)->after('respuesta_reforma_universitaria');
            $table->unsignedInteger('valoracion_muestra')->default(0)->after('valoracion_total_beneficiarios');
            $table->unsignedInteger('valoracion_excelente')->default(0)->after('valoracion_muestra');
            $table->unsignedInteger('valoracion_muy_buena')->default(0)->after('valoracion_excelente');
            $table->unsignedInteger('valoracion_regular')->default(0)->after('valoracion_muy_buena');
            $table->unsignedInteger('valoracion_mala')->default(0)->after('valoracion_regular');
            $table->text('observaciones_finales')->nullable()->after('valoracion_mala');
            $table->boolean('confirmacion_veracidad')->default(false)->after('observaciones_finales');
        });
    }

    public function down(): void
    {
        Schema::table('enf_informes_finales', function (Blueprint $table) {
            $table->dropColumn([
                'inscritos_hombres',
                'inscritos_mujeres',
                'no_presentaron_hombres',
                'no_presentaron_mujeres',
                'abandonaron_hombres',
                'abandonaron_mujeres',
                'reprobaron_hombres',
                'reprobaron_mujeres',
                'aprobaron_hombres',
                'aprobaron_mujeres',
                'graduados_unah_hombres',
                'graduados_unah_mujeres',
                'contenido_curricular_cambios',
                'cronograma_cambios',
                'modalidad_acreditacion',
                'seguimiento_sistematizacion',
                'dificultades',
                'lecciones_aprendidas',
                'buenas_practicas',
                'transformacion_lograda',
                'desafios',
                'respuesta_reforma_universitaria',
                'valoracion_total_beneficiarios',
                'valoracion_muestra',
                'valoracion_excelente',
                'valoracion_muy_buena',
                'valoracion_regular',
                'valoracion_mala',
                'observaciones_finales',
                'confirmacion_veracidad',
            ]);
        });
    }
};
