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

        CategoriaEmpleado::create([
            'nombre' => 'Profesores x hora',
            'descripcion' => 'Profesores x hora'
        ]);

        CategoriaEmpleado::create([
            'nombre' => 'Profesores horarios',
            'descripcion' => 'Profesores horarios'
        ]);

        CategoriaEmpleado::create([
            'nombre' => 'Administrativo',
            'descripcion' => 'Administrativo'
        ]);

        CategoriaEmpleado::create([
            'nombre' => 'Servicios',
            'descripcion' => 'Servicios'
        ]);

        CategoriaEmpleado::create([
            'nombre' => 'Asistentes técnicos laboratorios / Instructores',
            'descripcion' => 'Asistentes técnicos laboratorios / Instructores'
        ]);


        $user = User::create([
            'name' => 'NOTIFICACIONES  POA',
            'email' => 'notificacionespoa@unah.edu.hn',
            'microsoft_id' => "0d887b9b-9589-4e2c-8d65-4ced9d5d6c87",
            'password' => bcrypt('123'), // Asegurarse de encriptar la contraseña
            'surname' => 'POA',
            'given_name' => 'NOTIFICACIONES',
            'active_role_id' => 1
        ])->assignRole(['admin', 'docente', 'Director/Enlace']);

        $adminUser = Empleado::create([
            'nombre_completo' => 'NOTIFICACIONES POA',
            'numero_empleado' => '12280',
            'celular' => '99999999',
            'sexo' => 'Masculino',
            'user_id' => $user->id,
            'centro_facultad_id' => 4,
            'departamento_academico_id' => 9,
            'categoria_id' => 2
        ]);

        $adminUser->firma()->create([
            'tipo' => 'firma',
            'ruta_storage' => 'images/firmas/Firma_Oscar.png',
            'estado' => true
        ]);

        $adminUser->firma()->create([
            'tipo' => 'sello',
            'ruta_storage' => 'images/firmas/Sello_Victor.png',
            'estado' => true
        ]);

        if (app()->environment('local')) {

            $user2 = User::create([
                'name' => 'Usuario Ejemplo',
                'email' => 'usuario.ejemplo@unah.hn',
                'password' => bcrypt('123'),
                'surname' => 'Ernesto',
                'given_name' => 'Moncada Valladares',
            ]);

            Empleado::create([
                'nombre_completo' => 'Usuario Ejemplo',
                'numero_empleado' => '1228asdfasdf0',
                'celular' => '99999999',
                'sexo' => 'Masculino',
                'user_id' => $user2->id,
                'centro_facultad_id' => 4,
                'departamento_academico_id' => 9,
                'categoria_id' => 2
            ]);

            $user2->givePermissionTo('cambiar-datos-personales');
            $user2->givePermissionTo('configuracion-admin-mi-perfil');

            $user3 = User::create([
                'name' => 'Estudiante  POA',
                'email' => 'estudiante@unah.hn',
                'password' => bcrypt('123'),
                'surname' => 'POA',
                'given_name' => 'NOTIFICACIONES',
            ]);

            $user3 = Estudiante::create([
                'cuenta' => '123123',
                'user_id' => $user3->id,
                'nombre' => 'nombre',
                'apellido' => 'apellido',
                'sexo' => 'Masculino',
            ]);

            $user3->user->givePermissionTo('cambiar-datos-personales');
            $user3->user->givePermissionTo('configuracion-admin-mi-perfil');
        }
    }
}