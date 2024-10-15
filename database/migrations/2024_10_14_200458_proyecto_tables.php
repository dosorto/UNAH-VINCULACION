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
        // tablas catalogo del modulo de proyecto
        // tabla ods
        Schema::create('ods', function (Blueprint $table) {
            $table->id();
            $table->string('nombre')->unique();
            $table->softDeletes();
            $table->timestamps();
        });

        // tabla modalidad
        Schema::create('modalidad', function (Blueprint $table) {
            $table->id();
            $table->string('nombre');
            $table->softDeletes();
            $table->timestamps();
        });

        // tabla categorias
        Schema::create('categorias', function (Blueprint $table) {
            $table->id();
            $table->string('nombre')->unique();
            $table->softDeletes();
            $table->timestamps();
        });


        // tabla proyecto
        Schema::create('proyecto', function (Blueprint $table) {
            $table->id();
            $table->string('nombre_proyecto');
            $table->foreignId('carrera_facultad_centro_id')->contrained('carrera_facultad_centro')->nullable();
            $table->foreignId('entidad_academica_id')->contrained('entidad_academica')->nullable();
            $table->foreignId('coordinador_id')->constrained('empleado');
            $table->foreignId('ods_id')->constrained('ods');
            $table->foreignId('modalidad_id')->constrained('modalidad');
            $table->foreignId('categoria_id')->constrained('categorias');
            $table->foreignId('municipio_id')->constrained('municipio');
            $table->foreignId('departamento_id')->constrained('departamento');
            $table->foreignId('ciudad_id')->constrained('ciudad');
            $table->foreignId('aldea_id')->constrained('aldea');
            $table->string('resumen');
            $table->string('objetivo_general');
            $table->string('objetivos_especificos');
            $table->date('fecha_inicio');
            $table->date('fecha_finalizacion');
            $table->date('evaluacion_intermedia');
            $table->date('evaluacion_final');
            $table->decimal('poblacion_participante', 8, 2);
            $table->enum('modalidad_ejecucion', ['Distancia', 'Presencial', 'Bimodal']);
            $table->string('resultados_esperados');
            $table->string('indicadores_medicion_resultados');
            $table->softDeletes();
            $table->timestamps();
        });

        // tabla entidad_contraparte
        Schema::create('entidad_contraparte', function (Blueprint $table) {
            $table->id();
            $table->foreignId('proyecto_id')->constrained('proyecto');
            $table->string('nombre');
            $table->string('telefono');
            $table->string('correo');
            $table->string('nombre_contacto');
            $table->boolean('es_internacional')->default(false);
            $table->string('aporte');
            $table->string('instrumento_formalizacion');
            $table->softDeletes();
            $table->timestamps();
        });

        // tabla actividades
        Schema::create('actividades', function (Blueprint $table) {
            $table->id();
            $table->foreignId('proyecto_id')->constrained('proyecto');
            $table->foreignId('responsable_id')->constrained('empleado');
            $table->string('descripcion');
            $table->date('fecha_ejecucion');
            $table->softDeletes();
            $table->timestamps();
        });

        // tabla empleado_proyecto
        Schema::create('empleado_proyecto', function (Blueprint $table) {
            $table->id();
            $table->foreignId('empleado_id')->constrained('empleado');
            $table->foreignId('proyecto_id')->constrained('proyecto');
            $table->softDeletes();
            $table->timestamps();
        });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
