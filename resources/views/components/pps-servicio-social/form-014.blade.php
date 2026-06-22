@php
    use Illuminate\Support\Carbon;

    $isPdf = (bool) ($isPdf ?? false);
    $formData = $formData ?? \App\Support\PpsServicioSocial\FormDvus014Data::from($registro);
    $fields = $formData['fields'] ?? [];
    $checked = $formData['checked'] ?? [];
    $registro = (object) $fields;

    // Valor de una celda de datos: texto escapado o espacio para conservar altura.
    $dato = function ($campo): string {
        $campo = trim((string) $campo);

        return $campo !== '' ? e($campo) : '&nbsp;';
    };

    $fechaParte = function ($campo, string $parte): string {
        if (!$campo) {
            return '';
        }

        try {
            $fecha = $campo instanceof \DateTimeInterface
                ? Carbon::instance($campo)
                : Carbon::parse($campo);

            return match ($parte) {
                'anio' => $fecha->format('Y'),
                'mes' => $fecha->format('m'),
                'dia' => $fecha->format('d'),
                default => $fecha->format('d/m/Y'),
            };
        } catch (\Throwable) {
            return (string) $campo;
        }
    };

    // Casilla dibujada por CSS (independiente de glifos de la fuente).
    $cb = fn (bool $on): string => '<span class="cb">'.($on ? 'X' : '').'</span>';

    $fechaRegistro = $registro->fecha_registro ?? null;

    $logoSrc = $isPdf ? public_path('images/Image/Imagen1.jpg') : asset('images/Image/Imagen1.jpg');
    $watermarkSrc = $isPdf ? public_path('images/Image/Sol.jpg') : asset('images/Image/Sol.jpg');
    $hayLogo = file_exists(public_path('images/Image/Imagen1.jpg'));
    $hayWatermark = file_exists(public_path('images/Image/Sol.jpg'));

    $contentPartial = 'components.pps-servicio-social.partials.form-014-content';
    $headerPartial = 'components.pps-servicio-social.partials.form-014-header';
@endphp

