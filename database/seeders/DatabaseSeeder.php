<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Database\Seeders\Demografia\PaisesSeeder;
use Database\Seeders\Demografia\DepartamentoSeeder; 
use Database\Seeders\UnidadAcademica\UnidadAcademicaSeeder;
use Database\Seeders\Proyecto\ProyectoSeeder;
use Database\Seeders\Proyecto\VinculacionTiposAccionSeeder;
use Database\Seeders\Personal\PersonalSeeder;
use Database\Seeders\Personal\NotificacionesPoaRolesSeeder;
use Database\Seeders\Demografia\MunicipioSeeder;
use Database\Seeders\Personal\PermisosSeeder;
use Database\Seeders\EjesPrioritariosUnahSeeder;
use Database\Seeders\Proyecto\MetasContribuyeSeeder;
use Database\Seeders\ENF\EnfCatalogoSeeder;
use Database\Seeders\ProyectoTransformacionDigitalSeeder;



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
        $this->call(VinculacionTiposAccionSeeder::class);
        $this->call(ProyectoSeeder::class);
        $this->call(PermisosSeeder::class);
        $this->call(PersonalSeeder::class);
        $this->call(NotificacionesPoaRolesSeeder::class);
        $this->call(EjesPrioritariosUnahSeeder::class);
        $this->call(CarreraDepartamentoSeeder::class);
        $this->call(MetasContribuyeSeeder::class);
        $this->call(EnfCatalogoSeeder::class);

        // Opt-in para desarrollo: NEXO_SEED_TRANSFORMACION_DIGITAL=true php artisan db:seed
        if (app()->environment(['local', 'testing'])
            && filter_var(env('NEXO_SEED_TRANSFORMACION_DIGITAL', false), FILTER_VALIDATE_BOOL)) {
            $this->call(ProyectoTransformacionDigitalSeeder::class);
        }
    }
}
