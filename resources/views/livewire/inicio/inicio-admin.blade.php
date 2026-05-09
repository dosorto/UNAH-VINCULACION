<div>

    @if (auth()->user()->hasPermissionTo('dashboard.admin') &&
            auth()->user()->activeRole->hasPermissionTo('dashboard.admin'))
        @livewire('inicio.dashboards.dashboard')
    @endif

    @if (auth()->user()->hasPermissionTo('dashboard.docente') &&
            auth()->user()->activeRole->hasPermissionTo('dashboard.docente'))
        @livewire('inicio.dashboards.dasboard-docente')
    @endif

    @if (auth()->user()->hasPermissionTo('dashboard.estudiante') &&
            auth()->user()->activeRole->hasPermissionTo('dashboard.estudiante'))
        @livewire('inicio.dashboards.dashboard-estudiante')
    @endif
</div>
