@props([
    'model',
    'id' => 'image-dropzone',
    'accept' => '.jpg,.jpeg,.png,.webp',
])

<div
    x-data="{
        model: @js($model),
        dragging: false,
        uploading: false,
        progress: 0,
        previews: [],
        select(files) {
            this.clearPreviews();
            this.previews = Array.from(files).map((file) => ({
                file,
                name: file.name,
                size: file.size,
                url: file.type.startsWith('image/') ? URL.createObjectURL(file) : null,
            }));
        },
        drop(files) {
            const transfer = new DataTransfer();
            Array.from(files).forEach((file) => transfer.items.add(file));
            this.$refs.input.files = transfer.files;
            this.select(transfer.files);
            this.$refs.input.dispatchEvent(new Event('change', { bubbles: true }));
        },
        remove(index, wire) {
            const transfer = new DataTransfer();
            Array.from(this.$refs.input.files).forEach((file, current) => {
                if (current !== index) transfer.items.add(file);
            });
            if (this.previews[index]?.url) URL.revokeObjectURL(this.previews[index].url);
            this.previews.splice(index, 1);
            wire.cancelUpload(this.model);
            this.$refs.input.files = transfer.files;
            if (transfer.files.length) setTimeout(() => this.$refs.input.dispatchEvent(new Event('change', { bubbles: true })), 0);
        },
        clearPreviews() {
            this.previews.forEach((preview) => preview.url && URL.revokeObjectURL(preview.url));
            this.previews = [];
        },
        formatSize(bytes) {
            return bytes >= 1048576 ? (bytes / 1048576).toFixed(1) + ' MB' : (bytes / 1024).toFixed(1) + ' KB';
        },
    }"
    x-on:livewire-upload-start="uploading = true; progress = 0"
    x-on:livewire-upload-progress="progress = $event.detail.progress"
    x-on:livewire-upload-finish="uploading = false; progress = 100"
    x-on:livewire-upload-error="uploading = false"
    x-on:fotografias-guardadas.window="clearPreviews(); uploading = false; progress = 100; $refs.input.value = ''"
    class="w-full"
>
    <label
        for="{{ $id }}"
        tabindex="0"
        role="button"
        x-on:keydown.enter.prevent="$refs.input.click()"
        x-on:keydown.space.prevent="$refs.input.click()"
        x-on:dragenter.prevent="dragging = true"
        x-on:dragover.prevent="dragging = true"
        x-on:dragleave.prevent="dragging = false"
        x-on:drop.prevent="dragging = false; drop($event.dataTransfer.files)"
        x-bind:class="dragging || uploading ? 'border-blue-500 bg-blue-50 dark:bg-blue-950/30' : 'border-gray-300 bg-gray-50 hover:border-blue-400 hover:bg-blue-50/50 dark:border-gray-600 dark:bg-gray-800/50'"
        @class([
            'flex min-h-52 w-full cursor-pointer flex-col items-center justify-center rounded-xl border-2 border-dashed px-6 py-8 text-center transition focus:outline-none focus-visible:ring-2 focus-visible:ring-blue-500 focus-visible:ring-offset-2',
            'border-red-400 bg-red-50 dark:border-red-700 dark:bg-red-950/20' => $errors->has($model) || $errors->has($model.'.*'),
        ])
    >
        <input
            id="{{ $id }}"
            x-ref="input"
            type="file"
            wire:model="{{ $model }}"
            multiple
            accept="{{ $accept }}"
            x-on:change="select($event.target.files)"
            class="sr-only"
            aria-describedby="{{ $id }}-help {{ $id }}-errors"
        >
        <svg class="mb-3 h-10 w-10 text-blue-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 16V4m0 0L7.5 8.5M12 4l4.5 4.5M5 14.5v3A2.5 2.5 0 007.5 20h9a2.5 2.5 0 002.5-2.5v-3"/></svg>
        <p class="text-base font-semibold text-gray-900 dark:text-white" x-text="dragging ? 'Suelta las fotografías para cargarlas' : 'Arrastra y suelta las fotografías aquí'"></p>
        <p class="mt-1 text-sm text-gray-600 dark:text-gray-300">o haz clic para seleccionarlas</p>
        <span class="mt-4 rounded-md bg-white px-3 py-2 text-sm font-medium text-blue-700 shadow-sm ring-1 ring-gray-200 dark:bg-gray-900 dark:text-blue-300 dark:ring-gray-700">Seleccionar fotografías</span>
        <p id="{{ $id }}-help" class="mt-4 text-xs text-gray-500">JPG, JPEG, PNG o WEBP. Máximo 10 MB por fotografía y 20 por informe.</p>
    </label>

    <div x-show="uploading" x-cloak class="mt-3" aria-live="polite">
        <div class="flex justify-between text-xs text-blue-700"><span>Cargando fotografías…</span><span x-text="progress + '%'">0%</span></div>
        <div class="mt-1 h-2 overflow-hidden rounded-full bg-gray-200"><div class="h-full bg-blue-600 transition-all" x-bind:style="'width: ' + progress + '%'" role="progressbar" x-bind:aria-valuenow="progress" aria-valuemin="0" aria-valuemax="100"></div></div>
    </div>

    <div x-show="previews.length" x-cloak class="mt-4 grid gap-3 sm:grid-cols-2 lg:grid-cols-4" aria-label="Fotografías seleccionadas">
        <template x-for="(preview, index) in previews" :key="preview.name + index">
            <article class="overflow-hidden rounded-lg border border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-900">
                <img x-show="preview.url" x-bind:src="preview.url" x-bind:alt="'Previsualización de ' + preview.name" class="h-28 w-full object-cover">
                <div class="space-y-1 p-3 text-xs"><p class="truncate font-medium" x-text="preview.name"></p><p class="text-gray-500" x-text="formatSize(preview.size)"></p><div class="flex items-center justify-between"><span x-text="uploading ? 'Cargando' : 'Pendiente'" class="font-medium text-blue-700"></span><button type="button" x-on:click.prevent.stop="remove(index, $wire)" class="text-red-600 focus:outline-none focus-visible:ring-2 focus-visible:ring-red-500">Quitar</button></div></div>
            </article>
        </template>
    </div>

    <div id="{{ $id }}-errors" class="mt-3 space-y-1" role="alert" aria-live="assertive">
        @error($model)<p class="text-sm text-red-600">{{ $message }}</p>@enderror
        @foreach($errors->get($model.'.*') as $messages)
            @foreach($messages as $message)<p class="text-sm text-red-600">{{ $message }}</p>@endforeach
        @endforeach
    </div>
</div>
