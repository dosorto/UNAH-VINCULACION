<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('ficha_actualizacion', function (Blueprint $table) {
            $table->text('motivo_responsabilidades_nuevos')->nullable()->after('motivo_ampliacion');
            $table->text('motivo_razones_cambio')->nullable()->after('motivo_responsabilidades_nuevos');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ficha_actualizacion', function (Blueprint $table) {
            $table->dropColumn(['motivo_responsabilidades_nuevos', 'motivo_razones_cambio']);
        });
    }
};
