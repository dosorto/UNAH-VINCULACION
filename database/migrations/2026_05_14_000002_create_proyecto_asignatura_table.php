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
        Schema::create('proyecto_asignatura', function (Blueprint $table) {
            $table->id();
            $table->foreignId('proyecto_id')
                ->constrained('proyecto')
                ->cascadeOnDelete();
            $table->foreignId('asignatura_id')
                ->constrained('asignaturas')
                ->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['proyecto_id', 'asignatura_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('proyecto_asignatura');
    }
};
