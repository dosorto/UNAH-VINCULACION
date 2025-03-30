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
        // Tabla de tipos de participación
        Schema::create('tipo_participacion', function (Blueprint $table) {
            $table->id();
            $table->string('nombre')->unique();
            $table->timestamps();
        });

        // Insertar valores iniciales
        \DB::table('tipo_participacion')->insert([
            ['nombre' => 'Servicio Social Universitario'],
            ['nombre' => 'Práctica Profesional'],
            ['nombre' => 'Voluntariado'],
            ['nombre' => 'Práctica de Clase'],
        ]);

        // Tabla de estudiantes
        Schema::create('estudiante', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('set null');
            $table->string('nombre')->nullable();
            $table->string('apellido')->nullable();
            $table->string('cuenta')->nullable();
            $table->foreignId('centro_facultad_id')->nullable()->constrained('centro_facultad')->onDelete('set null');
            $table->foreignId('departamento_academico_id')->nullable()->constrained('departamento_academico')->onDelete('set null');
            $table->softDeletes();
            $table->timestamps();
        });

        // Tabla de relación entre estudiante y proyecto
        Schema::create('estudiante_proyecto', function (Blueprint $table) {
            $table->id();
            $table->foreignId('estudiante_id')->constrained('estudiante')->onDelete('cascade');
            $table->foreignId('proyecto_id')->constrained('proyecto')->onDelete('cascade');
            $table->foreignId('tipo_participacion_id')->constrained('tipo_participacion')->onDelete('cascade');
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('estudiante_proyecto');
        Schema::dropIfExists('estudiante');
        Schema::dropIfExists('tipo_participacion');
    }
};
