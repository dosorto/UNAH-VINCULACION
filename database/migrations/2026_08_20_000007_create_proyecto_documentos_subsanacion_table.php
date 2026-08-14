<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('proyecto_documentos_subsanacion', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('proyecto_id');
            $table->foreignId('estado_proyecto_id')->nullable();
            $table->foreignId('subido_por')->nullable();
            $table->string('ruta');
            $table->string('nombre_original');
            $table->string('mime_type', 100);
            $table->unsignedBigInteger('tamano_bytes');
            $table->timestamps();

            $table->foreign('proyecto_id', 'proy_doc_subsan_proyecto_fk')->references('id')->on('proyecto')->cascadeOnDelete();
            $table->foreign('estado_proyecto_id', 'proy_doc_subsan_movimiento_fk')->references('id')->on('estado_proyecto')->nullOnDelete();
            $table->foreign('subido_por', 'proy_doc_subsan_usuario_fk')->references('id')->on('users')->nullOnDelete();
            $table->index('proyecto_id', 'proy_doc_subsan_proyecto_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('proyecto_documentos_subsanacion');
    }
};
