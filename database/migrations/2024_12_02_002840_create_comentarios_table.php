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
        Schema::create('categoria_comentario', function (Blueprint $table) {
            $table->id();
            $table->string('nombre_categoria');
            $table->timestamps();
        });

        Schema::create('comentario', function (Blueprint $table) {
            $table->id();
            $table->text('contenido');
            $table->foreignId('empleado_id')->constrained('empleado');
            $table->foreignId('categoria_comentario_id')->constrained('categoria_comentario');
            $table->boolean('visto')->default(false);
            $table->timestamps();
        });

        Schema::create('respuesta_comentario', function (Blueprint $table) {
            $table->id();
            $table->text('contenido');
            $table->foreignId('empleado_id')->constrained('empleado');
            $table->foreignId('comentario_id')->constrained('comentario');
            $table->timestamps();
        });


    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('comentario');
    }
};
