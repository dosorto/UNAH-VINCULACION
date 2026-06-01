<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pps_servicio_social', function (Blueprint $table) {
            $table->timestamp('fecha_envio')->nullable()->after('estado');
            $table->unsignedBigInteger('enviado_por')->nullable()->index()->after('updated_by');
        });
    }

    public function down(): void
    {
        Schema::table('pps_servicio_social', function (Blueprint $table) {
            $table->dropColumn(['fecha_envio', 'enviado_por']);
        });
    }
};
