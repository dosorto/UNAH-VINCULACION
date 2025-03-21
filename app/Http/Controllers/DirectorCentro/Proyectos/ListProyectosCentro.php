<?php

namespace App\Http\Controllers\DirectorCentro\Proyectos;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class ListProyectosCentro extends Controller
{
    //

    public function render()
    {
        $FacultadCentro = auth()->user()->empleado->centro_facultad;

        return view('app.directorCentro.proyectos.ver-proyectos', compact('FacultadCentro'));
    }
}
