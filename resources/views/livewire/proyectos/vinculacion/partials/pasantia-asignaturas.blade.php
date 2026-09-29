<div class="md:col-span-2" x-data="{ abierto: false }">
    <div class="mb-3 flex flex-wrap items-center justify-between gap-3">
        <h4 class="text-sm font-medium text-gray-700 dark:text-gray-200">Asignaturas que se aplicarán en la pasantía</h4>
        <button type="button" x-on:click="abierto = true" class="inline-flex items-center rounded-md bg-blue-600 px-3 py-2 text-sm font-medium text-white hover:bg-blue-700">Seleccionar asignaturas</button>
    </div>
    <div class="overflow-x-auto rounded-lg border border-gray-200 dark:border-gray-700">
        <table class="w-full text-left text-sm">
            <thead class="bg-slate-50 text-gray-600 dark:bg-gray-800 dark:text-gray-300"><tr><th scope="col" class="px-4 py-3">Código</th><th scope="col" class="px-4 py-3">Nombre</th><th scope="col" class="px-4 py-3"><span class="sr-only">Acciones</span></th></tr></thead>
            <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                @forelse($this->asignaturasSeleccionadas() as $indice => $asignatura)
                    <tr wire:key="asignatura-seleccionada-{{ $indice }}"><td class="px-4 py-3 font-medium">{{ $asignatura['codigo'] ?? '' }}</td><td class="px-4 py-3">{{ $asignatura['nombre'] ?? '' }}</td><td class="px-4 py-3 text-right"><button type="button" wire:click="quitarAsignatura({{ $indice }})" wire:loading.attr="disabled" class="text-sm font-medium text-red-600 hover:underline" aria-label="Quitar {{ $asignatura['nombre'] ?? 'asignatura' }}">Quitar</button></td></tr>
                @empty
                    <tr><td colspan="3" class="px-4 py-6 text-center text-gray-500">Seleccione las asignaturas que aplicará durante la pasantía.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @error('form.asignaturas')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror

    <div x-cloak x-show="abierto" x-on:keydown.escape.window="abierto = false" x-trap.inert.noscroll="abierto" class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4" x-on:click.self="abierto = false">
        <section role="dialog" aria-modal="true" aria-labelledby="modal-asignaturas-titulo" class="flex max-h-[85vh] w-full max-w-3xl flex-col rounded-xl bg-white shadow-xl dark:bg-gray-900">
            <div class="flex items-start justify-between gap-4 border-b border-gray-200 p-5 dark:border-gray-700">
                <div><h3 id="modal-asignaturas-titulo" class="text-lg font-semibold">Asignaturas que se aplicarán en la pasantía</h3><p class="mt-1 text-sm text-gray-500">Busque en el catálogo y agregue las asignaturas correspondientes.</p></div>
                <button type="button" x-on:click="abierto = false" aria-label="Cerrar" class="rounded-lg px-3 py-1 text-xl text-gray-500 hover:bg-gray-100 dark:hover:bg-gray-800">&times;</button>
            </div>
            <div class="p-5">
                <label for="buscar-asignatura" class="mb-2 block text-sm font-medium">Buscar por código o nombre</label>
                <input id="buscar-asignatura" type="search" wire:model.live.debounce.300ms="busquedaAsignatura" class="{{ $inputClass }}" placeholder="Escriba el código o nombre de la asignatura">
                <p class="mt-2 text-xs text-gray-500">Las coincidencias aparecen mientras escribe. Se muestran hasta 50 resultados del catálogo general.</p>
                <p wire:loading wire:target="busquedaAsignatura" class="mt-2 text-xs text-gray-500" role="status">Buscando asignaturas…</p>
            </div>
            <div class="overflow-y-auto px-5" aria-live="polite">
                <table class="w-full text-left text-sm">
                    <thead class="sticky top-0 bg-slate-50 text-gray-600 dark:bg-gray-800 dark:text-gray-300"><tr><th scope="col" class="px-3 py-3">Código</th><th scope="col" class="px-3 py-3">Nombre</th><th scope="col" class="px-3 py-3"><span class="sr-only">Selección</span></th></tr></thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                        @forelse($catalogoAsignaturas as $asignatura)
                            <tr wire:key="catalogo-asignatura-{{ $asignatura->id }}">
                                <td class="px-3 py-3 font-medium">{{ $asignatura->codigo }}</td><td class="px-3 py-3">{{ $asignatura->nombre }}</td>
                                <td class="px-3 py-3 text-right">
                                    @if(collect($this->asignaturasSeleccionadas())->contains('codigo', $asignatura->codigo))
                                        <span class="text-xs font-semibold text-green-700 dark:text-green-400">Agregada</span>
                                    @else
                                        <button type="button" wire:click="agregarAsignatura({{ $asignatura->id }})" wire:loading.attr="disabled" class="rounded-lg bg-blue-600 px-3 py-2 text-xs font-semibold text-white hover:bg-blue-700 disabled:opacity-50" aria-label="Agregar {{ $asignatura->nombre }}">Agregar</button>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="3" class="px-3 py-8 text-center text-gray-500">{{ trim($busquedaAsignatura) === '' ? 'Escriba un código o nombre para buscar una asignatura.' : 'No se encontraron asignaturas activas con esa búsqueda.' }}</td></tr>
                        @endforelse
                    </tbody>
                </table>
                <div class="my-4 border-t border-gray-200 pt-4 dark:border-gray-700">
                    <button type="button" wire:click="$toggle('mostrarNuevaAsignatura')" class="text-sm font-semibold text-blue-600">{{ $mostrarNuevaAsignatura ? 'Cancelar registro' : '¿No encuentra la asignatura? Registrar nueva' }}</button>
                    @if($mostrarNuevaAsignatura)
                        <div class="mt-3 grid gap-3" wire:key="nueva-asignatura">
                            <div>
                                <label for="nueva-asignatura-codigo" class="mb-1 block text-sm font-medium">Código de asignatura</label>
                                <input id="nueva-asignatura-codigo" wire:model="nuevaAsignaturaCodigo" maxlength="50" class="{{ $inputClass }}" />
                                @error('nuevaAsignaturaCodigo')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                            </div>
                            <div>
                                <label for="nueva-asignatura-nombre" class="mb-1 block text-sm font-medium">Nombre de asignatura</label>
                                <input id="nueva-asignatura-nombre" wire:model="nuevaAsignaturaNombre" maxlength="255" class="{{ $inputClass }}" />
                                @error('nuevaAsignaturaNombre')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                            </div>
                            <button type="button" wire:click="crearAsignatura" wire:loading.attr="disabled" class="justify-self-start rounded-lg bg-blue-600 px-3 py-2 text-sm font-semibold text-white disabled:opacity-50">Crear y agregar</button>
                        </div>
                    @endif
                </div>
            </div>
            <div class="mt-4 flex items-center justify-between border-t border-gray-200 p-5 dark:border-gray-700">
                <span class="text-sm text-gray-500">{{ count($this->asignaturasSeleccionadas()) }} seleccionadas</span>
                <button type="button" x-on:click="abierto = false" class="rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700">Listo</button>
            </div>
        </section>
    </div>
</div>
