<?php

namespace App\Services\Dashboard;

use App\Models\Proyecto\Proyecto;
use App\Models\User;
use App\Support\Dashboard\AmbitoPanel;
use App\Support\Dashboard\EstadosProyecto;
use Closure;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * Métricas agregadas del panel estadístico.
 *
 * Reemplaza los bloques de consultas duplicados entre Dashboard,
 * DasboardDocente y DashboardDirector, donde cada conteo por estado traía 63
 * modelos hidratados solo para llamar a count() sobre ellos.
 *
 * Todo lo de aquí es agregado y cacheable. Las listas accionables (pendientes
 * de revisión, mis formularios, actividad reciente) viven en otros servicios y
 * NO se cachean: un revisor que aprueba algo debe ver el número bajar de
 * inmediato.
 *
 * Se resuelve por request, no como singleton: $memo evita repetir consultas
 * dentro de un mismo render de Livewire (cada loadMore() re-ejecuta render()
 * entero) pero no debe sobrevivir a la petición.
 */
class PanelEstadisticoService
{
    /** @var array<string, mixed> */
    private array $memo = [];

    public function __construct(private readonly AmbitoPanelResolver $resolver) {}

    public function ambito(?User $user = null): AmbitoPanel
    {
        return $this->resolver->para($user ?? auth()->user());
    }

    /**
     * Consulta base de proyectos del ámbito. Toda métrica compone sobre esta.
     *
     * @return Builder<Proyecto>
     */
    public function proyectos(AmbitoPanel $ambito, bool $incluirBorradores = true): Builder
    {
        $query = $ambito->aplicarA(Proyecto::query());

        if (! $incluirBorradores) {
            $ids = EstadosProyecto::ids(EstadosProyecto::BORRADOR);

            if ($ids !== []) {
                $query->whereNotExists(
                    fn ($sub) => $sub->selectRaw('1')
                        ->from('estado_proyecto')
                        ->whereColumn('estado_proyecto.estadoable_id', 'proyecto.id')
                        ->where('estado_proyecto.estadoable_type', Proyecto::class)
                        ->whereIn('estado_proyecto.tipo_estado_id', $ids)
                        ->where('estado_proyecto.es_actual', true)
                );
            }
        }

        return $query;
    }

    /**
     * Conteo por estado en UNA sola consulta.
     *
     * Antes eran cuatro consultas que además hidrataban modelos completos.
     *
     * Las categorías son mutuamente excluyentes y cubren el total: 'otros'
     * recoge los estados sin tarjeta propia (Aprobado, Inscrito, Cancelado,
     * PendienteInformacion...) para que las partes siempre sumen el total y
     * ninguna cifra del panel parezca contradecir a otra.
     *
     * @return array{total:int,borrador:int,en_revision:int,en_curso:int,finalizado:int,subsanacion:int,otros:int}
     */
    public function resumenEstados(AmbitoPanel $ambito): array
    {
        return $this->recordar('resumen', $ambito, function () use ($ambito): array {
            $grupos = ['en_curso' => EstadosProyecto::EN_CURSO,
                'finalizado' => EstadosProyecto::FINALIZADO,
                'subsanacion' => EstadosProyecto::SUBSANACION,
                'en_revision' => EstadosProyecto::EN_REVISION_ACTIVA];

            $select = ['COUNT(*) as total'];
            $bindings = [];

            foreach ($grupos as $clave => $nombres) {
                $ids = EstadosProyecto::ids($nombres);

                if ($ids === []) {
                    $select[] = "0 as `{$clave}`";

                    continue;
                }

                $marcadores = implode(',', array_fill(0, count($ids), '?'));
                $select[] = "SUM(CASE WHEN ep.tipo_estado_id IN ({$marcadores}) THEN 1 ELSE 0 END) as `{$clave}`";
                $bindings = array_merge($bindings, $ids);
            }

            // Un proyecto sin fila en estado_proyecto todavía no se envió, así
            // que cuenta como borrador junto a Borrador/Autoguardado.
            $idsBorrador = EstadosProyecto::ids(EstadosProyecto::SIN_ENVIAR);

            if ($idsBorrador === []) {
                $select[] = 'SUM(CASE WHEN ep.tipo_estado_id IS NULL THEN 1 ELSE 0 END) as `borrador`';
            } else {
                $marcadores = implode(',', array_fill(0, count($idsBorrador), '?'));
                $select[] = "SUM(CASE WHEN ep.tipo_estado_id IS NULL OR ep.tipo_estado_id IN ({$marcadores}) THEN 1 ELSE 0 END) as `borrador`";
                $bindings = array_merge($bindings, $idsBorrador);
            }

            $fila = $this->proyectos($ambito)
                ->leftJoin('estado_proyecto as ep', function ($join): void {
                    $join->on('ep.estadoable_id', '=', 'proyecto.id')
                        ->where('ep.estadoable_type', '=', Proyecto::class)
                        ->where('ep.es_actual', '=', true);
                })
                ->selectRaw(implode(', ', $select), $bindings)
                ->first();

            $resumen = [
                'total' => (int) ($fila->total ?? 0),
                'borrador' => (int) ($fila->borrador ?? 0),
                'en_revision' => (int) ($fila->en_revision ?? 0),
                'en_curso' => (int) ($fila->en_curso ?? 0),
                'finalizado' => (int) ($fila->finalizado ?? 0),
                'subsanacion' => (int) ($fila->subsanacion ?? 0),
            ];

            $resumen['otros'] = max(0, $resumen['total'] - (
                $resumen['borrador'] + $resumen['en_revision'] + $resumen['en_curso']
                + $resumen['finalizado'] + $resumen['subsanacion']
            ));

            return $resumen;
        });
    }

