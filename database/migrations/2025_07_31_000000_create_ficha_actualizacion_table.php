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
        Schema::create('ficha_actualizacion', function (Blueprint $table) {
            $table->id();
            $table->timestamp('fecha_registro')->nullable();
            $table->unsignedBigInteger('empleado_id');
            $table->unsignedBigInteger('proyecto_id');
            // Integrantes dados de baja (puede ser un JSON con los IDs de los integrantes dados de baja)
            $table->json('integrantes_baja')->nullable();
            $table->text('motivo_baja')->nullable();
            $table->date('fecha_baja')->nullable();
            // Usuarios actuales (puede ser el id de proyecto, ya está)
            // Ampliación de proyecto
            $table->date('fecha_ampliacion')->nullable();
            $table->text('motivo_ampliacion')->nullable();
            $table->timestamps();

            $table->foreign('empleado_id')->references('id')->on('empleado')->onDelete('cascade');
            $table->foreign('proyecto_id')->references('id')->on('proyecto')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ficha_actualizacion');
    }
};
