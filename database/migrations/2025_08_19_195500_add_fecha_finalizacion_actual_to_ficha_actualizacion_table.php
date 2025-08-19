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
            $table->date('fecha_finalizacion_actual')->nullable()->after('fecha_ampliacion')->comment('Fecha de finalización que tenía el proyecto al momento de crear la ficha');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ficha_actualizacion', function (Blueprint $table) {
            $table->dropColumn('fecha_finalizacion_actual');
        });
    }
};
