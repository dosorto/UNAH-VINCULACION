<x-panel.navbar.navbar-container>
    <x-panel.navbar.group-item titulo="Dashboard" icono="ri-dashboard-line">
        <x-panel.navbar.one-item titulo="Demografia" route="listarPaises" :routes="['listarPaises', 'crearPais', 'listarDepartamentos', 'crearDepartamento','ListarMunicipios', 'crearMunicipio', 'ListarAldeas', 'crearAldea', 'ListarCiudades', 'crearCiudad']" icono="heroicon-o-home" />
        <x-panel.navbar.one-item titulo="Usuarios" route="Usuarios" :routes="['Usuarios', 'roles', 'listPermisos']" icono="heroicon-o-user-group" /> 
        <x-panel.navbar.one-item titulo="Empleado" route="ListarEmpleados" :routes="['ListarEmpleados', 'crearEmpleado']" icono="heroicon-c-cube" />
        <x-panel.navbar.one-item titulo="Proyecto" route="listarProyectosVinculacion" :routes="['crearProyectoVinculacion', 'listarProyectosVinculacion']" icono="heroicon-m-puzzle-piece" />
    </x-panel.navbar.group-item>
</x-panel.navbar.navbar-container>
