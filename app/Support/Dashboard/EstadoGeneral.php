<?php

namespace App\Support\Dashboard;

use App\Models\Estado\TipoEstado;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Vocabulario común del panel: los cinco estados generales en los que se
 * resume cualquier trámite, sea cual sea su formulario.
 *
 * NEXO digitaliza decenas de formularios, cada uno con su flujo y sus nombres
 * de estado. El panel no puede conocerlos todos, así que cada formulario
 * traduce sus estados a estas cinco categorías (más «otros» para lo que no
 * encaja) y el panel solo trabaja con ellas. Es la misma separación entre
 * estado general y etapa que docs/estados-y-etapas-proyectos.md estableció
 * para los proyectos.
 */
final class EstadoGeneral
{
    public const BORRADOR = 'borrador';

    public const EN_REVISION = 'en_revision';

    public const SUBSANACION = 'subsanacion';

    public const APROBADO = 'aprobado';

    public const FINALIZADO = 'finalizado';

    /** Cancelados, anteriores al sistema y cualquier nombre no reconocido. */
    public const OTROS = 'otros';

    /** Orden en el que se muestran, que es el del ciclo de vida. */
    public const CLAVES = [self::BORRADOR, self::EN_REVISION, self::SUBSANACION, self::APROBADO, self::FINALIZADO, self::OTROS];

    public const ETIQUETAS = [
        self::BORRADOR => 'Borrador',
        self::EN_REVISION => 'En revisión',
        self::SUBSANACION => 'Subsanación',
        self::APROBADO => 'Aprobado',
        self::FINALIZADO => 'Finalizado',
        self::OTROS => 'Otros',
    ];

    /** Qué agrupa cada categoría, para las ayudas de la interfaz. */
    public const AYUDAS = [
        self::BORRADOR => 'Sin enviar',
        self::EN_REVISION => 'En alguna etapa del flujo',
        self::SUBSANACION => 'Devueltos para corregir',
        self::APROBADO => 'Incluye proyectos registrados',
        self::FINALIZADO => 'Cerrados',
        self::OTROS => 'Cancelados o antes del sistema',
    ];

    /** Nombres de `tipo_estado` reconocidos, ya normalizados. */
    private const NOMBRES = [
        self::BORRADOR => ['borrador', 'autoguardado'],
        self::EN_REVISION => [
            'en revision', 'en revisión', 'en_revision', 'en revision final', 'enviado', 'esperando documento',
            // Estados que antes seguían al cargo revisor.
            'coordinador proyecto', 'enlace vinculacion', 'jefe departamento', 'director centro',
        ],
        self::SUBSANACION => ['subsanacion', 'subsanación', 'subsanar documento', 'rechazado'],
        self::APROBADO => ['registrado', 'en curso', 'inscrito', 'aprobado', 'informe final habilitado', 'actualizacion realizada'],
        self::FINALIZADO => ['finalizado'],
        self::OTROS => ['cancelado', 'pendienteinformacion'],
    ];

    /** @var array<int,string>|null */
    private static ?array $porId = null;

    /**
     * Clasifica un nombre de estado. Un nombre desconocido cuenta como en
     * revisión si es el estado de algún cargo de firma (los flujos por cargo
     * crean uno por etapa), y como «otros» en cualquier otro caso.
     */
    public static function clasificar(?string $nombre, bool $esEstadoDeCargo = false): string
    {
        $normalizado = Str::lower(trim((string) $nombre));

        if ($normalizado === '') {
            return self::BORRADOR;
        }

        foreach (self::NOMBRES as $clave => $nombres) {
            if (in_array($normalizado, $nombres, true)) {
                return $clave;
            }
        }

        return $esEstadoDeCargo ? self::EN_REVISION : self::OTROS;
    }

    /**
     * Clasificación de cada fila de `tipo_estado`, para agrupar en SQL por id
     * y traducir en PHP. Se memoriza por petición.
     *
     * @return array<int,string>
     */
    public static function porId(): array
    {
        if (self::$porId !== null) {
            return self::$porId;
        }

        $deCargo = DB::table('cargo_firma')->whereNotNull('tipo_estado_id')->pluck('tipo_estado_id')
            ->map(fn ($id): int => (int) $id)->flip();

        return self::$porId = TipoEstado::query()->withTrashed()->get(['id', 'nombre'])
            ->mapWithKeys(fn (TipoEstado $estado): array => [
                (int) $estado->id => self::clasificar($estado->nombre, $deCargo->has((int) $estado->id)),
            ])
            ->all();
    }

    /**
     * Clasificación de un `tipo_estado`. Si el id no está en el mapa
     * memorizado (un estado creado después), se vuelve a leer una vez.
     */
    public static function deId(int $tipoEstadoId): string
    {
        if (! array_key_exists($tipoEstadoId, self::porId())) {
            self::olvidar();
        }

        return self::porId()[$tipoEstadoId] ?? self::OTROS;
    }

    /**
     * Ids de `tipo_estado` que se clasifican en una categoría.
     *
     * @return list<int>
     */
    public static function idsDe(string $clave): array
    {
        return array_keys(array_filter(self::porId(), fn (string $c): bool => $c === $clave));
    }

    /** @return array<string,int> Todas las claves en cero. */
    public static function vacio(): array
    {
        return array_fill_keys(self::CLAVES, 0);
    }

    /** Olvida la clasificación memorizada. Necesario en tests que crean estados. */
    public static function olvidar(): void
    {
        self::$porId = null;
    }
}
