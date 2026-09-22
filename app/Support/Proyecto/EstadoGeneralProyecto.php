<?php

namespace App\Support\Proyecto;

use App\Models\Estado\TipoEstado;
use App\Support\Dashboard\EstadosProyecto;

final class EstadoGeneralProyecto
{
    public const BORRADOR = 'Borrador';
    public const REVISION = 'En revision';
    public const SUBSANACION = 'Subsanacion';
    public const REGISTRADO = 'Registrado';
    public const FINALIZADO = 'Finalizado';

    private const COMPATIBLES = [
        self::BORRADOR => ['Borrador', 'Autoguardado'],
        self::REVISION => ['En revision', 'En revisión', 'En revision final', 'Coordinador Proyecto', 'Enlace Vinculacion', 'Jefe Departamento', 'Director centro'],
        self::SUBSANACION => ['Subsanacion', 'Subsanación'],
        self::REGISTRADO => ['Registrado', 'En curso', 'Inscrito', 'Aprobado'],
        self::FINALIZADO => ['Finalizado'],
    ];

    public static function nombre(?string $nombre): string
    {
        foreach (self::COMPATIBLES as $general => $anteriores) {
            foreach ($anteriores as $anterior) {
                if (mb_strtolower(trim((string) $nombre)) === mb_strtolower($anterior)) {
                    return $general;
                }
            }
        }

        return $nombre ?: self::BORRADOR;
    }

    public static function compatibles(string $nombre): array
    {
        return self::COMPATIBLES[self::nombre($nombre)] ?? [$nombre];
    }

    public static function opciones(): \Illuminate\Support\Collection
    {
        return TipoEstado::orderBy('id')->get(['id', 'nombre'])
            ->mapWithKeys(fn (TipoEstado $estado): array => [$estado->id => self::nombre($estado->nombre)])
            ->filter(fn (string $nombre): bool => array_key_exists($nombre, self::COMPATIBLES))
            ->unique();
    }

    public static function id(string $nombre): int
    {
        $estado = TipoEstado::firstOrCreate(['nombre' => $nombre]);
        if ($estado->wasRecentlyCreated) {
            EstadosProyecto::olvidar();
        }

        return (int) $estado->id;
    }
}
