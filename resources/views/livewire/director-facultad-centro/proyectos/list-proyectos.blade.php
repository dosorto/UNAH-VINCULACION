<div>
    <div class="mb-4 mt-4 flex justify-between items-center">
        <div>
            <p class="text-zinc-950 dark:text-white font-bold mb-1">{{ $titulo ?? ' }}</p>
            <p class="text-zinc-500 dark:text-gray-400 font-medium text-sm">{{ $descripcion ?? ' }}</p>
        </div>
    </div>

    {{-- Filtros --}}
    <div class="mb-4 flex flex-wrap gap-3">
        <input type="text" wire:model.live.debounce.300ms="search" placeholder="Buscar por nombre, código o dictamen..."
            class="w-full sm:w-72 rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 px-3 py-2 text-sm text-gray-900 dark:text-white shadow-sm focus:border-blue-500 focus:ring-blue-500" />
        <select wire:model.live="filterDepartamento"
            class="rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 px-3 py-2 text-sm text-gray-900 dark:text-white shadow-sm">
            <option value="">Todos los Departamentos</option>
            @foreach ($departamentos as $id => $nombre)
                <option value="{{ $id }}">{{ $nombre }}</option>
            @endforeach
        </select>
    </div>

    <div class="overflow-x-auto rounded-lg border border-gray-200 dark:border-gray-700">
        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 text-sm">
            <thead class="bg-gray-50 dark:bg-gray-800">
                <tr>
                    <th class="px-4 py-3 text-left font-semibold text-gray-600 dark:text-gray-300">Código</th>
                    <th class="px-4 py-3 text-left font-semibold text-gray-600 dark:text-gray-300">N° Dictamen</th>
                    <th class="px-4 py-3 text-left font-semibold text-gray-600 dark:text-gray-300">Nombre del Proyecto</th>
                    <th class="px-4 py-3 text-left font-semibold text-gray-600 dark:text-gray-300">Departamentos</th>
                    <th class="px-4 py-3 text-left font-semibold text-gray-600 dark:text-gray-300">Estado</th>
                    <th class="px-4 py-3 text-left font-semibold text-gray-600 dark:text-gray-300">Fecha Inicio</th>
                    <th class="px-4 py-3 text-left font-semibold text-gray-600 dark:text-gray-300">Acciones</th>
                </tr>
            </thead>
            <tbody class="bg-white dark:bg-gray-900 divide-y divide-gray-100 dark:divide-gray-800">
                @forelse ($records as $record)
                    @php
                        $estadoNombre = $record->tipo_estado?->nombre ?? 'Sin estado';
                        $estadoBadge = match($estadoNombre) {
                            'En curso'    => 'bg-green-100 text-green-800 dark:bg-green-900/20 dark:text-green-300',
                            'Subsanacion' => 'bg-red-100 text-red-800 dark:bg-red-900/20 dark:text-red-300',
                            'Borrador'    => 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/20 dark:text-yellow-300',
                            'Finalizado'  => 'bg-blue-100 text-blue-800 dark:bg-blue-900/20 dark:text-blue-300',
                            default       => 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300',
                        };
                    @endphp
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-800">
                        <td class="px-4 py-3 font-mono text-xs text-gray-700 dark:text-gray-300">{{ $record->codigo_proyecto ?: '—' }}</td>
                        <td class="px-4 py-3 text-gray-700 dark:text-gray-300">{{ $record->numero_dictamen ?: '—' }}</td>
                        <td class="px-4 py-3 text-gray-900 dark:text-white max-w-xs">{{ Str::limit($record->nombre_proyecto, 30) }}</td>
                        <td class="px-4 py-3">
                            @foreach ($record->departamentos_academicos as $depto)
                                <span class="inline-block px-2 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800 mr-1 mb-1">{{ $depto->nombre }}</span>
                            @endforeach
                        </td>
                        <td class="px-4 py-3">
                            <span class="px-2 py-1 rounded-full text-xs font-medium {{ $estadoBadge }}">{{ $estadoNombre }}</span>
                        </td>
                        <td class="px-4 py-3 text-gray-700 dark:text-gray-300">{{ $record->fecha_inicio?->format('d/m/Y') ?? '—' }}</td>
                        <td class="px-4 py-3">
                            <button wire:click="openView({{ $record->id }})"
                                class="inline-flex items-center px-3 py-1.5 text-xs font-medium rounded-md bg-blue-600 text-white hover:bg-blue-700">
                                Ver
                            </button>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="px-4 py-6 text-center text-gray-500 dark:text-gray-400">No hay proyectos registrados.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $records->links() }}</div>

    {{-- Modal Ver Ficha --}}
    @if ($viewModal && $viewProyecto)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/50" wire:click.self="closeView">
            <div class="bg-white dark:bg-gray-900 rounded-xl shadow-xl w-full max-w-7xl max-h-[90vh] overflow-y-auto mx-4">
                <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Proyecto de Vinculación</h3>
                    <button wire:click="closeView" class="text-gray-400 hover:text-gray-600 text-xl">&times;</button>
                </div>
                <div class="p-6">
                    @include('components.fichas.ficha-proyecto-vinculacion', ['proyecto' => $viewProyecto])
                </div>
                <div class="flex justify-end px-6 py-4 border-t border-gray-200 dark:border-gray-700">
                    <button wire:click="closeView"
                        class="px-4 py-2 text-sm font-medium rounded-md bg-gray-100 text-gray-700 hover:bg-gray-200 dark:bg-gray-700 dark:text-gray-300">
                        Cerrar
                    </button>
                </div>
            </div>
        </div>
    @endif
</div>
