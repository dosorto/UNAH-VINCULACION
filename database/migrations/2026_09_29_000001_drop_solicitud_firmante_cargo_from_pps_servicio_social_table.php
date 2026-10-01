<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * La solicitud de práctica la firma siempre el coordinador que llena el formulario, con su
 * cargo de coordinador: ya no se captura un cargo por registro.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('pps_servicio_social', 'solicitud_firmante_cargo')) {
            Schema::table('pps_servicio_social', function (Blueprint $table): void {
                $table->dropColumn('solicitud_firmante_cargo');
            });
        }
    }

    public function down(): void
    {
        Schema::table('pps_servicio_social', function (Blueprint $table): void {
            $table->string('solicitud_firmante_cargo')->nullable()->after('solicitud_lugar');
        });
    }
};
