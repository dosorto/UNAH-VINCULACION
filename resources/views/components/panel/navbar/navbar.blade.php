<x-panel.navbar.navbar-container>
    <x-panel.navbar.group-item titulo="Dashboard" icono="ri-dashboard-line">
        <x-panel.navbar.one-item titulo="Inicio" route="inicio" :routes="['inicio']" :permisos="['inicio-admin-inicio', 'inicio-docente-inicio']"
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
            icono="heroicon-o-map-pin" />

        <x-panel.navbar.one-item titulo="Usuarios" route="Usuarios" :routes="['Usuarios', 'roles', 'listPermisos']" icono="heroicon-o-user-group"
            :permisos="['usuarios-admin-usuarios', 'usuarios-admin-rol', 'usuarios-admin-permiso']" />

        <x-panel.navbar.one-item titulo="Empleado" route="ListarEmpleados" :routes="['ListarEmpleados', 'crearEmpleado']" icono="heroicon-c-cube"
            :permisos="['empleados-admin-empleados']" />


        <x-panel.navbar.one-item titulo="Proyecto" route="listarProyectosVinculacion" :routes="[
            'crearProyectoVinculacion',
            'listarProyectosVinculacion',
            'listarProyectosSolicitado',
            'listarProyectoRevisionFinal',
            'listarInformesSolicitado',
        ]"
            icono="heroicon-m-puzzle-piece" :permisos="['proyectos-admin-proyectos', 'proyectos-admin-solicitados', 'proyectos-admin-aprobados']" />

        <!-- Modulo de docentes -->
        <x-panel.navbar.one-item titulo="Proyectos" route="proyectosDocente" :routes="['proyectosDocente']"
            icono="heroicon-o-academic-cap" :permisos="['docente-admin-proyectos', 'docente-admin-solicitados']" />




        <!-- Grupo de "Firmas" dentro del grupo "Dashboard" -->
        <x-panel.navbar.one-item titulo="Firmas" route="SolicitudProyectosDocente" :routes="['SolicitudProyectosDocente']" :permisos="['docente-admin-proyectos', 'docente-admin-solicitados']"
            icono="heroicon-o-document-text" />
    </x-panel.navbar.group-item>
</x-panel.navbar.navbar-container>
