@if ($documentoProyecto && $documentoProyecto->documento_url != null)
<details class="rounded-xl border border-gray-200 bg-white shadow-sm">
    <summary class="flex cursor-pointer list-none items-center justify-between gap-x-4 px-6 py-4">
        <div>
            <span class="text-sm font-semibold text-gray-900">
                Documento {{$documentoProyecto->tipo_documento}}:
                Estado: {{$documentoProyecto->estado->tipoestado->nombre}}
            </span>
            <p class="mt-1 text-sm font-normal text-gray-500">{{$documentoProyecto->estado->comentario}}</p>
        </div>
        <svg class="h-5 w-5 flex-shrink-0 text-gray-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
            <path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 011.06.02L10 11.168l3.71-3.938a.75.75 0 111.08 1.04l-4.25 4.5a.75.75 0 01-1.08 0l-4.25-4.5a.75.75 0 01.02-1.06z" clip-rule="evenodd" />
        </svg>
    </summary>
    <div class="border-t border-gray-200 px-6 py-4">
        <embed src="{{ asset('storage/' . $documentoProyecto->documento_url) }}" type="application/pdf" width="100%" height="600px" />
    </div>
</details>
@endif