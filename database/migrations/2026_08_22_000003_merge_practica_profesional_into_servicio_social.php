<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * "Practica Profesional" es una modalidad duplicada de "Servicio Social o PPS".
     * Se fusionan los registros existentes antes de eliminar el valor del enum.
     */
    public function up(): void
    {
        DB::table('estudiante_proyecto')
            ->where('tipo_participacion_estudiante', 'Practica Profesional')
            ->update(['tipo_participacion_estudiante' => 'Servicio Social o PPS']);

        DB::statement("ALTER TABLE estudiante_proyecto MODIFY COLUMN tipo_participacion_estudiante
            ENUM('Servicio Social o PPS', 'Voluntariado', 'Practica Asignatura')");
    }

    /**
     * Reverse the migrations.
     *
     * NOTA: la fusión de datos realizada en up() no es reversible — los registros
     * que originalmente tenían 'Practica Profesional' quedarán como
     * 'Servicio Social o PPS' incluso después de revertir esta migración, ya que
     * no es posible distinguir cuáles filas fueron modificadas.
     */
    public function down(): void
    {
        DB::statement("ALTER TABLE estudiante_proyecto MODIFY COLUMN tipo_participacion_estudiante
            ENUM('Servicio Social o PPS', 'Practica Profesional', 'Voluntariado', 'Practica Asignatura')");
    }
};
