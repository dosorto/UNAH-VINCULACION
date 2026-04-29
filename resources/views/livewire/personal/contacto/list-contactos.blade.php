<div>
    <div class="mb-4">
        <p class="text-zinc-950 dark:text-white font-bold mb-1">Contactos</p>
        <p class="text-zinc-500 dark:text-gray-400 text-sm">Mensajes enviados mediante el formulario de contacto.</p>
    </div>
    <div class="mb-3">
        <input type="text" wire:model.live.debounce.300ms="search" placeholder="Buscar por nombre o correo..."
            class="w-full sm:w-80 rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 px-3 py-2 text-sm shadow-sm" />
    </div>
    <div class="overflow-x-auto rounded-lg border border-gray-200 dark:border-gray-700">
        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 text-sm">
            <thead class="bg-gray-50 dark:bg-gray-800">
                <tr>
                    <th class="px-4 py-3 text-left font-semibold text-gray-600 dark:text-gray-300">Nombre</th>
                    <th class="px-4 py-3 text-left font-semibold text-gray-600 dark:text-gray-300">Institución</th>
                    <th class="px-4 py-3 text-left font-semibold text-gray-600 dark:text-gray-300">Correo</th>
                    <th class="px-4 py-3 text-left font-semibold text-gray-600 dark:text-gray-300">Teléfono</th>
                    <th class="px-4 py-3 text-left font-semibold text-gray-600 dark:text-gray-300">Fecha</th>
                    <th class="px-4 py-3 text-right font-semibold text-gray-600 dark:text-gray-300">Acciones</th>
                </tr>
            </thead>
            <tbody class="bg-white dark:bg-gray-900 divide-y divide-gray-100 dark:divide-gray-800">
                @forelse ($records as $record)
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-800">
                        <td class="px-4 py-3 text-gray-900 dark:text-white">{{ $record->nombres }} {{ $record->apellidos }}</td>
                        <td class="px-4 py-3 text-gray-700 dark:text-gray-300">{{ $record->institucion }}</td>
                        <td class="px-4 py-3 text-gray-700 dark:text-gray-300">{{ $record->email }}</td>
                        <td class="px-4 py-3 text-gray-700 dark:text-gray-300">{{ $record->telefono }}</td>
                        <td class="px-4 py-3 text-gray-700 dark:text-gray-300">{{ $record->created_at->format('d/m/Y') }}</td>
                        <td class="px-4 py-3 text-right space-x-2">
                            <button wire:click="openView({{ $record->id }})"
                                class="inline-flex items-center px-2.5 py-1 text-xs font-medium text-blue-700 bg-blue-50 hover:bg-blue-100 dark:bg-blue-900/30 dark:text-blue-400 rounded-md">
                                Ver
                            </button>
                            <button wire:click="openResponder({{ $record->id }})"
                                class="inline-flex items-center px-2.5 py-1 text-xs font-medium text-green-700 bg-green-50 hover:bg-green-100 dark:bg-green-900/30 dark:text-green-400 rounded-md">
                                Responder
                            </button>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="px-4 py-6 text-center text-gray-500">No se encontraron contactos.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-4">{{ $records->links() }}</div>

    {{-- Modal Ver --}}
    @if($viewModal && $viewContacto)
        <div class="fixed inset-0 bg-black/50 flex items-center justify-center z-50" wire:click.self="$set('viewModal', false)">
            <div class="bg-white dark:bg-gray-900 rounded-lg shadow-xl w-full max-w-lg p-6 mx-4">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">{{ $viewContacto->nombres }} {{ $viewContacto->apellidos }}</h3>
                <dl class="space-y-2 text-sm">
                    <div><dt class="font-medium text-gray-500">Institución</dt><dd class="text-gray-900 dark:text-white">{{ $viewContacto->institucion }}</dd></div>
                    <div><dt class="font-medium text-gray-500">Correo</dt><dd class="text-gray-900 dark:text-white">{{ $viewContacto->email }}</dd></div>
                    <div><dt class="font-medium text-gray-500">Teléfono</dt><dd class="text-gray-900 dark:text-white">{{ $viewContacto->telefono }}</dd></div>
                    <div><dt class="font-medium text-gray-500">Mensaje</dt><dd class="text-gray-900 dark:text-white">{{ $viewContacto->mensaje }}</dd></div>
                </dl>
                <div class="flex justify-end mt-6">
                    <button wire:click="$set('viewModal', false)" class="px-4 py-2 text-sm bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-lg">Cerrar</button>
                </div>
            </div>
        </div>
    @endif

    {{-- Modal Responder --}}
    @if($respondModal && $respondContacto)
        <div class="fixed inset-0 bg-black/50 flex items-center justify-center z-50" wire:click.self="$set('respondModal', false)">
            <div class="bg-white dark:bg-gray-900 rounded-lg shadow-xl w-full max-w-lg p-6 mx-4">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-1">Responder a {{ $respondContacto->nombres }} {{ $respondContacto->apellidos }}</h3>
                <p class="text-sm text-gray-500 mb-4">Se enviará un correo a {{ $respondContacto->email }}</p>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Mensaje *</label>
                    <textarea wire:model="respuestaMensaje" rows="5"
                        class="w-full rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 px-3 py-2 text-sm"></textarea>
                    @error('respuestaMensaje') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
                </div>
                <div class="flex justify-end gap-3 mt-6">
                    <button wire:click="$set('respondModal', false)" class="px-4 py-2 text-sm bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-lg">Cancelar</button>
                    <button wire:click="responder" class="px-4 py-2 text-sm text-white bg-green-700 hover:bg-green-800 rounded-lg">Enviar</button>
                </div>
            </div>
        </div>
    @endif
</div>
