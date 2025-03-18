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
            'nombre' => 'Auxiliar',
            'descripcion' => 'Auxiliar de la universidad'
        ]);

        CategoriaEmpleado::create([
            'nombre' => 'Titular I',
            'descripcion' => 'Titular 1 de la universidad'
        ]);

        CategoriaEmpleado::create([
            'nombre' => 'Titular II',
            'descripcion' => 'Titular 2 de la universidad'
        ]);

        CategoriaEmpleado::create([
            'nombre' => 'Titular III',
            'descripcion' => 'Titular 3 de la universidad'
        ]);

        CategoriaEmpleado::create([
            'nombre' => 'Titular IV',
            'descripcion' => 'Titular 4 de la universidad'
        ]);

        CategoriaEmpleado::create([
            'nombre' => 'Titular V',
            'descripcion' => 'Titular 5 de la universidad'
        ]);

        


        // user para el administrador de centro / facultad

        $userAdminCentroFacultad = User::create([
            'name' => 'Admin Centro Facultad',
            'email' => 'admincentro@unah.edu.hn',
            'password' => bcrypt('123'), // Asegurarse de encriptar la contraseña
            'active_role_id' => 2
        ])->assignRole('admin_centro_facultad');

        // user validador de proyectos en CU    
        $userValidadorProyectosCU = User::create([
            'name' => 'Validador Proyectos',
            'email' => 'validador@unah.hn',
            'password' => bcrypt('123'), // Asegurarse de encriptar la contraseña
            'active_role_id' => 2
        ])->assignRole('Validador');



       

        $userVictor = User::create([
            'name' => 'Victor',
            'email' => 'victor@unah.edu.hn',
            'password' => bcrypt('123'), // Asegurarse de encriptar la contraseña
            'active_role_id' => 2
        ])->assignRole('docente');

        $userWilson = User::create([
            'name' => 'wilson',
            'email' => 'wilson@unah.edu.hn',
            'password' => bcrypt('123'), // Asegurarse de encriptar la contraseña
            'active_role_id' => 2
        ])->assignRole('docente');

        $userOscar = User::create([
            'name' => 'Oscar',
            'email' => 'oscar@unah.edu.hn',
            'password' => bcrypt('123'), // Asegurarse de encriptar la contraseña
            'active_role_id' => 2
        ])->assignRole('docente');

        $userJessica = User::create([
            'name' => 'Jessica',
            'email' => 'jessica@unah.edu.hn',
            'password' => bcrypt('123'),
        ])->assignRole('docente');

        $user = User::create([
            'name' => 'neto',
            'email' => 'notificacionespoa@unah.edu.hn',
            'password' => bcrypt('123'), // Asegurarse de encriptar la contraseña
            'active_role_id' => 1
        ])->assignRole(['admin', 'docente']);
        
       Empleado::create([
        'nombre_completo' => 'Administrador',
        'numero_empleado' => '123412125',
        'celular' => '99999999',
        'user_id' => $user->id,
        'centro_facultad_id' => 1,
        'departamento_academico_id' => 1,
        'categoria_id' => 1
       ]);

        Empleado::create([
            'nombre_completo' => 'validador proyectos',
            'numero_empleado' => '12345',
            'celular' => '99999999',
            'user_id' => $userValidadorProyectosCU->id,
            'centro_facultad_id' => 1,
            'departamento_academico_id' => 1,
            'categoria_id' => 1
        ]);



        $ingeJessica = Empleado::create([
            'nombre_completo' => 'JESSICA NOHELY AVILA CRUZ',
            'numero_empleado' => '12310',
            'celular' => '99999999',
            'user_id' => $userJessica->id,
            'centro_facultad_id' => 1,
            'departamento_academico_id' => 1,
            'categoria_id' => 1
        ]);
        
       

       $ingeOscar = Empleado::create([
            'nombre_completo' => 'OSCAR OMAR PINEDA',
            'numero_empleado' => '12311',
            'celular' => '99999999',
            'user_id' => $userOscar->id,
            'centro_facultad_id' => 1,
            'departamento_academico_id' => 1,
            'categoria_id' => 1
       ]);
       
       $ingeOscar->firma()->create([
            'tipo' => 'firma',
            'ruta_storage' => 'images/firmas/Firma_Oscar.png',
            'estado' => true
        ]);

        $ingeOscar->firma()->create([
            'tipo' => 'sello',
            'ruta_storage' => 'images/firmas/Sello_Victor.png',
            'estado' => true
        ]);

       $ingeWilson = Empleado::create([
            'nombre_completo' => 'WILSON OCTAVIO VILLANUEVA CASTILLO',
            'numero_empleado' => '12316',
            'celular' => '99999999',
            'user_id' => $userWilson->id,
            'centro_facultad_id' => 1,
            'departamento_academico_id' => 1,
            'categoria_id' => 1
       ]); 
       
       $ingeWilson->firma()->create([
            'tipo' => 'firma',
            'ruta_storage' => 'images/firmas/Firma_Wilson.png',
            'estado' => true
        ]);

        $ingeWilson->firma()->create([
            'tipo' => 'sello',
            'ruta_storage' => 'images/firmas/Sello_Wilson.png',
            'estado' => true
        ]);

       $licVictor = Empleado::create([
            'nombre_completo' => 'VICTOR NAHUN REYES NAVAS',
            'numero_empleado' => '12317',
            'celular' => '99999999',
            'user_id' => $userVictor->id,
            'centro_facultad_id' => 1,
            'departamento_academico_id' => 1,
            'categoria_id' => 1
       ]);

       $licVictor->firma()->create([
            'tipo' => 'firma',
            'ruta_storage' => 'images/firmas/Firma_Oscar.png',
            'estado' => true
        ]);

        $licVictor->firma()->create([
            'tipo' => 'sello',
            'ruta_storage' => 'images/firmas/Sello_Victor.png',
            'estado' => true
        ]);

       
        $ingeDorian->firma()->create([
            'tipo' => 'firma',
            'ruta_storage' => 'images/firmas/Firma_Dorian.png',
            'estado' => true,
        ]);

        $ingeDorian->firma()->create([
            'tipo' => 'sello',
            'ruta_storage' => 'images/firmas/Sello_Dorian.png',
            'estado' => true
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
                'cuenta' => '20212300157',
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
