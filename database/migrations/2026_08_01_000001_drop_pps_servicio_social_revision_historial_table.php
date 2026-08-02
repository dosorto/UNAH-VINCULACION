<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('pps_servicio_social_revision_historial');
    }

    public function down(): void
    {
        Schema::create('pps_servicio_social_revision_historial', function ($table) {
            $table->id();
            $table->unsignedBigInteger('pps_servicio_social_id');
            $table->unsignedBigInteger('flujo_aprobacion_id')->nullable();
            $table->unsignedBigInteger('etapa_origen_id')->nullable();
            $table->unsignedBigInteger('etapa_destino_id')->nullable();
            $table->string('accion', 80)->index();
            $table->string('estado_origen', 50)->nullable();
            $table->string('estado_destino', 50)->nullable();
            $table->text('comentario')->nullable();
            $table->text('motivo_rechazo')->nullable();
            $table->unsignedBigInteger('realizado_por')->nullable();
            $table->timestamps();
        });
    }
};
