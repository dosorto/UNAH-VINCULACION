<?php

namespace App\Services\Proyecto;

use App\Models\Proyecto\FlujoAprobacionEtapa;
use App\Models\Proyecto\Proyecto;
use App\Models\User;
use Illuminate\Support\Collection;

class ProyectoWorkflowService
{
    public function etapas(Proyecto $proyecto, string $proceso, bool $soloActivas = true): Collection
    {
        $flujo = $proyecto->resolveFlujoAprobacion();

        if (! $flujo) {
            return collect();
        }

        $columna = $this->columnaAplicacion($proceso);

        return $flujo->etapas
            ->filter(fn (FlujoAprobacionEtapa $etapa): bool => (bool) $etapa->{$columna})
            ->when($soloActivas, fn (Collection $etapas): Collection => $etapas
                ->filter(fn (FlujoAprobacionEtapa $etapa): bool => (bool) $etapa->activo))
            ->sortBy([
                ['orden', 'asc'],
                ['id', 'asc'],
            ])
            ->values();
    }

    public function etapasInscripcion(Proyecto $proyecto): Collection
    {
        return $this->etapas($proyecto, Proyecto::FLUJO_INSCRIPCION);
    }

    public function etapasInformeIntermedio(Proyecto $proyecto): Collection
    {
        return $this->etapas($proyecto, Proyecto::FLUJO_INFORME_INTERMEDIO);
    }

    public function etapasCierre(Proyecto $proyecto): Collection
    {
        return $this->etapas($proyecto, Proyecto::FLUJO_CIERRE_PROYECTO);
    }

    public function primeraEtapa(Proyecto $proyecto, string $proceso): ?FlujoAprobacionEtapa
    {
        return $this->etapas($proyecto, $proceso)->first();
    }

    public function siguienteEtapa(
        Proyecto $proyecto,
        string $proceso,
        int $etapaActualId
    ): ?FlujoAprobacionEtapa {
        $etapas = $this->etapas($proyecto, $proceso);
        $indice = $etapas->search(fn (FlujoAprobacionEtapa $etapa): bool => $etapa->id === $etapaActualId);

        return $indice === false ? null : $etapas->get($indice + 1);
    }

    /**
     * @param  array<int|string, int|string>  $usuariosElegidosPorEtapa
     * @return array<int, int> empleado_id indexado por etapa_id
     */
    public function resolverEmpleados(
        Proyecto $proyecto,
        string $proceso,
        array $usuariosElegidosPorEtapa = []
    ): array {
        $resultado = [];

        foreach ($this->etapas($proyecto, $proceso) as $etapa) {
            if (! $etapa->cargo_firma_id) {
                throw new \RuntimeException(sprintf('La etapa "%s" no tiene cargo de firma configurado.', $etapa->nombre));
            }

            $usuario = $this->resolverUsuarioEtapa($etapa, $usuariosElegidosPorEtapa);
            $empleado = $usuario?->empleado;

            if (! $empleado || $empleado->trashed()) {
                throw new \RuntimeException(sprintf(
                    'No existe un responsable válido para la etapa "%s".',
                    $etapa->nombre
                ));
            }

            $resultado[$etapa->id] = $empleado->id;
        }

        return $resultado;
    }

    public function destinatariosSeleccionables(Proyecto $proyecto, string $proceso): Collection
    {
        return $this->etapas($proyecto, $proceso)
            ->filter(fn (FlujoAprobacionEtapa $etapa): bool => (bool) $etapa->emisor_define_destinatario)
            ->mapWithKeys(function (FlujoAprobacionEtapa $etapa): array {
                $usuarios = $etapa->rolRevisor?->name
                    ? User::role($etapa->rolRevisor->name)
                        ->whereHas('empleado')
                        ->with('empleado')
                        ->orderBy('name')
                        ->get()
                    : collect();

                return [$etapa->id => [
                    'etapa' => $etapa,
                    'usuarios' => $usuarios,
                ]];
            });
    }

    public function inscripcionCompletada(Proyecto $proyecto): bool
    {
        $flujo = $proyecto->resolveFlujoAprobacion();
        $etapas = $this->etapasInscripcion($proyecto)
            ->filter(fn (FlujoAprobacionEtapa $etapa): bool => filled($etapa->cargo_firma_id))
            ->values();

        if (! $flujo || $etapas->isEmpty()) {
            return false;
        }

        $ultimoCiclo = (int) $proyecto->firma_proyecto()
            ->where('flujo_aprobacion_id', $flujo->id)
            ->whereIn('flujo_aprobacion_etapa_id', $etapas->pluck('id'))
            ->whereNull('deleted_at')
            ->max('revision_ciclo');

        if ($ultimoCiclo < 1) {
            return false;
        }

        $firmas = $proyecto->firma_proyecto()
            ->where('flujo_aprobacion_id', $flujo->id)
            ->where('revision_ciclo', $ultimoCiclo)
            ->whereIn('flujo_aprobacion_etapa_id', $etapas->pluck('id'))
            ->whereNull('deleted_at')
            ->get()
            ->groupBy('flujo_aprobacion_etapa_id');

        return $etapas->every(function (FlujoAprobacionEtapa $etapa) use ($firmas): bool {
            $activas = $firmas->get($etapa->id, collect())
                ->reject(fn ($firma): bool => $firma->estado_revision === 'Anulado')
                ->values();

            return $activas->count() === 1 && $activas->first()->estado_revision === 'Aprobado';
        });
    }

    private function resolverUsuarioEtapa(
        FlujoAprobacionEtapa $etapa,
        array $usuariosElegidosPorEtapa
    ): ?User {
        $etapa->loadMissing(['usuarioResponsable.empleado', 'rolRevisor']);

        if ($etapa->emisor_define_destinatario) {
            $usuarioId = $usuariosElegidosPorEtapa[$etapa->id] ?? null;

            if (! $usuarioId) {
                throw new \RuntimeException(sprintf(
                    'Debe seleccionar un destinatario para la etapa "%s".',
                    $etapa->nombre
                ));
            }

            $usuario = User::with('empleado')->find($usuarioId);
            $rol = $etapa->rolRevisor?->name;

            if (! $usuario || ! $rol || ! $usuario->hasRole($rol)) {
                throw new \RuntimeException(sprintf(
                    'El destinatario seleccionado para la etapa "%s" no pertenece al rol requerido.',
                    $etapa->nombre
                ));
            }

            return $usuario;
        }

        if ($etapa->usuarioResponsable) {
            return $etapa->usuarioResponsable;
        }

        if ($etapa->requiere_asignacion) {
            throw new \RuntimeException(sprintf(
                'La etapa "%s" requiere un responsable fijo válido antes de enviar.',
                $etapa->nombre
            ));
        }

        $rol = $etapa->rolRevisor?->name;

        return $rol
            ? User::role($rol)->whereHas('empleado')->with('empleado')->orderBy('name')->first()
            : null;
    }

    private function columnaAplicacion(string $proceso): string
    {
        return match ($proceso) {
            Proyecto::FLUJO_INSCRIPCION => 'aplica_inscripcion',
            Proyecto::FLUJO_INFORME_INTERMEDIO => 'aplica_informe_intermedio',
            Proyecto::FLUJO_CIERRE_PROYECTO => 'aplica_cierre_proyecto',
            default => throw new \InvalidArgumentException('El proceso de workflow indicado no es válido.'),
        };
    }
}
