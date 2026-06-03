@extends('layouts.panel.base')

@section('main')
    <div class="mx-auto max-w-5xl space-y-6">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h1 class="text-2xl font-bold text-slate-900 dark:text-slate-100">{{ $accion->nombre_accion }}</h1>
                <p class="text-sm text-slate-600 dark:text-slate-300">Registro ENF #{{ $accion->id }} · {{ $accion->estado_flujo }}</p>
            </div>
            <a href="{{ route('enf.acciones.index') }}" class="text-sm font-semibold text-blue-700 hover:text-blue-900">Ver registros</a>
        </div>

        @if (session('status'))
            <div class="rounded-md border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800">
                {{ session('status') }}
            </div>
        @endif

        <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
            <div class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-700 dark:bg-slate-900">
                <h2 class="mb-3 text-sm font-bold uppercase text-slate-500">Datos generales</h2>
                <dl class="space-y-2 text-sm">
                    <div><dt class="font-semibold text-slate-700 dark:text-slate-200">Tipo</dt><dd>{{ $accion->tipoAccion?->nombre ?? 'Educación No Formal' }}</dd></div>
                    <div><dt class="font-semibold text-slate-700 dark:text-slate-200">Modalidad</dt><dd>{{ $accion->modalidad?->nombre ?? 'Sin definir' }}</dd></div>
                    <div><dt class="font-semibold text-slate-700 dark:text-slate-200">Centro/Facultad</dt><dd>{{ $accion->centroFacultad?->nombre ?? 'Sin definir' }}</dd></div>
                    <div><dt class="font-semibold text-slate-700 dark:text-slate-200">Carrera</dt><dd>{{ $accion->carrera?->nombre ?? 'Sin definir' }}</dd></div>
                </dl>
            </div>

            <div class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-700 dark:bg-slate-900">
                <h2 class="mb-3 text-sm font-bold uppercase text-slate-500">Fechas y horas</h2>
                <dl class="space-y-2 text-sm">
                    <div><dt class="font-semibold text-slate-700 dark:text-slate-200">Inicio</dt><dd>{{ $accion->fecha_inicio?->format('d/m/Y') ?? 'Sin definir' }}</dd></div>
                    <div><dt class="font-semibold text-slate-700 dark:text-slate-200">Finalización</dt><dd>{{ $accion->fecha_finalizacion?->format('d/m/Y') ?? 'Sin definir' }}</dd></div>
                    <div><dt class="font-semibold text-slate-700 dark:text-slate-200">Horas</dt><dd>{{ $accion->total_horas ?: ($accion->horas_teoricas + $accion->horas_practicas) }}</dd></div>
                </dl>
            </div>
        </div>

        <div class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-700 dark:bg-slate-900">
            <h2 class="mb-3 text-sm font-bold uppercase text-slate-500">Resumen</h2>
            <p class="whitespace-pre-line text-sm text-slate-700 dark:text-slate-300">{{ $accion->resumen ?: 'Sin resumen registrado.' }}</p>
        </div>
    </div>
@endsection
