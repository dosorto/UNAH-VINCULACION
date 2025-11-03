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
            $table->json('cambios_cantidades_estudiantes')->nullable()->after('motivo_razones_cambio');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ficha_actualizacion', function (Blueprint $table) {
            $table->dropColumn('cambios_cantidades_estudiantes');
        });
    }
};
