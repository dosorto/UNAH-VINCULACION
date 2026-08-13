<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('resultado_esperado', function (Blueprint $table) {
            $table->foreignId('objetivo_especifico_id')->nullable()->change();
            $table->foreignId('proyecto_id')->nullable()->after('objetivo_especifico_id')
                ->constrained('proyecto')->cascadeOnDelete();
        });

        // Los resultados de mediano/largo plazo ya no dependen de un objetivo específico:
        // se re-adjuntan directamente al proyecto, preservando los que ya existían.
        DB::table('resultado_esperado as re')
            ->join('objetivo_especifico as oe', 'oe.id', '=', 're.objetivo_especifico_id')
            ->whereIn('re.plazo', ['mediano_plazo', 'largo_plazo'])
            ->select('re.id', 'oe.proyecto_id')
            ->orderBy('re.id')
            ->chunk(200, function ($rows) {
                foreach ($rows as $row) {
                    DB::table('resultado_esperado')
                        ->where('id', $row->id)
                        ->update([
                            'proyecto_id' => $row->proyecto_id,
                            'objetivo_especifico_id' => null,
                        ]);
                }
            });
    }

    public function down(): void
    {
        // No revertimos el backfill: no es posible reconstruir de forma confiable a qué
        // objetivo específico pertenecía cada resultado de mediano/largo plazo una vez
        // desasociado. Solo removemos la columna agregada.
        Schema::table('resultado_esperado', function (Blueprint $table) {
            $table->dropConstrainedForeignId('proyecto_id');
        });
    }
};
