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
        Schema::table('equipo_ejecutor_bajas', function (Blueprint $table) {
            $table->string('rol_anterior')->nullable()->after('integrante_id');
            $table->foreignId('usuario_baja_id')->nullable()->constrained('users')->after('motivo_baja');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('equipo_ejecutor_bajas', function (Blueprint $table) {
            $table->dropColumn(['rol_anterior', 'usuario_baja_id']);
        });
    }
};
