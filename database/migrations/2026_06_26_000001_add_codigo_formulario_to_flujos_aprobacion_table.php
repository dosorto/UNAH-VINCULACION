<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('flujos_aprobacion', 'codigo_formulario')) {
            Schema::table('flujos_aprobacion', function (Blueprint $table) {
                $table->string('codigo_formulario', 80)->nullable()->after('tipo_programa_id')->index();
            });
        }

        $actionIds = DB::table('vinculacion_tipos_accion')
            ->whereIn('codigo', [
                'DESARROLLO_LOCAL_REGIONAL',
                'EDUCACION_NO_FORMAL',
                'VOLUNTARIADO',
            ])
            ->pluck('id', 'codigo');

        $formCodesByAction = [
            'DESARROLLO_LOCAL_REGIONAL' => 'FORM-DVUS-001',
            'EDUCACION_NO_FORMAL' => 'FORM-DVUS-018',
            'VOLUNTARIADO' => 'FORM-DVUS-015',
        ];

        foreach ($formCodesByAction as $actionCode => $formCode) {
            $actionId = $actionIds->get($actionCode);

            if (! $actionId) {
                continue;
            }

            DB::table('flujos_aprobacion')
                ->where('proceso', 'PROYECTO')
                ->where('tipo_accion_id', $actionId)
                ->whereNull('codigo_formulario')
                ->update([
                    'codigo_formulario' => $formCode,
                    'updated_at' => now(),
                ]);
        }

        DB::table('flujos_aprobacion')
            ->where('proceso', 'PPS_SERVICIO_SOCIAL')
            ->whereNull('codigo_formulario')
            ->update([
                'codigo_formulario' => 'FORM-DVUS-014',
                'updated_at' => now(),
            ]);
    }

    public function down(): void
    {
        if (! Schema::hasColumn('flujos_aprobacion', 'codigo_formulario')) {
            return;
        }

        Schema::table('flujos_aprobacion', function (Blueprint $table) {
            $table->dropIndex(['codigo_formulario']);
            $table->dropColumn('codigo_formulario');
        });
    }
};