    /**
     * Serie anual de proyectos registrados, con los nombres de cada año para el
     * tooltip.
     *
     * @return array{categorias:list<string>,series:list<array{name:string,data:list<int>}>,detalle:array<string,list<string>>}
     */
    public function serieTemporal(AmbitoPanel $ambito, ?int $desde = null, ?int $hasta = null): array
    {
        $desde ??= (int) config('nexo.dashboard.anio_inicio', 2025);
        $hasta ??= (int) now()->year;

        if ($desde > $hasta) {
            $desde = $hasta;
        }

        return $this->recordar("serie:{$desde}:{$hasta}", $ambito, function () use ($ambito, $desde, $hasta): array {
            $filas = $this->proyectos($ambito)
                ->whereYear('proyecto.created_at', '>=', $desde)
                ->whereYear('proyecto.created_at', '<=', $hasta)
                ->get(['proyecto.id', 'proyecto.nombre_proyecto', 'proyecto.created_at'])
                ->groupBy(fn (Proyecto $p): string => (string) $p->created_at?->year);

            $categorias = [];
            $datos = [];
            $detalle = [];

            foreach (range($desde, $hasta) as $anio) {
                $delAnio = $filas->get((string) $anio, collect());
                $categorias[] = (string) $anio;
                $datos[] = $delAnio->count();
                $detalle[(string) $anio] = $delAnio
                    ->pluck('nombre_proyecto')
                    ->filter()
                    ->take(8)
                    ->values()
                    ->all();
            }

            return [
                'categorias' => $categorias,
                'series' => [['name' => 'Proyectos', 'data' => $datos]],
                'detalle' => $detalle,
            ];
        });
    }

