<x-panel.navbar.navbar-container>
    <x-panel.navbar.group-item titulo="Dashboard" icono="ri-dashboard-line">
        <x-panel.navbar.one-item titulo="Inicio" route="inicio" :routes="['inicio']" :permisos="['inicio-admin-inicio', 'ver-dashboard-admin', 'ver-dashboard-docente']"
            icono="heroicon-o-home" />
        <x-panel.navbar.one-item titulo="Demografía" route="listarPaises" :routes="[
            'listarPaises',
            'crearPais',
            'listarDepartamentos',
            'crearDepartamento',
            'ListarMunicipios',
            'crearMunicipio',
            'ListarAldeas',
            'crearAldea',
            'ListarCiudades',
            'crearCiudad',
        ]" :permisos="[
            'demografia-admin-pais',
            'demografia-admin-departamento',
            'demografia-admin-municipio',
            'demografia-admin-aldea',
            'demografia-admin-ciudad',
        ]"
            icono="heroicon-o-map-pin" :children="[
                [
                    'texto' => 'Pais',
                    'route' => 'listarPaises',
                    'permiso' => 'demografia-admin-pais',
                ],
                [
                    'texto' => 'Departamento',
                    'route' => 'listarDepartamentos',
                    'permiso' => 'demografia-admin-departamento',
                ],
                [
                    'texto' => 'Municipios',
                    'route' => 'ListarMunicipios',
                    'permiso' => 'demografia-admin-municipio',
                ],
            
                // More child routes...
            ]" />

        <x-panel.navbar.one-item titulo="Usuarios" route="Usuarios" :routes="['Usuarios', 'roles', 'listPermisos']" icono="heroicon-o-user-group"
            :permisos="['usuarios-admin-usuarios', 'usuarios-admin-rol', 'usuarios-admin-permiso']" :children="[
                [
                    'texto' => 'Usuarios',
                    'route' => 'Usuarios',
                    'permiso' => 'usuarios-admin-usuarios',
                ],
                [
                    'texto' => 'Roles',
                    'route' => 'roles',
                    'permiso' => 'usuarios-admin-rol',
                ],
                [
                    'texto' => 'Permisos',
                    'route' => 'listPermisos',
                    'permiso' => 'usuarios-admin-permiso',
                ],
            ]" />

        <x-panel.navbar.one-item titulo="Constancias" route="constancias" :routes="['constancias']"
            icono="heroicon-o-document-text" :permisos="['constancia-admin-constancias']" />


        <x-panel.navbar.one-item titulo="Empleado" route="ListarEmpleados" :routes="['ListarEmpleados', 'crearEmpleado']" icono="heroicon-c-cube"
            :permisos="['empleados-admin-empleados']" :children="[
                [
                    'texto' => 'Empleados',
                    'route' => 'ListarEmpleados',
                    'permiso' => 'empleados-admin-empleados',
                ],
                [
                    'texto' => 'Crear Empleado',
                    'route' => 'crearEmpleado',
                    'permiso' => 'empleados-admin-empleados',
                ],
            ]" />


        <x-panel.navbar.one-item titulo="Proyecto" route="listarProyectosVinculacion" :routes="[
            'crearProyectoVinculacion',
            'listarProyectosVinculacion',
            'listarProyectosSolicitado',
            'listarProyectoRevisionFinal',
            'listarInformesSolicitado',
            'proyectos-admin-revision-final',
            'proyectos-admin-informenes',
            'proyectos-admin-proyectos',
        ]"
            icono="heroicon-m-puzzle-piece" :permisos="[
                'proyectos-admin-proyectos',
                'proyectos-admin-solicitados',
                'proyectos-admin-aprobados',
                'proyectos-admin-informenes',
                'proyectos-admin-revision-final',
            ]" :children="[
                [
                    'texto' => 'Proyectos',
                    'route' => 'listarProyectosVinculacion',
                    'permiso' => 'proyectos-admin-proyectos',
                    // 'funcion' => 'obtenerCantidadProyectos',
                ],
                [
                    'texto' => 'Solicitud Proyecto',
                    'route' => 'listarProyectosSolicitado',
                    'permiso' => 'proyectos-admin-solicitados',
                    'funcion' => 'obtenerCantidadProyectosEnRevision',
                ],
                [
                    'texto' => 'Revisión de Informes',
                    'route' => 'listarInformesSolicitado',
                    'permiso' => 'proyectos-admin-informenes',
                    'funcion' => 'obtenerCantidadInformesSolicitados',
                ],
                [
                    'texto' => 'Revisión Final',
                    'route' => 'listarProyectoRevisionFinal',
                    'permiso' => 'proyectos-admin-revision-final',
                    'funcion' => 'obtenerCantidadProyectosEnRevisionFinal',
                ],
            ]" />



        <x-panel.navbar.one-item titulo="Unidad Academica" route="campus" :routes="['campus', 'carrera', 'departamento-academico', 'facultad-centro']"
            icono="heroicon-o-academic-cap" :permisos="[
                'unidad-academica-admin-campus',
                'unidad-academica-admin-carrera',
                'unidad-academica-admin-departamento',
                'unidad-academica-admin-facultad',
            ]" :children="[
                [
                    'texto' => 'Campus',
                    'route' => 'campus',
                    'permiso' => 'unidad-academica-admin-campus',
                ],
                [
                    'texto' => 'Carrera',
                    'route' => 'carrera',
                    'permiso' => 'unidad-academica-admin-carrera',
                ],
                [
                    'texto' => 'Departamento Academico',
                    'route' => 'departamento-academico',
                    'permiso' => 'unidad-academica-admin-departamento',
                ],
                [
                    'texto' => 'Facultad Centro',
                    'route' => 'facultad-centro',
                    'permiso' => 'unidad-academica-admin-facultad',
                ],
            ]" />

        <!-- MODULO DE DIRECTOR CENTRO -->
        @can('admin_centro_facultad-proyectos')
            <x-panel.navbar.one-item titulo="Proyectos" route="proyectosCentroFacultad" :routes="['proyectosCentroFacultad']"
                parametro="{{ auth()->user()->empleado->centro_facultad_id }}" icono="heroicon-o-academic-cap"
                :permisos="['admin_centro_facultad-proyectos']" />
        @endcan



        <!-- Modulo de docentes  -->
        <x-panel.navbar.one-item titulo="Proyectos" route="proyectosDocente" :routes="['proyectosDocente']"
            icono="heroicon-o-academic-cap" :permisos="['docente-admin-proyectos']" :children="[
                [
                    'texto' => 'Proyectos',
                    'route' => 'proyectosDocente',
                    'permiso' => 'docente-admin-proyectos',
                ],
                [
                    'texto' => 'Crear Proyecto',
                    'route' => 'crearProyectoVinculacion',
                    'permiso' => 'docente-admin-proyectos',
                ],
            ]" />



        <!-- Grupo de "Firmas" dentro del grupo "Dashboard" -->
        <x-panel.navbar.one-item titulo="Firmas" route="SolicitudProyectosDocente" :routes="['SolicitudProyectosDocente']" :permisos="['docente-admin-proyectos', 'docente-admin-solicitados']"
            icono="heroicon-o-document-text" :children="[
                [
                    'texto' => 'Solicitud Proyecto',
                    'route' => 'SolicitudProyectosDocente',
                    'permiso' => 'docente-admin-proyectos',
                    'funcion' => 'obtenerCantidadProyectosPorFirmar',
                ],
                [
                    'texto' => 'Aprobado Proyectos',
                    'route' => 'AprobadoProyectosDocente',
                    'permiso' => 'docente-admin-proyectos',
                ],
                [
                    'texto' => 'Pendientes Proyectos',
                    'route' => 'RechazadoProyectosDocente',
                    'permiso' => 'docente-admin-proyectos',
                ],
            ]" />
    </x-panel.navbar.group-item>

</x-panel.navbar.navbar-container>
