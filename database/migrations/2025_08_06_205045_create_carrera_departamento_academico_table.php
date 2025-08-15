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
        Schema::create('carrera_departamento_academico', function (Blueprint $table) {
            $table->id();
            $table->foreignId('carrera_id')->constrained('carrera');
            $table->foreignId('departamento_academico_id')->constrained('departamento_academico');
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('carrera_departamento_academico');
    }
};
