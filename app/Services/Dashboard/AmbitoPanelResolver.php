<?php

namespace App\Services\Dashboard;

use App\Models\User;
use App\Support\Dashboard\AmbitoPanel;
use App\Support\Dashboard\TipoAmbito;

/**
 * Decide qué datos ve cada usuario en el panel estadístico.
 *
 * El criterio no es nuevo: replica el de
 * HistorialProyecto::mount() (líneas 76-112) y
 * DirectorFacultadCentro\Proyectos\ListProyectos::mount(), que hasta ahora
 * estaba duplicado en esos dos sitios y ausente del panel.
 *
 * Motivo del cambio: DashboardDirector calculaba todo sobre empleado_proyecto,
 * así que los roles que solo revisan (Director centro, Director Vinculacion)
 * veían el panel entero en cero.
 */
final class AmbitoPanelResolver
{
    public function para(?User $user): AmbitoPanel
    {
        if (! $user) {
            return AmbitoPanel::ninguno();
        }

        $rolActivo = $user->activeRole;
        $rolActivoNombre = $rolActivo?->name;

        // El contexto de permisos es el ROL ACTIVO, no el usuario: quien tiene
        // varios roles debe ver el panel del que eligió en la barra superior.
        // Si no hay rol activo se cae al usuario, igual que HistorialProyecto.
        $contexto = $rolActivo ?? $user;

        $empleado = $user->empleado;
        $empleadoId = $empleado?->id;
        $permisos = $this->permisosDe($contexto);

        if ($this->puedeVerTodo($permisos, $rolActivoNombre)) {
            return new AmbitoPanel(
                tipo: TipoAmbito::Global,
                empleadoId: $empleadoId,
                userId: $user->id,
                etiqueta: 'Toda la UNAH',
                rolActivo: $rolActivoNombre,
            );
        }

        if (in_array('director.proyectos', $permisos, true)) {
            return $this->ambitoDeUnidad($user, $rolActivoNombre, $empleadoId);
        }

        if (in_array('docente.proyectos', $permisos, true) && $empleadoId) {
            return new AmbitoPanel(
                tipo: TipoAmbito::Personal,
                empleadoId: $empleadoId,
                userId: $user->id,
                etiqueta: 'Mis registros',
                rolActivo: $rolActivoNombre,
            );
        }

        return AmbitoPanel::ninguno($rolActivoNombre);
    }

    /**
     * Nombres de permiso del contexto.
     *
     * Se resuelven de una vez, en lugar de llamar a hasPermissionTo() por
     * permiso, porque ese método lanza PermissionDoesNotExist cuando el
     * permiso no está sembrado — y el panel es la primera pantalla tras el
     * login, así que una instalación incompleta dejaría al usuario sin poder
     * entrar. De paso evita una consulta por comprobación.
     *
     * @return list<string>
     */
    private function permisosDe(object $contexto): array
    {
        try {
            return $contexto->getAllPermissions()->pluck('name')->all();
        } catch (\Throwable) {
            return [];
        }
    }

    /** @param  list<string>  $permisos */
    private function puedeVerTodo(array $permisos, ?string $rolActivoNombre): bool
    {
        if ($rolActivoNombre === 'admin') {
            return true;
        }

        foreach (['proyectos.historial', 'proyectos.solicitados', 'proyectos.revision-final'] as $permiso) {
            if (in_array($permiso, $permisos, true)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Ámbito de centro o departamento para los roles de revisión de unidad.
     *
     * Qué roles miran su departamento en vez de su centro se declara en
     * config('nexo.dashboard.ambitos_por_rol'), no con un match de cadenas.
     */
    private function ambitoDeUnidad(User $user, ?string $rolActivoNombre, ?int $empleadoId): AmbitoPanel
    {
        $empleado = $user->empleado;
        $porRol = config('nexo.dashboard.ambitos_por_rol', []);
        $prefiereDepartamento = ($porRol[$rolActivoNombre] ?? null) === 'departamento';

        if ($prefiereDepartamento && $empleado?->departamento_academico_id) {
            $departamento = $empleado->departamento_academico;

            return new AmbitoPanel(
                tipo: TipoAmbito::Departamento,
                empleadoId: $empleadoId,
                userId: $user->id,
                centroFacultadId: $empleado->centro_facultad_id,
                departamentoAcademicoId: $empleado->departamento_academico_id,
                etiqueta: $departamento?->nombre ?: 'Mi departamento académico',
                rolActivo: $rolActivoNombre,
            );
        }

        $centroFacultadId = $this->resolverCentro($user);

        if ($centroFacultadId) {
            $centro = $empleado?->centro_facultad;

            return new AmbitoPanel(
                tipo: TipoAmbito::Centro,
                empleadoId: $empleadoId,
                userId: $user->id,
                centroFacultadId: $centroFacultadId,
                departamentoAcademicoId: $empleado?->departamento_academico_id,
                etiqueta: $centro?->nombre ?: 'Mi centro o facultad',
                rolActivo: $rolActivoNombre,
            );
        }

        // Sin centro asignado (NewUserOnboardingService crea empleados sin él).
        // Se degrada a los proyectos donde el usuario tiene firma, no a
        // "personal": un revisor sin proyectos propios volvería a ver el panel
        // vacío que este rediseño busca eliminar.
        return new AmbitoPanel(
            tipo: $empleadoId ? TipoAmbito::Revision : TipoAmbito::Ninguno,
            empleadoId: $empleadoId,
            userId: $user->id,
            etiqueta: $empleadoId ? 'Proyectos asignados a mí' : 'Sin datos disponibles',
            rolActivo: $rolActivoNombre,
            centroIndeterminado: true,
        );
    }

    /**
     * Centro del empleado, con respaldos: la columna puede venir nula y aun así
     * ser deducible por el departamento académico o la carrera.
     */
    private function resolverCentro(User $user): ?int
    {
        $empleado = $user->empleado;

        if (! $empleado) {
            return null;
        }

        return $empleado->centro_facultad_id
            ?: $empleado->departamento_academico?->centro_facultad_id
            ?: $empleado->carrera?->facultad_centro_id;
    }
}
