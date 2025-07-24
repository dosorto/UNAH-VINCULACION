<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Tabla para objetivos específicos
        Schema::create('objetivo_especifico', function (Blueprint $table) {
            $table->id();
            $table->foreignId('proyecto_id')->constrained('proyecto')->onDelete('cascade');
            $table->longText('descripcion');
            $table->integer('orden')->default(1);
            $table->softDeletes();
            $table->timestamps();
        });

        // Tabla para resultados esperados
        Schema::create('resultado_esperado', function (Blueprint $table) {
            $table->id();
            $table->foreignId('objetivo_especifico_id')->constrained('objetivo_especifico')->onDelete('cascade');
            $table->longText('descripcion');
            $table->integer('orden')->default(1);
            $table->softDeletes();
            $table->timestamps();
        });

        // Tabla para indicadores de resultados
        Schema::create('indicador_resultado', function (Blueprint $table) {
            $table->id();
            $table->foreignId('objetivo_especifico_id')->constrained('objetivo_especifico')->onDelete('cascade');
            $table->string('nombre');
            $table->longText('descripcion');
            $table->string('meta');
            $table->integer('orden')->default(1);
            $table->softDeletes();
            $table->timestamps();
        });

        // Tabla para medios de verificación
        Schema::create('medio_verificacion', function (Blueprint $table) {
            $table->id();
            $table->foreignId('objetivo_especifico_id')->constrained('objetivo_especifico')->onDelete('cascade');
            $table->string('nombre');
            $table->enum('tipo', ['documento', 'fotografia', 'video', 'informe', 'acta', 'certificado', 'otro']);
            $table->longText('descripcion');
            $table->string('archivo')->nullable();
            $table->integer('orden')->default(1);
            $table->softDeletes();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('medio_verificacion');
        Schema::dropIfExists('indicador_resultado');
        Schema::dropIfExists('resultado_esperado');
        Schema::dropIfExists('objetivo_especifico');
    }
};
