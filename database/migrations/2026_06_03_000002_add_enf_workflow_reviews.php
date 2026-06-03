<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('enf_acciones', function (Blueprint $table) {
            if (! Schema::hasColumn('enf_acciones', 'flujo_aprobacion_id')) {
                $table->foreignId('flujo_aprobacion_id')
                    ->nullable()
                    ->after('revision_ciclo')
                    ->constrained('flujos_aprobacion')
                    ->nullOnDelete();
            }
        });

        if (! Schema::hasTable('enf_revisiones')) {
            Schema::create('enf_revisiones', function (Blueprint $table) {
                $table->id();
                $table->foreignId('enf_accion_id')->constrained('enf_acciones')->cascadeOnDelete();
                $table->foreignId('flujo_aprobacion_etapa_id')->nullable()->constrained('flujos_aprobacion_etapas')->nullOnDelete();
                $table->unsignedInteger('revision_ciclo')->default(1);
                $table->unsignedInteger('orden');
                $table->string('etapa_codigo', 80);
                $table->string('etapa_nombre', 180);
                $table->string('rol_requerido', 180)->nullable();
                $table->foreignId('responsable_usuario_id')->nullable()->constrained('users')->nullOnDelete();
                $table->foreignId('asignado_usuario_id')->nullable()->constrained('users')->nullOnDelete();
                $table->foreignId('decidido_por_usuario_id')->nullable()->constrained('users')->nullOnDelete();
                $table->string('estado', 40)->default('PENDIENTE');
                $table->text('observaciones')->nullable();
                $table->timestamp('firmado_en')->nullable();
                $table->timestamps();

                $table->index(['enf_accion_id', 'revision_ciclo', 'orden'], 'enf_revisiones_ciclo_orden_idx');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('enf_revisiones');

        Schema::table('enf_acciones', function (Blueprint $table) {
            if (Schema::hasColumn('enf_acciones', 'flujo_aprobacion_id')) {
                $table->dropConstrainedForeignId('flujo_aprobacion_id');
            }
        });
    }
};
