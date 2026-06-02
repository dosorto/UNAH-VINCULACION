<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('pps_servicio_social_revision_historial')) {
            Schema::create('pps_servicio_social_revision_historial', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('pps_servicio_social_id');
                $table->unsignedBigInteger('flujo_aprobacion_id')->nullable();
                $table->unsignedBigInteger('etapa_origen_id')->nullable();
                $table->unsignedBigInteger('etapa_destino_id')->nullable();
                $table->string('accion');
                $table->string('estado_origen')->nullable();
                $table->string('estado_destino')->nullable();
                $table->text('comentario')->nullable();
                $table->text('motivo_rechazo')->nullable();
                $table->unsignedBigInteger('realizado_por')->nullable();
                $table->timestamps();

                $table->index('pps_servicio_social_id', 'pps_ss_hist_registro_idx');
                $table->index('flujo_aprobacion_id', 'pps_ss_hist_flujo_idx');
                $table->index('etapa_origen_id', 'pps_ss_hist_etapa_origen_idx');
                $table->index('etapa_destino_id', 'pps_ss_hist_etapa_destino_idx');
                $table->index('realizado_por', 'pps_ss_hist_realizado_por_idx');
            });

            return;
        }

        $this->addMissingIndex('pps_servicio_social_id', 'pps_ss_hist_registro_idx');
        $this->addMissingIndex('flujo_aprobacion_id', 'pps_ss_hist_flujo_idx');
        $this->addMissingIndex('etapa_origen_id', 'pps_ss_hist_etapa_origen_idx');
        $this->addMissingIndex('etapa_destino_id', 'pps_ss_hist_etapa_destino_idx');
        $this->addMissingIndex('realizado_por', 'pps_ss_hist_realizado_por_idx');
    }

    public function down(): void
    {
        Schema::dropIfExists('pps_servicio_social_revision_historial');
    }

    private function addMissingIndex(string $column, string $indexName): void
    {
        if ($this->indexExists($indexName)) {
            return;
        }

        Schema::table('pps_servicio_social_revision_historial', function (Blueprint $table) use ($column, $indexName) {
            $table->index($column, $indexName);
        });
    }

    private function indexExists(string $indexName): bool
    {
        return DB::table('information_schema.statistics')
            ->where('table_schema', DB::getDatabaseName())
            ->where('table_name', 'pps_servicio_social_revision_historial')
            ->where('index_name', $indexName)
            ->exists();
    }
};
