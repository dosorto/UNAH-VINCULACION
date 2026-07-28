<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('informe_intermedio_proyectos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('proyecto_id')->unique()->constrained('proyecto')->cascadeOnDelete();
            $table->foreignId('documento_proyecto_id')->nullable()->unique()
                ->constrained('proyecto_documento')->nullOnDelete();
            $table->string('archivo_pdf');
            $table->string('nombre_original');
            $table->string('mime_type', 100)->default('application/pdf');
            $table->unsignedBigInteger('tamano_bytes');
            $table->string('hash_sha256', 64);
            $table->string('estado', 30)->default('BORRADOR')->index();
            $table->foreignId('etapa_actual_id')->nullable()
                ->constrained('flujos_aprobacion_etapas')->nullOnDelete();
            $table->foreignId('etapa_retorno_id')->nullable()
                ->constrained('flujos_aprobacion_etapas')->nullOnDelete();
            $table->foreignId('subido_por')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('enviado_por')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('fecha_carga')->nullable();
            $table->timestamp('fecha_envio')->nullable();
            $table->foreignId('aprobado_por')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('fecha_aprobacion')->nullable();
            $table->text('observaciones')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('informe_intermedio_proyectos');
    }
};
