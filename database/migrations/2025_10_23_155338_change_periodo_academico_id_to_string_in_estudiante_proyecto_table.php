<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('estudiante_proyecto', function (Blueprint $table) {
            // Eliminar la foreign key constraint existente
            $table->dropForeign('estudiante_proyecto_periodo_academico_id_foreign');
            
            // Cambiar el tipo de columna de integer a string
            $table->string('periodo_academico_id', 50)->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('estudiante_proyecto', function (Blueprint $table) {
            // Revertir el cambio: cambiar de string a integer
            $table->unsignedBigInteger('periodo_academico_id')->nullable()->change();
            
            // Recrear la foreign key constraint
            $table->foreign('periodo_academico_id', 'estudiante_proyecto_periodo_academico_id_foreign')
                  ->references('id')->on('periodos_academicos');
        });
    }
};