    /**
     * Ciclo de vida completo de un proyecto, no solo su inscripción.
     *
     * Un proyecto no termina al aprobarse: sigue con el informe intermedio
     * —cuando su flujo lo contempla— y con el informe final que lo cierra. El
     * recorrido anterior se detenía en la aprobación, así que a un proyecto en
     * fase de cierre lo seguía contando como "en curso".
     *
     * La fase sale del documento más avanzado que ya entró en el flujo de
     * firmas, igual que Proyecto::procesoActivoParaStepper().
     *
     * Los borradores quedan fuera a propósito: no han entrado al recorrido, así
     * que como etapa no dicen nada. Van aparte, en `sin_iniciar`.
     *
     * @return array{fases:list<array{clave:string,etiqueta:string,valor:int,tono:string}>,en_flujo:int,sin_iniciar:int}
     */
    public function cicloDeVida(AmbitoPanel $ambito): array
    {
        return $this->recordar('ciclo', $ambito, function () use ($ambito): array {
            $idsBorrador = EstadosProyecto::ids(EstadosProyecto::SIN_ENVIAR);
            $idsRevision = EstadosProyecto::ids(EstadosProyecto::EN_REVISION_ACTIVA);
            $idsSubsanar = EstadosProyecto::ids(EstadosProyecto::SUBSANACION);
            $idsCurso = EstadosProyecto::ids(EstadosProyecto::EN_CURSO);
            $idsFinal = EstadosProyecto::ids(EstadosProyecto::FINALIZADO);

            $filas = $this->proyectos($ambito)
                ->leftJoin('estado_proyecto as ep', function ($join): void {
                    $join->on('ep.estadoable_id', '=', 'proyecto.id')
                        ->where('ep.estadoable_type', '=', Proyecto::class)
                        ->where('ep.es_actual', '=', true);
                })
                ->selectRaw(
                    'proyecto.id,
                     ep.tipo_estado_id,
                     EXISTS (
                        SELECT 1 FROM proyecto_documento pd
                        JOIN firma_proyecto fp
                          ON fp.firmable_id = pd.id
                         AND fp.firmable_type = ?
                         AND fp.deleted_at IS NULL
                        WHERE pd.proyecto_id = proyecto.id
                          AND pd.tipo_documento = ?
                     ) as tiene_intermedio,
                     EXISTS (
                        SELECT 1 FROM proyecto_documento pd
                        JOIN firma_proyecto fp
                          ON fp.firmable_id = pd.id
                         AND fp.firmable_type = ?
                         AND fp.deleted_at IS NULL
                        WHERE pd.proyecto_id = proyecto.id
                          AND pd.tipo_documento = ?
                     ) as tiene_final',
                    [
                        \App\Models\Proyecto\DocumentoProyecto::class, 'Informe Intermedio',
                        \App\Models\Proyecto\DocumentoProyecto::class, 'Informe Final',
                    ]
                )
                ->get();

            $conteo = ['revision' => 0, 'ejecucion' => 0, 'intermedio' => 0, 'final' => 0, 'cerrado' => 0];
            $sinIniciar = 0;

            foreach ($filas as $fila) {
                $estado = $fila->tipo_estado_id !== null ? (int) $fila->tipo_estado_id : null;

                // El documento manda sobre el estado: un proyecto con el informe
                // final en revisión sigue "En curso" como estado, pero su fase
                // real del ciclo es el cierre.
                if ($fila->tiene_final) {
                    $conteo['final']++;
                } elseif ($fila->tiene_intermedio) {
                    $conteo['intermedio']++;
                } elseif ($estado !== null && in_array($estado, $idsFinal, true)) {
                    $conteo['cerrado']++;
                } elseif ($estado !== null && in_array($estado, $idsCurso, true)) {
                    $conteo['ejecucion']++;
                } elseif ($estado === null || in_array($estado, $idsBorrador, true)) {
                    $sinIniciar++;
                } else {
                    // Cualquier otro estado con el expediente ya enviado
                    // (subsanación, o estados sueltos del catálogo como
                    // "PendienteInformacion") sigue siendo trámite de
                    // inscripción: así las fases suman siempre el total y no
                    // queda un resto sin explicar.
                    $conteo['revision']++;
                }
            }

            return [
                'fases' => [
                    ['clave' => 'revision', 'etiqueta' => 'Inscripción', 'valor' => $conteo['revision'], 'tono' => 'info'],
                    ['clave' => 'ejecucion', 'etiqueta' => 'En ejecución', 'valor' => $conteo['ejecucion'], 'tono' => 'exito'],
                    ['clave' => 'intermedio', 'etiqueta' => 'Informe intermedio', 'valor' => $conteo['intermedio'], 'tono' => 'acento'],
                    ['clave' => 'final', 'etiqueta' => 'Informe final', 'valor' => $conteo['final'], 'tono' => 'acento'],
                    ['clave' => 'cerrado', 'etiqueta' => 'Cerrado', 'valor' => $conteo['cerrado'], 'tono' => 'neutro'],
                ],
                'en_flujo' => array_sum($conteo),
                'sin_iniciar' => $sinIniciar,
            ];
        });
    }

    /**
     * Registros mes a mes de los últimos N meses.
     *
     * La serie anual solo tiene un punto por año —con datos desde 2025 son dos—
     * y eso no dibuja una tendencia. El detalle mensual sí muestra el ritmo de
     * inscripción, que es lo que la forma promete.
     *
     * @return array{categorias:list<string>,series:list<array{name:string,data:list<int>}>,detalle:array<string,list<string>>}
     */
    public function serieMensual(AmbitoPanel $ambito, int $meses = 12): array
    {
        return $this->recordar("serie-mes:{$meses}", $ambito, function () use ($ambito, $meses): array {
            $desde = now()->startOfMonth()->subMonths($meses - 1);

            $filas = $this->proyectos($ambito)
                ->where('proyecto.created_at', '>=', $desde)
                ->get(['proyecto.id', 'proyecto.nombre_proyecto', 'proyecto.created_at'])
                ->groupBy(fn (Proyecto $p): string => $p->created_at?->format('Y-m') ?? '');

            $categorias = [];
            $datos = [];
            $detalle = [];

            for ($i = 0; $i < $meses; $i++) {
                $mes = $desde->copy()->addMonths($i);
                $clave = $mes->format('Y-m');
                $delMes = $filas->get($clave, collect());

                // Etiqueta corta; el año solo cuando cambia, para no repetirlo.
                $etiqueta = $mes->locale('es')->isoFormat('MMM');
                $etiqueta = mb_convert_case($etiqueta, MB_CASE_TITLE);

                if ($i === 0 || $mes->month === 1) {
                    $etiqueta .= ' '.$mes->format('y');
                }

                $categorias[] = $etiqueta;
                $datos[] = $delMes->count();
                $detalle[$etiqueta] = $delMes->pluck('nombre_proyecto')->filter()->take(6)->values()->all();
            }

            return [
                'categorias' => $categorias,
                'series' => [['name' => 'Proyectos', 'data' => $datos]],
                'detalle' => $detalle,
            ];
        });
    }

    // ── Rankings ─────────────────────────────────────────────────────────────

    /** @return list<array{etiqueta:string,valor:int,porcentaje:float}> */
    public function rankingPorOds(AmbitoPanel $ambito, int $limite = 6): array
    {
        return $this->ranking('ods', $ambito, $limite, 'proyecto_ods', 'ods_id', 'ods', 'nombre');
    }

