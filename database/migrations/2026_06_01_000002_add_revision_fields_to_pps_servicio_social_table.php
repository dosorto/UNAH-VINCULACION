<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pps_servicio_social', function (Blueprint $table) {
            $table->timestamp('fecha_revision')->nullable()->after('fecha_envio');
            $table->unsignedBigInteger('revisado_por')->nullable()->index()->after('enviado_por');
            $table->text('motivo_rechazo')->nullable()->after('revisado_por');
        });
    }

    public function down(): void
    {
        Schema::table('pps_servicio_social', function (Blueprint $table) {
            $table->dropColumn(['fecha_revision', 'revisado_por', 'motivo_rechazo']);
        });
    }
};
