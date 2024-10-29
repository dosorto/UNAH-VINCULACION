<?php

namespace Database\Seeders\Proyecto;

use Illuminate\Database\Seeder;
use App\Models\Proyecto\EmpleadoProyecto;
use App\Models\Personal\Empleado;
use App\Models\Proyecto\Proyecto;

class EmpleadoProyectoSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        EmpleadoProyecto::insert([
            'empleado_id' => 2,
            'proyecto_id' => 1,
            'rol' => 'Coordinador',
        ]);
    }
}

