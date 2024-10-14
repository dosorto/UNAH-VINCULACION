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

        Schema::create('facultad_centro', function (Blueprint $table) {
            $table->id();
            $table->string('nombre');
            $table->softDeletes();
            $table->timestamps();
        });

        Schema::create('carrera', function (Blueprint $table) {
            $table->id();
            $table->string('nombre');
            $table->foreignId('facultad_centro_id')->constrained('facultad_centro');
            $table->softDeletes();
            $table->timestamps();
        });

        Schema::create('carrera_facultad_centro', function (Blueprint $table) {
            $table->id();
            $table->foreignId('carrera_id')->constrained('carrera');
            $table->foreignId('facultad_centro_id')->constrained('facultad_centro');
            $table->softDeletes();
            $table->timestamps();
        });

        // tabla tipo_entidad_academica
        Schema::create('tipo_entidad_academica', function (Blueprint $table) {
            $table->id();
            $table->string('nombre')->unique();
            $table->softDeletes();
            $table->timestamps();
        });

        // relacion polimorfica entre el tipo de entidad academica y los posibles carreras o facultad_centro
        Schema::create('entidad_academica', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tipo_entidad_academica_id')->constrained('tipo_entidad_academica');
            // debe ser capaz de relacionarse con una carrera o facultad_centro
            $table->nullableMorphs('entidad_academica', 'entidad_academica_index');
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
