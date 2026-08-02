<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('informe_final_documentos_revision', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('informe_final_proyecto_id');
            $table->foreignId('firma_proyecto_id');
            $table->foreignId('estado_proyecto_id')->nullable();
            $table->foreignId('flujo_aprobacion_id');
            $table->foreignId('flujo_aprobacion_etapa_id')->nullable();
            $table->foreignId('subido_por')->nullable();
            $table->unsignedInteger('revision_ciclo')->default(1);
            $table->string('ruta');
            $table->string('nombre_original');
            $table->string('mime_type', 100);
            $table->unsignedBigInteger('tamano_bytes');
            $table->timestamps();
            $table->foreign('informe_final_proyecto_id', 'if_doc_rev_informe_fk')->references('id')->on('informe_final_proyectos')->cascadeOnDelete();
            $table->foreign('firma_proyecto_id', 'if_doc_rev_firma_fk')->references('id')->on('firma_proyecto')->cascadeOnDelete();
            $table->foreign('estado_proyecto_id', 'if_doc_rev_movimiento_fk')->references('id')->on('estado_proyecto')->nullOnDelete();
            $table->foreign('flujo_aprobacion_id', 'if_doc_rev_flujo_fk')->references('id')->on('flujos_aprobacion')->cascadeOnDelete();
            $table->foreign('flujo_aprobacion_etapa_id', 'if_doc_rev_etapa_fk')->references('id')->on('flujos_aprobacion_etapas')->nullOnDelete();
            $table->foreign('subido_por', 'if_doc_rev_usuario_fk')->references('id')->on('users')->nullOnDelete();
            $table->index(['informe_final_proyecto_id', 'revision_ciclo'], 'inf_final_revision_documento_ciclo_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('informe_final_documentos_revision');
    }
};
