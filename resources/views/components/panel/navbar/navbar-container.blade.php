{{--
  Sidebar – solo navegación.
  Estado controlado por desktopSidebarOpen / mobileSidebarOpen del scope Alpine padre.
  - Expandido (desktopSidebarOpen=true)  → sm:w-72
  - Colapsado (desktopSidebarOpen=false) → sm:w-16 (solo iconos)
  - Mobile overlay                        → translate-x-0 / -translate-x-full
  Nota: sin overflow-hidden en el contenedor para que los flyouts fijos no queden cortados.
--}}
<div id="mobile-menu"
    x-cloak
    :class="[
        mobileSidebarOpen ? 'translate-x-0' : '-translate-x-full',
        desktopSidebarOpen
            ? 'sm:translate-x-0 sm:w-72 sm:py-4 sm:pr-2 sm:pl-4 sm:opacity-100 sm:pointer-events-auto'
            : 'sm:translate-x-0 sm:w-16 sm:py-4 sm:px-2 sm:opacity-100 sm:pointer-events-auto'
    ]"
    {{-- group/sb → variante group-data-[sb=*]/sb:* disponible en descendientes --}}
    class="group/sb fixed inset-0 bg-white shadow-lg barra dark:barra transition-all duration-200 transform
           flex flex-col py-4 pr-2 pl-4 w-3/4
           sm:shadow-none sm:sticky sm:inset-auto sm:top-14 sm:h-[calc(100vh-3.5rem)]
           sm:shrink-0 sm:bg-gray-100 dark:bg-gray-950"
    :data-sb="desktopSidebarOpen ? 'open' : 'closed'"
    style="z-index: 40;">

    <div id="fondoimagen" class="flex flex-col h-full">

        {{-- ── MOBILE: botón cerrar (solo visible en móvil) ── --}}
        <div class="flex items-center justify-between pb-3 mb-3 sm:hidden
                    border-b border-gray-200 dark:border-gray-700 shrink-0">
            <span class="text-sm font-semibold text-gray-700 dark:text-gray-300">Navegación</span>
            <button
                type="button"
                @click="mobileSidebarOpen = false"
                aria-label="Cerrar menú lateral"
                class="inline-flex h-8 w-8 items-center justify-center rounded-lg
                       border border-gray-300 bg-white text-gray-600
                       hover:bg-gray-50 hover:text-red-500
                       transition-colors duration-150
                       focus:outline-none focus:ring-2 focus:ring-gray-400
                       dark:border-gray-700 dark:bg-gray-800 dark:text-gray-400 dark:hover:bg-white/5">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>

        {{-- ── Navegación (scroll interno) ── --}}
        <div
            id="sidebar-navigation-scroll"
            x-data="{
                storageKey: 'sidebar-navigation-scroll-top',
                rememberScroll() {
                    sessionStorage.setItem(this.storageKey, this.$el.scrollTop);
                },
                restoreScroll() {
                    const scrollTop = Number(sessionStorage.getItem(this.storageKey) || 0);

                    this.$nextTick(() => {
                        this.$el.scrollTop = scrollTop;
                        requestAnimationFrame(() => this.$el.scrollTop = scrollTop);
                    });
                }
            }"
            x-init="restoreScroll()"
            @scroll.passive="rememberScroll()"
            @click.capture="rememberScroll()"
            class="overflow-y-auto scrollbar-hidden pb-4 flex-1"
            wire:scroll>
            {{ $slot }}
        </div>

    </div>
</div>
