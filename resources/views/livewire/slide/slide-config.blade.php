<div>
    <div class="mb-6">
        <p class="text-zinc-950 dark:text-white font-bold mb-1">Configuración de Slides</p>
        <p class="text-zinc-500 dark:text-gray-400 font-medium text-sm">Administra las imágenes del slider de la página principal.</p>
    </div>

    {{-- Agregar nuevo slide --}}
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow border border-gray-200 dark:border-gray-700 p-4 mb-6">
        <h3 class="text-base font-semibold dark:text-white mb-3">Agregar nuevo slide</h3>
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 items-end">
            <div class="sm:col-span-2">
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Imagen</label>
                <input type="file" wire:model="newImage" accept="image/*"
                       class="w-full text-sm text-gray-700 dark:text-gray-300 file:mr-3 file:py-1.5 file:px-3 file:rounded file:border-0 file:text-sm file:font-medium file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100">
                @error('newImage') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                <div wire:loading wire:target="newImage" class="mt-1 text-sm text-gray-500">Cargando imagen...</div>
                @if ($newImage)
                    <img src="{{ $newImage->temporaryUrl() }}" class="mt-2 h-20 rounded object-cover" alt="Preview">
                @endif
            </div>
            <div>
                <label class="flex items-center gap-2 text-sm font-medium text-gray-700 dark:text-gray-300 mb-1 cursor-pointer">
                    <input type="checkbox" wire:model="newEstado" class="rounded border-gray-300 text-blue-600">
                    Activo
                </label>
                <button wire:click="addSlide()" wire:loading.attr="disabled" wire:target="addSlide"
                        class="w-full mt-1 px-4 py-2 text-sm font-medium text-white bg-yellow-500 hover:bg-yellow-600 rounded-lg disabled:opacity-50">
                    <span wire:loading.remove wire:target="addSlide">Agregar Slide</span>
                    <span wire:loading wire:target="addSlide">Agregando...</span>
                </button>
            </div>
        </div>
    </div>

    {{-- Lista de slides --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
        @forelse ($slides as $slide)
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow border border-gray-200 dark:border-gray-700 overflow-hidden">
                <img src="{{ asset('storage/' . $slide->image_url) }}" class="w-full h-40 object-cover" alt="Slide">
                <div class="p-3 flex items-center justify-between">
                    <label class="flex items-center gap-2 text-sm text-gray-700 dark:text-gray-300 cursor-pointer">
                        <input type="checkbox" wire:click="toggleEstado({{ $slide->id }})"
                               @if($slide->estado) checked @endif
                               class="rounded border-gray-300 text-blue-600">
                        {{ $slide->estado ? 'Activo' : 'Inactivo' }}
                    </label>
                    <button wire:click="deleteSlide({{ $slide->id }})"
                            wire:confirm="¿Estás seguro de eliminar este slide?"
                            class="px-3 py-1 text-sm font-medium text-white bg-red-600 hover:bg-red-700 rounded-lg">
                        Eliminar
                    </button>
                </div>
            </div>
        @empty
            <div class="col-span-3 py-8 text-center text-gray-500 dark:text-gray-400">
                No hay slides registrados.
            </div>
        @endforelse
    </div>
</div>
