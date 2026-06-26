@props([
    'titulo'         => 'titulo',
    'icono'          => 'heroicon-o-home',
    'routes'         => [],
    'notificaciones' => 0,
    'route'          => 'home',
    'class'          => '',
    'permisos'       => [],
    'parametro'      => '',
    'children'       => [],
    'funcion'        => null,
    'DataNavBar'     => null,
])

@if (auth()->user()->activeRole && auth()->user()->activeRole->hasAnyPermission($permisos))
    @php
        $isActive    = in_array(request()->route()->getName(), $routes);
        $hasChildren = !empty($children);
        $componentId = 'sidebar-' . str_replace('.', '-', $route);
    @endphp

    {{--
      x-data:
        open        → estado del accordion (expandido)
        isActive    → ítem activo según la ruta actual
        hovering    → controla la visibilidad del flyout
        leaveTimer  → timeout para evitar parpadeo al cruzar el gap icono→flyout
        flyoutY     → Y relativa al sidebar (containing block de position:fixed por transform)
        flyoutH     → altura del item (para centrar el tooltip sin hijos)
        sidebarRight→ ancho del sidebar + gap (siempre igual cuando colapsado)

      NOTA DE POSICIONAMIENTO:
        El sidebar tiene transform:translateX(0) → se convierte en containing block
        de sus descendientes con position:fixed.
        Por tanto las coordenadas del flyout deben ser RELATIVAS al sidebar,
        no al viewport. Se calcula: item.top - sidebar.top (ambas en viewport),
        lo que da la posición correcta dentro del sistema de coordenadas del sidebar.

      desktopSidebarOpen se hereda del scope Alpine padre (base.blade.php).
    --}}
    <div
        x-data="{
            open: localStorage.getItem('{{ $componentId }}') === 'true' || false,
            isActive: {{ $isActive ? 'true' : 'false' }},
            hovering: false,
            leaveTimer: null,
            flyoutY: 0,
            flyoutH: 40,
            sidebarRight: 0,
            toggleMenu() {
                this.open = !this.open;
                localStorage.setItem('{{ $componentId }}', this.open);
            },
            updateFlyoutPos(el) {
                const r  = el.getBoundingClientRect();
                const sb = document.getElementById('mobile-menu');
                const sr = sb ? sb.getBoundingClientRect() : { top: 0, left: 0, width: 64 };
                // Coordenadas relativas al sidebar (su containing block por transform)
                this.flyoutY      = r.top  - sr.top;
                this.flyoutH      = r.height;
                this.sidebarRight = sr.width + 8;
            },
            openFlyout(el) {
                clearTimeout(this.leaveTimer);
                this.hovering = true;
                this.updateFlyoutPos(el);
            },
            closeFlyout() {
                this.leaveTimer = setTimeout(() => { this.hovering = false; }, 120);
            }
        }"
        class="relative mt-1"
        @mouseenter="openFlyout($el)"
        @mouseleave="closeFlyout()">

        @if ($hasChildren)
            {{-- ══════════════════════════════════════════════════════
                 CON CHILDREN
                 Expandido   → accordion (botón + lista plegable)
                 Colapsado   → solo icono + flyout al hover
            ══════════════════════════════════════════════════════ --}}

            {{-- Botón accordion / icono colapsado --}}
            <button
                @click="desktopSidebarOpen ? toggleMenu() : null"
                :class="isActive
                    ? 'text-primary-600 dark:text-primary-400 bg-gray-200 dark:bg-white/5 cursor-default'
                    : 'hover:bg-gray-200 dark:hover:bg-white/5'"
                class="w-full flex items-center rounded-md transition-colors duration-150 ease-in-out
                       dark:text-gray-200 {{ $class }}
                       py-2 px-4 justify-between
                       sm:group-data-[sb=closed]/sb:justify-center sm:group-data-[sb=closed]/sb:px-0"
                :aria-expanded="open"
                :aria-label="'{{ addslashes($titulo) }}'"
                :title="desktopSidebarOpen ? null : '{{ addslashes($titulo) }}'">

                <div class="flex items-center min-w-0">
                    <div class="shrink-0">
                        @if ($isActive)
                            @svg($icono, ['class' => 'fi-sidebar-item-icon h-6 w-6 text-primary-600 dark:text-primary-400'])
                        @else
                            @svg($icono, ['class' => 'fi-sidebar-item-icon h-6 w-6 text-gray-400 dark:text-gray-400'])
                        @endif
                    </div>
                    <span class="ml-4 truncate sm:group-data-[sb=closed]/sb:hidden">{{ $titulo }}</span>
                </div>

                <div class="shrink-0 sm:group-data-[sb=closed]/sb:hidden">
                    @svg('heroicon-o-chevron-down', [
                        'class'        => 'h-5 w-5 text-gray-400 dark:text-gray-400 transition-transform duration-200',
                        'x-bind:class' => "open ? 'rotate-180' : ''",
                    ])
                </div>
            </button>

            {{-- Lista de subopciones (accordion, solo cuando expandido) --}}
            <div
                x-show="open && desktopSidebarOpen"
                x-transition:enter="transition ease-out duration-200"
                x-transition:enter-start="opacity-0 -translate-y-2"
                x-transition:enter-end="opacity-100 translate-y-0"
                x-transition:leave="transition ease-in duration-150"
                x-transition:leave-start="opacity-100 translate-y-0"
                x-transition:leave-end="opacity-0 -translate-y-2"
                class="pl-10 mt-1 space-y-1">
                @foreach ($children as $child)
                    @if (auth()->user()->activeRole && auth()->user()->activeRole->hasPermissionTo($child['permiso']))
                        @php
                            $childIsActive     = request()->route()->getName() === $child['route'];
                            $resultado_funcion = 0;
                            if (isset($child['funcion']) && isset($DataNavBar) && auth()->user()->hasPermissionTo($child['permiso'])) {
                                $resultado_funcion = App\Clases\DataNavBar::{$child['funcion']}();
                            }
                        @endphp
                        <a href="{{ route($child['route'], $child['parametro'] ?? '') }}" wire:navigate.hover
                            class="flex items-center py-2 px-4 rounded-md transition-colors duration-150 ease-in-out dark:text-gray-200
                            {{ $childIsActive ? 'text-primary-600 dark:text-primary-400 bg-gray-200 dark:bg-white/5' : 'hover:bg-gray-200 dark:hover:bg-white/5' }}">
                            <span>{{ $child['texto'] }}</span>

                            @if (isset($child['funcion']) && isset($DataNavBar) && $resultado_funcion > 0)
                                <span
                                    class="ml-auto bg-gradient-to-r from-indigo-500 to-pink-500 text-white px-4 py-1 rounded-full text-xs font-semibold transition-transform transform hover:scale-105 hover:shadow-xl focus:outline-none focus:ring-4 focus:ring-pink-500 focus:ring-opacity-60">
                                    {{ $resultado_funcion }}
                                </span>
                            @endif
                        </a>
                    @endif
                @endforeach
            </div>

            {{--
              FLYOUT (solo cuando sidebar colapsado)
              position:fixed calculado con getBoundingClientRect → escapa overflow-y-auto del scroll.
              Timer de 120 ms evita parpadeo al cruzar el gap entre icono y flyout.
            --}}
            <div
                x-show="!desktopSidebarOpen && hovering"
                @mouseenter="clearTimeout(leaveTimer)"
                @mouseleave="closeFlyout()"
                x-transition:enter="transition ease-out duration-150"
                x-transition:enter-start="opacity-0 translate-x-2"
                x-transition:enter-end="opacity-100 translate-x-0"
                x-transition:leave="transition ease-in duration-100"
                x-transition:leave-start="opacity-100 translate-x-0"
                x-transition:leave-end="opacity-0 translate-x-2"
                :style="{ position: 'fixed', top: flyoutY + 'px', left: sidebarRight + 'px', zIndex: '200' }"
                class="w-52 rounded-xl bg-white border border-gray-200 shadow-xl overflow-hidden
                       dark:bg-gray-900 dark:border-gray-700">

                {{-- Cabecera del módulo --}}
                <div class="flex items-center gap-2.5 px-4 py-2.5
                            bg-gray-50 dark:bg-gray-800
                            border-b border-gray-100 dark:border-gray-700">
                    @if ($isActive)
                        @svg($icono, ['class' => 'h-4 w-4 shrink-0 text-primary-600 dark:text-primary-400'])
                    @else
                        @svg($icono, ['class' => 'h-4 w-4 shrink-0 text-gray-500 dark:text-gray-400'])
                    @endif
                    <p class="text-sm font-semibold text-gray-800 dark:text-gray-200 truncate">{{ $titulo }}</p>
                </div>

                {{-- Subopciones --}}
                <div class="py-1 max-h-72 overflow-y-auto">
                    @foreach ($children as $child)
                        @if (auth()->user()->activeRole && auth()->user()->activeRole->hasPermissionTo($child['permiso']))
                            @php $childIsActive = request()->route()->getName() === $child['route']; @endphp
                            <a href="{{ route($child['route'], $child['parametro'] ?? '') }}" wire:navigate.hover
                                class="flex items-center gap-2 px-4 py-2 text-sm transition-colors duration-150
                                       {{ $childIsActive
                                           ? 'text-primary-600 dark:text-primary-400 bg-primary-50 dark:bg-primary-900/20 font-medium'
                                           : 'text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-white/5' }}">
                                <svg class="h-2.5 w-2.5 shrink-0 {{ $childIsActive ? 'text-primary-500' : 'text-gray-300 dark:text-gray-600' }}"
                                     fill="currentColor" viewBox="0 0 8 8">
                                    <circle cx="4" cy="4" r="3"/>
                                </svg>
                                <span class="truncate">{{ $child['texto'] }}</span>
                            </a>
                        @endif
                    @endforeach
                </div>
            </div>

        @else
            {{-- ══════════════════════════════════════════════════════
                 SIN CHILDREN
                 Expandido  → enlace normal con texto
                 Colapsado  → solo icono + tooltip al hover
            ══════════════════════════════════════════════════════ --}}

            <a href="{{ route($route, $parametro) }}" wire:navigate.hover
                :title="desktopSidebarOpen ? null : '{{ addslashes($titulo) }}'"
                aria-label="{{ $titulo }}"
                class="flex items-center rounded-md transition-colors duration-150 ease-in-out dark:text-gray-200 {{ $class }}
                       py-2 px-4
                       sm:group-data-[sb=closed]/sb:justify-center sm:group-data-[sb=closed]/sb:px-0
                       {{ $isActive
                           ? 'text-primary-600 dark:text-primary-400 bg-gray-200 dark:bg-white/5 cursor-default pointer-events-none'
                           : 'hover:bg-gray-200 dark:hover:bg-white/5' }}">
                <div class="shrink-0">
                    @if ($isActive)
                        @svg($icono, ['class' => 'fi-sidebar-item-icon h-6 w-6 text-primary-600 dark:text-primary-400'])
                    @else
                        @svg($icono, ['class' => 'fi-sidebar-item-icon h-6 w-6 text-gray-400 dark:text-gray-400'])
                    @endif
                </div>
                <span class="ml-4 truncate sm:group-data-[sb=closed]/sb:hidden">{{ $titulo }}</span>
                @if ($notificaciones > 0)
                    <span class="ml-auto sm:group-data-[sb=closed]/sb:hidden
                                 bg-primary-100 text-primary-600 dark:bg-primary-900 dark:text-primary-400
                                 px-2 py-0.5 rounded-full text-xs">
                        {{ $notificaciones }}
                    </span>
                @endif
            </a>

            {{-- Tooltip flyout (solo cuando colapsado) --}}
            <div
                x-show="!desktopSidebarOpen && hovering"
                @mouseenter="clearTimeout(leaveTimer)"
                @mouseleave="closeFlyout()"
                x-transition:enter="transition ease-out duration-150"
                x-transition:enter-start="opacity-0 translate-x-2"
                x-transition:enter-end="opacity-100 translate-x-0"
                x-transition:leave="transition ease-in duration-100"
                x-transition:leave-start="opacity-100 translate-x-0"
                x-transition:leave-end="opacity-0 translate-x-2"
                :style="{ position: 'fixed', top: (flyoutY + 4) + 'px', left: sidebarRight + 'px', zIndex: '200' }"
                class="flex items-center gap-2 whitespace-nowrap rounded-lg
                       bg-white border border-gray-200 shadow-md px-3 py-1.5
                       dark:bg-gray-900 dark:border-gray-700"
                style="pointer-events: none;">
                <span class="text-sm font-medium text-gray-700 dark:text-gray-200">{{ $titulo }}</span>
                @if ($notificaciones > 0)
                    <span class="bg-primary-100 text-primary-600 dark:bg-primary-900 dark:text-primary-400
                                 px-1.5 py-0.5 rounded-full text-xs">
                        {{ $notificaciones }}
                    </span>
                @endif
            </div>
        @endif

    </div>
@endif
