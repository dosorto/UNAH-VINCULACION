<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Spatie\Permission\Models\Role;

class EnsureUserHasRole
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next)
    {
        $user = Auth::user();
        
        if ($user) {
            // Verificar si el usuario no tiene ningún rol asignado
            if (!$user->hasAnyRole(['admin', 'docente', 'Director/Enlace'])) {
                // Asignar el rol de docente por defecto
                $docenteRole = Role::where('name', 'docente')->first();
                if ($docenteRole) {
                    $user->assignRole('docente');
                    
                    // Si no tiene active_role_id, asignarlo
                    if (!$user->active_role_id) {
                        $user->active_role_id = $docenteRole->id;
                        $user->save();
                    }
                }
            }
            
            // Asegurar que tiene un active_role_id válido
            if (!$user->active_role_id && $user->roles->count() > 0) {
                $user->active_role_id = $user->roles->first()->id;
                $user->save();
            }
        }

        return $next($request);
    }
}
