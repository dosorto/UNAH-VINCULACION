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

    @if (auth()->user()->hasPermissionTo('dashboard.director') &&
            auth()->user()->activeRole->hasPermissionTo('dashboard.director'))
        @livewire('inicio.dashboards.dashboard-director')
    @endif

    @if (!auth()->user()->hasPermissionTo('dashboard.admin') &&
         !auth()->user()->hasPermissionTo('dashboard.docente') &&
         !auth()->user()->hasPermissionTo('dashboard.estudiante') &&
         !auth()->user()->hasPermissionTo('dashboard.director'))
        <div class="flex flex-col items-center justify-center py-16 text-gray-500 dark:text-gray-400">
            <svg class="w-12 h-12 mb-3 opacity-40" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
            </svg>
            <p class="text-sm font-medium">No hay panel disponible para el rol activo.</p>
        </div>
    @endif
</div>
