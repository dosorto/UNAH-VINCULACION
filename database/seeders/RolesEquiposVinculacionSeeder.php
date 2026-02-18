<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RolesEquiposVinculacionSeeder extends Seeder
{
    /**
     * Run the database seeder.
     */
    public function run(): void
    {
        // Crear roles para equipos de vinculación
        $roleDesarrolloLocal = Role::firstOrCreate(['name' => 'equipo_desarrollo_local']);
        $roleEducacionNoFormal = Role::firstOrCreate(['name' => 'equipo_vinculacion_educacion_no_formal']);

        // Permisos base que tendrán ambos equipos
        $permisosBase = [
            'inicio-admin-inicio',
            'proyectos-admin-proyectos',
            'proyectos-admin-solicitados',
            'proyectos-admin-revision-final',
            'configuracion-admin-mi-perfil',
            'global-set-role',
            'tickets-ver-modulo',
            'ver-dashboard-admin',
        ];

        // Asignar permisos al equipo de Desarrollo Local
        $roleDesarrolloLocal->givePermissionTo($permisosBase);

        // Asignar permisos al equipo de Educación No Formal
        $roleEducacionNoFormal->givePermissionTo($permisosBase);

        $this->command->info('Roles de equipos de vinculación creados exitosamente:');
        $this->command->info('- equipo_desarrollo_local');
        $this->command->info('- equipo_vinculacion_educacion_no_formal');
    }
}
