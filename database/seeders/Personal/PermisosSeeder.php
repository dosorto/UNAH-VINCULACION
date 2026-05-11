<?php

namespace Database\Seeders\Personal;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class PermisosSeeder extends Seeder
{
    public function run(): void
    {
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        $permissions = [
            // Demografia
            ['name' => 'demografia.pais',          'display_name' => 'Administrar Paises'],
            ['name' => 'demografia.departamento',  'display_name' => 'Administrar Departamentos Geograficos'],
            ['name' => 'demografia.municipio',     'display_name' => 'Administrar Municipios'],
            ['name' => 'demografia.aldea',         'display_name' => 'Administrar Aldeas'],
            ['name' => 'demografia.ciudad',        'display_name' => 'Administrar Ciudades'],
            // Usuarios
            ['name' => 'usuarios.usuarios',        'display_name' => 'Administrar Usuarios'],
            ['name' => 'usuarios.roles',           'display_name' => 'Administrar Roles'],
            ['name' => 'usuarios.permisos',        'display_name' => 'Administrar Permisos'],
            // Empleados
            ['name' => 'empleados.empleados',      'display_name' => 'Administrar Empleados'],
            // Proyectos (Admin DVUS)
            ['name' => 'proyectos.historial',      'display_name' => 'Ver Historial de Proyectos'],
            ['name' => 'proyectos.solicitados',    'display_name' => 'Revisar Proyectos Solicitados'],
            ['name' => 'proyectos.aprobados',      'display_name' => 'Ver Proyectos Aprobados'],
            ['name' => 'proyectos.firma-director', 'display_name' => 'Firma Director DVUS'],
            ['name' => 'proyectos.informes',       'display_name' => 'Revisar Informes de Proyectos'],
            ['name' => 'proyectos.revision-final', 'display_name' => 'Revision Final y Firma DVUS'],
            // Configuracion
            ['name' => 'configuracion.logs',       'display_name' => 'Ver Logs del Sistema'],
            ['name' => 'configuracion.perfil',     'display_name' => 'Editar Mi Perfil'],
            ['name' => 'configuracion.contactanos','display_name' => 'Administrar Contactanos'],
            // Dashboard / Inicio
            ['name' => 'inicio.admin',             'display_name' => 'Inicio Administrador'],
            ['name' => 'inicio.docente',           'display_name' => 'Inicio Docente'],
            ['name' => 'inicio.estudiante',        'display_name' => 'Inicio Estudiante'],
            ['name' => 'dashboard.admin',          'display_name' => 'Ver Dashboard Administrador'],
            ['name' => 'dashboard.docente',        'display_name' => 'Ver Dashboard Docente'],
            ['name' => 'dashboard.estudiante',     'display_name' => 'Ver Dashboard Estudiante'],
            ['name' => 'dashboard.director',       'display_name' => 'Ver Dashboard Director Enlace'],
            // Constancias
            ['name' => 'constancia.constancias',   'display_name' => 'Administrar Constancias'],
            // Docente
            ['name' => 'docente.proyectos',        'display_name' => 'Gestion de Proyectos Docente'],
            ['name' => 'docente.crear-proyecto',   'display_name' => 'Crear Proyecto de Vinculacion'],
            // Director/Enlace
            ['name' => 'director.proyectos',       'display_name' => 'Historial Vinculacion Director'],
            // Perfil
            ['name' => 'perfil.editar',            'display_name' => 'Editar Datos Personales'],
            // Unidad Academica
            ['name' => 'unidad-academica.campus',       'display_name' => 'Administrar Campus'],
            ['name' => 'unidad-academica.carrera',      'display_name' => 'Administrar Carreras'],
            ['name' => 'unidad-academica.departamento', 'display_name' => 'Administrar Departamentos Academicos'],
            ['name' => 'unidad-academica.facultad',     'display_name' => 'Administrar Facultades y Centros'],
            // Global
            ['name' => 'global.set-role',          'display_name' => 'Cambiar Rol Activo'],
            // Apariencia
            ['name' => 'apariencia.slides',        'display_name' => 'Administrar Apariencia Slides'],
            // Estudiantes
            ['name' => 'estudiante.admin',         'display_name' => 'Administrar Estudiantes'],
            // Tickets
            ['name' => 'tickets.ver',              'display_name' => 'Ver Modulo de Tickets'],
            ['name' => 'tickets.administrar',      'display_name' => 'Administrar Tickets'],
        ];

        foreach ($permissions as $p) {
            Permission::updateOrCreate(['name' => $p['name']], ['display_name' => $p['display_name']]);
        }

        $role           = Role::firstOrCreate(['name' => 'admin',          'guard_name' => 'web']);
        $roleDocente    = Role::firstOrCreate(['name' => 'docente',         'guard_name' => 'web']);
        $roleDirector   = Role::firstOrCreate(['name' => 'Director/Enlace', 'guard_name' => 'web']);
        $roleEstudiante = Role::firstOrCreate(['name' => 'estudiante',      'guard_name' => 'web']);

        // modulo de configuracion
        $permission12 = Permission::create(['name' => 'configuracion-admin-logs']);
        $permission13 = Permission::create(['name' => 'configuracion-admin-mi-perfil']);
        $permission13b = Permission::create(['name' => 'configuracion-admin-flujos']);

        $roleDocente->syncPermissions([
            'inicio.docente', 'dashboard.docente',
            'docente.proyectos', 'docente.crear-proyecto',
            'configuracion.perfil', 'global.set-role',
            'tickets.ver',
        ]);

        $roleDirector->syncPermissions([
            'director.proyectos', 'inicio.admin',
            'dashboard.director', 'global.set-role', 'tickets.ver',
        ]);

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
        $permission34 = Permission::create(['name' => 'configuracion-admin-contactanos']);
        $permission35 = Permission::create(['name' => 'estudiante-admin-estudiante']);
        $permission36 = Permission::create(['name' => 'tickets-ver-modulo']);
        $permission37 = Permission::create(['name' => 'admin-tickets-administrar-tickets']);

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
            'configuracion-admin-flujos',
            'unidad-academica-admin-campus',
            'unidad-academica-admin-carrera',
            'unidad-academica-admin-departamento',
            'unidad-academica-admin-facultad',
            'global-set-role',
            'estudiante-admin-estudiante',
            'constancia-admin-constancias',
            'configuracion-admin-contactanos',
            'tickets-ver-modulo',
            'admin-tickets-administrar-tickets'
            
        ])->save();

        $roleDocente->givePermissionTo([
            'inicio-docente-inicio',
            'docente-admin-proyectos',
            'docente-crear-proyecto',
            'configuracion-admin-mi-perfil',
            'ver-dashboard-docente',
            'global-set-role',
            'tickets-ver-modulo',
        ])->save();

        $roleAdminCentroFacultad->givePermissionTo([
            'admin_centro_facultad-proyectos',
            'inicio-admin-inicio',
            'global-set-role',
            'ver-dashboard-admin-centro-facultad',
            'tickets-ver-modulo',            
        ])->save();

        $rolEstudiante->givePermissionTo([
            'inicio-estudiante-inicio',
            'global-set-role',    
            'ver-dashboard-estudiante',
            'tickets-ver-modulo',
        ])->save();
    }
}
