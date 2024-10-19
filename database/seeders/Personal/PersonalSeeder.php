<?php

namespace Database\Seeders\Personal;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Personal\Empleado;


class PersonalSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        //
        $userDaniel = User::create([
            'name' => 'Daniel',
            'email' => 'jdhenrriquez@unah.hn',
            'password' => bcrypt('123'), // Asegurarse de encriptar la contraseña
        ]);

        $userKenis = User::create([
            'name' => 'Kenis',
            'email' => 'knorosorto@unah.hn',
            'password' => bcrypt('123'), // Asegurarse de encriptar la contraseña
        ]);

        $userFrancisco = User::create([
            'name' => 'Francisco',
            'email' => 'fjpazf@unah.hn',
            'password' => bcrypt('123'), // Asegurarse de encriptar la contraseña
        ]);

        $userEdu = User::create([
            'name' => 'Edu',
            'email' => 'edu@unah.hn',
            'password' => bcrypt('123'), // Asegurarse de encriptar la contraseña
        ]);

        Empleado::create([
            'nombre_completo' => 'Daniel Josue Henrriquez',
            'numero_empleado' => '12312',
            'celular' => '99999999',
            'categoria' => 'Docente',
            'user_id' => $userDaniel->id,
            'campus_id' => 1,
            'departamento_academico_id' => 1
        ]);

        Empleado::create([
            'nombre_completo' => 'Kenis Norosorto',
            'numero_empleado' => '12313',
            'celular' => '99999999',
            'categoria' => 'Docente',
            'user_id' => $userKenis->id,
            'campus_id' => 1,
            'departamento_academico_id' => 1
        ]);

        Empleado::create([
            'nombre_completo' => 'Francisco Jose Paz',
            'numero_empleado' => '12314',
            'celular' => '99999999',
            'categoria' => 'Docente',
            'user_id' => $userFrancisco->id,
            'campus_id' => 1,
            'departamento_academico_id' => 1
        ]);

        Empleado::create([
            'nombre_completo' => 'Eduardo',
            'numero_empleado' => '12315',
            'celular' => '99999999',
            'categoria' => 'Docente',
            'user_id' => $userEdu->id,
            'campus_id' => 1,
            'departamento_academico_id' => 1
        ]);





    }
}
