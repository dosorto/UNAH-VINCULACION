<?php

namespace Database\Seeders\UnidadAcademica;

use Illuminate\Database\Seeder;
use App\Models\Asignatura;

class AsignaturaSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $sample = [
            ['codigo' => 'MAT101', 'nombre' => 'Matemáticas I', 'descripcion' => 'Fundamentos de matemáticas básicas', 'carrera_id' => null],
            ['codigo' => 'FIS101', 'nombre' => 'Física I', 'descripcion' => 'Introducción a la física', 'carrera_id' => null],
            ['codigo' => 'PROG101', 'nombre' => 'Introducción a la Programación', 'descripcion' => 'Conceptos básicos de programación', 'carrera_id' => null],
        ];

        foreach ($sample as $row) {
            Asignatura::create($row);
        }
    }
}
