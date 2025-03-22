@props([
    'titulo' => 'titulo',
    'icono' => 'heroicon-o-home',
    'routes' => [],
    'notificaciones' => 0,
    'route' => 'home',
    'class' => '',
    'permisos' => [],
    'parametro' => '',
    'children' => [], // New prop for child routes
])

@if (auth()->user()->activeRole && auth()->user()->activeRole->hasAnyPermission($permisos))
    @php
        $isActive = in_array(request()->route()->getName(), $routes);
        $hasChildren = !empty($children);
        $componentId = 'sidebar-' . str_replace('.', '-', $route);
    @endphp

    <div x-data="{
        open: localStorage.getItem('{{ $componentId }}') === 'true' || false,
        isActive: {{ $isActive ? 'true' : 'false' }},
        toggleMenu() {
            this.open = !this.open;
            localStorage.setItem('{{ $componentId }}', this.open);
        }
    }" class="mt-1">
        @if ($hasChildren)
            <button @click="toggleMenu"
                :class="isActive ? 'text-primary-600 dark:text-primary-400 bg-gray-200 dark:bg-white/5 cursor-default' :
                    'hover:bg-gray-200 dark:hover:bg-white/5'"
                class="w-full flex items-center justify-between py-2 px-4 rounded-md transition-colors duration-150 ease-in-out dark:text-gray-200 {{ $class }}"
                >
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        @if ($isActive)
                            @svg($icono, ['class' => 'fi-sidebar-item-icon h-6 w-6 text-primary-600 dark:text-primary-400'])
                        @else
                            @svg($icono, ['class' => 'fi-sidebar-item-icon h-6 w-6 text-gray-400 dark:text-gray-400'])
                        @endif
                    </div>
                    <span class="ml-4">{{ $titulo }}</span>
                </div>
                <div>
                    @svg('heroicon-o-chevron-down', [
                        'class' => 'h-5 w-5 text-gray-400 dark:text-gray-400 transition-transform duration-200',
                        'x-bind:class' => "open ? 'rotate-180' : ''",
                    ])
                </div>
            </button>

            <div x-show="open" x-transition:enter="transition ease-out duration-200"
                x-transition:enter-start="opacity-0 transform -translate-y-2"
                x-transition:enter-end="opacity-100 transform translate-y-0"
                x-transition:leave="transition ease-in duration-150"
                x-transition:leave-start="opacity-100 transform translate-y-0"
                x-transition:leave-end="opacity-0 transform -translate-y-2" class="pl-10 mt-1 space-y-1">
                @foreach ($children as $child)
                    @if (auth()->user()->activeRole && auth()->user()->activeRole->hasPermissionTo($child['permiso']))
                        @php
                            $childIsActive = request()->route()->getName() === $child['route'];
                        @endphp
                        <a href="{{ route($child['route'], $child['parametro'] ?? '') }}" wire:navigate
                            class="flex items-center py-2 px-4 rounded-md transition-colors duration-150 ease-in-out dark:text-gray-200
                            {{ $childIsActive ? 'text-primary-600 dark:text-primary-400 bg-gray-200 dark:bg-white/5' : 'hover:bg-gray-200 dark:hover:bg-white/5' }}">
                            <span>{{ $child['texto'] }}</span>
                        </a>
                    @endif
                @endforeach
            </div>
        @else
            <a href="{{ route($route, $parametro) }}" wire:navigate
                class="flex items-center py-2 px-4 rounded-md transition-colors duration-150 ease-in-out dark:text-gray-200 {{ $class }}
                    {{ $isActive ? 'text-primary-600 dark:text-primary-400 bg-gray-200 dark:bg-white/5 cursor-default pointer-events-none' : 'hover:bg-gray-200 dark:hover:bg-white/5' }}">
                <div class="flex-shrink-0">
                    @if ($isActive)
                        @svg($icono, ['class' => 'fi-sidebar-item-icon h-6 w-6 text-primary-600 dark:text-primary-400'])
                    @else
                        @svg($icono, ['class' => 'fi-sidebar-item-icon h-6 w-6 text-gray-400 dark:text-gray-400'])
                    @endif
                </div>
                <span class="ml-4">{{ $titulo }}</span>

                @if ($notificaciones > 0)
                    <span
                        class="ml-auto bg-primary-100 text-primary-600 dark:bg-primary-900 dark:text-primary-400 px-2 py-0.5 rounded-full text-xs">
                        {{ $notificaciones }}
                    </span>
                @endif
            </a>
        @endif
    </div>
@endif
