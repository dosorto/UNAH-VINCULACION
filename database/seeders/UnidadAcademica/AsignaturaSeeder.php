<?php

namespace Database\Seeders\UnidadAcademica;

use App\Models\Asignatura;
use Illuminate\Database\Seeder;

class AsignaturaSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $sample = [
            ['codigo' => 'MAT101', 'nombre' => 'Matematicas I', 'carrera_id' => null],
            ['codigo' => 'FIS101', 'nombre' => 'Fisica I', 'carrera_id' => null],
            ['codigo' => 'PROG101', 'nombre' => 'Introduccion a la Programacion', 'carrera_id' => null],
        ];

        foreach ($sample as $row) {
            Asignatura::create($row);
        }
    }
}
