<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Auth;

class VerificarPermisoDeCompletarPerfil
{
    public function handle($request, Closure $next)
    {
        $user = Auth::user();
       
        if ($user && $user->hasPermissionTo('cambiar-datos-personales')) {
          
            // Evita un bucle infinito si ya está en la ruta de completar perfil
            if (!$request->is('mi_perfil')) {
                return redirect()->route('mi_perfil');
            }
        }


        return $next($request);
    }
}
