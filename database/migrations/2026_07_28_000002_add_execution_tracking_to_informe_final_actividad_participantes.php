<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('informe_final_actividad_participantes', function (Blueprint $table) {
            $table->string('origen', 20)->default('PLANIFICADO')->after('orden');
            $table->string('estado_participacion', 20)->default('activo')->after('origen');
            $table->text('observacion_no_participacion')->nullable()->after('estado_participacion');
            $table->timestamp('removido_en')->nullable()->after('observacion_no_participacion');
            $table->foreignId('removido_por')->nullable()->after('removido_en')->constrained('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('informe_final_actividad_participantes', function (Blueprint $table) {
            $table->dropConstrainedForeignId('removido_por');
            $table->dropColumn(['origen', 'estado_participacion', 'observacion_no_participacion', 'removido_en']);
        });
    }
};
