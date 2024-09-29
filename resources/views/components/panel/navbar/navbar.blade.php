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

    </x-panel.navbar.navbar-container>
</div>
