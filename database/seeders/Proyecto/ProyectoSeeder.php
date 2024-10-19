<?php

namespace Database\Seeders\Proyecto;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Proyecto\Proyecto;
use App\Models\Proyecto\Modalidad;

class ProyectoSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // crear las modalidades para el proyecto
        // Unidisciplinar Multidisciplinar Interdisciplinar Transdisciplinar ____
 
        Modalidad::insert([
            ['nombre' => 'Unidisciplinar'],
            ['nombre' => 'Multidisciplinar'],
            ['nombre' => 'Interdisciplinar'],
            ['nombre' => 'Transdisciplinar'],
        ]);
    }
}
