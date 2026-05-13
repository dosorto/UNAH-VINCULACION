<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('asignatura_prerrequisitos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('asignatura_id')->constrained('asignaturas')->cascadeOnDelete();
            $table->foreignId('prerrequisito_asignatura_id')->constrained('asignaturas')->cascadeOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['asignatura_id', 'prerrequisito_asignatura_id'], 'uq_asig_prerreq');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('asignatura_prerrequisitos');
    }
};
