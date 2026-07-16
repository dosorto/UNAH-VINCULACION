<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('informe_final_proyectos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('proyecto_id')->unique()->constrained('proyecto')->cascadeOnDelete();
            $table->string('numero_registro')->nullable();
            $table->date('fecha_registro')->nullable();
            $table->string('nombre_proyecto');
            $table->longText('objetivo_general')->nullable();
            $table->foreignId('centro_facultad_id')->nullable()->constrained('centro_facultad')->nullOnDelete();
            $table->foreignId('departamento_academico_id')->nullable()->constrained('departamento_academico')->nullOnDelete();
            $table->foreignId('carrera_id')->nullable()->constrained('carrera')->nullOnDelete();
            $table->foreignId('modalidad_id')->nullable()->constrained('modalidad')->nullOnDelete();
            $table->foreignId('categoria_id')->nullable()->constrained('categorias')->nullOnDelete();
            $table->foreignId('departamento_territorial_id')->nullable()->constrained('departamento', indexName: 'inf_final_departamento_fk')->nullOnDelete();
            $table->foreignId('municipio_id')->nullable()->constrained('municipio')->nullOnDelete();
            $table->string('facultad_centro')->nullable();
            $table->string('unidad_academica')->nullable();
            $table->string('departamento_academico')->nullable();
            $table->string('carrera')->nullable();
            $table->string('programa_vinculacion')->nullable();
            $table->text('linea_investigacion')->nullable();
            $table->string('modalidad')->nullable();
            $table->text('ejes_prioritarios')->nullable();
            $table->string('categoria')->nullable();
            $table->date('fecha_inicio')->nullable();
            $table->date('fecha_finalizacion')->nullable();
            $table->string('pais')->nullable();
            $table->string('region')->nullable();
            $table->string('departamento_territorial')->nullable();
            $table->string('municipio')->nullable();
            $table->string('aldea_ciudad')->nullable();
            $table->string('caserio')->nullable();
            $table->longText('dificultades')->nullable();
            $table->longText('acciones_dificultades')->nullable();
            $table->longText('lecciones_aprendidas')->nullable();
            $table->longText('buenas_practicas')->nullable();
            $table->longText('problema_inicial')->nullable();
            $table->longText('transformacion_lograda')->nullable();
            $table->longText('mecanismos_sostenibilidad')->nullable();
            $table->longText('acciones_contraparte_sostenibilidad')->nullable();
            $table->longText('desafios')->nullable();
            $table->longText('respuesta_reforma_universitaria')->nullable();
            $table->longText('recomendaciones')->nullable();
            $table->longText('bibliografia')->nullable();
            $table->unsignedInteger('valoracion_total_beneficiarios')->default(0);
            $table->unsignedInteger('valoracion_muestra')->default(0);
            $table->unsignedInteger('valoracion_excelente')->default(0);
            $table->unsignedInteger('valoracion_muy_buena')->default(0);
            $table->unsignedInteger('valoracion_regular')->default(0);
            $table->unsignedInteger('valoracion_mala')->default(0);
            $table->decimal('presupuesto_planificado', 15, 2)->default(0);
            $table->decimal('aporte_beneficiarios', 15, 2)->default(0);
            $table->decimal('otros_aportes', 15, 2)->default(0);
            $table->date('fecha_cierre')->nullable();
            $table->longText('observaciones_finales')->nullable();
            $table->boolean('confirmacion_veracidad')->default(false);
            $table->enum('estado', ['BORRADOR', 'COMPLETO'])->default('BORRADOR')->index();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
            $table->index(['departamento_territorial_id', 'fecha_cierre'], 'inf_final_territorio_cierre_idx');
        });

        Schema::create('informe_final_beneficiarios', function (Blueprint $table) {
            $table->id();
            $table->foreignId('informe_final_proyecto_id')->constrained('informe_final_proyectos', indexName: 'inf_beneficiarios_informe_fk')->cascadeOnDelete();
            foreach (['hombres', 'mujeres', 'edad_0_10', 'edad_11_18', 'edad_19_25', 'edad_26_35', 'edad_36_50', 'edad_51_65', 'edad_66_80', 'edad_81_mas', 'indigena_hombres', 'indigena_mujeres', 'afrodescendiente_hombres', 'afrodescendiente_mujeres', 'mestizo_hombres', 'mestizo_mujeres'] as $campo) {
                $table->unsignedInteger($campo)->default(0);
            }
            $table->timestamps();
            $table->unique('informe_final_proyecto_id', 'inf_beneficiarios_informe_unique');
        });

        Schema::create('informe_final_equipo_docente', function (Blueprint $table) {
            $table->id();
            $table->foreignId('informe_final_proyecto_id')->constrained('informe_final_proyectos', indexName: 'inf_equipo_informe_fk')->cascadeOnDelete();
            $table->foreignId('empleado_id')->nullable()->constrained('empleado')->nullOnDelete();
            $table->string('nombre');
            $table->string('numero_empleado')->nullable();
            $table->string('correo')->nullable();
            $table->string('categoria')->nullable();
            $table->string('departamento')->nullable();
            $table->string('sexo')->nullable();
            $table->decimal('horas_dedicadas', 10, 2)->default(0);
            $table->string('tipo_participacion')->nullable();
            $table->boolean('es_coordinador')->default(false);
            $table->timestamps();
            $table->index(['informe_final_proyecto_id', 'es_coordinador'], 'inf_equipo_coordinador_idx');
        });

        Schema::create('informe_final_cooperacion', function (Blueprint $table) {
            $table->id();
            $table->foreignId('informe_final_proyecto_id')->constrained('informe_final_proyectos', indexName: 'inf_cooperacion_informe_fk')->cascadeOnDelete();
            $table->string('nombre');
            $table->string('pasaporte')->nullable();
            $table->string('correo')->nullable();
            $table->string('pais')->nullable();
            $table->string('universidad')->nullable();
            $table->decimal('horas_dedicadas', 10, 2)->default(0);
            $table->timestamps();
        });

        Schema::create('informe_final_estudiantes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('informe_final_proyecto_id')->constrained('informe_final_proyectos', indexName: 'inf_estudiantes_informe_fk')->cascadeOnDelete();
            $table->foreignId('estudiante_id')->nullable()->constrained('estudiante')->nullOnDelete();
            $table->string('nombre');
            $table->string('sexo')->nullable();
            $table->string('numero_cuenta')->nullable();
            $table->string('carrera')->nullable();
            $table->enum('tipo_participacion', ['practica_asignatura', 'pps_servicio_social', 'voluntariado']);
            $table->decimal('horas_dedicadas', 10, 2)->default(0);
            $table->unsignedInteger('cantidad')->default(1);
            $table->timestamps();
        });

        Schema::create('informe_final_voluntarios', function (Blueprint $table) {
            $table->id();
            $table->foreignId('informe_final_proyecto_id')->constrained('informe_final_proyectos', indexName: 'inf_voluntarios_informe_fk')->cascadeOnDelete();
            $table->string('nombre');
            $table->string('sexo')->nullable();
            $table->string('identidad')->nullable();
            $table->string('departamento')->nullable();
            $table->enum('tipo', ['profesor_hora', 'pas', 'profesor_permanente', 'egresado']);
            $table->decimal('horas_dedicadas', 10, 2)->default(0);
            $table->timestamps();
        });

        Schema::create('informe_final_contrapartes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('informe_final_proyecto_id')->constrained('informe_final_proyectos', indexName: 'inf_contrapartes_informe_fk')->cascadeOnDelete();
            $table->foreignId('entidad_contraparte_id')->nullable()->constrained('entidad_contraparte', indexName: 'inf_contraparte_entidad_fk')->nullOnDelete();
            $table->boolean('existe_apoyo')->default(true);
            $table->string('nombre');
            $table->enum('tipo', ['gobierno_nacional', 'gobierno_municipal', 'ong', 'sociedad_civil', 'sector_privado', 'internacional']);
            $table->string('contacto')->nullable();
            $table->string('correo')->nullable();
            $table->string('cargo')->nullable();
            $table->string('telefono')->nullable();
            $table->enum('tipo_instrumento', ['carta_formal', 'carta_intenciones', 'convenio_marco'])->nullable();
            $table->text('compromisos_asumidos')->nullable();
            $table->text('compromisos_cumplidos')->nullable();
            $table->string('territorio')->nullable();
            $table->decimal('aporte_monetario', 15, 2)->default(0);
            $table->decimal('aporte_especie', 15, 2)->default(0);
            $table->string('documento_respaldo')->nullable();
            $table->timestamps();
        });

        Schema::create('informe_final_resultados', function (Blueprint $table) {
            $table->id();
            $table->foreignId('informe_final_proyecto_id')->constrained('informe_final_proyectos', indexName: 'inf_resultados_informe_fk')->cascadeOnDelete();
            $table->foreignId('resultado_esperado_id')->nullable()->constrained('resultado_esperado', indexName: 'inf_resultado_esperado_fk')->nullOnDelete();
            $table->text('objetivo_especifico')->nullable();
            $table->text('resultado_planificado');
            $table->text('indicador_propuesto')->nullable();
            $table->decimal('meta_numerica', 15, 2)->nullable();
            $table->string('unidad_medida')->nullable();
            $table->decimal('valor_alcanzado', 15, 2)->nullable();
            $table->decimal('porcentaje_cumplimiento', 5, 2)->default(0);
            $table->enum('estado', ['alcanzado', 'parcialmente_alcanzado', 'no_alcanzado', 'no_aplica'])->default('no_alcanzado');
            $table->text('producto_logrado')->nullable();
            $table->text('observaciones')->nullable();
            $table->timestamps();
        });

        Schema::create('informe_final_actividades', function (Blueprint $table) {
            $table->id();
            $table->foreignId('informe_final_proyecto_id')->constrained('informe_final_proyectos', indexName: 'inf_actividades_informe_fk')->cascadeOnDelete();
            $table->foreignId('actividad_id')->nullable()->constrained('actividades')->nullOnDelete();
            $table->text('actividad_planificada')->nullable();
            $table->text('actividad_realizada')->nullable();
            $table->string('responsable')->nullable();
            $table->date('fecha_inicial')->nullable();
            $table->date('fecha_final')->nullable();
            $table->decimal('horas_dedicadas', 10, 2)->default(0);
            $table->text('medio_verificacion')->nullable();
            $table->enum('estado', ['ejecutada', 'parcial', 'no_ejecutada'])->default('no_ejecutada');
            $table->enum('origen', ['planificada', 'emergente'])->default('planificada');
            $table->timestamps();
        });

        Schema::create('informe_final_acciones_no_ejecutadas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('informe_final_proyecto_id')->constrained('informe_final_proyectos', indexName: 'inf_no_ejecutadas_informe_fk')->cascadeOnDelete();
            $table->text('resultado_previsto')->nullable();
            $table->text('actividad_planificada');
            $table->text('explicacion');
            $table->text('afectacion_proyecto')->nullable();
            $table->string('responsable')->nullable();
            $table->enum('impacto', ['bajo', 'medio', 'alto']);
            $table->timestamps();
        });

        Schema::create('informe_final_acciones_emergentes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('informe_final_proyecto_id')->constrained('informe_final_proyectos', indexName: 'inf_emergentes_informe_fk')->cascadeOnDelete();
            $table->foreignId('informe_final_resultado_id')->nullable()->constrained('informe_final_resultados', indexName: 'inf_emergente_resultado_fk')->nullOnDelete();
            $table->text('producto_logrado')->nullable();
            $table->text('actividad_realizada');
            $table->text('justificacion');
            $table->string('responsables')->nullable();
            $table->date('fecha')->nullable();
            $table->decimal('horas', 10, 2)->default(0);
            $table->timestamps();
        });

        Schema::create('informe_final_ods', function (Blueprint $table) {
            $table->id();
            $table->foreignId('informe_final_proyecto_id')->constrained('informe_final_proyectos', indexName: 'inf_ods_informe_fk')->cascadeOnDelete();
            $table->foreignId('ods_id')->nullable()->constrained('ods')->restrictOnDelete();
            $table->foreignId('meta_contribuye_id')->nullable()->constrained('metas_contribuye')->nullOnDelete();
            $table->text('meta_ods')->nullable();
            $table->text('descripcion_aporte')->nullable();
            $table->text('evidencia')->nullable();
            $table->enum('nivel_contribucion', ['directa', 'indirecta'])->default('directa');
            $table->timestamps();
            $table->unique(['informe_final_proyecto_id', 'ods_id', 'meta_contribuye_id'], 'inf_final_ods_meta_unique');
        });

        Schema::create('informe_final_presupuesto_detalles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('informe_final_proyecto_id')->constrained('informe_final_proyectos', indexName: 'inf_presupuesto_informe_fk')->cascadeOnDelete();
            $table->foreignId('informe_final_contraparte_id')->nullable()->constrained('informe_final_contrapartes', indexName: 'inf_presupuesto_contraparte_fk')->nullOnDelete();
            $table->enum('fuente', ['UNAH', 'CONTRAPARTE']);
            $table->string('concepto');
            $table->string('unidad')->nullable();
            $table->decimal('cantidad', 15, 2)->default(0);
            $table->decimal('costo_unitario', 15, 2)->default(0);
            $table->string('origen_fondos')->nullable();
            $table->timestamps();
            $table->index(['informe_final_proyecto_id', 'fuente'], 'inf_presupuesto_fuente_idx');
        });

        Schema::create('informe_final_anexos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('informe_final_proyecto_id')->constrained('informe_final_proyectos', indexName: 'inf_anexos_informe_fk')->cascadeOnDelete();
            $table->foreignId('informe_final_resultado_id')->nullable()->constrained('informe_final_resultados', indexName: 'inf_anexo_resultado_fk')->nullOnDelete();
            $table->foreignId('informe_final_actividad_id')->nullable()->constrained('informe_final_actividades', indexName: 'inf_anexo_actividad_fk')->nullOnDelete();
            $table->enum('tipo', ['materiales', 'encuestas', 'procesamiento', 'fotografias', 'videos', 'difusion', 'asistencia', 'manuales', 'guias', 'actas', 'otros']);
            $table->text('descripcion')->nullable();
            $table->string('archivo')->nullable();
            $table->text('enlace')->nullable();
            $table->date('fecha')->nullable();
            $table->unsignedInteger('orden')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        foreach (['informe_final_anexos', 'informe_final_presupuesto_detalles', 'informe_final_ods', 'informe_final_acciones_emergentes', 'informe_final_acciones_no_ejecutadas', 'informe_final_actividades', 'informe_final_resultados', 'informe_final_contrapartes', 'informe_final_voluntarios', 'informe_final_estudiantes', 'informe_final_cooperacion', 'informe_final_equipo_docente', 'informe_final_beneficiarios', 'informe_final_proyectos'] as $tabla) {
            Schema::dropIfExists($tabla);
        }
    }
};
