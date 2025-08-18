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
        Schema::create('equipo_ejecutor_nuevos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('proyecto_id')->constrained('proyecto')->onDelete('cascade');
            $table->unsignedBigInteger('integrante_id'); // ID del empleado/estudiante/internacional
            $table->enum('tipo_integrante', ['empleado', 'estudiante', 'integrante_internacional']);
            $table->string('nombre_integrante');
            $table->string('rol_nuevo'); // Rol que tendrá el nuevo integrante
            $table->text('motivo_incorporacion'); // Motivo por el cual se incorpora
            $table->timestamp('fecha_solicitud');
            $table->foreignId('usuario_solicitud_id')->constrained('users')->onDelete('cascade');
            $table->enum('estado_incorporacion', ['pendiente', 'aplicado'])->default('pendiente');
            $table->foreignId('ficha_actualizacion_id')->nullable()->constrained('ficha_actualizacion')->onDelete('cascade');
            $table->timestamps();
            
            // Índices
            $table->index(['proyecto_id', 'estado_incorporacion']);
            $table->index(['tipo_integrante', 'integrante_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('equipo_ejecutor_nuevos');
    }
};
