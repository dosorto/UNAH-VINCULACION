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

        Schema::create('categoria', function (Blueprint $table) {
            $table->id(); // Identificador único de la categoría
            $table->string('nombre')->nullable(); // Nombre de la categoría
            $table->string('descripcion')->nullable(); // Descripción de la categoría
            $table->softDeletes(); // Soft delete
            $table->timestamps(); // Created at y updated at
        });


        Schema::create('empleado', function (Blueprint $table) {
            $table->id(); // Identificador único del empleado
            $table->string('nombre_completo')->nullable(); // Nombre completo del empleado
            $table->string('numero_empleado')->unique()->nullable(); // Número de empleado
            $table->string('celular')->nullable(); // Número de celular
            $table->unsignedBigInteger('categoria_id')->nullable(); // Identificador de la categoría
            $table->unsignedBigInteger('user_id'); // Llave foránea para la tabla users
            $table->integer('centro_facultad_id')->nullable(); // Identificador del campus
            $table->integer('departamento_academico_id')->nullable(); // Identificador del departamento académico
            $table->softDeletes(); // Soft delete
            $table->timestamps(); // Created at y updated at
        
            // Definir la clave foránea hacia la tabla users
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('categoria_id')->references('id')->on('categoria')->onDelete('cascade');
        });

        Schema::create('firma_sello_empleado', function (Blueprint $table) {
            $table->id(); // ID autoincremental
            $table->foreignId('empleado_id') // Relación con empleado
                ->constrained('empleado'); // Indica que está relacionado con la tabla 'empleados'
            $table->string('tipo'); // Tipo de documento: firma o sello
            $table->string('ruta_storage'); // Ruta donde se guardará el archivo en el almacenamiento
            $table->boolean('estado')->default(true); // Estado del documento (activo o no)
            $table->softDeletes(); // Soft delete
            $table->timestamps(); // Campos 'created_at' y 'updated_at'
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
