<?php

namespace App\Services\Workflow;

use App\Models\Personal\Empleado;
use App\Models\Proyecto\FlujoAprobacion;
use App\Models\Proyecto\FlujoAprobacionEtapa;
use App\Models\Proyecto\Proyecto;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;

class WorkflowReviewerResolver
{
    public function candidatosParaEtapa(FlujoAprobacionEtapa $etapa, Proyecto $proyecto): Collection
    {
        $this->validarConfiguracionEtapa($etapa, $proyecto);

        if ($etapa->alcance_academico === FlujoAprobacionEtapa::ALCANCE_PROYECTO) {
            return $this->candidatosDelProyecto($etapa, $proyecto);
        }

        return $this->queryCandidatosBase($etapa)
            ->tap(fn (Builder $query) => $this->aplicarFiltroAcademico($query, $etapa, $proyecto))
            ->get()
            ->unique('id')
            ->values();
    }

    public function candidatosPorUnidadParaEtapa(FlujoAprobacionEtapa $etapa, Proyecto $proyecto): Collection
    {
        $this->validarConfiguracionEtapa($etapa, $proyecto);

        if ($etapa->multiplicidad_revision !== FlujoAprobacionEtapa::MULTIPLICIDAD_POR_CADA_UNIDAD) {
            return collect();
        }

        return $this->unidadesAcademicas($etapa, $proyecto)
            ->map(fn ($unidad): array => [
                'tipo' => $etapa->alcance_academico,
                'unidad_id' => $unidad->id,
                'unidad_nombre' => $unidad->nombre,
                'candidatos' => $this->candidatosParaUnidad($etapa, $proyecto, (int) $unidad->id),
            ])
            ->values();
    }

    public function empleadoEsElegible(FlujoAprobacionEtapa $etapa, Proyecto $proyecto, Empleado|int $empleado): bool
    {
        $empleadoId = $empleado instanceof Empleado ? $empleado->id : $empleado;

        if (! $empleadoId || ! Empleado::query()->whereKey($empleadoId)->exists()) {
            return false;
        }

        return $this->candidatosParaEtapa($etapa, $proyecto)
            ->contains(fn (Empleado $candidato): bool => (int) $candidato->id === (int) $empleadoId);
    }

    public function validarEmpleadoElegible(FlujoAprobacionEtapa $etapa, Proyecto $proyecto, Empleado|int $empleado): Empleado
    {
        if (! $this->empleadoEsElegible($etapa, $proyecto, $empleado)) {
            throw new \RuntimeException(sprintf(
                'El empleado seleccionado no es elegible para la etapa "%s".',
                $etapa->nombre
            ));
        }

        $empleadoId = $empleado instanceof Empleado ? $empleado->id : $empleado;

        return Empleado::query()->findOrFail($empleadoId);
    }

    public function unidadesSinCandidatos(FlujoAprobacionEtapa $etapa, Proyecto $proyecto): Collection
    {
        if ($etapa->multiplicidad_revision !== FlujoAprobacionEtapa::MULTIPLICIDAD_POR_CADA_UNIDAD) {
            return collect();
        }

        return $this->candidatosPorUnidadParaEtapa($etapa, $proyecto)
            ->filter(fn (array $grupo): bool => $grupo['candidatos']->isEmpty())
            ->values();
    }

    protected function validarConfiguracionEtapa(FlujoAprobacionEtapa $etapa, Proyecto $proyecto): void
    {
        if (! $etapa->exists || ! $etapa->flujo_aprobacion_id || ! FlujoAprobacion::query()->whereKey($etapa->flujo_aprobacion_id)->exists()) {
            throw new \RuntimeException('La etapa no pertenece a un flujo de aprobación válido.');
        }

        if (! $etapa->activo) {
            throw new \RuntimeException('La etapa no se encuentra activa.');
        }

        if (! $etapa->tieneAlcanceAcademicoValido()) {
            throw new \RuntimeException('El alcance académico configurado para la etapa no es válido.');
        }

        if (! $etapa->tieneMultiplicidadRevisionValida()) {
            throw new \RuntimeException('La multiplicidad configurada para la etapa no es válida.');
        }

        if (! $etapa->rol_revisor_id && ! $etapa->usuario_responsable_id) {
            throw new \RuntimeException('La etapa no tiene un rol ni un usuario responsable configurado.');
        }

        if ($etapa->requiereFiltroAcademico() && $this->unidadesAcademicas($etapa, $proyecto)->isEmpty()) {
            throw new \RuntimeException($this->mensajeUnidadRequerida($etapa));
        }

        $porUnidad = $etapa->multiplicidad_revision === FlujoAprobacionEtapa::MULTIPLICIDAD_POR_CADA_UNIDAD;

        if ($porUnidad && ! in_array($etapa->alcance_academico, [
            FlujoAprobacionEtapa::ALCANCE_CENTRO,
            FlujoAprobacionEtapa::ALCANCE_DEPARTAMENTO,
            FlujoAprobacionEtapa::ALCANCE_CARRERA,
        ], true)) {
            throw new \RuntimeException('La multiplicidad por unidad no es válida para el alcance configurado.');
        }

        if (! $porUnidad && in_array($etapa->alcance_academico, [
            FlujoAprobacionEtapa::ALCANCE_GLOBAL,
            FlujoAprobacionEtapa::ALCANCE_SIN_FILTRO,
            FlujoAprobacionEtapa::ALCANCE_PROYECTO,
        ], true) && $etapa->multiplicidad_revision !== FlujoAprobacionEtapa::MULTIPLICIDAD_UNICO) {
            throw new \RuntimeException('La multiplicidad por unidad no es válida para el alcance configurado.');
        }

        if ($etapa->usuario_responsable_id && $porUnidad && $this->unidadesAcademicas($etapa, $proyecto)->count() > 1) {
            throw new \RuntimeException('Un responsable fijo no puede cubrir varias unidades académicas en esta configuración.');
        }
    }

