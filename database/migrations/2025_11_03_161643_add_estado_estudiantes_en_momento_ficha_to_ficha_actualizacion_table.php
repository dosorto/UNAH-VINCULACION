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
            $table->json('estado_estudiantes_en_momento_ficha')->nullable()->after('cambios_cantidades_estudiantes');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ficha_actualizacion', function (Blueprint $table) {
            $table->dropColumn('estado_estudiantes_en_momento_ficha');
        });
    }
};
