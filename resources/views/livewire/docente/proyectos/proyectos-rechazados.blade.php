<div>
    <div>
        <h1 class="text-2xl font-bold dark:text-white text-gray-800">Solicitudes de Firmas en Proyectos</h1>
    </div>

    <x-panel.navbar-horizontal.navbar-horizontal>
        <x-panel.navbar-horizontal.item titulo="Solicitudes de Firma" ruta="SolicitudProyectosDocente" permiso="docente-admin-proyectos" />
        <x-panel.navbar-horizontal.item titulo="Firmas Aprobadas" ruta="AprobadoProyectosDocente" permiso="docente-admin-proyectos" />
        <x-panel.navbar-horizontal.item titulo="Firmas Pendientes" ruta="RechazadoProyectosDocente" permiso="docente-admin-proyectos" />
    </x-panel.navbar-horizontal.navbar-horizontal>
    {{ $this->table }}
</div>
