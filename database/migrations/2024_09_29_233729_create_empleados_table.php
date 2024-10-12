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
        Schema::create('empleados', function (Blueprint $table) {
            $table->id(); // Identificador único del empleado
            $table->string('nombre'); // Nombre completo del empleado
            $table->date('fecha_contratacion'); // Fecha de contratación
            $table->decimal('salario', 8, 2); // Salario con 8 dígitos, 2 decimales
            $table->string('supervisor'); // Nombre del supervisor
            $table->enum('jornada', ['completa', 'parcial']); // Tipo de jornada laboral
            $table->softDeletes(); // Soft delete
            $table->timestamps(); // Created at y updated at
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('empleados');
    }
};
