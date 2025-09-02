<?php

namespace Database\Seeders\Proyecto;

use Illuminate\Database\Seeder;
use App\Models\Constancia\TipoConstancia;

class ConstanciaSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $tiposConstancia = [
        ['nombre' => 'Inscripcion', 'descripcion' => 'Se emite cuando un proyecto alcanza el estado en curso'],
        ['nombre' => 'Finalizacion', 'descripcion' => 'Se emite cuando un proyecto alcanza el estado Finalizado'],
        ['nombre' => 'Actualizacion', 'descripcion' => 'Se emite cuando hay cambios en el proyecto'],
        ['nombre' => 'Dictamen', 'descripcion' => 'Se emite el dictamen del proyecto'],
    ];

    foreach ($tiposConstancia as $tipo) {
        \App\Models\Constancia\TipoConstancia::updateOrCreate(
            ['nombre' => $tipo['nombre']],
            ['descripcion' => $tipo['descripcion']]
        );
    }
    }
}