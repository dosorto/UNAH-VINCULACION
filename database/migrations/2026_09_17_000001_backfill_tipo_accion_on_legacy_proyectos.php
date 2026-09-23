<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Asigna el tipo de acción a los proyectos heredados que no lo tienen.
 *
 * Los proyectos importados del sistema anterior son todos de desarrollo local
 * y regional (FORM-DVUS-001), pero llegaron con `tipo_accion_id` nulo. Sin
 * tipo no resuelven el flujo del FORM-DVUS-001, no pueden abrir el informe
 * final INF-001 y el panel los agrupa como «Proyectos sin tipo de acción».
 * Adaptarlos al flujo no lo corrige: la adopción solo asigna el flujo.
 *
 * Esta migración se ejecutó en algunas bases el 17 de septiembre de 2026 sin
 * llegar al repositorio; se restituye con el mismo nombre para que las bases
 * que ya la registraron no la repitan.
 */
return new class extends Migration
{
    public function up(): void
    {
        $desarrolloLocal = DB::table('vinculacion_tipos_accion')
            ->where('codigo', 'DESARROLLO_LOCAL_REGIONAL')
            ->value('id');

        if (! $desarrolloLocal) {
            return;
        }

        // Sin tocar updated_at: no es una edición del proyecto.
        DB::table('proyecto')
            ->whereNull('tipo_accion_id')
            ->update(['tipo_accion_id' => $desarrolloLocal]);
    }

    public function down(): void
    {
        // Irreversible: no queda registro de qué proyectos no tenían tipo, y
        // vaciarlo a todos rompería los que se registraron con el suyo.
    }
};
