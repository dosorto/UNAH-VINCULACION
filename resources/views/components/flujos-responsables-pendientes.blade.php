@can('configuracion.flujos')
    @php
        $flujosPendientes = \App\Models\Proyecto\FlujoAprobacion::query()
            ->where('proceso', 'PROYECTO')->where('activo', true)
            ->whereHas('etapas', fn ($q) => $q->pendientesDeResponsable())
            ->with(['etapas' => fn ($q) => $q->pendientesDeResponsable()])
            ->orderBy('nombre')->get();
    @endphp
    @if ($flujosPendientes->isNotEmpty())
        <aside role="status" class="m-4 rounded-lg border border-amber-300 bg-amber-50 p-4 text-sm text-amber-950 dark:border-amber-700 dark:bg-amber-950 dark:text-amber-100">
            <p class="font-semibold">Configure los responsables de los flujos</p>
            <p>Hay etapas pendientes de configurar. Asigne sus responsables para habilitar los nuevos envíos y las adaptaciones que las necesiten.</p>
            <ul class="my-2 list-inside list-disc">
                @foreach ($flujosPendientes as $flujoPendiente)
                    <li>{{ $flujoPendiente->nombre }}: {{ $flujoPendiente->etapas->pluck('nombre')->implode(', ') }}.</li>
                @endforeach
            </ul>
            <a href="{{ route('configuracion.flujos.proyectos') }}" class="font-semibold underline">Configurar responsables</a>
        </aside>
    @endif
@endcan
