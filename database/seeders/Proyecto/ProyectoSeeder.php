<?php

namespace Database\Seeders\Proyecto;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Proyecto\Proyecto;
use App\Models\Proyecto\Modalidad;
use App\Models\Proyecto\Categoria;
use App\Models\Proyecto\Od;


class ProyectoSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // crear las modalidades para el proyecto
        // Unidisciplinar Multidisciplinar Interdisciplinar Transdisciplinar ____

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
