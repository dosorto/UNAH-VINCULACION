<div class="mx-auto max-w-7xl space-y-6 px-4 py-6 sm:px-6 lg:px-8">
    <section class="flex flex-col gap-4 rounded-2xl bg-white px-5 py-5 shadow-sm ring-1 ring-slate-200 dark:bg-slate-900 dark:ring-slate-800 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <p class="text-xs font-semibold uppercase tracking-[0.24em] text-cyan-700 dark:text-cyan-400">Configuración</p>
            <h1 class="mt-1 text-2xl font-bold tracking-tight text-slate-900 dark:text-slate-100">Integraciones API</h1>
            <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
                Configura la integración de estudiantes sin exponer credenciales ni respuestas sensibles.
            </p>
        </div>
        <button type="button" wire:click="create"
            class="inline-flex items-center justify-center rounded-full bg-primary px-4 py-2 text-sm font-semibold text-white transition hover:opacity-90">
            Nueva integración
        </button>
    </section>

    @if (session('status'))
        <div class="rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-700 dark:border-emerald-900/60 dark:bg-emerald-950/40 dark:text-emerald-300">
            {{ session('status') }}
        </div>
    @endif

    @error('toggle')
        <div class="rounded-2xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm font-medium text-rose-700 dark:border-rose-900/60 dark:bg-rose-950/40 dark:text-rose-300">
            {{ $message }}
        </div>
    @enderror

    <section class="overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-slate-200 dark:bg-slate-900 dark:ring-slate-800">
        <div class="border-b border-slate-200 px-5 py-4 dark:border-slate-800">
            <h2 class="font-semibold text-slate-900 dark:text-slate-100">Integración de estudiantes</h2>
            <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
                La configuración protegida “Estudiantes” puede editarse y probarse, pero no eliminarse.
            </p>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200 text-sm dark:divide-slate-800">
                <thead class="bg-slate-50 text-left text-xs font-semibold uppercase tracking-wide text-slate-500 dark:bg-slate-800/60 dark:text-slate-400">
                    <tr>
                        <th class="px-4 py-3">Nombre</th>
                        <th class="px-4 py-3">Perfil</th>
                        <th class="px-4 py-3">Método</th>
                        <th class="px-4 py-3">Base URL</th>
                        <th class="px-4 py-3">Ruta</th>
                        <th class="px-4 py-3">Estado</th>
                        <th class="px-4 py-3">Última prueba</th>
                        <th class="px-4 py-3">Resultado</th>
                        <th class="px-4 py-3 text-right">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200 text-slate-700 dark:divide-slate-800 dark:text-slate-200">
                    @forelse ($integraciones as $item)
                        <tr wire:key="integracion-{{ $item->id }}" class="align-top">
                            <td class="px-4 py-4">
                                <span class="font-semibold">{{ $item->nombre }}</span>
                                <span class="mt-1 block text-xs text-slate-400">{{ $item->codigo }}</span>
                            </td>
                            <td class="px-4 py-4">{{ $item->tipo_perfil }}</td>
                            <td class="px-4 py-4"><span class="rounded-full bg-cyan-50 px-2 py-1 text-xs font-semibold text-cyan-700 dark:bg-cyan-950/40 dark:text-cyan-300">{{ $item->metodo_http }}</span></td>
                            <td class="max-w-56 break-all px-4 py-4">{{ $item->base_url ?: 'Sin configurar' }}</td>
                            <td class="max-w-48 break-all px-4 py-4">{{ $item->ruta_busqueda ?: 'Sin configurar' }}</td>
                            <td class="px-4 py-4">
                                <span class="rounded-full px-2 py-1 text-xs font-semibold {{ $item->activo ? 'bg-emerald-50 text-emerald-700 dark:bg-emerald-950/40 dark:text-emerald-300' : 'bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-300' }}">
                                    {{ $item->activo ? 'Activa' : 'Inactiva' }}
                                </span>
                            </td>
                            <td class="whitespace-nowrap px-4 py-4">{{ $item->ultima_prueba_at?->format('d/m/Y H:i') ?? 'Sin prueba' }}</td>
                            <td class="max-w-52 px-4 py-4">
                                @if ($item->ultima_prueba_at)
                                    <span class="{{ $item->ultima_prueba_exitosa ? 'text-emerald-600 dark:text-emerald-300' : 'text-rose-600 dark:text-rose-300' }}">
                                        {{ $item->ultimo_mensaje }}
                                    </span>
                                @else
                                    <span class="text-slate-400">Pendiente</span>
                                @endif
                            </td>
                            <td class="px-4 py-4">
                                <div class="flex min-w-52 flex-wrap justify-end gap-2">
                                    <button type="button" wire:click="edit({{ $item->id }})" class="rounded-full border border-blue-200 px-3 py-1.5 text-xs font-semibold text-blue-700 hover:bg-blue-50 dark:border-blue-800 dark:text-blue-300 dark:hover:bg-blue-950/30">Editar</button>
                                    <button type="button" wire:click="openTest({{ $item->id }})" class="rounded-full border border-emerald-200 px-3 py-1.5 text-xs font-semibold text-emerald-700 hover:bg-emerald-50 dark:border-emerald-800 dark:text-emerald-300 dark:hover:bg-emerald-950/30">Probar conexión</button>
                                    <button type="button" wire:click="toggle({{ $item->id }})" class="rounded-full border border-amber-200 px-3 py-1.5 text-xs font-semibold text-amber-700 hover:bg-amber-50 dark:border-amber-800 dark:text-amber-300 dark:hover:bg-amber-950/30">{{ $item->activo ? 'Desactivar' : 'Activar' }}</button>
                                    <button type="button" wire:click="viewHistory({{ $item->id }})" class="rounded-full border border-slate-200 px-3 py-1.5 text-xs font-semibold text-slate-600 hover:bg-slate-50 dark:border-slate-700 dark:text-slate-300 dark:hover:bg-slate-800">Historial</button>
                                    @unless ($item->protegida)
                                        <button type="button" wire:click="delete({{ $item->id }})" wire:confirm="¿Desea eliminar esta integración?" class="rounded-full border border-rose-200 px-3 py-1.5 text-xs font-semibold text-rose-700 hover:bg-rose-50 dark:border-rose-800 dark:text-rose-300 dark:hover:bg-rose-950/30">Eliminar</button>
                                    @endunless
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="px-5 py-10 text-center text-slate-500 dark:text-slate-400">
                                No hay integraciones configuradas.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>

    @if ($showForm)
        <div class="fixed inset-0 z-50 overflow-y-auto" role="dialog" aria-modal="true">
            <div class="fixed inset-0 bg-slate-950/60" wire:click="closeForm"></div>
            <div class="relative flex min-h-full items-start justify-center p-4 sm:p-8">
                <section class="relative w-full max-w-5xl overflow-hidden rounded-2xl bg-white shadow-2xl dark:bg-slate-900">
                    <header class="flex items-start justify-between border-b border-slate-200 px-5 py-4 dark:border-slate-800">
                        <div>
                            <h2 class="text-lg font-bold text-slate-900 dark:text-slate-100">Editar integración API</h2>
                            <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
                                Configura autenticación, endpoint y mapeo de respuesta para el tipo de perfil seleccionado.
                            </p>
                        </div>
                        <button type="button" wire:click="closeForm" class="rounded-full p-2 text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800" aria-label="Cerrar">✕</button>
                    </header>

                    <div class="max-h-[75vh] space-y-6 overflow-y-auto px-5 py-5">
                        <fieldset class="grid gap-4 md:grid-cols-3">
                            <legend class="mb-3 text-sm font-bold uppercase tracking-wide text-slate-700 dark:text-slate-200">Información general</legend>
                            <x-integration-field label="Nombre" model="form.nombre" />
                            <x-integration-field label="Slug" model="form.codigo" />
                            <label class="space-y-1.5">
                                <span class="text-sm font-medium text-slate-700 dark:text-slate-300">Tipo de perfil</span>
                                <select wire:model="form.tipo_perfil" class="w-full rounded-xl border-slate-300 bg-white text-sm dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100">
                                    <option value="ESTUDIANTE">Estudiante</option>
                                    <option value="EMPLEADO">Empleado</option>
                                    <option value="EXTERNO">Externo</option>
                                </select>
                                @error('form.tipo_perfil')<p class="text-xs text-rose-600">{{ $message }}</p>@enderror
                            </label>
                            <label class="inline-flex items-center gap-3 text-sm font-medium text-slate-700 dark:text-slate-300 md:col-span-3">
                                <input type="checkbox" wire:model="form.activo" class="rounded border-slate-300 text-primary dark:border-slate-700 dark:bg-slate-800">
                                Integración activa
                            </label>
                        </fieldset>

                        <fieldset class="grid gap-4 border-t border-slate-200 pt-5 dark:border-slate-800 md:grid-cols-3">
                            <legend class="mb-3 text-sm font-bold uppercase tracking-wide text-slate-700 dark:text-slate-200">Endpoint</legend>
                            <div class="md:col-span-2"><x-integration-field label="Base URL" model="form.base_url" placeholder="https://api.institucion.edu" /></div>
                            <x-integration-field label="Ruta de búsqueda" model="form.ruta_busqueda" placeholder="/estudiantes/buscar" />
                            <label class="space-y-1.5">
                                <span class="text-sm font-medium text-slate-700 dark:text-slate-300">Método HTTP</span>
                                <select wire:model="form.metodo_http" class="w-full rounded-xl border-slate-300 bg-white text-sm dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100"><option>GET</option><option>POST</option></select>
                            </label>
                            <x-integration-field label="Parámetro de consulta" model="form.parametro_busqueda" />
                            <x-integration-field label="Timeout (segundos)" model="form.timeout_segundos" type="number" />
                            <x-integration-field label="Reintentos" model="form.reintentos" type="number" />
                            <label class="inline-flex items-center gap-3 self-end pb-3 text-sm font-medium text-slate-700 dark:text-slate-300">
                                <input type="checkbox" wire:model="form.verificar_ssl" class="rounded border-slate-300 text-primary dark:border-slate-700 dark:bg-slate-800">
                                Verificar certificado SSL
                            </label>
                        </fieldset>

                        <fieldset class="grid gap-4 border-t border-slate-200 pt-5 dark:border-slate-800 md:grid-cols-3">
                            <legend class="mb-3 text-sm font-bold uppercase tracking-wide text-slate-700 dark:text-slate-200">Autenticación</legend>
                            <label class="space-y-1.5">
                                <span class="text-sm font-medium text-slate-700 dark:text-slate-300">Tipo</span>
                                <select wire:model.live="form.tipo_autenticacion" class="w-full rounded-xl border-slate-300 bg-white text-sm dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100">
                                    <option value="NINGUNA">Ninguna</option>
                                    <option value="BEARER">Bearer Token</option>
                                    <option value="BASIC">Basic Auth</option>
                                    <option value="API_KEY">API Key</option>
                                </select>
                                @error('form.tipo_autenticacion')<p class="text-xs text-rose-600">{{ $message }}</p>@enderror
                            </label>

                            @if (in_array($form['tipo_autenticacion'], ['BEARER', 'API_KEY'], true))
                                <x-integration-field label="{{ $editingId ? 'Nuevo secreto (opcional)' : 'Secreto' }}" model="form.token" type="password" />
                            @endif
                            @if ($form['tipo_autenticacion'] === 'BASIC')
                                <x-integration-field label="{{ $editingId ? 'Nuevo usuario (opcional)' : 'Usuario API' }}" model="form.usuario_api" type="password" />
                                <x-integration-field label="{{ $editingId ? 'Nueva contraseña (opcional)' : 'Contraseña API' }}" model="form.password_api" type="password" />
                            @endif
                            @if ($form['tipo_autenticacion'] === 'API_KEY')
                                <x-integration-field label="Nombre del header o parámetro" model="form.api_key_header" />
                                <label class="space-y-1.5">
                                    <span class="text-sm font-medium text-slate-700 dark:text-slate-300">Ubicación</span>
                                    <select wire:model="form.api_key_ubicacion" class="w-full rounded-xl border-slate-300 bg-white text-sm dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100"><option value="HEADER">Header</option><option value="QUERY">Query</option></select>
                                </label>
                            @endif
                            @if ($editingId && $form['tipo_autenticacion'] !== 'NINGUNA')
                                <p class="text-xs text-slate-500 dark:text-slate-400 md:col-span-3">Los secretos guardados nunca se muestran. Deje los campos vacíos para conservarlos.</p>
                            @endif
                        </fieldset>

                        <fieldset class="grid gap-4 border-t border-slate-200 pt-5 dark:border-slate-800 md:grid-cols-2">
                            <legend class="mb-3 text-sm font-bold uppercase tracking-wide text-slate-700 dark:text-slate-200">Respuesta</legend>
                            <x-integration-field label="Ruta interna de respuesta" model="form.ruta_respuesta" placeholder="data.estudiante" />
                            <div></div>
                            <label class="space-y-1.5">
                                <span class="text-sm font-medium text-slate-700 dark:text-slate-300">Headers JSON</span>
                                <textarea wire:model="form.headers_json" rows="8" spellcheck="false" class="w-full rounded-xl border-slate-300 bg-white font-mono text-xs dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100"></textarea>
                                @error('form.headers_json')<p class="text-xs text-rose-600">{{ $message }}</p>@enderror
                            </label>
                            <label class="space-y-1.5">
                                <span class="text-sm font-medium text-slate-700 dark:text-slate-300">Mapeo de campos JSON</span>
                                <textarea wire:model="form.mapeo_campos_json" rows="12" spellcheck="false" class="w-full rounded-xl border-slate-300 bg-white font-mono text-xs dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100"></textarea>
                                @error('form.mapeo_campos_json')<p class="text-xs text-rose-600">{{ $message }}</p>@enderror
                            </label>
                            <div class="rounded-xl bg-slate-50 p-3 text-xs text-slate-600 dark:bg-slate-800/60 dark:text-slate-300 md:col-span-2">
                                <strong>Campos internos permitidos:</strong> {{ implode(', ', $camposPermitidos) }}.
                                Las rutas externas admiten notación punto, por ejemplo <code>carrera.nombre</code>.
                            </div>
                        </fieldset>
                    </div>

                    <footer class="flex justify-end gap-2 border-t border-slate-200 px-5 py-4 dark:border-slate-800">
                        <button type="button" wire:click="closeForm" class="rounded-full border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700 dark:border-slate-700 dark:text-slate-200">Cancelar</button>
                        <button type="button" wire:click="save" wire:loading.attr="disabled" class="rounded-full bg-primary px-4 py-2 text-sm font-semibold text-white disabled:opacity-50">Guardar integración</button>
                    </footer>
                </section>
            </div>
        </div>
    @endif

    @if ($showTest)
        <div class="fixed inset-0 z-50 overflow-y-auto" role="dialog" aria-modal="true">
            <div class="fixed inset-0 bg-slate-950/60" wire:click="closeTest"></div>
            <div class="relative flex min-h-full items-center justify-center p-4">
                <section class="relative w-full max-w-lg rounded-2xl bg-white p-5 shadow-2xl dark:bg-slate-900">
                    <h2 class="text-lg font-bold text-slate-900 dark:text-slate-100">Probar conexión</h2>
                    <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">El número utilizado no se almacenará.</p>
                    <div class="mt-4"><x-integration-field label="Número de cuenta de ejemplo" model="testValue" /></div>
                    @if ($testResult)
                        <div class="mt-4 rounded-xl border p-4 text-sm {{ $testResult['ok'] ? 'border-emerald-200 bg-emerald-50 text-emerald-700 dark:border-emerald-900 dark:bg-emerald-950/30 dark:text-emerald-300' : 'border-rose-200 bg-rose-50 text-rose-700 dark:border-rose-900 dark:bg-rose-950/30 dark:text-rose-300' }}">
                            <p class="font-semibold">{{ $testResult['mensaje'] }}</p>
                            @if ($testResult['resumen'])
                                <dl class="mt-3 grid grid-cols-2 gap-2 text-xs">
                                    @foreach ($testResult['resumen'] as $field => $value)
                                        <dt class="font-semibold">{{ str($field)->replace('_', ' ')->title() }}</dt><dd>{{ $value }}</dd>
                                    @endforeach
                                </dl>
                            @endif
                            <p class="mt-2 text-xs">Duración: {{ $testResult['duracion_ms'] }} ms</p>
                        </div>
                    @endif
                    <div class="mt-5 flex justify-end gap-2">
                        <button type="button" wire:click="closeTest" class="rounded-full border border-slate-300 px-4 py-2 text-sm font-semibold dark:border-slate-700 dark:text-slate-200">Cerrar</button>
                        <button type="button" wire:click="testConnection" wire:loading.attr="disabled" class="rounded-full bg-primary px-4 py-2 text-sm font-semibold text-white disabled:opacity-50">Ejecutar prueba</button>
                    </div>
                </section>
            </div>
        </div>
    @endif

    @if ($showHistory)
        <div class="fixed inset-0 z-50 overflow-y-auto" role="dialog" aria-modal="true">
            <div class="fixed inset-0 bg-slate-950/60" wire:click="closeHistory"></div>
            <div class="relative flex min-h-full items-center justify-center p-4">
                <section class="relative w-full max-w-2xl rounded-2xl bg-white p-5 shadow-2xl dark:bg-slate-900">
                    <h2 class="text-lg font-bold text-slate-900 dark:text-slate-100">Historial técnico</h2>
                    <div class="mt-4 max-h-[60vh] space-y-2 overflow-y-auto">
                        @forelse ($historyRecords as $record)
                            <article class="rounded-xl border border-slate-200 p-3 text-sm dark:border-slate-800">
                                <div class="flex justify-between gap-3"><strong class="text-slate-800 dark:text-slate-100">{{ $record['descripcion'] }}</strong><span class="whitespace-nowrap text-xs text-slate-400">{{ $record['fecha'] }}</span></div>
                                <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">{{ $record['usuario'] }} @if($record['resultado']) · {{ $record['resultado'] }} @endif</p>
                            </article>
                        @empty
                            <p class="py-8 text-center text-sm text-slate-500">No hay eventos registrados.</p>
                        @endforelse
                    </div>
                    <div class="mt-5 flex justify-end"><button type="button" wire:click="closeHistory" class="rounded-full border border-slate-300 px-4 py-2 text-sm font-semibold dark:border-slate-700 dark:text-slate-200">Cerrar</button></div>
                </section>
            </div>
        </div>
    @endif
</div>
