<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Los aportes de cada contraparte del INF-001 deben escribirse explícitamente (0 es válido):
     * con DEFAULT 0 no se distingue "no se registró" de "el aporte fue cero".
     */
    public function up(): void
    {
        Schema::table('informe_final_contrapartes', function (Blueprint $table) {
            $table->decimal('aporte_monetario', 15, 2)->nullable()->default(null)->change();
            $table->decimal('aporte_especie', 15, 2)->nullable()->default(null)->change();
        });

        // En borradores, las contrapartes que nunca se completaron (el modal exige los
        // compromisos cumplidos) conservan el 0 por defecto: se dejan vacías para que se registren.
        DB::table('informe_final_contrapartes')
            ->whereIn('informe_final_proyecto_id', DB::table('informe_final_proyectos')->where('estado', 'BORRADOR')->select('id'))
            ->where(fn ($query) => $query->whereNull('compromisos_cumplidos')->orWhere('compromisos_cumplidos', ''))
            ->update(['aporte_monetario' => null, 'aporte_especie' => null]);
    }

    public function down(): void
    {
        DB::table('informe_final_contrapartes')->whereNull('aporte_monetario')->update(['aporte_monetario' => 0]);
        DB::table('informe_final_contrapartes')->whereNull('aporte_especie')->update(['aporte_especie' => 0]);

        Schema::table('informe_final_contrapartes', function (Blueprint $table) {
            $table->decimal('aporte_monetario', 15, 2)->default(0)->change();
            $table->decimal('aporte_especie', 15, 2)->default(0)->change();
        });
    }
};
