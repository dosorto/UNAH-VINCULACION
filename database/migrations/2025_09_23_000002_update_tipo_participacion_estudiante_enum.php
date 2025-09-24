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
        Schema::table('estudiante_proyecto', function (Blueprint $table) {
            DB::statement("ALTER TABLE estudiante_proyecto MODIFY COLUMN tipo_participacion_estudiante 
            ENUM('Servicio Social o PPS', 'Practica Profesional', 'Voluntariado', 'Practica Asignatura')");
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('estudiante_proyecto', function (Blueprint $table) {
            DB::statement("ALTER TABLE estudiante_proyecto MODIFY COLUMN tipo_participacion_estudiante ENUM('Servicio Social o PPS', 'Practica Profesional', 'Voluntariado')");
        });
    }
};