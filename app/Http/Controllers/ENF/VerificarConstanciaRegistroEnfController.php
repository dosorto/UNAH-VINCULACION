<?php

namespace App\Http\Controllers\ENF;

use App\Http\Controllers\Controller;
use App\Models\ENF\EnfConstanciaRegistro;

class VerificarConstanciaRegistroEnfController extends Controller
{
    public function __invoke(string $token)
    {
        $constancia = EnfConstanciaRegistro::query()
            ->where('token_hash', hash('sha256', $token))
            ->vigente()
            ->firstOrFail();

        return view('constancias.verificacion-enf', [
            'constancia' => $constancia,
            'token' => $token,
            'titulo' => 'Constancia de registro ENF',
            'datos' => [
                'numero' => $constancia->numero,
                'tipo' => 'Registro ENF',
                'accion' => data_get($constancia->snapshot, 'accion.nombre'),
                'codigo' => data_get($constancia->snapshot, 'accion.codigo_formulario'),
                'unidad' => data_get($constancia->snapshot, 'accion.unidad_academica'),
                'fecha_emision' => optional($constancia->fecha_emision)->format('d/m/Y H:i'),
            ],
        ]);
    }
}
