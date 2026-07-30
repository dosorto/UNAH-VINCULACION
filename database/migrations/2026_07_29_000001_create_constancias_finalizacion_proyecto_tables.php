<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('constancia_correlativos', function (Blueprint $table): void {
            $table->id();
            $table->string('tipo', 40);
            $table->unsignedSmallInteger('anio');
            $table->string('unidad_emisora', 40);
            $table->unsignedInteger('ultimo_correlativo')->default(0);
            $table->timestamps();
            $table->unique(['tipo', 'anio', 'unidad_emisora'], 'constancia_correlativo_unico');
        });

        Schema::create('constancias_finalizacion_proyecto', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('proyecto_id');
            $table->foreignId('informe_final_proyecto_id');
            $table->foreignId('documento_proyecto_id');
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
            $table->foreign('proyecto_id', 'cfp_proyecto_fk')
                ->references('id')->on('proyecto')->restrictOnDelete();
            $table->foreign('informe_final_proyecto_id', 'cfp_informe_fk')
                ->references('id')->on('informe_final_proyectos')->restrictOnDelete();
            $table->foreign('documento_proyecto_id', 'cfp_documento_fk')
                ->references('id')->on('proyecto_documento')->restrictOnDelete();
            $table->foreign('emitida_por', 'cfp_emitida_fk')
                ->references('id')->on('users')->nullOnDelete();
            $table->foreign('anulada_por', 'cfp_anulada_fk')
                ->references('id')->on('users')->nullOnDelete();

            $table->unique('informe_final_proyecto_id', 'cfp_informe_unique');
            $table->unique('documento_proyecto_id', 'cfp_documento_unique');
            $table->unique('numero', 'cfp_numero_unique');
            $table->unique('codigo_validacion', 'cfp_codigo_unique');
            $table->unique('token_hash', 'cfp_token_unique');
            $table->index('estado', 'cfp_estado_idx');
            $table->index(['proyecto_id', 'estado'], 'cfp_proyecto_estado_idx');
            $table->unique(['anio', 'correlativo'], 'cfp_correlativo_anual_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('constancias_finalizacion_proyecto');
        Schema::dropIfExists('constancia_correlativos');
    }
};
