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
            ['name' => 'configuracion.flujos',     'display_name' => 'Administrar Flujos de Proyectos'],
            // DAFT
            ['name' => 'daft.acceso',              'display_name' => 'Acceder al Modulo DAFT'],
            ['name' => 'configuracion.integraciones-api', 'display_name' => 'Administrar Integraciones API'],
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
            ['name' => 'unidad-academica.asignatura',   'display_name' => 'Administrar Asignaturas'],
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
        $roleCoordinadorProyecto = Role::firstOrCreate(['name' => 'Coordinador Proyecto', 'guard_name' => 'web']);
        $roleEnlaceVinculacion = Role::firstOrCreate(['name' => 'Enlace Vinculacion', 'guard_name' => 'web']);
        $roleJefeDepartamento = Role::firstOrCreate(['name' => 'Jefe Departamento', 'guard_name' => 'web']);
        $roleDirectorCentro = Role::firstOrCreate(['name' => 'Director centro', 'guard_name' => 'web']);
        $roleRevisorVinculacion = Role::firstOrCreate(['name' => 'Revisor Vinculacion', 'guard_name' => 'web']);
        $roleDirectorVinculacion = Role::firstOrCreate(['name' => 'Director Vinculacion', 'guard_name' => 'web']);
        $roleDaftGestor = Role::firstOrCreate(['name' => 'DAFT Gestor', 'guard_name' => 'web']);
        $roleDaftRevisorEtapa1 = Role::firstOrCreate(['name' => 'DAFT Revisor Etapa 1', 'guard_name' => 'web']);
        $roleDaftRevisorEtapa2 = Role::firstOrCreate(['name' => 'DAFT Revisor Etapa 2', 'guard_name' => 'web']);

        $role->syncPermissions([
            'demografia.pais', 'demografia.departamento', 'demografia.municipio',
            'demografia.aldea', 'demografia.ciudad',
            'usuarios.usuarios', 'usuarios.roles', 'usuarios.permisos',
            'empleados.empleados',
            'proyectos.historial', 'proyectos.solicitados', 'proyectos.aprobados',
            'proyectos.firma-director', 'proyectos.informes', 'proyectos.revision-final',
            'configuracion.logs', 'configuracion.perfil', 'configuracion.contactanos', 'configuracion.flujos',
            'inicio.admin', 'dashboard.admin',
            'constancia.constancias',
            'unidad-academica.campus', 'unidad-academica.carrera',
            'unidad-academica.asignatura', 'unidad-academica.departamento', 'unidad-academica.facultad',
            'global.set-role',
            'apariencia.slides',
            'estudiante.admin',
            'tickets.ver', 'tickets.administrar',
            'daft.acceso',
        ]);

        $roleDocente->syncPermissions([
            'inicio.docente', 'dashboard.docente',
            'docente.proyectos', 'docente.crear-proyecto',
            'unidad-academica.asignatura',
            'configuracion.perfil', 'global.set-role',
            'tickets.ver',
        ]);

        $roleDirector->syncPermissions([
            'director.proyectos', 'inicio.admin',
            'dashboard.director', 'global.set-role', 'tickets.ver',
        ]);

        $roleEstudiante->syncPermissions([
            'inicio.estudiante', 'dashboard.estudiante',
            'constancia.constancias', 'global.set-role',
            'tickets.ver',
        ]);

        $roleCoordinadorProyecto->syncPermissions([
            'inicio.admin', 'dashboard.director',
            'docente.proyectos', 'docente.crear-proyecto',
            'unidad-academica.asignatura',
            'configuracion.perfil', 'global.set-role',
            'tickets.ver',
        ]);

        $roleEnlaceVinculacion->syncPermissions([
            'director.proyectos', 'docente.proyectos', 'inicio.admin',
            'dashboard.director', 'global.set-role',
            'configuracion.perfil', 'tickets.ver',
        ]);

        $roleJefeDepartamento->syncPermissions([
            'director.proyectos', 'docente.proyectos', 'inicio.admin',
            'dashboard.director', 'global.set-role',
            'configuracion.perfil', 'tickets.ver',
        ]);

        $roleDirectorCentro->syncPermissions([
            'director.proyectos', 'docente.proyectos', 'inicio.admin',
            'dashboard.director', 'global.set-role',
            'configuracion.perfil', 'tickets.ver',
        ]);

        $roleRevisorVinculacion->syncPermissions([
            'proyectos.historial', 'proyectos.solicitados', 'proyectos.informes', 'docente.proyectos',
            'inicio.admin', 'dashboard.director',
            'global.set-role', 'configuracion.perfil', 'tickets.ver',
        ]);

        $roleDirectorVinculacion->syncPermissions([
            'proyectos.historial', 'proyectos.aprobados', 'proyectos.firma-director',
            'proyectos.revision-final', 'proyectos.informes', 'docente.proyectos',
            'inicio.admin', 'dashboard.director',
            'global.set-role', 'configuracion.perfil', 'tickets.ver',
        ]);

        $daftPermissions = [
            'daft.acceso',
            'configuracion.flujos',
            'inicio.admin',
            'dashboard.admin',
            'global.set-role',
            'configuracion.perfil',
            'tickets.ver',
        ];

        $roleDaftGestor->syncPermissions($daftPermissions);
        $roleDaftRevisorEtapa1->syncPermissions($daftPermissions);
        $roleDaftRevisorEtapa2->syncPermissions($daftPermissions);
    }
}
