<?php

namespace Database\Seeders\Proyecto;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Proyecto\Proyecto;
use App\Models\Proyecto\Modalidad;
use App\Models\Proyecto\Categoria;
use App\Models\Proyecto\Od;
use App\Models\Estado\EstadoProyecto;
use App\Models\Proyecto\CargoFirma;
use App\Models\Estado\TipoEstado;
use App\Models\Proyecto\TipoCargoFirma;




class ProyectoSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // crear los cargos de las firmas 
        $cargosFirmas = collect(config('nexo.cargos_firmas'));

        $cargosFirmas->each(function ($cargo) {
            TipoCargoFirma::create([
                'nombre' => $cargo,
            ]);
        });


        // crear los tipos de estado para el proyecto
        $estadosProyecto = collect(config('nexo.estados_proyecto'));

        $estadosProyecto->each(function ($estado) {
            TipoEstado::create([
                'nombre' => $estado,
            ]);
        });


        // crear los cargos de las firmas de los proyectos
        $fimasCargos = collect(config('nexo.firmas_cargos'));

        $fimasCargos->each(function ($firma) {
            $firmas = collect($firma);
            $firmas->each(function ($firma) {
                CargoFirma::create([
                    'descripcion' => $firma['descripcion'], // Cambiado a notación de arreglo
                    'tipo_cargo_firma_id' => TipoCargoFirma::where('nombre', $firma['cargo'])->first()->id,
                    'tipo_estado_id' => TipoEstado::where('nombre', $firma['estado'])->first()->id,
                    'estado_siguiente_id' => TipoEstado::where('nombre', $firma['estado_siguiente'])->first()->id,
                ]);
            });
        });


        Modalidad::insert([
            ['nombre' => 'Unidisciplinar'],
            ['nombre' => 'Multidisciplinar'],
            ['nombre' => 'Interdisciplinar'],
            ['nombre' => 'Transdisciplinar'],
        ]);

        // crear las categorias para el proyecto
        //  Categorías de proyectos de vinculación
        //Educación No Formal y/o Continua ______
        //APS
        //Desarrollo Regional
        //Desarrollo local
        //Investigación-acción-participación
        //Asesoría técnico-científica
        //Artísticos-culturales
        //Otras áreas

        Categoria::insert([
            ['nombre' => 'Educación No Formal y/o Continua'],
            ['nombre' => 'APS'],
            ['nombre' => 'Desarrollo Regional'],
            ['nombre' => 'Desarrollo local'],
            ['nombre' => 'Investigación-acción-participación'],
            ['nombre' => 'Asesoría técnico-científica'],
            ['nombre' => 'Artísticos-culturales'],
            ['nombre' => 'Otras áreas'],
        ]);

        /*
            ODS en el que se enmarca el proyecto: Utilizar el documento Agenda 20/45 y objetivos de desarrollo sostenible.
        */

        Od::insert([
            ['nombre' => 'Fin de la pobreza'],
            ['nombre' => 'Hambre cero'],
            ['nombre' => 'Salud y bienestar'],
            ['nombre' => 'Educación de calidad'],
            ['nombre' => 'Igualdad de género'],
            ['nombre' => 'Agua limpia y saneamiento'],
            ['nombre' => 'Energía asequible y no contaminante'],
            ['nombre' => 'Trabajo decente y crecimiento económico'],
            ['nombre' => 'Industria, innovación e infraestructura'],
            ['nombre' => 'Reducción de las desigualdades'],
            ['nombre' => 'Ciudades y comunidades sostenibles'],
            ['nombre' => 'Producción y consumo responsables'],
            ['nombre' => 'Acción por el clima'],
            ['nombre' => 'Vida submarina'],
            ['nombre' => 'Vida de ecosistemas terrestres'],
            ['nombre' => 'Paz, justicia e instituciones sólidas'],
            ['nombre' => 'Alianzas para lograr los objetivos'],
        ]);
    }
}
