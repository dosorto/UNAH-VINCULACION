<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const ACTION_CODE = 'PASANTIAS';

    private const FLOW_CODE = 'PASANTIAS_FORM_DVUS_013';

    private const PROCESS = 'PASANTIAS_DEFAULT';

    private const FORM_CODE = 'FORM-DVUS-013';

    public function up(): void
    {
        if (! Schema::hasTable('vinculacion_tipos_accion') || ! Schema::hasTable('flujos_aprobacion')) {
            return;
        }

        $now = now();
        DB::table('vinculacion_tipos_accion')->updateOrInsert(
            ['codigo' => self::ACTION_CODE],
            [
                'nombre' => 'Pasantías universitarias',
                'descripcion' => 'Registro institucional de pasantías universitarias (FORM-DVUS-013).',
                'badge' => 'Disponible',
                'icono' => 'graduacion',
                'activo' => true,
                'orden' => 8,
                'updated_at' => $now,
                'created_at' => $now,
            ]
        );

        $actionId = (int) DB::table('vinculacion_tipos_accion')
            ->where('codigo', self::ACTION_CODE)
            ->value('id');

        if (! $actionId) {
            return;
        }

        DB::table('flujos_aprobacion')->updateOrInsert(
            [
                'codigo' => self::FLOW_CODE,
            ],
            [
                'nombre' => 'Flujo FORM-DVUS-013 - Pasantías',
                'proceso' => self::PROCESS,
                'tipo_accion_id' => $actionId,
                'tipo_programa_id' => null,
                'codigo_formulario' => self::FORM_CODE,
                'descripcion' => 'Flujo configurable para el FORM-DVUS-013.',
                'activo' => true,
                'updated_at' => $now,
                'created_at' => $now,
            ]
        );

        $flowId = (int) DB::table('flujos_aprobacion')
            ->where('codigo', self::FLOW_CODE)
            ->value('id');

        if ($flowId && Schema::hasTable('flujos_aprobacion_etapas') && ! DB::table('flujos_aprobacion_etapas')->where('flujo_aprobacion_id', $flowId)->exists()) {
            $cargoFirmaId = DB::table('cargo_firma')
                ->where('descripcion', 'Proyecto')
                ->orderBy('id')
                ->value('id');

            DB::table('flujos_aprobacion_etapas')->insert([
                'flujo_aprobacion_id' => $flowId,
                'orden' => 1,
                'codigo' => 'PASANTIAS_ETAPA_01',
                'nombre' => 'Revisión inicial de Pasantías',
                'tipo_etapa' => 'REVISION',
                'cargo_firma_id' => $cargoFirmaId,
                'requiere_asignacion' => true,
                'emisor_define_destinatario' => false,
                'activo' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('flujos_aprobacion')) {
            return;
        }

        $flowId = DB::table('flujos_aprobacion')
            ->where('codigo', self::FLOW_CODE)
            ->value('id');

        if ($flowId) {
            if (Schema::hasTable('flujos_aprobacion_etapas')) {
                DB::table('flujos_aprobacion_etapas')->where('flujo_aprobacion_id', $flowId)->delete();
            }

            DB::table('flujos_aprobacion')->where('id', $flowId)->delete();
        }

        if (Schema::hasTable('vinculacion_tipos_accion')) {
            $actionId = DB::table('vinculacion_tipos_accion')
                ->where('codigo', self::ACTION_CODE)
                ->value('id');

            if ($actionId && ! DB::table('flujos_aprobacion')->where('tipo_accion_id', $actionId)->exists()) {
                DB::table('vinculacion_tipos_accion')->where('id', $actionId)->delete();
            }
        }
    }
};
