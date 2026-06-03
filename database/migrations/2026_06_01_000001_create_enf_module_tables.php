<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('enf_acciones', function (Blueprint $table) {
            $table->id();
            $table->string('codigo_formulario', 80)->nullable()->index();
            $table->foreignId('tipo_accion_id')->nullable()->constrained('vinculacion_tipos_accion')->nullOnDelete();
            $table->foreignId('modalidad_id')->nullable()->constrained('modalidad')->nullOnDelete();
            $table->foreignId('centro_facultad_id')->nullable()->constrained('centro_facultad')->nullOnDelete();
            $table->foreignId('departamento_academico_id')->nullable()->constrained('departamento_academico')->nullOnDelete();
            $table->foreignId('carrera_id')->nullable()->constrained('carrera')->nullOnDelete();
            $table->string('nombre_accion', 250);
            $table->unsignedInteger('numero_edicion')->default(1);
            $table->date('fecha_solicitud')->nullable();
            $table->date('fecha_inicio')->nullable();
            $table->date('fecha_finalizacion')->nullable();
            $table->string('resolucion_vra', 120)->nullable();
            $table->string('resolucion_original', 120)->nullable();
            $table->string('resolucion_actualizacion', 120)->nullable();
            $table->unsignedInteger('horas_teoricas')->default(0);
            $table->unsignedInteger('horas_practicas')->default(0);
            $table->unsignedInteger('total_horas')->default(0);
            $table->text('resumen')->nullable();
            $table->text('descripcion_participantes')->nullable();
            $table->text('definicion_problema')->nullable();
            $table->text('objetivo_general')->nullable();
            $table->text('alineamiento_reforma')->nullable();
            $table->text('metodologia')->nullable();
            $table->text('logistica')->nullable();
            $table->text('bibliografia')->nullable();
            $table->boolean('genera_ingresos')->default(false);
            $table->text('mecanismo_administracion')->nullable();
            $table->text('descripcion_excedente')->nullable();
            $table->string('estado_flujo', 40)->default('BORRADOR')->index();
            $table->unsignedInteger('revision_ciclo')->default(0);
            $table->foreignId('responsable_revision_id')->nullable()->constrained('empleado')->nullOnDelete();
            $table->foreignId('creado_por_usuario_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('modificado_por_usuario_id')->nullable()->constrained('users')->nullOnDelete();
            $table->date('fecha_aprobacion')->nullable();
            $table->date('fecha_registro')->nullable();
            $table->string('numero_libro', 80)->nullable();
            $table->string('numero_tomo', 80)->nullable();
            $table->string('numero_folio', 80)->nullable();
            $table->string('numero_registro', 100)->nullable()->index();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('enf_catalogos', function (Blueprint $table) {
            $table->id();
            $table->string('tipo', 80)->index();
            $table->string('codigo', 120);
            $table->string('nombre', 180);
            $table->text('descripcion')->nullable();
            $table->boolean('activo')->default(true);
            $table->unsignedInteger('orden')->default(0);
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['tipo', 'codigo'], 'uq_enf_catalogos_tipo_codigo');
        });

        Schema::create('enf_lugares_ejecucion', function (Blueprint $table) {
            $table->id();
            $table->foreignId('enf_accion_id')->constrained('enf_acciones')->cascadeOnDelete();
            $table->foreignId('campus_id')->nullable()->constrained('campus')->nullOnDelete();
            $table->foreignId('departamento_id')->nullable()->constrained('departamento')->nullOnDelete();
            $table->foreignId('municipio_id')->nullable()->constrained('municipio')->nullOnDelete();
            $table->string('nombre_lugar', 180)->nullable();
            $table->string('aldea_caserio', 180)->nullable();
            $table->text('direccion')->nullable();
            $table->string('modalidad_ejecucion', 60)->nullable();
            $table->string('plataforma', 120)->nullable();
            $table->string('url_acceso', 500)->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('enf_accion_catalogo', function (Blueprint $table) {
            $table->id();
            $table->foreignId('enf_accion_id')->constrained('enf_acciones')->cascadeOnDelete();
            $table->foreignId('enf_catalogo_id')->nullable()->constrained('enf_catalogos')->nullOnDelete();
            $table->string('tipo', 80)->index();
            $table->string('valor_texto', 250)->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('enf_beneficiarios', function (Blueprint $table) {
            $table->id();
            $table->foreignId('enf_accion_id')->constrained('enf_acciones')->cascadeOnDelete();
            $table->unsignedInteger('hombres')->default(0);
            $table->unsignedInteger('mujeres')->default(0);
            $table->unsignedInteger('otros')->default(0);
            $table->unsignedInteger('total')->default(0);
            $table->unsignedInteger('estudiantes_unah')->default(0);
            $table->unsignedInteger('docentes_unah')->default(0);
            $table->unsignedInteger('administrativos_unah')->default(0);
            $table->unsignedInteger('externos')->default(0);
            $table->text('descripcion')->nullable();
            $table->json('distribucion')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique('enf_accion_id', 'uq_enf_beneficiarios_accion');
        });

        Schema::create('enf_equipo', function (Blueprint $table) {
            $table->id();
            $table->foreignId('enf_accion_id')->constrained('enf_acciones')->cascadeOnDelete();
            $table->foreignId('empleado_id')->nullable()->constrained('empleado')->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('integrante_internacional_id')->nullable()->constrained('integrante_internacional')->nullOnDelete();
            $table->string('nombre_completo', 220)->nullable();
            $table->string('rol', 120)->nullable();
            $table->text('responsabilidades')->nullable();
            $table->boolean('es_coordinador')->default(false);
            $table->unsignedInteger('horas_dedicadas')->default(0);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('enf_participacion_universitaria', function (Blueprint $table) {
            $table->id();
            $table->foreignId('enf_accion_id')->constrained('enf_acciones')->cascadeOnDelete();
            $table->foreignId('estudiante_id')->nullable()->constrained('estudiante')->nullOnDelete();
            $table->foreignId('empleado_id')->nullable()->constrained('empleado')->nullOnDelete();
            $table->foreignId('centro_facultad_id')->nullable()->constrained('centro_facultad')->nullOnDelete();
            $table->foreignId('carrera_id')->nullable()->constrained('carrera')->nullOnDelete();
            $table->string('tipo_participacion', 120)->nullable();
            $table->unsignedInteger('cantidad')->default(0);
            $table->text('descripcion')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('enf_practicas_asignatura', function (Blueprint $table) {
            $table->id();
            $table->foreignId('enf_accion_id')->constrained('enf_acciones')->cascadeOnDelete();
            $table->foreignId('asignatura_id')->nullable()->constrained('asignaturas')->nullOnDelete();
            $table->foreignId('periodo_academico_id')->nullable()->constrained('periodos_academicos')->nullOnDelete();
            $table->foreignId('carrera_id')->nullable()->constrained('carrera')->nullOnDelete();
            $table->unsignedInteger('cantidad_estudiantes')->default(0);
            $table->unsignedInteger('cantidad_docentes')->default(0);
            $table->text('descripcion_practica')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('enf_contrapartes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('enf_accion_id')->constrained('enf_acciones')->cascadeOnDelete();
            $table->foreignId('tipo_contraparte_id')->nullable()->constrained('enf_catalogos')->nullOnDelete();
            $table->foreignId('instrumento_alianza_id')->nullable()->constrained('enf_catalogos')->nullOnDelete();
            $table->string('nombre', 220);
            $table->string('representante', 180)->nullable();
            $table->string('telefono', 80)->nullable();
            $table->string('correo', 180)->nullable();
            $table->decimal('aporte_monetario', 14, 2)->default(0);
            $table->decimal('aporte_especie', 14, 2)->default(0);
            $table->text('compromisos')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('enf_objetivos_especificos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('enf_accion_id')->constrained('enf_acciones')->cascadeOnDelete();
            $table->unsignedInteger('orden')->default(0);
            $table->text('descripcion');
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('enf_resultados', function (Blueprint $table) {
            $table->id();
            $table->foreignId('enf_accion_id')->constrained('enf_acciones')->cascadeOnDelete();
            $table->foreignId('enf_objetivo_especifico_id')->nullable()->constrained('enf_objetivos_especificos')->nullOnDelete();
            $table->unsignedInteger('orden')->default(0);
            $table->text('resultado');
            $table->text('indicador')->nullable();
            $table->text('medio_verificacion')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('enf_accion_ods', function (Blueprint $table) {
            $table->id();
            $table->foreignId('enf_accion_id')->constrained('enf_acciones')->cascadeOnDelete();
            $table->foreignId('ods_id')->nullable()->constrained('ods')->nullOnDelete();
            $table->foreignId('meta_contribuye_id')->nullable()->constrained('metas_contribuye')->nullOnDelete();
            $table->text('contribucion')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('enf_accion_ejes_unah', function (Blueprint $table) {
            $table->id();
            $table->foreignId('enf_accion_id')->constrained('enf_acciones')->cascadeOnDelete();
            $table->foreignId('eje_prioritario_unah_id')->nullable()->constrained('ejes_prioritarios_unah')->nullOnDelete();
            $table->text('contribucion')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('enf_presupuestos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('enf_accion_id')->constrained('enf_acciones')->cascadeOnDelete();
            $table->string('tipo', 80)->default('general');
            $table->string('fuente_financiamiento', 180)->nullable();
            $table->decimal('monto_solicitado', 14, 2)->default(0);
            $table->decimal('monto_aprobado', 14, 2)->default(0);
            $table->decimal('monto_ejecutado', 14, 2)->default(0);
            $table->text('observaciones')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('enf_presupuesto_detalles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('enf_presupuesto_id')->constrained('enf_presupuestos')->cascadeOnDelete();
            $table->string('rubro', 180);
            $table->text('descripcion')->nullable();
            $table->decimal('cantidad', 12, 2)->default(0);
            $table->decimal('costo_unitario', 14, 2)->default(0);
            $table->decimal('total', 14, 2)->default(0);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('enf_cronograma', function (Blueprint $table) {
            $table->id();
            $table->foreignId('enf_accion_id')->constrained('enf_acciones')->cascadeOnDelete();
            $table->string('actividad', 250);
            $table->text('descripcion')->nullable();
            $table->date('fecha_inicio')->nullable();
            $table->date('fecha_finalizacion')->nullable();
            $table->foreignId('responsable_empleado_id')->nullable()->constrained('empleado')->nullOnDelete();
            $table->unsignedInteger('porcentaje_avance')->default(0);
            $table->string('estado', 60)->default('pendiente');
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('enf_certificados', function (Blueprint $table) {
            $table->id();
            $table->foreignId('enf_accion_id')->constrained('enf_acciones')->cascadeOnDelete();
            $table->foreignId('tipo_certificado_id')->nullable()->constrained('enf_catalogos')->nullOnDelete();
            $table->string('nombre_certificado', 220);
            $table->foreignId('figura_acreditacion_id')->nullable()->constrained('enf_catalogos')->nullOnDelete();
            $table->unsignedInteger('horas_certificadas')->default(0);
            $table->text('requisitos_emision')->nullable();
            $table->string('plantilla_path', 500)->nullable();
            $table->boolean('activo')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->unique('enf_accion_id', 'uq_enf_certificados_accion');
        });

        Schema::create('enf_certificado_carreras', function (Blueprint $table) {
            $table->id();
            $table->foreignId('enf_certificado_id')->constrained('enf_certificados')->cascadeOnDelete();
            $table->foreignId('carrera_id')->nullable()->constrained('carrera')->nullOnDelete();
            $table->foreignId('centro_facultad_id')->nullable()->constrained('centro_facultad')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('enf_espacios_aprendizaje', function (Blueprint $table) {
            $table->id();
            $table->foreignId('enf_accion_id')->constrained('enf_acciones')->cascadeOnDelete();
            $table->foreignId('plataforma_id')->nullable()->constrained('enf_catalogos')->nullOnDelete();
            $table->string('nombre', 220);
            $table->string('url', 500)->nullable();
            $table->text('descripcion')->nullable();
            $table->boolean('activo')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('enf_informes_finales', function (Blueprint $table) {
            $table->id();
            $table->foreignId('enf_accion_id')->constrained('enf_acciones')->cascadeOnDelete();
            $table->date('fecha_presentacion')->nullable();
            $table->text('resumen_ejecutivo')->nullable();
            $table->text('resultados_obtenidos')->nullable();
            $table->text('limitaciones')->nullable();
            $table->text('conclusiones')->nullable();
            $table->text('recomendaciones')->nullable();
            $table->foreignId('aprobado_por_empleado_id')->nullable()->constrained('empleado')->nullOnDelete();
            $table->date('fecha_aprobacion')->nullable();
            $table->string('estado', 60)->default('borrador');
            $table->timestamps();
            $table->softDeletes();

            $table->unique('enf_accion_id', 'uq_enf_informes_finales_accion');
        });

        Schema::create('enf_participantes_finales', function (Blueprint $table) {
            $table->id();
            $table->foreignId('enf_informe_final_id')->constrained('enf_informes_finales')->cascadeOnDelete();
            $table->foreignId('estudiante_id')->nullable()->constrained('estudiante')->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('nombre_completo', 220);
            $table->string('documento_identidad', 120)->nullable();
            $table->string('correo', 180)->nullable();
            $table->string('sexo', 20)->nullable();
            $table->unsignedInteger('edad')->default(0);
            $table->boolean('certificado_emitido')->default(false);
            $table->string('codigo_certificado', 120)->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('enf_acciones_ejecutadas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('enf_informe_final_id')->constrained('enf_informes_finales')->cascadeOnDelete();
            $table->string('actividad', 250);
            $table->date('fecha_inicio')->nullable();
            $table->date('fecha_finalizacion')->nullable();
            $table->text('resultados')->nullable();
            $table->text('observaciones')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('enf_acciones_no_ejecutadas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('enf_informe_final_id')->constrained('enf_informes_finales')->cascadeOnDelete();
            $table->string('actividad', 250);
            $table->text('motivo')->nullable();
            $table->text('acciones_correctivas')->nullable();
            $table->date('fecha_reprogramacion')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('enf_sistematizaciones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('enf_accion_id')->constrained('enf_acciones')->cascadeOnDelete();
            $table->foreignId('enf_informe_final_id')->nullable()->constrained('enf_informes_finales')->nullOnDelete();
            $table->text('antecedentes')->nullable();
            $table->text('descripcion_experiencia')->nullable();
            $table->text('metodologia_sistematizacion')->nullable();
            $table->text('lecciones_aprendidas')->nullable();
            $table->text('buenas_practicas')->nullable();
            $table->text('recomendaciones')->nullable();
            $table->string('estado', 60)->default('borrador');
            $table->timestamps();
            $table->softDeletes();

            $table->unique('enf_accion_id', 'uq_enf_sistematizaciones_accion');
        });

        Schema::create('enf_sistematizacion_documentos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('enf_sistematizacion_id')->constrained('enf_sistematizaciones')->cascadeOnDelete();
            $table->string('tipo_documento', 120)->nullable();
            $table->string('nombre', 220);
            $table->string('ruta', 500);
            $table->text('descripcion')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('enf_sistematizacion_fases', function (Blueprint $table) {
            $table->id();
            $table->foreignId('enf_sistematizacion_id')->constrained('enf_sistematizaciones')->cascadeOnDelete();
            $table->unsignedInteger('orden')->default(0);
            $table->string('fase', 180);
            $table->text('descripcion')->nullable();
            $table->date('fecha_inicio')->nullable();
            $table->date('fecha_finalizacion')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('enf_documentos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('enf_accion_id')->constrained('enf_acciones')->cascadeOnDelete();
            $table->foreignId('subido_por_usuario_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('tipo_documento', 120)->nullable();
            $table->string('nombre', 220);
            $table->string('ruta', 500);
            $table->string('mime_type', 120)->nullable();
            $table->unsignedBigInteger('tamano_bytes')->default(0);
            $table->text('descripcion')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('enf_firmas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('enf_accion_id')->constrained('enf_acciones')->cascadeOnDelete();
            $table->foreignId('enf_documento_id')->nullable()->constrained('enf_documentos')->nullOnDelete();
            $table->foreignId('empleado_id')->nullable()->constrained('empleado')->nullOnDelete();
            $table->foreignId('firma_sello_empleado_id')->nullable()->constrained('firma_sello_empleado')->nullOnDelete();
            $table->foreignId('cargo_firma_id')->nullable()->constrained('cargo_firma')->nullOnDelete();
            $table->foreignId('tipo_estado_id')->nullable()->constrained('tipo_estado')->nullOnDelete();
            $table->string('rol_firma', 150)->nullable();
            $table->string('estado_revision', 60)->default('Pendiente');
            $table->timestamp('fecha_firma')->nullable();
            $table->string('hash', 255)->nullable();
            $table->text('observaciones')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('enf_firmas');
        Schema::dropIfExists('enf_documentos');
        Schema::dropIfExists('enf_sistematizacion_fases');
        Schema::dropIfExists('enf_sistematizacion_documentos');
        Schema::dropIfExists('enf_sistematizaciones');
        Schema::dropIfExists('enf_acciones_no_ejecutadas');
        Schema::dropIfExists('enf_acciones_ejecutadas');
        Schema::dropIfExists('enf_participantes_finales');
        Schema::dropIfExists('enf_informes_finales');
        Schema::dropIfExists('enf_espacios_aprendizaje');
        Schema::dropIfExists('enf_certificado_carreras');
        Schema::dropIfExists('enf_certificados');
        Schema::dropIfExists('enf_cronograma');
        Schema::dropIfExists('enf_presupuesto_detalles');
        Schema::dropIfExists('enf_presupuestos');
        Schema::dropIfExists('enf_accion_ejes_unah');
        Schema::dropIfExists('enf_accion_ods');
        Schema::dropIfExists('enf_resultados');
        Schema::dropIfExists('enf_objetivos_especificos');
        Schema::dropIfExists('enf_contrapartes');
        Schema::dropIfExists('enf_practicas_asignatura');
        Schema::dropIfExists('enf_participacion_universitaria');
        Schema::dropIfExists('enf_equipo');
        Schema::dropIfExists('enf_beneficiarios');
        Schema::dropIfExists('enf_accion_catalogo');
        Schema::dropIfExists('enf_lugares_ejecucion');
        Schema::dropIfExists('enf_catalogos');
        Schema::dropIfExists('enf_acciones');
    }
};
