<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('pasantias')) {
            return;
        }

        Schema::create('pasantias', function (Blueprint $table): void {
            $table->id();
            $table->string('codigo_registro')->nullable()->unique();
            $table->string('estado')->default('borrador')->index();
            $table->string('proceso')->default('PASANTIAS_DEFAULT')->index();

            // Flujo y auditoría genérica para integrar posteriormente workflow/historial.
            $table->unsignedBigInteger('tipo_accion_id')->nullable()->index();
            $table->unsignedBigInteger('flujo_aprobacion_id')->nullable()->index();
            $table->unsignedBigInteger('etapa_actual_id')->nullable()->index();
            $table->timestamp('fecha_envio')->nullable();
            $table->timestamp('fecha_revision')->nullable();
            $table->unsignedBigInteger('enviado_por')->nullable()->index();
            $table->unsignedBigInteger('revisado_por')->nullable()->index();
            $table->text('motivo_rechazo')->nullable();
            $table->json('destinatarios_emisor')->nullable();

            // Estudiante y unidad académica.
            $table->date('fecha_registro')->nullable();
            $table->string('facultad_centro')->nullable();
            $table->string('escuela_departamento')->nullable();
            $table->string('carrera')->nullable();
            $table->string('numero_cuenta')->nullable()->index();
            $table->string('nombre_estudiante')->nullable();
            $table->string('celular_estudiante')->nullable();
            $table->string('correo_institucional')->nullable();
            $table->string('correo_personal')->nullable();

            // Pasantía, fechas, duración, horas, créditos y modalidad.
            $table->string('tipo_pasantia')->nullable();
            $table->date('fecha_inicio')->nullable()->index();
            $table->date('fecha_finalizacion')->nullable()->index();
            $table->unsignedInteger('duracion_semanas')->nullable();
            $table->unsignedInteger('total_horas')->nullable();
            $table->unsignedInteger('horas_semanales')->nullable();
            $table->boolean('pasantia_obligatoria')->nullable();
            $table->boolean('otorga_creditos')->nullable();
            $table->decimal('cantidad_creditos', 5, 2)->unsigned()->nullable();
            $table->string('modalidad_ejecucion')->nullable();

            // Experiencia, cargo, asignaturas y compensación.
            $table->text('descripcion_experiencia')->nullable();
            $table->text('descripcion_cargo')->nullable();
            $table->text('resumen_responsabilidades')->nullable();
            $table->string('area_departamento')->nullable();
            $table->string('area_conocimiento')->nullable();
            $table->json('asignaturas')->nullable();
            $table->string('codigo_asignatura')->nullable();
            $table->string('nombre_asignatura')->nullable();
            $table->text('descripcion_conocimientos_teoricos')->nullable();
            $table->text('habilidades_desarrollar')->nullable();
            $table->boolean('pasantia_remunerada')->nullable();
            $table->decimal('monto_remuneracion', 12, 2)->nullable();

            // Institución y representante legal.
            $table->string('nombre_institucion')->nullable();
            $table->text('direccion_institucion')->nullable();
            $table->string('ciudad_institucion')->nullable();
            $table->string('pais_institucion')->nullable();
            $table->string('representante_legal')->nullable();
            $table->string('telefono_representante')->nullable();
            $table->string('correo_rrhh')->nullable();
            $table->string('tipo_institucion')->nullable();
            $table->string('sector_institucion')->nullable();
            $table->text('compromisos_institucion')->nullable();

            // Contacto directo de la pasantía.
            $table->string('nombre_contacto_directo')->nullable();
            $table->string('celular_contacto_directo')->nullable();
            $table->string('correo_contacto_directo')->nullable();
            $table->string('cargo_contacto_directo')->nullable();
            $table->string('grado_academico_contacto_directo')->nullable();
            $table->string('tipo_instrumento')->nullable();

            // Docente supervisor y jornada.
            $table->string('nombre_docente_supervisor')->nullable();
            $table->string('numero_empleado_docente')->nullable();
            $table->string('celular_docente')->nullable();
            $table->string('correo_docente')->nullable();
            $table->string('categoria_docente')->nullable();
            $table->string('departamento_docente')->nullable();
            $table->string('jornada_laboral_docente')->nullable();
            $table->string('ubicacion_cubiculo_docente')->nullable();

            // Firmas y documentos adjuntos.
            $table->string('nombre_firma_coordinador')->nullable();
            $table->string('firma_coordinador')->nullable();
            $table->string('nombre_firma_supervisor')->nullable();
            $table->string('firma_supervisor')->nullable();
            $table->string('nombre_firma_estudiante')->nullable();
            $table->string('firma_estudiante')->nullable();
            $table->boolean('adjunta_carta_formalizacion')->nullable();
            $table->string('archivo_carta_formalizacion')->nullable();
            $table->boolean('adjunta_convenio_marco')->nullable();
            $table->string('archivo_convenio_marco')->nullable();

            $table->unsignedBigInteger('created_by')->nullable()->index();
            $table->unsignedBigInteger('updated_by')->nullable()->index();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pasantias');
    }
};
