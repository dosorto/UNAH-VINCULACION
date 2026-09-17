<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * El territorio de ejecución del INF-001 puede abarcar más de un país, departamento y
 * municipio (igual que el registro del proyecto). Se agregan tablas pivote para
 * departamento/municipio y se convierte `pais` a arreglo JSON. Las columnas de texto
 * `departamento_territorial` / `municipio` se conservan como resumen legible.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inf_final_departamento', function (Blueprint $table) {
            $table->id();
            $table->foreignId('informe_final_proyecto_id')->constrained('informe_final_proyectos', indexName: 'iff_dep_informe_fk')->cascadeOnDelete();
            $table->foreignId('departamento_id')->constrained('departamento', indexName: 'iff_dep_departamento_fk')->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['informe_final_proyecto_id', 'departamento_id'], 'iff_dep_unq');
        });

        Schema::create('inf_final_municipio', function (Blueprint $table) {
            $table->id();
            $table->foreignId('informe_final_proyecto_id')->constrained('informe_final_proyectos', indexName: 'iff_mun_informe_fk')->cascadeOnDelete();
            $table->foreignId('municipio_id')->constrained('municipio', indexName: 'iff_mun_municipio_fk')->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['informe_final_proyecto_id', 'municipio_id'], 'iff_mun_unq');
        });

        // Semilla de las pivotes con el valor único que existía en las columnas FK.
        foreach (DB::table('informe_final_proyectos')->whereNotNull('departamento_territorial_id')->pluck('departamento_territorial_id', 'id') as $informeId => $departamentoId) {
            DB::table('inf_final_departamento')->insertOrIgnore([
                'informe_final_proyecto_id' => $informeId,
                'departamento_id' => $departamentoId,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
        foreach (DB::table('informe_final_proyectos')->whereNotNull('municipio_id')->pluck('municipio_id', 'id') as $informeId => $municipioId) {
            DB::table('inf_final_municipio')->insertOrIgnore([
                'informe_final_proyecto_id' => $informeId,
                'municipio_id' => $municipioId,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // Normalizar `pais` a JSON antes de cambiar el tipo de columna.
        foreach (DB::table('informe_final_proyectos')->get(['id', 'pais']) as $fila) {
            $valor = $fila->pais;
            $normalizado = null;

            if ($valor !== null && $valor !== '') {
                $decodificado = json_decode((string) $valor, true);
                $normalizado = is_array($decodificado)
                    ? array_values(array_filter($decodificado, fn ($v) => filled($v)))
                    : [$valor];
            }

            DB::table('informe_final_proyectos')->where('id', $fila->id)->update([
                'pais' => $normalizado === null ? null : json_encode($normalizado),
            ]);
        }

        Schema::table('informe_final_proyectos', function (Blueprint $table) {
            $table->json('pais')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('informe_final_proyectos', function (Blueprint $table) {
            $table->string('pais')->nullable()->change();
        });

        foreach (DB::table('informe_final_proyectos')->get(['id', 'pais']) as $fila) {
            $decodificado = json_decode((string) $fila->pais, true);
            DB::table('informe_final_proyectos')->where('id', $fila->id)->update([
                'pais' => is_array($decodificado) ? ($decodificado[0] ?? null) : $fila->pais,
            ]);
        }

        Schema::dropIfExists('inf_final_municipio');
        Schema::dropIfExists('inf_final_departamento');
    }
};
