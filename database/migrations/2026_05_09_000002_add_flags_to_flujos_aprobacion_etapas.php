<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('flujos_aprobacion_etapas', function (Blueprint $table) {
            $table->boolean('requiere_asignacion')
                ->default(true)
                ->after('cargo_firma_id');
            $table->boolean('emisor_define_destinatario')
                ->default(false)
                ->after('requiere_asignacion');
        });
    }

    public function down(): void
    {
        Schema::table('flujos_aprobacion_etapas', function (Blueprint $table) {
            $table->dropColumn(['requiere_asignacion', 'emisor_define_destinatario']);
        });
    }
};
