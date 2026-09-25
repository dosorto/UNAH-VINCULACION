@props(['model', 'options' => [], 'selected' => null, 'disabled' => false, 'label' => 'Seleccionar opción', 'placeholder' => null])
@php
    $items = collect($options)->map(fn ($text, $value) => ['value' => (string) $value, 'text' => (string) $text])->values()->all();
    $key = 'searchable-select-'.md5($model.json_encode($items).json_encode($selected).(int) $disabled);
@endphp
<div wire:key="{{ $key }}" class="relative" x-id="['selector', 'opciones']"
    x-data="{
        open: false, search: '', active: -1,
        selected: @js((string) ($selected ?? '')),
        options: @js($items),
        normalize(value) { return String(value).normalize('NFD').replace(/[\u0300-\u036f]/g, '').toLowerCase(); },
        get filtered() { return this.options.filter(option => this.normalize(option.text).includes(this.normalize(this.search.trim()))); },
        get selectedText() { return this.options.find(option => option.value === this.selected)?.text || ''; },
        show() { if (this.open) return; this.open = true; this.search = ''; this.active = -1; },
        close() { this.open = false; this.search = ''; this.active = -1; },
        move(direction) {
            if (!this.open) this.show();
            if (!this.filtered.length) return;
            this.active = (this.active + direction + this.filtered.length) % this.filtered.length;
            this.$nextTick(() => this.$refs.list.querySelectorAll('[role=option]')[this.active]?.scrollIntoView({ block: 'nearest' }));
        },
        choose(value) { this.selected = value; this.$refs.search.focus(); this.close(); $wire.set(@js($model), value || null, true); }
    }"
    x-on:click.outside="close()" x-on:keydown.escape.stop.prevent="close()"
    x-on:focusout="if (!$el.contains($event.relatedTarget)) close()">
    <div class="relative">
        <input x-ref="search" type="text" autocomplete="off" @disabled($disabled)
            x-bind:value="open ? search : selectedText"
            x-on:focus="show()" x-on:click="show()"
            x-on:input="search = $event.target.value; open = true; active = -1"
            placeholder="{{ $placeholder ?? 'Buscar o seleccionar '.mb_strtolower($label).'...' }}"
            aria-label="Buscar: {{ $label }}" role="combobox" aria-autocomplete="list" aria-haspopup="listbox"
            x-bind:aria-expanded="open" x-bind:aria-controls="$id('opciones')"
            x-bind:aria-activedescendant="active >= 0 ? $id('selector') + '-' + active : null"
            x-on:keydown.arrow-down.prevent="move(1)" x-on:keydown.arrow-up.prevent="move(-1)"
            x-on:keydown.enter.prevent="if (open && active >= 0 && filtered[active]) choose(filtered[active].value)"
            class="w-full rounded-lg border border-gray-300 bg-white py-3 pl-3 pr-14 text-sm text-gray-900 placeholder-gray-400 shadow-sm outline-none focus:border-blue-500 focus:ring-1 focus:ring-blue-500 disabled:cursor-not-allowed disabled:bg-gray-100 disabled:opacity-60 dark:border-gray-600 dark:bg-gray-800 dark:text-white">
        <button x-cloak x-show="selected !== ''" type="button" @disabled($disabled)
            x-on:click="choose('')" aria-label="Quitar selección: {{ $label }}"
            class="absolute inset-y-0 right-7 px-1 text-gray-400 hover:text-gray-700 focus:text-blue-600 disabled:hidden dark:hover:text-gray-200">&times;</button>
        <svg class="pointer-events-none absolute right-3 top-1/2 h-3 w-3 -translate-y-1/2 text-gray-400" x-bind:class="open ? 'rotate-180' : ''" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path d="M6 8l4 4 4-4H6z"/></svg>
    </div>
    <div x-cloak x-show="open" class="absolute z-[70] mt-1 w-full overflow-hidden rounded-lg border border-gray-100 bg-white py-1 shadow-lg dark:border-gray-600 dark:bg-gray-800">
        <div role="listbox" x-bind:id="$id('opciones')" aria-label="{{ $label }}" class="max-h-64 overflow-y-auto">
            <div x-ref="list">
                <template x-for="(option, index) in filtered" :key="option.value">
                    <button type="button" tabindex="-1" role="option" x-bind:id="$id('selector') + '-' + index" x-bind:aria-selected="selected === option.value"
                        x-on:mousedown.prevent x-on:click="choose(option.value)" x-on:mouseenter="active = index"
                        class="block w-full px-3 py-3 text-left text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
                        x-bind:class="active === index || selected === option.value ? 'bg-blue-50 text-blue-800 dark:bg-blue-950 dark:text-blue-200' : 'text-gray-900 dark:text-gray-100'"
                        x-text="option.text"></button>
                </template>
            </div>
            <p x-show="filtered.length === 0" role="status" class="px-3 py-4 text-center text-sm text-gray-500 dark:text-gray-400">No se encontraron opciones.</p>
        </div>
    </div>
</div>
