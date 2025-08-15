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
        // tablas del modulo de unidad academica


        // tablas de campus
        Schema::create('campus', function (Blueprint $table) {
            $table->id();
            $table->string('nombre_campus');
            $table->string('siglas')->nullable();
            $table->string('direccion');
            $table->string('telefono');
            $table->string('url');
            $table->softDeletes();
            $table->timestamps();
        });

        // tablas de centro / facultad
        Schema::create('centro_facultad', function (Blueprint $table) {
            $table->id();
            $table->string('nombre');
            $table->boolean('es_facultad');
            $table->string('siglas');
            $table->foreignId('campus_id')->constrained('campus');
            $table->softDeletes();
            $table->timestamps();
        });

        // administradores de centro / facultad
        Schema::create('administrador_centro_facultad', function (Blueprint $table) {
            $table->id();
            $table->foreignId('centro_facultad_id')->constrained('centro_facultad');
            $table->foreignId('user_id')->constrained('users');
            $table->boolean('es_director');
            $table->boolean('estado');
            $table->softDeletes();
            $table->timestamps();
        });

        // tablas de departamento
        Schema::create('departamento_academico', function (Blueprint $table) {
            $table->id();
            $table->foreignId('centro_facultad_id')->constrained('centro_facultad');
            $table->string('nombre');
            $table->string('siglas')->nullable();
            $table->softDeletes();
            $table->timestamps();
        });


        // tabla de carreras
        Schema::create('carrera', function (Blueprint $table) {
            $table->id();
            $table->string('nombre');
            $table->string('siglas')->nullable();
            $table->foreignId('facultad_centro_id')->constrained('centro_facultad');
            $table->softDeletes();
            $table->timestamps();
        });

       
        // tabla de carrera_facultad_centro
        Schema::create('carrera_facultad_centro', function (Blueprint $table) {
            $table->id();
            $table->foreignId('carrera_id')->constrained('carrera');
            $table->foreignId('facultad_centro_id')->constrained('centro_facultad');
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
