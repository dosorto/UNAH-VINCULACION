<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('firma_proyecto', function (Blueprint $table) {
            $table->foreignId('flujo_aprobacion_id')
                ->nullable()
                ->after('firmable_id')
                ->constrained('flujos_aprobacion')
                ->nullOnDelete();
            $table->foreignId('flujo_aprobacion_etapa_id')
                ->nullable()
                ->after('flujo_aprobacion_id')
                ->constrained('flujos_aprobacion_etapas')
                ->nullOnDelete();
            $table->unsignedInteger('orden_revision')->nullable()->after('flujo_aprobacion_etapa_id');
            $table->string('etapa_codigo', 80)->nullable()->after('orden_revision');
            $table->string('etapa_nombre', 180)->nullable()->after('etapa_codigo');
            $table->string('rol_requerido', 180)->nullable()->after('etapa_nombre');
            $table->foreignId('responsable_usuario_id')
                ->nullable()
                ->after('rol_requerido')
                ->constrained('users')
                ->nullOnDelete();
            $table->unsignedInteger('revision_ciclo')->nullable()->after('responsable_usuario_id');

            $table->index('revision_ciclo', 'firma_proyecto_revision_ciclo_index');
            $table->index(
                ['firmable_type', 'firmable_id', 'flujo_aprobacion_etapa_id', 'revision_ciclo'],
                'firma_proyecto_firmable_etapa_ciclo_idx'
            );
        });
    }

    public function down(): void
    {
        Schema::table('firma_proyecto', function (Blueprint $table) {
            $table->dropIndex('firma_proyecto_firmable_etapa_ciclo_idx');
            $table->dropIndex('firma_proyecto_revision_ciclo_index');

            $table->dropConstrainedForeignId('responsable_usuario_id');
            $table->dropConstrainedForeignId('flujo_aprobacion_etapa_id');
            $table->dropConstrainedForeignId('flujo_aprobacion_id');

            $table->dropColumn([
                'orden_revision',
                'etapa_codigo',
                'etapa_nombre',
                'rol_requerido',
                'revision_ciclo',
            ]);
        });
    }
};
