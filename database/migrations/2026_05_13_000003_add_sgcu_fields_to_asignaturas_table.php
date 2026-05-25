<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('asignaturas', function (Blueprint $table) {
            $table->decimal('creditos_academicos', 5, 2)->default(0)->after('nombre');
            $table->unsignedInteger('horas_academicas')->default(0)->after('creditos_academicos');
            $table->string('ruta_documento_descripcion_minima', 500)->nullable()->after('horas_academicas');
            $table->boolean('activa')->default(true)->after('ruta_documento_descripcion_minima');
        });
    }

    public function down(): void
    {
        Schema::table('asignaturas', function (Blueprint $table) {
            $table->dropColumn(['creditos_academicos', 'horas_academicas', 'ruta_documento_descripcion_minima', 'activa']);
        });
    }
};
