<?php

namespace Database\Seeders;

use App\Models\Proyecto\EjesPrioritariosUnah;
use Illuminate\Database\Seeder;

class EjesPrioritariosUnahSeeder extends Seeder
{
    public function run(): void
    {
        collect([
            'Desarrollo económico y social',
            'Democracia y gobernabilidad',
            'Población y condiciones de vida',
            'Ambiente, biodiversidad y desarrollo',
        ])->each(fn (string $nombre) => EjesPrioritariosUnah::firstOrCreate(['nombre' => $nombre]));
    }
}
