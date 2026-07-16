@extends('layouts.panel.base')

@section('main')
    <div class="mx-auto max-w-6xl space-y-6">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h1 class="text-2xl font-bold text-slate-900 dark:text-slate-100">Educación No Formal</h1>
                <p class="text-sm text-slate-600 dark:text-slate-300">Seleccione el tipo de acción que desea registrar.</p>
            </div>
            <a href="{{ route('selectorTipoAccion') }}" class="text-sm font-semibold text-blue-700 hover:text-blue-900">Volver</a>
        </div>

        <div class="grid grid-cols-1 gap-6 md:grid-cols-3">
            @forelse ($tipos as $tipo)
                @php
                    $esCertificado = $tipo->nombre === 'Certificado universitario';
                    $estaDisponible = ! $esCertificado || $formCertificadoUniversitarioDisponible;
                    $formQuery = $esCertificado ? ['form' => '016'] : [];
                    $codigoFormulario = $esCertificado ? 'FORM-DVUS-016' : 'FORM-DVUS-018';
                @endphp
                @if ($estaDisponible)
                    <a href="{{ route('enf.acciones.create', array_merge(['tipo_accion_enf_id' => $tipo->id], $esCertificado ? [] : ['nuevo' => 1], $formQuery)) }}"
                        class="group relative block rounded-xl border border-orange-200 bg-white p-8 shadow-lg transition-all duration-300 hover:scale-105 hover:shadow-2xl dark:border-orange-700 dark:bg-gray-800">
                @else
                    <div class="group relative block rounded-xl border border-gray-200 bg-white p-8 opacity-40 shadow-lg transition-all duration-300 dark:border-gray-700 dark:bg-gray-800">
                @endif
                    <span class="absolute right-4 top-4 rounded-full {{ $estaDisponible ? 'bg-orange-100 text-orange-700 transition group-hover:bg-orange-200 dark:bg-orange-900 dark:text-orange-300' : 'bg-gray-100 text-gray-700 dark:bg-gray-900 dark:text-gray-300' }} px-3 py-1 text-xs font-semibold">
                        {{ $estaDisponible ? 'ENF' : 'Próximamente' }}
                    </span>
                    <div class="flex flex-col items-center text-center">
                        <div class="mb-5 rounded-full {{ $estaDisponible ? 'bg-orange-100 transition group-hover:bg-orange-200 dark:bg-orange-900' : 'bg-gray-100 dark:bg-gray-900' }} p-4">
                            <svg class="h-10 w-10 {{ $estaDisponible ? 'text-orange-700 dark:text-orange-300' : 'text-gray-600 dark:text-gray-300' }}" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5S19.832 5.477 21 6.253v13C19.832 18.477 18.246 18 16.5 18s-3.332.477-4.5 1.253" />
                            </svg>
                        </div>
                        <h2 class="mb-2 text-xl font-bold {{ $estaDisponible ? 'text-orange-900 dark:text-orange-200' : 'text-gray-900 dark:text-gray-100' }}">{{ $tipo->nombre }}</h2>
                        <p class="text-sm text-gray-600 dark:text-gray-300">
                            Registrar {{ strtolower($tipo->nombre) }} mediante {{ $codigoFormulario }}.
                        </p>
                    </div>
                @if ($estaDisponible)
                    </a>
                @else
                    </div>
                @endif
            @empty
                <div class="rounded-lg border border-yellow-200 bg-yellow-50 p-4 text-sm text-yellow-800">
                    No hay tipos ENF configurados. Ejecute el seeder de catálogos ENF.
                </div>
            @endforelse
        </div>
    </div>
@endsection
