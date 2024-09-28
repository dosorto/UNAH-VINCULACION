<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();

        // User::factory()->create([
        //     'name' => 'Test User',
        //     'email' => 'test@example.com',
        // ]);
         // Crear un rol
         $role = Role::create(['name' => 'admin']);

         // Crear un permiso
         $permission = Permission::create(['name' => 'edit articles']);
 
         // Asignar un rol a un usuario (con contraseña encriptada)
         $user = User::create([
             'name' => 'neto',
             'email' => 'neto@unah.hn',
             'password' => bcrypt('123'), // Asegurarse de encriptar la contraseña
         ]);
         $user->assignRole('admin')->save();
 
         // Asignar permisos a roles
         $role->givePermissionTo('edit articles')->save();
    }
}
