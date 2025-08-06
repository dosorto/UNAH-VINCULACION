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
        // tabla tipo_Estado está en migraciones de proyecto
        // estado del servicio tecnologico por empleado usando fecha
        Schema::create('estado_servicio_tecnologico', function (Blueprint $table) {
            $table->id();
            
            $table->foreignId('empleado_id')->constrained('empleado');
            $table->foreignId('tipo_estado_id')->constrained('tipo_estado');
            $table->date('fecha');
            $table->text('comentario')->nullable();
            $table->boolean('es_actual')->default(true);

            $table->morphs('estadoable');
            $table->softDeletes();
            $table->timestamps();
        });

        Schema::create('servicios_tecnologicos', function (Blueprint $table) {
             $table->id();
            
            // I. INFORMACIÓN GENERAL
            $table->string('nombre_accion');
            $table->foreignId('modalidad_id')->nullable()->constrained('modalidad');
            $table->longText('aldea')->nullable();
            $table->integer('hombres')->nullable();
            $table->integer('mujeres')->nullable();
            $table->integer('otros')->nullable();
            $table->integer('indigenas_hombres')->default(0);
            $table->integer('indigenas_mujeres')->default(0);
            $table->integer('afroamericanos_hombres')->default(0);
            $table->integer('afroamericanos_mujeres')->default(0);
            $table->integer('mestizos_hombres')->default(0);
            $table->integer('mestizos_mujeres')->default(0);
            $table->longText('descripción_servicio')->nullable();
            $table->longText('descripcion_problema')->nullable();
            $table->longText('descripcion_participante')->nullable();
            $table->longText('objetivo_general')->nullable();
            $table->longText('objetivo_especifico')->nullable();
            $table->longText('resultados_esperados')->nullable();
            $table->longText('indicadores_resultados')->nullable();
            $table->longText('descripción_ser_infraestructura')->nullable();
            $table->longText('ubicacion')->nullable();
            $table->longText('unidad_gestora')->nullable();
            // Fechas de ejecución
            $table->date('fecha_inicio');
            $table->date('fecha_finalizacion');
            $table->timestamps();
        });

        // tabla empleado_servicio_tecnologico
        Schema::create('empleado_servicio', function (Blueprint $table) {
            $table->id();
            $table->foreignId('empleado_id')->constrained('empleado');
            $table->foreignId('servicio_tecnologico_id')->constrained('servicios_tecnologicos');
            $table->enum('rol', ['Coordinador', 'Subcoordinador', 'Integrante']);
            // hash
            $table->string('hash')->nullable();

            $table->softDeletes();
            $table->timestamps();
        });

         // tabla de realcion con entidad_facultad_centro
        Schema::create('servicio_centro_facultad', function (Blueprint $table) {
            $table->id();
            $table->foreignId('servicio_tecnologico_id')->constrained('servicios_tecnologicos');
            $table->foreignId('centro_facultad_id')->constrained('centro_facultad');
            $table->softDeletes();
            $table->timestamps();
        });

        // tabla de realcion con entidad_carrera
        Schema::create('servicio_depto_ac', function (Blueprint $table) {
            $table->id();
            $table->foreignId('servicio_tecnologico_id')->constrained('servicios_tecnologicos');
            $table->foreignId('departamento_academico_id')->constrained('departamento_academico');
            $table->softDeletes();
            $table->timestamps();
        });

        Schema::create('servicio_departamento', function (Blueprint $table) {
            $table->id();
            $table->foreignId('servicio_tecnologico_id')->constrained('servicios_tecnologicos');
            $table->foreignId('departamento_id')->constrained('departamento');
            $table->softDeletes();
            $table->timestamps();
        });

        Schema::create('servicio_municipio', function (Blueprint $table) {
            $table->id();
            $table->foreignId('servicio_tecnologico_id')->constrained('servicios_tecnologicos');
            $table->foreignId('municipio_id')->constrained('municipio');
            $table->softDeletes();
            $table->timestamps();
        });

        // tabla estudiante_servicio_tecnologico
        Schema::create('estudiante_servicio', function (Blueprint $table) {
            $table->id();
            $table->foreignId('estudiante_id')->constrained('estudiante');
            $table->foreignId('servicio_tecnologico_id')->constrained('servicios_tecnologicos');
            $table->enum('tipo_participacion_estudiante', [
                'Servicio Social o PPS',
                'Horas artículo 140 Normas Académicas',
                'Práctica de asignatura',
            ]);
            $table->softDeletes();
            $table->timestamps();
        });

          // tabla actividades
         Schema::create('actividades_servicio', function (Blueprint $table) {
            $table->id();
            $table->foreignId('servicio_tecnologico_id')->constrained('servicios_tecnologicos');
            
            $table->longText('descripcion');
            $table->date('fecha_inicio');
            $table->date('fecha_finalizacion');
            // $table->foreignId('empleado_proyecto_id')->constrained('empleado_proyecto');
            // incluir el id del proyecto para recuperarlo mmas rapido
            $table->longText('objetivos')->nullable();
            $table->longText('resultados')->nullable();
            $table->integer('horas')->nullable();
            $table->softDeletes();
            $table->timestamps();
        });

        Schema::create('acti_empleado_srvc', function (Blueprint $table) {
            $table->id();
            $table->foreignId('actividad_id')->constrained('actividades')->onDelete('cascade');
            $table->foreignId('empleado_id')->constrained('empleado')->onDelete('cascade');
            $table->timestamps();
        });
    
        // tabla de cargos de firma 
        Schema::create('cargo_servicio', function (Blueprint $table) {
            $table->id();
            $table->string('descripcion')->nullable();
            $table->foreignId('tipo_cargo_firma_id')->nullable()->constrained('tipo_cargo_firma');
            $table->foreignId('tipo_estado_id')->nullable()->constrained('tipo_estado');
            $table->foreignId('estado_siguiente_id')->nullable()->constrained('tipo_estado');
            $table->softDeletes();
            $table->timestamps();
        });

        // tabla de firmas de proyecto
        Schema::create('firma_servicio', function (Blueprint $table) {
            $table->id();
            $table->foreignId('empleado_id')->constrained('empleado');
            $table->foreignId('cargo_firma_id')->constrained('cargo_firma');
            
            $table->foreignId('firma_id')->nullable();//->constrained('firma');
            $table->foreignId('sello_id')->nullable();
            $table->dateTime('fecha_firma')->nullable();
            $table->enum('estado_revision', ['Pendiente', 'Rechazado', 'Aprobado'])->default('Pendiente');
            $table->string('hash')->nullable();

            $table->morphs('firmable');
            $table->softDeletes();
            $table->timestamps();
        });

        // Tabla para objetivos específicos de servicio tecnológico
        Schema::create('objetivo_especifico_S', function (Blueprint $table) {
            $table->id();
            $table->foreignId('servicio_tecnologico_id')->constrained('servicios_tecnologicos')->onDelete('cascade');
            $table->longText('descripcion');
            $table->integer('orden')->default(1);
            $table->softDeletes();
            $table->timestamps();
        });

        // Tabla para resultados esperados de servicio tecnológico
        Schema::create('resultado_esperado_S', function (Blueprint $table) {
            $table->id();
            $table->foreignId('objetivo_especifico_id')->constrained('objetivo_especifico_S')->onDelete('cascade');
            $table->longText('descripcion');
            $table->integer('orden')->default(1);
            $table->softDeletes();
            $table->timestamps();
        });

        // Tabla para indicadores de resultados de servicio tecnológico
        Schema::create('indicador_resultado_S', function (Blueprint $table) {
            $table->id();
            $table->foreignId('objetivo_especifico_id')->constrained('objetivo_especifico_S')->onDelete('cascade');
            $table->string('nombre');
            $table->longText('descripcion');
            $table->string('meta');
            $table->integer('orden')->default(1);
            $table->softDeletes();
            $table->timestamps();
        });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
{
    Schema::dropIfExists('indicador_resultado_S');
    Schema::dropIfExists('resultado_esperado_S');
    Schema::dropIfExists('objetivo_especifico_S');
    Schema::dropIfExists('firma_servicio');
    Schema::dropIfExists('cargo_servicio');
    Schema::dropIfExists('acti_empleado_srvc');
    Schema::dropIfExists('actividades_servicio');
    Schema::dropIfExists('estudiante_servicio');
    Schema::dropIfExists('servicio_municipio');
    Schema::dropIfExists('servicio_departamento');
    Schema::dropIfExists('servicio_depto_ac');
    Schema::dropIfExists('servicio_centro_facultad');
    Schema::dropIfExists('empleado_servicio');
    Schema::dropIfExists('estado_servicio_tecnologico');
    Schema::dropIfExists('servicios_tecnologicos');
}
};
