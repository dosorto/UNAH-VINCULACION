<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement(
            "ALTER TABLE `firma_proyecto`
             MODIFY `estado_revision` ENUM('Pendiente', 'Rechazado', 'Aprobado', 'Anulado')
             NOT NULL DEFAULT 'Pendiente'"
        );
    }

    public function down(): void
    {
        $firmasAnuladas = DB::table('firma_proyecto')
            ->where('estado_revision', 'Anulado')
            ->exists();

        if ($firmasAnuladas) {
            throw new RuntimeException(
                'No se puede revertir la migración porque existen firmas con estado Anulado. Revise esos registros antes de continuar.'
            );
        }

        DB::statement(
            "ALTER TABLE `firma_proyecto`
             MODIFY `estado_revision` ENUM('Pendiente', 'Rechazado', 'Aprobado')
             NOT NULL DEFAULT 'Pendiente'"
        );
    }
};
