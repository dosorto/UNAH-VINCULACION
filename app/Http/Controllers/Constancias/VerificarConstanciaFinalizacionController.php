<?php

namespace App\Http\Controllers\Constancias;

use App\Http\Controllers\Controller;
use App\Models\Constancias\ConstanciaFinalizacionProyecto;

class VerificarConstanciaFinalizacionController extends Controller
{
    public function __invoke(string $token)
    {
        $constancia = ConstanciaFinalizacionProyecto::query()
            ->where('token_hash', hash('sha256', $token))
            ->firstOrFail();

        return view('constancias.verificacion-finalizacion', [
            'constancia' => $constancia,
            'vigente' => $constancia->estado === ConstanciaFinalizacionProyecto::ESTADO_EMITIDA && ! $constancia->anulada_en,
            'datos' => [
                'numero' => $constancia->numero,
                'tipo' => 'Constancia de Finalización',
                'proyecto' => data_get($constancia->snapshot, 'proyecto.nombre', 'No registrado'),
                'codigo' => data_get($constancia->snapshot, 'proyecto.codigo', 'No registrado'),
                'unidad' => data_get($constancia->snapshot, 'proyecto.unidad_academica', 'No registrado'),
                'fecha_emision' => $constancia->fecha_emision?->format('d/m/Y'),
            ],
        ]);
    }
}
