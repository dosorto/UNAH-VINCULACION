<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * En el paso 2 del FORM-DVUS-001/015 cada integrante del equipo puede ser
 * «Integrante» o «Voluntario». Los voluntarios no van en la lista del equipo
 * docente: suman en el voluntariado personal de la UNAH según su categoría y
 * sexo (ítem 13 del 001, 15 del 015).
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE empleado_proyecto MODIFY rol ENUM('Coordinador','Subcoordinador','Integrante','Voluntario') NOT NULL");
    }

    /** Los voluntarios vuelven a ser integrantes: el ENUM anterior no los admite. */
    public function down(): void
    {
        DB::table('empleado_proyecto')->where('rol', 'Voluntario')->update(['rol' => 'Integrante']);
        DB::statement("ALTER TABLE empleado_proyecto MODIFY rol ENUM('Coordinador','Subcoordinador','Integrante') NOT NULL");
    }
};
