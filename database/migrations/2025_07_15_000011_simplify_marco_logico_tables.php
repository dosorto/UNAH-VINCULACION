<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Actualizar tabla resultado_esperado
        Schema::table('resultado_esperado', function (Blueprint $table) {
            $table->renameColumn('descripcion', 'nombre');
        });

        // Actualizar tabla indicador_resultado - eliminar campos innecesarios
        Schema::table('indicador_resultado', function (Blueprint $table) {
            $table->dropColumn(['descripcion', 'meta']);
        });

        // Actualizar tabla medio_verificacion - eliminar campos innecesarios
        Schema::table('medio_verificacion', function (Blueprint $table) {
            $table->dropColumn(['tipo', 'descripcion', 'archivo']);
        });
    }

    public function down(): void
    {
        // Revertir cambios en resultado_esperado
        Schema::table('resultado_esperado', function (Blueprint $table) {
            $table->renameColumn('nombre', 'descripcion');
        });

        // Revertir cambios en indicador_resultado
        Schema::table('indicador_resultado', function (Blueprint $table) {
            $table->longText('descripcion')->after('nombre');
            $table->string('meta')->after('descripcion');
        });

        // Revertir cambios en medio_verificacion
        Schema::table('medio_verificacion', function (Blueprint $table) {
            $table->enum('tipo', ['documento', 'fotografia', 'video', 'informe', 'acta', 'certificado', 'otro'])->after('nombre');
            $table->longText('descripcion')->after('tipo');
            $table->string('archivo')->nullable()->after('descripcion');
        });
    }
};
