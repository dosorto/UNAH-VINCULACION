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

    
        $userVictor = User::create([
            'name' => 'Victor',
            'email' => 'victor@unah.edu.hn',
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

        


        $ingeJessica = Empleado::create([
            'nombre_completo' => 'JESSICA NOHELY AVILA CRUZ',
            'numero_empleado' => '12310',
            'celular' => '99999999',
            'user_id' => $userJessica->id,
            'centro_facultad_id' => 1,
            'departamento_academico_id' => 1,
            'categoria_id' => 1
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

       


        

        




    }
}
