<?php

namespace Database\Seeders\Personal;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Personal\Empleado;
use App\Models\Estudiante\Estudiante;

use App\Models\Personal\CategoriaEmpleado;
use Spatie\Permission\Models\Role;


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

        $usuariosPruebaVinculacion = [
            [
                'name' => 'Coordinador Proyecto Prueba',
                'email' => 'anbetancourt@unah.hn',
                'given_name' => 'Coordinador',
                'surname' => 'Proyecto',
                'role' => 'Coordinador Proyecto',
                'numero_empleado' => '900001',
            ],
            [
                'name' => 'Enlace Vinculacion Prueba',
                'email' => 'anbetancourt@unah.hn',
                'given_name' => 'Enlace',
                'surname' => 'Vinculacion',
                'role' => 'Enlace Vinculacion',
                'numero_empleado' => '900002',
            ],
            [
                'name' => 'Jefe Departamento Prueba',
                'email' => 'anbetancourt@unah.hn',
                'given_name' => 'Jefe',
                'surname' => 'Departamento',
                'role' => 'Jefe Departamento',
                'numero_empleado' => '900003',
            ],
            [
                'name' => 'Director Centro Prueba',
                'email' => 'anbetancourt@unah.hn',
                'given_name' => 'Director',
                'surname' => 'Centro',
                'role' => 'Director centro',
                'numero_empleado' => '900004',
            ],
            [
                'name' => 'Revisor Vinculacion Prueba',
                'email' => 'anbetancourt@unah.hn',
                'given_name' => 'Revisor',
                'surname' => 'Vinculacion',
                'role' => 'Revisor Vinculacion',
                'numero_empleado' => '900005',
            ],
            [
                'name' => 'Director Vinculacion Prueba',
                'email' => 'anbetancourt@unah.hn',
                'given_name' => 'Director',
                'surname' => 'Vinculacion',
                'role' => 'Director Vinculacion',
                'numero_empleado' => '900006',
            ],
        ];

        $categoriaAdministrativoId = CategoriaEmpleado::where('nombre', 'Administrativo')->value('id') ?? 9;

        foreach ($usuariosPruebaVinculacion as $usuarioRol) {
            $role = Role::firstOrCreate([
                'name' => $usuarioRol['role'],
                'guard_name' => 'web',
            ]);

            $usuario = User::updateOrCreate(
                ['email' => $usuarioRol['email']],
                [
                    'name' => $usuarioRol['name'],
                    'password' => bcrypt('123'),
                    'surname' => $usuarioRol['surname'],
                    'given_name' => $usuarioRol['given_name'],
                    'active_role_id' => $role->id,
                ]
            );

            $usuario->syncRoles([$role->name]);

            Empleado::updateOrCreate(
                ['numero_empleado' => $usuarioRol['numero_empleado']],
                [
                    'nombre_completo' => $usuarioRol['name'],
                    'celular' => '99999999',
                    'sexo' => 'Masculino',
                    'user_id' => $usuario->id,
                    'centro_facultad_id' => 4,
                    'departamento_academico_id' => 9,
                    'categoria_id' => $categoriaAdministrativoId,
                    'tipo_empleado' => 'administrativo',
                ]
            );
        }

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

            $user2->givePermissionTo('perfil.editar');
            $user2->givePermissionTo('configuracion.perfil');

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

            $user3->user->givePermissionTo('perfil.editar');
            $user3->user->givePermissionTo('configuracion.perfil');
        }
    }
}
