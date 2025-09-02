<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->alias([
            'role' => \Spatie\Permission\Middleware\RoleMiddleware::class,
            'permission' => \Spatie\Permission\Middleware\PermissionMiddleware::class,
            'role_or_permission' => \Spatie\Permission\Middleware\RoleOrPermissionMiddleware::class,
            'ensure_user_has_role' => \App\Http\Middleware\EnsureUserHasRole::class,
        ]);
       
        $middleware->redirectGuestsTo('/');

        // redirect users to the 'listarPais' route after login
        $middleware->redirectUsersTo('inicio');

        // Aplicar middleware global para usuarios autenticados
        $middleware->web(append: [
            \App\Http\Middleware\EnsureUserHasRole::class,
        ]);

        $middleware->trustProxies(at: '*');
        //
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
