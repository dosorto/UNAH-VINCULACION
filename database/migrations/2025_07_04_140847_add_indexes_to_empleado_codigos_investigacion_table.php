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
        Schema::table('empleado_codigos_investigacion', function (Blueprint $table) {
            // Agregar índices con nombres específicos
            $table->index(['empleado_id', 'estado_verificacion'], 'emp_cod_inv_emp_estado_idx');
            $table->unique(['codigo_proyecto', 'empleado_id'], 'emp_cod_inv_codigo_emp_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('empleado_codigos_investigacion', function (Blueprint $table) {
            $table->dropIndex('emp_cod_inv_emp_estado_idx');
            $table->dropUnique('emp_cod_inv_codigo_emp_unique');
        });
    }
};