    /** @return list<array{etiqueta:string,valor:int,porcentaje:float}> */
    public function rankingPorCentro(AmbitoPanel $ambito, int $limite = 8): array
    {
        return $this->ranking('centro', $ambito, $limite, 'proyecto_centro_facultad', 'centro_facultad_id', 'centro_facultad', 'nombre');
    }

    /** @return list<array{etiqueta:string,valor:int,porcentaje:float}> */
    public function rankingPorDepartamento(AmbitoPanel $ambito, int $limite = 8): array
    {
        return $this->ranking('depto', $ambito, $limite, 'proyecto_depto_ac', 'departamento_academico_id', 'departamento_academico', 'nombre');
    }

    /** @return list<array{etiqueta:string,valor:int,porcentaje:float}> */
    public function rankingPorCategoria(AmbitoPanel $ambito, int $limite = 6): array
    {
        return $this->ranking('categoria', $ambito, $limite, 'proyecto_categoria', 'categoria_id', 'categorias', 'nombre');
    }

    /**
     * Ranking genérico sobre un pivote proyecto↔catálogo.
     *
     * El porcentaje se calcula contra el MÁXIMO del ranking, no contra el total
     * de proyectos: los pivotes son N:M (63 proyectos tienen 131 filas en
     * proyecto_ods), así que porcentajes sobre el total sumarían más de 100 y
     * las barras se verían desproporcionadas.
     *
     * Cada entrada trae además los proyectos que la componen, para que al abrir
     * el detalle se pueda ir al proyecto concreto: un "5 proyectos" que no deja
     * ver cuáles obliga a salir del panel a buscarlos a mano.
     *
     * @return list<array{id:int,etiqueta:string,valor:int,porcentaje:float,proyectos:list<array{id:int,nombre:string,codigo:?string}>}>
     */
    private function ranking(
        string $clave,
        AmbitoPanel $ambito,
        int $limite,
        string $pivote,
        string $columnaFk,
        string $catalogo,
        string $columnaNombre,
        int $proyectosPorEntrada = 12
    ): array {
        return $this->recordar("rank:{$clave}:{$limite}:{$proyectosPorEntrada}", $ambito, function () use ($ambito, $limite, $pivote, $columnaFk, $catalogo, $columnaNombre, $proyectosPorEntrada): array {
            $query = DB::table($pivote)
                ->join($catalogo, "{$catalogo}.id", '=', "{$pivote}.{$columnaFk}")
                ->whereIn("{$pivote}.proyecto_id", $ambito->idsProyectoQuery())
                ->groupBy("{$catalogo}.id", "{$catalogo}.{$columnaNombre}")
                ->orderByDesc('valor')
                ->limit($limite)
                ->selectRaw("{$catalogo}.id as id, {$catalogo}.{$columnaNombre} as etiqueta, COUNT(DISTINCT {$pivote}.proyecto_id) as valor");

            if ($this->tieneSoftDeletes($pivote)) {
                $query->whereNull("{$pivote}.deleted_at");
            }

            if ($this->tieneSoftDeletes($catalogo)) {
                $query->whereNull("{$catalogo}.deleted_at");
            }

            $filas = $query->get();

            if ($filas->isEmpty()) {
                return [];
            }

            $maximo = max(1, (int) $filas->max('valor'));
            $porCategoria = $this->proyectosDelPivote(
                $ambito, $pivote, $columnaFk, $filas->pluck('id')->all(), $proyectosPorEntrada
            );

            return $filas->map(fn ($f): array => [
                'id' => (int) $f->id,
                'etiqueta' => (string) $f->etiqueta,
                'valor' => (int) $f->valor,
                'porcentaje' => round(((int) $f->valor / $maximo) * 100, 1),
                'proyectos' => $porCategoria[(int) $f->id] ?? [],
            ])->all();
        });
    }

    /**
     * Cobertura de una dimensión: cuántos proyectos tienen algo asignado en
     * ella y entre cuántas categorías se reparten.
     *
     * Sirve para la cifra de cabecera. Sumar los valores del ranking no vale:
     * está recortado al top-N, así que con muchas categorías la cifra se
     * quedaría corta sin avisar.
     *
     * @return array{proyectos:int,categorias:int}
     */
    public function coberturaDimension(AmbitoPanel $ambito, string $dimension): array
    {
        [$pivote, $columnaFk] = match ($dimension) {
            'ods' => ['proyecto_ods', 'ods_id'],
            'centro' => ['proyecto_centro_facultad', 'centro_facultad_id'],
            'departamento' => ['proyecto_depto_ac', 'departamento_academico_id'],
            'categoria' => ['proyecto_categoria', 'categoria_id'],
            default => [null, null],
        };

        if ($pivote === null) {
            return ['proyectos' => 0, 'categorias' => 0];
        }

        return $this->recordar("cobertura:{$dimension}", $ambito, function () use ($ambito, $pivote, $columnaFk): array {
            $query = DB::table($pivote)
                ->whereIn("{$pivote}.proyecto_id", $ambito->idsProyectoQuery());

            if ($this->tieneSoftDeletes($pivote)) {
                $query->whereNull("{$pivote}.deleted_at");
            }

            $fila = $query->selectRaw(
                "COUNT(DISTINCT {$pivote}.proyecto_id) as proyectos,
                 COUNT(DISTINCT {$pivote}.{$columnaFk}) as categorias"
            )->first();

            return [
                'proyectos' => (int) ($fila->proyectos ?? 0),
                'categorias' => (int) ($fila->categorias ?? 0),
            ];
        });
    }

