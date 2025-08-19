<?php

namespace Database\Seeders\Proyecto;

use App\Models\Constancia\TipoConstancia;
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
            ['nombre' => 'Transdisciplinar'],
            ['nombre' => 'Interdisciplinar'],
            ['nombre' => 'Multidisciplinar'],
            ['nombre' => 'Unidisciplinar'],
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
            ['nombre' => 'APS'],
            ['nombre' => 'Desarrollo Regional'],
            ['nombre' => 'Desarrollo Local'],
            ['nombre' => 'Volunt. Académico'],
            ['nombre' => 'Seguim. a egresados'],
            ['nombre' => 'I + D + i'],
            ['nombre' => 'Cultural'],
            ['nombre' => 'Comunicación'],
        ]);

        /*
            ODS en el que se enmarca el proyecto: Utilizar el documento Agenda 20/45 y objetivos de desarrollo sostenible.
        */

        Od::insert([
            ['nombre' => '1. Fin de la pobreza'],
            ['nombre' => '2. Hambre cero'],
            ['nombre' => '3. Salud y bienestar'],
            ['nombre' => '4. Educación de calidad'],
            ['nombre' => '5. Igualdad de género'],
            ['nombre' => '6. Agua limpia y saneamiento'],
            ['nombre' => '7. Energía asequible y no contaminante'],
            ['nombre' => '8. Trabajo decente y crecimiento económico'],
            ['nombre' => '9. Industria, innovación e infraestructura'],
            ['nombre' => '10. Reducción de las desigualdades'],
            ['nombre' => '11. Ciudades y comunidades sostenibles'],
            ['nombre' => '12, Producción y consumo responsables'],
            ['nombre' => '13. Acción por el clima'],
            ['nombre' => '14. Vida submarina'],
            ['nombre' => '15. Vida de ecosistemas terrestres'],
            ['nombre' => '16. Paz, justicia e instituciones sólidas'],
            ['nombre' => '17. Alianzas para lograr los objetivos'],
        ]);

        TipoConstancia::insert([
            ['nombre' => 'Inscripcion', 'descripcion' => 'Se emite cuando un proyecto alcanza el estado en curso'],
            ['nombre' => 'Finalizacion', 'descripcion' => 'Se emite cuando un proyecto alcanza el estado Finalizado'],
            ['nombre' => 'Actualizacion', 'descripcion' => 'Se emite cuando hay cambios en el proyecto'],
            ['nombre' => 'Dictamen', 'descripcion' => 'Se emite el dictamen del proyecto'],
        ]);
    }
}