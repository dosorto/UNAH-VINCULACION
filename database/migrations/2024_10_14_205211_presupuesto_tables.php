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
        Schema::create('tipo_presupuesto', function (Blueprint $table) {
            $table->id();
            $table->string('nombre')->unique();
            $table->softDeletes();
            $table->timestamps();
        });

        // tabla presupuesto
        Schema::create('presupuesto', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tipo_presupuesto_id')->constrained('tipo_presupuesto');
            $table->foreignId('administrador_id')->constrained('empleado');
            $table->foreignId('proyecto_id')->constrained('proyecto');
            $table->softDeletes();
            $table->timestamps();
            $table->unique(['proyecto_id', 'tipo_presupuesto_id']);
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
