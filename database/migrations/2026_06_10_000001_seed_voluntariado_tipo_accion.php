<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('vinculacion_tipos_accion')->updateOrInsert(
            ['codigo' => 'VOLUNTARIADO'],
            [
                'nombre' => 'Proyectos de Voluntariado Académico',
                'descripcion' => 'Registro de proyectos de voluntariado académico (FORM-DVUS-015): acciones de vinculación donde la comunidad universitaria participa de forma voluntaria en beneficio de comunidades, con marco lógico, equipo ejecutor, contraparte y presupuesto.',
                'badge' => 'Disponible',
                'icono' => 'graduacion',
                'activo' => true,
                'orden' => 7,
                'updated_at' => now(),
                'created_at' => now(),
            ]
        );
    }

    public function down(): void
    {
        DB::table('vinculacion_tipos_accion')
            ->where('codigo', 'VOLUNTARIADO')
            ->delete();
    }
};
