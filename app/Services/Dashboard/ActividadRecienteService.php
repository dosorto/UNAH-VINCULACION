<?php

namespace App\Services\Dashboard;

use App\Models\Estado\EstadoProyecto;
use App\Models\Proyecto\DocumentoProyecto;
use App\Models\Proyecto\Proyecto;
use App\Support\Dashboard\AmbitoPanel;
use App\Support\Dashboard\TipoAmbito;
use Illuminate\Support\Collection;

/**
 * Actividad reciente del ámbito: cambios de estado de proyectos y de sus
 * documentos.
 *
 * Antes cada panel la calculaba a su manera y siempre sobre empleado_proyecto,
 * así que a un Director centro sin proyectos propios le salía vacía
 * (DashboardDirector devolvía collect() sin más). Al colgarla del ámbito, ese
 * mismo usuario ve el movimiento de su centro.
 *
 * No se cachea: es información que debe reflejar el último cambio.
 */
class ActividadRecienteService
{
    /**
     * @return Collection<int, object{tipo_elemento:string,nombre_elemento:string,estado:string,es_actual:bool,comentario:?string,fecha:string,fecha_orden:mixed,href:?string}>
     */
    public function para(AmbitoPanel $ambito, int $limite = 8): Collection
    {
        if ($ambito->tipo === TipoAmbito::Ninguno) {
            return collect();
        }

        $proyectoIds = $ambito->aplicarA(Proyecto::query())->pluck('proyecto.id');

        if ($proyectoIds->isEmpty()) {
            return collect();
        }

        $documentoIds = DocumentoProyecto::whereIn('proyecto_id', $proyectoIds)->pluck('id');

        return EstadoProyecto::query()
            ->where(function ($q) use ($proyectoIds, $documentoIds): void {
                $q->where(fn ($p) => $p->where('estadoable_type', Proyecto::class)
                    ->whereIn('estadoable_id', $proyectoIds));

                if ($documentoIds->isNotEmpty()) {
                    $q->orWhere(fn ($d) => $d->where('estadoable_type', DocumentoProyecto::class)
                        ->whereIn('estadoable_id', $documentoIds));
                }
            })
            // "Borrador" y "Autoguardado" son ruido: los genera el formulario al
            // guardarse solo, no representan actividad del flujo.
            ->whereHas('tipoestado', fn ($q) => $q->whereNotIn('nombre', ['Borrador', 'Autoguardado']))
            ->with(['tipoestado', 'estadoable'])
            ->orderByDesc('created_at')
            ->limit($limite)
            ->get()
            ->map(function (EstadoProyecto $estado): object {
                $esProyecto = $estado->estadoable_type === Proyecto::class;

                return (object) [
                    'tipo_elemento' => $esProyecto ? 'Proyecto' : 'Documento',
                    'nombre_elemento' => $esProyecto
                        ? ($estado->estadoable->nombre_proyecto ?? 'Proyecto')
                        : ($estado->estadoable->nombre ?? 'Documento'),
                    'estado' => $estado->tipoestado?->nombre ?? 'Sin estado',
                    'es_actual' => (bool) $estado->es_actual,
                    'comentario' => $estado->comentario,
                    'fecha' => $estado->created_at?->format('d/m/Y H:i') ?? '',
                    'fecha_orden' => $estado->created_at,
                    'href' => $esProyecto && $estado->estadoable
                        ? route('historialproyecto', $estado->estadoable_id)
                        : null,
                ];
            });
    }
}