    /**
     * Proyectos de cada entrada del ranking, en una sola consulta para todas.
     *
     * @param  list<int>  $idsCatalogo
     * @return array<int, list<array{id:int,nombre:string,codigo:?string}>>
     */
    private function proyectosDelPivote(
        AmbitoPanel $ambito,
        string $pivote,
        string $columnaFk,
        array $idsCatalogo,
        int $porEntrada
    ): array {
        if ($idsCatalogo === []) {
            return [];
        }

        $query = DB::table($pivote)
            ->join('proyecto', 'proyecto.id', '=', "{$pivote}.proyecto_id")
            ->whereIn("{$pivote}.{$columnaFk}", $idsCatalogo)
            ->whereIn("{$pivote}.proyecto_id", $ambito->idsProyectoQuery())
            ->whereNull('proyecto.deleted_at')
            ->orderBy('proyecto.nombre_proyecto')
            ->select([
                "{$pivote}.{$columnaFk} as categoria_id",
                'proyecto.id',
                'proyecto.nombre_proyecto',
                'proyecto.codigo_proyecto',
            ]);

        if ($this->tieneSoftDeletes($pivote)) {
            $query->whereNull("{$pivote}.deleted_at");
        }

        return $query->get()
            ->groupBy('categoria_id')
            ->map(fn ($grupo) => $grupo
                ->take($porEntrada)
                ->map(fn ($p): array => [
                    'id' => (int) $p->id,
                    'nombre' => (string) $p->nombre_proyecto,
                    'codigo' => $p->codigo_proyecto,
                ])->values()->all()
            )
            ->all();
    }

    // ── Distribuciones ───────────────────────────────────────────────────────

    /**
     * Reparto por modalidad disciplinar (unidisciplinar, interdisciplinar…).
     *
     * Usa la relación con el catálogo `modalidad`, no la columna
     * `modalidad_ejecucion`: esa última está prevista en el esquema
     * (presencial/distancia/bimodal) pero los formularios no la rellenan, así
     * que dibujaba un bloque vacío mientras el dato real estaba al lado.
     *
     * @return list<array{etiqueta:string,valor:int,porcentaje:float}>
     */
    public function distribucionModalidad(AmbitoPanel $ambito): array
    {
        return $this->recordar('modalidad', $ambito, function () use ($ambito): array {
            $filas = $this->proyectos($ambito)
                ->join('modalidad', 'modalidad.id', '=', 'proyecto.modalidad_id')
                ->whereNull('modalidad.deleted_at')
                ->groupBy('modalidad.id', 'modalidad.nombre')
                ->orderByDesc('valor')
                ->pluck(DB::raw('COUNT(*) as valor'), 'modalidad.nombre');

            return $this->conPorcentajeSobreTotal($filas->all());
        });
    }

    /**
     * Reparto por modalidad de ejecución (presencial, distancia, bimodal).
     *
     * Separada de la disciplinar porque son dos ejes distintos y hoy solo el
     * primero tiene datos. Devuelve vacío mientras los formularios no la
     * rellenen, y el panel decide si merece la pena mostrarla.
     *
     * @return list<array{etiqueta:string,valor:int,porcentaje:float}>
     */
    public function distribucionModalidadEjecucion(AmbitoPanel $ambito): array
    {
        return $this->recordar('modalidad-ejecucion', $ambito, function () use ($ambito): array {
            $filas = $this->proyectos($ambito)
                ->whereNotNull('proyecto.modalidad_ejecucion')
                ->where('proyecto.modalidad_ejecucion', '!=', '')
                ->groupBy('proyecto.modalidad_ejecucion')
                ->orderByDesc('valor')
                ->pluck(DB::raw('COUNT(*) as valor'), 'proyecto.modalidad_ejecucion');

            return $this->conPorcentajeSobreTotal($filas->all());
        });
    }

