<?php

use App\Livewire\Docente\Proyectos\HistorialProyecto;
use App\Livewire\User\Roles;
use App\Livewire\User\Users;
use App\Livewire\Login\Login;
use App\Livewire\Inicio\InicioAdmin;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;
use App\Livewire\Demografia\Pais\CreatePais;
use App\Livewire\Demografia\Pais\ListPaises;
use App\Livewire\Configuracion\Logs\ListLogs;
use App\Livewire\Configuracion\Flujos\ConfiguracionFlujosProyectos;
use App\Livewire\SGCU\Catalogos\SgcuCatalogos;
use App\Livewire\SGCU\Flujos\FlujosProgramas;
use App\Livewire\SGCU\Programas\ListBandejaRevision;
use App\Livewire\SGCU\Programas\ListProgramas;
use App\Livewire\SGCU\Programas\ListTiposPrograma;
use App\Livewire\SGCU\Programas\ProgramaForm;
use App\Livewire\Personal\Perfil\EditPerfil;
use App\Livewire\Personal\Permiso\ListPermisos;
use App\Livewire\Personal\Empleado\ListEmpleado;
use App\Livewire\Personal\Empleado\CreateEmpleado;
use App\Http\Controllers\Docente\VerificarConstancia;
use App\Http\Controllers\Auth\MicrosoftAuthController;
use App\Livewire\Demografia\Municipio\CreateMunicipio;
use App\Livewire\Demografia\Municipio\ListaMunicipios;
use App\Livewire\Docente\Proyectos\ProyectosAprobados;
use App\Livewire\Docente\Proyectos\ProyectosRechazados;
use App\Livewire\Docente\Proyectos\ProyectosDocenteList;
use App\Livewire\Proyectos\Vinculacion\AreaProyectoSelector;
use App\Livewire\Proyectos\Vinculacion\CategoriaProyectoSelector;
use App\Livewire\Docente\Proyectos\ProyectosAntesDelSistema;
use App\Livewire\Docente\Proyectos\EditProyectoAntesDelSistema;
use App\Livewire\Estudiante\CreateEstudiante;
use App\Livewire\Estudiante\ListarEstudiante;
use App\Livewire\Ticket\HistorialTicket;
use App\Livewire\Ticket\ListarTicket;

use App\Livewire\Demografia\Departamento\ListDepartamentos;
use App\Livewire\Demografia\Departamento\CreateDepartamento;
use App\Livewire\Proyectos\Vinculacion\ListInformesSolicitado;
use App\Livewire\Proyectos\Vinculacion\ListProyectosSolicitado;
use App\Livewire\Proyectos\Vinculacion\ListProyectosVinculacion;

use App\Livewire\Proyectos\Vinculacion\CreateProyectoVinculacion;
use App\Livewire\Proyectos\Vinculacion\CreatePpsServicioSocial;
use App\Livewire\Proyectos\Vinculacion\EditPpsServicioSocial;
use App\Livewire\Proyectos\Vinculacion\ShowPpsServicioSocial;
use App\Livewire\Proyectos\Actualizacion\EditProyectoActualizacion;
use App\Livewire\Proyectos\Vinculacion\EditProyectoVinculacionForm;

use App\Livewire\Proyectos\Vinculacion\ListProyectoRevisionFinal;
use App\Livewire\Proyectos\Vinculacion\ListFichasActualizacionVinculacion;
use App\Livewire\Slide\SlideConfig;

use App\Http\Controllers\SetRoleController;
use App\Livewire\UnidadAcademica\Campus\CampusList;
use App\Livewire\UnidadAcademica\Carrera\CarreraList;
use App\Livewire\UnidadAcademica\DepartamentoAcademico\DepartamentoAcademicoList;
use App\Livewire\UnidadAcademica\FacultadCentro\FacultadCentroList;
use App\Livewire\UnidadAcademica\Asignatura\Asignatura;
use App\Http\Controllers\PDFController;
use App\Http\Controllers\ENF\EnfAccionController;
use App\Http\Controllers\ENF\EnfPresupuestoController;
use App\Http\Controllers\ENF\EnfCronogramaController;
use App\Http\Controllers\ENF\EnfInformeFinalController;
use App\Http\Controllers\ENF\EnfSistematizacionController;
use App\Http\Controllers\ENF\EnfDocumentoController;
use App\Http\Controllers\Proyectos\Vinculacion\PpsServicioSocialAnexoController;
use App\Http\Controllers\Proyectos\Vinculacion\PpsServicioSocialPdfController;

