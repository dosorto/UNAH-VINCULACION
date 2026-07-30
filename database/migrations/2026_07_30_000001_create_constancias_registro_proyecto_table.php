<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('constancias_registro_proyecto', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('proyecto_id');
            $table->string('numero', 50);
            $table->unsignedSmallInteger('anio');
            $table->unsignedInteger('correlativo');
            $table->string('codigo_validacion', 20);
            $table->char('token_hash', 64);
            $table->text('token_cifrado')->nullable();
            $table->string('ruta_archivo')->nullable();
            $table->char('hash_archivo', 64)->nullable();
            $table->json('snapshot');
            $table->dateTime('fecha_emision');
            $table->foreignId('emitida_por')->nullable();
            $table->string('estado', 16)->default('PENDIENTE');
            $table->unsignedInteger('version')->default(1);
            $table->text('motivo_anulacion')->nullable();
            $table->foreignId('anulada_por')->nullable();
            $table->dateTime('anulada_en')->nullable();
            $table->timestamps();
            $table->foreign('proyecto_id', 'crp_proyecto_fk')
                ->references('id')->on('proyecto')->restrictOnDelete();
            $table->foreign('emitida_por', 'crp_emitida_fk')
                ->references('id')->on('users')->nullOnDelete();
            $table->foreign('anulada_por', 'crp_anulada_fk')
                ->references('id')->on('users')->nullOnDelete();

            $table->unique('proyecto_id', 'crp_proyecto_unique');
            $table->unique('numero', 'crp_numero_unique');
            $table->unique('codigo_validacion', 'crp_codigo_unique');
            $table->unique('token_hash', 'crp_token_unique');
            $table->index('estado', 'crp_estado_idx');
            $table->index(['proyecto_id', 'estado'], 'crp_proyecto_estado_idx');
            $table->unique(['anio', 'correlativo'], 'crp_correlativo_anual_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('constancias_registro_proyecto');
    }
};
