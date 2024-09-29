<?php

use Illuminate\Support\Facades\Route;
use App\Livewire\Demografia\Pais\CreatePais;
use App\Livewire\Demografia\Pais\ListPaises;
use App\Livewire\Demografia\Departamento\CreateDepartamento;
use App\Livewire\Demografia\Departamento\ListDepartamentos;


Route::get('/', function () {
    return view('welcome');
});


Route::get('crearPais', CreatePais::class)
    ->name('crearPais');

Route::get('listarPais', ListPaises::class)
    ->name('listarPaises');


Route::get('crearDepartamento', CreateDepartamento::class)
    ->name('crearDepartamento');

Route::get('ListarDepartamentos', ListDepartamentos::class)
    ->name('listarDepartamentos');
