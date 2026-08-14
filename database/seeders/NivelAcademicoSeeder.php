<?php

namespace Database\Seeders;

use App\Models\NivelAcademico;
use Illuminate\Database\Seeder;

class NivelAcademicoSeeder extends Seeder
{
    public function run(): void
    {
        $niveles = [
            ['nombre' => 'Estudiante de grado', 'orden' => 1],
            ['nombre' => 'Maestría', 'orden' => 2],
            ['nombre' => 'Doctorado/Posgrado', 'orden' => 3],
        ];

        foreach ($niveles as $nivel) {
            NivelAcademico::withTrashed()->firstOrCreate(
                ['nombre' => $nivel['nombre']],
                ['activo' => true, 'orden' => $nivel['orden']]
            );
        }
    }
}
