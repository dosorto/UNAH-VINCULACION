<?php

use Illuminate\Support\Facades\Route;
use App\Livewire\Demografia\Pais\CreatePais;
use App\Livewire\Demografia\Pais\ListPaises;
use App\Livewire\Demografia\Departamento\CreateDepartamento;
use App\Livewire\Demografia\Departamento\ListDepartamentos;
use App\Livewire\Demografia\Municipio\CreateMunicipio;
use App\Livewire\Demografia\Municipio\ListaMunicipios;
use App\Livewire\Demografia\Aldea\CreateAldea;
use App\Livewire\Demografia\Aldea\ListAldeas;
use App\Livewire\Demografia\Ciudad\CreateCiudad;
use App\Livewire\Personal\Empleado\CreateEmpleado;
use App\Livewire\Personal\Empleado\ListEmpleado;
use App\Livewire\Personal\Empleado\EditPerfil;
use App\Livewire\Demografia\Ciudad\ListaCiudad;
use App\Livewire\Personal\Permiso\ListPermisos;
use App\Livewire\Login\Login;
use App\Livewire\User\Users;
use App\Livewire\User\Roles;
use App\Livewire\Configuracion\Logs\ListLogs;
use App\Livewire\Auth\ForgotPasswordController;
use App\Livewire\Auth\ResetPasswordController;
use App\Http\Controllers\Auth\MicrosoftController;

use App\Livewire\Proyectos\Vinculacion\CreateProyectoVinculacion;
use App\Livewire\Proyectos\Vinculacion\ListProyectosVinculacion;
use App\Livewire\Proyectos\Vinculacion\ListProyectosSolicitado;


// Rutas para redireccionar a los usuario autenticados
Route::middleware(['guest'])->group(function () {
    // rutas para autenticación con Microsoft
    Route::get('auth/microsoft', [MicrosoftController::class, 'redirectToMicrosoft'])
        ->name('auth.microsoft');

    Route::get('auth/microsoft/callback', [MicrosoftController::class, 'handleMicrosoftCallback'])
        ->name('auth.microsoft.callback');

    Route::get('/', Login::class)
        ->name('login')
        ->middleware('guest');

    // Rutas para restablecimiento de contraseña olvidada
    Route::get('password/reset', ForgotPasswordController::class)
        ->name('password.request');
    Route::get('password/reset/{token}', ResetPasswordController::class)
        ->name('password.reset');
});



// Rutas para redireccionar a los usuario  no autenticados
Route::middleware(['auth'])->group(function () {

    // rutas agrupadas para el modulo de demografia :)
    Route::middleware(['auth'])->group(function () {

        Route::get('crearPais', CreatePais::class)
            ->name('crearPais');

        Route::get('listarPais', ListPaises::class)
            ->name('listarPaises');


        Route::get('crearDepartamento', CreateDepartamento::class)
            ->name('crearDepartamento');

        Route::get('ListarDepartamentos', ListDepartamentos::class)

            ->name('listarDepartamentos');
        Route::get('ListarCiudades', ListaCiudad::class)

            ->name('ListarCiudades');

        Route::get('crearCiudad', CreateCiudad::class)
            ->name('crearCiudad');


        Route::get('ListarAldeas', ListAldeas::class)
            ->name('ListarAldeas');

        Route::get('crearAldea', CreateAldea::class)
            ->name('crearAldea');

        Route::get('ListarMunicipios', ListaMunicipios::class)
            ->name('ListarMunicipios');

        Route::get('crearMunicipio', CreateMunicipio::class)
            ->name('crearMunicipio');
    });


    // rutas agrupadas para el modulo de Usuarios
    Route::middleware(['auth'])->group(function () {
        Route::get('/users', Users::class)
            ->name('Usuarios');

        Route::get('/roles', Roles::class)
            ->name('roles');

        Route::get('listarPermisos', ListPermisos::class)
            ->name('listPermisos');


        Route::get('/logout', function () {
            Auth::logout();
            return redirect('/');
        })
            ->name('logout');
    });


    // rutas agrupadas para el modulo de Personal
    Route::middleware(['auth'])->group(function () {

        Route::get('crearEmpleado', CreateEmpleado::class)
            ->name('crearEmpleado');

        Route::get('listarEmpleados', ListEmpleado::class)
            ->name('ListarEmpleados');

        Route::get('mi_perfil', EditPerfil::class)
            ->name('mi_perfil');
    });

    // rutas agrupadas para el modulo de Proyectos
    Route::middleware(['auth'])->group(function () {

        Route::get('crearProyectoVinculacion', CreateProyectoVinculacion::class)
            ->name('crearProyectoVinculacion');

        Route::get('listarProyectosVinculacion', ListProyectosVinculacion::class)
            ->name('listarProyectosVinculacion');

        Route::get('listarProyectosSolicitado', ListProyectosSolicitado::class)
            ->name('listarProyectosSolicitado');
    });


    // rutas agrupadas para el modulo de Configuración
    Route::middleware(['auth'])->group(function () {

        Route::get('listarLogs', ListLogs::class)
            ->name('listarLogs');

    });
});
