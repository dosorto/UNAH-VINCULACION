@php
    $isPdfMode = !empty($isPdf);

    // En pantalla la firma se sirve por URL; en el PDF se usa una ruta local
    // autorizada por el chroot de DomPDF. Toda esa lógica vive en el helper.
    // Sello y firma van apilados dentro de un cuadro de alto fijo (ver .signature-image-cell):
    // cada imagen se ajusta a esta caja para que los 4 cuadros midan siempre lo mismo.
    $cajaFirmaAncho = 160;
    $cajaFirmaAlto = 60;

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
    // Una etapa puede habilitar a varias personas (todas las que tienen el rol), así que
    // hay una fila de firma por cada candidato. El cuadro le corresponde a quien firmó:
    // mientras nadie lo haga queda en blanco, para no mostrar como firmante a quien no firmó.
    $firmaDelCargo = fn ($relacion) => $relacion
        ->where('estado_revision', '!=', 'Anulado')
        ->get()
        ->firstWhere('estado_revision', 'Aprobado');

    $cuadrosFirma = [
        [
            'titulo' => 'Coordinador del proyecto por la UNAH',
            // El FORM-DVUS-015 usa "programa" en los pies de firma.
            'pie' => 'Firma del profesor/a responsable del ' . (!empty($esVoluntariado) ? 'programa' : 'proyecto'),
            'firma' => $firmaDelCargo($proyecto->firma_coodinador_proyecto()),
        ],
        [
            'titulo' => 'Jefe de la Unidad Académica que lidera el proyecto',
            'pie' => 'Firma del Jefe/a de la Unidad Académica que lidera el ' . (!empty($esVoluntariado) ? 'programa' : 'proyecto'),
            'firma' => $firmaDelCargo($proyecto->firma_proyecto_jefe()),
        ],
        [
            'titulo' => 'Coordinador(a) del Comité de Vinculación de la Facultad o Unidad de Vinculación del Centro Regional',
            'pie' => 'Firma del coordinador del Comité Local',
            'firma' => $firmaDelCargo($proyecto->firma_proyecto_enlace()),
        ],
        [
            'titulo' => 'Decano(a) o Director(a) del Centro Regional',
            'pie' => 'Firma y sello del Decano(a) o Director(a)',
            'firma' => $firmaDelCargo($proyecto->firma_proyecto_decano()),
        ],
    ];
@endphp

<div class="section-title">{{ $tituloFirmas ?? 'VIII. FIRMAS' }}</div>

@foreach (array_chunk($cuadrosFirma, 2) as $par)
    {{-- Anchos fijos (16% + 34% por cuadro) para que ambos cuadros de cada fila midan 50%. --}}
    <table class="table_datos4 signature-table">
        <colgroup>
            @foreach ($par as $cuadro)
                <col style="width: 16%;">
                <col style="width: 34%;">
            @endforeach
        </colgroup>
        <tr>
            @foreach ($par as $cuadro)
                <td class="sub-header signature-title-cell" colspan="2">{{ $cuadro['titulo'] }}</td>
            @endforeach
        </tr>
        <tr>
            @foreach ($par as $cuadro)
                <td class="full-width" colspan="1" width="16%">Nombre:</td>
                <td class="full-width" colspan="1" width="34%">
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
                    $firmaRegistro = $cuadro['firma'] ?? null;
                    // Las firmas antiguas pueden tener firma_id/sello_id nulos
                    // aunque el empleado sí conserve una firma activa.
                    $firmaSello = $firmaRegistro?->sello ?: $firmaRegistro?->empleado?->sello;
                    $firmaDigital = $firmaRegistro?->firma ?: $firmaRegistro?->empleado?->firma;
                    $sello = $resolverRutaFirma($firmaSello?->ruta_storage);
                    $firmaImg = $resolverRutaFirma($firmaDigital?->ruta_storage);
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
                <th class="header signature-caption-cell" colspan="2">
                    {{ $cuadro['pie'] }}<br>
                    <span>{{ $formatearFechaFirma(optional($cuadro['firma'])->fecha_firma) }}</span>
                </th>
            @endforeach
        </tr>
    </table>
@endforeach
