<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tipos_programa', function (Blueprint $table) {
            $table->string('modalidad_duracion', 20)->default('HORAS')->after('nombre');
            $table->unsignedInteger('dias_minimos')->nullable()->after('horas_maximas');
            $table->unsignedInteger('dias_maximos')->nullable()->after('dias_minimos');
            $table->unsignedInteger('horas_minimas_por_dia')->nullable()->after('dias_maximos');
            $table->boolean('dias_consecutivos')->default(false)->after('horas_minimas_por_dia');
        });
    }

    public function down(): void
    {
        Schema::table('tipos_programa', function (Blueprint $table) {
            $table->dropColumn([
                'modalidad_duracion',
                'dias_minimos',
                'dias_maximos',
                'horas_minimas_por_dia',
                'dias_consecutivos',
            ]);
        });
    }
};
