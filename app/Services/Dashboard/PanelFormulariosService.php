<?php

namespace App\Services\Dashboard;

use App\Support\Dashboard\AmbitoPanel;
use App\Support\Dashboard\EstadoGeneral;
use App\Support\Dashboard\Formularios\FormularioPanel;
use Closure;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

/**
 * Estado de los trámites de todos los formularios, sin conocer ninguno.
 *
 * Sustituye a los carriles por familia, que dibujaban un recorrido propio por
 * cada tipo de trámite: con más de treinta formularios, cada uno con su flujo,
 * eso no escala. Aquí todo se reduce a lo que comparten (estado general, etapa
 * actual y espera) y el detalle de un formulario se pide de uno en uno.
 *
 * Cacheado por ámbito como el resto de métricas agregadas
 * (nexo.dashboard.cache_ttl).
 */
class PanelFormulariosService
{
    /** @var array<string, mixed> */
    private array $memo = [];

    public function __construct(private readonly RegistroFormularios $registro) {}

    /**
     * Totales por estado general, sumando todos los formularios.
     *
     * @return array{total:int,conteos:array<string,int>}
     */
    public function resumen(AmbitoPanel $ambito): array
    {
        $conteos = EstadoGeneral::vacio();

        foreach ($this->porFormulario($ambito) as $fila) {
            foreach ($fila['conteos'] as $clave => $valor) {
                $conteos[$clave] += $valor;
            }
        }

        return ['total' => array_sum($conteos), 'conteos' => $conteos];
    }

    /**
     * Formularios × estado general, agrupados por tipo de acción. Solo los que
     * tienen trámites en el ámbito.
     *
     * @return list<array{tipo_accion:string,etiqueta:string,conteos:array<string,int>,total:int,esperando:int,formularios:list<array>}>
     */
    public function matriz(AmbitoPanel $ambito): array
    {
        return collect($this->porFormulario($ambito))
            ->filter(fn (array $fila): bool => $fila['total'] > 0)
            ->groupBy(fn (array $fila): string => (string) $fila['tipo_accion'])
            ->map(function (Collection $formularios, string $tipoAccion): array {
                $conteos = EstadoGeneral::vacio();

                foreach ($formularios as $fila) {
                    foreach ($fila['conteos'] as $clave => $valor) {
                        $conteos[$clave] += $valor;
                    }
                }

                return [
                    'tipo_accion' => $tipoAccion,
                    'etiqueta' => $this->registro->etiquetaTipoAccion($tipoAccion),
                    'conteos' => $conteos,
                    'total' => array_sum($conteos),
                    'esperando' => $formularios->sum('esperando'),
                    'formularios' => $formularios->sortByDesc('total')->values()->all(),
                ];
            })
            ->sortByDesc('total')
            ->values()
            ->all();
    }

    /**
     * Etapas donde más trámites esperan, de todos los formularios.
     *
     * @return list<array{codigo:string,formulario:string,proceso:string,etapa:string,rol:?string,heredado:bool,tramites:int,dias_promedio:int,dias_maximo:int}>
     */
    public function atascos(AmbitoPanel $ambito, int $limite = 8): array
    {
        return $this->registro->todos()
            ->flatMap(fn (FormularioPanel $formulario): Collection => $this->esperandoDe($ambito, $formulario)
                ->map(fn (array $fila): array => $fila + ['codigo' => $formulario->codigo(), 'formulario' => $formulario->nombre()]))
            ->groupBy(fn (array $fila): string => implode('|', [$fila['codigo'], $fila['proceso'], $fila['etapa'], (int) $fila['heredado']]))
            ->map(fn (Collection $grupo): array => [
                'codigo' => $grupo->first()['codigo'],
                'formulario' => $grupo->first()['formulario'],
                'proceso' => $grupo->first()['proceso'],
                'etapa' => $grupo->first()['etapa'],
                'rol' => $grupo->pluck('rol')->filter()->first(),
                'heredado' => $grupo->first()['heredado'],
                'tramites' => $grupo->count(),
                'dias_promedio' => (int) round($grupo->avg('dias')),
                'dias_maximo' => (int) $grupo->max('dias'),
            ])
            ->sortBy([['tramites', 'desc'], ['dias_maximo', 'desc']])
            ->take($limite)
            ->values()
            ->all();
    }

