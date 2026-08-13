<?php

namespace Database\Seeders\UnidadAcademica;

use App\Models\PeriodoAcademico;
use Illuminate\Database\Seeder;

class PeriodoAcademicoSeeder extends Seeder
{
    public function run(): void
    {
        foreach (PeriodoAcademico::NOMBRES_BASE as $nombre) {
            PeriodoAcademico::query()->firstOrCreate(['nombre' => $nombre]);
        }
    }
}
