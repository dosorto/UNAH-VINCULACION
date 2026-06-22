<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Campos propios del formulario FORM-DVUS-015 (Voluntariado Académico).
     * Todos nullable para no afectar a los proyectos existentes de otros tipos de acción.
     */
    public function up(): void
    {
        Schema::table('proyecto', function (Blueprint $table) {
            if (!Schema::hasColumn('proyecto', 'tematica_principal')) {
                $table->string('tematica_principal', 120)->nullable()->after('modalidad_id');
            }
            if (!Schema::hasColumn('proyecto', 'tematica_principal_otro')) {
                $table->string('tematica_principal_otro', 180)->nullable()->after('tematica_principal');
            }
            if (!Schema::hasColumn('proyecto', 'metodologia_seguimiento')) {
                $table->json('metodologia_seguimiento')->nullable()->after('tematica_principal_otro');
            }
            if (!Schema::hasColumn('proyecto', 'experiencia_conocimientos_teoricos')) {
                $table->text('experiencia_conocimientos_teoricos')->nullable()->after('alineamiento_reforma');
            }
            if (!Schema::hasColumn('proyecto', 'experiencia_habilidades_tecnicas')) {
                $table->text('experiencia_habilidades_tecnicas')->nullable()->after('experiencia_conocimientos_teoricos');
            }
            if (!Schema::hasColumn('proyecto', 'experiencia_competencias_blandas')) {
                $table->text('experiencia_competencias_blandas')->nullable()->after('experiencia_habilidades_tecnicas');
            }
        });
    }

    public function down(): void
    {
        Schema::table('proyecto', function (Blueprint $table) {
            $columnas = [
                'tematica_principal',
                'tematica_principal_otro',
                'metodologia_seguimiento',
                'experiencia_conocimientos_teoricos',
                'experiencia_habilidades_tecnicas',
                'experiencia_competencias_blandas',
            ];

            foreach ($columnas as $columna) {
                if (Schema::hasColumn('proyecto', $columna)) {
                    $table->dropColumn($columna);
                }
            }
        });
    }
};