    /**
     * Alcance poblacional: son columnas planas de `proyecto`, no una tabla de
     * beneficiarios. Se evitan los accessors getEstudiantesHombresAttribute
     * porque hacen join con `estudiante`, tabla casi vacía (3 filas).
     *
     * @return array{poblacion:int,hombres:int,mujeres:int,otros:int,etnias:list<array{etiqueta:string,valor:int,porcentaje:float}>}
     */
    public function alcancePoblacional(AmbitoPanel $ambito): array
    {
        return $this->recordar('poblacion', $ambito, function () use ($ambito): array {
            $f = $this->proyectos($ambito)->selectRaw(
                'COALESCE(SUM(proyecto.poblacion_participante),0) poblacion,
                 COALESCE(SUM(proyecto.hombres),0) hombres,
                 COALESCE(SUM(proyecto.mujeres),0) mujeres,
                 COALESCE(SUM(proyecto.otros),0) otros,
                 COALESCE(SUM(proyecto.indigenas_hombres + proyecto.indigenas_mujeres),0) indigenas,
                 COALESCE(SUM(proyecto.afroamericanos_hombres + proyecto.afroamericanos_mujeres),0) afro,
                 COALESCE(SUM(proyecto.mestizos_hombres + proyecto.mestizos_mujeres),0) mestizos'
            )->first();

            return [
                'poblacion' => (int) ($f->poblacion ?? 0),
                'hombres' => (int) ($f->hombres ?? 0),
                'mujeres' => (int) ($f->mujeres ?? 0),
                'otros' => (int) ($f->otros ?? 0),
                'etnias' => $this->conPorcentajeSobreMaximo([
                    'Indígenas' => (int) ($f->indigenas ?? 0),
                    'Afroamericanos' => (int) ($f->afro ?? 0),
                    'Mestizos' => (int) ($f->mestizos ?? 0),
                ]),
            ];
        });
    }

    /** @return array{total:int,hombres:int,mujeres:int,por_tipo:list<array{etiqueta:string,valor:int,porcentaje:float}>} */
    public function participacionEstudiantil(AmbitoPanel $ambito): array
    {
        return $this->recordar('estudiantes', $ambito, function () use ($ambito): array {
            $ids = $ambito->idsProyectoQuery();

            $totales = DB::table('estudiante_proyecto')
                ->whereIn('proyecto_id', $ids)
                ->whereNull('deleted_at')
                ->selectRaw(
                    'COALESCE(SUM(total_estudiantes),0) total,
                     COALESCE(SUM(cantidad_estudiantes_hombres),0) hombres,
                     COALESCE(SUM(cantidad_estudiantes_mujeres),0) mujeres'
                )->first();

            $porTipo = DB::table('estudiante_proyecto')
                ->whereIn('proyecto_id', $ambito->idsProyectoQuery())
                ->whereNull('deleted_at')
                ->whereNotNull('tipo_participacion_estudiante')
                ->groupBy('tipo_participacion_estudiante')
                ->pluck(DB::raw('COALESCE(SUM(total_estudiantes),0) as valor'), 'tipo_participacion_estudiante');

            return [
                'total' => (int) ($totales->total ?? 0),
                'hombres' => (int) ($totales->hombres ?? 0),
                'mujeres' => (int) ($totales->mujeres ?? 0),
                'por_tipo' => $this->conPorcentajeSobreTotal($porTipo->all()),
            ];
        });
    }

    /** @return array{horas:int,actividades:int,aporte:float} */
    public function esfuerzoInstitucional(AmbitoPanel $ambito): array
    {
        return $this->recordar('esfuerzo', $ambito, function () use ($ambito): array {
            $actividades = DB::table('actividades')
                ->whereIn('proyecto_id', $ambito->idsProyectoQuery())
                ->whereNull('deleted_at')
                ->selectRaw('COALESCE(SUM(horas),0) horas, COUNT(*) total')
                ->first();

            $aporte = DB::table('aporte_institucional')
                ->whereIn('proyecto_id', $ambito->idsProyectoQuery())
                ->whereNull('deleted_at')
                ->sum('costo_total');

            return [
                'horas' => (int) ($actividades->horas ?? 0),
                'actividades' => (int) ($actividades->total ?? 0),
                'aporte' => (float) $aporte,
            ];
        });
    }

    // ── Salud del flujo de aprobación ────────────────────────────────────────

