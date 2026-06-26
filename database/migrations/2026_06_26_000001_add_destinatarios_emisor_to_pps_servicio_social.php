<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pps_servicio_social', function (Blueprint $table): void {
            $table->json('destinatarios_emisor')->nullable()->after('motivo_rechazo');
        });
    }

    public function down(): void
    {
        Schema::table('pps_servicio_social', function (Blueprint $table): void {
            $table->dropColumn('destinatarios_emisor');
        });
    }
};
