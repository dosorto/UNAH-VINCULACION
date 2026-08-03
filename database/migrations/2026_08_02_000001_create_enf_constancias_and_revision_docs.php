<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('constancia_correlativos')) {
            Schema::create('constancia_correlativos', function (Blueprint $table): void {
                $table->id();
                $table->string('tipo', 40);
                $table->unsignedSmallInteger('anio');
                $table->string('unidad_emisora', 40);
                $table->unsignedInteger('ultimo_correlativo')->default(0);
                $table->timestamps();
                $table->unique(['tipo', 'anio', 'unidad_emisora'], 'constancia_correlativo_unico');
            });
        }

        Schema::create('enf_constancias_registro', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('enf_accion_id');
            $table->string('numero', 60);
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

            $table->foreign('enf_accion_id', 'enf_cr_accion_fk')->references('id')->on('enf_acciones')->restrictOnDelete();
            $table->foreign('emitida_por', 'enf_cr_emitida_fk')->references('id')->on('users')->nullOnDelete();
            $table->foreign('anulada_por', 'enf_cr_anulada_fk')->references('id')->on('users')->nullOnDelete();

            $table->unique('enf_accion_id', 'enf_cr_accion_unique');
            $table->unique('numero', 'enf_cr_numero_unique');
            $table->unique('codigo_validacion', 'enf_cr_codigo_unique');
            $table->unique('token_hash', 'enf_cr_token_unique');
            $table->unique(['anio', 'correlativo'], 'enf_cr_correlativo_anual_unique');
            $table->index(['enf_accion_id', 'estado'], 'enf_cr_accion_estado_idx');
        });

        Schema::create('enf_constancias_finalizacion', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('enf_accion_id');
            $table->foreignId('enf_informe_final_id');
            $table->string('numero', 60);
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

            $table->foreign('enf_accion_id', 'enf_cf_accion_fk')->references('id')->on('enf_acciones')->restrictOnDelete();
            $table->foreign('enf_informe_final_id', 'enf_cf_informe_fk')->references('id')->on('enf_informes_finales')->restrictOnDelete();
            $table->foreign('emitida_por', 'enf_cf_emitida_fk')->references('id')->on('users')->nullOnDelete();
            $table->foreign('anulada_por', 'enf_cf_anulada_fk')->references('id')->on('users')->nullOnDelete();

            $table->unique('enf_informe_final_id', 'enf_cf_informe_unique');
            $table->unique('numero', 'enf_cf_numero_unique');
            $table->unique('codigo_validacion', 'enf_cf_codigo_unique');
            $table->unique('token_hash', 'enf_cf_token_unique');
            $table->unique(['anio', 'correlativo'], 'enf_cf_correlativo_anual_unique');
            $table->index(['enf_accion_id', 'estado'], 'enf_cf_accion_estado_idx');
        });

        Schema::create('enf_informe_final_documentos_revision', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('enf_informe_final_id');
            $table->foreignId('enf_revision_id');
            $table->foreignId('enf_accion_id');
            $table->foreignId('flujo_aprobacion_etapa_id')->nullable();
            $table->foreignId('subido_por')->nullable();
            $table->unsignedInteger('revision_ciclo')->default(1);
            $table->string('ruta');
            $table->string('nombre_original');
            $table->string('mime_type', 100);
            $table->unsignedBigInteger('tamano_bytes');
            $table->timestamps();

            $table->foreign('enf_informe_final_id', 'enf_if_doc_informe_fk')->references('id')->on('enf_informes_finales')->cascadeOnDelete();
            $table->foreign('enf_revision_id', 'enf_if_doc_revision_fk')->references('id')->on('enf_revisiones')->cascadeOnDelete();
            $table->foreign('enf_accion_id', 'enf_if_doc_accion_fk')->references('id')->on('enf_acciones')->cascadeOnDelete();
            $table->foreign('flujo_aprobacion_etapa_id', 'enf_if_doc_etapa_fk')->references('id')->on('flujos_aprobacion_etapas')->nullOnDelete();
            $table->foreign('subido_por', 'enf_if_doc_usuario_fk')->references('id')->on('users')->nullOnDelete();
            $table->index(['enf_informe_final_id', 'revision_ciclo'], 'enf_inf_final_doc_rev_ciclo_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('enf_informe_final_documentos_revision');
        Schema::dropIfExists('enf_constancias_finalizacion');
        Schema::dropIfExists('enf_constancias_registro');
    }
};
