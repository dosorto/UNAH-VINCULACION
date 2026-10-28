<?php

namespace App\Http\Middleware;

use App\Support\ProfileCompletion;
use Closure;
use Illuminate\Support\Facades\Auth;

/**
 * Mientras el perfil esté incompleto (ProfileCompletion::isRequired), cualquier página lleva a
 * completarlo: no se puede navegar a ningún otro lado.
 */
class VerificarPermisoDeCompletarPerfil
{
    public function handle($request, Closure $next)
    {
        // Livewire needs these technical routes to render, upload files and
        // submit the profile form. Logout must always remain available.
        if ($request->routeIs('completar_perfil', 'logout', 'livewire.*')) {
            return $next($request);
        }

        if (! ProfileCompletion::isRequired(Auth::user())) {
            return $next($request);
        }

        return redirect()->route('completar_perfil');
    }
}
