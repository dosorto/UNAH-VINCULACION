<x-app-layout>
    <div class="mx-auto max-w-2xl px-4 py-12">
        <div class="rounded-xl border {{ $vigente ? 'border-emerald-200 bg-emerald-50' : 'border-rose-200 bg-rose-50' }} p-6 shadow-sm">
            <p class="text-sm font-semibold uppercase tracking-wide {{ $vigente ? 'text-emerald-700' : 'text-rose-700' }}">{{ $vigente ? 'Constancia vigente' : 'Constancia no vigente' }}</p>
            <h1 class="mt-2 text-xl font-bold text-gray-900">Verificación de Constancia de Finalización</h1>
            <dl class="mt-6 grid gap-4 text-sm sm:grid-cols-2">
                <div><dt class="text-gray-500">Número</dt><dd class="font-semibold">{{ $datos['numero'] }}</dd></div>
                <div><dt class="text-gray-500">Tipo</dt><dd class="font-semibold">{{ $datos['tipo'] }}</dd></div>
                <div><dt class="text-gray-500">Proyecto</dt><dd>{{ $datos['proyecto'] }}</dd></div>
                <div><dt class="text-gray-500">Código</dt><dd>{{ $datos['codigo'] }}</dd></div>
                <div><dt class="text-gray-500">Unidad académica</dt><dd>{{ $datos['unidad'] }}</dd></div>
                <div><dt class="text-gray-500">Fecha de emisión</dt><dd>{{ $datos['fecha_emision'] }}</dd></div>
            </dl>
        </div>
    </div>
</x-app-layout>