use App\Livewire\DirectorFacultadCentro\Proyectos\ListProyectos;
use App\Livewire\Constancia\ListConstancias;
use App\Livewire\Docente\Proyectos\ProyectosPorFirmar;
use App\Livewire\Docente\Proyectos\FichasActualizacionPorFirmar;
use App\Livewire\Docente\Proyectos\FichasActualizacionDocente;
use App\Models\Slide\Slide;
use App\Livewire\Personal\Contacto\ListContactos;

use App\Livewire\ServicioTecnologico\CreateServicioTecnologico;
use App\Livewire\ServicioTecnologico\ListServiciosTecnologicos;
use App\Models\Proyecto\InstrumenFormalizacion;
use Illuminate\Support\Facades\Storage;

Route::get('/acercade', function () {
    $slides = Slide::where('estado', true)
                    ->get();

        $data = ['slides' => $slides];

    return view('aplicacion.home', $data);
})->name('home');

Route::get('verificacion_constancia', [VerificarConstancia::class, 'verificacionConstanciaVista'])
    ->name('validar');

//...


Route::get('verificacion_constancia/{hash?}', [VerificarConstancia::class, 'index'])
    ->name('verificacion_constancia');

Route::get('/constancia/{constancia:hash}/pdf', [PDFController::class, 'generatePDF'])
    ->name('constancia.pdf');

Route::get('/logout', function () {
    if (Auth::check()) {
        Auth::logout();
        request()->session()->invalidate();
        request()->session()->regenerateToken();
    }

    return redirect()->route('login');
})->name('logout');

// Rutas para redireccionar a los usuario autenticados
Route::middleware(['guest'])->group(function () {
    Route::get('/', Login::class)
        ->name('login');

    Route::get('auth/microsoft/redirect', [MicrosoftAuthController::class, 'redirect'])
        ->name('login.microsoft.redirect');

    Route::get('auth/microsoft/callback', [MicrosoftAuthController::class, 'callback'])
        ->name('login.microsoft.callback');

    Route::get('password/reset', \App\Livewire\Auth\ForgotPasswordController::class)
        ->name('password.request');

    Route::get('password/reset/{token}', \App\Livewire\Auth\ResetPasswordController::class)
        ->name('password.reset');
});

Route::middleware(['auth'])->prefix('enf')->name('enf.')->group(function () {
    Route::get('tipos', [EnfAccionController::class, 'tipos'])->name('tipos');
    Route::get('acciones/{accion}/pdf', [EnfAccionController::class, 'descargarPdf'])->name('acciones.pdf');
    Route::post('acciones/{accion}/reenviar-revision', [EnfAccionController::class, 'reenviarRevision'])->name('acciones.reenviar-revision');
    Route::post('acciones/{accion}/revisiones/{revision}/aprobar', [EnfAccionController::class, 'aprobarRevision'])->name('acciones.revisiones.aprobar');
    Route::post('acciones/{accion}/revisiones/{revision}/subsanar', [EnfAccionController::class, 'subsanarRevision'])->name('acciones.revisiones.subsanar');
    Route::resource('acciones', EnfAccionController::class)
        ->parameters(['acciones' => 'accion']);
    Route::resource('presupuestos', EnfPresupuestoController::class)
        ->parameters(['presupuestos' => 'presupuesto']);
    Route::resource('cronograma', EnfCronogramaController::class)
        ->parameters(['cronograma' => 'cronograma']);
    Route::resource('informes-finales', EnfInformeFinalController::class)
        ->parameters(['informes-finales' => 'informeFinal']);
    Route::resource('sistematizaciones', EnfSistematizacionController::class)
        ->parameters(['sistematizaciones' => 'sistematizacion']);
    Route::resource('documentos', EnfDocumentoController::class)
        ->parameters(['documentos' => 'documento']);
});

