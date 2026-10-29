<div class="space-y-4 md:col-span-2">
    @php
        $anexosPasantia = collect(\App\Livewire\Proyectos\Vinculacion\CreatePasantia::TIPOS_ANEXO)
            ->map(fn ($nombre, $tipo) => $form['archivo_'.$tipo] ?? null)->filter()->all();
    @endphp
    <p class="text-sm text-gray-500 dark:text-gray-400">Los anexos son opcionales. Puede guardar un archivo por tipo; agregar otro del mismo tipo reemplaza el anterior.</p>
    <div class="flex items-center justify-between gap-4">
        <h4 class="text-sm font-medium text-gray-700 dark:text-gray-300">Anexos guardados ({{ count($anexosPasantia) }})</h4>
        <button wire:click="openAnexoModal" type="button"
            class="inline-flex items-center px-3 py-1.5 text-xs font-medium rounded-md bg-blue-600 text-white hover:bg-blue-700">
            + Agregar anexo
        </button>
    </div>

    <div class="overflow-x-auto rounded-lg border border-gray-200 dark:border-gray-700">
        <table class="min-w-full divide-y divide-gray-200 text-sm dark:divide-gray-700">
            <thead class="bg-gray-50 dark:bg-gray-800">
                <tr>
                    <th class="w-16 px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-600 dark:text-gray-300">No.</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-600 dark:text-gray-300">Nombre</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-600 dark:text-gray-300">Tipo</th>
                    <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-gray-600 dark:text-gray-300">Acciones</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 bg-white dark:divide-gray-700 dark:bg-gray-900">
                @forelse($anexosPasantia as $tipo => $ruta)
                    <tr wire:key="anexo-guardado-{{ $tipo }}">
                        <td class="px-4 py-3 text-gray-600 dark:text-gray-300">{{ $loop->iteration }}</td>
                        <td class="px-4 py-3 text-gray-900 dark:text-white">
                            <span class="block max-w-md truncate" title="{{ basename($ruta) }}">
                                {{ basename($ruta) }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-gray-700 dark:text-gray-300">
                            <span>{{ \App\Livewire\Proyectos\Vinculacion\CreatePasantia::TIPOS_ANEXO[$tipo] }}</span>

                        </td>
                        <td class="px-4 py-3 text-right whitespace-nowrap">
                            <a href="{{ Storage::disk('public')->url($ruta) }}" target="_blank" rel="noopener" class="text-xs text-blue-600 hover:text-blue-800">Ver</a>
                            <button type="button" x-on:click.prevent="confirmDialog('¿Eliminar este anexo?', { type: 'danger' }).then((ok) => ok && $wire.deleteAnexo('{{ $tipo }}'))" class="ml-3 text-xs text-red-600 hover:text-red-800">Eliminar</button>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="px-4 py-8 text-center text-sm text-gray-500 dark:text-gray-400">No hay anexos agregados.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($showAnexoModal)

    <div class="fixed inset-0 z-50 overflow-y-auto" role="dialog" aria-modal="true">
        <div class="fixed inset-0 bg-black/50"></div>
        <div class="relative flex min-h-full items-center justify-center p-4">
            <div class="relative w-full max-w-xl rounded-lg bg-white dark:bg-gray-900 shadow-xl border border-gray-200 dark:border-gray-700">
                <div class="flex items-center justify-between border-b border-gray-200 dark:border-gray-700 px-5 py-3">
                    <h4 class="text-sm font-semibold text-gray-900 dark:text-white">Agregar anexo</h4>
                    <button wire:click="closeAnexoModal" type="button" class="text-gray-500 hover:text-gray-800 dark:hover:text-gray-200 text-lg leading-none">✕</button>
                </div>

                <div class="p-5 space-y-4">
                    <div>
                        <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Tipo de documento <span class="text-red-500">*</span></label>
                        <select aria-label="Tipo de documento" wire:model.live="nuevoAnexoTipo" class="w-full rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 px-3 py-2 text-sm focus:border-blue-500">
                            <option value="">Seleccione el tipo de documento</option>
                            @foreach(\App\Livewire\Proyectos\Vinculacion\CreatePasantia::TIPOS_ANEXO as $valor => $nombre)
                                <option value="{{ $valor }}">{{ $nombre }}</option>
                            @endforeach
                        </select>
                        @error('nuevoAnexoTipo') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>



                    <div>
                        <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Archivo <span class="text-red-500">*</span></label>
                        <div class="relative flex min-h-40 items-center justify-center rounded-lg border-2 border-dashed border-gray-300 dark:border-gray-600 bg-gray-50 dark:bg-gray-800/50 p-6 text-center hover:border-blue-400">
                            <input aria-label="Archivo del anexo" type="file" wire:model="nuevoAnexoArchivo"
                                accept=".pdf,.doc,.docx,.jpg,.jpeg,.png"
                                class="absolute inset-0 h-full w-full cursor-pointer opacity-0" />
                            <div class="pointer-events-none">
                                <p class="text-sm font-medium text-gray-700 dark:text-gray-200">Seleccione o suelte su documento aquí</p>
                                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">PDF, Word o imagen · máximo 10 MB por archivo</p>
                                <p wire:loading wire:target="nuevoAnexoArchivo" class="mt-2 text-xs font-medium text-blue-600">Cargando archivos...</p>
                            </div>
                        </div>
                        @error('nuevoAnexoArchivo') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror

                    </div>

                    @if(!empty($nuevoAnexoArchivo))
                        <div class="space-y-1.5">

                                <div class="flex items-center justify-between rounded bg-blue-50 dark:bg-blue-900/20 px-3 py-2 text-sm">
                                    <span class="max-w-sm truncate text-gray-700 dark:text-gray-300">{{ $nuevoAnexoArchivo->getClientOriginalName() }}</span>
                                    <button wire:click="quitarArchivoPendiente" class="ml-3 shrink-0 text-xs text-red-600 hover:text-red-800">Quitar</button>
                                </div>

                        </div>
                    @endif
                </div>

                <div class="flex justify-end gap-2 border-t border-gray-200 dark:border-gray-700 px-5 py-3">
                    <button wire:click="closeAnexoModal" type="button" class="form-action form-action--secondary" wire:loading.attr="disabled">Cancelar</button>
                    <button wire:click="uploadAnexo" type="button" wire:loading.attr="disabled" wire:target="uploadAnexo,nuevoAnexoArchivo" @disabled(empty($nuevoAnexoArchivo) || empty($nuevoAnexoTipo))
                        class="px-3 py-1.5 text-xs font-medium rounded-md bg-blue-600 text-white hover:bg-blue-700 disabled:opacity-50 disabled:cursor-not-allowed">
                        <span wire:loading.remove wire:target="uploadAnexo">Agregar</span>
                        <span wire:loading wire:target="uploadAnexo">Subiendo...</span>
                    </button>
                </div>
            </div>
        </div>
    </div>
    @endif
</div>
