<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\QueryException;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Consolida catálogos repetidos preservando todas sus referencias.
     */
    public function up(): void
    {
        DB::transaction(function (): void {
            // Las restricciones usan una colación accent/case insensitive.
            // La clave de deduplicación debe aplicar la misma equivalencia para
            // consolidar, por ejemplo, "CORTÉS" y "CORTES" antes del UNIQUE.
            $normalizar = static fn (mixed $valor): string => Str::lower(
                Str::ascii(trim((string) $valor))
            );

            $tablasRelacion = [
                'carrera_departamento_academico',
                'carrera_facultad_centro',
                'enf_certificado_carreras',
                'programas_centros_facultad',
                'proyecto_carrera',
                'proyecto_centro_facultad',
                'proyecto_departamento',
                'proyecto_depto_ac',
                'proyecto_municipio',
                'servicio_carrera',
                'servicio_centro_facultad',
                'servicio_departamento',
                'servicio_depto_ac',
                'servicio_municipio',
            ];

            $reasignar = function (
                string $tabla,
                string $columna,
                int $duplicadoId,
                int $canonicoId
            ) use ($tablasRelacion): void {
                if (! Schema::hasTable($tabla) || ! Schema::hasColumn($tabla, $columna)) {
                    return;
                }

                if (! Schema::hasColumn($tabla, 'id')) {
                    DB::table($tabla)->where($columna, $duplicadoId)->update([$columna => $canonicoId]);

                    return;
                }

                DB::table($tabla)
                    ->where($columna, $duplicadoId)
                    ->orderBy('id')
                    ->pluck('id')
                    ->each(function (int $id) use (
                        $tabla,
                        $columna,
                        $canonicoId,
                        $tablasRelacion
                    ): void {
                        try {
                            DB::table($tabla)->where('id', $id)->update([$columna => $canonicoId]);
                        } catch (QueryException $exception) {
                            if (! in_array($tabla, $tablasRelacion, true)) {
                                throw $exception;
                            }

                            // Dos filas de una tabla pivote pasan a representar
                            // la misma relación después de consolidar el catálogo.
                            DB::table($tabla)->where('id', $id)->delete();
                        }
                    });
            };

            $consolidar = function (
                string $tabla,
                callable $clave,
                array $referencias
            ) use ($reasignar): void {
                if (! Schema::hasTable($tabla)) {
                    return;
                }

                $query = DB::table($tabla);
                if (Schema::hasColumn($tabla, 'deleted_at')) {
                    $query->orderByRaw('deleted_at IS NULL DESC');
                }

                $canonicos = [];

                foreach ($query->orderBy('id')->get() as $registro) {
                    $claveNatural = $clave($registro);

                    if (! isset($canonicos[$claveNatural])) {
                        $canonicos[$claveNatural] = (int) $registro->id;

                        continue;
                    }

                    $canonicoId = $canonicos[$claveNatural];

                    foreach ($referencias as [$tablaHija, $columnaHija]) {
                        $reasignar($tablaHija, $columnaHija, (int) $registro->id, $canonicoId);
                    }

                    DB::table($tabla)->where('id', $registro->id)->delete();
                }
            };

            $consolidar(
                'modalidad',
                fn (object $fila): string => $normalizar($fila->nombre),
                [
                    ['enf_acciones', 'modalidad_id'],
                    ['informe_final_proyectos', 'modalidad_id'],
                    ['proyecto', 'modalidad_id'],
                    ['servicios_tecnologicos', 'modalidad_id'],
                ],
            );

            $consolidar(
                'pais',
                fn (object $fila): string => $normalizar($fila->codigo_iso),
                [['departamento', 'pais_id']],
            );

            $consolidar(
                'departamento',
                fn (object $fila): string => implode('|', [
                    $fila->pais_id,
                    $fila->codigo_departamento ?? $normalizar($fila->nombre),
                ]),
                [
                    ['enf_lugares_ejecucion', 'departamento_id'],
                    ['informe_final_proyectos', 'departamento_territorial_id'],
                    ['municipio', 'departamento_id'],
                    ['proyecto_departamento', 'departamento_id'],
                    ['servicio_departamento', 'departamento_id'],
                ],
            );

            $consolidar(
                'municipio',
                fn (object $fila): string => $fila->departamento_id.'|'.$normalizar($fila->nombre),
                [
                    ['enf_lugares_ejecucion', 'municipio_id'],
                    ['informe_final_proyectos', 'municipio_id'],
                    ['proyecto_municipio', 'municipio_id'],
                    ['servicio_municipio', 'municipio_id'],
                ],
            );

            $consolidar(
                'campus',
                fn (object $fila): string => $normalizar($fila->nombre_campus),
                [
                    ['centro_facultad', 'campus_id'],
                    ['enf_lugares_ejecucion', 'campus_id'],
                ],
            );

            $consolidar(
                'centro_facultad',
                fn (object $fila): string => $fila->campus_id.'|'.$normalizar($fila->nombre),
                [
                    ['administrador_centro_facultad', 'centro_facultad_id'],
                    ['carrera', 'facultad_centro_id'],
                    ['carrera_facultad_centro', 'facultad_centro_id'],
                    ['departamento_academico', 'centro_facultad_id'],
                    ['ediciones_programa', 'centro_facultad_id'],
                    ['enf_acciones', 'centro_facultad_id'],
                    ['enf_certificado_carreras', 'centro_facultad_id'],
                    ['enf_participacion_universitaria', 'centro_facultad_id'],
                    ['estudiante', 'centro_facultad_id'],
                    ['informe_final_proyectos', 'centro_facultad_id'],
                    ['programas_centros_facultad', 'centro_facultad_id'],
                    ['programas_certificacion', 'centro_facultad_id'],
                    ['proyecto_centro_facultad', 'centro_facultad_id'],
                    ['servicio_centro_facultad', 'centro_facultad_id'],
                ],
            );

            $consolidar(
                'departamento_academico',
                fn (object $fila): string => $fila->centro_facultad_id.'|'.$normalizar($fila->nombre),
                [
                    ['carrera', 'departamento_academico_id'],
                    ['carrera_departamento_academico', 'departamento_academico_id'],
                    ['enf_acciones', 'departamento_academico_id'],
                    ['informe_final_proyectos', 'departamento_academico_id'],
                    ['proyecto_depto_ac', 'departamento_academico_id'],
                    ['servicio_depto_ac', 'departamento_academico_id'],
                ],
            );

            $consolidar(
                'carrera',
                fn (object $fila): string => $fila->facultad_centro_id.'|'.$normalizar($fila->nombre),
                [
                    ['asignaturas', 'carrera_id'],
                    ['carrera_departamento_academico', 'carrera_id'],
                    ['carrera_facultad_centro', 'carrera_id'],
                    ['enf_acciones', 'carrera_id'],
                    ['enf_certificado_carreras', 'carrera_id'],
                    ['enf_participacion_universitaria', 'carrera_id'],
                    ['enf_practicas_asignatura', 'carrera_id'],
                    ['estudiante', 'carrera_id'],
                    ['estudiante_proyecto', 'carrera_id'],
                    ['informe_final_proyectos', 'carrera_id'],
                    ['proyecto_carrera', 'carrera_id'],
                    ['servicio_carrera', 'carrera_id'],
                ],
            );
        });

        Schema::table('modalidad', function (Blueprint $table): void {
            $table->unique('nombre', 'modalidad_nombre_unique');
        });
        Schema::table('pais', function (Blueprint $table): void {
            $table->unique('codigo_iso', 'pais_codigo_iso_unique');
        });
        Schema::table('departamento', function (Blueprint $table): void {
            $table->unique(['pais_id', 'codigo_departamento'], 'departamento_pais_codigo_unique');
        });
        Schema::table('municipio', function (Blueprint $table): void {
            $table->unique(['departamento_id', 'nombre'], 'municipio_departamento_nombre_unique');
        });
        Schema::table('campus', function (Blueprint $table): void {
            $table->unique('nombre_campus', 'campus_nombre_unique');
        });
        Schema::table('centro_facultad', function (Blueprint $table): void {
            $table->unique(['campus_id', 'nombre'], 'centro_campus_nombre_unique');
        });
        Schema::table('departamento_academico', function (Blueprint $table): void {
            $table->unique(['centro_facultad_id', 'nombre'], 'depto_academico_centro_nombre_unique');
        });
        Schema::table('carrera', function (Blueprint $table): void {
            $table->unique(['facultad_centro_id', 'nombre'], 'carrera_facultad_nombre_unique');
        });
    }

    public function down(): void
    {
        Schema::table('carrera', function (Blueprint $table): void {
            $table->dropUnique('carrera_facultad_nombre_unique');
        });
        Schema::table('departamento_academico', function (Blueprint $table): void {
            $table->dropUnique('depto_academico_centro_nombre_unique');
        });
        Schema::table('centro_facultad', function (Blueprint $table): void {
            $table->dropUnique('centro_campus_nombre_unique');
        });
        Schema::table('campus', function (Blueprint $table): void {
            $table->dropUnique('campus_nombre_unique');
        });
        Schema::table('municipio', function (Blueprint $table): void {
            $table->dropUnique('municipio_departamento_nombre_unique');
        });
        Schema::table('departamento', function (Blueprint $table): void {
            $table->dropUnique('departamento_pais_codigo_unique');
        });
        Schema::table('pais', function (Blueprint $table): void {
            $table->dropUnique('pais_codigo_iso_unique');
        });
        Schema::table('modalidad', function (Blueprint $table): void {
            $table->dropUnique('modalidad_nombre_unique');
        });
    }
};
