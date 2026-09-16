@props([
    // RegistroFamiliasTramite::carriles()
    'carriles' => [],
    'vacio' => 'Todavía no hay trámites registrados.',
])

@if (empty($carriles))
    <x-dashboard.estado-vacio :titulo="$vacio" icono="heroicon-o-inbox" />
@else
    {{--
        Un carril por familia, con solo las fases que esa familia recorre.

        Un único recorrido común daría por incompleta una práctica profesional
        que ya terminó, porque nunca va a tener informes. Aquí cada trámite se
        mide contra su propio itinerario.
    --}}
    <div {{ $attributes->merge(['class' => 'space-y-5']) }}>
        @foreach ($carriles as $carril)
            <section>
                <div class="mb-3 flex flex-wrap items-center gap-x-3 gap-y-1">
                    <h3 class="text-xs font-bold uppercase tracking-[0.14em] text-slate-600 dark:text-slate-300">
                        {{ $carril['etiqueta'] }}
                    </h3>
                    <span class="rounded-full bg-slate-100 px-2 py-0.5 text-[11px] font-bold tabular-nums text-slate-600 dark:bg-slate-800 dark:text-slate-300">
                        {{ number_format($carril['total']) }}
                    </span>

                    @if ($carril['sin_iniciar'] > 0)
                        <span class="text-[11px] text-slate-400 dark:text-slate-500">
                            {{ number_format($carril['sin_iniciar']) }} en borrador
                        </span>
                    @endif

                    @if ($carril['ambito_parcial'] ?? false)
                        {{-- Esta familia no sabe acotarse al centro; decirlo evita
                             que la cifra se lea como si fuera de la unidad. --}}
                        <span class="inline-flex items-center gap-1 rounded-full bg-amber-50 px-2 py-0.5 text-[10px] font-semibold text-amber-700 dark:bg-amber-950 dark:text-amber-300"
                            title="Este trámite no guarda la unidad académica como dato enlazado, así que la cifra es de toda la UNAH.">
                            @svg('heroicon-o-information-circle', ['class' => 'h-3 w-3'])
                            cifra institucional
                        </span>
                    @endif
                </div>

                <x-dashboard.proceso-etapas
                    :etapas="$carril['fases']"
                    :total="max(1, $carril['total'] - $carril['sin_iniciar'])"
                />
            </section>
        @endforeach
    </div>
@endif
