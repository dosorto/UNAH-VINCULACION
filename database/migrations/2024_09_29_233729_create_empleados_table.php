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
        Schema::create('empleado', function (Blueprint $table) {
            $table->id(); // Identificador único del empleado
            $table->string('nombre_completo')->nullable(); // Nombre completo del empleado
            $table->string('numero_empleado')->unique()->nullable(); // Número de empleado
            $table->string('celular')->nullable(); // Número de celular
            $table->string('categoria')->nullable(); // Categoría del empleado
            $table->unsignedBigInteger('user_id'); // Llave foránea para la tabla users
            $table->integer('campus_id')->nullable(); // Identificador del campus
            $table->integer('departamento_academico_id')->nullable(); // Identificador del departamento académico
            $table->softDeletes(); // Soft delete
            $table->timestamps(); // Created at y updated at
        
            // Definir la clave foránea hacia la tabla users
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
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
