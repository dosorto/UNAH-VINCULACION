<?php
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\EmpleadoProyectosController;
Route::get('/empleados/{identificador}/proyectos', [EmpleadoProyectosController::class, '__invoke'])
    ->middleware('api.employee.token:empleados.proyectos');
