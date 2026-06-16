@extends('layouts.panel.base')

@section('main')
    <div class="space-y-6">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h1 class="text-2xl font-bold text-slate-900 dark:text-slate-100">Educación No Formal</h1>
                <p class="text-sm text-slate-600 dark:text-slate-300">Acciones y formularios registrados en el módulo ENF.</p>
            </div>
            <a href="{{ route('enf.acciones.create', ['nuevo' => 1]) }}"
                class="inline-flex items-center justify-center rounded-md bg-blue-700 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-blue-800">
                Registrar ENF
            </a>
        </div>

        @if (session('status'))
            <div class="rounded-md border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800">
                {{ session('status') }}
            </div>
        @endif

        <div class="overflow-hidden rounded-lg border border-slate-200 bg-white shadow-sm dark:border-slate-700 dark:bg-slate-900">
            <table class="min-w-full divide-y divide-slate-200 text-sm dark:divide-slate-700">
                <thead class="bg-slate-50 text-left text-xs font-semibold uppercase tracking-wide text-slate-600 dark:bg-slate-800 dark:text-slate-300">
                    <tr>
                        <th class="px-4 py-3">Nombre</th>
                        <th class="px-4 py-3">Formulario</th>
                        <th class="px-4 py-3">Modalidad</th>
                        <th class="px-4 py-3">Centro/Facultad</th>
                        <th class="px-4 py-3">Estado</th>
                        <th class="px-4 py-3"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                    @forelse ($acciones as $accion)
                        <tr>
                            <td class="px-4 py-3 font-medium text-slate-900 dark:text-slate-100">{{ $accion->nombre_accion }}</td>
                            <td class="px-4 py-3 text-slate-700 dark:text-slate-300">{{ $accion->codigo_formulario ?? '-' }}</td>
                            <td class="px-4 py-3 text-slate-700 dark:text-slate-300">{{ $accion->modalidad?->nombre ?? 'Sin definir' }}</td>
                            <td class="px-4 py-3 text-slate-700 dark:text-slate-300">{{ $accion->centroFacultad?->nombre ?? 'Sin definir' }}</td>
                            <td class="px-4 py-3 text-slate-700 dark:text-slate-300">{{ $accion->estado_flujo }}</td>
                            <td class="px-4 py-3 text-right">
                                <a href="{{ route('enf.acciones.show', $accion) }}" class="font-semibold text-blue-700 hover:text-blue-900">Ver</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-4 py-8 text-center text-slate-500">Todavía no hay acciones ENF registradas.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{ $acciones->links() }}
    </div>
@endsection