    protected function queryCandidatosBase(FlujoAprobacionEtapa $etapa): Builder
    {
        return Empleado::query()
            ->with(['user.roles'])
            ->whereHas('user', function (Builder $query) use ($etapa): void {
                $query->whereNull('deleted_at');

                if ($etapa->usuario_responsable_id) {
                    $query->whereKey($etapa->usuario_responsable_id);
                }

                if ($etapa->rol_revisor_id) {
                    $query
                        ->where('active_role_id', $etapa->rol_revisor_id)
                        ->whereHas('roles', fn (Builder $roleQuery) => $roleQuery->where('roles.id', $etapa->rol_revisor_id));
                }
            })
            ->orderBy(User::query()->select('name')->whereColumn('users.id', 'empleado.user_id'))
            ->orderBy('empleado.id');
    }

    protected function aplicarFiltroAcademico(Builder $query, FlujoAprobacionEtapa $etapa, Proyecto $proyecto): void
    {
        match ($etapa->alcance_academico) {
            FlujoAprobacionEtapa::ALCANCE_CENTRO => $query->whereIn('centro_facultad_id', $this->centroIds($proyecto)),
            FlujoAprobacionEtapa::ALCANCE_DEPARTAMENTO => $query
                ->whereIn('departamento_academico_id', $this->departamentoIds($proyecto))
                ->when($this->centroIds($proyecto)->isNotEmpty(), fn (Builder $q) => $q->whereIn('centro_facultad_id', $this->centroIds($proyecto))),
            FlujoAprobacionEtapa::ALCANCE_CARRERA => $this->aplicarFiltroCarrera($query, $proyecto),
            default => null,
        };
    }

    protected function aplicarFiltroCarrera(Builder $query, Proyecto $proyecto): void
    {
        if (! Schema::hasColumn('empleado', 'carrera_id')) {
            throw new \RuntimeException('No existe una relación académica confiable entre empleados y carreras para resolver esta etapa.');
        }

        $query
            ->whereIn('carrera_id', $this->carreraIds($proyecto))
            ->when($this->departamentoIds($proyecto)->isNotEmpty(), fn (Builder $q) => $q->whereIn('departamento_academico_id', $this->departamentoIds($proyecto)))
            ->when($this->centroIds($proyecto)->isNotEmpty(), fn (Builder $q) => $q->whereIn('centro_facultad_id', $this->centroIds($proyecto)));
    }

    protected function candidatosParaUnidad(FlujoAprobacionEtapa $etapa, Proyecto $proyecto, int $unidadId): Collection
    {
        return $this->queryCandidatosBase($etapa)
            ->tap(function (Builder $query) use ($etapa, $proyecto, $unidadId): void {
                match ($etapa->alcance_academico) {
                    FlujoAprobacionEtapa::ALCANCE_CENTRO => $query->where('centro_facultad_id', $unidadId),
                    FlujoAprobacionEtapa::ALCANCE_DEPARTAMENTO => $query
                        ->where('departamento_academico_id', $unidadId)
                        ->when($this->centroIds($proyecto)->isNotEmpty(), fn (Builder $q) => $q->whereIn('centro_facultad_id', $this->centroIds($proyecto))),
                    FlujoAprobacionEtapa::ALCANCE_CARRERA => $this->aplicarFiltroCarreraUnidad($query, $proyecto, $unidadId),
                    default => null,
                };
            })
            ->get()
            ->unique('id')
            ->values();
    }

