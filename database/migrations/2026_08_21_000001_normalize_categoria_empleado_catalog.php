<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const UNIQUE_INDEX = 'categoria_nombre_unique';

    public function up(): void
    {
        if (! Schema::hasTable('categoria')) {
            return;
        }

        DB::transaction(function (): void {
            $categorias = DB::table('categoria')
                ->orderByRaw('deleted_at IS NULL DESC')
                ->orderBy('id')
                ->get(['id', 'nombre', 'descripcion', 'deleted_at']);

            $categorias
                ->filter(fn ($categoria) => filled($categoria->nombre))
                ->groupBy(fn ($categoria) => mb_strtolower(trim((string) $categoria->nombre)))
                ->each(function ($duplicadas): void {
                    $principal = $duplicadas->first();
                    $idsDuplicados = $duplicadas->skip(1)->pluck('id');

                    DB::table('categoria')
                        ->where('id', $principal->id)
                        ->update(['nombre' => trim((string) $principal->nombre)]);

                    if ($idsDuplicados->isEmpty()) {
                        return;
                    }

                    if (Schema::hasTable('empleado')) {
                        DB::table('empleado')
                            ->whereIn('categoria_id', $idsDuplicados)
                            ->update(['categoria_id' => $principal->id]);
                    }

                    DB::table('categoria')->whereIn('id', $idsDuplicados)->delete();
                });
        });

        Schema::table('categoria', function (Blueprint $table): void {
            $table->unique('nombre', self::UNIQUE_INDEX);
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('categoria')) {
            return;
        }

        Schema::table('categoria', function (Blueprint $table): void {
            $table->dropUnique(self::UNIQUE_INDEX);
        });
    }
};