    /**
     * Días promedio que tarda cada etapa en resolverse.
     *
     * El nombre de la etapa sale de firma_proyecto.etapa_nombre, pero las
     * firmas anteriores al sistema de flujos lo tienen nulo (en la base actual,
     * las 81); para esas se cae al cargo que firmó, y así la métrica sirve
     * también con el histórico.
     *
     * Se excluyen los proyectos de workflow_legacy_adoptions: fueron migrados a
     * mitad de flujo, así que sus firmas se crearon con fecha artificial y
     * distorsionan el promedio.
     *
     * @return list<array{etiqueta:string,valor:float,firmas:int,porcentaje:float}>
     */
    public function tiemposPorEtapa(AmbitoPanel $ambito, int $limite = 8): array
    {
        return $this->recordar("tiempos:{$limite}", $ambito, function () use ($ambito, $limite): array {
            $etiqueta = $this->expresionNombreEtapa();

            $filas = DB::table('firma_proyecto')
                ->leftJoin('cargo_firma', 'cargo_firma.id', '=', 'firma_proyecto.cargo_firma_id')
                ->leftJoin('tipo_cargo_firma', 'tipo_cargo_firma.id', '=', 'cargo_firma.tipo_cargo_firma_id')
                ->where('firma_proyecto.firmable_type', Proyecto::class)
                ->whereIn('firma_proyecto.firmable_id', $ambito->idsProyectoQuery())
                ->where('firma_proyecto.estado_revision', 'Aprobado')
                ->whereNotNull('firma_proyecto.fecha_firma')
                ->whereNull('firma_proyecto.deleted_at')
                ->whereNotIn('firma_proyecto.firmable_id', $this->proyectosAdoptadosQuery())
                ->groupByRaw($etiqueta)
                ->orderByDesc('valor')
                ->limit($limite)
                ->selectRaw(
                    "{$etiqueta} as etiqueta,
                     AVG(DATEDIFF(firma_proyecto.fecha_firma, firma_proyecto.created_at)) as valor,
                     COUNT(*) as firmas"
                )
                ->get();

            $maximo = max(0.1, (float) $filas->max('valor'));

            return $filas->map(fn ($f): array => [
                'etiqueta' => (string) $f->etiqueta,
                'valor' => round((float) $f->valor, 1),
                'firmas' => (int) $f->firmas,
                'porcentaje' => round(((float) $f->valor / $maximo) * 100, 1),
            ])->all();
        });
    }

    /**
     * Cuántos proyectos están esperando AHORA en cada etapa, y cuánto llevan.
     *
     * "Esperando aquí" no es lo mismo que "tiene una firma pendiente de esta
     * etapa": al crearse el expediente se generan las firmas de todo el
     * recorrido, así que un proyecto que apenas entró en Enlace Vinculación ya
     * arrastra una firma pendiente de Director centro. Contarlas todas inflaba
     * las últimas etapas con trabajo que todavía no les ha llegado.
     *
     * El criterio correcto es el mismo que usa la bandeja de tareas
     * (ResolvesFirmasPendientes): la firma cuenta cuando el estado actual del
     * proyecto coincide con el estado que esa etapa atiende.
     *
     * @return array<string, array{proyectos:int,dias_promedio:int,dias_maximo:int}>
     */
    public function detenidosPorEtapa(AmbitoPanel $ambito): array
    {
        return $this->recordar('detenidos-etapa', $ambito, function () use ($ambito): array {
            $etiqueta = $this->expresionNombreEtapa();

            return DB::table('firma_proyecto')
                ->join('cargo_firma', 'cargo_firma.id', '=', 'firma_proyecto.cargo_firma_id')
                ->leftJoin('tipo_cargo_firma', 'tipo_cargo_firma.id', '=', 'cargo_firma.tipo_cargo_firma_id')
                ->join('proyecto', 'proyecto.id', '=', 'firma_proyecto.firmable_id')
                ->join('estado_proyecto as ep', function ($join): void {
                    $join->on('ep.estadoable_id', '=', 'proyecto.id')
                        ->where('ep.estadoable_type', '=', Proyecto::class)
                        ->where('ep.es_actual', '=', true);
                })
                // Aquí está la clave: la etapa que atiende el estado actual.
                ->whereColumn('ep.tipo_estado_id', 'cargo_firma.tipo_estado_id')
                ->where('firma_proyecto.firmable_type', Proyecto::class)
                ->whereIn('firma_proyecto.firmable_id', $ambito->idsProyectoQuery())
                ->where('firma_proyecto.estado_revision', 'Pendiente')
                ->whereNull('firma_proyecto.deleted_at')
                ->whereNull('proyecto.deleted_at')
                ->groupByRaw($etiqueta)
                ->selectRaw(
                    "{$etiqueta} as etapa,
                     COUNT(DISTINCT proyecto.id) as proyectos,
                     ROUND(AVG(DATEDIFF(NOW(), firma_proyecto.created_at))) as dias_promedio,
                     MAX(DATEDIFF(NOW(), firma_proyecto.created_at)) as dias_maximo"
                )
                ->get()
                ->mapWithKeys(fn ($f): array => [
                    mb_strtolower(trim((string) $f->etapa)) => [
                        'proyectos' => (int) $f->proyectos,
                        'dias_promedio' => (int) $f->dias_promedio,
                        'dias_maximo' => (int) $f->dias_maximo,
                    ],
                ])
                ->all();
        });
    }

