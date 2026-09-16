<?php

namespace App\Support\Dashboard;

use App\Models\Estado\TipoEstado;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

/**
 * Resolución de `tipo_estado` por nombre.
 *
 * La base de datos tiene el catálogo sembrado más de una vez: `TipoEstado` usa
 * SoftDeletes y el seeder llama a `firstOrCreate`, que no ve las filas borradas,
 * así que cada re-siembra crea un juego nuevo de ids. En producción hay 33 filas
 * para 16 nombres (ids 1-16 y 17-32 son los mismos nombres repetidos).
 *
 * Por eso `TipoEstado::where('nombre', X)->first()->id` cuenta solo una parte de
 * los proyectos. Todo consumidor debe usar `ids()` + `whereIn`, nunca `first()`.
 *
 * La búsqueda normaliza a minúsculas, lo que además absorbe las variantes de
 * capitalización que conviven en el código ('Director centro' en config/nexo.php
 * frente a 'Director Centro' en los dashboards).
 */
final class EstadosProyecto
{
    public const BORRADOR = 'Borrador';

    public const EN_CURSO = 'En curso';

    public const FINALIZADO = 'Finalizado';

    public const SUBSANACION = 'Subsanacion';

    public const RECHAZADO = 'Rechazado';

    public const APROBADO = 'Aprobado';

    public const AUTOGUARDADO = 'Autoguardado';

    /**
     * Todo lo que el autor todavía no ha enviado a revisión.
     *
     * 'Autoguardado' cuenta como borrador: es lo que graba el formulario solo,
     * y en la base actual hay 14 proyectos así frente a 0 en 'Borrador'. Una
     * tarjeta "Borradores: 0" con 14 proyectos sin enviar sería engañosa.
     *
     * @var list<string>
     */
    public const SIN_ENVIAR = [
        self::BORRADOR,
        self::AUTOGUARDADO,
    ];

    /**
     * Estados en los que el proyecto está esperando a alguien dentro del flujo.
     *
     * Deliberadamente NO incluye Subsanacion, Rechazado, Cancelado, Inscrito ni
     * Aprobado: esos son desenlaces, y varios tienen tarjeta propia en el
     * panel. Mezclarlos —como hace EN_REVISION— produce tarjetas que se
     * solapan, donde "En revisión: 13" ya contiene los "Subsanación: 3".
     *
     * @var list<string>
     */
    public const EN_REVISION_ACTIVA = [
        'Esperando documento',
        'Subsanar documento',
        'Enlace Vinculacion',
        'Coordinador Proyecto',
        'Jefe Departamento',
        'Director centro',
        'En revision',
        'En revision final',
    ];

    /**
     * Estados que significan "el formulario está dentro del flujo de revisión".
     * Esta lista estaba duplicada literalmente seis veces entre los tres
     * componentes de dashboard.
     *
     * Incluye desenlaces (Aprobado, Subsanacion, Rechazado...) porque así la
     * usaban los paneles: se conserva para no alterar sus conteos. Para el
     * resumen del panel nuevo se usa EN_REVISION_ACTIVA.
     *
     * @var list<string>
     */
    public const EN_REVISION = [
        'Esperando documento',
        'Subsanar documento',
        'Enlace Vinculacion',
        'Coordinador Proyecto',
        'Jefe Departamento',
        'Director centro',
        'En revision final',
        'Aprobado',
        'Subsanacion',
        'Rechazado',
        'Inscrito',
        'Cancelado',
        'En revision',
    ];

    private const CLAVE_CACHE = 'nexo.tipo_estado.mapa';

    /** Memoización por request, por encima de la caché compartida. */
    private static ?array $mapa = null;

    /**
     * Todos los ids de `tipo_estado` que corresponden a los nombres dados.
     *
     * @param  string|list<string>  $nombres
     * @return list<int>
     */
    public static function ids(string|array $nombres): array
    {
        $mapa = self::mapa();
        $ids = [];

        foreach ((array) $nombres as $nombre) {
            foreach ($mapa[self::normalizar($nombre)] ?? [] as $id) {
                $ids[] = $id;
            }
        }

        return array_values(array_unique($ids));
    }

    /**
     * Mapa nombre normalizado => ids.
     *
     * @return array<string, list<int>>
     */
    public static function mapa(): array
    {
        if (self::$mapa !== null) {
            return self::$mapa;
        }

        return self::$mapa = Cache::remember(
            self::CLAVE_CACHE,
            now()->addHour(),
            fn (): array => TipoEstado::query()
                ->select(['id', 'nombre'])
                ->get()
                ->groupBy(fn (TipoEstado $estado): string => self::normalizar($estado->nombre))
                ->map(fn ($grupo): array => $grupo->pluck('id')->all())
                ->all()
        );
    }

    /**
     * Olvida el mapa memoizado y el cacheado. Necesario en tests que siembran
     * `tipo_estado` después de que algo ya lo haya leído.
     */
    public static function olvidar(): void
    {
        self::$mapa = null;
        Cache::forget(self::CLAVE_CACHE);
    }

    private static function normalizar(?string $nombre): string
    {
        return Str::lower(trim((string) $nombre));
    }
}
