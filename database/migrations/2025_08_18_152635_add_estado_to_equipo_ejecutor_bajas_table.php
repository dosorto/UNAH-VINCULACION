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
        Schema::table('equipo_ejecutor_bajas', function (Blueprint $table) {
            // Estado de la baja: 'pendiente' (esperando aprobación) o 'aplicada' (ya procesada)
            $table->enum('estado_baja', ['pendiente', 'aplicada'])->default('pendiente')->after('usuario_baja_id');
            
            // ID de la ficha de actualización que solicitó esta baja
            $table->unsignedBigInteger('ficha_actualizacion_id')->nullable()->after('estado_baja');
            
            // Agregar foreign key
            $table->foreign('ficha_actualizacion_id')->references('id')->on('ficha_actualizacion')->onDelete('cascade');
            
            // Índices para mejorar el rendimiento
            $table->index(['proyecto_id', 'estado_baja']);
            $table->index(['ficha_actualizacion_id', 'estado_baja']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('equipo_ejecutor_bajas', function (Blueprint $table) {
            $table->dropForeign(['ficha_actualizacion_id']);
            $table->dropIndex(['proyecto_id', 'estado_baja']);
            $table->dropIndex(['ficha_actualizacion_id', 'estado_baja']);
            $table->dropColumn(['estado_baja', 'ficha_actualizacion_id']);
        });
    }
};