    /**
     * Firmas pendientes más antiguas del ámbito: dónde está atascado el flujo.
     *
     * @return list<array{proyecto_id:int,nombre:string,codigo:?string,etapa:string,rol:?string,dias:int}>
     */
    public function cuellosDeBotella(AmbitoPanel $ambito, int $limite = 5, int $diasMinimos = 0): array
    {
        return $this->recordar("cuellos:{$limite}:{$diasMinimos}", $ambito, function () use ($ambito, $limite, $diasMinimos): array {
            $etiqueta = $this->expresionNombreEtapa();

            // Un proyecto detenido suele tener varias firmas pendientes a la
            // vez (una por cargo). Se agrupa por proyecto para no repetir la
            // misma fila y se toma la espera más larga.
            return DB::table('firma_proyecto')
                ->join('proyecto', 'proyecto.id', '=', 'firma_proyecto.firmable_id')
                ->leftJoin('cargo_firma', 'cargo_firma.id', '=', 'firma_proyecto.cargo_firma_id')
                ->leftJoin('tipo_cargo_firma', 'tipo_cargo_firma.id', '=', 'cargo_firma.tipo_cargo_firma_id')
                ->where('firma_proyecto.firmable_type', Proyecto::class)
                ->whereIn('firma_proyecto.firmable_id', $ambito->idsProyectoQuery())
                ->where('firma_proyecto.estado_revision', 'Pendiente')
                ->whereNull('firma_proyecto.deleted_at')
                ->whereNull('proyecto.deleted_at')
                ->groupBy('proyecto.id', 'proyecto.nombre_proyecto', 'proyecto.codigo_proyecto')
                ->havingRaw('dias >= ?', [$diasMinimos])
                ->orderByDesc('dias')
                ->limit($limite)
                ->selectRaw(
                    "proyecto.id as proyecto_id,
                     proyecto.nombre_proyecto as nombre,
                     proyecto.codigo_proyecto as codigo,
                     MIN({$etiqueta}) as etapa,
                     MIN(firma_proyecto.rol_requerido) as rol,
                     COUNT(*) as firmas,
                     MAX(DATEDIFF(NOW(), firma_proyecto.created_at)) as dias"
                )
                ->get()
                ->map(fn ($f): array => [
                    'proyecto_id' => (int) $f->proyecto_id,
                    'nombre' => (string) $f->nombre,
                    'codigo' => $f->codigo,
                    'etapa' => (string) ($f->etapa ?: 'Sin etapa'),
                    'rol' => $f->rol,
                    'firmas' => (int) $f->firmas,
                    'dias' => (int) $f->dias,
                ])->all();
        });
    }

    /**
     * Nombre de etapa de una firma, con respaldo al cargo.
     *
     * firma_proyecto.etapa_nombre solo se llena desde que existe el flujo por
     * etapas; las firmas anteriores lo tienen nulo y su única identificación es
     * el cargo que las emitió.
     */
    private function expresionNombreEtapa(): string
    {
        return 'COALESCE(firma_proyecto.etapa_nombre, tipo_cargo_firma.nombre, cargo_firma.descripcion)';
    }

    // ── Auxiliares ───────────────────────────────────────────────────────────

    private function proyectosAdoptadosQuery(): \Illuminate\Database\Query\Builder
    {
        return DB::table('workflow_legacy_adoptions')
            ->where('adoptable_type', Proyecto::class)
            ->select('adoptable_id');
    }

    /**
     * @param  array<string,int|string>  $valores
     * @return list<array{etiqueta:string,valor:int,porcentaje:float}>
     */
    private function conPorcentajeSobreTotal(array $valores): array
    {
        $total = max(1, array_sum(array_map('intval', $valores)));
        $salida = [];

        foreach ($valores as $etiqueta => $valor) {
            $salida[] = [
                'etiqueta' => (string) $etiqueta,
                'valor' => (int) $valor,
                'porcentaje' => round(((int) $valor / $total) * 100, 1),
            ];
        }

        return $salida;
    }

    /**
     * @param  array<string,int>  $valores
     * @return list<array{etiqueta:string,valor:int,porcentaje:float}>
     */
    private function conPorcentajeSobreMaximo(array $valores): array
    {
        $maximo = max(1, ...array_map('intval', array_values($valores) ?: [0]));
        $salida = [];

        foreach ($valores as $etiqueta => $valor) {
            $salida[] = [
                'etiqueta' => (string) $etiqueta,
                'valor' => (int) $valor,
                'porcentaje' => round(((int) $valor / $maximo) * 100, 1),
            ];
        }

        return $salida;
    }

    /** @var array<string,bool> */
    private array $softDeletes = [];

    private function tieneSoftDeletes(string $tabla): bool
    {
        return $this->softDeletes[$tabla] ??= \Illuminate\Support\Facades\Schema::hasColumn($tabla, 'deleted_at');
    }

    /**
     * Memoiza por request y cachea por ámbito.
     *
     * La memoización es la que más pesa: cada loadMore() o cambio de año en
     * Livewire vuelve a ejecutar render() completo, y sin ella cada métrica se
     * recalcularía otra vez dentro de la misma petición.
     */
    private function recordar(string $sufijo, AmbitoPanel $ambito, Closure $fn): mixed
    {
        $clave = "nexo.panel.{$sufijo}.".$ambito->clave();

        if (array_key_exists($clave, $this->memo)) {
            return $this->memo[$clave];
        }

        $ttl = (int) config('nexo.dashboard.cache_ttl', 120);

        return $this->memo[$clave] = $ttl > 0
            ? Cache::remember($clave, $ttl, $fn)
            : $fn();
    }
}
