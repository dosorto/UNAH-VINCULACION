<?php

namespace App\Http\Middleware;

use App\Support\ProfileCompletion;
use Closure;
use Illuminate\Support\Facades\Auth;

class VerificarPermisoDeCompletarPerfil
{
    public function handle($request, Closure $next)
    {
        $user = Auth::user();

        if (! ProfileCompletion::isRequired($user)) {
            return $next($request);
        }

        // Livewire needs these technical routes to render, upload files and
        // submit the profile form. Logout must always remain available.
        if ($request->routeIs('completar_perfil', 'logout', 'livewire.*')) {
            return $next($request);
        }

        return redirect()->route('completar_perfil');
    }
}
