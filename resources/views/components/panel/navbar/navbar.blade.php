<div>
    <x-panel.navbar.navbar-container>
        <x-panel.navbar.one-item icono="bx-bell" titulo="Dashboard" notificaciones=0 />
        <x-panel.navbar.title-item titulo="Demografia"/>

        <x-panel.navbar.group-item titulo="Pais" >
            <x-panel.navbar.one-group-item titulo="Crear Pais" route="crearPais"/>
            <x-panel.navbar.one-group-item titulo="Lista Paises" route="listarPaises"/>
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
            <x-panel.navbar.one-group-item titulo="Lista Departamentos" route="ListarCiudades"/>
        </x-panel.navbar.group-item>
        <x-panel.navbar.title-item titulo="Usuario"/>

        <x-panel.navbar.group-item titulo="Empleados" >
            <x-panel.navbar.one-group-item titulo="Crear Empleado" route="crearEmpleado"/>
            <x-panel.navbar.one-group-item titulo="Lista Empleado" route="ListarEmpleados"/>
        </x-panel.navbar.group-item>

    </x-panel.navbar.navbar-container>
</div>
