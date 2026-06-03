<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        $actions = DB::table('vinculacion_tipos_accion')
            ->whereIn('codigo', ['DESARROLLO_LOCAL_REGIONAL', 'EDUCACION_NO_FORMAL'])
            ->get()
            ->keyBy('codigo');

        $templateFlow = DB::table('flujos_aprobacion')
            ->where('proceso', 'PROYECTO')
            ->where(function ($query) use ($actions) {
                $query->where('codigo', 'PROYECTO_DEFAULT')
                    ->orWhere('tipo_accion_id', $actions->get('DESARROLLO_LOCAL_REGIONAL')?->id);
            })
            ->orderBy('id')
            ->first();

        foreach ($actions as $action) {
            $existing = DB::table('flujos_aprobacion')
                ->where('proceso', 'PROYECTO')
                ->where('tipo_accion_id', $action->id)
                ->first();

            if ($existing) {
                if ($action->codigo === 'DESARROLLO_LOCAL_REGIONAL' && $existing->codigo === 'PROYECTO_DEFAULT') {
                    DB::table('flujos_aprobacion')
                        ->where('id', $existing->id)
                        ->update([
                            'codigo' => $this->uniqueFlowCode('PROYECTO_'.$action->codigo, $existing->id),
                            'updated_at' => now(),
                        ]);
                }

                continue;
            }

            $flowId = DB::table('flujos_aprobacion')->insertGetId([
                'codigo' => $this->uniqueFlowCode('PROYECTO_'.$action->codigo),
                'nombre' => 'Flujo de aprobacion de '.$action->nombre,
                'proceso' => 'PROYECTO',
                'tipo_accion_id' => $action->id,
                'tipo_programa_id' => null,
                'descripcion' => 'Flujo configurable para '.$action->nombre.'.',
                'activo' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            if ($templateFlow) {
                $this->copyStages((int) $templateFlow->id, $flowId);
            } else {
                $this->createFallbackStage($flowId);
            }
        }
    }

    public function down(): void
    {
        $educationActionId = DB::table('vinculacion_tipos_accion')
            ->where('codigo', 'EDUCACION_NO_FORMAL')
            ->value('id');

        if ($educationActionId) {
            DB::table('flujos_aprobacion')
                ->where('proceso', 'PROYECTO')
                ->where('tipo_accion_id', $educationActionId)
                ->where('codigo', 'like', 'PROYECTO_EDUCACION_NO_FORMAL%')
                ->delete();
        }
    }

    private function copyStages(int $sourceFlowId, int $targetFlowId): void
    {
        $stages = DB::table('flujos_aprobacion_etapas')
            ->where('flujo_aprobacion_id', $sourceFlowId)
            ->orderBy('orden')
            ->get();

        foreach ($stages as $stage) {
            DB::table('flujos_aprobacion_etapas')->insert([
                'flujo_aprobacion_id' => $targetFlowId,
                'orden' => $stage->orden,
                'codigo' => $stage->codigo,
                'aplica_inscripcion' => $stage->aplica_inscripcion ?? true,
                'aplica_informe_intermedio' => $stage->aplica_informe_intermedio ?? false,
                'aplica_cierre_proyecto' => $stage->aplica_cierre_proyecto ?? false,
                'nombre' => $stage->nombre,
                'tipo_etapa' => $stage->tipo_etapa ?? 'REVISION',
                'rol_revisor_id' => $stage->rol_revisor_id ?? null,
                'usuario_responsable_id' => $stage->usuario_responsable_id ?? null,
                'cargo_firma_id' => $stage->cargo_firma_id,
                'requiere_asignacion' => $stage->requiere_asignacion ?? true,
                'emisor_define_destinatario' => $stage->emisor_define_destinatario ?? false,
                'activo' => $stage->activo,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    private function createFallbackStage(int $flowId): void
    {
        $cargoFirmaId = DB::table('cargo_firma')->where('descripcion', 'Proyecto')->orderBy('id')->value('id');

        if (! $cargoFirmaId) {
            return;
        }

        DB::table('flujos_aprobacion_etapas')->insert([
            'flujo_aprobacion_id' => $flowId,
            'orden' => 1,
            'codigo' => 'ETAPA_01',
            'nombre' => 'Revision inicial',
            'tipo_etapa' => 'REVISION',
            'cargo_firma_id' => $cargoFirmaId,
            'requiere_asignacion' => true,
            'emisor_define_destinatario' => false,
            'activo' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function uniqueFlowCode(string $base, ?int $ignoreId = null): string
    {
        $base = Str::of($base)->upper()->replaceMatches('/[^A-Z0-9]+/', '_')->trim('_')->value();
        $candidate = $base;
        $suffix = 2;

        while (DB::table('flujos_aprobacion')
            ->where('codigo', $candidate)
            ->when($ignoreId, fn ($query) => $query->where('id', '!=', $ignoreId))
            ->exists()
        ) {
            $candidate = $base.'_'.$suffix;
            $suffix++;
        }

        return $candidate;
    }
};
