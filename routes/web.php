<?php

use Illuminate\Support\Facades\Route;
use App\Livewire\Demografia\CreatePais;
use App\Livewire\Demografia\Pais\ListPaises;
use App\Livewire\Demografia\Departamento\CreateDepartamento;
use App\Livewire\Demografia\Departamento\ListDepartamentos;
use App\Livewire\Login\Login;
use App\Livewire\User\Users;
use App\Livewire\User\Roles;


Route::get('/', Login::class)
->name('login');


Route::middleware(['auth'])->group(function () {
    
    Route::get('crearPais', CreatePais::class)
        ->name('crear-pais');
    
    Route::get('listarPais', ListPaises::class)
        ->name('lista-paises');
    
    Route::get('crearDepartamento', CreateDepartamento::class)
        ->name('crearDepartamento');
    
    Route::get('ListarDepartamentos', ListDepartamentos::class)
        ->name('ListarDepartamentos');

    Route::get('/users', Users::class)
        ->name('Usuarios');

    Route::get('/roles', Roles::class)
        ->name('roles');

    Route::get('/logout', function () {
        Auth::logout(); 
        return redirect('/'); 
    })
        ->name('logout');

});
