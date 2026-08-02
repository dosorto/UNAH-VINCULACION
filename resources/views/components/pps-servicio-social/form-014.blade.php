@php
    use Illuminate\Support\Carbon;

    $isPdf = (bool) ($isPdf ?? false);
    $formData = $formData ?? \App\Support\PpsServicioSocial\FormDvus014Data::from($registro);
    $fields = $formData['fields'] ?? [];
    $checked = $formData['checked'] ?? [];
    $registro = (object) $fields;

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

    $cb = fn (bool $on): string => '<span class="cb">'.($on ? 'X' : '').'</span>';

    $fechaRegistro = $registro->fecha_registro ?? null;

    $headerPartial = 'components.pps-servicio-social.partials.form-014-header';
    $contentPartial = 'components.pps-servicio-social.partials.form-014-content';
@endphp

@include('components.fichas.partials.institutional-pdf-chrome-styles')

<style>
    @if($isPdf)
        @page {
            size: letter portrait;
            margin: 35mm 9mm 13mm;
        }
    @endif

    .fdv {
        background: #fff;
        color: #111;
        font-family: Arial, Helvetica, "DejaVu Sans", sans-serif;
        font-size: {{ $isPdf ? '7pt' : '9px' }};
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
        min-height: auto;
        padding: {{ $isPdf ? '0' : '20px' }};
        border: 1px solid {{ $isPdf ? '#d1d5db' : '#ccc' }};
        box-shadow: {{ $isPdf ? '0 12px 26px rgba(15, 23, 42, 0.12)' : 'none' }};
        overflow: {{ $isPdf ? 'hidden' : 'visible' }};
    }

    .fdv .content { position: relative; z-index: 1; }

    .fdv .section { margin-top: {{ $isPdf ? '1.5mm' : '10px' }}; page-break-inside: auto; }
    .fdv .section--page-break { page-break-before: always; }
    .fdv .section-bar {
        background: #001b44;
        color: #fff;
        font-weight: bold;
        text-transform: uppercase;
        font-size: {{ $isPdf ? '7.5pt' : '10px' }};
        padding: {{ $isPdf ? '.9mm 1.2mm' : '4px 9px' }};
        page-break-after: avoid;
    }

    .fdv table.grid { border-collapse: collapse; table-layout: fixed; width: 100%; }
    .fdv table.grid th,
    .fdv table.grid td {
        border: .5pt solid #374151;
        padding: {{ $isPdf ? '.85mm 1.1mm' : '3px 5px' }};
        vertical-align: middle;
        word-break: break-word;
        overflow-wrap: anywhere;
    }
    .fdv table.grid tr { page-break-inside: avoid; }

    .fdv .num {
        background: #001b44;
        color: #fff;
        font-weight: bold;
        text-align: center;
        font-size: {{ $isPdf ? '6.5pt' : '9px' }};
    }
    .fdv .num:after { content: "."; }
    .fdv .lbl {
        background: #001b44;
        color: #fff;
        font-weight: bold;
        font-size: {{ $isPdf ? '6.5pt' : '' }};
    }
    .fdv .lbl-g {
        background: #edf0f4;
        color: #111;
        font-weight: bold;
        font-size: {{ $isPdf ? '6.5pt' : '' }};
    }
    .fdv .subhdr {
        background: #001b44;
        color: #fff;
        font-weight: bold;
        text-align: center;
        font-size: {{ $isPdf ? '6.5pt' : '' }};
    }
    .fdv .subbar {
        background: #001b44;
        color: #fff;
        font-weight: bold;
        text-transform: uppercase;
        text-align: left;
        font-size: {{ $isPdf ? '6.5pt' : '' }};
    }
    .fdv .cas { background: #edf0f4; }
    .fdv .data { background: #fff; }
    .fdv .center { text-align: center; }

    .fdv .opt { display: inline-block; margin-right: 9px; white-space: nowrap; line-height: 1.5; }
    .fdv .opt-block { display: block; margin: 1px 0; }
    .fdv .cb {
        display: inline-block;
        width: 8pt;
        height: 8pt;
        border: .7pt solid #001b44;
        background: #fff;
        text-align: center;
        line-height: 7pt;
        font-size: 6.5pt;
        font-weight: bold;
        margin-right: 2pt;
        vertical-align: middle;
    }

    .fdv .sign { page-break-inside: avoid; }
    .fdv .sign td { height: {{ $isPdf ? '36px' : '70px' }}; vertical-align: top; text-align: center; width: 33.33%; }
    .fdv .sign .sline { border-top: 1px solid #111; margin: {{ $isPdf ? '20px' : '40px' }} 8% 3px; }
    .fdv .sign .scap { font-size: {{ $isPdf ? '6.2pt' : '8px' }}; color: #333; }
</style>

@if($isPdf)
    @include($headerPartial)
    @include('components.fichas.partials.institutional-pdf-watermark')
    <div class="fdv">
        <div class="content">
            @include($contentPartial)
        </div>
    </div>
    @include('components.fichas.partials.institutional-pdf-footer')
@else
    <div class="fdv-shell">
        <div class="fdv">
            <div class="sheet">
                @include($headerPartial)
                <div class="content">
                    @include($contentPartial)
                </div>
            </div>
        </div>
    </div>
@endif
