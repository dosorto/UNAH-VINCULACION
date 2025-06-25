<?php

namespace Database\Seeders;

use App\Models\Demografia\Departamento;
use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Database\Seeders\Demografia\PaisesSeeder;
use Database\Seeders\Demografia\DepartamentoSeeder; 
use Database\Seeders\UnidadAcademica\UnidadAcademicaSeeder;
use Database\Seeders\Proyecto\ProyectoSeeder;
use Database\Seeders\Personal\PersonalSeeder;
use Database\Seeders\Demografia\MunicipioSeeder;
use Database\Seeders\Personal\PermisosSeeder;
use Database\Seeders\Proyecto\EstadoProyectoSeeder;
use Database\Seeders\Proyecto\DataProyectoSeeder;
use Database\Seeders\Proyecto\EmpleadoProyectoSeeder;



class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {

        $this->call(PaisesSeeder::class);
        $this->call(DepartamentoSeeder::class);
        $this->call(MunicipioSeeder::class);
        $this->call(UnidadAcademicaSeeder::class);
        $this->call(ProyectoSeeder::class);
        $this->call(PermisosSeeder::class);
        $this->call(PersonalSeeder::class);
       

       // $this->call(DataProyectoSeeder::class);
        //$this->call(EmpleadoProyectoSeeder::class);
       // $this->call(EstadoProyectoSeeder::class);
        // User::factory(10)->create();

        // User::factory()->create([
        //     'name' => 'Test User',
        //     'email' => 'test@example.com',
        // ]);
         // Crear un rol
         //$role = Role::create(['name' => 'admin']);

         // Crear un permiso
         //$permission = Permission::create(['name' => 'edit articles']);
 
         // Asignar un rol a un usuario (con contraseña encriptada)
        
 
         // Asignar permisos a roles
        // $role->givePermissionTo('edit articles')->save();
    }
}
