<?php

namespace Database\Seeders\Personal;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Personal\Empleado;
use App\Models\Estudiante\Estudiante;

use App\Models\Personal\CategoriaEmpleado;


class PersonalSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // crear los seeders de las categorias de empleados
        CategoriaEmpleado::create([
            'nombre' => 'Docente',
            'descripcion' => 'Docente de la universidad'
        ]);

        CategoriaEmpleado::create([
            'nombre' => 'Administrativo',
            'descripcion' => 'Administrativo de la universidad'
        ]);

        CategoriaEmpleado::create([
            'nombre' => 'Obrero',
            'descripcion' => 'Obrero de la universidad'
        ]);

        CategoriaEmpleado::create([
            'nombre' => 'Otro',
            'descripcion' => 'Otro tipo de empleado'
        ]);

        // Datos de empleados
        $userDorian = User::create([
            'name' => 'Dorian',
            'email' => 'dorianadolfo@unah.edu.hn',
            'password' => bcrypt('123'), // Asegurarse de encriptar la contraseña
        ]);

        $userVictor = User::create([
            'name' => 'Victor',
            'email' => 'victor@unah.edu.hn',
            'password' => bcrypt('123'), // Asegurarse de encriptar la contraseña
        ]);

        $userWilson = User::create([
            'name' => 'wilson',
            'email' => 'wilson@unah.edu.hn',
            'password' => bcrypt('123'), // Asegurarse de encriptar la contraseña
        ]);

        $userOscar = User::create([
            'name' => 'Oscar',
            'email' => 'oscar@unah.edu.hn',
            'password' => bcrypt('123'), // Asegurarse de encriptar la contraseña
        ]);

        $userJessica = User::create([
            'name' => 'Jessica',
            'email' => 'jessica@unah.edu.hn',
            'password' => bcrypt('123'),
        ]);

        Empleado::create([
            'nombre_completo' => 'JESSICA NOHELY AVILA CRUZ',
            'numero_empleado' => '12310',
            'celular' => '99999999',
            'user_id' => $userJessica->id,
            'centro_facultad_id' => 1,
            'departamento_academico_id' => 1,
            'categoria_id' => 1
        ]);

        Empleado::create([
            'nombre_completo' => 'OSCAR OMAR PINEDA',
            'numero_empleado' => '12311',
            'celular' => '99999999',
            'user_id' => $userOscar->id,
            'centro_facultad_id' => 1,
            'departamento_academico_id' => 1,
            'categoria_id' => 1
        ]);

        Empleado::create([
            'nombre_completo' => 'WILSON OCTAVIO VILLANUEVA CASTILLO',
            'numero_empleado' => '12316',
            'celular' => '99999999',
            'user_id' => $userWilson->id,
            'centro_facultad_id' => 1,
            'departamento_academico_id' => 1,
            'categoria_id' => 1
        ]);

        Empleado::create([
            'nombre_completo' => 'VICTOR NAHUN REYES NAVAS',
            'numero_empleado' => '12317',
            'celular' => '99999999',
            'user_id' => $userVictor->id,
            'centro_facultad_id' => 1,
            'departamento_academico_id' => 1,
            'categoria_id' => 1
        ]);





        Empleado::create([
            'nombre_completo' => 'DORIAN ADOLFO ORDONEZ OSORTO',
            'numero_empleado' => '12312',
            'celular' => '99999999',
            'user_id' => $userDorian->id,
            'centro_facultad_id' => 1,
            'departamento_academico_id' => 1,
            'categoria_id' => 1
        ]);

        

           // Datos de estudiantes
           $estudiantes = [
            [
                'nombre' => 'Cristhian Enrique',
                'apellido' => 'Avila Sauceda',
                'cuenta' => 'Grupo 1',
                'email' => 'cristian@unah.hn',
            ],
            [
                'nombre' => 'Ingrid Geraldina',
                'apellido' => 'Baquedano Lozano',
                'cuenta' => 'Grupo 1',
                'email' => 'ingrid@unah.hn',
            ],
            [
                'nombre' => 'Fernanda Sarahi',
                'apellido' => 'Betancourth Barahona',
                'cuenta' => 'Grupo 1',
                'email' => 'fernanda@unah.hn',
            ],
            [
                'nombre' => 'Mario Fernando',
                'apellido' => 'Carbajal Galo',
                'cuenta' => 'Grupo 1',
                'email' => 'mario@unah.hn',
            ],
            [
                'nombre' => 'Dacia Lisbeth',
                'apellido' => 'Espinoza Portillo',
                'cuenta' => 'Grupo 1',
                'email' => 'dacia@unah.hn',
            ],
            [
                'nombre' => 'Josue Daniel',
                'apellido' => 'Henriquez Corrales',
                'cuenta' => 'Grupo 2',
                'email' => 'josue@unah.hn',
            ],
            [
                'nombre' => 'Ernesto Noe',
                'apellido' => 'Moncada Valladares',
                'cuenta' => 'Grupo 2',
                'email' => 'ernesto@unah.hn',
            ],
            [
                'nombre' => 'Kenis Noe',
                'apellido' => 'Osorto Reyes',
                'cuenta' => 'Grupo 2',
                'email' => 'kenis@unah.hn',
            ],
            [
                'nombre' => 'Yahir Eduardo',
                'apellido' => 'Oyuela Diaz',
                'cuenta' => 'Grupo 2',
                'email' => 'yahir@unah.hn',
            ],
            [
                'nombre' => 'Francisco Josafat',
                'apellido' => 'Paz Flores',
                'cuenta' => 'Grupo 2',
                'email' => 'fjpazf@unah.hn',
            ],
        ];

        foreach ($estudiantes as $estudianteData) {
            // Crear el usuario
            $user = User::create([
                'name' => $estudianteData['nombre'],
                'email' => $estudianteData['email'],
                'password' => bcrypt('123'), // Asegúrate de encriptar la contraseña
            ]);

            // Crear el estudiante asociado
            Estudiante::create([
                'user_id' => $user->id,
                'nombre' => $estudianteData['nombre'],
                'apellido' => $estudianteData['apellido'],
                'cuenta' => $estudianteData['cuenta'],
            ]);
        }




    }
}
