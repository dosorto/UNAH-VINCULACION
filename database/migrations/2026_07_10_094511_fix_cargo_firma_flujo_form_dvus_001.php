<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Las 3 etapas del flujo FORM-DVUS-001 (Desarrollo local y regional) quedaron
 * guardadas con el mismo cargo_firma_id (5 = "Revisor Vinculacion" -> estado
 * "En revision") en vez del cargo_firma_id correspondiente a cada rol
 * revisor. Esto hacía que el estado_proyecto resultante no coincidiera con
 * el que buscan los dashboards de pendientes por rol.
 */
return new class extends Migration
{
    /**
     * Etapa "nombre" => [cargo_firma_id correcto, tipo_estado esperado]
     * (cargo_firma con descripcion="Proyecto", ver database: ids 1-6)
     */
    private array $etapaCargoFirma = [
        'coordinador proyecto' => 1, // tipo_cargo_firma "Coordinador Proyecto" -> TipoEstado "Coordinador Proyecto"
        'jefe departamento'    => 3, // tipo_cargo_firma "Jefe Departamento"    -> TipoEstado "Jefe Departamento"
        'director vinculacion' => 6, // tipo_cargo_firma "Director Vinculacion" -> TipoEstado "En revision final"
    ];

    private int $cargoFirmaIncorrecto = 5;

    public function up(): void
    {
        $flujo = DB::table('flujos_aprobacion')
            ->where('codigo_formulario', 'FORM-DVUS-001')
            ->where('proceso', 'PROYECTO')
            ->first();

        if (! $flujo) {
            return;
        }

        $etapas = DB::table('flujos_aprobacion_etapas')
            ->where('flujo_aprobacion_id', $flujo->id)
            ->get();

        foreach ($etapas as $etapa) {
            $cargoFirmaCorrecto = $this->etapaCargoFirma[$etapa->nombre] ?? null;

            if (! $cargoFirmaCorrecto || (int) $etapa->cargo_firma_id !== $this->cargoFirmaIncorrecto) {
                continue;
            }

            DB::table('flujos_aprobacion_etapas')
                ->where('id', $etapa->id)
                ->update(['cargo_firma_id' => $cargoFirmaCorrecto]);

            $this->corregirEstadoProyectosEnEtapa($etapa->id, $cargoFirmaCorrecto);
        }
    }

    /**
     * Recalcula el estado_proyecto actual de los proyectos que tienen una
     * firma "Pendiente" en esta etapa, para que quede consistente con el
     * cargo_firma corregido (sin esto, el proyecto ya enviado por el
     * docente quedaría con el estado viejo hasta que se reenviara).
     */
    private function corregirEstadoProyectosEnEtapa(int $etapaId, int $cargoFirmaId): void
    {
        $tipoEstadoId = DB::table('cargo_firma')->where('id', $cargoFirmaId)->value('tipo_estado_id');

        if (! $tipoEstadoId) {
            return;
        }

        $proyectoIds = DB::table('firma_proyecto')
            ->where('flujo_aprobacion_etapa_id', $etapaId)
            ->where('estado_revision', 'Pendiente')
            ->where('firmable_type', \App\Models\Proyecto\Proyecto::class)
            ->whereNull('deleted_at')
            ->pluck('firmable_id');

        foreach ($proyectoIds as $proyectoId) {
            $estadoActual = DB::table('estado_proyecto')
                ->where('estadoable_type', \App\Models\Proyecto\Proyecto::class)
                ->where('estadoable_id', $proyectoId)
                ->where('es_actual', true)
                ->first();

            if (! $estadoActual || (int) $estadoActual->tipo_estado_id === (int) $tipoEstadoId) {
                continue;
            }

            DB::table('estado_proyecto')
                ->where('id', $estadoActual->id)
                ->update(['tipo_estado_id' => $tipoEstadoId]);
        }
    }

    public function down(): void
    {
        $flujo = DB::table('flujos_aprobacion')
            ->where('codigo_formulario', 'FORM-DVUS-001')
            ->where('proceso', 'PROYECTO')
            ->first();

        if (! $flujo) {
            return;
        }

        DB::table('flujos_aprobacion_etapas')
            ->where('flujo_aprobacion_id', $flujo->id)
            ->whereIn('nombre', array_keys($this->etapaCargoFirma))
            ->update(['cargo_firma_id' => $this->cargoFirmaIncorrecto]);
    }
};
