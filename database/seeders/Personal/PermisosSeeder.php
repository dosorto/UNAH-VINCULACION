<?php

namespace Database\Seeders\Personal;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;


class PermisosSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // crear los permisos de administrador dependiendo los modulos 
        // modulo de demografia
        $permission = Permission::create(['name' => 'demografia-admin-pais']);
        $permission1 = Permission::create(['name' => 'demografia-admin-departamento']);
        $permission2 = Permission::create(['name' => 'demografia-admin-municipio']);
        $permission3 = Permission::create(['name' => 'demografia-admin-aldea']);
        $permission4 = Permission::create(['name' => 'demografia-admin-ciudad']);

        // modulo de usuarios
        $permission5 = Permission::create(['name' => 'usuarios-admin-usuarios']);
        $permission6 = Permission::create(['name' => 'usuarios-admin-rol']);
        $permission7 = Permission::create(['name' => 'usuarios-admin-permiso']);

        // modulo de empleados
        $permission8 = Permission::create(['name' => 'empleados-admin-empleados']);

        // modulo de proyectos
        $permission9 = Permission::create(['name' => 'proyectos-admin-proyectos']);
        $permission10 = Permission::create(['name' => 'proyectos-admin-solicitados']);
        $permission11 = Permission::create(['name' => 'proyectos-admin-aprobados']);

        // modulo de configuracion
        $permission12 = Permission::create(['name' => 'configuracion-admin-logs']);
        $permission13 = Permission::create(['name' => 'configuracion-admin-mi-perfil']);

        // modulo de inicio
        $permission14 = Permission::create(['name' => 'inicio-admin-inicio']);
        $permission15 = Permission::create(['name' => 'inicio-docente-inicio']);

        // modulo para el usuario docente
        $permission16 = Permission::create(['name' => 'docente-admin-proyectos']);
        $permission17 = Permission::create(['name' => 'docente-crear-proyecto']);

        // crear un rol de administrador con todos los permisos anteriores
        $role = Role::create(['name' => 'admin']);
        $roleDocente = Role::create(['name' => 'docente']);

        $role->givePermissionTo([
            'demografia-admin-pais',
            'demografia-admin-departamento',
            'demografia-admin-municipio',
            'demografia-admin-aldea',
            'demografia-admin-ciudad',
            'usuarios-admin-usuarios',
            'usuarios-admin-rol',
            'usuarios-admin-permiso',
            'empleados-admin-empleados',
            'proyectos-admin-proyectos',
            'proyectos-admin-solicitados',
            'proyectos-admin-aprobados',
            'configuracion-admin-logs',
            'inicio-admin-inicio',
            
        ])->save();

        $roleDocente->givePermissionTo([
            'configuracion-admin-mi-perfil',
            'inicio-docente-inicio',
            'docente-admin-proyectos',
            'docente-crear-proyecto',
        ])->save();
    }
}
