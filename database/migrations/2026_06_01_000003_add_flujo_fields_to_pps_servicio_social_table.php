<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pps_servicio_social', function (Blueprint $table) {
            $table->unsignedBigInteger('flujo_aprobacion_id')->nullable()->index()->after('estado');
            $table->unsignedBigInteger('etapa_actual_id')->nullable()->index()->after('flujo_aprobacion_id');
        });
    }

    public function down(): void
    {
        Schema::table('pps_servicio_social', function (Blueprint $table) {
            $table->dropColumn(['flujo_aprobacion_id', 'etapa_actual_id']);
        });
    }
};
