@php
    $isPdfMode = !empty($isPdf);
    $procesoFirmas = $proceso ?? \App\Models\Proyecto\Proyecto::FLUJO_INSCRIPCION;
    $documentoFirmas = $documento ?? null;
    $filasFirmas = $proyecto->firmasParaFicha($procesoFirmas, $documentoFirmas);

    // En pantalla devuelve una URL; en el PDF, un data URI base64 (DomPDF no
    // sigue el symlink `public/storage`). La lógica vive en el helper.
    $resolverRutaFirma = fn (?string $ruta) => \App\Support\Fichas\FirmaImagen::resolver($ruta, $isPdfMode);

    $formatearFechaFirma = function ($fecha) {
        if (empty($fecha)) {
            return '';
        }

        return \Carbon\Carbon::parse($fecha)
            ->locale('es')
            ->isoFormat('dddd D [de] MMMM [de] YYYY hh:mm:ss A');
    };
@endphp

<div class="section-title">VIII. FIRMAS</div>

@if ($filasFirmas->isEmpty())
    <table class="table_datos4">
        <tr>
            <td class="full-width" colspan="4">Este flujo no tiene etapas de aprobación (firma) configuradas.</td>
        </tr>
    </table>
@else
    @foreach ($filasFirmas->chunk(2) as $par)
        <table class="table_datos4">
            <tr>
                @foreach ($par as $fila)
                    <td class="sub-header" colspan="2">{{ $fila['etapa']->nombre }}</td>
                @endforeach
            </tr>
            <tr>
                @foreach ($par as $fila)
                    <td class="full-width" colspan="1">Nombre:</td>
                    <td class="full-width" colspan="1">
                        <input disabled type="text" class="input-field"
                            placeholder="Ingrese el nombre"
                            value="{{ ($fila['adoptada_antes'] ?? false) ? 'Completada antes de la adopción al flujo' : optional($fila['firma'])->empleado?->nombre_completo }}"
                            disabled>
                    </td>
                @endforeach
            </tr>
            <tr>
                @foreach ($par as $fila)
                    @php
                        $firmaRegistro = $fila['firma'] ?? null;
                        $firmaSello = $firmaRegistro?->sello ?: $firmaRegistro?->empleado?->sello;
                        $firmaDigital = $firmaRegistro?->firma ?: $firmaRegistro?->empleado?->firma;
                        $sello = $resolverRutaFirma($firmaSello?->ruta_storage);
                        $firmaImg = $resolverRutaFirma($firmaDigital?->ruta_storage);
                    @endphp
                    <td class="full-width signature-image-cell" colspan="2">
                        @if ($sello)
                            <img src="{{ $sello['src'] }}" alt="Sello de aprobación">
                        @endif
                        @if ($firmaImg)
                            <img src="{{ $firmaImg['src'] }}" alt="Firma de aprobación">
                        @endif
                        @if ($sello || $firmaImg)
                            <br>
                            <p class="signature-digital-caption">
                                Firmado digitalmente<br>
                                {{ $formatearFechaFirma(optional($fila['firma'])->fecha_firma) }}
                            </p>
                        @elseif($fila['adoptada_antes'] ?? false)
                            <p class="signature-digital-caption">
                                Antecedente legacy conservado sin crear una firma digital ficticia.
                            </p>
                        @endif
                    </td>
                @endforeach
            </tr>
            <tr>
                @foreach ($par as $fila)
                    <th class="header" colspan="2">
                        Firma y sello — {{ $fila['etapa']->nombre }}<br>
                        <span>{{ $formatearFechaFirma(optional($fila['firma'])->fecha_firma) }}</span>
                    </th>
                @endforeach
            </tr>
        </table>
    @endforeach
@endif
