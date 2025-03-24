<?php

namespace App\Policies\UnidadAcademica;

use App\Models\UnidadAcademica\FacultadCentro;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class FacultadCentroPolicy
{

    public function adminCentroFacultadProyectos(User $user, FacultadCentro $facultadCentro): bool
    {
        // Verifica si el usuario tiene alguno de los permisos
        if ($user->hasPermissionTo('proyectos-admin-proyectos')) {
            dd('entro');
            return true; // Este permiso le da acceso total
        }

        if ($user->hasPermissionTo('admin_centro_facultad-proyectos')) {
            // Si tiene este permiso, valida que pertenezca a la misma facultad/centro
            return $user->empleado->facultad_centro_id === $facultadCentro->id;
        }

        return false; // Si no tiene ningún permiso, denegar acceso
    }

  
}
