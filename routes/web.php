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
use App\Livewire\Demografia\Ciudad\ListaCiudad;
use App\Livewire\Login\Login;


Route::get('/', Login::class)
    ->name('login');


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
