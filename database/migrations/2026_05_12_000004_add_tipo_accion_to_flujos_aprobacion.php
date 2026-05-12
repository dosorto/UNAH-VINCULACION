<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('flujos_aprobacion', function (Blueprint $table) {
            $table->foreignId('tipo_accion_id')
                ->nullable()
                ->after('proceso')
                ->constrained('vinculacion_tipos_accion')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('flujos_aprobacion', function (Blueprint $table) {
            $table->dropConstrainedForeignId('tipo_accion_id');
        });
    }
};
