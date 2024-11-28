<?php

use App\Livewire\User\Roles;
use App\Livewire\User\Users;
use App\Livewire\Login\Login;
use App\Livewire\Inicio\InicioAdmin;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Route;
use App\Livewire\Demografia\Pais\CreatePais;
use App\Livewire\Demografia\Pais\ListPaises;
use App\Livewire\Configuracion\Logs\ListLogs;
use App\Livewire\Demografia\Aldea\ListAldeas;
use App\Livewire\Auth\ResetPasswordController;
use App\Livewire\Demografia\Aldea\CreateAldea;
use App\Livewire\Personal\Empleado\EditPerfil;
use App\Livewire\Auth\ForgotPasswordController;
use App\Livewire\Demografia\Ciudad\ListaCiudad;
use App\Livewire\Personal\Permiso\ListPermisos;
use App\Livewire\Demografia\Ciudad\CreateCiudad;
use App\Livewire\Personal\Empleado\ListEmpleado;
use App\Http\Controllers\Auth\MicrosoftController;
use App\Livewire\Personal\Empleado\CreateEmpleado;
use App\Http\Controllers\Docente\VerificarConstancia;
use App\Livewire\Demografia\Municipio\CreateMunicipio;

use App\Livewire\Demografia\Municipio\ListaMunicipios;
use App\Livewire\Docente\Proyectos\ProyectosAprobados;
use App\Livewire\Docente\Proyectos\ProyectosRechazados;
use App\Livewire\Docente\Proyectos\ProyectosDocenteList;

use App\Livewire\Demografia\Departamento\ListDepartamentos;
use App\Livewire\Demografia\Departamento\CreateDepartamento;
use App\Livewire\Proyectos\Vinculacion\ListInformesSolicitado;
use App\Livewire\Proyectos\Vinculacion\ListProyectosSolicitado;
use App\Livewire\Proyectos\Vinculacion\ListProyectosVinculacion;

use App\Livewire\Docente\Proyectos\SolicitudProyectosDocenteList;
use App\Livewire\Proyectos\Vinculacion\CreateProyectoVinculacion;
use App\Livewire\Proyectos\Vinculacion\EditProyectoVinculacionForm;
use App\Http\Controllers\Docente\ProyectoController as DocenteProyectoController;

use App\Livewire\Proyectos\Vinculacion\EditProyectoVinculacionForm;
use App\Livewire\Proyectos\Vinculacion\ListInformesSolicitado;
use App\Livewire\Proyectos\Vinculacion\ListProyectoRevisionFinal;

// Rutas para redireccionar a los usuario autenticados
Route::middleware(['guest'])->group(function () {
    // rutas para autenticación con Microsoft
    Route::get('auth/microsoft', [MicrosoftController::class, 'redirectToMicrosoft'])
        ->name('auth.microsoft');

    Route::get('auth/microsoft/callback', [MicrosoftController::class, 'handleMicrosoftCallback'])
        ->name('auth.microsoft.callback');

    Route::get('/', function () {
        return redirect(route('login'));
    })
        ->middleware('guest');;
    Route::get('/login', Login::class)
        ->name('login')
        ->middleware('guest');

    // Rutas para restablecimiento de contraseña olvidada
    Route::get('password/reset', ForgotPasswordController::class)
        ->name('password.request');
    Route::get('password/reset/{token}', ResetPasswordController::class)
        ->name('password.reset');

        
    });
Route::get('verificacion_constancia/{hashedProjectId}/{hashedEmployeeId}', [VerificarConstancia::class, 'index'])->name('verificacion_constancia');

