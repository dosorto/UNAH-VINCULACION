@php
    $isPdfMode = !empty($isPdf);

    // En pantalla la firma se sirve por URL; en el PDF se embebe como data URI
    // (base64), porque DomPDF no sigue el symlink `public/storage` y en
    // producción lo dejaría en blanco. Toda esa lógica vive en el helper.
    $cajaFirmaAncho = 160;
    $cajaFirmaAlto = 90;

    $resolverRutaFirma = fn (?string $ruta) => \App\Support\Fichas\FirmaImagen::resolver($ruta, $isPdfMode);

    // DomPDF no respeta `object-fit`, así que el tamaño "contain" (misma
    // proporción, mismo cuadro) se calcula acá y va como width/height en el <img>.
    $dimensionesContenidas = fn (?string $rutaArchivo) => \App\Support\Fichas\FirmaImagen::dimensionesContenidas($rutaArchivo, $cajaFirmaAncho, $cajaFirmaAlto);

    $formatearFechaFirma = function ($fecha) {
        if (empty($fecha)) {
            return '';
        }

        return \Carbon\Carbon::parse($fecha)
            ->locale('es')
            ->isoFormat('dddd D [de] MMMM [de] YYYY hh:mm:ss A');
    };

    // Todo formulario de proyecto tiene siempre estos 4 cuadros de firma fijos,
    // sin importar cómo esté configurado el flujo de revisión (ver
    // ConfiguracionFlujosProyectos::CARGOS_FIRMA_FIJOS). No se calculan desde
    // las etapas configurables para que el nombre/orden nunca cambie.
    $cuadrosFirma = [
        [
            'titulo' => 'Coordinador del proyecto por la UNAH',
            'pie' => 'Firma del profesor/a responsable del proyecto',
            'firma' => $proyecto->firma_coodinador_proyecto()->where('estado_revision', '!=', 'Anulado')->first(),
        ],
        [
            'titulo' => 'Jefe de la Unidad Académica que lidera el proyecto',
            'pie' => 'Firma del Jefe/a de la Unidad Académica que lidera el proyecto',
            'firma' => $proyecto->firma_proyecto_jefe()->where('estado_revision', '!=', 'Anulado')->first(),
        ],
        [
            'titulo' => 'Coordinador(a) del Comité de Vinculación de la Facultad o Unidad de Vinculación del Centro Regional',
            'pie' => 'Firma del coordinador del Comité Local',
            'firma' => $proyecto->firma_proyecto_enlace()->where('estado_revision', '!=', 'Anulado')->first(),
        ],
        [
            'titulo' => 'Decano(a) o Director(a) del Centro Regional',
            'pie' => 'Firma y sello del Decano(a) o Director(a)',
            'firma' => $proyecto->firma_proyecto_decano()->where('estado_revision', '!=', 'Anulado')->first(),
        ],
    ];
@endphp

<div class="section-title">VIII. FIRMAS</div>

@foreach (array_chunk($cuadrosFirma, 2) as $par)
    <table class="table_datos4">
        <tr>
            @foreach ($par as $cuadro)
                <td class="sub-header" colspan="2">{{ $cuadro['titulo'] }}</td>
            @endforeach
        </tr>
        <tr>
            @foreach ($par as $cuadro)
                <td class="full-width" colspan="1">Nombre:</td>
                <td class="full-width" colspan="1">
                    <input disabled type="text" class="input-field"
                        placeholder="Ingrese el nombre"
                        value="{{ optional($cuadro['firma'])->empleado?->nombre_completo }}"
                        disabled>
                </td>
            @endforeach
        </tr>
        <tr>
            @foreach ($par as $cuadro)
                @php
                    $sello = $resolverRutaFirma(optional(optional($cuadro['firma'])->sello)->ruta_storage);
                    $firmaImg = $resolverRutaFirma(optional(optional($cuadro['firma'])->firma)->ruta_storage);
                    $selloDim = $sello ? $dimensionesContenidas($sello['path']) : null;
                    $firmaDim = $firmaImg ? $dimensionesContenidas($firmaImg['path']) : null;
                @endphp
                <td class="full-width signature-image-cell" style="text-align: center;" colspan="2">
                    @if ($sello)
                        <img src="{{ $sello['src'] }}" alt="Sello de aprobación" width="{{ $selloDim['width'] }}" height="{{ $selloDim['height'] }}" style="width: {{ $selloDim['width'] }}px; height: {{ $selloDim['height'] }}px;">
                        <br>
                    @endif
                    @if ($firmaImg)
                        <img src="{{ $firmaImg['src'] }}" alt="Firma de aprobación" width="{{ $firmaDim['width'] }}" height="{{ $firmaDim['height'] }}" style="width: {{ $firmaDim['width'] }}px; height: {{ $firmaDim['height'] }}px;">
                    @endif
                    @if ($sello || $firmaImg)
                        <br>
                        <p class="signature-digital-caption">
                            Firmado digitalmente<br>
                            {{ $formatearFechaFirma(optional($cuadro['firma'])->fecha_firma) }}
                        </p>
                    @endif
                </td>
            @endforeach
        </tr>
        <tr>
            @foreach ($par as $cuadro)
                <th class="header" colspan="2">
                    {{ $cuadro['pie'] }}<br>
                    <span>{{ $formatearFechaFirma(optional($cuadro['firma'])->fecha_firma) }}</span>
                </th>
            @endforeach
        </tr>
    </table>
@endforeach
