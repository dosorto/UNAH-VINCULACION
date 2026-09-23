<?php

namespace App\Support\InformeFinal;

/**
 * Conceptos del apartado X «Ejecución presupuestaria» del formato oficial INF-001.
 * Cada concepto es [letra, concepto, unidad], tal como aparecen impresos en el formato.
 */
final class ConceptosPresupuestoInf001
{
    public const TASA_COSTOS_INDIRECTOS = 0.03;

    /** Aporte de la UNAH (manifestado en lempiras). */
    public const UNAH = [
        'horas_trabajo_docentes' => ['a', 'Horas de trabajo docentes', 'Hra/profes'],
        'horas_trabajo_estudiantes' => ['b', 'Horas de participación de estudiantes', 'Hra/estud'],
        'contratacion_personal' => ['c', 'Contratación de personal - consultorías', 'Global'],
        'gastos_alimentacion' => ['d', 'Gastos de alimentación', 'Global'],
        'viaticos_estipendios' => ['e', 'Viáticos / estipendios', 'Global'],
        'gastos_movilizacion' => ['f', 'Gastos de movilización (pasajes aéreos, terrestres)', 'Global'],
        'combustible' => ['g', 'Combustible', 'Global'],
        'utiles_materiales_oficina' => ['h', 'Útiles y materiales de oficina', 'Global'],
        'gastos_impresion' => ['i', 'Gastos de impresión', 'Global'],
        'insumos_estudiantes' => ['j', 'Aportación insumos/materiales adquiridos por estudiantes', 'Global'],
        'costos_indirectos_infraestructura' => ['k', 'Costos indirectos por infraestructura universidad (depreciación de equipo, calculado sobre la sumatoria de los conceptos a – b)', '3%'],
        'costos_indirectos_servicios' => ['l', 'Costos indirectos por servicios públicos (internet, electricidad, otros, calculado sobre la sumatoria de los conceptos a – b)', '3%'],
    ];

    /** k) y l) se calculan: 3% de la sumatoria de a) y b). */
    public const UNAH_INDIRECTOS = ['costos_indirectos_infraestructura', 'costos_indirectos_servicios'];
    public const UNAH_BASE_INDIRECTOS = ['horas_trabajo_docentes', 'horas_trabajo_estudiantes'];

    /** Aporte de la contraparte (manifestado en lempiras). */
    public const CONTRAPARTE = [
        'contratacion_personal' => ['a', 'Contratación de personal', 'Global'],
        'insumos_materiales' => ['b', 'Insumos / materiales', 'Global'],
        'gastos_movilizacion' => ['c', 'Gastos de movilización (combustible, viáticos, transporte)', 'Global'],
        'gastos_hospedaje' => ['d', 'Gastos de hospedaje', 'Global'],
        'gastos_alimentacion' => ['e', 'Gastos de alimentación', 'Global'],
        'gastos_impresion' => ['f', 'Gastos de impresión', 'Global'],
        'otros_gastos' => ['g', 'Otros gastos', 'Global'],
    ];

    /** @return array<string, array{0:string,1:string,2:string}> */
    public static function catalogo(string $fuente): array
    {
        return $fuente === 'CONTRAPARTE' ? self::CONTRAPARTE : self::UNAH;
    }

    /** Concepto con su letra, p. ej. «a) Horas de trabajo docentes». */
    public static function etiqueta(string $fuente, string $codigo): ?string
    {
        $concepto = self::catalogo($fuente)[$codigo] ?? null;

        return $concepto ? "{$concepto[0]}) {$concepto[1]}" : null;
    }

    public static function unidad(string $fuente, string $codigo): ?string
    {
        return self::catalogo($fuente)[$codigo][2] ?? null;
    }

    public static function esIndirecto(?string $codigo): bool
    {
        return in_array($codigo, self::UNAH_INDIRECTOS, true);
    }

    /**
     * Código de una fila guardada: el explícito o, para filas anteriores al catálogo,
     * el que se deduce del texto del concepto (null si no corresponde a ninguno).
     */
    public static function codigoDeFila(string $fuente, ?string $codigo, ?string $concepto): ?string
    {
        if (filled($codigo) && array_key_exists($codigo, self::catalogo($fuente))) {
            return $codigo;
        }

        return self::codigoDesdeTexto($fuente, $concepto);
    }

    public static function codigoDesdeTexto(string $fuente, ?string $texto): ?string
    {
        $t = mb_strtolower(trim((string) $texto));
        if ($t === '') {
            return null;
        }

        $tiene = fn (string ...$palabras) => collect($palabras)->contains(fn ($palabra) => str_contains($t, $palabra));

        if ($fuente === 'CONTRAPARTE') {
            return match (true) {
                $tiene('contratación', 'contratacion', 'personal') => 'contratacion_personal',
                $tiene('insumo', 'material') => 'insumos_materiales',
                $tiene('movilización', 'movilizacion', 'transporte', 'combustible', 'viático', 'viatico') => 'gastos_movilizacion',
                $tiene('hospedaje') => 'gastos_hospedaje',
                $tiene('alimentación', 'alimentacion') => 'gastos_alimentacion',
                $tiene('impresión', 'impresion') => 'gastos_impresion',
                $tiene('otros') => 'otros_gastos',
                default => null,
            };
        }

        // Los indirectos se reconocen solo por su redacción completa: «servicios profesionales»
        // (consultorías) no debe confundirse con «servicios públicos».
        return match (true) {
            $tiene('indirecto') && $tiene('infraestructura') => 'costos_indirectos_infraestructura',
            $tiene('servicios públicos', 'servicios publicos') => 'costos_indirectos_servicios',
            $tiene('docente') => 'horas_trabajo_docentes',
            $tiene('estudiante') && $tiene('hora', 'participación', 'participacion') => 'horas_trabajo_estudiantes',
            $tiene('consultor', 'contratación', 'contratacion') => 'contratacion_personal',
            $tiene('alimentación', 'alimentacion') => 'gastos_alimentacion',
            $tiene('viático', 'viatico', 'estipendio') => 'viaticos_estipendios',
            $tiene('movilización', 'movilizacion', 'pasaje') => 'gastos_movilizacion',
            $tiene('combustible') => 'combustible',
            $tiene('útiles', 'utiles', 'oficina') => 'utiles_materiales_oficina',
            $tiene('impresión', 'impresion') => 'gastos_impresion',
            $tiene('insumo') => 'insumos_estudiantes',
            default => null,
        };
    }
}
