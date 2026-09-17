<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * El INF-001 copia `proyecto.programa_pertenece` y `proyecto.region` —ambas
 * LONGTEXT, sin límite práctico— a columnas VARCHAR(255) del informe final.
 * Cuando el proyecto trae un texto más largo, la inserción del borrador muere
 * con «SQLSTATE[22001] Data too long» y el informe final no se puede abrir.
 *
 * `linea_investigacion`, que nace del mismo tipo de campo libre, ya era TEXT;
 * esto alinea a las otras dos con ese criterio en vez de truncar el dato del
 * proyecto, que es la fuente oficial.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('informe_final_proyectos', function (Blueprint $table) {
            $table->text('programa_vinculacion')->nullable()->change();
            $table->text('region')->nullable()->change();
        });
    }

    public function down(): void
    {
        // Volver a VARCHAR(255) truncaría cualquier valor más largo que se
        // haya guardado mientras la columna era TEXT, así que se recortan
        // antes de estrechar la columna para que la reversión no falle.
        \Illuminate\Support\Facades\DB::table('informe_final_proyectos')
            ->whereRaw('CHAR_LENGTH(programa_vinculacion) > 255')
            ->update(['programa_vinculacion' => \Illuminate\Support\Facades\DB::raw('LEFT(programa_vinculacion, 255)')]);

        \Illuminate\Support\Facades\DB::table('informe_final_proyectos')
            ->whereRaw('CHAR_LENGTH(region) > 255')
            ->update(['region' => \Illuminate\Support\Facades\DB::raw('LEFT(region, 255)')]);

        Schema::table('informe_final_proyectos', function (Blueprint $table) {
            $table->string('programa_vinculacion', 255)->nullable()->change();
            $table->string('region', 255)->nullable()->change();
        });
    }
};
