<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Database\Seeders\Demografia\PaisesSeeder;
use Database\Seeders\Demografia\DepartamentoSeeder; 
use Database\Seeders\UnidadAcademica\UnidadAcademicaSeeder;
use Database\Seeders\Proyecto\ProyectoSeeder;
use Database\Seeders\Personal\PersonalSeeder;
use Database\Seeders\Demografia\MunicipioSeeder;
use Database\Seeders\Personal\PermisosSeeder;



class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {

        $this->call(PaisesSeeder::class);
        $this->call(DepartamentoSeeder::class);
        $this->call(MunicipioSeeder::class);
        $this->call(UnidadAcademicaSeeder::class);
        $this->call(ProyectoSeeder::class);
        $this->call(PermisosSeeder::class);
        $this->call(PersonalSeeder::class);
    }
}
