<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('workflow_legacy_adoptions', function (Blueprint $table): void {
            $table->id();
            $table->morphs('adoptable');
            $table->foreignId('flujo_aprobacion_id')
                ->constrained('flujos_aprobacion')
                ->restrictOnDelete();
            $table->foreignId('etapa_inicio_id')
                ->nullable()
                ->constrained('flujos_aprobacion_etapas')
                ->nullOnDelete();
            $table->unsignedInteger('orden_inicio')->nullable();
            $table->string('proceso', 80);
            $table->string('modo', 40);
            $table->foreignId('estado_origen_id')
                ->nullable()
                ->constrained('tipo_estado')
                ->nullOnDelete();
            $table->string('estado_origen', 180)->nullable();
            $table->foreignId('revisor_usuario_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();
            $table->foreignId('adoptado_por_usuario_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();
            $table->json('evidencia')->nullable();
            $table->timestamp('adoptado_en');
            $table->timestamps();

            $table->unique(
                ['adoptable_type', 'adoptable_id'],
                'workflow_legacy_adoptable_unique'
            );
            $table->index(['proceso', 'modo'], 'workflow_legacy_process_mode_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('workflow_legacy_adoptions');
    }
};
