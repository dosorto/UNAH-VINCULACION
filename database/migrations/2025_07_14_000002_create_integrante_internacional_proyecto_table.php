<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('integrante_internacional_proyecto', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('proyecto_id');
            $table->unsignedBigInteger('integrante_internacional_id');
            $table->string('rol')->default('Cooperante Internacional');
            $table->softDeletes();
            $table->timestamps();

            // Claves foráneas con nombres cortos
            $table->foreign('proyecto_id', 'iip_proyecto_fk')->references('id')->on('proyecto')->onDelete('cascade');
            $table->foreign('integrante_internacional_id', 'iip_integrante_fk')->references('id')->on('integrante_internacional')->onDelete('cascade');
            
            // Índice único para evitar duplicados con nombre corto
            $table->unique(['proyecto_id', 'integrante_internacional_id'], 'iip_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('integrante_internacional_proyecto');
    }
};
