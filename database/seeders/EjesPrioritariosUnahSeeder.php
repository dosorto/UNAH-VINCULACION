<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class EjesPrioritariosUnahSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('ejes_prioritarios_unah')->insert([
            ['nombre' => 'Desarrollo económico y social'],
            ['nombre' => 'Democracia y gobernabilidad'],
            ['nombre' => 'Población y condiciones de vida'],
            ['nombre' => 'Ambiente, biodiversidad y desarrollo'],
        ]);
    }
}
