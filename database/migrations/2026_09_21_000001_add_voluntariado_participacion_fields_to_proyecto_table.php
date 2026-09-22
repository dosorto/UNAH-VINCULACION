<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Ítems 15 (Voluntariado personal de la UNAH) y 16 (Voluntariado internacional)
     * del FORM-DVUS-015. Son cantidades capturadas directamente en el formulario;
     * nullable para no afectar a los proyectos existentes de otros tipos de acción.
     */
    private const COLUMNAS = [
        'vol_profesores_hora_hombres',
        'vol_profesores_hora_mujeres',
        'vol_personal_administrativo_hombres',
        'vol_personal_administrativo_mujeres',
        'vol_personal_servicio_hombres',
        'vol_personal_servicio_mujeres',
        'vol_asistentes_tecnicos_hombres',
        'vol_asistentes_tecnicos_mujeres',
        'vol_int_grado_hombres',
        'vol_int_grado_mujeres',
        'vol_int_maestria_hombres',
        'vol_int_maestria_mujeres',
        'vol_int_doctorado_hombres',
        'vol_int_doctorado_mujeres',
    ];

    public function up(): void
    {
        Schema::table('proyecto', function (Blueprint $table) {
            $anterior = 'experiencia_competencias_blandas';

            foreach (self::COLUMNAS as $columna) {
                if (!Schema::hasColumn('proyecto', $columna)) {
                    $table->unsignedInteger($columna)->nullable()->after($anterior);
                }

                $anterior = $columna;
            }
        });
    }

    public function down(): void
    {
        Schema::table('proyecto', function (Blueprint $table) {
            foreach (self::COLUMNAS as $columna) {
                if (Schema::hasColumn('proyecto', $columna)) {
                    $table->dropColumn($columna);
                }
            }
        });
    }
};
