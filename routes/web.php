<?php

use Illuminate\Support\Facades\Route;
use App\Livewire\Demografia\CreatePais;
use App\Livewire\Demografia\Pais\ListPaises;
use App\Livewire\Demografia\Departamento\CreateDepartamento;
use App\Livewire\Demografia\Departamento\ListDepartamentos;
use App\Livewire\Demografia\Municipio\CreateMunicipio;
use App\Livewire\Demografia\Municipio\ListaMunicipios;
use App\Livewire\Demografia\Aldea\CreateAldea;
use App\Livewire\Demografia\Aldea\ListAldeas;
use App\Livewire\Demografia\Ciudad\CreateCiudad;
use App\Livewire\Demografia\Ciudad\ListaCiudad;


Route::get('/', function () {
    return view('welcome');
});


Route::get('crearPais', CreatePais::class)
    ->name('crear-pais');

Route::get('listarPais', ListPaises::class)
    ->name('lista-paises');


Route::get('crearDepartamento', CreateDepartamento::class)
    ->name('crearDepartamento');

Route::get('ListarDepartamentos', ListDepartamentos::class)
    ->name('ListarDepartamentos');

Route::get('crearMunicipio', CreateMunicipio::class)
    ->name('crearMunicipio');

Route::get('ListarMunicipios', ListaMunicipios::class)
    ->name('ListarMunicipios');

Route::get('crearAldea', CreateAldea::class)
    ->name('crearAldea');

Route::get('ListarAldeas', ListAldeas::class)
    ->name('ListarAldeas');

Route::get('crearCiudad', CreateCiudad::class)
    ->name('crearCiudad');

Route::get('ListarCiudades', ListaCiudad::class)
    ->name('ListarCiudades');
