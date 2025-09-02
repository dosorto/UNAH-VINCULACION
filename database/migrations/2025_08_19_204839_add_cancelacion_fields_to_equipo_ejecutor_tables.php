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
        // Agregar campos de cancelación a la tabla equipo_ejecutor_nuevos
        Schema::table('equipo_ejecutor_nuevos', function (Blueprint $table) {
            $table->timestamp('fecha_cancelacion')->nullable()->after('fecha_solicitud');
            $table->text('motivo_cancelacion')->nullable()->after('fecha_cancelacion');
        });

        // Agregar campos de cancelación a la tabla equipo_ejecutor_bajas
        Schema::table('equipo_ejecutor_bajas', function (Blueprint $table) {
            $table->timestamp('fecha_cancelacion')->nullable()->after('fecha_baja');
            $table->text('motivo_cancelacion')->nullable()->after('fecha_cancelacion');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('equipo_ejecutor_nuevos', function (Blueprint $table) {
            $table->dropColumn(['fecha_cancelacion', 'motivo_cancelacion']);
        });

        Schema::table('equipo_ejecutor_bajas', function (Blueprint $table) {
            $table->dropColumn(['fecha_cancelacion', 'motivo_cancelacion']);
        });
    }
};
