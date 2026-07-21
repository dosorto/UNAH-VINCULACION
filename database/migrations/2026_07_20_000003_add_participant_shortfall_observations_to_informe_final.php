<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('informe_final_grupos_estudiantes', function (Blueprint $table) {
            $table->text('observacion_no_cumplimiento')->nullable();
        });

        Schema::table('informe_final_proyectos', function (Blueprint $table) {
            $table->text('observacion_voluntarios_no_incorporados')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('informe_final_grupos_estudiantes', function (Blueprint $table) {
            $table->dropColumn('observacion_no_cumplimiento');
        });

        Schema::table('informe_final_proyectos', function (Blueprint $table) {
            $table->dropColumn('observacion_voluntarios_no_incorporados');
        });
    }
};
