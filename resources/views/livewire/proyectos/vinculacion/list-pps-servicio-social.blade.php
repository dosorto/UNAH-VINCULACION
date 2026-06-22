<div>
    @php
        $descripcionVista = match($viewMode) {
            'pendientes' => 'Registros enviados que están en una etapa activa asignada a tu usuario y rol activo, o a tu rol activo.',
            'todos' => 'Vista administrativa con todos los registros PPS/Servicio Social.',
            default => 'Registros PPS/Servicio Social creados por tu usuario.',
        };

        $tituloVacio = match($viewMode) {
            'pendientes' => 'No hay registros pendientes de revisión.',
            'todos' => 'No hay registros PPS/Servicio Social.',
            default => 'No hay registros creados por tu usuario.',
        };

        $textoVacio = match($viewMode) {
            'pendientes' => 'Cuando un registro llegue a una etapa asignada a tu rol, aparecerá aquí.',
            'todos' => $canCreateRecord
                ? 'Crea el primer registro desde el botón "Nuevo registro".'
                : 'Aún no existen registros PPS/Servicio Social.',
            default => $canCreateRecord
                ? 'Crea el primer registro desde el botón "Nuevo registro".'
                : 'No registraste PPS/Servicio Social con este usuario.',
        };

        $tipoPpsEtiqueta = fn (?string $tipo) => [
            'Practica Profesional Supervisada' => 'Práctica Profesional Supervisada',
        ][$tipo] ?? ($tipo ?: 'No registrado');
    @endphp

    <div class="mb-4 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <p class="text-xs font-semibold uppercase tracking-wide text-blue-700 dark:text-blue-300">FORM-DVUS-014</p>
            <h1 class="text-2xl font-bold text-gray-950 dark:text-white">PPS / Servicio Social</h1>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                {{ $descripcionVista }}
            </p>
        </div>

        @if($canCreateRecord)
            <a href="{{ route('crearPpsServicioSocial') }}" wire:navigate
               class="inline-flex items-center justify-center rounded-lg bg-blue-700 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-blue-800">
                Nuevo registro
            </a>
        @endif
    </div>

    <div class="mb-4 flex flex-wrap gap-2 border-b border-gray-200 pb-3 dark:border-gray-700">
        <button type="button"
                wire:click="$set('viewMode', 'mis')"
                class="inline-flex items-center gap-2 rounded-md px-3 py-2 text-sm font-semibold transition {{ $viewMode === 'mis' ? 'bg-blue-700 text-white shadow-sm' : 'bg-white text-gray-700 ring-1 ring-gray-200 hover:bg-gray-50 dark:bg-gray-900 dark:text-gray-200 dark:ring-gray-700 dark:hover:bg-gray-800' }}">
            <span>Mis registros</span>
            <span class="rounded-full px-2 py-0.5 text-xs {{ $viewMode === 'mis' ? 'bg-white/20 text-white' : 'bg-gray-100 text-gray-600 dark:bg-gray-800 dark:text-gray-300' }}">{{ $ownRecordsCount }}</span>
        </button>

        <button type="button"
                wire:click="$set('viewMode', 'pendientes')"
                class="inline-flex items-center gap-2 rounded-md px-3 py-2 text-sm font-semibold transition {{ $viewMode === 'pendientes' ? 'bg-blue-700 text-white shadow-sm' : 'bg-white text-gray-700 ring-1 ring-gray-200 hover:bg-gray-50 dark:bg-gray-900 dark:text-gray-200 dark:ring-gray-700 dark:hover:bg-gray-800' }}">
            <span>Pendientes de revisión</span>
            <span class="rounded-full px-2 py-0.5 text-xs {{ $viewMode === 'pendientes' ? 'bg-white/20 text-white' : ($pendingReviewCount > 0 ? 'bg-amber-100 text-amber-800 dark:bg-amber-900/40 dark:text-amber-200' : 'bg-gray-100 text-gray-600 dark:bg-gray-800 dark:text-gray-300') }}">{{ $pendingReviewCount }}</span>
        </button>

        @if($canViewAllRecords)
            <button type="button"
                    wire:click="$set('viewMode', 'todos')"
                    class="inline-flex items-center gap-2 rounded-md px-3 py-2 text-sm font-semibold transition {{ $viewMode === 'todos' ? 'bg-blue-700 text-white shadow-sm' : 'bg-white text-gray-700 ring-1 ring-gray-200 hover:bg-gray-50 dark:bg-gray-900 dark:text-gray-200 dark:ring-gray-700 dark:hover:bg-gray-800' }}">
                <span>Todos</span>
                <span class="rounded-full px-2 py-0.5 text-xs {{ $viewMode === 'todos' ? 'bg-white/20 text-white' : 'bg-gray-100 text-gray-600 dark:bg-gray-800 dark:text-gray-300' }}">{{ $allRecordsCount }}</span>
            </button>
        @endif
    </div>

    <div class="mb-4 grid grid-cols-1 gap-3 lg:grid-cols-4">
        <input type="text"
               wire:model.live.debounce.300ms="search"
               placeholder="Buscar por código, estudiante, cuenta o institución..."
               class="lg:col-span-2 rounded-md border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 shadow-sm focus:border-blue-500 focus:ring-1 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-800 dark:text-white">

        <select wire:model.live="filterEstado"
                class="rounded-md border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 shadow-sm focus:border-blue-500 focus:ring-1 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-800 dark:text-white">
            <option value="">Todos los estados</option>
            @foreach($estados as $estado)
                <option value="{{ $estado }}">{{ ucfirst($estado) }}</option>
            @endforeach
        </select>

        <select wire:model.live="filterTipo"
                class="rounded-md border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 shadow-sm focus:border-blue-500 focus:ring-1 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-800 dark:text-white">
            <option value="">Todos los tipos</option>
            @foreach($tipos as $tipo)
                <option value="{{ $tipo }}">{{ $tipo }}</option>
            @endforeach
        </select>
    </div>

    <div class="overflow-x-auto rounded-xl border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-900">
        <table class="min-w-full divide-y divide-gray-200 text-sm dark:divide-gray-700">
            <thead class="bg-gray-50 dark:bg-gray-800">
                <tr>
                    <th class="px-4 py-3 text-left font-semibold text-gray-600 dark:text-gray-300">Código / ID</th>
                    <th class="px-4 py-3 text-left font-semibold text-gray-600 dark:text-gray-300">Estudiante</th>
                    <th class="px-4 py-3 text-left font-semibold text-gray-600 dark:text-gray-300">Cuenta</th>
                    <th class="px-4 py-3 text-left font-semibold text-gray-600 dark:text-gray-300">Tipo PPS/SS</th>
                    <th class="px-4 py-3 text-left font-semibold text-gray-600 dark:text-gray-300">Institución</th>
                    <th class="px-4 py-3 text-left font-semibold text-gray-600 dark:text-gray-300">Fechas</th>
                    <th class="px-4 py-3 text-left font-semibold text-gray-600 dark:text-gray-300">Estado</th>
                    <th class="px-4 py-3 text-left font-semibold text-gray-600 dark:text-gray-300">Acciones</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 bg-white dark:divide-gray-800 dark:bg-gray-900">
                @forelse($records as $record)
                    @php
                        $puedeEditar = $record->estado === 'borrador'
                            && $record->created_by !== null
                            && auth()->id() !== null
                            && (int) $record->created_by === (int) auth()->id();
                        $estadoBadge = match($record->estado) {
                            'borrador' => 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/40 dark:text-yellow-200',
                            'enviado' => 'bg-blue-100 text-blue-800 dark:bg-blue-900/40 dark:text-blue-200',
                            'en_revision' => 'bg-cyan-100 text-cyan-800 dark:bg-cyan-900/40 dark:text-cyan-200',
                            'aprobado' => 'bg-green-100 text-green-800 dark:bg-green-900/40 dark:text-green-200',
                            'rechazado' => 'bg-red-100 text-red-800 dark:bg-red-900/40 dark:text-red-200',
                            'subsanacion' => 'bg-amber-100 text-amber-800 dark:bg-amber-900/40 dark:text-amber-200',
                            default => 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-200',
                        };
                    @endphp
                    <tr class="transition hover:bg-gray-50 dark:hover:bg-gray-800">
                        <td class="px-4 py-3 font-medium text-gray-900 dark:text-white">
                            {{ $record->codigo_registro ?: '#' . $record->id }}
                        </td>
                        <td class="px-4 py-3 text-gray-700 dark:text-gray-300">
                            <p class="font-medium text-gray-900 dark:text-white">{{ $record->nombre_estudiante }}</p>
                            <p class="text-xs text-gray-500 dark:text-gray-400">{{ $record->correo_institucional }}</p>
                        </td>
                        <td class="px-4 py-3 text-gray-700 dark:text-gray-300">{{ $record->numero_cuenta }}</td>
                        <td class="px-4 py-3 text-gray-700 dark:text-gray-300">{{ $tipoPpsEtiqueta($record->tipo_pps_ss) }}</td>
                        <td class="px-4 py-3 text-gray-700 dark:text-gray-300">
                            {{ \Illuminate\Support\Str::limit($record->nombre_institucion, 45) }}
                        </td>
                        <td class="px-4 py-3 text-gray-700 dark:text-gray-300">
                            <span>{{ $record->fecha_inicio?->format('d/m/Y') ?? '-' }}</span>
                            <span class="text-gray-400">-</span>
                            <span>{{ $record->fecha_finalizacion?->format('d/m/Y') ?? '-' }}</span>
                        </td>
                        <td class="px-4 py-3">
                            <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-semibold {{ $estadoBadge }}">
                                {{ ucfirst($record->estado ?: 'sin estado') }}
                            </span>
                            @if($viewMode === 'pendientes' && $record->etapaActual)
                                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                                    Etapa: {{ $record->etapaActual->nombre }}
                                </p>
                            @endif
                        </td>
                        <td class="px-4 py-3">
                            <div class="flex flex-wrap gap-2">
                                <a href="{{ route('pps-servicio-social.show', $record->id) }}" wire:navigate
                                   class="inline-flex items-center justify-center rounded-md border border-blue-200 bg-blue-50 px-3 py-1.5 text-xs font-semibold text-blue-700 transition hover:bg-blue-100 dark:border-blue-900/60 dark:bg-blue-900/30 dark:text-blue-200 dark:hover:bg-blue-900/50">
                                    Ver
                                </a>
                                @if($puedeEditar)
                                    <a href="{{ route('pps-servicio-social.edit', $record->id) }}" wire:navigate
                                       class="inline-flex items-center justify-center rounded-md border border-amber-200 bg-amber-50 px-3 py-1.5 text-xs font-semibold text-amber-700 transition hover:bg-amber-100 dark:border-amber-900/60 dark:bg-amber-900/30 dark:text-amber-200 dark:hover:bg-amber-900/50">
                                        Editar
                                    </a>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="px-4 py-10 text-center">
                            <p class="font-medium text-gray-700 dark:text-gray-200">{{ $tituloVacio }}</p>
                            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ $textoVacio }}</p>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">
        {{ $records->links() }}
    </div>
</div>
