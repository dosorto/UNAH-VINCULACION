<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

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
            $roles = $user->roles()->get();

            if ($roles->isEmpty()) {
                if ($user->active_role_id !== null) {
                    $user->active_role_id = null;
                    $user->save();
                }

                if (! $request->routeIs('logout')) {
                    abort(403, 'Tu usuario no tiene roles asignados. Contacta al administrador del sistema.');
                }

                return $next($request);
            }

            $activeRoleIsAssigned = $user->active_role_id
                && $roles->contains('id', (int) $user->active_role_id);

            if (! $activeRoleIsAssigned) {
                $user->active_role_id = $roles->first()->id;
                $user->save();
            }
        }

        return $next($request);
    }
}
