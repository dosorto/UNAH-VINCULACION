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
            $table->string('nombre_proyecto')->nullable();
            // $table->foreignId('coordinador_id')->constrained('empleado');
            $table->foreignId('modalidad_id')->nullable()->constrained('modalidad');
            // $table->foreignId('municipio_id')->nullable()->constrained('municipio');
            // $table->foreignId('departamento_id')->nullable()->constrained('departamento');
            $table->foreignId('ciudad_id')->nullable()->constrained('ciudad');
            $table->string('aldea')->nullable();
            $table->string('resumen')->nullable();
            $table->string('objetivo_general')->nullable();
            $table->string('objetivos_especificos')->nullable();
            $table->date('fecha_inicio')->nullable();
            $table->date('fecha_finalizacion')->nullable();
            $table->date('evaluacion_intermedia')->nullable();
            $table->date('evaluacion_final')->nullable();
            $table->decimal('poblacion_participante', 8, 2)->nullable();
            $table->enum('modalidad_ejecucion', ['Distancia', 'Presencial', 'Bimodal'])->nullable();
            $table->string('resultados_esperados')->nullable();
            $table->string('indicadores_medicion_resultados')->nullable();
            $table->date('fecha_registro')->nullable();

            $table->foreignId('responsable_revision_id')->nullable()->constrained('empleado');
            $table->date('fecha_aprobacion')->nullable();
            $table->string('numero_libro')->nullable();
            $table->string('numero_tomo')->nullable();
            $table->string('numero_folio')->nullable();
            $table->string('numero_dictamen')->nullable();
            $table->softDeletes();
            $table->timestamps();
        });

        Schema::create('anexo', function (Blueprint $table) {
            $table->id();
            $table->foreignId('proyecto_id')->constrained('proyecto');
            $table->string('documento_url');
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
            // $table->string('instrumento_formalizacion');
            $table->softDeletes();
            $table->timestamps();
        });

        // tabla instrumento_formalizacion
        Schema::create('instrumento_formalizacion', function (Blueprint $table) {
            $table->id();
            $table->foreignId('entidad_contraparte_id')->constrained('entidad_contraparte');
            $table->string('documento_url');
            $table->softDeletes();
            $table->timestamps();
        });
       

        // tabla empleado_proyecto
        Schema::create('empleado_proyecto', function (Blueprint $table) {
            $table->id();
            $table->foreignId('empleado_id')->constrained('empleado');
            $table->foreignId('proyecto_id')->constrained('proyecto');
            $table->enum('rol', ['Coordinador', 'Subcoordinador', 'Integrante']);
            $table->softDeletes();
            $table->timestamps();
        });

         // tabla actividades
         Schema::create('actividades', function (Blueprint $table) {
            $table->id();
            $table->string('descripcion');
            $table->date('fecha_inicio');
            $table->date('fecha_finalizacion');
            // $table->foreignId('empleado_proyecto_id')->constrained('empleado_proyecto');
            // incluir el id del proyecto para recuperarlo mmas rapido
            $table->foreignId('proyecto_id')->constrained('proyecto');
            $table->text('objetivos')->nullable();
            $table->text('resultados')->nullable();
            $table->integer('horas')->nullable();
            $table->softDeletes();
            $table->timestamps();
        });

        Schema::create('actividad_empleado', function (Blueprint $table) {
            $table->id();
            $table->foreignId('actividad_id')->constrained('actividades')->onDelete('cascade');
            $table->foreignId('empleado_id')->constrained('empleado')->onDelete('cascade');
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

        Schema::create('proyecto_departamento', function (Blueprint $table) {
            $table->id();
            $table->foreignId('proyecto_id')->constrained('proyecto');
            $table->foreignId('departamento_id')->constrained('departamento');
            $table->softDeletes();
            $table->timestamps();
        });

        Schema::create('proyecto_municipio', function (Blueprint $table) {
            $table->id();
            $table->foreignId('proyecto_id')->constrained('proyecto');
            $table->foreignId('municipio_id')->constrained('municipio');
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
            $table->integer('cargo_firma_anterior_id')->nullable();
            $table->foreignId('estado_proyecto_id')->nullable();//->constrained('estado_proyecto');
            $table->foreignId('estado_actual_id')->nullable();//->constrained('estado_proyecto');
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
            $table->string('sello_id')->nullable();
            $table->enum('estado_revision', ['Pendiente', 'Rechazado', 'Aprobado'])->default('Pendiente');
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
