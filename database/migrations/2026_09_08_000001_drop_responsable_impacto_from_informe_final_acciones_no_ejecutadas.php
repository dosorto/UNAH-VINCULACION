<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * El formulario oficial INF-001 (sección VII) solo contempla:
     * resultado previsto, actividad planificada, explicación y afectación al proyecto.
     * Las columnas "responsable" e "impacto" no forman parte del instrumento.
     */
    public function up(): void
    {
        Schema::table('informe_final_acciones_no_ejecutadas', function (Blueprint $table) {
            if (Schema::hasColumn('informe_final_acciones_no_ejecutadas', 'responsable')) {
                $table->dropColumn('responsable');
            }
            if (Schema::hasColumn('informe_final_acciones_no_ejecutadas', 'impacto')) {
                $table->dropColumn('impacto');
            }
        });
    }

    public function down(): void
    {
        Schema::table('informe_final_acciones_no_ejecutadas', function (Blueprint $table) {
            if (! Schema::hasColumn('informe_final_acciones_no_ejecutadas', 'responsable')) {
                $table->string('responsable')->nullable();
            }
            if (! Schema::hasColumn('informe_final_acciones_no_ejecutadas', 'impacto')) {
                $table->enum('impacto', ['bajo', 'medio', 'alto'])->default('medio');
            }
        });
    }
};
