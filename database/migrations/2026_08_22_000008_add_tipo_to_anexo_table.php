<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('anexo', function (Blueprint $table) {
            $table->foreignId('tipo_anexo_id')
                ->nullable()
                ->after('proyecto_id')
                ->constrained('tipos_anexo')
                ->nullOnDelete();
            $table->string('nombre_archivo')->nullable()->after('documento_url');
            $table->string('detalle')->nullable()->after('nombre_archivo');
        });
    }

    public function down(): void
    {
        Schema::table('anexo', function (Blueprint $table) {
            $table->dropForeign(['tipo_anexo_id']);
            $table->dropColumn(['tipo_anexo_id', 'nombre_archivo', 'detalle']);
        });
    }
};
