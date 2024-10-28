<?php

namespace Database\Seeders\Proyecto;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Proyecto\EmpleadoProyecto;
use App\Models\Personal\Empleado;
use App\Models\Proyecto\Proyecto;
use App\Models\Estado\EstadoProyecto;

class EstadoProyectoSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        //
        EstadoProyecto::insert([
            'proyecto_id' => 1,
            'empleado_id' => 1,
            'tipo_estado_id' => 1,
            'fecha' => '2024-10-10',
            'comentario' => 'comentario seeder',
        ]);
    }
}
