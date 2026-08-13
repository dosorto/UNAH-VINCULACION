@php
    $isPdfMode = !empty($isPdf);

    // DomPDF (motor usado para generar el PDF de la ficha) no soporta de forma
    // confiable `object-fit` en <img>, así que en vez de recortar por CSS se
    // calcula acá el tamaño "contain" (igual proporción, mismo cuadro) y se
    // aplica como width/height explícitos en la etiqueta <img>, que DomPDF sí
    // respeta siempre.
    $cajaFirmaAncho = 160;
    $cajaFirmaAlto = 90;

    $resolverRutaFirma = function (?string $ruta) use ($isPdfMode) {
        if (empty($ruta)) {
            return null;
        }

        // Imagen embebida como data URI (base64): getimagesize() la lee
        // directamente, no hace falta resolver ruta de archivo.
        if (str_starts_with($ruta, 'data:')) {
            return ['src' => $ruta, 'path' => $ruta];
        }

        $rutaNormalizada = ltrim($ruta, '/');

        if (str_starts_with($rutaNormalizada, 'storage/')) {
            $rutaNormalizada = substr($rutaNormalizada, strlen('storage/'));
        }

        $rutaPublica = public_path('storage/' . $rutaNormalizada);
        $rutaDiscoPublico = storage_path('app/public/' . $rutaNormalizada);

        if (filter_var($ruta, FILTER_VALIDATE_URL)) {
            return ['src' => $ruta, 'path' => $ruta];
        }

        if (is_file($ruta)) {
            return [
                'src' => $isPdfMode ? $ruta : asset(str_replace(public_path() . '/', '', $ruta)),
                'path' => $ruta,
            ];
        }

        if (is_file($rutaPublica)) {
            return [
                'src' => $isPdfMode ? $rutaPublica : asset('storage/' . $rutaNormalizada),
                'path' => $rutaPublica,
            ];
        }

        if (is_file($rutaDiscoPublico) || \Illuminate\Support\Facades\Storage::disk('public')->exists($rutaNormalizada)) {
            return [
                'src' => $isPdfMode ? $rutaPublica : \Illuminate\Support\Facades\Storage::url($rutaNormalizada),
                'path' => $rutaDiscoPublico,
            ];
        }

        if (!$isPdfMode) {
            return ['src' => \Illuminate\Support\Facades\Storage::url($rutaNormalizada), 'path' => $rutaDiscoPublico];
        }

        return null;
    };

    $dimensionesContenidas = function (?string $rutaArchivo) use ($cajaFirmaAncho, $cajaFirmaAlto) {
        // getimagesize() funciona tanto con rutas locales como con URLs
        // remotas (no hace falta is_file() antes, que solo sirve para rutas
        // locales y descartaba silenciosamente cualquier imagen por URL).
        $medidas = $rutaArchivo ? @getimagesize($rutaArchivo) : false;

        if (! $medidas) {
            return ['width' => $cajaFirmaAncho, 'height' => $cajaFirmaAlto];
        }

        [$anchoOriginal, $altoOriginal] = $medidas;

        if ($anchoOriginal <= 0 || $altoOriginal <= 0) {
            return ['width' => $cajaFirmaAncho, 'height' => $cajaFirmaAlto];
        }

        // Sin tope en 1: las imágenes chicas también se agrandan hasta tocar
        // el borde de la caja, para que todas ocupen el mismo espacio visual
        // sin importar el tamaño original que subió cada persona.
        $escala = min($cajaFirmaAncho / $anchoOriginal, $cajaFirmaAlto / $altoOriginal);

        return [
            'width' => (int) round($anchoOriginal * $escala),
            'height' => (int) round($altoOriginal * $escala),
        ];
    };

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
