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
        // tablas de modulo de estado
        Schema::create('tipo_estado', function (Blueprint $table) {
            $table->id();
            $table->string('nombre');
            $table->softDeletes();
            $table->timestamps();
        });

        // estado del proyecto por empleado usando fecha
        Schema::create('estado_proyecto', function (Blueprint $table) {
            $table->id();
            $table->foreignId('proyecto_id')->constrained('proyecto');
            $table->foreignId('empleado_id')->constrained('empleado');
            $table->foreignId('tipo_estado_id')->constrained('tipo_estado');
            $table->date('fecha');
            $table->string('comentario');
            $table->boolean('es_actual')->default(true);
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
