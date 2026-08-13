<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Congela el valor de "requiere_asignacion" de la etapa en el momento en
     * que se crea la firma, para que editar el flujo después no cambie si una
     * firma en curso puede reasignarse (antes se leía en vivo desde
     * flujos_aprobacion_etapas, afectando instancias ya enviadas).
     */
    public function up(): void
    {
        Schema::table('firma_proyecto', function (Blueprint $table) {
            $table->boolean('requiere_asignacion')->nullable()->after('responsable_usuario_id');
        });
    }

    public function down(): void
    {
        Schema::table('firma_proyecto', function (Blueprint $table) {
            $table->dropColumn('requiere_asignacion');
        });
    }
};