// Rutas para redireccionar a los usuario  no autenticados
Route::middleware(['auth'])->group(function () {

    // rutas agrupadas para el modulo de inicio
    Route::get('inicio', InicioAdmin::class)
        ->name('inicio')
        ->middleware('permission:inicio-admin-inicio|inicio-docente-inicio');

    // rutas agrupadas para el modulo de demografia :)
    Route::middleware(['auth'])->group(function () {

        Route::get('crearPais', CreatePais::class)
            ->name('crearPais')
            ->middleware('can:demografia-admin-pais');

        Route::get('listarPais', ListPaises::class)
            ->name('listarPaises')
            ->middleware('can:demografia-admin-pais');


        Route::get('crearDepartamento', CreateDepartamento::class)
            ->name('crearDepartamento')
            ->middleware('can:demografia-admin-departamento');

        Route::get('ListarDepartamentos', ListDepartamentos::class)
            ->name('listarDepartamentos')
            ->middleware('can:demografia-admin-departamento');

        Route::get('ListarCiudades', ListaCiudad::class)
            ->name('ListarCiudades')
            ->middleware('can:demografia-admin-ciudad');

        Route::get('crearCiudad', CreateCiudad::class)
            ->name('crearCiudad')
            ->middleware('can:demografia-admin-ciudad');


        Route::get('ListarAldeas', ListAldeas::class)
            ->name('ListarAldeas')
            ->middleware('can:demografia-admin-aldea');

        Route::get('crearAldea', CreateAldea::class)
            ->name('crearAldea')
            ->middleware('can:demografia-admin-aldea');

        Route::get('ListarMunicipios', ListaMunicipios::class)
            ->name('ListarMunicipios')
            ->middleware('can:demografia-admin-municipio');

        Route::get('crearMunicipio', CreateMunicipio::class)
            ->name('crearMunicipio')
            ->middleware('can:demografia-admin-municipio');
    });


    // rutas agrupadas para el modulo de Usuarios
    Route::middleware(['auth'])->group(function () {
        Route::get('/users', Users::class)
            ->name('Usuarios')
            ->middleware('can:usuarios-admin-usuarios');

        Route::get('/roles', Roles::class)
            ->name('roles')
            ->middleware('can:usuarios-admin-rol');

        Route::get('listarPermisos', ListPermisos::class)
            ->name('listPermisos')
            ->middleware('can:usuarios-admin-permiso');


        Route::get('/logout', function () {
            Auth::logout();
            return redirect('/');
        })
            ->name('logout');
    });


    // rutas agrupadas para el modulo de Personal
    Route::middleware(['auth'])->group(function () {

        Route::get('crearEmpleado', CreateEmpleado::class)
            ->name('crearEmpleado')
            ->middleware('can:empleados-admin-empleados');

        Route::get('listarEmpleados', ListEmpleado::class)
            ->name('ListarEmpleados')
            ->middleware('can:empleados-admin-empleados');

        Route::get('mi_perfil', EditPerfil::class)
            ->name('mi_perfil')
            ->middleware('can:configuracion-admin-mi-perfil');
    });

    // rutas agrupadas para el modulo de Proyectos
    Route::middleware(['auth'])->group(function () {

        Route::get('crearProyectoVinculacion', CreateProyectoVinculacion::class)
            ->name('crearProyectoVinculacion')
            ->middleware('permission:docente-crear-proyecto|proyectos-admin-proyectos');

        // editar un proyecto ya sea en borrador o en subsanacion
        Route::get('editarProyectoVinculacion/{proyecto}', EditProyectoVinculacionForm::class)
            ->name('editarProyectoVinculacion')
            ->middleware('permission:docente-crear-proyecto|proyectos-admin-proyectos');

        Route::get('listarProyectosVinculacion', ListProyectosVinculacion::class)
            ->name('listarProyectosVinculacion')
            ->middleware('can:proyectos-admin-proyectos');

        Route::get('listarProyectosSolicitado', ListProyectosSolicitado::class)
            ->name('listarProyectosSolicitado')
            ->middleware('can:proyectos-admin-solicitados');

        Route::get('listarInformesSolicitado', ListInformesSolicitado::class)
            ->name('listarInformesSolicitado');
            //->middleware('can:proyectos-admin-solicitados');

        Route::get('listarProyectoRevisionFinal', ListProyectoRevisionFinal::class)
            ->name('listarProyectoRevisionFinal');
            //->middleware('can:proyectos-admin-revision-final');
    });


    // rutas agrupadas para el modulo de Configuración
    Route::middleware(['auth'])->group(function () {

        Route::get('listarLogs', ListLogs::class)
            ->name('listarLogs')
            ->middleware('can:configuracion-admin-logs');
    });


    // agregar rutas para el modulo de docente
    Route::middleware(['auth'])->group(function () {
        Route::get('proyectosDocente', [DocenteProyectoController::class, 'listaDeProyectos'])
            ->name('proyectosDocente')
            ->middleware('can:docente-admin-proyectos');

        Route::get('SolicitudProyectosDocente', [DocenteProyectoController::class, 'SolicitudProyectosDocente'])
            ->name('SolicitudProyectosDocente')
            ->middleware('can:docente-admin-proyectos');
        Route::get('AprobadoProyectosDocente', ProyectosAprobados::class)
            ->name('AprobadoProyectosDocente')
            ->middleware('can:docente-admin-proyectos');
        Route::get('RechazadoProyectosDocente', ProyectosRechazados::class)
            ->name('RechazadoProyectosDocente')
            ->middleware('can:docente-admin-proyectos');
    });
});
