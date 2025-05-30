<div>

    @if (auth()->user()->hasPermissionTo('ver-dashboard-admin') &&
            auth()->user()->activeRole->hasPermissionTo('ver-dashboard-admin'))
        @livewire('inicio.dashboards.dashboard')
    @endif

    @if (auth()->user()->hasPermissionTo('ver-dashboard-docente') &&
            auth()->user()->activeRole->hasPermissionTo('ver-dashboard-docente'))
        @livewire('inicio.dashboards.dasboard-docente')
    @endif

    @if (auth()->user()->hasPermissionTo('ver-dashboard-estudiante') &&
            auth()->user()->activeRole->hasPermissionTo('ver-dashboard-estudiante'))
        @livewire('inicio.dashboards.dashboard-estudiante')
    @endif
</div>
