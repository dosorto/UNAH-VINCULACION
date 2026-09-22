<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Los responsables de una acción emergente (VIII) se eligen entre las personas del
     * informe y se guardan uno por línea; un texto de 255 caracteres se queda corto.
     */
    public function up(): void
    {
        Schema::table('informe_final_acciones_emergentes', function (Blueprint $table) {
            $table->text('responsables')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('informe_final_acciones_emergentes', function (Blueprint $table) {
            $table->string('responsables')->nullable()->change();
        });
    }
};
