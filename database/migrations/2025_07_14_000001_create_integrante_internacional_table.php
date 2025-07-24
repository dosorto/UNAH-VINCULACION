<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('integrante_internacional', function (Blueprint $table) {
            $table->id();
            $table->string('nombre_completo');
            $table->string('documento_identidad'); // Pasaporte
            $table->enum('sexo', ['masculino', 'femenino', 'otro'])->nullable(); // Sexo del integrante
            $table->string('email')->unique();
            $table->string('pais');
            $table->string('institucion'); // Universidad/Institución
            $table->timestamps();

            // Índices
            $table->index('email');
            $table->index('pais');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('integrante_internacional');
    }
};
