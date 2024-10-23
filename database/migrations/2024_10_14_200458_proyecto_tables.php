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
            $table->foreignId('coordinador_id')->constrained('empleado');
            $table->foreignId('modalidad_id')->constrained('modalidad');
            $table->foreignId('municipio_id')->nullable()->constrained('municipio');
            $table->foreignId('departamento_id')->nullable()->constrained('departamento');
            $table->foreignId('ciudad_id')->nullable()->constrained('ciudad');
            $table->string('aldea')->nullable();
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
            $table->date('fecha_registro');

            $table->foreignId('responsable_revision_id')->nullable()->constrained('empleado');
            $table->date('fecha_aprobacion')->nullable();
            $table->string('numero_libro')->nullable();
            $table->string('numero_tomo')->nullable();
            $table->string('numero_folio')->nullable();
            $table->string('numero_dictamen')->nullable();
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

       

        // tabla empleado_proyecto
        Schema::create('empleado_proyecto', function (Blueprint $table) {
            $table->id();
            $table->foreignId('empleado_id')->constrained('empleado');
            $table->foreignId('proyecto_id')->constrained('proyecto');
            $table->softDeletes();
            $table->timestamps();
        });

         // tabla actividades
         Schema::create('actividades', function (Blueprint $table) {
            $table->id();
            $table->string('descripcion');
            $table->date('fecha_ejecucion');
            $table->foreignId('empleado_proyecto_id')->constrained('empleado_proyecto');
            $table->softDeletes();
            $table->timestamps();
        });


        // tabla de realcion con entidad_facultad_centro
        Schema::create('proyecto_centro_facultad', function (Blueprint $table) {
            $table->id();
            $table->foreignId('proyecto_id')->constrained('proyecto');
            $table->foreignId('centro_facultad_id')->constrained('centro_facultad');
            $table->softDeletes();
            $table->timestamps();
        });

        // tabla de realcion con entidad_carrera
        Schema::create('proyecto_depto_ac', function (Blueprint $table) {
            $table->id();
            $table->foreignId('proyecto_id')->constrained('proyecto');
            $table->foreignId('departamento_academico_id')->constrained('departamento_academico');
            $table->softDeletes();
            $table->timestamps();
        });

        // tabla de superavit_proyecto
        Schema::create('superavit_proyecto', function (Blueprint $table) {
            $table->id();
            $table->foreignId('proyecto_id')->constrained('proyecto');
            $table->string('inversion');
            $table->string('monto');
            $table->softDeletes();
            $table->timestamps();
        });

        // tabla de relacion de muchos a muchos con categoria
        Schema::create('proyecto_categoria', function (Blueprint $table) {
            $table->id();
            $table->foreignId('proyecto_id')->constrained('proyecto');
            $table->foreignId('categoria_id')->constrained('categorias');
            $table->softDeletes();
            $table->timestamps();
        });

        // tabla de relacion de muchos a muchos con ods
        Schema::create('proyecto_ods', function (Blueprint $table) {
            $table->id();
            $table->foreignId('proyecto_id')->constrained('proyecto');
            $table->foreignId('ods_id')->constrained('ods');
            $table->softDeletes();
            $table->timestamps();
        });

        // tabla de cargos de firma 
        Schema::create('cargo_firma', function (Blueprint $table) {
            $table->id();
            $table->string('nombre');
            $table->softDeletes();
            $table->timestamps();
        });

        // tabla de firmas de proyecto
        Schema::create('firma_proyecto', function (Blueprint $table) {
            $table->id();
            $table->foreignId('proyecto_id')->constrained('proyecto');
            $table->foreignId('empleado_id')->constrained('empleado');
            $table->foreignId('cargo_firma_id')->constrained('cargo_firma');
            $table->foreignId('firma_id')->nullable();//->constrained('firma');
            $table->string('estado_revision');
            $table->string('hash')->nullable();
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
