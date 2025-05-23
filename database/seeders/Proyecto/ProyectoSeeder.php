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

        $proyectos = [
            [
                'nombre_proyecto' => 'Proyecto de Educación Comunitaria',
                'modalidad_id' => Modalidad::firstOrCreate(['nombre' => 'Multidisciplinar'])->id,
                'resumen' => 'Proyecto para mejorar la educación en comunidades rurales',
                'objetivo_general' => 'Mejorar los índices de educación en comunidades marginadas',
                'fecha_inicio' => '2023-01-15',
                'fecha_finalizacion' => '2023-12-15',
                'modalidad_ejecucion' => 'Bimodal',
                'fecha_registro' => now(),
            ],

            [
                'nombre_proyecto' => 'Proyecto de Salud Preventiva',
                'modalidad_id' => Modalidad::firstOrCreate(['nombre' => 'Multidisciplinar'])->id, 
                'resumen' => 'Iniciativa para promover la salud preventiva en zonas urbanas',
                'objetivo_general' => 'Reducir la incidencia de enfermedades prevenibles',
                'fecha_inicio' => '2023-03-01',
                'fecha_finalizacion' => '2023-11-30',
                'modalidad_ejecucion' => 'Presencial',
                'fecha_registro' => now(),
            ],
        ];
    
        foreach ($proyectos as $proyecto) {
            Proyecto::create($proyecto);
        }


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

        $categorias = [
            'Educación No Formal y/o Continua',
            'APS',
            'Desarrollo Regional',
            'Desarrollo local',
            'Investigación-acción-participación',
            'Asesoría técnico-científica',
            'Artísticos-culturales',
            'Otras áreas'
        ];
    
        foreach ($categorias as $categoria) {
            Categoria::firstOrCreate(['nombre' => $categoria]);
        }
        /*
            ODS en el que se enmarca el proyecto: Utilizar el documento Agenda 20/45 y objetivos de desarrollo sostenible.
        */

        $objetivos = [
            '1. Fin de la pobreza',
            '2. Hambre cero',
            '3. Salud y bienestar',
            '4. Educación de calidad',
            '5. Igualdad de género',
            '6. Agua limpia y saneamiento',
            '7. Energía asequible y no contaminante',
            '8. Trabajo decente y crecimiento económico',
            '9. Industria, innovación e infraestructura',
            '10. Reducción de las desigualdades',
            '11. Ciudades y comunidades sostenibles',
            '12. Producción y consumo responsables', // Corregida la coma
            '13. Acción por el clima',
            '14. Vida submarina',
            '15. Vida de ecosistemas terrestres',
            '16. Paz, justicia e instituciones sólidas',
            '17. Alianzas para lograr los objetivos',
        ];
    
        foreach ($objetivos as $objetivo) {
            Od::firstOrCreate(['nombre' => $objetivo]);
        }
    }
}
