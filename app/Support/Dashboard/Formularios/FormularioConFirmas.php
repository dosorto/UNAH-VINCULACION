<?php

namespace App\Support\Dashboard\Formularios;

use App\Support\Dashboard\AmbitoPanel;
use App\Support\Dashboard\EstadoGeneral;
use App\Support\Proyecto\EtapaActualFirma;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Formulario sobre el motor común: estado actual en `estado_proyecto` y firmas
 * por etapa en `firma_proyecto`, ambos polimórficos. Estados, etapa actual y
 * espera se resuelven igual para todos; cada subclase solo dice qué registros
 * le pertenecen dentro de un ámbito.
 */
abstract class FormularioConFirmas implements FormularioPanel
{
    use LeeEtapasDelFlujo;

    public function __construct(
        protected readonly string $codigo,
        protected readonly string $nombre,
        protected readonly ?string $tipoAccion = null,
    ) {}

    public function codigo(): string
    {
        return $this->codigo;
    }

    public function nombre(): string
    {
        return $this->nombre;
    }

    public function tipoAccion(): ?string
    {
        return $this->tipoAccion;
    }

    /** Clase del modelo cuyo estado vive en `estado_proyecto`. */
    abstract protected function modelo(): string;

    /** Subconsulta con los ids de los registros del formulario en el ámbito. */
    abstract protected function idsQuery(AmbitoPanel $ambito): Builder;

    /**
     * Firmas que pueden estar esperando por un trámite del formulario, sobre
     * la tabla `firma_proyecto`. Por defecto, las del propio registro.
     */
    protected function consultaFirmas(AmbitoPanel $ambito): Builder
    {
        return DB::table('firma_proyecto')
            ->where('firma_proyecto.firmable_type', $this->modelo())
            ->whereIn('firma_proyecto.firmable_id', $this->idsQuery($ambito));
    }

    /** Expresión SQL con el proceso de la firma. */
    protected function procesoSql(): string
    {
        return "'".self::INSCRIPCION."'";
    }

    public function conteos(AmbitoPanel $ambito): array
    {
        $conteos = EstadoGeneral::vacio();
        $total = DB::query()->fromSub($this->idsQuery($ambito), 'registros')->count();

        if ($total === 0) {
            return $conteos;
        }

        // Un estado actual por registro, aunque haya quedado más de una fila
        // marcada como actual.
        $actuales = DB::table('estado_proyecto')
            ->selectRaw('MAX(id) as id')
            ->where('estadoable_type', $this->modelo())
            ->where('es_actual', true)
            ->whereIn('estadoable_id', $this->idsQuery($ambito))
            ->groupBy('estadoable_id');

        DB::table('estado_proyecto as ep')
            ->joinSub($actuales, 'actuales', 'actuales.id', '=', 'ep.id')
            ->selectRaw('ep.tipo_estado_id, COUNT(*) as cantidad')
            ->groupBy('ep.tipo_estado_id')
            ->get()
            ->each(function ($fila) use (&$conteos): void {
                $conteos[EstadoGeneral::deId((int) $fila->tipo_estado_id)] += (int) $fila->cantidad;
            });

        // Sin ningún estado registrado, el trámite todavía no se envió.
        $conteos[EstadoGeneral::BORRADOR] += max(0, $total - array_sum($conteos));

        return $conteos;
    }

    public function esperando(AmbitoPanel $ambito): Collection
    {
        $llegada = EtapaActualFirma::llegadaSql();

        $query = $this->consultaFirmas($ambito)
            ->leftJoin('cargo_firma', 'cargo_firma.id', '=', 'firma_proyecto.cargo_firma_id')
            ->leftJoin('tipo_cargo_firma', 'tipo_cargo_firma.id', '=', 'cargo_firma.tipo_cargo_firma_id');

        return EtapaActualFirma::filtrar($query)
            ->selectRaw(
                "firma_proyecto.firmable_type, firma_proyecto.firmable_id,
                 firma_proyecto.flujo_aprobacion_etapa_id as etapa_id,
                 COALESCE(firma_proyecto.etapa_nombre, tipo_cargo_firma.nombre, cargo_firma.descripcion) as etapa,
                 firma_proyecto.orden_revision as orden,
                 firma_proyecto.rol_requerido as rol,
                 {$this->procesoSql()} as proceso,
                 DATEDIFF(NOW(), {$llegada}) as dias"
            )
            ->get()
            // Una fila por trámite: una etapa enviada a todos los usuarios de
            // un rol tiene varias firmas candidatas.
            ->unique(fn ($fila): string => $fila->firmable_type.'#'.$fila->firmable_id)
            ->map(fn ($fila): array => [
                'tramite' => $fila->firmable_type.'#'.$fila->firmable_id,
                'proceso' => (string) $fila->proceso,
                'etapa_id' => $fila->etapa_id !== null ? (int) $fila->etapa_id : null,
                'etapa' => (string) ($fila->etapa ?: 'Sin etapa'),
                'orden' => $fila->orden !== null ? (int) $fila->orden : null,
                'rol' => $fila->rol,
                'heredado' => $fila->etapa_id === null,
                'dias' => max(0, (int) $fila->dias),
            ])
            ->values();
    }
}