// Rutas para redireccionar a los usuario  no autenticados
Route::middleware(['auth', \App\Http\Middleware\VerificarPermisoDeCompletarPerfil::class])->group(function () {



    Route::get('campus', CampusList::class)
        ->name('campus')
        ->middleware('can:unidad-academica.campus');

    Route::get('carrera', CarreraList::class)
        ->name('carrera')
        ->middleware('can:unidad-academica.carrera');

    Route::get('asignatura', Asignatura::class)
        ->name('asignatura')
        ->middleware('can:unidad-academica.asignatura');

    Route::get('departamento-academico', DepartamentoAcademicoList::class)
        ->name('departamento-academico')
        ->middleware('can:unidad-academica.departamento');

    Route::get('facultad-centro', FacultadCentroList::class)
        ->name('facultad-centro')
        ->middleware('can:unidad-academica.facultad');


    Route::get('setPerfil/{role_id}', [SetRoleController::class, 'SetRole'])
        ->name('setrole');
    // ->middleware('can:global.set-role');


    // rutas agrupadas para el modulo de inicio
    Route::get('inicio', InicioAdmin::class)
        ->name('inicio');
    // ->middleware('permission:inicio.admin|inicio.docente|perfil.editar|inicio.estudiante|perfil.editar');
    // rutas agrupadas para el modulo de demografia :)
    Route::middleware(['auth'])->group(function () {


        Route::get('crearPais', CreatePais::class)
            ->name('crearPais')
            ->middleware('can:demografia.pais');

        Route::get('listarPais', ListPaises::class)
            ->name('listarPaises')
            ->middleware('can:demografia.pais');


        Route::get('crearDepartamento', CreateDepartamento::class)
            ->name('crearDepartamento')
            ->middleware('can:demografia.departamento');

        Route::get('ListarDepartamentos', ListDepartamentos::class)
            ->name('listarDepartamentos')
            ->middleware('can:demografia.departamento');

        // Route::get('ListarCiudades', ListaCiudad::class)
        //    ->name('ListarCiudades')
        //    ->middleware('can:demografia.ciudad');

        //  Route::get('crearCiudad', CreateCiudad::class)
        //     ->name('crearCiudad')
        //    ->middleware('can:demografia.ciudad');


        //Route::get('ListarAldeas', ListAldeas::class)
        //   ->name('ListarAldeas')
        //   ->middleware('can:demografia.aldea');

        //    Route::get('crearAldea', CreateAldea::class)
        //      ->name('crearAldea')
        //    ->middleware('can:demografia.aldea');

        Route::get('ListarMunicipios', ListaMunicipios::class)
            ->name('ListarMunicipios')
            ->middleware('can:demografia.municipio');

        Route::get('crearMunicipio', CreateMunicipio::class)
            ->name('crearMunicipio')
            ->middleware('can:demografia.municipio');



    });


    // rutas agrupadas para el modulo de Usuarios
    Route::middleware(['auth'])->group(function () {
        Route::get('/users', Users::class)
            ->name('Usuarios')
            ->middleware('can:usuarios.usuarios');

        Route::get('/roles', Roles::class)
            ->name('roles')
            ->middleware('can:usuarios.roles');

        Route::get('listarPermisos', ListPermisos::class)
            ->name('listPermisos')
            ->middleware('can:usuarios.permisos');

        Route::get('slides', SlideConfig::class)
            ->name('slides')
            ->middleware('can:apariencia.slides');

        Route::get('configuracion/flujos-proyectos', ConfiguracionFlujosProyectos::class)
            ->name('configuracion.flujos.proyectos')
            ->middleware('can:configuracion.flujos');

        Route::prefix('sgcu')->middleware('can:configuracion.flujos')->group(function () {
            Route::get('catalogos', SgcuCatalogos::class)
                ->name('sgcu.catalogos');
            Route::get('tipos-programa', ListTiposPrograma::class)
                ->name('sgcu.tipos-programa');
            Route::get('programas', ListProgramas::class)
                ->name('sgcu.programas');
            Route::get('programas/crear', ProgramaForm::class)
                ->name('sgcu.programas.create');
            Route::get('programas/{programa}/editar', ProgramaForm::class)
                ->name('sgcu.programas.edit');
            Route::get('bandeja-revision', ListBandejaRevision::class)
                ->name('sgcu.bandeja-revision');
            Route::get('flujos-programa', FlujosProgramas::class)
                ->name('sgcu.flujos-programa');
        });
    });


    // rutas agrupadas para el modulo de Personal
    Route::middleware(['auth'])->group(function () {

        Route::get('crearEmpleado', CreateEmpleado::class)
            ->name('crearEmpleado')
            ->middleware('can:empleados.empleados');

        Route::get('listarEmpleados', ListEmpleado::class)
            ->name('ListarEmpleados')
            ->middleware('can:empleados.empleados');

        Route::get('codigos-investigacion-admin', \App\Livewire\Personal\CodigosInvestigacionAdmin::class)
            ->name('codigosInvestigacionAdmin')
            ->middleware('can:empleados.empleados');

        Route::get('mi_perfil', EditPerfil::class)
            ->name('mi_perfil')
            ->middleware('can:configuracion.perfil');
        Route::get('mi_perfil_estudiante', EditPerfil::class)
            ->name('mi_perfil_estudiante')
            ->middleware('can:perfil.editar');
    });

    // rutas agrupadas para el modulo de Proyectos
    Route::middleware(['auth'])->group(function () {

        Route::get('/crearProyectoVinculacion/{record?}', CreateProyectoVinculacion::class)
            ->name('crearProyectoVinculacion')
            ->middleware('permission:docente.crear-proyecto');

        Route::get('/crearPpsServicioSocial', CreatePpsServicioSocial::class)
            ->name('crearPpsServicioSocial')
            ->middleware('permission:docente.crear-proyecto');

        Route::get('/pps-servicio-social', function () {
            $activeRole = auth()->user()?->activeRole;

            if ($activeRole?->hasPermissionTo('docente.proyectos')) {
                return redirect()->route('proyectosDocente');
            }

            if ($activeRole?->hasPermissionTo('director.proyectos')) {
                return redirect()->route('proyectosCentroFacultad');
            }

            if ($activeRole?->hasPermissionTo('proyectos.historial')) {
                return redirect()->route('listarProyectosVinculacion');
            }

            return redirect()->route('inicio');
        })
            ->name('pps-servicio-social.index')
            ->middleware('permission:docente.crear-proyecto|docente.proyectos|director.proyectos|proyectos.historial|proyectos.revision-final');

        Route::get('/pps-servicio-social/{id}/editar', EditPpsServicioSocial::class)
            ->name('pps-servicio-social.edit')
            ->middleware('permission:docente.crear-proyecto|docente.proyectos');

        Route::get('/pps-servicio-social/{id}/pdf', PpsServicioSocialPdfController::class)
            ->name('pps-servicio-social.pdf')
            ->middleware('permission:docente.crear-proyecto|docente.proyectos|director.proyectos|proyectos.historial|proyectos.revision-final');

        Route::get('/pps-servicio-social/{id}/anexo/{tipo}', PpsServicioSocialAnexoController::class)
            ->name('pps-servicio-social.anexo')
            ->middleware('permission:docente.crear-proyecto|docente.proyectos|director.proyectos|proyectos.historial|proyectos.revision-final');

        Route::get('/pps-servicio-social/{id}', ShowPpsServicioSocial::class)
            ->name('pps-servicio-social.show')
            ->middleware('permission:docente.crear-proyecto|docente.proyectos|director.proyectos|proyectos.historial|proyectos.revision-final');

        Route::get('/instrumentos-formalizacion/{instrumento}/documento', function (InstrumenFormalizacion $instrumento) {
            $proyecto = $instrumento->entidadContraparte?->proyecto;
            abort_unless($proyecto && $proyecto->coordinadorIsCurrentUser(), 403);

            $path = $instrumento->documento_url;
            abort_unless($path, 404);

            $path = ltrim($path, '/');
            $path = preg_replace('#^storage/#', '', $path);
            $path = preg_replace('#^public/#', '', $path);
            $path = preg_replace('#^app/public/#', '', $path);

            abort_unless(Storage::disk('public')->exists($path), 404);

            $displayName = $instrumento->nombre_archivo ?? basename($path);

            return Storage::disk('public')->response($path, $displayName);
        })->name('instrumentos-formalizacion.documento');

        // editar un proyecto ya sea en borrador o en subsanacion
        Route::get('editarProyectoVinculacion/{proyecto}', EditProyectoVinculacionForm::class)
            ->name('editarProyectoVinculacion')
            ->middleware('permission:docente.crear-proyecto');

        Route::get('listarProyectosVinculacion', ListProyectosVinculacion::class)
            ->name('listarProyectosVinculacion')
            ->middleware('permission:proyectos.historial');

        Route::get('proyectos-vinculacion', ListProyectos::class)
            ->name('proyectosCentroFacultad')
            ->middleware('permission:director.proyectos|proyectos.historial');

        Route::get('listarProyectosSolicitado', ListProyectosSolicitado::class)
            ->name('listarProyectosSolicitado')
            ->middleware('can:proyectos.solicitados');

        Route::get('listarInformesSolicitado', ListInformesSolicitado::class)
            ->name('listarInformesSolicitado')
            ->middleware('can:proyectos.informes');

        Route::get('listarProyectoRevisionFinal', ListProyectoRevisionFinal::class)
            ->name('listarProyectoRevisionFinal')
            ->middleware('can:proyectos.revision-final');

        Route::get('fichasActualizacionVinculacion', ListFichasActualizacionVinculacion::class)
            ->name('fichasActualizacionVinculacion')
            ->middleware('can:proyectos.revision-final');
    });


    // rutas agrupadas para el modulo de Configuración
    Route::middleware(['auth'])->group(function () {

        Route::get('listarLogs', ListLogs::class)
            ->name('listarLogs')
            ->middleware('can:configuracion.logs');
    });

    // rutas para el modludo de constancias


    Route::middleware(['auth'])->group(function () {

        Route::get('listConstancias', ListConstancias::class)
            ->name('constancias')
            ->middleware('can:constancia.constancias');

        
        Route::get('listContactanos', ListContactos::class) 
            ->name('contactanos')
            ->middleware('can:configuracion.contactanos');
    });

    // agregar rutas para el modulo de docente
    Route::middleware(['auth'])->group(function () {
        Route::get('proyectosDocente',  ProyectosDocenteList::class)
            ->name('proyectosDocente')
            ->middleware('can:docente.proyectos');

        Route::get('selectorTipoAccion',  AreaProyectoSelector::class)
            ->name('selectorTipoAccion')
            ->middleware('can:docente.proyectos');

        Route::get('selectorCategoria',  CategoriaProyectoSelector::class)
            ->name('selectorCategoria')
            ->middleware('can:docente.proyectos');

        Route::get('historialproyecto/{proyecto}', HistorialProyecto::class)
            ->name('historialproyecto')
            ->middleware('can:docente.proyectos');

        Route::get('/proyectos/{proyecto}/ficha-actualizacion', EditProyectoActualizacion::class)
            ->name('ficha-actualizacion')
            ->middleware('can:docente.proyectos');

        Route::get('SolicitudProyectosDocente', ProyectosPorFirmar::class)
            ->name('SolicitudProyectosDocente')
            ->middleware('can:docente.proyectos');

        Route::get('FichasActualizacionPorFirmar', FichasActualizacionPorFirmar::class)
            ->name('FichasActualizacionPorFirmar')
            ->middleware('can:docente.proyectos');

        Route::get('FichasActualizacionDocente', FichasActualizacionDocente::class)
            ->name('FichasActualizacionDocente')
            ->middleware('can:docente.proyectos');

        Route::get('AprobadoProyectosDocente', ProyectosAprobados::class)
            ->name('AprobadoProyectosDocente')
            ->middleware('can:docente.proyectos');

        Route::get('PendientesProyectosDocente', ProyectosRechazados::class)
            ->name('RechazadoProyectosDocente')
            ->middleware('can:docente.proyectos');

        // Rutas para proyectos creados desde códigos de investigación
        Route::get('proyectosAntesDelSistema', ProyectosAntesDelSistema::class)
            ->name('proyectosAntesDelSistema')
            ->middleware('can:docente.proyectos');

        Route::get('editarProyectoAntesDelSistema/{proyecto}', EditProyectoAntesDelSistema::class)
            ->name('editarProyectoAntesDelSistema')
            ->middleware('can:docente.proyectos');
    });

    // rutas para el modulo de Estudiante
    Route::middleware(['auth'])->group(function () {

        Route::get('listarEstudiante', ListarEstudiante::class)
            ->name('listarEstudiante')
            ->middleware('can:estudiante.admin');

        Route::get('crearEstudiante', CreateEstudiante::class)
            ->name('crearEstudiante')
            ->middleware('can:estudiante.admin');
    });

    // rutas para el modulo de Tickets

        Route::middleware(['auth'])->group(function () {

            Route::get('listarTicket', ListarTicket::class)
                ->name('listarTicket')
                ->middleware('can:tickets.ver');

            Route::get('historialTicket', HistorialTicket::class)
                ->name('historialTicket')
                ->middleware('can:tickets.ver');

        });

        // Servicios Tecnologicos
        Route::middleware(['auth'])->group(function () {

            Route::get('createServicioTecnologico', CreateServicioTecnologico::class)
                ->name('createServicioTecnologico');

            Route::get('/servicios-tecnologicos', ListServiciosTecnologicos::class)
                ->name('listServiciosTecnologicos');

        });
        

        Route::get('/descargar-pdf', [PDFController::class, 'generateGenericPDF']);
        
        Route::get('/ver-pdf', [PDFController::class, 'verVista']);

        Route::get('/proyectos/{proyecto}/perfil-pdf', [PDFController::class, 'previsualizarPerfilProyecto'])
            ->name('proyecto.perfil.pdf');

        Route::get('/proyectos/{proyecto}/perfil-pdf/descargar', [PDFController::class, 'descargarPerfilProyecto'])
            ->name('proyecto.perfil.pdf.download');

    Route::delete('/eliminar-constancia/{path}', function ($path) {
        $filePath = storage_path('app/public/' . $path);
        if (file_exists($filePath)) {
            unlink($filePath);
        }
        return response()->json(['message' => 'Archivo eliminado']);
    })->name('eliminar.constancia');
    
});
