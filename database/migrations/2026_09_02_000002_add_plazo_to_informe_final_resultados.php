<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Los resultados del INF-001 son de dos clases: los de corto plazo ligados a un objetivo
 * específico y los de mediano/largo plazo que cuelgan del proyecto. Se agrega `plazo` para
 * poder separarlos en la vista del paso 5.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('informe_final_resultados', function (Blueprint $table) {
            $table->enum('plazo', ['corto_plazo', 'mediano_plazo', 'largo_plazo'])
                ->default('corto_plazo')
                ->after('resultado_esperado_id');
        });

        // Retro-completar desde el resultado_esperado enlazado.
        DB::table('informe_final_resultados as ifr')
            ->join('resultado_esperado as re', 're.id', '=', 'ifr.resultado_esperado_id')
            ->whereNotNull('ifr.resultado_esperado_id')
            ->update(['ifr.plazo' => DB::raw('re.plazo')]);

        // Heurística de respaldo por si el enlace se perdió.
        DB::table('informe_final_resultados')
            ->where('objetivo_especifico', 'like', '%mediano plazo del proyecto%')
            ->update(['plazo' => 'mediano_plazo']);
        DB::table('informe_final_resultados')
            ->where('objetivo_especifico', 'like', '%largo plazo del proyecto%')
            ->update(['plazo' => 'largo_plazo']);
    }

    public function down(): void
    {
        Schema::table('informe_final_resultados', function (Blueprint $table) {
            $table->dropColumn('plazo');
        });
    }
};