    protected function aplicarFiltroCarreraUnidad(Builder $query, Proyecto $proyecto, int $carreraId): void
    {
        if (! Schema::hasColumn('empleado', 'carrera_id')) {
            throw new \RuntimeException('No existe una relación académica confiable entre empleados y carreras para resolver esta etapa.');
        }

        $query
            ->where('carrera_id', $carreraId)
            ->when($this->departamentoIds($proyecto)->isNotEmpty(), fn (Builder $q) => $q->whereIn('departamento_academico_id', $this->departamentoIds($proyecto)))
            ->when($this->centroIds($proyecto)->isNotEmpty(), fn (Builder $q) => $q->whereIn('centro_facultad_id', $this->centroIds($proyecto)));
    }

    protected function candidatosDelProyecto(FlujoAprobacionEtapa $etapa, Proyecto $proyecto): Collection
    {
        $empleados = $this->empleadosResponsablesDelProyecto($proyecto);

        if ($etapa->usuario_responsable_id) {
            $responsableEmpleadoId = User::query()
                ->with('empleado')
                ->whereKey($etapa->usuario_responsable_id)
                ->first()
                ?->empleado
                ?->id;

            $empleados = $empleados->filter(fn (Empleado $empleado): bool => (int) $empleado->id === (int) $responsableEmpleadoId);
        }

        if ($etapa->rol_revisor_id) {
            $empleados = $empleados->filter(fn (Empleado $empleado): bool => $this->empleadoCumpleRol($empleado, (int) $etapa->rol_revisor_id));
        }

        return $empleados
            ->filter(fn (Empleado $empleado): bool => ! $empleado->trashed() && $empleado->user && ! $empleado->user->trashed())
            ->unique('id')
            ->sortBy(fn (Empleado $empleado): array => [(string) $empleado->user?->name, (int) $empleado->id])
            ->values();
    }

    protected function empleadoCumpleRol(Empleado $empleado, int $rolId): bool
    {
        $user = $empleado->user;

        return $user
            && ! $user->trashed()
            && (int) $user->active_role_id === $rolId
            && $user->roles()->where('roles.id', $rolId)->exists();
    }

    protected function empleadosResponsablesDelProyecto(Proyecto $proyecto): Collection
    {
        $coordinadores = $proyecto->coordinador_proyecto()
            ->with('empleado.user.roles')
            ->get()
            ->pluck('empleado')
            ->filter();

        $responsableRevision = $proyecto->responsable_revision()
            ->with('user.roles')
            ->first();

        return $coordinadores
            ->when($responsableRevision, fn (Collection $empleados) => $empleados->push($responsableRevision))
            ->unique('id')
            ->values();
    }

    protected function unidadesAcademicas(FlujoAprobacionEtapa $etapa, Proyecto $proyecto): Collection
    {
        return match ($etapa->alcance_academico) {
            FlujoAprobacionEtapa::ALCANCE_CENTRO => $proyecto->facultades_centros()->orderBy('nombre')->orderBy('centro_facultad.id')->get(),
            FlujoAprobacionEtapa::ALCANCE_DEPARTAMENTO => $proyecto->departamentos_academicos()->orderBy('nombre')->orderBy('departamento_academico.id')->get(),
            FlujoAprobacionEtapa::ALCANCE_CARRERA => $proyecto->carreras()->orderBy('nombre')->orderBy('carrera.id')->get(),
            default => collect(),
        };
    }

    protected function centroIds(Proyecto $proyecto): Collection
    {
        return $proyecto->facultades_centros()->pluck('centro_facultad.id')->map(fn ($id): int => (int) $id)->values();
    }

    protected function departamentoIds(Proyecto $proyecto): Collection
    {
        return $proyecto->departamentos_academicos()->pluck('departamento_academico.id')->map(fn ($id): int => (int) $id)->values();
    }

    protected function carreraIds(Proyecto $proyecto): Collection
    {
        return $proyecto->carreras()->pluck('carrera.id')->map(fn ($id): int => (int) $id)->values();
    }

    protected function mensajeUnidadRequerida(FlujoAprobacionEtapa $etapa): string
    {
        return match ($etapa->alcance_academico) {
            FlujoAprobacionEtapa::ALCANCE_CENTRO => 'La etapa requiere centros académicos, pero el proyecto no tiene centros asociados.',
            FlujoAprobacionEtapa::ALCANCE_DEPARTAMENTO => 'La etapa requiere departamentos académicos, pero el proyecto no tiene departamentos asociados.',
            FlujoAprobacionEtapa::ALCANCE_CARRERA => 'La etapa requiere carreras, pero el proyecto no tiene carreras asociadas.',
            default => 'La etapa requiere unidades académicas, pero el proyecto no tiene unidades asociadas.',
        };
    }
}
