@if ($documentoProyecto && $documentoProyecto->documento_url != null)
@php
    $documentoUrl = asset('storage/' . $documentoProyecto->documento_url);
    $extension = strtolower(pathinfo($documentoProyecto->documento_url, PATHINFO_EXTENSION));
    $estado = $documentoProyecto->estadoActual ?? $documentoProyecto->estado;
@endphp
<details class="rounded-xl border border-gray-200 bg-white shadow-sm">
    <summary class="flex cursor-pointer list-none items-center justify-between gap-x-4 px-6 py-4">
        <div>
            <span class="text-sm font-semibold text-gray-900">
                Documento {{$documentoProyecto->tipo_documento}}:
                Estado: {{$estado?->tipoestado?->nombre ?? 'Sin estado'}}
            </span>
            <p class="mt-1 text-sm font-normal text-gray-500">{{$estado?->comentario ?? 'Sin comentario'}}</p>
        </div>
        <svg class="h-5 w-5 flex-shrink-0 text-gray-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
            <path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 011.06.02L10 11.168l3.71-3.938a.75.75 0 111.08 1.04l-4.25 4.5a.75.75 0 01-1.08 0l-4.25-4.5a.75.75 0 01.02-1.06z" clip-rule="evenodd" />
        </svg>
    </summary>
    <div class="border-t border-gray-200 px-6 py-4">
        @if ($extension === 'pdf')
            <iframe src="{{ $documentoUrl }}" type="application/pdf" width="100%" height="600" class="rounded-lg border border-gray-200"></iframe>
        @elseif (in_array($extension, ['jpg', 'jpeg', 'png', 'gif', 'webp']))
            <img src="{{ $documentoUrl }}" alt="Documento {{ $documentoProyecto->tipo_documento }}" class="max-h-[70vh] w-auto rounded-lg border border-gray-200">
        @else
            <div class="rounded-lg border border-amber-200 bg-amber-50 p-4 text-sm text-amber-800">
                Este tipo de archivo no puede previsualizarse en el navegador.
            </div>
        @endif

        <div class="mt-4 flex flex-wrap gap-2">
            <a href="{{ $documentoUrl }}" target="_blank" rel="noopener"
               class="inline-flex items-center px-3 py-1.5 text-sm font-medium rounded-md bg-blue-600 text-white hover:bg-blue-700">
                Abrir en nueva pestaña
            </a>
            <a href="{{ $documentoUrl }}" download
               class="inline-flex items-center px-3 py-1.5 text-sm font-medium rounded-md bg-gray-100 text-gray-700 hover:bg-gray-200">
                Descargar
            </a>
        </div>
    </div>
</details>
@endif
