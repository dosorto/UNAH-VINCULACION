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
        $permission111 = Permission::create(['name' => 'proyectos-admin-solicitados-final']);

        // modulo de configuracion
        $permission12 = Permission::create(['name' => 'configuracion-admin-logs']);
        $permission13 = Permission::create(['name' => 'configuracion-admin-mi-perfil']);

        // modulo de inicio
        $permission14 = Permission::create(['name' => 'inicio-admin-inicio']);
        $permission15 = Permission::create(['name' => 'inicio-docente-inicio']);

        // modulo de constancias 
        $permission15 = Permission::create(['name' => 'constancia-admin-constancias']);

        // modulo para el usuario docente
        $permission16 = Permission::create(['name' => 'docente-admin-proyectos']);
        $permission17 = Permission::create(['name' => 'docente-crear-proyecto']);

        // modulo para el administrador de centro / facultad
        $permission18 = Permission::create(['name' => 'admin_centro_facultad-proyectos']);

        $permission19 = Permission::create(['name' => 'cambiar-datos-personales']);

        $permission20 = Permission::create(['name' => 'unidad-academica-admin-campus']);
        $permission21 = Permission::create(['name' => 'unidad-academica-admin-carrera']);
        $permission22 = Permission::create(['name' => 'unidad-academica-admin-departamento']);
        $permission23 = Permission::create(['name' => 'unidad-academica-admin-facultad']);
        $permission24 = Permission::create(['name' => 'global-set-role']);

        $permission25 = Permission::create(['name' => 'estudiante-inicio-inicio']);

        // apariencia-admin-slides
        $permission27 = Permission::create(['name' => 'apariencia-admin-slides']);
        // proyectos-admin-informenes
        $permission28 = Permission::create(['name' => 'proyectos-admin-informenes']);
        // proyectos-admin-revision-final
        $permission29 = Permission::create(['name' => 'proyectos-admin-revision-final']);

        $permission30 = Permission::create(['name' => 'ver-dashboard-admin']);
        $permission31 = Permission::create(['name' => 'ver-dashboard-docente']);
        $permission32 = Permission::create(['name' => 'ver-dashboard-estudiante']);
        $permission33 = Permission::create(['name' => 'ver-dashboard-admin-centro-facultad']);

        // crear un rol de administrador con todos los permisos anteriores
        $role = Role::create(['name' => 'admin']);
        $roleDocente = Role::create(['name' => 'docente']);
        $roleAdminCentroFacultad = Role::create(['name' => 'Director/Enlace']);
        $rolEstudiante = Role::create(['name' => 'estudiante']);

  //      $rolEstudiante->givePermissionTo([
//
       // ]);

        $role->givePermissionTo([
            'demografia-admin-pais',
            'demografia-admin-departamento',
            'demografia-admin-municipio',
            'usuarios-admin-usuarios',
            'ver-dashboard-admin',
            'proyectos-admin-revision-final',
            'apariencia-admin-slides',
            'proyectos-admin-informenes',
            'usuarios-admin-rol',
            'usuarios-admin-permiso',
            'empleados-admin-empleados',
            'proyectos-admin-proyectos',
            'proyectos-admin-solicitados',
            'proyectos-admin-aprobados',
            'configuracion-admin-logs',
            'inicio-admin-inicio',
            'proyectos-admin-solicitados-final',
            'configuracion-admin-mi-perfil',
            'unidad-academica-admin-campus',
            'unidad-academica-admin-carrera',
            'unidad-academica-admin-departamento',
            'unidad-academica-admin-facultad',
            'global-set-role',
            'constancia-admin-constancias',
            
            
        ])->save();

        $roleDocente->givePermissionTo([
            'inicio-docente-inicio',
            'docente-admin-proyectos',
            'docente-crear-proyecto',
            'configuracion-admin-mi-perfil',
            'ver-dashboard-docente',
            'global-set-role',
        ])->save();

        $roleAdminCentroFacultad->givePermissionTo([
            'admin_centro_facultad-proyectos',
            'global-set-role',
            'ver-dashboard-admin-centro-facultad',
        ])->save();
    }
}
