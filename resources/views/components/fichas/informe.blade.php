@if ($documentoProyecto && $documentoProyecto->documento_url != null)
<x-filament::section collapsible collapsed persist-collapsed id="user-details">
    <x-slot name="heading">
        Documento {{$documentoProyecto->tipo_documento}}: 
         Estado: {{$documentoProyecto->estado->tipoestado->nombre}}
    </x-slot>
    <x-slot name="description">
        {{$documentoProyecto->estado->comentario}}
    </x-slot>
    <embed src="{{ asset('storage/' . $documentoProyecto->documento_url) 
        
        }}" type="application/pdf" width="100%"
        height="600px" />
</x-filament::section>
@endif