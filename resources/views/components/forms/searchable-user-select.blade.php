@props([
    'model',
    'options' => [],
    'selected' => null,
    'placeholder' => 'Buscar y seleccionar usuario...',
    'wireKey' => null,
])

@php
    $normalizedOptions = collect($options)->values()->all();
    $componentKey = $wireKey ?: 'searchable-user-'.md5($model.json_encode($normalizedOptions).$selected);
@endphp

<div
    wire:key="{{ $componentKey }}"
    x-data="{
        open: false,
        search: '',
        selected: @js(filled($selected) ? (string) $selected : ''),
        options: @js($normalizedOptions),
        normalize(value) {
            return String(value ?? '')
                .normalize('NFD')
                .replace(/[\u0300-\u036f]/g, '')
                .toLowerCase();
        },
        get selectedOption() {
            return this.options.find(option => String(option.id) === String(this.selected));
        },
        get filteredOptions() {
            const term = this.normalize(this.search.trim());

            if (! term) {
                return this.options;
            }

            return this.options.filter(option => this.normalize(`${option.nombre} ${option.email}`).includes(term));
        },
        choose(option) {
            this.selected = String(option.id);
            this.open = false;
            this.search = '';
            $wire.set(@js($model), option.id, true);
        },
        clearSelection() {
            this.selected = '';
            this.search = '';
            $wire.set(@js($model), null, true);
        },
    }"
    @click.outside="open = false"
    @keydown.escape.window="open = false"
    class="relative"
>
    <button
        type="button"
        @click="open = !open; if (open) { $nextTick(() => $refs.searchInput?.focus()) }"
        :aria-expanded="open"
        aria-haspopup="listbox"
        class="flex w-full items-center justify-between gap-3 rounded-md border border-gray-300 bg-white px-3 py-2 text-left text-sm text-gray-900 shadow-sm transition hover:border-blue-400 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/20 dark:border-gray-600 dark:bg-gray-800 dark:text-white"
    >
        <span class="min-w-0 truncate" x-text="selectedOption ? `${selectedOption.nombre} — ${selectedOption.email}` : @js($placeholder)"></span>
        <span class="material-symbols-outlined shrink-0 text-[20px] text-gray-400" x-text="open ? 'expand_less' : 'expand_more'"></span>
    </button>

    <div
        x-cloak
        x-show="open"
        x-transition.origin.top
        class="absolute z-[70] mt-2 w-full overflow-hidden rounded-lg border border-gray-200 bg-white shadow-xl dark:border-gray-700 dark:bg-gray-800"
    >
        <div class="border-b border-gray-200 p-2 dark:border-gray-700">
            <div class="relative">
                <span class="material-symbols-outlined pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-[19px] text-gray-400">search</span>
                <input
                    x-ref="searchInput"
                    x-model="search"
                    type="search"
                    placeholder="Buscar por nombre o correo..."
                    class="w-full rounded-md border border-gray-300 bg-white py-2 pl-10 pr-3 text-sm text-gray-900 focus:border-blue-500 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-900 dark:text-white"
                />
            </div>
        </div>

        <div role="listbox" class="max-h-64 overflow-y-auto p-1">
            <button
                x-show="selected"
                type="button"
                @click="clearSelection(); open = false"
                class="flex w-full items-center gap-2 rounded-md px-3 py-2 text-left text-sm text-gray-500 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-700"
            >
                <span class="material-symbols-outlined text-[18px]">close</span>
                Quitar selección
            </button>

            <template x-for="option in filteredOptions" :key="option.id">
                <button
                    type="button"
                    role="option"
                    @click="choose(option)"
                    :aria-selected="String(option.id) === String(selected)"
                    :class="String(option.id) === String(selected) ? 'bg-blue-50 text-blue-800 dark:bg-blue-950/40 dark:text-blue-200' : 'text-gray-800 hover:bg-gray-100 dark:text-gray-100 dark:hover:bg-gray-700'"
                    class="flex w-full items-start justify-between gap-3 rounded-md px-3 py-2 text-left"
                >
                    <span class="min-w-0">
                        <span class="block truncate text-sm font-medium" x-text="option.nombre"></span>
                        <span class="block truncate text-xs text-gray-500 dark:text-gray-400" x-text="option.email"></span>
                    </span>
                    <span x-show="String(option.id) === String(selected)" class="material-symbols-outlined shrink-0 text-[19px] text-blue-600">check</span>
                </button>
            </template>

            <p x-show="filteredOptions.length === 0" class="px-3 py-5 text-center text-sm text-gray-500 dark:text-gray-400">
                No se encontraron usuarios con ese nombre o correo.
            </p>
        </div>
    </div>
</div>
