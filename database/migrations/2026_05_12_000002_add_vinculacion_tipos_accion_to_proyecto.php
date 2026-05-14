<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('proyecto', function (Blueprint $table) {
            $table->foreignId('tipo_accion_id')
                ->nullable()
                ->after('modalidad_id')
                ->constrained('vinculacion_tipos_accion')
                ->nullOnDelete();
            $table->foreignId('tipo_accion_opcion_id')
                ->nullable()
                ->after('tipo_accion_id')
                ->constrained('vinculacion_tipos_accion_opciones')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('proyecto', function (Blueprint $table) {
            $table->dropConstrainedForeignId('tipo_accion_opcion_id');
            $table->dropConstrainedForeignId('tipo_accion_id');
        });
    }
};
