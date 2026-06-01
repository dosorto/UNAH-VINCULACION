<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pps_servicio_social', function (Blueprint $table) {
            $table->id();
            $table->string('codigo_registro')->nullable()->unique();
            $table->string('estado')->default('borrador');

            // Informacion general
            $table->string('facultad_centro');
            $table->string('carrera');

            // Datos del estudiante
            $table->string('numero_cuenta');
            $table->string('nombre_estudiante');
            $table->string('celular_estudiante');
            $table->string('correo_institucional');
            $table->string('correo_personal')->nullable();

            // Informacion PPS / SS
            $table->string('tipo_pps_ss');
            $table->date('fecha_inicio');
            $table->date('fecha_finalizacion');
            $table->string('tipo_instrumento');
            $table->string('territorio_ejecucion');

            // Datos territoriales
            $table->string('departamento')->nullable();
            $table->string('municipio')->nullable();
            $table->string('aldea_ciudad')->nullable();
            $table->string('caserio')->nullable();

            // Alcance
            $table->text('descripcion_tipo_pps')->nullable();
            $table->unsignedInteger('total_horas');
            $table->string('area_realizacion')->nullable();
            $table->text('resumen_responsabilidades')->nullable();
            $table->string('modalidad_ejecucion');

            // Institucion / Organizacion
            $table->string('nombre_institucion');
            $table->text('compromisos_institucion')->nullable();
            $table->text('direccion_institucion')->nullable();
            $table->string('representante_legal')->nullable();
            $table->string('telefono_representante')->nullable();
            $table->string('correo_rrhh')->nullable();
            $table->string('tipo_institucion')->nullable();
            $table->string('sector_institucion')->nullable();

            // Jefe directo
            $table->string('nombre_jefe_directo');
            $table->string('celular_jefe_directo')->nullable();
            $table->string('correo_jefe_directo')->nullable();
            $table->string('cargo_jefe_directo')->nullable();
            $table->string('grado_academico_jefe_directo')->nullable();

            // Docente supervisor
            $table->string('nombre_docente_supervisor');
            $table->string('numero_empleado_docente')->nullable();
            $table->string('celular_docente')->nullable();
            $table->string('correo_docente')->nullable();
            $table->string('categoria_docente')->nullable();
            $table->string('departamento_docente')->nullable();
            $table->string('jornada_laboral_docente')->nullable();
            $table->string('ubicacion_cubiculo_docente')->nullable();

            // Documentos adjuntos
            $table->boolean('adjunta_carta_formalizacion')->default(false);
            $table->string('archivo_carta_formalizacion')->nullable();
            $table->boolean('adjunta_convenio_marco')->default(false);
            $table->string('archivo_convenio_marco')->nullable();

            // Auditoria basica sin forzar relacion hasta definir politica global.
            $table->unsignedBigInteger('created_by')->nullable()->index();
            $table->unsignedBigInteger('updated_by')->nullable()->index();

            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pps_servicio_social');
    }
};
