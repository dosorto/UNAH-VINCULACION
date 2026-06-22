<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Sección VI de FORM-DVUS-015: uso de espacios, servicios y medios institucionales.
     * Tabla repetible asociada a un proyecto.
     */
    public function up(): void
    {
        Schema::create('proyecto_espacio_institucional', function (Blueprint $table) {
            $table->id();
            $table->foreignId('proyecto_id')
                ->constrained('proyecto')
                ->cascadeOnDelete();
            $table->string('descripcion', 255);
            $table->string('ubicacion', 255)->nullable();
            $table->string('unidad_gestora', 255)->nullable();
            $table->decimal('tiempo_uso_horas', 8, 2)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('proyecto_espacio_institucional');
    }
};
