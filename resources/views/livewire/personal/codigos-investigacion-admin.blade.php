<div>
    <div class="space-y-6">
        <div class="bg-white dark:bg-gray-800 shadow rounded-lg p-6">
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-xl font-semibold text-gray-900 dark:text-white">Gestión de Códigos de Proyectos de Vinculación</h2>
                    <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">Administrar y verificar los códigos de proyectos de vinculación registrados por los empleados.</p>
                </div>
                <div class="flex gap-2 text-xs">
                    <span class="px-2.5 py-0.5 rounded-full font-medium bg-yellow-100 text-yellow-800">Pendientes</span>
                    <span class="px-2.5 py-0.5 rounded-full font-medium bg-green-100 text-green-800">Verificados</span>
                    <span class="px-2.5 py-0.5 rounded-full font-medium bg-red-100 text-red-800">Rechazados</span>
                </div>
            </div>
        </div>

        {{-- Filtros --}}
        <div class="flex flex-wrap gap-3">
            <input type="text" wire:model.live.debounce.300ms="search" placeholder="Buscar docente o código..."
                class="w-full sm:w-64 rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 px-3 py-2 text-sm text-gray-900 dark:text-white shadow-sm focus:border-blue-500 focus:ring-blue-500" />
            <select wire:model.live="filterEstado"
                class="rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 px-3 py-2 text-sm text-gray-900 dark:text-white shadow-sm">
                <option value="">Todos los estados</option>
                <option value="pendiente">Pendiente</option>
                <option value="verificado">Verificado</option>
                <option value="rechazado">Rechazado</option>
            </select>
            <select wire:model.live="filterRol"
                class="rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 px-3 py-2 text-sm text-gray-900 dark:text-white shadow-sm">
                <option value="">Todos los roles</option>
                <option value="coordinador">Coordinador</option>
                <option value="integrante">Integrante</option>
            </select>
            <select wire:model.live="filterAnio"
                class="rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 px-3 py-2 text-sm text-gray-900 dark:text-white shadow-sm">
                <option value="">Todos los años</option>
                @foreach ($anios as $year)
                    <option value="{{ $year }}">{{ $year }}</option>
                @endforeach
            </select>
        </div>

        <div class="overflow-x-auto rounded-lg border border-gray-200 dark:border-gray-700">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 text-sm">
                <thead class="bg-gray-50 dark:bg-gray-800">
                    <tr>
                        <th class="px-4 py-3 text-left font-semibold text-gray-600 dark:text-gray-300">Docente</th>
                        <th class="px-4 py-3 text-left font-semibold text-gray-600 dark:text-gray-300">Código</th>
                        <th class="px-4 py-3 text-left font-semibold text-gray-600 dark:text-gray-300">Nombre del Proyecto</th>
                        <th class="px-4 py-3 text-left font-semibold text-gray-600 dark:text-gray-300">Rol</th>
                        <th class="px-4 py-3 text-left font-semibold text-gray-600 dark:text-gray-300">Año</th>
                        <th class="px-4 py-3 text-left font-semibold text-gray-600 dark:text-gray-300">Estado</th>
                        <th class="px-4 py-3 text-left font-semibold text-gray-600 dark:text-gray-300">Verificado por</th>
                        <th class="px-4 py-3 text-left font-semibold text-gray-600 dark:text-gray-300">Acciones</th>
                    </tr>
                </thead>
                <tbody class="bg-white dark:bg-gray-900 divide-y divide-gray-100 dark:divide-gray-800">
                    @forelse ($records as $record)
                        @php
                            $estadoBadge = match($record->estado_verificacion) {
                                'pendiente'  => 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/20 dark:text-yellow-300',
                                'verificado' => 'bg-green-100 text-green-800 dark:bg-green-900/20 dark:text-green-300',
                                'rechazado'  => 'bg-red-100 text-red-800 dark:bg-red-900/20 dark:text-red-300',
                                default      => 'bg-gray-100 text-gray-800',
                            };
                            $estadoLabel = match($record->estado_verificacion) {
                                'pendiente'  => 'Pendiente',
                                'verificado' => 'Verificado',
                                'rechazado'  => 'Rechazado',
                                default      => 'Desconocido',
                            };
                            $rolBadge = $record->rol_docente === 'coordinador'
                                ? 'bg-green-100 text-green-800 dark:bg-green-900/20 dark:text-green-300'
                                : 'bg-blue-100 text-blue-800 dark:bg-blue-900/20 dark:text-blue-300';
                        @endphp
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-800">
                            <td class="px-4 py-3 text-gray-900 dark:text-white">{{ $record->empleado?->nombre_completo }}</td>
                            <td class="px-4 py-3 font-mono text-xs text-gray-700 dark:text-gray-300">{{ $record->codigo_proyecto }}</td>
                            <td class="px-4 py-3 text-gray-700 dark:text-gray-300 max-w-xs">
                                <span title="{{ $record->nombre_proyecto }}">{{ Str::limit($record->nombre_proyecto, 50) }}</span>
                            </td>
                            <td class="px-4 py-3">
                                <span class="px-2 py-1 rounded-full text-xs font-medium {{ $rolBadge }}">
                                    {{ $record->rol_docente === 'coordinador' ? 'Coordinador' : 'Integrante' }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-gray-700 dark:text-gray-300">{{ $record->año }}</td>
                            <td class="px-4 py-3">
                                <span class="px-2 py-1 rounded-full text-xs font-medium {{ $estadoBadge }}">{{ $estadoLabel }}</span>
                            </td>
                            <td class="px-4 py-3 text-gray-700 dark:text-gray-300">{{ $record->verificadoPor?->nombre_completo ?? '—' }}</td>
                            <td class="px-4 py-3">
                                <div class="flex gap-2 flex-wrap">
                                    @if ($record->estado_verificacion === 'pendiente')
                                        <button wire:click="openVerificar({{ $record->id }})"
                                            class="inline-flex items-center px-2.5 py-1 text-xs font-medium rounded-md bg-green-600 text-white hover:bg-green-700">
                                            Verificar
                                        </button>
                                        <button wire:click="openRechazar({{ $record->id }})"
                                            class="inline-flex items-center px-2.5 py-1 text-xs font-medium rounded-md bg-red-600 text-white hover:bg-red-700">
                                            Rechazar
                                        </button>
                                    @else
                                        <button wire:click="revertir({{ $record->id }})" wire:confirm="¿Revertir este código a estado pendiente?"
                                            class="inline-flex items-center px-2.5 py-1 text-xs font-medium rounded-md bg-yellow-500 text-white hover:bg-yellow-600">
                                            Revertir
                                        </button>
                                    @endif
                                    <button wire:click="openView({{ $record->id }})"
                                        class="inline-flex items-center px-2.5 py-1 text-xs font-medium rounded-md bg-gray-100 text-gray-700 hover:bg-gray-200 dark:bg-gray-700 dark:text-gray-300">
                                        Ver
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-4 py-6 text-center text-gray-500 dark:text-gray-400">No se encontraron registros.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-4">{{ $records->links() }}</div>
    </div>

    {{-- Modal Verificar --}}
    @if ($verificarModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/50">
            <div class="bg-white dark:bg-gray-900 rounded-xl shadow-xl w-full max-w-lg mx-4">
                <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Verificar Código</h3>
                </div>
                <div class="p-6 space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Número de Dictamen <span class="text-red-500">*</span></label>
                        <input type="text" wire:model="verificarDictamen" placeholder="Agregar número de dictamen..."
                            class="w-full rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 px-3 py-2 text-sm text-gray-900 dark:text-white focus:border-blue-500 focus:ring-blue-500" />
                        @error('verificarDictamen') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Observaciones (Opcional)</label>
                        <textarea wire:model="verificarObservaciones" rows="3" placeholder="Agregar observaciones..."
                            class="w-full rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 px-3 py-2 text-sm text-gray-900 dark:text-white focus:border-blue-500 focus:ring-blue-500"></textarea>
                    </div>
                </div>
                <div class="flex justify-end gap-3 px-6 py-4 border-t border-gray-200 dark:border-gray-700">
                    <button wire:click="$set('verificarModal', false)"
                        class="px-4 py-2 text-sm font-medium rounded-md bg-gray-100 text-gray-700 hover:bg-gray-200 dark:bg-gray-700 dark:text-gray-300">
                        Cancelar
                    </button>
                    <button wire:click="verificar"
                        class="px-4 py-2 text-sm font-medium rounded-md bg-green-600 text-white hover:bg-green-700">
                        Verificar
                    </button>
                </div>
            </div>
        </div>
    @endif

    {{-- Modal Rechazar --}}
    @if ($rechazarModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/50">
            <div class="bg-white dark:bg-gray-900 rounded-xl shadow-xl w-full max-w-lg mx-4">
                <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Rechazar Código</h3>
                </div>
                <div class="p-6">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Motivo del Rechazo <span class="text-red-500">*</span></label>
                    <textarea wire:model="rechazarObservaciones" rows="3" placeholder="Explicar por qué se rechaza este código..."
                        class="w-full rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 px-3 py-2 text-sm text-gray-900 dark:text-white focus:border-blue-500 focus:ring-blue-500"></textarea>
                    @error('rechazarObservaciones') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>
                <div class="flex justify-end gap-3 px-6 py-4 border-t border-gray-200 dark:border-gray-700">
                    <button wire:click="$set('rechazarModal', false)"
                        class="px-4 py-2 text-sm font-medium rounded-md bg-gray-100 text-gray-700 hover:bg-gray-200 dark:bg-gray-700 dark:text-gray-300">
                        Cancelar
                    </button>
                    <button wire:click="rechazar"
                        class="px-4 py-2 text-sm font-medium rounded-md bg-red-600 text-white hover:bg-red-700">
                        Rechazar
                    </button>
                </div>
            </div>
        </div>
    @endif

    {{-- Modal Ver Detalles --}}
    @if ($viewModal && $viewRecord)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/50" wire:click.self="$set('viewModal', false)">
            <div class="bg-white dark:bg-gray-900 rounded-xl shadow-xl w-full max-w-2xl max-h-[90vh] overflow-y-auto mx-4">
                <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Detalles del Código</h3>
                    <button wire:click="$set('viewModal', false)" class="text-gray-400 hover:text-gray-600 text-xl">&times;</button>
                </div>
                <div class="p-6">
                    @include('livewire.personal.codigo-investigacion-detalles', ['record' => $viewRecord])
                </div>
                <div class="flex justify-end px-6 py-4 border-t border-gray-200 dark:border-gray-700">
                    <button wire:click="$set('viewModal', false)"
                        class="px-4 py-2 text-sm font-medium rounded-md bg-gray-100 text-gray-700 hover:bg-gray-200 dark:bg-gray-700 dark:text-gray-300">
                        Cerrar
                    </button>
                </div>
            </div>
        </div>
    @endif
</div>
