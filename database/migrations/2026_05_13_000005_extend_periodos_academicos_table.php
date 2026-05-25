<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('periodos_academicos', function (Blueprint $table) {
            $table->unsignedInteger('anio')->nullable()->after('nombre');
            $table->unsignedTinyInteger('periodo')->nullable()->after('anio');
            $table->date('inicio')->nullable()->after('periodo');
            $table->date('fin')->nullable()->after('inicio');
        });
    }

    public function down(): void
    {
        Schema::table('periodos_academicos', function (Blueprint $table) {
            $table->dropColumn(['anio', 'periodo', 'inicio', 'fin']);
        });
    }
};
