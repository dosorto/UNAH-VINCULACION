<div>
    @can('inicio-admin-inicio')
        @livewire(\App\Livewire\Inicio\Cards\Cards::class)
    @endcan
    @can('inicio-docente-inicio')
    <h2>Estas registrado como un docente</h2>
    @endcan
</div>
