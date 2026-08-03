<?php

namespace App\Http\Controllers\ENF;

use App\Http\Controllers\Controller;
use App\Models\ENF\EnfConstanciaFinalizacion;

class VerificarConstanciaFinalizacionEnfController extends Controller
{
    public function __invoke(string $token)
    {
        $constancia = EnfConstanciaFinalizacion::query()
            ->where('token_hash', hash('sha256', $token))
            ->vigente()
            ->firstOrFail();

        return view('constancias.verificacion-enf', [
            'constancia' => $constancia,
            'token' => $token,
            'titulo' => 'Constancia de finalizacion ENF',
            'datos' => [
                'numero' => $constancia->numero,
                'tipo' => 'Finalizacion ENF',
                'accion' => data_get($constancia->snapshot, 'accion.nombre'),
                'codigo' => data_get($constancia->snapshot, 'accion.codigo_formulario'),
                'unidad' => data_get($constancia->snapshot, 'accion.unidad_academica'),
                'fecha_emision' => optional($constancia->fecha_emision)->format('d/m/Y H:i'),
            ],
        ]);
    }
}
