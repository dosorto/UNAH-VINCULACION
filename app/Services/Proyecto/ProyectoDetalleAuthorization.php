<?php

namespace App\Services\Proyecto;

use App\Models\Personal\EmpleadoProyecto;
use App\Models\Proyecto\FirmaProyecto;
use App\Models\Proyecto\Proyecto;
use App\Models\User;

/** Autoriza las rutas documentales con el mismo alcance de acceso del detalle del proyecto. */
class ProyectoDetalleAuthorization
{
    public function puedeVer(Proyecto $proyecto, ?User $user): bool
    {
        if (! $user) {
            return false;
        }

        $activeRole = $user->activeRole;
        $permissionContext = $activeRole ?? $user;
        $puedeVerTodos = $activeRole?->name === 'admin'
            || (bool) $permissionContext?->hasPermissionTo('proyectos.historial')
            || (bool) $permissionContext?->hasPermissionTo('proyectos.solicitados')
            || (bool) $permissionContext?->hasPermissionTo('proyectos.revision-final');
        $puedeVerCentro = (bool) $permissionContext?->hasPermissionTo('director.proyectos');

        if ($puedeVerTodos
            || $proyecto->usuarioPuedeAuditarInformeFinal($user)
            || $proyecto->usuarioPuedeGestionarInformeFinal($user)) {
            return true;
        }

        $empleado = $user->empleado;
        $esFirmante = $empleado && FirmaProyecto::query()
            ->where('firmable_type', Proyecto::class)
            ->where('firmable_id', $proyecto->id)
            ->where('empleado_id', $empleado->id)
            ->exists();
        if ($esFirmante) {
            return true;
        }

        if ($puedeVerCentro) {
            return (bool) ($empleado?->centro_facultad_id
                && $proyecto->facultades_centros()->whereKey($empleado->centro_facultad_id)->exists());
        }

        return (bool) ($empleado && EmpleadoProyecto::query()
            ->where('proyecto_id', $proyecto->id)
            ->where('empleado_id', $empleado->id)
            ->exists());
    }
}