<style>
    @if($isPdf)
        @page {
            size: letter portrait;
            margin: 38mm 25.4mm 18mm 25.4mm;
        }
    @endif

    .fdv {
        background: #fff;
        color: #111;
        font-family: "DejaVu Sans", Arial, Helvetica, sans-serif;
        font-size: {{ $isPdf ? '8px' : '9px' }};
        line-height: 1.12;
    }

    .fdv * { box-sizing: border-box; }

    .fdv-shell {
        {{ $isPdf ? '' : 'background: #fff; display: flex; justify-content: center; margin-top: 20px; overflow-x: auto; padding: 0; width: 100%;' }}
    }

    .fdv .sheet {
        background: #fff;
        position: relative;
        width: {{ $isPdf ? '816px' : '100%' }};
        max-width: {{ $isPdf ? '816px' : '100%' }};
        margin: 0 auto;
        min-height: {{ $isPdf ? '1056px' : 'auto' }};
        padding: {{ $isPdf ? '26px 96px' : '20px' }};
        border: 1px solid {{ $isPdf ? '#d1d5db' : '#ccc' }};
        box-shadow: {{ $isPdf ? '0 12px 26px rgba(15, 23, 42, 0.12)' : 'none' }};
        overflow: {{ $isPdf ? 'hidden' : 'visible' }};
    }

    /* ---------- Encabezado / pie ---------- */
    .page-header {
        font-family: "DejaVu Sans", Arial, Helvetica, sans-serif;
        @if($isPdf)
            position: fixed;
            top: -34mm;
            left: 0;
            right: 0;
        @else
            margin-bottom: 8px;
        @endif
    }

    .hdr { border-collapse: collapse; width: 100%; }
    .hdr td { border: 0; padding: 0; vertical-align: middle; }
    .hdr-logo { width: 70%; }
    .hdr-logo img { width: 100%; max-width: {{ $isPdf ? '102mm' : '430px' }}; height: auto; }
    .hdr-contact {
        width: 30%;
        text-align: right;
        color: #002060;
        font-weight: bold;
        font-size: {{ $isPdf ? '6.6px' : '8px' }};
        line-height: 1.18;
    }
    .hdr-accent { width: 8px; background: #f4c542; }

    .code-bar {
        background: #002060;
        color: #fff;
        font-weight: bold;
        text-align: center;
        font-size: {{ $isPdf ? '9px' : '11px' }};
        letter-spacing: 0.5px;
        padding: {{ $isPdf ? '2px 0' : '3px 0' }};
        margin-top: {{ $isPdf ? '4px' : '6px' }};
    }

    /* ---------- Marca de agua ---------- */
    .watermark {
        opacity: 0.07;
        z-index: 0;
        @if($isPdf)
            position: fixed;
            top: 38%;
            left: 22%;
            width: 56%;
        @else
            position: absolute;
            top: 38%;
            left: 22%;
            width: 56%;
        @endif
    }

    .fdv .content { position: relative; z-index: 1; }

    /* ---------- Título ---------- */
    .fdv .doc-title {
        text-align: center;
        text-transform: uppercase;
        font-weight: 800;
        color: #000;
        font-size: {{ $isPdf ? '11px' : '13px' }};
        line-height: 1.12;
        margin: {{ $isPdf ? '0 auto 8px' : '4px auto 12px' }};
        max-width: 96%;
    }

    /* ---------- Barras de sección (romanos) ---------- */
    .fdv .section { margin-top: {{ $isPdf ? '7px' : '10px' }}; page-break-inside: auto; }
    .fdv .section-bar {
        background: #002060;
        color: #fff;
        font-weight: bold;
        text-transform: uppercase;
        font-size: {{ $isPdf ? '8.4px' : '10px' }};
        padding: {{ $isPdf ? '3px 7px' : '4px 9px' }};
        page-break-after: avoid;
    }

    /* ---------- Tablas ---------- */
    .fdv table.grid { border-collapse: collapse; table-layout: fixed; width: 100%; }
    .fdv table.grid th,
    .fdv table.grid td {
        border: 0.7px solid #9aa0aa;
        padding: {{ $isPdf ? '2px 3px' : '3px 5px' }};
        vertical-align: middle;
        word-break: break-word;
        overflow-wrap: anywhere;
    }
    .fdv table.grid tr { page-break-inside: avoid; }

    .fdv .num {
        background: #002060;
        color: #fff;
        font-weight: bold;
        text-align: center;
        font-size: {{ $isPdf ? '7.4px' : '9px' }};
    }
    /* La referencia numera con punto final: 1.  2.  14.1.  29.1. */
    .fdv .num:after { content: "."; }
    .fdv .lbl {
        background: #002060;
        color: #fff;
        font-weight: bold;
    }
    .fdv .lbl-g {
        background: #d9d9d9;
        color: #111;
        font-weight: bold;
    }
    .fdv .subhdr {
        background: #002060;
        color: #fff;
        font-weight: bold;
        text-align: center;
    }
    .fdv .subbar {
        background: #002060;
        color: #fff;
        font-weight: bold;
        text-transform: uppercase;
        text-align: left;
    }
    .fdv .cas { background: #d9d9d9; }
    .fdv .data { background: #fff; }
    .fdv .center { text-align: center; }

    .fdv .opt { display: inline-block; margin-right: 9px; white-space: nowrap; line-height: 1.5; }
    .fdv .opt-block { display: block; margin: 1px 0; }
    .fdv .cb {
        display: inline-block;
        width: 9px;
        height: 9px;
        border: 0.8px solid #002060;
        background: #fff;
        text-align: center;
        line-height: 8px;
        font-size: 7px;
        font-weight: bold;
        margin-right: 3px;
        vertical-align: middle;
    }

    /* ---------- Firmas ---------- */
    .fdv .sign td { height: {{ $isPdf ? '46px' : '70px' }}; vertical-align: top; text-align: center; width: 33.33%; }
    .fdv .sign .sline { border-top: 1px solid #111; margin: {{ $isPdf ? '24px' : '40px' }} 8% 3px; }
    .fdv .sign .scap { font-size: {{ $isPdf ? '6.8px' : '8px' }}; color: #333; }
</style>

@if($isPdf)
    {{-- En PDF, el encabezado / pie / marca de agua deben ser hijos directos de
         <body> para que dompdf los repita en todas las páginas. --}}
    <div class="page-header">
        @include($headerPartial)
    </div>

    @if($hayWatermark)
        <img class="watermark" src="{{ $watermarkSrc }}" alt="">
    @endif

    <div class="fdv">
        <div class="content">
            @include($contentPartial)
        </div>
    </div>

    {{-- Pie con numeración de página dibujado por dompdf en cada página. Va al
         final del cuerpo para que el total de páginas ya esté determinado.
         counter(pages) en CSS no resuelve el total dentro de elementos fixed,
         por eso se usa page_text() con los placeholders {PAGE_NUM}/{PAGE_COUNT}. --}}
    <script type="text/php">
        if (isset($pdf)) {
            $font = $fontMetrics->getFont("DejaVu Sans", "normal");
            $size = 7;
            $color = [0.29, 0.33, 0.39];
            $w = $pdf->get_width();
            $h = $pdf->get_height();
            $left = 72;          // 1" margen izquierdo
            $right = $w - 72;    // 1" margen derecho
            $y = $h - 46;        // dentro del margen inferior
            $pdf->line($left, $y - 4, $right, $y - 4, [0.72, 0.74, 0.77], 0.7);
            $pdf->page_text($left, $y, "FORM-DVUS-014", $font, $size, $color);
            $texto = "Página {PAGE_NUM} de {PAGE_COUNT}";
            $tw = $fontMetrics->get_text_width($texto, $font, $size);
            $pdf->page_text($right - $tw, $y, $texto, $font, $size, $color);
        }
    </script>
@else
    <div class="fdv-shell">
        <div class="fdv">
            <div class="sheet">
                @if($hayWatermark)
                    <img class="watermark" src="{{ $watermarkSrc }}" alt="">
                @endif

                <div class="page-header">
                    @include($headerPartial)
                </div>

                <div class="content">
                    @include($contentPartial)
                </div>
            </div>
        </div>
    </div>
@endif
