<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Eliminar las tablas separadas ya que ahora todo está en resultado_esperado
        Schema::dropIfExists('indicador_resultado');
        Schema::dropIfExists('medio_verificacion');
    }

    public function down(): void
    {
        // Recrear las tablas si es necesario hacer rollback
        Schema::create('indicador_resultado', function (Blueprint $table) {
            $table->id();
            $table->foreignId('objetivo_especifico_id')->constrained('objetivo_especifico')->onDelete('cascade');
            $table->string('nombre');
            $table->integer('orden')->default(1);
            $table->softDeletes();
            $table->timestamps();
        });

        Schema::create('medio_verificacion', function (Blueprint $table) {
            $table->id();
            $table->foreignId('objetivo_especifico_id')->constrained('objetivo_especifico')->onDelete('cascade');
            $table->string('nombre');
            $table->integer('orden')->default(1);
            $table->softDeletes();
            $table->timestamps();
        });
    }
};
