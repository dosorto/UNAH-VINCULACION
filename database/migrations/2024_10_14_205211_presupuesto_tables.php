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
        // tabla de modulo de presupuesto
        // tabla tipo_presupuesto
       

        // tabla presupuesto
        Schema::create('presupuesto', function (Blueprint $table) {
            $table->id();
            $table->foreignId('proyecto_id')->constrained('proyecto');
            $table->string('aporte_estudiantes')->nullable();
            $table->string('aporte_profesores')->nullable();
            $table->string('aporte_academico_unah')->nullable();
            $table->string('aporte_transporte_unah')->nullable();
            $table->string('aporte_contraparte')->nullable();
            $table->string('aporte_comunidad')->nullable();
            $table->softDeletes();
            $table->timestamps();
            $table->unique(['proyecto_id']);
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