    /**
     * Recorrido de un formulario: sus etapas configuradas, proceso por proceso,
     * con cuántos trámites esperan en cada una. Se pide de uno en uno.
     *
     * Las esperas en etapas que ya no están en el flujo vigente van aparte
     * («otras»), y los expedientes sin adaptar al flujo, agrupados por el cargo
     * que los atiende.
     *
     * @return array{codigo:string,nombre:string,procesos:list<array>,heredados:list<array>,sin_flujo:bool,esperando:int}|null
     */
    public function detalle(AmbitoPanel $ambito, ?string $codigo): ?array
    {
        $formulario = $this->registro->porCodigo($codigo);

        if (! $formulario) {
            return null;
        }

        return $this->recordar("detalle:{$formulario->codigo()}", $ambito, function () use ($ambito, $formulario): array {
            $esperando = $this->esperandoDe($ambito, $formulario);
            $configuradas = $formulario->etapasConfiguradas();
            $procesos = [];

            foreach (FormularioPanel::PROCESOS as $clave => $etiqueta) {
                $etapas = $configuradas->filter(fn (array $etapa): bool => in_array($clave, $etapa['procesos'], true))->values();
                $filas = $esperando->where('proceso', $clave)->where('heredado', false);
                $ids = $etapas->pluck('id');

                $nodos = $etapas->map(fn (array $etapa): array => $this->nodo(
                    $etapa['nombre'],
                    $filas->where('etapa_id', $etapa['id']),
                    $etapa['rol']
                ));
                $otras = $filas->reject(fn (array $fila): bool => $ids->contains($fila['etapa_id']))
                    ->groupBy('etapa')
                    ->map(fn (Collection $grupo, string $nombre): array => $this->nodo($nombre, $grupo, $grupo->first()['rol']))
                    ->values();

                if ($nodos->isEmpty() && $otras->isEmpty()) {
                    continue;
                }

                $procesos[] = [
                    'clave' => $clave,
                    'etiqueta' => $etiqueta,
                    'etapas' => $nodos->all(),
                    'otras' => $otras->all(),
                    'esperando' => $filas->count(),
                ];
            }

            return [
                'codigo' => $formulario->codigo(),
                'nombre' => $formulario->nombre(),
                'procesos' => $procesos,
                'heredados' => $esperando->where('heredado', true)
                    ->groupBy('etapa')
                    ->map(fn (Collection $grupo, string $cargo): array => $this->nodo($cargo, $grupo, null))
                    ->sortByDesc('valor')
                    ->values()
                    ->all(),
                'sin_flujo' => $configuradas->isEmpty(),
                'esperando' => $esperando->count(),
            ];
        });
    }

    /** Formulario que abre el detalle por defecto: el que más trámites tiene esperando. */
    public function formularioConMasEspera(AmbitoPanel $ambito): ?string
    {
        $fila = collect($this->porFormulario($ambito))
            ->filter(fn (array $fila): bool => $fila['total'] > 0)
            ->sortBy([['esperando', 'desc'], ['total', 'desc']])
            ->first();

        return $fila['codigo'] ?? null;
    }

    /**
     * Opciones del selector del detalle: los formularios con trámites.
     *
     * @return array<string,string> código => nombre
     */
    public function opcionesDetalle(AmbitoPanel $ambito): array
    {
        return collect($this->porFormulario($ambito))
            ->filter(fn (array $fila): bool => $fila['total'] > 0)
            ->mapWithKeys(fn (array $fila): array => [$fila['codigo'] => $fila['codigo'].' · '.$fila['nombre']])
            ->all();
    }

    /** @return list<array{codigo:string,nombre:string,tipo_accion:?string,conteos:array<string,int>,total:int,esperando:int,ambito_parcial:bool}> */
    private function porFormulario(AmbitoPanel $ambito): array
    {
        return $this->recordar('por-formulario', $ambito, fn (): array => $this->registro->todos()
            ->map(function (FormularioPanel $formulario) use ($ambito): array {
                $conteos = $formulario->conteos($ambito);

                return [
                    'codigo' => $formulario->codigo(),
                    'nombre' => $formulario->nombre(),
                    'tipo_accion' => $formulario->tipoAccion(),
                    'conteos' => $conteos,
                    'total' => array_sum($conteos),
                    'esperando' => $this->esperandoDe($ambito, $formulario)->count(),
                    // Si el formulario no sabe acotarse al ámbito, su cifra es
                    // institucional y el panel debe decirlo.
                    'ambito_parcial' => ! $formulario->admiteAmbito($ambito),
                ];
            })
            ->values()
            ->all());
    }

    /** @return Collection<int, array> */
    private function esperandoDe(AmbitoPanel $ambito, FormularioPanel $formulario): Collection
    {
        return collect($this->recordar(
            "esperando:{$formulario->codigo()}",
            $ambito,
            fn (): array => $formulario->esperando($ambito)->all()
        ));
    }

    /** @param  Collection<int, array>  $filas */
    private function nodo(string $etiqueta, Collection $filas, ?string $rol): array
    {
        $diasMaximo = (int) $filas->max('dias');

        return [
            'etiqueta' => $etiqueta,
            'rol' => $rol,
            'valor' => $filas->count(),
            'dias_promedio' => (int) round($filas->avg('dias') ?? 0),
            'dias_maximo' => $diasMaximo,
            // Mismo semáforo que la lista de pendientes.
            'tono' => match (true) {
                $filas->isEmpty() => 'neutro',
                $diasMaximo >= 15 => 'riesgo',
                $diasMaximo >= 7 => 'acento',
                default => 'info',
            },
        ];
    }

    private function recordar(string $sufijo, AmbitoPanel $ambito, Closure $fn): mixed
    {
        $clave = "nexo.panel.formularios.{$sufijo}.".$ambito->clave();

        if (array_key_exists($clave, $this->memo)) {
            return $this->memo[$clave];
        }

        $ttl = (int) config('nexo.dashboard.cache_ttl', 120);

        return $this->memo[$clave] = $ttl > 0
            ? Cache::remember($clave, $ttl, $fn)
            : $fn();
    }
}
