<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Agregar campos de indicador y medio de verificación a la tabla resultado_esperado
        Schema::table('resultado_esperado', function (Blueprint $table) {
            $table->renameColumn('nombre', 'nombre_resultado');
            $table->string('nombre_indicador')->after('nombre_resultado');
            $table->string('nombre_medio_verificacion')->after('nombre_indicador');
        });
    }

    public function down(): void
    {
        Schema::table('resultado_esperado', function (Blueprint $table) {
            $table->renameColumn('nombre_resultado', 'nombre');
            $table->dropColumn(['nombre_indicador', 'nombre_medio_verificacion']);
        });
    }
};
