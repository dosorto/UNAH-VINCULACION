<div>
    <x-panel.navbar.navbar-container>
        <x-panel.navbar.one-item icono="bx-bell" titulo="Dashboard" notificaciones=0 route="crearPais" />
        <x-panel.navbar.title-item titulo="Demografía"/>

        <x-panel.navbar.group-item titulo="País" >
            <x-panel.navbar.one-group-item titulo="Crear País" route="crearPais"/>
            <x-panel.navbar.one-group-item titulo="Lista Países" route="listarPaises"/>
        </x-panel.navbar.group-item>

        <x-panel.navbar.group-item titulo="Departamento" >
            <x-panel.navbar.one-group-item titulo="Crear Departamento" route="crearDepartamento"/>
            <x-panel.navbar.one-group-item titulo="Lista Departamentos" route="listarDepartamentos"/>
        </x-panel.navbar.group-item>

        <x-panel.navbar.group-item titulo="Municipio" >
            <x-panel.navbar.one-group-item titulo="Crear Municio" route="crearMunicipio"/>
            <x-panel.navbar.one-group-item titulo="Lista Municipios" route="ListarMunicipios"/>
        </x-panel.navbar.group-item>

        <x-panel.navbar.group-item titulo="Aldea" >
            <x-panel.navbar.one-group-item titulo="Crear Aldea" route="crearAldea"/>
            <x-panel.navbar.one-group-item titulo="Lista Aldeas" route="ListarAldeas"/>
        </x-panel.navbar.group-item>


        <x-panel.navbar.group-item titulo="Ciudad" >
            <x-panel.navbar.one-group-item titulo="Crear Ciudad" route="crearCiudad"/>
            <x-panel.navbar.one-group-item titulo="Lista Ciudades" route="ListarCiudades"/>
        </x-panel.navbar.group-item>
        <x-panel.navbar.title-item titulo="Usuario"/>

        <x-panel.navbar.one-item titulo="Usuarios" notificaciones=0 route="Usuarios"/>

        <x-panel.navbar.group-item titulo="Empleados" >
            <x-panel.navbar.one-group-item titulo="Crear Empleado" route="crearEmpleado"/>
            <x-panel.navbar.one-group-item titulo="Lista Empleado" route="ListarEmpleados"/>
        </x-panel.navbar.group-item>
        <x-panel.navbar.title-item titulo="Administración"/>

        <x-panel.navbar.one-item titulo="Roles" route="roles"/>
        <x-panel.navbar.one-item titulo="Permisos" route="listPermisos"/>

    </x-panel.navbar.navbar-container>
</div>
