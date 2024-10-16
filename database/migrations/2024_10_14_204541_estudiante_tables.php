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
        // tabla de modulo de estudiante
        // tabla estudiante
        Schema::create('estudiante', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->nullable();
            $table->string('nombre');
            $table->string('apellido');
            $table->string('cuenta');
            $table->softDeletes();
            $table->timestamps();
        });

        // tabla estudiante_proyecto
        Schema::create('estudiante_proyecto', function (Blueprint $table) {
            $table->id();
            $table->foreignId('estudiante_id')->constrained('estudiante');
            $table->foreignId('proyecto_id')->constrained('proyecto');
            $table->enum('tipo_participacion', ['voluntario', 'practica', 'profesional']);
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