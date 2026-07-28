<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\QueryException;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::transaction(function (): void {
            $canonicos = [];

            foreach (DB::table('ejes_prioritarios_unah')->orderBy('id')->get() as $eje) {
                $clave = mb_strtolower(trim($eje->nombre), 'UTF-8');

                if (! isset($canonicos[$clave])) {
                    $canonicos[$clave] = (int) $eje->id;

                    continue;
                }

                $canonicoId = $canonicos[$clave];

                foreach ([
                    ['eje_prioritario_proyecto', 'ejes_prioritarios_unah_id'],
                    ['enf_accion_ejes_unah', 'eje_prioritario_unah_id'],
                ] as [$tabla, $columna]) {
                    if (! Schema::hasTable($tabla)) {
                        continue;
                    }

                    DB::table($tabla)
                        ->where($columna, $eje->id)
                        ->orderBy('id')
                        ->pluck('id')
                        ->each(function (int $id) use ($tabla, $columna, $canonicoId): void {
                            try {
                                DB::table($tabla)->where('id', $id)->update([$columna => $canonicoId]);
                            } catch (QueryException) {
                                DB::table($tabla)->where('id', $id)->delete();
                            }
                        });
                }

                DB::table('ejes_prioritarios_unah')->where('id', $eje->id)->delete();
            }
        });

        Schema::table('ejes_prioritarios_unah', function (Blueprint $table): void {
            $table->unique('nombre', 'eje_prioritario_nombre_unique');
        });
    }

    public function down(): void
    {
        Schema::table('ejes_prioritarios_unah', function (Blueprint $table): void {
            $table->dropUnique('eje_prioritario_nombre_unique');
        });
    }
};
