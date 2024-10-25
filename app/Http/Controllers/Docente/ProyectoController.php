<?php

namespace App\Http\Controllers\Docente;

use App\Http\Controllers\Controller;
use App\Models\Personal\Empleado;
use Illuminate\Http\Request;

class ProyectoController extends Controller
{
    //

    // listaDeProyectos
    public function listaDeProyectos()
    {
        $docenteLogeado = auth()->user()->empleado;

        return view('app.docente.proyectos.ver-proyectos', compact('docenteLogeado'));

    }
}
