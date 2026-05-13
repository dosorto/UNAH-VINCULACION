<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('flujos_aprobacion', function (Blueprint $table) {
            $table->foreignId('tipo_programa_id')
                ->nullable()
                ->after('proceso')
                ->constrained('tipos_programa')
                ->nullOnDelete();

            $table->unique(['proceso', 'tipo_programa_id'], 'uq_flujo_aprobacion_proceso_tipo');
        });
    }

    public function down(): void
    {
        Schema::table('flujos_aprobacion', function (Blueprint $table) {
            $table->dropUnique('uq_flujo_aprobacion_proceso_tipo');
            $table->dropConstrainedForeignId('tipo_programa_id');
        });
    }
};
