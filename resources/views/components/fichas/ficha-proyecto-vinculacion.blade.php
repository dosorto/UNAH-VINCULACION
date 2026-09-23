@php
    // FORM-DVUS-015 (Voluntariado Académico) reutiliza esta ficha en "modo voluntariado":
    // alterna encabezado, numeración de secciones, nombre de archivo del PDF y los campos
    // exclusivos del formato 015.
    $esVoluntariado = $proyecto->esVoluntariado();
    $codigoFormulario = $esVoluntariado ? 'FORM-DVUS-015' : 'FORM-DVUS-001';
    $tituloFormulario = $esVoluntariado
        ? 'Registro de Proyectos de Voluntariado Académico'
        : 'Registro de Proyectos de Vinculación';
@endphp
{{-- Al incrustarse en una pantalla («embebido») no se emite el documento completo: un
     segundo <html> dentro de la página hace que Livewire y Alpine se carguen dos veces. --}}
@unless($embebido ?? false)
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=3, user-scalable=yes">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>{{ $codigoFormulario }} - {{ $tituloFormulario }}</title>
@endunless
    @if (!empty($isPdf))
        <style>
            {!! file_get_contents(public_path('css/app/fichaHistorial.css')) !!}
        </style>
        <style>
            @page {
                size: letter portrait;
                margin: 8mm 6mm;
            }
            body {
                background: #fff !important;
                margin: 0;
                padding: 0;
                color: #111;
                font-size: 9px;
                line-height: 1.2;
            }
            .container {
                width: 100% !important;
                max-width: 100% !important;
                margin: 0 auto !important;
                border: 1px solid #ccc !important;
                padding: 8px !important;
                box-sizing: border-box !important;
            }
            .header img {
                max-width: 100% !important;
                height: auto !important;
            }
            .table_datos1, .table_datos2, .table_datos3, .table_datos4, .table_datos5, .table_datos6, .table_datos7 {
                width: 100% !important;
                border-collapse: collapse !important;
                table-layout: fixed !important;
                page-break-inside: auto !important;
                break-inside: auto !important;
            }
            .header-table {
                width: 100% !important;
                border-collapse: collapse !important;
                table-layout: fixed !important;
                margin-bottom: 8px !important;
            }
            .logos-cell {
                text-align: center !important;
                padding-bottom: 12px !important;
            }
            .header-logo {
                width: 500px !important;
                max-width: 100% !important;
                height: auto !important;
            }
            .title-cell {
                width: 78% !important;
                font-size: 12px !important;
                font-weight: bold !important;
                line-height: 1.25 !important;
                text-align: center !important;
                vertical-align: top !important;
                padding-right: 10px !important;
                white-space: normal !important;
                word-break: normal !important;
            }
            .contact-cell {
                width: 22% !important;
                font-size: 8.5px !important;
                font-weight: bold !important;
                color: #001b5d !important;
                line-height: 1.2 !important;
                text-align: right !important;
                vertical-align: top !important;
                white-space: normal !important;
                word-break: break-word !important;
            }
            .pdf-content-wrapper {
                display: block !important;
                margin-top: 0 !important;
                background: #fff !important;
            }
            .pdf-table {
                width: 100% !important;
                border-collapse: collapse !important;
                table-layout: fixed !important;
                page-break-inside: auto !important;
                break-inside: auto !important;
            }
            .info-general-table {
                table-layout: fixed !important;
            }
            .info-general-table .info-col-label {
                width: 20% !important;
            }
            .info-general-table .info-col-detail {
                width: 16% !important;
            }
            .info-general-table th,
            .info-general-table td,
            .info-general-table .full-width,
            .info-general-table .full-width1,
            .info-general-table .sub-header,
            .info-general-table .sub-header1 {
                width: auto !important;
            }
            .pdf-table td,
            .pdf-table th {
                border: 1px solid #000 !important;
                padding: 3px 5px !important;
                vertical-align: top !important;
                line-height: 1.15 !important;
                height: auto !important;
                max-height: none !important;
                overflow: visible !important;
            }
            .pdf-section-avoid-break {
                page-break-inside: auto !important;
                break-inside: auto !important;
            }
            .section-title {
                page-break-after: avoid !important;
            }
            .table_datos1 td, .table_datos1 th,
            .table_datos2 td, .table_datos2 th,
            .table_datos3 td, .table_datos3 th,
            .table_datos4 td, .table_datos4 th,
            .table_datos5 td, .table_datos5 th,
            .table_datos6 td, .table_datos6 th,
            .table_datos7 td, .table_datos7 th {
                word-break: break-word !important;
                overflow-wrap: anywhere !important;
                white-space: normal !important;
                padding: 2px 3px !important;
                vertical-align: top !important;
                font-size: 8.5px !important;
                height: auto !important;
                max-height: none !important;
                overflow: visible !important;
            }
            tr, td, th {
                page-break-inside: auto !important;
                break-inside: auto !important;
            }
            thead {
                display: table-header-group;
            }
            h1 { font-size: 12px !important; line-height: 1.2 !important; margin: 0 0 6px 0 !important; }
            p, .section-title, .detalles, .contact-info, .date-part .date-label {
                font-size: 8.5px !important;
                line-height: 1.2 !important;
            }
            input.input-field,
            textarea.input-field,
            textarea.input-field-multiline,
            .pdf-text-block {
                border: none !important;
                background: transparent !important;
                box-shadow: none !important;
                padding: 0 !important;
                width: 100% !important;
                min-width: 0 !important;
                height: auto !important;
                max-height: none !important;
                font-size: 9px !important;
                line-height: 1.25 !important;
                overflow: visible !important;
                white-space: normal !important;
                word-break: break-word !important;
                overflow-wrap: anywhere !important;
            }
            .pdf-text-block {
                display: block !important;
                box-sizing: border-box !important;
            }
            iframe, embed, .no-print, .fi-btn, .fi-modal {
                display: none !important;
            }
        </style>
    @else
        <link rel="stylesheet" href="{{ asset('css/app/fichaVinculacion.css') }}">
    @endif
    <style>
        .date-cell {
            padding: 0 !important;
            vertical-align: middle !important;
            overflow: visible !important;
        }

        .date-inner-table {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
            margin: 0;
        }

        .date-inner-table th,
        .date-inner-table td {
            width: 33.33%;
            text-align: center;
            vertical-align: middle;
            border: 0 !important;
            border-right: 1px solid #000 !important;
            padding: 3px 2px !important;
            line-height: 1.1 !important;
            white-space: normal !important;
            overflow: visible !important;
        }

        .date-inner-table th {
            background-color: #002060;
            color: #fff;
            font-weight: bold;
            border-bottom: 1px solid #000 !important;
        }

        .date-inner-table th:last-child,
        .date-inner-table td:last-child {
            border-right: 0 !important;
        }

        .execution-dates-table {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
            margin: 0;
        }

        .execution-dates-table td {
            border: 0 !important;
            border-right: 1px solid #000 !important;
            padding: 0 !important;
            vertical-align: middle !important;
            overflow: visible !important;
        }

        .execution-dates-table td:last-child {
            border-right: 0 !important;
        }

        .execution-date-label {
            width: 18%;
            background-color: #ebeeef;
            font-style: italic;
            padding: 3px 4px !important;
        }

        .execution-date-value {
            width: 32%;
        }

        .form-header-table {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
            margin-bottom: 8px;
        }

        .form-header-table td {
            border: 0;
        }

        .form-header-brand {
            text-align: center;
            padding-bottom: 10px;
        }

        .form-header-brand img {
            width: 76%;
            height: auto;
        }

        .form-header-title {
            width: 74%;
            color: #001b5d;
            font-size: 12px;
            font-weight: bold;
            line-height: 1.2;
            text-align: center;
            vertical-align: top;
        }

        .form-header-contact {
            width: 26%;
            color: #001b5d;
            font-size: 9px;
            font-weight: bold;
            line-height: 1.25;
            padding-left: 12px;
            text-align: left;
            vertical-align: top;
            border-left: 1px solid #001b5d !important;
        }

        .signature-image-cell,
        .signature-name-cell {
            text-align: center;
        }

        /* Los 4 cuadros de firma miden lo mismo: columnas fijas y alto fijo por fila. */
        .signature-table {
            table-layout: fixed;
            width: 100%;
        }

        .signature-title-cell,
        .signature-caption-cell {
            height: 56px;
            vertical-align: middle !important;
        }

        .signature-image-cell {
            /* Sello (60px) + firma (60px) + leyenda de 2 líneas caben sin estirar la fila. */
            height: 230px;
            vertical-align: middle;
        }

        .signature-image-cell .signature-digital-caption {
            margin: 4px 0 0;
        }

        .signature-image-cell img {
            display: block;
            max-width: 160px;
            max-height: 60px;
            object-fit: contain;
            margin: 0 auto;
        }

        @media print {
            .no-print {
                display: none !important;
            }
        }
    </style>
    @if (!empty($isPdf))
        @include('components.fichas.partials.form-dvus-001-pdf-styles')
        <style>
            .table_datos1 td, .table_datos1 th,
            .table_datos2 td, .table_datos2 th,
            .table_datos3 td, .table_datos3 th,
            .table_datos4 td, .table_datos4 th,
            .table_datos5 td, .table_datos5 th,
            .table_datos6 td, .table_datos6 th,
            .table_datos7 td, .table_datos7 th,
            .pdf-table td, .pdf-table th,
            .beneficiary-summary td, .beneficiary-summary th,
            .beneficiary-ethnicity td, .beneficiary-ethnicity th {
                box-sizing: border-box !important;
            }
            .section-title {
                background: #002060 !important;
                border-left: 2.4mm solid #ffc400 !important;
                color: #fff !important;
            }
            .section3 .sub-header {
                background: #d6dde3 !important;
            }
            .section3 .full-width1 {
                font-style: italic !important;
            }
        </style>
    @endif
@unless($embebido ?? false)
</head>

<body style="background-color: #f2f2f2; ">
@endunless
    @php
        $encabezadoFicha = $esVoluntariado
            ? [
                'institutionalTitle' => 'FORMULARIO DE REGISTRO DE PROYECTOS DE',
                'institutionalSubtitle' => 'VOLUNTARIADO ACADÉMICO',
                'institutionalCode' => 'FORM-DVUS-015',
                'institutionalPhone' => '2216-7070 Ext. 110576',
            ]
            : [];
        $renderPdfText = static function ($value, string $fallback = '') {
            $text = filled($value) ? (string) $value : $fallback;

            return nl2br(e($text));
        };
        $pdfCheck = static fn (bool $checked) => new \Illuminate\Support\HtmlString(
            '<span class="pdf-check'.($checked ? ' is-checked' : '').'">'.($checked ? 'X' : '&nbsp;').'</span>'
        );

        // Numeración de ítems y secciones. El formato FORM-DVUS-001 y el FORM-DVUS-015
        // difieren: el 015 añade "Temática principal" (I.6) y "Metodología de seguimiento"
        // (I.10), lo que corre toda la numeración posterior, y suma la sección VI
        // "Uso de espacios institucionales". Este helper mantiene el 001 intacto.
        $numItem001 = [
            'fecha_registro' => '1', 'nombre' => '2', 'unidad' => '3', 'modalidad' => '4',
            'alineamiento' => '5', 'tematica' => null, 'fecha_ejecucion' => '6',
            'beneficiarios' => '7', 'sitio' => '8', 'metodologia_seguimiento' => null,
            'coordinador' => '9', 'docentes' => '10', 'internacionales' => '11',
            'estudiantes' => '12', 'vol_personal' => '13', 'vol_internacional' => '14',
            'detalle_practica' => '15', 'contraparte_nombre' => '16', 'contraparte_tipo' => '17',
            'contraparte_contacto' => '18', 'contraparte_cargo' => '19', 'contraparte_instrumento' => '20',
            'contraparte_compromisos' => '21', 'antecedentes' => '22', 'participantes' => '23',
            'problema' => '24', 'objetivo_general' => '25', 'objetivos_especificos' => '26',
            'resultados' => '27', 'ods' => '28', 'alineamiento_reforma' => '29',
            'experiencia' => null, 'metodologia' => '30', 'bibliografia' => '31',
            'actividades' => '32', 'aporte_institucional' => '33', 'otras_aportaciones' => '34',
        ];
        $numItem015 = [
            'fecha_registro' => '1', 'nombre' => '2', 'unidad' => '3', 'modalidad' => '4',
            'alineamiento' => '5', 'tematica' => '6', 'fecha_ejecucion' => '7',
            'beneficiarios' => '8', 'sitio' => '9', 'metodologia_seguimiento' => '10',
            'coordinador' => '11', 'docentes' => '12', 'internacionales' => '13',
            'estudiantes' => '14', 'vol_personal' => '15', 'vol_internacional' => '16',
            'detalle_practica' => '17', 'contraparte_nombre' => '18', 'contraparte_tipo' => '19',
            'contraparte_contacto' => '20', 'contraparte_cargo' => '21', 'contraparte_instrumento' => '22',
            'contraparte_compromisos' => '23', 'antecedentes' => '24', 'participantes' => '25',
            'problema' => '26', 'objetivo_general' => '27', 'objetivos_especificos' => '28',
            'resultados' => '29', 'ods' => '30', 'alineamiento_reforma' => '31',
            'experiencia' => '32', 'metodologia' => '33', 'bibliografia' => '34',
            'actividades' => '1', 'aporte_institucional' => '2', 'otras_aportaciones' => '3',
        ];
        $numItem = static fn (string $clave) => ($esVoluntariado ? $numItem015 : $numItem001)[$clave] ?? '';

        // Secciones romanas. El 015 intercala "VI. Uso de espacios" antes del cronograma.
        $numSec = static fn (string $clave) => match ($clave) {
            'espacios'    => $esVoluntariado ? 'VI. ' : '',
            'cronograma'  => $esVoluntariado ? 'VII. ' : 'VI. ',
            'presupuesto' => $esVoluntariado ? 'VIII. ' : 'VII. ',
            'firmas'      => $esVoluntariado ? 'IX. ' : '',
            'anexos'      => $esVoluntariado ? '' : 'XI. ',
            default       => '',
        };
    @endphp
    @if (empty($isPdf) && empty($hideEmbeddedDocuments) && $proyecto->documento_intermedio() && $proyecto->documento_intermedio()->documento_url != null)
        <details class="rounded-xl border border-gray-200 bg-white shadow-sm">
            <summary class="flex cursor-pointer list-none items-center justify-between gap-x-4 px-6 py-4">
                <div>
                    <span class="text-sm font-semibold text-gray-900">Informe Intermedio, Estado: {{ $proyecto->documento_intermedio()->estado?->tipoestado?->nombre ?? 'No especificado' }}</span>
                    <p class="mt-1 text-sm font-normal text-gray-500">{{ $proyecto->documento_intermedio()->estado?->comentario ?? 'Sin comentario' }}</p>
                </div>
                <svg class="h-5 w-5 flex-shrink-0 text-gray-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 011.06.02L10 11.168l3.71-3.938a.75.75 0 111.08 1.04l-4.25 4.5a.75.75 0 01-1.08 0l-4.25-4.5a.75.75 0 01.02-1.06z" clip-rule="evenodd" />
                </svg>
            </summary>
            <div class="border-t border-gray-200 px-6 py-4">
                <embed src="{{ asset('storage/' . $proyecto->documento_intermedio()->documento_url) }}"
                    type="application/pdf" width="100%" height="600px" />
            </div>
        </details>
    @endif
    @if (empty($isPdf) && empty($hideEmbeddedDocuments) && $proyecto->documento_final() && $proyecto->documento_final()->documento_url != null)
        <details class="rounded-xl border border-gray-200 bg-white shadow-sm">
            <summary class="flex cursor-pointer list-none items-center justify-between gap-x-4 px-6 py-4">
                <div>
                    <span class="text-sm font-semibold text-gray-900">Informe Final, Estado: {{ $proyecto->documento_final()->estado?->tipoestado?->nombre ?? 'No especificado' }}</span>
                    <p class="mt-1 text-sm font-normal text-gray-500">{{ $proyecto->documento_final()->estado?->comentario ?? 'Sin comentario' }}</p>
                </div>
                <svg class="h-5 w-5 flex-shrink-0 text-gray-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 011.06.02L10 11.168l3.71-3.938a.75.75 0 111.08 1.04l-4.25 4.5a.75.75 0 01-1.08 0l-4.25-4.5a.75.75 0 01.02-1.06z" clip-rule="evenodd" />
                </svg>
            </summary>
            <div class="border-t border-gray-200 px-6 py-4">
                <h1>Visualizador de PDF</h1>
                <embed src="{{ asset('storage/' . $proyecto->documento_final()->documento_url) }}" type="application/pdf"
                    width="100%" height="600px" />
            </div>
        </details>
    @endif

    @if (!empty($isPdf))
        @include('components.fichas.partials.form-dvus-001-header', array_merge(['isPdf' => true], $encabezadoFicha))
    @endif

    <div class="{{ empty($isPdf) ? 'rounded-xl border border-gray-200 bg-white shadow-sm' : '' }}">
        @if (empty($isPdf))
            <div class="flex items-center justify-between border-b border-gray-200 px-6 py-4 no-print">
                <span class="text-sm font-semibold text-gray-900">Ficha del proyecto</span>
            </div>
        @endif

        <div class="{{ !empty($isPdf) ? 'pdf-content-wrapper' : '' }}" style="{{ empty($isPdf) ? 'display: flex; justify-content: center; margin-top: 20px; background-color: white;' : '' }}">
            <div class="container">
                @if (empty($isPdf))
                    @include('components.fichas.partials.form-dvus-001-header', array_merge(['isPdf' => false], $encabezadoFicha))
                @endif

                {{-- INFORMACIÓN GENERAL --}}
                <div class="section1">
                    <div class="section-title">@if ($esVoluntariado) I. INFORMACIÓN GENERAL @else I. INFORMACIÓN GENERAL DEL PROYECTO @endif</div>
                    <table class="table_datos1 info-general-table">
                        <colgroup>
                            <col class="info-col-label">
                            <col class="info-col-detail">
                            <col class="info-col-detail">
                            <col class="info-col-detail">
                            <col class="info-col-detail">
                            <col class="info-col-detail">
                        </colgroup>
                        @php
                            $fechaRegistro = $proyecto->fecha_registro;
                            // El FORM-DVUS-015 ordena la fecha de registro como Año / Mes / Día.
                            $partesFechaRegistro = $esVoluntariado
                                ? ['Año' => 'Y', 'Mes' => 'm', 'Día' => 'd']
                                : ['Día' => 'd', 'Mes' => 'm', 'Año' => 'Y'];
                        @endphp
                        <tr>
                            <th class="full-width1">{{ $numItem('fecha_registro') }}. {{ $esVoluntariado ? 'Fecha de registro' : 'Fecha de solicitud de registro:' }}</th>
                            <td class="full-width date-cell" colspan="5">
                                <table class="date-inner-table">
                                    <tr>
                                        @foreach ($partesFechaRegistro as $etiquetaFecha => $formatoFecha)
                                            <th>{{ $etiquetaFecha }}</th>
                                        @endforeach
                                    </tr>
                                    <tr>
                                        @foreach ($partesFechaRegistro as $formatoFecha)
                                            <td>{{ $fechaRegistro ? $fechaRegistro->format($formatoFecha) : '' }}</td>
                                        @endforeach
                                    </tr>
                                </table>
                            </td>
                        </tr>
                        <tr>
                            <th class="full-width1">{{ $numItem('nombre') }}. {{ $esVoluntariado ? 'Nombre del proyecto' : 'Nombre del Proyecto:' }}</th>
                            <td class="full-width" colspan="5">

                                @if (!empty($isPdf))
                                    <div class="pdf-text-block">{!! $renderPdfText($proyecto->nombre_proyecto) !!}</div>
                                @else
                                    <div class="input-field-multiline-static">{{ $proyecto->nombre_proyecto }}</div>
                                @endif
                            </td>
                        </tr>
                        <tr>
                            <th class="full-width1" rowspan="{{ $esVoluntariado ? 5 : 3 }}">{{ $numItem('unidad') }}. {{ $esVoluntariado ? 'Unidad(s) Académica(s)' : 'Unidad(s) Académica(as):' }}</th>
                            <td class="sub-header" colspan="1">Facultad /Centro Universitario Regional/Instituto Tecnológico</td>
                            <td class="full-width" colspan="4">
                                <ul>
                                    @foreach ($proyecto->facultades_centros as $centro)
                                        <li> {{ $centro->nombre }} </li>
                                    @endforeach
                                </ul>
                            </td>
                        </tr>
                        <tr>
                            <td class="sub-header" colspan="1">Escuela, Departamento Académico, Técnicos Universitarios, Instituto de Investigación, Observatorio, Consultorio</td>
                            <td class="full-width" colspan="4">
                                <ul>
                                    @foreach ($proyecto->departamentos_academicos as $departamento)
                                        <li>{{ $departamento->nombre }}</li>
                                    @endforeach
                                </ul>
                            </td>
                        </tr>
                        <tr>
                            <td class="sub-header" colspan="1">{{ $esVoluntariado ? 'Carrera' : 'Carreras' }}</td>
                            <td class="full-width" colspan="4">
                                @if($proyecto->carrera_no_aplica)
                                    <span>No aplica</span>
                                @else
                                    <ul>
                                        @foreach ($proyecto->carreras as $carrera)
                                            <li>{{ $carrera->nombre }}</li>
                                        @endforeach
                                    </ul>
                                @endif
                            </td>
                        </tr>
                        @if ($esVoluntariado)
                        {{-- En el FORM-DVUS-015 programa y líneas son parte del ítem 3 --}}
                        <tr>
                            <td class="sub-header" colspan="1">Programa al que pertenece</td>
                            <td class="full-width" colspan="4">
                                @if (!empty($isPdf))
                                    <div class="pdf-text-block">{!! $renderPdfText($proyecto->programa_pertenece) !!}</div>
                                @else
                                    <div class="input-field-multiline-static">{{ $proyecto->programa_pertenece }}</div>
                                @endif
                            </td>
                        </tr>
                        <tr>
                            <td class="sub-header" colspan="1">Líneas de investigación de la unidad académica</td>
                            <td class="full-width" colspan="4">
                                @if (!empty($isPdf))
                                    <div class="pdf-text-block">{!! $renderPdfText($proyecto->lineas_investigacion_academica) !!}</div>
                                @else
                                    <div class="input-field-multiline-static">{{ $proyecto->lineas_investigacion_academica }}</div>
                                @endif
                            </td>
                        </tr>
                        @endif
                        <tr>
                            <th class="full-width1" rowspan="1">{{ $numItem('modalidad') }}. Modalidad</th>
                            <td class="sub-header1 pdf-choice" colspan="1">Unidisciplinar <br>
                                @if (!empty($isPdf)){!! $pdfCheck($proyecto->modalidad?->nombre == 'Unidisciplinar') !!}@else<input disabled type="checkbox" class="No" @if ($proyecto->modalidad?->nombre == 'Unidisciplinar') checked @endif>@endif
                            </td>
                            <td class="sub-header1 pdf-choice" colspan="1">Multidisciplinar<br>
                                @if (!empty($isPdf)){!! $pdfCheck($proyecto->modalidad?->nombre == 'Multidisciplinar') !!}@else<input disabled type="checkbox" class="No" @if ($proyecto->modalidad?->nombre == 'Multidisciplinar') checked @endif>@endif
                            </td>
                            <td class="sub-header1 pdf-choice" colspan="1">Interdisciplinar <br>
                                @if (!empty($isPdf)){!! $pdfCheck($proyecto->modalidad?->nombre == 'Interdisciplinar') !!}@else<input disabled type="checkbox" class="No" @if ($proyecto->modalidad?->nombre == 'Interdisciplinar') checked @endif>@endif
                            </td>
                            <td class="sub-header1 pdf-choice" colspan="2">Transdisciplinar<br>
                                @if (!empty($isPdf)){!! $pdfCheck($proyecto->modalidad?->nombre == 'Transdisciplinar') !!}@else<input disabled type="checkbox" class="No" @if ($proyecto->modalidad?->nombre == 'Transdisciplinar') checked @endif>@endif
                            </td>
                        </tr>
                        <tr>
                            <th class="full-width1" rowspan="{{ $esVoluntariado ? 1 : 3 }}">{{ $numItem('alineamiento') }}. {{ $esVoluntariado ? 'Alineamiento con ejes prioritarios de la UNAH' : 'Alineamiento institucional' }}</th>
                            <td class="sub-header1 pdf-choice" colspan="1">Desarrollo económico y social <br>
                                @if (!empty($isPdf)){!! $pdfCheck((bool) $proyecto->ejes_prioritarios_unah?->contains('nombre', 'Desarrollo económico y social')) !!}@else<input disabled type="checkbox" class="No" @if ($proyecto->ejes_prioritarios_unah?->contains('nombre', 'Desarrollo económico y social')) checked @endif>@endif
                            </td>
                            <td class="sub-header1 pdf-choice" colspan="1">Democracia y gobernabilidad<br>
                                @if (!empty($isPdf)){!! $pdfCheck((bool) $proyecto->ejes_prioritarios_unah?->contains('nombre', 'Democracia y gobernabilidad')) !!}@else<input disabled type="checkbox" class="No" @if ($proyecto->ejes_prioritarios_unah?->contains('nombre', 'Democracia y gobernabilidad')) checked @endif>@endif
                            </td>
                            <td class="sub-header1 pdf-choice" colspan="1">Población y condiciones de vida <br>
                                @if (!empty($isPdf)){!! $pdfCheck((bool) $proyecto->ejes_prioritarios_unah?->contains('nombre', 'Población y condiciones de vida')) !!}@else<input disabled type="checkbox" class="No" @if ($proyecto->ejes_prioritarios_unah?->contains('nombre', 'Población y condiciones de vida')) checked @endif>@endif
                            </td>
                            <td class="sub-header1 pdf-choice" colspan="2">Ambiente, biodiversidad y desarrollo<br>
                                @if (!empty($isPdf)){!! $pdfCheck((bool) $proyecto->ejes_prioritarios_unah?->contains('nombre', 'Ambiente, biodiversidad y desarrollo')) !!}@else<input disabled type="checkbox" class="No" @if ($proyecto->ejes_prioritarios_unah?->contains('nombre', 'Ambiente, biodiversidad y desarrollo')) checked @endif>@endif
                            </td>
                        </tr>
                        @if (!$esVoluntariado)
                        <tr>
                            <td class="sub-header" colspan="1">Programa/estrategia al que pertenece</td>
                            <td class="full-width" colspan="4">
                                @if (!empty($isPdf))
                                    <div class="pdf-text-block">{!! $renderPdfText($proyecto->programa_pertenece) !!}</div>
                                @else
                                    <div class="input-field-multiline-static">{{ $proyecto->programa_pertenece }}</div>
                                @endif
                            </td>
                        </tr>
                        <tr>
                            <td class="sub-header" colspan="1">Líneas de investigación de la unidad académica</td>
                            <td class="full-width" colspan="4">
                                @if (!empty($isPdf))
                                    <div class="pdf-text-block">{!! $renderPdfText($proyecto->lineas_investigacion_academica) !!}</div>
                                @else
                                    <div class="input-field-multiline-static">{{ $proyecto->lineas_investigacion_academica }}</div>
                                @endif
                            </td>
                        </tr>
                        @endif

                        @if ($esVoluntariado)
                            @php
                                $tematicaOpciones = [
                                    'educacion' => 'Educación',
                                    'salud_bienestar' => 'Salud y bienestar',
                                    'cultura_patrimonio' => 'Cultura y patrimonio',
                                    'ambiente_sostenibilidad' => 'Ambiente y sostenibilidad',
                                    'desarrollo_comunitario' => 'Desarrollo comunitario',
                                    'otros' => 'Otros',
                                ];
                            @endphp
                            <tr>
                                <th class="full-width1" rowspan="1">{{ $numItem('tematica') }}. Temática principal del proyecto</th>
                                <td class="full-width" colspan="5" style="padding:0 !important;">
                                    <table style="width:100%; border-collapse:collapse; table-layout:fixed;">
                                        <tr>
                                            @foreach ($tematicaOpciones as $clave => $etiqueta)
                                                <td class="sub-header1 pdf-choice" style="border:1px solid #000; padding:3px 5px; text-align:center;">
                                                    {{ $etiqueta }}<br>
                                                    @if (!empty($isPdf)){!! $pdfCheck($proyecto->tematica_principal === $clave) !!}@else<input disabled type="checkbox" @if ($proyecto->tematica_principal === $clave) checked @endif>@endif
                                                </td>
                                            @endforeach
                                        </tr>
                                        @if ($proyecto->tematica_principal === 'otros' && filled($proyecto->tematica_principal_otro))
                                            <tr>
                                                <td colspan="6" style="border:1px solid #000; padding:3px 5px;">Otro: {{ $proyecto->tematica_principal_otro }}</td>
                                            </tr>
                                        @endif
                                    </table>
                                </td>
                            </tr>
                        @endif

                        <!-- FECHAS DE EJECUCION  -->
                        @php
                            $fechaInicio = $proyecto->fecha_inicio;
                            $fechaFinalizacion = $proyecto->fecha_finalizacion;
                        @endphp
                        <tr>
                            <th class="full-width1" rowspan="1">{{ $numItem('fecha_ejecucion') }}. Fecha de ejecución</th>
                            <td class="full-width date-cell" colspan="5" style="padding:0 !important;">
                                <table style="width:100%; border-collapse:collapse; table-layout:fixed;">
                                    <tr>
                                        <th colspan="3" style="width:50%; background-color:#002060; color:#fff; border:1px solid #000; padding:3px 5px; text-align:center;">Fecha de inicio</th>
                                        <th colspan="3" style="width:50%; background-color:#002060; color:#fff; border:1px solid #000; padding:3px 5px; text-align:center;">Fecha de finalización</th>
                                    </tr>
                                    <tr>
                                        <th style="width:16.6667%; background-color:#002060; color:#fff; border:1px solid #000; padding:3px 5px; text-align:center;">Día</th>
                                        <th style="width:16.6667%; background-color:#002060; color:#fff; border:1px solid #000; padding:3px 5px; text-align:center;">Mes</th>
                                        <th style="width:16.6667%; background-color:#002060; color:#fff; border:1px solid #000; padding:3px 5px; text-align:center;">Año</th>
                                        <th style="width:16.6667%; background-color:#002060; color:#fff; border:1px solid #000; padding:3px 5px; text-align:center;">Día</th>
                                        <th style="width:16.6667%; background-color:#002060; color:#fff; border:1px solid #000; padding:3px 5px; text-align:center;">Mes</th>
                                        <th style="width:16.6667%; background-color:#002060; color:#fff; border:1px solid #000; padding:3px 5px; text-align:center;">Año</th>
                                    </tr>
                                    <tr>
                                        <td style="border:1px solid #000; padding:3px 5px; text-align:center;">{{ $fechaInicio ? $fechaInicio->format('d') : '' }}</td>
                                        <td style="border:1px solid #000; padding:3px 5px; text-align:center;">{{ $fechaInicio ? $fechaInicio->format('m') : '' }}</td>
                                        <td style="border:1px solid #000; padding:3px 5px; text-align:center;">{{ $fechaInicio ? $fechaInicio->format('Y') : '' }}</td>
                                        <td style="border:1px solid #000; padding:3px 5px; text-align:center;">{{ $fechaFinalizacion ? $fechaFinalizacion->format('d') : '' }}</td>
                                        <td style="border:1px solid #000; padding:3px 5px; text-align:center;">{{ $fechaFinalizacion ? $fechaFinalizacion->format('m') : '' }}</td>
                                        <td style="border:1px solid #000; padding:3px 5px; text-align:center;">{{ $fechaFinalizacion ? $fechaFinalizacion->format('Y') : '' }}</td>
                                    </tr>
                                </table>
                            </td>
                        </tr>
                   
                        @if ($esVoluntariado)
                        {{-- 8. Beneficiarios directos (estructura del FORM-DVUS-015) --}}
                        @php
                            $etniasFicha = [
                                'Pueblo originario' => ['indigenas_hombres', 'indigenas_mujeres'],
                                'Afrodescendiente' => ['afroamericanos_hombres', 'afroamericanos_mujeres'],
                                'Mestizo' => ['mestizos_hombres', 'mestizos_mujeres'],
                            ];
                            $etniaMarcada = fn (string $campo) => ($proyecto->{$campo . '_marcado'} || ($proyecto->{$campo} ?? 0) > 0) ? 'X' : '';
                        @endphp
                        <tr>
                            <th class="full-width1" rowspan="3">{{ $numItem('beneficiarios') }}. Beneficiarios directos</th>
                            <td class="sub-header" colspan="1">Hombres</td>
                            <td class="full-width" colspan="4">{{ $proyecto->hombres ?? 0 }}</td>
                        </tr>
                        <tr>
                            <td class="sub-header" colspan="1">Mujeres</td>
                            <td class="full-width" colspan="4">{{ $proyecto->mujeres ?? 0 }}</td>
                        </tr>
                        <tr>
                            <td class="sub-header" colspan="1">Indicar tipo de etnia</td>
                            <td class="full-width" colspan="4" style="padding:0 !important;">
                                <table class="beneficiary-ethnicity" style="width:100%; border-collapse:collapse;">
                                    <tr>
                                        @foreach ($etniasFicha as $etnia => $campos)
                                            <th colspan="2" style="background-color:#ebeeef; border:1px solid #000; padding:3px 5px; text-align:center;">{{ $etnia }}</th>
                                        @endforeach
                                    </tr>
                                    <tr>
                                        @foreach ($etniasFicha as $campos)
                                            <th style="background-color:#ebeeef; border:1px solid #000; padding:3px 5px; text-align:center;">Homb</th>
                                            <th style="background-color:#ebeeef; border:1px solid #000; padding:3px 5px; text-align:center;">Mujer</th>
                                        @endforeach
                                    </tr>
                                    <tr>
                                        @foreach ($etniasFicha as [$campoHombres, $campoMujeres])
                                            <td style="border:1px solid #000; padding:3px 5px; text-align:center;">{{ $etniaMarcada($campoHombres) }}</td>
                                            <td style="border:1px solid #000; padding:3px 5px; text-align:center;">{{ $etniaMarcada($campoMujeres) }}</td>
                                        @endforeach
                                    </tr>
                                </table>
                            </td>
                        </tr>
                        @else
                        <!-- TABLA DE BENEFICIARIOS DIRECTOS -->
                        <tr>
                            <th class="full-width1" rowspan="3">{{ $numItem('beneficiarios') }}. Descripción de los beneficiarios</th>
                            <td class="sub-header" colspan="1">Cantidad aproximada</td>
                            <td class="full-width" colspan="4" style="padding:0 !important;">
                                <table class="beneficiary-summary" style="width:100%; border-collapse:collapse;">
                                    <tr>
                                        <th style="background-color:#ebeeef; border:1px solid #000; padding:3px 5px; text-align:center; width:50%;">Hombres</th>
                                        <th style="background-color:#ebeeef; border:1px solid #000; padding:3px 5px; text-align:center; width:50%;">Mujeres</th>
                                    </tr>
                                    <tr>
                                        <td style="border:1px solid #000; padding:3px 5px; text-align:center;">{{ $proyecto->hombres ?? 0 }}</td>
                                        <td style="border:1px solid #000; padding:3px 5px; text-align:center;">{{ $proyecto->mujeres ?? 0 }}</td>
                                    </tr>
                                </table>
                            </td>
                        </tr>
                        <tr>
                            <td class="sub-header" colspan="1">Tipo de población a la que está dirigido el proyecto</td>
                            <td class="full-width" colspan="4" style="padding:0 !important;">
                                <table class="beneficiary-ethnicity" style="width:100%; border-collapse:collapse;">
                                    <tr>
                                        <th colspan="2" style="background-color:#ebeeef; border:1px solid #000; padding:3px 5px; text-align:center;">Indígena</th>
                                        <th colspan="2" style="background-color:#ebeeef; border:1px solid #000; padding:3px 5px; text-align:center;">Afrodescendiente</th>
                                        <th colspan="2" style="background-color:#ebeeef; border:1px solid #000; padding:3px 5px; text-align:center;">Mestizo</th>
                                    </tr>
                                    <tr>
                                        <th style="background-color:#ebeeef; border:1px solid #000; padding:3px 5px; text-align:center;">Hombres</th>
                                        <th style="background-color:#ebeeef; border:1px solid #000; padding:3px 5px; text-align:center;">Mujeres</th>
                                        <th style="background-color:#ebeeef; border:1px solid #000; padding:3px 5px; text-align:center;">Hombres</th>
                                        <th style="background-color:#ebeeef; border:1px solid #000; padding:3px 5px; text-align:center;">Mujeres</th>
                                        <th style="background-color:#ebeeef; border:1px solid #000; padding:3px 5px; text-align:center;">Hombres</th>
                                        <th style="background-color:#ebeeef; border:1px solid #000; padding:3px 5px; text-align:center;">Mujeres</th>
                                    </tr>
                                    <tr>
                                        <td style="border:1px solid #000; padding:3px 5px; text-align:center;">{{ ($proyecto->indigenas_hombres_marcado || ($proyecto->indigenas_hombres ?? 0) > 0) ? 'X' : '' }}</td>
                                        <td style="border:1px solid #000; padding:3px 5px; text-align:center;">{{ ($proyecto->indigenas_mujeres_marcado || ($proyecto->indigenas_mujeres ?? 0) > 0) ? 'X' : '' }}</td>
                                        <td style="border:1px solid #000; padding:3px 5px; text-align:center;">{{ ($proyecto->afroamericanos_hombres_marcado || ($proyecto->afroamericanos_hombres ?? 0) > 0) ? 'X' : '' }}</td>
                                        <td style="border:1px solid #000; padding:3px 5px; text-align:center;">{{ ($proyecto->afroamericanos_mujeres_marcado || ($proyecto->afroamericanos_mujeres ?? 0) > 0) ? 'X' : '' }}</td>
                                        <td style="border:1px solid #000; padding:3px 5px; text-align:center;">{{ ($proyecto->mestizos_hombres_marcado || ($proyecto->mestizos_hombres ?? 0) > 0) ? 'X' : '' }}</td>
                                        <td style="border:1px solid #000; padding:3px 5px; text-align:center;">{{ ($proyecto->mestizos_mujeres_marcado || ($proyecto->mestizos_mujeres ?? 0) > 0) ? 'X' : '' }}</td>
                                    </tr>
                                </table>
                            </td>
                        </tr>
                        @endif
        
                    </table>

                    <!-- Sitio de ejecución del proyecto -->
                    <table class="table_datos1 pdf-table pdf-section-avoid-break section-site-execution">
                    <tr>
                        <th class="full-width1" colspan="6" style="text-align:left !important;">{{ $numItem('sitio') }}. Sitio de ejecución del proyecto</th>
                    </tr>
                    @php
                        $departamentosTexto = $proyecto->departamento->pluck('nombre')->implode(', ');
                        $municipiosTexto    = $proyecto->municipio->pluck('nombre')->implode(', ');
                        $caserioArr         = is_array($proyecto->caserio) ? $proyecto->caserio : (array) ($proyecto->caserio ?? []);
                        $caserioTexto       = implode(', ', array_filter($caserioArr, fn ($v) => filled($v)));
                        $regionArr          = is_array($proyecto->region) ? $proyecto->region : (array) $proyecto->region;
                        $regionTexto        = implode(', ', array_filter($regionArr, fn ($v) => filled($v)));
                        $paisArr            = is_array($proyecto->pais) ? $proyecto->pais : (array) $proyecto->pais;
                        $paisTexto          = implode(', ', array_filter($paisArr, fn ($v) => filled($v)));
                    @endphp
                    <tr>
                        <td class="sub-header" colspan="1">Departamento</td>
                        <td class="full-width" colspan="1">
                            @if (!empty($isPdf))
                                <div class="pdf-text-block">{!! $renderPdfText($departamentosTexto, 'No hay departamentos') !!}</div>
                            @else
                                <div class="input-field-multiline-static">{{ $departamentosTexto !== '' ? $departamentosTexto : 'No hay departamentos' }}</div>
                            @endif
                        </td>
                        <td class="sub-header" colspan="1">{{ $esVoluntariado ? 'Aldea (incluye ciudad)' : 'Aldea (Aplica también para ciudad)' }}</td>
                        <td class="full-width" colspan="3">
                            @if (!empty($isPdf))
                                <div class="pdf-text-block">{!! $renderPdfText($proyecto->aldea) !!}</div>
                            @else
                                <div class="input-field-multiline-static">{{ $proyecto->aldea }}</div>
                            @endif
                        </td>
                    </tr>
                    <tr>
                        <td class="sub-header" colspan="1">Municipio</td>
                        <td class="full-width" colspan="1">
                            @if (!empty($isPdf))
                                <div class="pdf-text-block">{!! $renderPdfText($municipiosTexto, 'No hay municipios') !!}</div>
                            @else
                                <div class="input-field-multiline-static">{{ $municipiosTexto !== '' ? $municipiosTexto : 'No hay municipios' }}</div>
                            @endif
                        </td>
                        <td class="sub-header" colspan="1">Caserío</td>
                        <td class="full-width" colspan="3">
                            @if (!empty($isPdf))
                                <div class="pdf-text-block">{!! $renderPdfText($caserioTexto) !!}</div>
                            @else
                                <div class="input-field-multiline-static">{{ $caserioTexto }}</div>
                            @endif
                        </td>
                    </tr>
                    <tr>
                        <td class="sub-header" colspan="1">Región</td>
                        <td class="full-width" colspan="1">
                            @if (!empty($isPdf))
                                <div class="pdf-text-block">{!! $renderPdfText($regionTexto) !!}</div>
                            @else
                                <div class="input-field-multiline-static">{{ $regionTexto }}</div>
                            @endif
                        </td>
                        <td class="sub-header" colspan="1">País</td>
                        <td class="full-width" colspan="3">
                            @if (!empty($isPdf))
                                <div class="pdf-text-block">{!! $renderPdfText($paisTexto) !!}</div>
                            @else
                                <div class="input-field-multiline-static">{{ $paisTexto }}</div>
                            @endif
                        </td>
                    </tr>

                    </table>

                    @if ($esVoluntariado)
                        @php
                            $metodologiaSeguimientoSel = is_array($proyecto->metodologia_seguimiento) ? $proyecto->metodologia_seguimiento : [];
                        @endphp
                        <table class="table_datos1 pdf-table pdf-section-avoid-break">
                            <tr>
                                <th class="full-width1" colspan="6" style="text-align:left !important;">{{ $numItem('metodologia_seguimiento') }}. Metodología de seguimiento</th>
                            </tr>
                            <tr>
                                <td class="sub-header1 pdf-choice" colspan="3" style="text-align:center;">
                                    Encuestas<br>
                                    @if (!empty($isPdf)){!! $pdfCheck(in_array('encuestas', $metodologiaSeguimientoSel, true)) !!}@else<input disabled type="checkbox" @if (in_array('encuestas', $metodologiaSeguimientoSel, true)) checked @endif>@endif
                                </td>
                                <td class="sub-header1 pdf-choice" colspan="3" style="text-align:center;">
                                    Entrevistas<br>
                                    @if (!empty($isPdf)){!! $pdfCheck(in_array('entrevistas', $metodologiaSeguimientoSel, true)) !!}@else<input disabled type="checkbox" @if (in_array('entrevistas', $metodologiaSeguimientoSel, true)) checked @endif>@endif
                                </td>
                            </tr>
                        </table>
                    @endif

                </div>

                {{-- EQUIPO EJECUTOR --}}
                <div class="section2">
                    @php
                        $coordinador = optional($proyecto->coordinador_proyecto->first())->empleado;
                    @endphp
                    <div class="section-title">II. EQUIPO EJECUTOR DEL PROYECTO{{ $esVoluntariado ? '' : '.' }} </div>
                    <table class="table_datos1">
                        <!-- TABLA COORDINADOR DEL PROYECTO -->
                        <tr>
                            <th class="full-width1" rowspan="3">{{ $numItem('coordinador') }}. Coordinador/a del {{ $esVoluntariado ? 'Programa' : 'Proyecto' }}:</th>
                            <td class="sub-header">Nombre Completo:</td>
                            <td class="full-width" colspan="2">
                                @if (!empty($isPdf))
                                    <div class="pdf-text-block">{!! $renderPdfText($coordinador?->nombre_completo, 'No especificado') !!}</div>
                                @else
                                    <div class="input-field-multiline-static">{{ $coordinador?->nombre_completo ?? 'No especificado' }}</div>
                                @endif
                            </td>
                            <td class="sub-header">{{ $esVoluntariado ? 'No. de empleado/a:' : 'No. de empleado:' }}</td>
                            <td class="full-width" colspan="2">
                                @if (!empty($isPdf))
                                    <div class="pdf-text-block">{!! $renderPdfText($coordinador?->numero_empleado ?? 'No especificado') !!}</div>
                                @else
                                    <div class="input-field-multiline-static">{{ $coordinador?->numero_empleado ?? 'No especificado' }}</div>
                                @endif
                            </td>
                        </tr>
                        <tr>
                            <td class="sub-header">Correo electrónico:</td>
                            <td class="full-width" colspan="2">
                                @if (!empty($isPdf))
                                    <div class="pdf-text-block">{!! $renderPdfText($coordinador?->user?->email, 'No especificado') !!}</div>
                                @else
                                    <div class="input-field-multiline-static">{{ $coordinador?->user?->email ?? 'No especificado' }}</div>
                                @endif
                            </td>
                            <td class="sub-header">Celular:</td>
                            <td class="full-width" colspan="2">
                                @if (!empty($isPdf))
                                    <div class="pdf-text-block">{!! $renderPdfText($coordinador?->celular ?? 'No especificado') !!}</div>
                                @else
                                    <div class="input-field-multiline-static">{{ $coordinador?->celular ?? 'No especificado' }}</div>
                                @endif
                            </td>
                        </tr>
                        <tr>
                            <td class="sub-header">Categoría:</td>
                            <td class="full-width" colspan="2">
                                @if (!empty($isPdf))
                                    <div class="pdf-text-block">{!! $renderPdfText($coordinador?->categoria?->nombre, 'No especificado') !!}</div>
                                @else
                                    <div class="input-field-multiline-static">{{ $coordinador?->categoria?->nombre ?? 'No especificado' }}</div>
                                @endif
                            </td>
                            <td class="sub-header">{{ $esVoluntariado ? 'Departamento al que pertenece:' : 'Departamento:' }}</td>
                            <td class="full-width" colspan="2">
                                @if (!empty($isPdf))
                                    <div class="pdf-text-block">{!! $renderPdfText($coordinador?->departamento_academico?->nombre, 'No especificado') !!}</div>
                                @else
                                    <div class="input-field-multiline-static">{{ $coordinador?->departamento_academico?->nombre ?? 'No especificado' }}</div>
                                @endif
                            </td>
                        </tr>
                    </table>

                    <!-- TABLA DE INTEGRANTES DEL EQUIPO UNIVERSITARIO -->
                    <table class="table_datos1">
                        <tr>
                            <th class="full-width1" colspan="8" style="text-align:left !important; padding-left:8px;">{{ $numItem('docentes') }}. Integrantes del equipo docente permanente tiempo completo
                                (Agregar más líneas de ser necesario)</th>
                        </tr>
                        @if ($esVoluntariado)
                        {{-- 12. FORM-DVUS-015: columnas del formato; el coordinador ya figura en el ítem 11 --}}
                        @php
                            $columnasEquipo015 = [
                                ['N°', 1], ['Nombre Completo', 2], ['No. de empleado/a', 1],
                                ['Correo electrónico', 2], ['Categoría', 1], ['Departamento al que pertenece', 1],
                            ];
                            $integrantesEquipo015 = $proyecto->integrantes->reject(fn ($empleado) => $empleado->id === $coordinador?->id)->values();
                        @endphp
                        <tr>
                            @foreach ($columnasEquipo015 as [$columnaEquipo, $spanEquipo])
                                <td class="sub-header" colspan="{{ $spanEquipo }}" style="background-color:#002060; color:#fff; font-weight:bold; text-align:center; font-style:normal;{{ $loop->first ? ' width:5%; white-space:nowrap; padding-left:2px; padding-right:2px;' : '' }}">{{ $columnaEquipo }}</td>
                            @endforeach
                        </tr>
                        @forelse ($integrantesEquipo015 as $integrante)
                            @php
                                $celdasIntegrante = [
                                    [$integrante->nombre_completo, 2],
                                    [$integrante->numero_empleado, 1],
                                    [$integrante->user?->email, 2],
                                    [$integrante->categoria?->nombre, 1],
                                    [$integrante->departamento_academico?->nombre, 1],
                                ];
                            @endphp
                            <tr>
                                <td class="full-width" colspan="1" style="text-align:center; width:3%;">{{ $loop->iteration }}</td>
                                @foreach ($celdasIntegrante as [$valorCelda, $spanCelda])
                                    <td class="full-width" colspan="{{ $spanCelda }}">
                                        @if (!empty($isPdf))
                                            <div class="pdf-text-block">{!! $renderPdfText($valorCelda) !!}</div>
                                        @else
                                            <div class="input-field-multiline-static">{{ $valorCelda }}</div>
                                        @endif
                                    </td>
                                @endforeach
                            </tr>
                        @empty
                            <tr>
                                <td class="full-width" colspan="8">
                                    @if (!empty($isPdf))
                                        <div class="pdf-text-block">{!! $renderPdfText('No hay docentes') !!}</div>
                                    @else
                                        <div class="input-field-multiline-static">No hay docentes</div>
                                    @endif
                                </td>
                            </tr>
                        @endforelse
                        @else
                        <tr>
                            <td class="sub-header" style="background-color:#002060; color:#fff; font-weight:bold; text-align:center; font-style:normal; width:5%; white-space:nowrap; padding-left:2px; padding-right:2px;">N°</td>
                            <td class="sub-header" style="background-color:#002060; color:#fff; font-weight:bold; text-align:center; font-style:normal;">Nombre Completo:</td>
                            <td class="sub-header" style="background-color:#002060; color:#fff; font-weight:bold; text-align:center; font-style:normal;">No. de empleado/a:</td>
                            <td class="sub-header" style="background-color:#002060; color:#fff; font-weight:bold; text-align:center; font-style:normal;">Correo electrónico:</td>
                            <td class="sub-header" style="background-color:#002060; color:#fff; font-weight:bold; text-align:center; font-style:normal;">Categoría:</td>
                            <td class="sub-header" style="background-color:#002060; color:#fff; font-weight:bold; text-align:center; font-style:normal;">Departamento al que pertenece:</td>
                            <td class="sub-header" style="background-color:#002060; color:#fff; font-weight:bold; text-align:center; font-style:normal;">Tiempo de participación en el proyecto (estimado en horas)</td>
                            <td class="sub-header" style="background-color:#002060; color:#fff; font-weight:bold; text-align:center; font-style:normal;">Productos que tendrá a su cargo</td>
                        </tr>
                        @php
                            $tiempoPorIntegrante = [];
                            $productosPorIntegrante = [];
                            foreach ($proyecto->actividades as $actividadEquipo) {
                                foreach ($actividadEquipo->empleados as $responsableActividad) {
                                    $tiempoPorIntegrante[$responsableActividad->id] = ($tiempoPorIntegrante[$responsableActividad->id] ?? 0) + (int) ($actividadEquipo->horas ?? 0);
                                    if (filled($actividadEquipo->resultados)) {
                                        $productosPorIntegrante[$responsableActividad->id][] = $actividadEquipo->resultados;
                                    }
                                }
                            }
                        @endphp
                        @forelse ($proyecto->integrantes as $integrante)
                            <tr>
                                <td class="full-width" colspan="1" style="text-align:center; width:3%;">{{ $loop->iteration }}</td>
                                <td class="full-width" colspan="1">
                                    @if (!empty($isPdf))
                                        <div class="pdf-text-block">{!! $renderPdfText($integrante->nombre_completo) !!}</div>
                                    @else
                                        <div class="input-field-multiline-static">{{ $integrante->nombre_completo }}</div>
                                    @endif
                                </td>
                                <td class="full-width" colspan="1">
                                    @if (!empty($isPdf))
                                        <div class="pdf-text-block">{!! $renderPdfText($integrante->numero_empleado) !!}</div>
                                    @else
                                        <div class="input-field-multiline-static">{{ $integrante->numero_empleado }}</div>
                                    @endif
                                </td>
                                <td class="full-width" colspan="1">
                                    @if (!empty($isPdf))
                                        <div class="pdf-text-block">{!! $renderPdfText($integrante->user?->email, 'No especificado') !!}</div>
                                    @else
                                        <div class="input-field-multiline-static">{{ $integrante->user?->email ?? 'No especificado' }}</div>
                                    @endif
                                </td>
                                <td class="full-width" colspan="1">
                                    @if (!empty($isPdf))
                                        <div class="pdf-text-block">{!! $renderPdfText($integrante->categoria?->nombre, 'Sin categoría') !!}</div>
                                    @else
                                        <div class="input-field-multiline-static">{{ $integrante->categoria?->nombre ?? 'Sin categoría' }}</div>
                                    @endif
                                </td>
                                <td class="full-width" colspan="1">
                                    @if (!empty($isPdf))
                                        <div class="pdf-text-block">{!! $renderPdfText($integrante->departamento_academico?->nombre, 'Sin departamento') !!}</div>
                                    @else
                                        <div class="input-field-multiline-static">{{ $integrante->departamento_academico?->nombre ?? 'Sin departamento' }}</div>
                                    @endif
                                </td>
                                <td class="full-width" colspan="1" style="text-align:center;">
                                    {{ $tiempoPorIntegrante[$integrante->id] ?? 0 }}
                                </td>
                                <td class="full-width" colspan="1">
                                    @php
                                        $productosIntegrante = implode(', ', $productosPorIntegrante[$integrante->id] ?? []);
                                    @endphp
                                    @if (!empty($isPdf))
                                        <div class="pdf-text-block">{!! $renderPdfText($productosIntegrante, 'No especificado') !!}</div>
                                    @else
                                        <div class="input-field-multiline-static">{{ $productosIntegrante ?: 'No especificado' }}</div>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td class="full-width
                                " colspan="8">
                                    @if (!empty($isPdf))
                                        <div class="pdf-text-block">{!! $renderPdfText('No hay docentes') !!}</div>
                                    @else
                                        <div class="input-field-multiline-static">No hay docentes</div>
                                    @endif
                                </td>
                            </tr>
                        @endforelse
                        @endif
                       
                        <tr>
                            <th class="full-width1" colspan="8" style="text-align:left !important; padding-left:8px;">{{ $numItem('internacionales') }}. {{ $esVoluntariado ? 'Integrantes del equipo de cooperación internacional' : 'Docentes internacionales participantes en el proyecto' }}
                                (Agregar más líneas de ser necesario)</th>
                        </tr>
                        <tr>
                            <td class="sub-header" colspan="1" style="background-color:#002060; color:#fff; font-weight:bold; text-align:center; font-style:normal; width:5%; white-space:nowrap; padding-left:2px; padding-right:2px;">N°</td>
                            <td class="sub-header" colspan="2" style="background-color:#002060; color:#fff; font-weight:bold; text-align:center; font-style:normal;">{{ $esVoluntariado ? 'Nombre Completo' : 'Nombre Completo:' }}</td>
                            <td class="sub-header" colspan="1" style="background-color:#002060; color:#fff; font-weight:bold; text-align:center; font-style:normal;">{{ $esVoluntariado ? 'Pasaporte' : 'Pasaporte:' }}</td>
                            <td class="sub-header" colspan="2" style="background-color:#002060; color:#fff; font-weight:bold; text-align:center; font-style:normal;">{{ $esVoluntariado ? 'Correo electrónico' : 'Correo electrónico:' }}</td>
                            <td class="sub-header" colspan="1" style="background-color:#002060; color:#fff; font-weight:bold; text-align:center; font-style:normal;">{{ $esVoluntariado ? 'País' : 'País:' }}</td>
                            <td class="sub-header" colspan="1" style="background-color:#002060; color:#fff; font-weight:bold; text-align:center; font-style:normal;">{{ $esVoluntariado ? 'Universidad' : 'Universidad/Institución:' }}</td>
                        </tr>
                        @forelse ($proyecto->integrantesInternacionales as $integrante)
                            <tr>
                                <td class="full-width" colspan="1" style="text-align:center; width:3%;">{{ $loop->iteration }}</td>
                                <td class="full-width" colspan="2">
                                    @if (!empty($isPdf))
                                        <div class="pdf-text-block">{!! $renderPdfText($integrante->nombre_completo) !!}</div>
                                    @else
                                        <div class="input-field-multiline-static">{{ $integrante->nombre_completo }}</div>
                                    @endif
                                </td>
                                <td class="full-width" colspan="1">
                                    @if (!empty($isPdf))
                                        <div class="pdf-text-block">{!! $renderPdfText($integrante->documento_identidad) !!}</div>
                                    @else
                                        <div class="input-field-multiline-static">{{ $integrante->documento_identidad }}</div>
                                    @endif
                                </td>
                                <td class="full-width" colspan="2">
                                    @if (!empty($isPdf))
                                        <div class="pdf-text-block">{!! $renderPdfText($integrante->email) !!}</div>
                                    @else
                                        <div class="input-field-multiline-static">{{ $integrante->email }}</div>
                                    @endif
                                </td>
                                <td class="full-width" colspan="1">
                                    @if (!empty($isPdf))
                                        <div class="pdf-text-block">{!! $renderPdfText($integrante->pais) !!}</div>
                                    @else
                                        <div class="input-field-multiline-static">{{ $integrante->pais }}</div>
                                    @endif
                                </td>
                                <td class="full-width" colspan="1">
                                    @if (!empty($isPdf))
                                        <div class="pdf-text-block">{!! $renderPdfText($integrante->institucion) !!}</div>
                                    @else
                                        <div class="input-field-multiline-static">{{ $integrante->institucion }}</div>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td class="full-width
                                " colspan="8">
                                    @if (!empty($isPdf))
                                        <div class="pdf-text-block">{!! $renderPdfText('No hay integrantes') !!}</div>
                                    @else
                                        <div class="input-field-multiline-static">No hay integrantes</div>
                                    @endif
                                </td>
                            </tr>
                        @endforelse
                    </table>

                </div>

                @php
                    $estudianteParticipaciones = collect($proyecto->estudiante_proyecto ?? []);

                    $normalizarTipoParticipacion = function ($tipo) {
                        $tipoNormalizado = mb_strtolower((string) $tipo, 'UTF-8');

                        if (str_contains($tipoNormalizado, 'servicio social')) {
                            return 'servicio';
                        }
                        if (str_contains($tipoNormalizado, 'voluntariado')) {
                            return 'voluntariado';
                        }
                        if (str_contains($tipoNormalizado, 'practica') || str_contains($tipoNormalizado, 'práctica')) {
                            return 'practica';
                        }

                        return 'otro';
                    };

                    $sumarEstudiantesPorTipoYSexo = function (string $tipo, string $sexo) use ($estudianteParticipaciones, $normalizarTipoParticipacion) {
                        $columna = $sexo === 'hombres' ? 'cantidad_estudiantes_hombres' : 'cantidad_estudiantes_mujeres';

                        return $estudianteParticipaciones
                            ->filter(fn ($item) => $normalizarTipoParticipacion($item->tipo_participacion_estudiante ?? '') === $tipo)
                            ->sum(fn ($item) => (int) ($item->{$columna} ?? 0));
                    };

                    $totalEstudiantesHombres = $estudianteParticipaciones->sum(fn ($item) => (int) ($item->cantidad_estudiantes_hombres ?? 0));
                    $totalEstudiantesMujeres = $estudianteParticipaciones->sum(fn ($item) => (int) ($item->cantidad_estudiantes_mujeres ?? 0));

                    $practicaHombres = $sumarEstudiantesPorTipoYSexo('practica', 'hombres');
                    $practicaMujeres = $sumarEstudiantesPorTipoYSexo('practica', 'mujeres');
                    $servicioHombres = $sumarEstudiantesPorTipoYSexo('servicio', 'hombres');
                    $servicioMujeres = $sumarEstudiantesPorTipoYSexo('servicio', 'mujeres');
                    $voluntariadoHombres = $sumarEstudiantesPorTipoYSexo('voluntariado', 'hombres');
                    $voluntariadoMujeres = $sumarEstudiantesPorTipoYSexo('voluntariado', 'mujeres');

                    $integrantesProyecto = collect($proyecto->integrantes ?? []);

                    $categoriaNombre = fn ($empleado) => mb_strtolower((string) optional($empleado->categoria)->nombre, 'UTF-8');
                    $tipoEmpleado = fn ($empleado) => mb_strtolower((string) ($empleado->tipo_empleado ?? ''), 'UTF-8');

                    $esDocenteBase = fn ($empleado) => $tipoEmpleado($empleado) === 'docente'
                        || str_contains($categoriaNombre($empleado), 'profesor')
                        || str_contains($categoriaNombre($empleado), 'titular')
                        || str_contains($categoriaNombre($empleado), 'auxiliar');

                    $esAdministrativoBase = fn ($empleado) => $tipoEmpleado($empleado) === 'administrativo'
                        || str_contains($categoriaNombre($empleado), 'administrativo')
                        || str_contains($categoriaNombre($empleado), 'servicio')
                        || str_contains($categoriaNombre($empleado), 'tecnico')
                        || str_contains($categoriaNombre($empleado), 'instructor');

                    $esDocenteXHora = fn ($empleado) => $esDocenteBase($empleado) && str_contains($categoriaNombre($empleado), 'x hora');

                    $esAdministrativo = fn ($empleado) => $esAdministrativoBase($empleado) && str_contains($categoriaNombre($empleado), 'administrativo');
                    $esServicios = fn ($empleado) => $esAdministrativoBase($empleado) && str_contains($categoriaNombre($empleado), 'servicio');
                    $esAsistenteTecnico = fn ($empleado) => $esAdministrativoBase($empleado)
                        && (str_contains($categoriaNombre($empleado), 'tecnico')
                            || str_contains($categoriaNombre($empleado), 'instructor')
                            || str_contains($categoriaNombre($empleado), 'laboratorio'));

                    $contarIntegrantes = function (callable $filtro, ?string $sexo = null) use ($integrantesProyecto) {
                        return $integrantesProyecto->filter(function ($empleado) use ($filtro, $sexo) {
                            if (!$filtro($empleado)) {
                                return false;
                            }

                            if (!$sexo) {
                                return true;
                            }

                            return mb_strtolower((string) ($empleado->sexo ?? ''), 'UTF-8') === $sexo;
                        })->count();
                    };

                    $docentesXHoraHombres = $contarIntegrantes($esDocenteXHora, 'masculino');
                    $docentesXHoraMujeres = $contarIntegrantes($esDocenteXHora, 'femenino');

                    $administrativosHombres = $contarIntegrantes($esAdministrativo, 'masculino');
                    $administrativosMujeres = $contarIntegrantes($esAdministrativo, 'femenino');
                    $serviciosHombres = $contarIntegrantes($esServicios, 'masculino');
                    $serviciosMujeres = $contarIntegrantes($esServicios, 'femenino');
                    $asistentesTecnicosHombres = $contarIntegrantes($esAsistenteTecnico, 'masculino');
                    $asistentesTecnicosMujeres = $contarIntegrantes($esAsistenteTecnico, 'femenino');

                    if ($esVoluntariado) {
                        // Ítem 15 del FORM-DVUS-015: cantidades capturadas en el formulario, no
                        // derivadas del ítem 12 (que solo admite docentes permanentes).
                        $docentesXHoraHombres = (int) $proyecto->vol_profesores_hora_hombres;
                        $docentesXHoraMujeres = (int) $proyecto->vol_profesores_hora_mujeres;
                        $administrativosHombres = (int) $proyecto->vol_personal_administrativo_hombres;
                        $administrativosMujeres = (int) $proyecto->vol_personal_administrativo_mujeres;
                        $serviciosHombres = (int) $proyecto->vol_personal_servicio_hombres;
                        $serviciosMujeres = (int) $proyecto->vol_personal_servicio_mujeres;
                        $asistentesTecnicosHombres = (int) $proyecto->vol_asistentes_tecnicos_hombres;
                        $asistentesTecnicosMujeres = (int) $proyecto->vol_asistentes_tecnicos_mujeres;
                    }

                    $periodosAcademicosFicha = \App\Models\PeriodoAcademico::pluck('nombre', 'id');
                    // El formulario guarda el id del periodo; los registros antiguos guardaban el nombre.
                    $nombrePeriodoAcademico = fn ($valor) => filled($valor) ? $periodosAcademicosFicha->get($valor, $valor) : '';

                    $practicasAsignatura = $estudianteParticipaciones
                        ->filter(fn ($item) => $normalizarTipoParticipacion($item->tipo_participacion_estudiante ?? '') === 'practica' && $item->asignatura_id);
                    $filasPractica = max($practicasAsignatura->count(), 4);
                    $practicasIndexadas = $practicasAsignatura->values();
                @endphp

                {{-- PARTICIPACIÓN DE ESTUDIANTES Y VOLUNTARIOS --}}
                <div class="section3">
                    <div class="section-title">III. {{ $esVoluntariado ? 'PARTICIPACIÓN DE LA COMUNIDAD UNIVERSITARIA' : 'PARTICIPACIÓN DE ESTUDIANTES Y VOLUNTARIOS' }}</div>
                    <table class="table_datos1">
                        <!-- 12. PARTICIPACIÓN DE ESTUDIANTES UNAH -->
                        <tr>
                            <th class="full-width1" rowspan="4">{{ $numItem('estudiantes') }}. Participación de estudiantes UNAH</th>
                            <td colspan="8" style="background-color:#002060; color:#fff; font-weight:bold; font-style:italic; text-align:center;">Desglose del tipo de participación de estudiantes (cantidad)</td>
                        </tr>
                        <tr>
                            <td class="sub-header" colspan="2" style="text-align:center;">Práctica de asignatura / posgrado</td>
                            <td class="sub-header" colspan="2" style="text-align:center;">Servicio social o PPS</td>
                            <td class="sub-header" colspan="2" style="text-align:center;">Voluntariado</td>
                            <td class="sub-header" colspan="2" style="text-align:center;">@if ($esVoluntariado)Total estudiantes @else <u>Total</u> estudiantes @endif</td>
                        </tr>
                        <tr>
                            <td class="sub-header" style="text-align:center;">Hombres</td>
                            <td class="sub-header" style="text-align:center;">Mujeres</td>
                            <td class="sub-header" style="text-align:center;">Hombres</td>
                            <td class="sub-header" style="text-align:center;">Mujeres</td>
                            <td class="sub-header" style="text-align:center;">Hombres</td>
                            <td class="sub-header" style="text-align:center;">Mujeres</td>
                            <td class="sub-header" style="text-align:center;">Hombres</td>
                            <td class="sub-header" style="text-align:center;">Mujeres</td>
                        </tr>
                        <tr>
                            <td class="full-width" style="text-align:center;">{{ $practicaHombres }}</td>
                            <td class="full-width" style="text-align:center;">{{ $practicaMujeres }}</td>
                            <td class="full-width" style="text-align:center;">{{ $servicioHombres }}</td>
                            <td class="full-width" style="text-align:center;">{{ $servicioMujeres }}</td>
                            <td class="full-width" style="text-align:center;">{{ $voluntariadoHombres }}</td>
                            <td class="full-width" style="text-align:center;">{{ $voluntariadoMujeres }}</td>
                            <td class="full-width" style="text-align:center;">{{ $totalEstudiantesHombres }}</td>
                            <td class="full-width" style="text-align:center;">{{ $totalEstudiantesMujeres }}</td>
                        </tr>

                        <!-- 13. VOLUNTARIADO PERSONAL DE LA UNAH -->
                        <tr>
                            <th class="full-width1" rowspan="4">{{ $numItem('vol_personal') }}. Voluntariado personal de la UNAH</th>
                            <td colspan="8" style="background-color:#002060; color:#fff; font-weight:bold; font-style:italic; text-align:center;">Desglose del tipo de participación de personal de la UNAH (cantidad)</td>
                        </tr>
                        <tr>
                            <td class="sub-header" colspan="2" style="text-align:center;">Profesores horario x hora</td>
                            <td class="sub-header" colspan="2" style="text-align:center;">Personal administrativo</td>
                            <td class="sub-header" colspan="2" style="text-align:center;">Personal de servicio</td>
                            <td class="sub-header" colspan="2" style="text-align:center;">Asistentes técnicos laboratorios / instructores</td>
                        </tr>
                        <tr>
                            <td class="sub-header" style="text-align:center;">Hombres</td>
                            <td class="sub-header" style="text-align:center;">Mujeres</td>
                            <td class="sub-header" style="text-align:center;">Hombres</td>
                            <td class="sub-header" style="text-align:center;">Mujeres</td>
                            <td class="sub-header" style="text-align:center;">Hombres</td>
                            <td class="sub-header" style="text-align:center;">Mujeres</td>
                            <td class="sub-header" style="text-align:center;">Hombres</td>
                            <td class="sub-header" style="text-align:center;">Mujeres</td>
                        </tr>
                        <tr>
                            <td class="full-width" style="text-align:center;">{{ $docentesXHoraHombres }}</td>
                            <td class="full-width" style="text-align:center;">{{ $docentesXHoraMujeres }}</td>
                            <td class="full-width" style="text-align:center;">{{ $administrativosHombres }}</td>
                            <td class="full-width" style="text-align:center;">{{ $administrativosMujeres }}</td>
                            <td class="full-width" style="text-align:center;">{{ $serviciosHombres }}</td>
                            <td class="full-width" style="text-align:center;">{{ $serviciosMujeres }}</td>
                            <td class="full-width" style="text-align:center;">{{ $asistentesTecnicosHombres }}</td>
                            <td class="full-width" style="text-align:center;">{{ $asistentesTecnicosMujeres }}</td>
                        </tr>

                        <!-- 14. VOLUNTARIADO INTERNACIONAL -->
                        @php
                            $integrantesInternacionalesFicha = $proyecto->integrantesInternacionales;
                            $contarVoluntariosInternacionales = function (string $nombreNivel, string $sexo) use ($integrantesInternacionalesFicha) {
                                return $integrantesInternacionalesFicha->filter(function ($integrante) use ($nombreNivel, $sexo) {
                                    return ($integrante->nivelAcademico?->nombre === $nombreNivel) && $integrante->sexo === $sexo;
                                })->count();
                            };

                            if ($esVoluntariado) {
                                // Ítem 16 del FORM-DVUS-015: cantidades capturadas en el formulario, no
                                // derivadas del equipo de cooperación internacional (ítem 13).
                                $columnasVoluntariadoInternacional = [
                                    'Estudiante de grado' => 'vol_int_grado',
                                    'Maestría' => 'vol_int_maestria',
                                    'Doctorado/Posgrado' => 'vol_int_doctorado',
                                ];
                                $contarVoluntariosInternacionales = fn (string $nombreNivel, string $sexo) => (int) $proyecto->{$columnasVoluntariadoInternacional[$nombreNivel] . ($sexo === 'masculino' ? '_hombres' : '_mujeres')};
                            }
                        @endphp
                        <tr>
                            <th class="full-width1" rowspan="4">{{ $numItem('vol_internacional') }}. Voluntariado internacional</th>
                            <td colspan="8" style="background-color:#002060; color:#fff; font-weight:bold; font-style:italic; text-align:center;">Desglose del voluntariado internacional (cantidad)</td>
                        </tr>
                        <tr>
                            <td class="sub-header" colspan="2" style="text-align:center;">Estudiantes de grado</td>
                            <td class="sub-header" colspan="2" style="text-align:center;">Estudiantes de maestría</td>
                            <td class="sub-header" colspan="4" style="text-align:center;">Doctorados / posdoctorados</td>
                        </tr>
                        <tr>
                            <td class="sub-header" style="text-align:center;">Hombres</td>
                            <td class="sub-header" style="text-align:center;">Mujeres</td>
                            <td class="sub-header" style="text-align:center;">Hombres</td>
                            <td class="sub-header" style="text-align:center;">Mujeres</td>
                            <td class="sub-header" colspan="2" style="text-align:center;">Hombres</td>
                            <td class="sub-header" colspan="2" style="text-align:center;">Mujeres</td>
                        </tr>
                        <tr>
                            <td class="full-width" style="text-align:center;">{{ $contarVoluntariosInternacionales('Estudiante de grado', 'masculino') }}</td>
                            <td class="full-width" style="text-align:center;">{{ $contarVoluntariosInternacionales('Estudiante de grado', 'femenino') }}</td>
                            <td class="full-width" style="text-align:center;">{{ $contarVoluntariosInternacionales('Maestría', 'masculino') }}</td>
                            <td class="full-width" style="text-align:center;">{{ $contarVoluntariosInternacionales('Maestría', 'femenino') }}</td>
                            <td class="full-width" colspan="2" style="text-align:center;">{{ $contarVoluntariosInternacionales('Doctorado/Posgrado', 'masculino') }}</td>
                            <td class="full-width" colspan="2" style="text-align:center;">{{ $contarVoluntariosInternacionales('Doctorado/Posgrado', 'femenino') }}</td>
                        </tr>

                        <!-- 15. DETALLE DE LA PRÁCTICA DE ASIGNATURA/POSGRADO -->
                        <tr>
                            <th class="full-width1" rowspan="{{ 2 + $filasPractica }}">{{ $numItem('detalle_practica') }}. Detalle de la Práctica de asignatura{{ $esVoluntariado ? ' / ' : '/' }}posgrado estudiantes UNAH</th>
                            <td colspan="1" rowspan="2" style="background-color:#002060; color:#fff; font-weight:bold; font-style:normal; text-align:center;">Código</td>
                            <td colspan="3" rowspan="2" style="background-color:#002060; color:#fff; font-weight:bold; font-style:normal; text-align:center;">Nombre</td>
                            <td colspan="2" rowspan="2" style="background-color:#002060; color:#fff; font-weight:bold; font-style:normal; text-align:center;">{{ $esVoluntariado ? 'Período académico' : 'Periodo académico' }}</td>
                            <td colspan="2" style="background-color:#002060; color:#fff; font-weight:bold; font-style:normal; text-align:center;">Matrícula</td>
                        </tr>
                        <tr>
                            <td style="background-color:#002060; color:#fff; font-weight:bold; font-style:normal; text-align:center;">Hombres</td>
                            <td style="background-color:#002060; color:#fff; font-weight:bold; font-style:normal; text-align:center;">Mujeres</td>
                        </tr>
                        @for ($i = 0; $i < $filasPractica; $i++)
                            @php $practica = $practicasIndexadas->get($i); @endphp
                            <tr>
                                <td class="full-width" colspan="1">{{ $practica?->asignatura?->codigo ?? '' }}</td>
                                <td class="full-width" colspan="3">{{ $practica?->asignatura?->nombre ?? '' }}</td>
                                <td class="full-width" colspan="2">{{ $nombrePeriodoAcademico($practica?->periodo_academico_id) }}</td>
                                <td class="full-width" style="text-align:center;">{{ $practica?->cantidad_estudiantes_hombres ?? '' }}</td>
                                <td class="full-width" style="text-align:center;">{{ $practica?->cantidad_estudiantes_mujeres ?? '' }}</td>
                            </tr>
                        @endfor
                    </table>
                </div>
                {{-- ENTIDAD CONTRAPARTE --}}
                <div class="section4">
                    <div class="section-title">@if ($esVoluntariado) IV. INFORMACIÓN DE LA ENTIDAD CONTRAPARTE @else IV. INFORMACIÓN DE LA ENTIDAD CONTRAPARTE DEL PROYECTO (OBLIGATORIO) @endif</div>
                    <table class="table_datos2">
                        <tr>
                            <th class="header" colspan="7">
                                @if ($esVoluntariado)
                                    (Sí existe más de una contraparte añadir una tabla de información por cada una de ellas)
                                @else
                                    En caso de que la contraparte sea nacional (añadir una tabla de información por cada una de las contrapartes)
                                @endif
                            </th>
                        </tr>
                        @forelse ($proyecto->entidad_contraparte_proyecto()->with('entidadContraparte')->with('instrumentoFormalizacion')->get() as $pivot)
                            @php
                                $entidad = $pivot->entidadContraparte;
                                // Tipo y contacto son los registrados para este proyecto (tabla pivote);
                                // el catálogo solo completa lo que falte.
                                $datosContraparte = (object) [
                                    'nombre' => $entidad?->nombre ?: $pivot->nombre,
                                    'rtn' => $entidad?->rtn,
                                    'tipo_entidad' => $pivot->tipo_entidad ?: $entidad?->tipo_entidad,
                                    'nombre_contacto' => $pivot->nombre_contacto ?: $entidad?->nombre_contacto,
                                    'cargo_contacto' => $pivot->cargo_contacto ?: $entidad?->cargo_contacto,
                                    'telefono' => $pivot->telefono ?: $entidad?->telefono,
                                    'correo' => $pivot->correo ?: $entidad?->correo,
                                ];
                            @endphp
                            <tr>
                                <td style="background-color:#002060; color:#fff; font-weight:bold; font-style:italic;">{{ $numItem('contraparte_nombre') }}. Nombre de la contraparte{{ $esVoluntariado ? '' : ':' }}</td>
                                <td class="full-width" colspan="6">
                                    @if (!empty($isPdf))
                                        <div class="pdf-text-block">{!! $renderPdfText($datosContraparte->nombre) !!}</div>
                                    @else
                                        <div class="input-field-multiline-static">{{ $datosContraparte->nombre }}</div>
                                    @endif
                                </td>
                            </tr>
                            @if (!$esVoluntariado)
                            <tr>
                                <td class="sub-header" rowspan="1">RTN:</td>
                                <td class="full-width" colspan="6">
                                    @if (!empty($isPdf))
                                        <div class="pdf-text-block">{!! $renderPdfText($datosContraparte->rtn ?? '') !!}</div>
                                    @else
                                        <div class="input-field-multiline-static">{{ $datosContraparte->rtn ?? '' }}</div>
                                    @endif
                                </td>
                            </tr>
                            @endif
                            <tr>
                                <td rowspan="1" style="background-color:#002060; color:#fff; font-weight:bold; font-style:italic;">{{ $numItem('contraparte_tipo') }}. Tipo de contraparte{{ $esVoluntariado ? '' : ':' }}</td>
                                <td class="sub-header1 pdf-choice" colspan="1">Gobierno Nacional <br>
                                    @if (!empty($isPdf)){!! $pdfCheck($datosContraparte->tipo_entidad == 'gobierno_nacional') !!}@else<input disabled type="checkbox" class="No" @if ($datosContraparte->tipo_entidad == 'gobierno_nacional') checked @endif>@endif
                                </td>
                                <td class="sub-header1 pdf-choice" colspan="1">Gobierno Municipal<br>
                                    @if (!empty($isPdf)){!! $pdfCheck($datosContraparte->tipo_entidad == 'gobierno_municipal') !!}@else<input disabled type="checkbox" class="No" @if ($datosContraparte->tipo_entidad == 'gobierno_municipal') checked @endif>@endif
                                </td>
                                <td class="sub-header1 pdf-choice" colspan="1">ONG<br>
                                    @if (!empty($isPdf)){!! $pdfCheck($datosContraparte->tipo_entidad == 'ong') !!}@else<input disabled type="checkbox" class="No" @if ($datosContraparte->tipo_entidad == 'ong') checked @endif>@endif
                                </td>
                                <td class="sub-header1 pdf-choice" colspan="1">{{ $esVoluntariado ? 'Sociedad civil organizada' : 'Sociedad Civil Organizada' }}<br>
                                    @if (!empty($isPdf)){!! $pdfCheck($datosContraparte->tipo_entidad == 'sociedad_civil') !!}@else<input disabled type="checkbox" class="No" @if ($datosContraparte->tipo_entidad == 'sociedad_civil') checked @endif>@endif
                                </td>
                                <td class="sub-header1 pdf-choice" colspan="1">Sector Privado<br>
                                    @if (!empty($isPdf)){!! $pdfCheck($datosContraparte->tipo_entidad == 'sector_privado') !!}@else<input disabled type="checkbox" class="No" @if ($datosContraparte->tipo_entidad == 'sector_privado') checked @endif>@endif
                                </td>
                                <td class="sub-header1 pdf-choice" colspan="1">Internacional<br>
                                    @if (!empty($isPdf)){!! $pdfCheck($datosContraparte->tipo_entidad == 'internacional') !!}@else<input disabled type="checkbox" class="No" @if ($datosContraparte->tipo_entidad == 'internacional') checked @endif>@endif
                                </td>
                            </tr>
                            <tr>
                                <td rowspan="1" style="background-color:#002060; color:#fff; font-weight:bold; font-style:italic;">{{ $numItem('contraparte_contacto') }}. Nombre del contacto directo</td>
                                <td class="full-width" colspan="3">
                                    @if (!empty($isPdf))
                                        <div class="pdf-text-block">{!! $renderPdfText($datosContraparte->nombre_contacto) !!}</div>
                                    @else
                                        <div class="input-field-multiline-static">{{ $datosContraparte->nombre_contacto }}</div>
                                    @endif
                                </td>
                                <td class="sub-header" colspan="1">Correo Electrónico</td>
                                <td class="full-width" colspan="2">
                                    @if (!empty($isPdf))
                                        <div class="pdf-text-block">{!! $renderPdfText($datosContraparte->correo) !!}</div>
                                    @else
                                        <div class="input-field-multiline-static">{{ $datosContraparte->correo }}</div>
                                    @endif
                                </td>
                            </tr>
                            <tr>
                                <td colspan="1" style="background-color:#002060; color:#fff; font-weight:bold; font-style:italic;">{{ $numItem('contraparte_cargo') }}. {{ $esVoluntariado ? 'Cargo del contacto' : 'Cargo del contacto del proyecto' }}</td>
                                <td class="full-width" colspan="3">
                                    @if (!empty($isPdf))
                                        <div class="pdf-text-block">{!! $renderPdfText($datosContraparte->cargo_contacto ?? '') !!}</div>
                                    @else
                                        <div class="input-field-multiline-static">{{ $datosContraparte->cargo_contacto ?? '' }}</div>
                                    @endif
                                </td>
                                <td class="sub-header" colspan="1">Teléfono</td>
                                <td class="full-width" colspan="2">
                                    @if (!empty($isPdf))
                                        <div class="pdf-text-block">{!! $renderPdfText($datosContraparte->telefono) !!}</div>
                                    @else
                                        <div class="input-field-multiline-static">{{ $datosContraparte->telefono }}</div>
                                    @endif
                                </td>
                            </tr>
                            <tr>
                                <td colspan="1" style="background-color:#002060; color:#fff; font-weight:bold; font-style:italic;">{{ $numItem('contraparte_instrumento') }}. Tipo de instrumento que da lugar a la alianza</td>
                                <td class="sub-header1 pdf-choice" colspan="2">Carta formal de solicitud a la unidad académica <br>
                                    @if (!empty($isPdf)){!! $pdfCheck($pivot->instrumentoFormalizacion->contains('tipo_documento', 'carta_formal_solicitud')) !!}@else<input disabled type="checkbox" class="No" @if ($pivot->instrumentoFormalizacion->contains('tipo_documento', 'carta_formal_solicitud')) checked @endif>@endif
                                </td>
                                <td class="sub-header1 pdf-choice" colspan="2">Carta de intenciones con la UNAH<br>
                                    @if (!empty($isPdf)){!! $pdfCheck($pivot->instrumentoFormalizacion->contains('tipo_documento', 'carta_intenciones')) !!}@else<input disabled type="checkbox" class="No" @if ($pivot->instrumentoFormalizacion->contains('tipo_documento', 'carta_intenciones')) checked @endif>@endif
                                </td>
                                <td class="sub-header1 pdf-choice" colspan="2">Convenio marco con la UNAH<br>
                                    @if (!empty($isPdf)){!! $pdfCheck($pivot->instrumentoFormalizacion->contains('tipo_documento', 'convenio_marco')) !!}@else<input disabled type="checkbox" class="No" @if ($pivot->instrumentoFormalizacion->contains('tipo_documento', 'convenio_marco')) checked @endif>@endif
                                </td>
                            </tr>
                            <tr>
                                <td colspan="1" style="background-color:#002060; color:#fff; font-weight:bold; font-style:italic;">{{ $numItem('contraparte_compromisos') }}. Breve descripción de los compromisos asumidos por la contraparte</td>
                                <td class="full-width" colspan="6">
                                    @if (!empty($isPdf))
                                        <div class="pdf-text-block">{!! $renderPdfText($pivot->descripcion_acuerdos ?? '') !!}</div>
                                    @else
                                        <textarea disabled class="input-field" rows="3" placeholder="Describa los compromisos">{{ $pivot->descripcion_acuerdos ?? '' }}</textarea>
                                    @endif
                                </td>
                            </tr>
                            {{-- El FORM-DVUS-015 no tiene este bloque: en su PDF el instrumento ya consta en el ítem 22;
                                 en pantalla se conserva para consultar los documentos adjuntos. --}}
                            @if (!$esVoluntariado || empty($isPdf))
                            <tr>
                                <td class="sub-header" colspan="7">{{ $esVoluntariado ? 'Documentos de los instrumentos de formalización:' : 'Instrumentos de formalización de alianza (Si hubiese):' }}</td>
                            </tr>
                            <tr>
                                @forelse ($pivot->instrumentoFormalizacion as $instrumento)
                            <tr>
                                <td class="full-width
                                    " colspan="4">
                                    @if (!empty($isPdf))
                                        <div class="pdf-text-block">{!! $renderPdfText($instrumento->tipo_documento_display) !!}</div>
                                    @else
                                        <div class="input-field-multiline-static">{{ $instrumento->tipo_documento_display }}</div>
                                    @endif
                                </td>
                                <td class="full-width
                                    " colspan="3">
                                    @if (empty($isPdf) && !empty($instrumento->documento_url))
                                        <div x-data="{ open: false }">
                                            <button type="button" @click="open = true"
                                                class="inline-flex items-center gap-1.5 rounded-lg bg-blue-600 px-3 py-1.5 text-sm font-medium text-white hover:bg-blue-500 transition">
                                                Ver documento
                                            </button>
                                            <div x-show="open" x-cloak class="fixed inset-0 z-50 overflow-y-auto" role="dialog" aria-modal="true">
                                                <div class="fixed inset-0 bg-black/60" @click.self="open = false"></div>
                                                <div class="relative flex min-h-full items-start justify-center p-4">
                                                    <div class="relative w-full max-w-7xl bg-white dark:bg-gray-900 rounded-xl shadow-2xl my-4">
                                                        <div class="flex items-center justify-between border-b border-gray-200 dark:border-gray-700 px-6 py-3">
                                                            <span class="text-sm font-semibold text-gray-900 dark:text-white">Documento de formalización</span>
                                                            <button type="button" @click="open = false"
                                                                class="inline-flex items-center justify-center rounded-lg p-1.5 text-gray-500 hover:bg-gray-100 dark:hover:bg-gray-800 transition"
                                                                aria-label="Cerrar">
                                                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="h-5 w-5">
                                                                    <path d="M6.28 5.22a.75.75 0 0 0-1.06 1.06L8.94 10l-3.72 3.72a.75.75 0 1 0 1.06 1.06L10 11.06l3.72 3.72a.75.75 0 1 0 1.06-1.06L11.06 10l3.72-3.72a.75.75 0 0 0-1.06-1.06L10 8.94 6.28 5.22Z"/>
                                                                </svg>
                                                            </button>
                                                        </div>
                                                        <div class="p-0">
                                                            <template x-if="open">
                                                                <iframe src="{{ Storage::url($instrumento->documento_url) }}"
                                                                    style="width: 100%; height: 85vh; border: none;"></iframe>
                                                            </template>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <a href="{{ Storage::url($instrumento->documento_url) }}" download
                                            class="inline-flex items-center gap-1.5 rounded-lg bg-gray-200 px-3 py-1.5 text-sm font-medium text-gray-700 hover:bg-gray-300 transition">
                                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="h-4 w-4">
                                                <path d="M10.75 2.75a.75.75 0 0 0-1.5 0v8.614L6.295 8.235a.75.75 0 1 0-1.09 1.03l4.25 4.5a.75.75 0 0 0 1.09 0l4.25-4.5a.75.75 0 0 0-1.09-1.03l-2.955 3.129V2.75Z"/>
                                                <path d="M3.5 12.75a.75.75 0 0 0-1.5 0v2.5A2.75 2.75 0 0 0 4.75 18h10.5A2.75 2.75 0 0 0 18 15.25v-2.5a.75.75 0 0 0-1.5 0v2.5c0 .69-.56 1.25-1.25 1.25H4.75c-.69 0-1.25-.56-1.25-1.25v-2.5Z"/>
                                            </svg>
                                            Descargar
                                        </a>
                                    @elseif (!empty($instrumento->documento_url))
                                        <span>Documento adjunto</span>
                                    @else
                                        <span>No especificado</span>
                                    @endif

                                </td>
                            </tr>
                        @empty
                            <td class="full-width
                                    " colspan="7">
                                @if (!empty($isPdf))
                                    <div class="pdf-text-block">{!! $renderPdfText('No hay instrumentos de formalización') !!}</div>
                                @else
                                    <div class="input-field-multiline-static">No hay instrumentos de formalización</div>
                                @endif
                            </td>
                        @endforelse
                        </tr>
                            @endif
                    @empty
                        <tr>
                            <td class="full-width
                                " colspan="7">
                                @if (!empty($isPdf))
                                    <div class="pdf-text-block">{!! $renderPdfText('No hay entidades contraparte') !!}</div>
                                @else
                                    <div class="input-field-multiline-static">No hay entidades contraparte</div>
                                @endif
                            </td>
                        </tr>
                        @endforelse
                    </table>
                </div>


                <div class="section2">
                    <div class="section-title">V. FORMULACIÓN DEL PROYECTO </div>
                    <table class="table_datos3">
                        <tr>
                            <th class="header" colspan="19" style="text-align:left !important;">{{ $numItem('antecedentes') }}. @if ($esVoluntariado)ANTECEDENTES: (Explicar brevemente en qué consiste el programa, los antecedentes que dieron su origen y la importancia que tiene para los objetivos estratégicos de la UNAH. Este programa es de carácter ) @else DESCRIPCIÓN DE LOS ANTECEDENTES DEL PROYECTO: (Explicar brevemente los antecedentes que dieron su origen y
                                la importancia que tiene para los objetivos estratégicos de la UNAH) @endif</th>
                        </tr>
                        <tr>
                            <td class="full-width" colspan="19">
                                @if (!empty($isPdf))
                                    <div class="pdf-text-block">{!! $renderPdfText(old('resumen', $proyecto->resumen)) !!}</div>
                                @else
                                    <textarea disabled id="resumen" name="resumen" cols="30" rows="6" class="input-field"
                                        placeholder="Ingrese el resumen">{{ old('resumen', $proyecto->resumen) }}</textarea>
                                @endif
                            </td>
                        </tr>

                        </tr>
                        <tr>
                            <th class="header" colspan="19" style="text-align:left !important;">{{ $numItem('participantes') }}. @if ($esVoluntariado)DESCRIPCIÓN DE LAS PARTICIPANTES (Descripción breve de las unidades académicas participantes y su alineamiento con la estrategia de vinculación de la unidad. También se realizará una breve descripción de las contrapartes participantes, a qué se dedican y cómo se alinea el programa a los planes estratégicos @else DESCRIPCIÓN DE LOS PARTICIPANTES DEL PROYECTO (En esta sección se hace una breve
                                descripción de los alcances de la participación de los actores del proyecto. En el caso de la participación de
                                la UNAH, se describirá de manera sucinta, cómo se articula el proyecto de vinculación con las funciones de
                                la docencia (participación de asignaturas) y/o la investigación (si participa un grupo de investigación, o se
                                generan insumos de una investigación en marcha)) @endif</th>
                        </tr>
                        <tr>
                            <td class="sub-header" colspan="5" style="font-style:normal; font-weight:bold;">Descripción de la participación
                                de la UNAH en el proyecto a través de las funciones de docencia e investigación</td>
                            <td class="full-width" colspan="14">
                                @if (!empty($isPdf))
                                    <div class="pdf-text-block">{!! $renderPdfText($proyecto->participacion_unah) !!}</div>
                                @else
                                    <textarea disabled cols="30" rows="4" class="input-field"
                                        placeholder="Descripción de la participación de la UNAH">{{ $proyecto->participacion_unah ?? '' }}</textarea>
                                @endif
                            </td>
                        </tr>
                        <tr>
                            <td class="sub-header" colspan="5" style="font-style:normal; font-weight:bold;">Descripción de la participación
                                de la entidad contraparte</td>
                            <td class="full-width" colspan="14">
                                @if (!empty($isPdf))
                                    <div class="pdf-text-block">{!! $renderPdfText($proyecto->participacion_contraparte) !!}</div>
                                @else
                                    <textarea disabled cols="30" rows="4" class="input-field"
                                        placeholder="Descripción de la participación de la entidad contraparte">{{ $proyecto->participacion_contraparte ?? '' }}</textarea>
                                @endif
                            </td>
                        </tr>
                        <tr>
                            <td class="sub-header" colspan="5" style="font-style:normal; font-weight:bold;">Descripción de la partipación
                                de la comunidad beneficiada</td>
                            <td class="full-width" colspan="14">
                                @if (!empty($isPdf))
                                    <div class="pdf-text-block">{!! $renderPdfText($proyecto->participacion_comunidad) !!}</div>
                                @else
                                    <textarea disabled cols="30" rows="4" class="input-field"
                                        placeholder="Descripción de la participación de la comunidad beneficiada">{{ $proyecto->participacion_comunidad ?? '' }}</textarea>
                                @endif
                            </td>
                        </tr>

                        <tr>
                            <th class="header" colspan="19" style="text-align:left !important;">{{ $numItem('problema') }}. @if ($esVoluntariado)DEFINICIÓN DEL PROBLEMA. Breve descripción del problema que se desea resolver, indicando línea base que se tendrá en consideración para la definición de los resultados del programa @else DEFINICIÓN DEL PROBLEMA: Breve descripción del problema que se desea resolver, indicando línea base que se tendrá en consideración
                                para la definición de los resultados del proyecto. La línea base debe representarse con datos y debe de describirse las causas del problema identificado @endif</th>
                        </tr>
                        <tr>
                            <td class="full-width" colspan="19">
                                @if (!empty($isPdf))
                                    <div class="pdf-text-block">{!! $renderPdfText($proyecto->definicion_problema) !!}</div>
                                @else
                                    <textarea disabled cols="30" rows="6" class="input-field"
                                        placeholder="Definición del problema">{{ $proyecto->definicion_problema ?? '' }}</textarea>
                                @endif
                            </td>
                        </tr>

                        <tr>
                            <th class="header" colspan="19" style="text-align:left !important;">{{ $numItem('objetivo_general') }}. @if ($esVoluntariado)OBJETIVO GENERAL (El objetivo debe estar basado en la población participante del programa) @else OBJETIVO GENERAL (El objetivo debe estar basado en la población participante del proyecto) @endif</th>
                        </tr>
                        <tr>
                            <td class="full-width" colspan="19">
                                @if (!empty($isPdf))
                                    <div class="pdf-text-block">{!! $renderPdfText($proyecto->objetivo_general, 'Sin objetivo general especificado') !!}</div>
                                @else
                                    <textarea disabled cols="30" rows="4" class="input-field"
                                        placeholder="Objetivo general">{{ $proyecto->objetivo_general ?? '' }}</textarea>
                                @endif
                            </td>
                        </tr>

                        <tr>
                            <th class="header" colspan="19" style="text-align:left !important;">{{ $numItem('objetivos_especificos') }}. @if ($esVoluntariado)OBJETIVOS ESPECÍFICOS (Los objetivos específicos deben estar relacionados con los resultados que esperan obtener en el programa) @else OBJETIVOS ESPECÍFICOS (Los objetivos específicos deben estar relacionados con los resultados que esperan obtener en el proyecto) @endif</th>
                        </tr>
                        <tr>
                            <td class="full-width" colspan="19">
                                @php
                                    $objetivosEspecificosTexto = $proyecto->objetivosEspecificos
                                        ->map(fn($objetivoEsp, $idxObjetivoEsp) => ($idxObjetivoEsp + 1) . '. ' . $objetivoEsp->descripcion)
                                        ->implode("\n");
                                @endphp
                                @if (!empty($isPdf))
                                    <div class="pdf-text-block">{!! $renderPdfText($objetivosEspecificosTexto, 'Sin objetivos específicos registrados') !!}</div>
                                @else
                                    <textarea disabled cols="30" rows="6" class="input-field"
                                        placeholder="Objetivos específicos">{{ $objetivosEspecificosTexto }}</textarea>
                                @endif
                            </td>
                        </tr>

                        <tr>
                            <th class="header" colspan="19">{{ $numItem('resultados') }}. @if ($esVoluntariado)RESULTADOS DEL PROYECTO. El indicador de resultado es una medida específica y observable que permite evaluar el grado de cumplimiento de los resultados que se han planteado. Sirven para evaluar en qué medida y calidad se lograron los objetivos del programa. Hay tres tipos de resultados: 1) corto plazo, que son los productos que se obtendrán con el programa, 2) los de mediano plazo: que son los efectos que alcanzará el programa y 3) los de largo plazo: resultados de impacto. @else RESULTADOS DEL PROYECTO
                                El indicador de resultado es una medida específica y observable que permite evaluar el grado de cumplimiento
                                de los resultados que se han planteado. Sirven para evaluar en qué medida y calidad se lograron los objetivos
                                del proyecto. Hay tres tipos de resultados: 1) corto plazo, que son los productos que se obtendrán con el
                                proyecto, 2) los de mediano plazo: que son los efectos que alcanzará el proyecto y 3) los de largo plazo:
                                resultados de impacto. @endif</th>
                        </tr>

                        <tr>
                            <td class="header" colspan="19" style="text-align:left !important;">@if ($esVoluntariado)a) Resultados de corto plazo. Debe de plantearse resultados para cada objetivo específico. Son los productos que se lograrán a corto plazo @else a) Resultados de corto plazo del proyecto. Debe de plantearse resultados para cada objetivo específico. Son los productos que se lograrán a corto plazo @endif</td>
                        </tr>
                        <tr>
                            <td class="sub-header" colspan="2" style="font-style:normal; font-weight:bold; text-align:center;">OE<sup>{{ $esVoluntariado ? '1' : 'i' }}</sup></td>
                            <td class="sub-header" colspan="8" style="font-style:normal; font-weight:bold; text-align:center;">Descripción del resultado de corto plazo</td>
                            <td class="sub-header" colspan="9" style="font-style:normal; font-weight:bold; text-align:center;">Medio de verificación (indicador)</td>
                        </tr>
                        @php
                            $huboResultadoCortoPlazo = false;
                        @endphp
                        @foreach ($proyecto->objetivosEspecificos as $idxObjetivoEsp => $objetivoEsp)
                            @foreach ($objetivoEsp->resultados->where('plazo', 'corto_plazo') as $resultadoCorto)
                                @php $huboResultadoCortoPlazo = true; @endphp
                                <tr>
                                    <td class="full-width" colspan="2" style="text-align:center;">{{ $idxObjetivoEsp + 1 }}</td>
                                    <td class="full-width" colspan="8">
                                        @if (!empty($isPdf))
                                            <div class="pdf-text-block">{!! $renderPdfText($resultadoCorto->nombre_resultado) !!}</div>
                                        @else
                                            <textarea disabled cols="30" rows="2" class="input-field">{{ $resultadoCorto->nombre_resultado }}</textarea>
                                        @endif
                                    </td>
                                    <td class="full-width" colspan="9">
                                        @php
                                            $verificacionCorto = trim(($resultadoCorto->nombre_indicador ?? '') . (!empty($resultadoCorto->nombre_medio_verificacion) ? ' / ' . $resultadoCorto->nombre_medio_verificacion : ''));
                                        @endphp
                                        @if (!empty($isPdf))
                                            <div class="pdf-text-block">{!! $renderPdfText($verificacionCorto) !!}</div>
                                        @else
                                            <textarea disabled cols="30" rows="2" class="input-field">{{ $verificacionCorto }}</textarea>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        @endforeach
                        @if (!$huboResultadoCortoPlazo)
                            <tr>
                                <td class="full-width" colspan="19">
                                    @if (!empty($isPdf))
                                        <div class="pdf-text-block">{!! $renderPdfText('Sin resultados de corto plazo registrados') !!}</div>
                                    @else
                                        <textarea disabled cols="30" rows="2" class="input-field">Sin resultados de corto plazo registrados</textarea>
                                    @endif
                                </td>
                            </tr>
                        @endif

                        <tr>
                            <td class="header" colspan="19" style="text-align:left !important;">@if ($esVoluntariado)b) Indicadores de mediano plazo. Son los efectos que se esperan alcanzar del programa, es decir, la transformación esperada en la población beneficiada @else b) Resultados de mediano plazo. Son los efectos que se esperan alcanzar del proyecto, es decir, la transformación esperada en la población beneficiada @endif</td>
                        </tr>
                        <tr>
                            <td class="sub-header" colspan="9" style="font-style:normal; font-weight:bold; text-align:center;">Descripción del resultado</td>
                            <td class="sub-header" colspan="10" style="font-style:normal; font-weight:bold; text-align:center;">Medio de verificación (indicador)</td>
                        </tr>
                        @php
                            $resultadosMedianoPlazo = $proyecto->resultadosProyecto->where('plazo', 'mediano_plazo');
                        @endphp
                        @forelse ($resultadosMedianoPlazo as $resultadoMediano)
                            <tr>
                                <td class="full-width" colspan="9">
                                    @if (!empty($isPdf))
                                        <div class="pdf-text-block">{!! $renderPdfText($resultadoMediano->nombre_resultado) !!}</div>
                                    @else
                                        <textarea disabled cols="30" rows="2" class="input-field">{{ $resultadoMediano->nombre_resultado }}</textarea>
                                    @endif
                                </td>
                                <td class="full-width" colspan="10">
                                    @php
                                        $verificacionMediano = trim(($resultadoMediano->nombre_indicador ?? '') . (!empty($resultadoMediano->nombre_medio_verificacion) ? ' / ' . $resultadoMediano->nombre_medio_verificacion : ''));
                                    @endphp
                                    @if (!empty($isPdf))
                                        <div class="pdf-text-block">{!! $renderPdfText($verificacionMediano) !!}</div>
                                    @else
                                        <textarea disabled cols="30" rows="2" class="input-field">{{ $verificacionMediano }}</textarea>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td class="full-width" colspan="19">
                                    @if (!empty($isPdf))
                                        <div class="pdf-text-block">{!! $renderPdfText('Sin resultados de mediano plazo registrados') !!}</div>
                                    @else
                                        <textarea disabled cols="30" rows="2" class="input-field">Sin resultados de mediano plazo registrados</textarea>
                                    @endif
                                </td>
                            </tr>
                        @endforelse

                        <tr>
                            <td class="header" colspan="19" style="text-align:left !important;">@if ($esVoluntariado)c) Impacto que se desea generar (Debe de expresar los indicadores de impacto del programa) @else c) Impacto que se desea generar en el proyecto (Debe de expresar los indicadores de impacto del proyecto) @endif</td>
                        </tr>
                        <tr>
                            <td class="sub-header" colspan="9" style="font-style:normal; font-weight:bold; text-align:center;">Descripción del resultado de largo plazo</td>
                            <td class="sub-header" colspan="10" style="font-style:normal; font-weight:bold; text-align:center;">Medio de verificación (indicador con el que se evaluará su cumplimiento)</td>
                        </tr>
                        @php
                            $resultadosLargoPlazo = $proyecto->resultadosProyecto->where('plazo', 'largo_plazo');
                        @endphp
                        @forelse ($resultadosLargoPlazo as $resultadoLargo)
                            <tr>
                                <td class="full-width" colspan="9">
                                    @if (!empty($isPdf))
                                        <div class="pdf-text-block">{!! $renderPdfText($resultadoLargo->nombre_resultado) !!}</div>
                                    @else
                                        <textarea disabled cols="30" rows="2" class="input-field">{{ $resultadoLargo->nombre_resultado }}</textarea>
                                    @endif
                                </td>
                                <td class="full-width" colspan="10">
                                    @php
                                        $verificacionLargo = trim(($resultadoLargo->nombre_indicador ?? '') . (!empty($resultadoLargo->nombre_medio_verificacion) ? ' / ' . $resultadoLargo->nombre_medio_verificacion : ''));
                                    @endphp
                                    @if (!empty($isPdf))
                                        <div class="pdf-text-block">{!! $renderPdfText($verificacionLargo) !!}</div>
                                    @else
                                        <textarea disabled cols="30" rows="2" class="input-field">{{ $verificacionLargo }}</textarea>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td class="full-width" colspan="19">
                                    @if (!empty($isPdf))
                                        <div class="pdf-text-block">{!! $renderPdfText('Sin resultados de largo plazo registrados') !!}</div>
                                    @else
                                        <textarea disabled cols="30" rows="2" class="input-field">Sin resultados de largo plazo registrados</textarea>
                                    @endif
                                </td>
                            </tr>
                        @endforelse

                        <tr>
                            <td class="header" colspan="19" style="text-align:left !important;">{{ $numItem('ods') }}. @if ($esVoluntariado)OBJETIVOS DE DESARROLLO SOSTENIBLE (ODS) A LOS QUE SE CONTRIBUYE: Indicar el o los ODS a los que pretende contribuir el programa y las metas correspondientes. Para esta descripción deberá basarse en el documento de ODS que puede consultar en el siguiente enlace: @else OBJETIVOS DE DESARROLLO SOSTENIBLE (ODS) A LOS QUE SE CONTRIBUYE: Indicar el o los
                                ODS a los que pretende contribuir el proyecto y las metas correspondientes. Para esta descripción deberá basarse
                                en el documento de ODS que puede consultar en el siguiente enlace: @endif</th>
                        </tr>
                        <tr>
                            <td class="header" colspan="19">
                                <a href="https://www.un.org/sustainabledevelopment/es/objetivos-de-desarrollo-sostenible/">Objetivos y metas de desarrollo sostenible - Desarrollo Sostenible</a>
                            </td>
                        </tr>
                        <tr>
                            <td class="sub-header" colspan="9" style="font-style:normal; font-weight:bold; text-align:center;">ODS</td>
                            <td class="sub-header" colspan="10" style="font-style:normal; font-weight:bold; text-align:center;">Meta a la que se contribuye</td>
                        </tr>
                        @forelse ($proyecto->ods as $odsIndex => $ods)
                            <tr>
                                <td class="full-width" colspan="9">
                                    @php
                                        $odsTexto = $odsIndex === 0 && !$esVoluntariado ? $ods->nombre . ' (ODS principal)' : $ods->nombre;
                                    @endphp
                                    @if (!empty($isPdf))
                                        <div class="pdf-text-block">{!! $renderPdfText($odsTexto) !!}</div>
                                    @else
                                        <div class="input-field-multiline-static">{{ $odsTexto }}</div>
                                    @endif
                                </td>
                                <td class="full-width" colspan="10">
                                    @php
                                        $metasOds = $proyecto->metasContribuye->where('ods_id', $ods->id)->count() > 0
                                            ? $proyecto->metasContribuye->where('ods_id', $ods->id)->map(function($meta) { return 'Meta ' . $meta->numero_meta . ': ' . $meta->descripcion; })->implode("\n")
                                            : 'Sin metas específicas registradas';
                                    @endphp
                                    @if (!empty($isPdf))
                                        <div class="pdf-text-block">{!! $renderPdfText($metasOds) !!}</div>
                                    @else
                                        <textarea disabled class="input-field" rows="2" placeholder="Metas a las que contribuye">{{ $metasOds }}</textarea>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td class="full-width" colspan="19">
                                    @if (!empty($isPdf))
                                        <div class="pdf-text-block">{!! $renderPdfText('No hay Objetivos de Desarrollo Sostenible registrados para este proyecto') !!}</div>
                                    @else
                                        <textarea disabled cols="30" rows="6" class="input-field"
                                            placeholder="No hay ODS registrados">No hay Objetivos de Desarrollo Sostenible registrados para este proyecto</textarea>
                                    @endif
                                </td>
                            </tr>
                        @endforelse

                        <tr>
                            <td class="header" colspan="19">{{ $numItem('alineamiento_reforma') }}. @if ($esVoluntariado)ALINEAMIENTO CON LO ESENCIAL DE LA REFORMA DE LA UNAH (detalle brevemente cómo se alinean los ejes de lo esencial de la reforma en la ejecución de este programa, en resumen, describa qué competencias relacionadas con los ejes de lo esencial de la reforma adquirirán los estudiantes con la participación en este programa. @else ALINEAMIENTO CON LO ESENCIAL DE LA REFORMA DE LA UNAH (detalle brevemente cómo se alinean los ejes de lo esencial de la reforma en la ejecución de este proyecto, en resumen, describa qué competencias relacionadas con los ejes de lo esencial de la reforma adquirirán los estudiantes con la participación en este proyecto. @endif</th>
                        </tr>
                        <tr>
                            <td class="full-width" colspan="19">
                                @if (!empty($isPdf))
                                    <div class="pdf-text-block">{!! $renderPdfText($proyecto->alineamiento_reforma, 'No hay información específica registrada para este campo') !!}</div>
                                @else
                                    <textarea disabled cols="30" rows="6" class="input-field"
                                        placeholder="Alineamiento con la reforma">{{ $proyecto->alineamiento_reforma ?? 'No hay información específica registrada para este campo' }}</textarea>
                                @endif
                            </td>
                        </tr>
                        @if ($esVoluntariado)
                            <tr>
                                <td class="header" colspan="19" style="text-align:left !important;">{{ $numItem('experiencia') }}. DESCRIPCIÓN DE LA EXPERIENCIA ACADÉMICA QUE SE DESARROLLARÁ</td>
                            </tr>
                            <tr>
                                <td class="sub-header" colspan="5" style="font-style:normal; font-weight:bold;">Descripción de los conocimientos teóricos que se aplicarán</td>
                                <td class="full-width" colspan="14">
                                    @if (!empty($isPdf))
                                        <div class="pdf-text-block">{!! $renderPdfText($proyecto->experiencia_conocimientos_teoricos) !!}</div>
                                    @else
                                        <textarea disabled cols="30" rows="4" class="input-field">{{ $proyecto->experiencia_conocimientos_teoricos ?? '' }}</textarea>
                                    @endif
                                </td>
                            </tr>
                            <tr>
                                <td class="sub-header" colspan="5" style="font-style:normal; font-weight:bold;">Descripción de las habilidades técnicas que se aplicarán</td>
                                <td class="full-width" colspan="14">
                                    @if (!empty($isPdf))
                                        <div class="pdf-text-block">{!! $renderPdfText($proyecto->experiencia_habilidades_tecnicas) !!}</div>
                                    @else
                                        <textarea disabled cols="30" rows="4" class="input-field">{{ $proyecto->experiencia_habilidades_tecnicas ?? '' }}</textarea>
                                    @endif
                                </td>
                            </tr>
                            <tr>
                                <td class="sub-header" colspan="5" style="font-style:normal; font-weight:bold;">Descripción de las competencias blandas que adquirirán los(as) estudiantes con esta experiencia</td>
                                <td class="full-width" colspan="14">
                                    @if (!empty($isPdf))
                                        <div class="pdf-text-block">{!! $renderPdfText($proyecto->experiencia_competencias_blandas) !!}</div>
                                    @else
                                        <textarea disabled cols="30" rows="4" class="input-field">{{ $proyecto->experiencia_competencias_blandas ?? '' }}</textarea>
                                    @endif
                                </td>
                            </tr>
                        @endif
                        <tr>
                            <td class="header" colspan="19" style="text-align:left !important;">{{ $numItem('metodologia') }}. METODOLOGÍA</th>
                        </tr>
                        <tr>
                            <td class="full-width" colspan="19">
                                @if (!empty($isPdf))
                                    <div class="pdf-text-block">{!! $renderPdfText($proyecto->metodologia) !!}</div>
                                @else
                                    <textarea disabled cols="30" rows="6" class="input-field"
                                        placeholder="Metodología">{{ $proyecto->metodologia ?? '' }}</textarea>
                                @endif
                            </td>
                        </tr>
                        <tr>
                            <td class="header" colspan="19" style="text-align:left !important;">{{ $numItem('bibliografia') }}. BIBLIOGRAFÍA</th>
                        </tr>
                        <tr>
                            <td class="full-width" colspan="19">
                                @if (!empty($isPdf))
                                    <div class="pdf-text-block">{!! $renderPdfText($proyecto->bibliografia) !!}</div>
                                @else
                                    <textarea disabled cols="30" rows="6" class="input-field"
                                        placeholder="Bibliografía">{{ $proyecto->bibliografia ?? '' }}</textarea>
                                @endif
                            </td>
                        </tr>
                    </table>
                </div>

                @if ($esVoluntariado)
                    {{-- USO DE ESPACIOS, SERVICIOS Y MEDIOS INSTITUCIONALES (FORM-DVUS-015) --}}
                    <div class="section2">
                        <div class="section-title">{{ $numSec('espacios') }}INFORMACIÓN SOBRE EL USO DE ESPACIOS, SERVICIOS Y MEDIOS INSTITUCIONALES <span style="font-weight:normal;">(En esta sección detallarán los espacios o servicios de la UNAH, que utilizará para el desarrollo de la actividad, tales como: uso de laboratorios, aulas, auditorios, medios de comunicación, etc)</span></div>
                        <table class="table_datos3">
                            <thead>
                                <tr>
                                    <th class="header" style="text-align:left !important;">Descripción del servicio o infraestructura</th>
                                    <th class="header" style="text-align:left !important;">UBICACIÓN</th>
                                    <th class="header" style="text-align:left !important;">UNIDAD GESTORA</th>
                                    <th class="header" style="text-align:left !important;">Tiempo de uso (horas)</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($proyecto->espaciosInstitucionales as $espacio)
                                    <tr>
                                        <td class="full-width">{{ $espacio->descripcion }}</td>
                                        <td class="full-width">{{ $espacio->ubicacion }}</td>
                                        <td class="full-width">{{ $espacio->unidad_gestora }}</td>
                                        <td class="full-width" style="text-align:center;">{{ $espacio->tiempo_uso_horas }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td class="full-width" colspan="4" style="text-align:center;">No se registraron espacios, servicios o medios institucionales.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                @endif

                {{-- CRONOGRAMA --}}
                <div class="section2">
                    <div class="section-title">{{ $numSec('cronograma') }}CRONOGRAMA DE LAS ACTIVIDADES DEL PROYECTO</div>
                    <table class="table_datos3">
                        <thead>
                            <tr>
                                <th class="header" colspan="19" style="text-align:left !important;">{{ $numItem('actividades') }}. DESCRIPCIÓN DE ACTIVIDADES DEL PROYECTO (Descripción de todas las actividades enmarcadas en
                                    el proyecto, las cuales pueden ser, entre otras, la negociación inicial, la organización de los equipos de
                                    trabajo, la planificación, el desarrollo de actividades de capacitación y fortalecimiento, presentación de
                                    informe intermedio o parciales, presentación del informe final, proceso de evaluación, proceso de
                                    sistematización, publicación de artículo, otras acciones de divulgación)</th>
                            </tr>
                            <tr>
                                <td class="sub-header3" colspan="19">Cronograma de actividades</td>
                            </tr>
                            <tr>
                                <td class="sub-header3" colspan="4">Actividad</td>
                                <td class="sub-header3" colspan="4">Producto</td>
                                <td class="sub-header3" colspan="4">Fecha de ejecución</td>
                                <td class="sub-header3" colspan="4">Responsable</td>
                                <td class="sub-header3" colspan="3">Horas requeridas</td>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($proyecto->actividades as $actividad)
                                <tr>
                                    <td class="s3" colspan="4">
                                        @if (!empty($isPdf))
                                            <div class="pdf-text-block">{!! $renderPdfText($actividad->descripcion) !!}</div>
                                        @else
                                            {{ $actividad->descripcion }}
                                        @endif
                                    </td>
                                    <td class="s3" colspan="4">
                                        @if (!empty($isPdf))
                                            <div class="pdf-text-block">{!! $renderPdfText($actividad->resultados) !!}</div>
                                        @else
                                            {{ $actividad->resultados }}
                                        @endif
                                    </td>
                                    <td class="s3" colspan="4">{{ $actividad->fecha_inicio ? \Illuminate\Support\Carbon::parse($actividad->fecha_inicio)->format('d/m/Y') : '' }} - {{ $actividad->fecha_finalizacion ? \Illuminate\Support\Carbon::parse($actividad->fecha_finalizacion)->format('d/m/Y') : '' }}</td>
                                    <td class="s3" colspan="4">
                                        @forelse ($actividad->empleados as $responsable)
                                            @if (!empty($isPdf))
                                                <div class="pdf-text-block">{!! $renderPdfText($responsable->nombre_completo) !!}</div>
                                            @else
                                                @if (!empty($isPdf))
                                                    <div class="pdf-text-block">{!! $renderPdfText($responsable->nombre_completo) !!}</div>
                                                @else
                                                    <div class="input-field-multiline-static">{{ $responsable->nombre_completo }}</div>
                                                @endif
                                            @endif
                                        @empty
                                        @endforelse
                                    </td>
                                    <td class="s3" colspan="3" style="text-align:center;">{{ $actividad->horas }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td class="full-width" colspan="19">
                                        @if (!empty($isPdf))
                                            <div class="pdf-text-block">{!! $renderPdfText('No hay actividades registradas') !!}</div>
                                        @else
                                            <div class="input-field-multiline-static">No hay actividades registradas</div>
                                        @endif
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>


                {{-- PRESUPUESTO --}}
                <div class="section2 section-budget">
                    <div class="section-title">{{ $numSec('presupuesto') }}DETALLE DEL PRESUPUESTO</div>
                    <table class="table_datos3">
                        <tr>
                            <td class="header" colspan="19" style="text-align:left !important;">{{ $numItem('aporte_institucional') }}. APORTE INSTITUCIONAL (manifestado en lempiras)</td>
                        </tr>
                        <tr>
                            <td class="header" colspan="7">Concepto</td>
                            <td class="header" colspan="3">Unidad</td>
                            <td class="header" colspan="3">Cantidad</td>
                            <td class="header" colspan="3">{{ $esVoluntariado ? 'Costo unitario' : 'Costo Unitario' }}</td>
                            <td class="header" colspan="3">Costo Total</td>
                        </tr>
                        
                        @php
                            // Crear un array asociativo para fácil acceso a los conceptos
                            $conceptos = collect($proyecto->aporteInstitucional)->keyBy('concepto');
                            $baseAporteInstitucional = collect([
                                $conceptos->get('horas_trabajo_docentes'),
                                $conceptos->get('horas_trabajo_estudiantes'),
                                $conceptos->get('gastos_movilizacion'),
                                $conceptos->get('utiles_materiales_oficina'),
                                $conceptos->get('gastos_impresion'),
                            ])->filter();
                            // 3% sobre la sumatoria de los conceptos a–e (ver etiquetas f/g y formatos oficiales).
                            // f) y g): 3% de la sumatoria (costo total) de a–e, igual que
                            // CreateProyectoVinculacion::recalculateAporteInstitucional(). Se calculan aquí
                            // y no se leen de lo guardado porque los proyectos enviados antes de la
                            // corrección conservan valores de una fórmula anterior.
                            $cantidadIndirecta = 3;
                            $costoUnitarioIndirecto = round((float) $baseAporteInstitucional->sum('costo_total'), 2);
                            $costoTotalIndirecto = round($costoUnitarioIndirecto * 0.03, 2);
                            $totalAporteInstitucionalFicha = round($costoUnitarioIndirecto + 2 * $costoTotalIndirecto, 2);
                        @endphp
                        
                        <!-- Horas de trabajo docentes -->
                        <tr>
                            <td class="sub-header" colspan="7">a) Horas de trabajo docentes</td>
                            <td class="sub-header" colspan="3">Hra/profes</td>
                            <td class="full-width" colspan="3">
                                @if (!empty($isPdf))
                                    <div class="pdf-text-block">{!! $renderPdfText($conceptos->get('horas_trabajo_docentes')?->cantidad ?? '') !!}</div>
                                @else
                                    <div class="input-field-multiline-static">{{ $conceptos->get('horas_trabajo_docentes')?->cantidad ?? '' }}</div>
                                @endif
                            </td>
                            <td class="full-width" colspan="3">
                                @if (!empty($isPdf))
                                    <div class="pdf-text-block">{!! $renderPdfText($conceptos->get('horas_trabajo_docentes')?->costo_unitario ?? '') !!}</div>
                                @else
                                    <div class="input-field-multiline-static">{{ $conceptos->get('horas_trabajo_docentes')?->costo_unitario ?? '' }}</div>
                                @endif
                            </td>
                            <td class="full-width" colspan="3">
                                @if (!empty($isPdf))
                                    <div class="pdf-text-block">{!! $renderPdfText($conceptos->get('horas_trabajo_docentes')?->costo_total ?? '') !!}</div>
                                @else
                                    <div class="input-field-multiline-static">{{ $conceptos->get('horas_trabajo_docentes')?->costo_total ?? '' }}</div>
                                @endif
                            </td>
                        </tr>
                        
                        <!-- Horas de trabajo estudiantes -->
                        <tr>
                            <td class="sub-header" colspan="7">b) Horas de trabajo estudiantes</td>
                            <td class="sub-header" colspan="3">Hra/estud</td>
                            <td class="full-width" colspan="3">
                                @if (!empty($isPdf))
                                    <div class="pdf-text-block">{!! $renderPdfText($conceptos->get('horas_trabajo_estudiantes')?->cantidad ?? '') !!}</div>
                                @else
                                    <div class="input-field-multiline-static">{{ $conceptos->get('horas_trabajo_estudiantes')?->cantidad ?? '' }}</div>
                                @endif
                            </td>
                            <td class="full-width" colspan="3">
                                @if (!empty($isPdf))
                                    <div class="pdf-text-block">{!! $renderPdfText($conceptos->get('horas_trabajo_estudiantes')?->costo_unitario ?? '') !!}</div>
                                @else
                                    <div class="input-field-multiline-static">{{ $conceptos->get('horas_trabajo_estudiantes')?->costo_unitario ?? '' }}</div>
                                @endif
                            </td>
                            <td class="full-width" colspan="3">
                                @if (!empty($isPdf))
                                    <div class="pdf-text-block">{!! $renderPdfText($conceptos->get('horas_trabajo_estudiantes')?->costo_total ?? '') !!}</div>
                                @else
                                    <div class="input-field-multiline-static">{{ $conceptos->get('horas_trabajo_estudiantes')?->costo_total ?? '' }}</div>
                                @endif
                            </td>
                        </tr>
                        
                        <!-- Gastos de movilización -->
                        <tr>
                            <td class="sub-header" colspan="7">c) Gastos de movilización</td>
                            <td class="sub-header" colspan="3">Global</td>
                            <td class="full-width" colspan="3">
                                @if (!empty($isPdf))
                                    <div class="pdf-text-block">{!! $renderPdfText($conceptos->get('gastos_movilizacion')?->cantidad ?? '') !!}</div>
                                @else
                                    <div class="input-field-multiline-static">{{ $conceptos->get('gastos_movilizacion')?->cantidad ?? '' }}</div>
                                @endif
                            </td>
                            <td class="full-width" colspan="3">
                                @if (!empty($isPdf))
                                    <div class="pdf-text-block">{!! $renderPdfText($conceptos->get('gastos_movilizacion')?->costo_unitario ?? '') !!}</div>
                                @else
                                    <div class="input-field-multiline-static">{{ $conceptos->get('gastos_movilizacion')?->costo_unitario ?? '' }}</div>
                                @endif
                            </td>
                            <td class="full-width" colspan="3">
                                @if (!empty($isPdf))
                                    <div class="pdf-text-block">{!! $renderPdfText($conceptos->get('gastos_movilizacion')?->costo_total ?? '') !!}</div>
                                @else
                                    <div class="input-field-multiline-static">{{ $conceptos->get('gastos_movilizacion')?->costo_total ?? '' }}</div>
                                @endif
                            </td>
                        </tr>
                        
                        <!-- Útiles y materiales de oficina -->
                        <tr>
                            <td class="sub-header" colspan="7">d) Útiles y materiales de oficina</td>
                            <td class="sub-header" colspan="3">Global</td>
                            <td class="full-width" colspan="3">
                                @if (!empty($isPdf))
                                    <div class="pdf-text-block">{!! $renderPdfText($conceptos->get('utiles_materiales_oficina')?->cantidad ?? '') !!}</div>
                                @else
                                    <div class="input-field-multiline-static">{{ $conceptos->get('utiles_materiales_oficina')?->cantidad ?? '' }}</div>
                                @endif
                            </td>
                            <td class="full-width" colspan="3">
                                @if (!empty($isPdf))
                                    <div class="pdf-text-block">{!! $renderPdfText($conceptos->get('utiles_materiales_oficina')?->costo_unitario ?? '') !!}</div>
                                @else
                                    <div class="input-field-multiline-static">{{ $conceptos->get('utiles_materiales_oficina')?->costo_unitario ?? '' }}</div>
                                @endif
                            </td>
                            <td class="full-width" colspan="3">
                                @if (!empty($isPdf))
                                    <div class="pdf-text-block">{!! $renderPdfText($conceptos->get('utiles_materiales_oficina')?->costo_total ?? '') !!}</div>
                                @else
                                    <div class="input-field-multiline-static">{{ $conceptos->get('utiles_materiales_oficina')?->costo_total ?? '' }}</div>
                                @endif
                            </td>
                        </tr>
                        
                        <!-- Gastos de impresión -->
                        <tr>
                            <td class="sub-header" colspan="7">e) Gastos de impresión</td>
                            <td class="sub-header" colspan="3">Global</td>
                            <td class="full-width" colspan="3">
                                @if (!empty($isPdf))
                                    <div class="pdf-text-block">{!! $renderPdfText($conceptos->get('gastos_impresion')?->cantidad ?? '') !!}</div>
                                @else
                                    <div class="input-field-multiline-static">{{ $conceptos->get('gastos_impresion')?->cantidad ?? '' }}</div>
                                @endif
                            </td>
                            <td class="full-width" colspan="3">
                                @if (!empty($isPdf))
                                    <div class="pdf-text-block">{!! $renderPdfText($conceptos->get('gastos_impresion')?->costo_unitario ?? '') !!}</div>
                                @else
                                    <div class="input-field-multiline-static">{{ $conceptos->get('gastos_impresion')?->costo_unitario ?? '' }}</div>
                                @endif
                            </td>
                            <td class="full-width" colspan="3">
                                @if (!empty($isPdf))
                                    <div class="pdf-text-block">{!! $renderPdfText($conceptos->get('gastos_impresion')?->costo_total ?? '') !!}</div>
                                @else
                                    <div class="input-field-multiline-static">{{ $conceptos->get('gastos_impresion')?->costo_total ?? '' }}</div>
                                @endif
                            </td>
                        </tr>
                        
                        <!-- Costos indirectos por infraestructura -->
                        <tr>
                            <td class="sub-header" colspan="7">f) Costos indirectos por infraestructura universidad (depreciación de equipo, 3% calculado sobre la sumatoria de los conceptos a – e)</td>
                            <td class="sub-header" colspan="3">%</td>
                            <td class="full-width" colspan="3">
                                @if (!empty($isPdf))
                                    <div class="pdf-text-block">{!! $renderPdfText(number_format($cantidadIndirecta, 2, '.', ',')) !!}</div>
                                @else
                                    <div class="input-field-multiline-static">{{ number_format($cantidadIndirecta, 2, '.', ',') }}</div>
                                @endif
                            </td>
                            <td class="full-width" colspan="3">
                                @if (!empty($isPdf))
                                    <div class="pdf-text-block">{!! $renderPdfText(number_format($costoUnitarioIndirecto, 2, '.', ',')) !!}</div>
                                @else
                                    <div class="input-field-multiline-static">{{ number_format($costoUnitarioIndirecto, 2, '.', ',') }}</div>
                                @endif
                            </td>
                            <td class="full-width" colspan="3">
                                @if (!empty($isPdf))
                                    <div class="pdf-text-block">{!! $renderPdfText(number_format($costoTotalIndirecto, 2, '.', ',')) !!}</div>
                                @else
                                    <div class="input-field-multiline-static">{{ number_format($costoTotalIndirecto, 2, '.', ',') }}</div>
                                @endif
                            </td>
                        </tr>
                        
                        <!-- Costos indirectos por servicios públicos -->
                        <tr>
                            <td class="sub-header" colspan="7">g) Costos indirectos por servicios públicos (internet, electricidad, otros, 3% calculado sobre la sumatoria de los conceptos a – e)</td>
                            <td class="sub-header" colspan="3">%</td>
                            <td class="full-width" colspan="3">
                                @if (!empty($isPdf))
                                    <div class="pdf-text-block">{!! $renderPdfText(number_format($cantidadIndirecta, 2, '.', ',')) !!}</div>
                                @else
                                    <div class="input-field-multiline-static">{{ number_format($cantidadIndirecta, 2, '.', ',') }}</div>
                                @endif
                            </td>
                            <td class="full-width" colspan="3">
                                @if (!empty($isPdf))
                                    <div class="pdf-text-block">{!! $renderPdfText(number_format($costoUnitarioIndirecto, 2, '.', ',')) !!}</div>
                                @else
                                    <div class="input-field-multiline-static">{{ number_format($costoUnitarioIndirecto, 2, '.', ',') }}</div>
                                @endif
                            </td>
                            <td class="full-width" colspan="3">
                                @if (!empty($isPdf))
                                    <div class="pdf-text-block">{!! $renderPdfText(number_format($costoTotalIndirecto, 2, '.', ',')) !!}</div>
                                @else
                                    <div class="input-field-multiline-static">{{ number_format($costoTotalIndirecto, 2, '.', ',') }}</div>
                                @endif
                            </td>
                        </tr>
                        
                        <!-- Fila de totales y aportes -->
                        <tr>
                            <td class="sub-headeri" colspan="16">Total aporte institucional</td>
                            <td class="full-width" colspan="3">
                                @if (!empty($isPdf))
                                    <div class="pdf-text-block">{!! $renderPdfText(number_format($totalAporteInstitucionalFicha, 2, '.', ',')) !!}</div>
                                @else
                                    <div class="input-field-multiline-static">{{ number_format($totalAporteInstitucionalFicha, 2, '.', ',') }}</div>
                                @endif
                            </td>
                        </tr>
                        <tr>
                            <td class="header" colspan="19" style="text-align:left !important;">{{ $numItem('otras_aportaciones') }}. OTRAS APORTACIONES (Manifestado en lempiras)</td>
                        </tr>
                        <tr>
                            <td class="sub-headert" colspan="16">Aporte de la contraparte</td>
                            <td class="full-width" colspan="3">
                                @if (!empty($isPdf))
                                    <div class="pdf-text-block">{!! $renderPdfText(number_format($proyecto->presupuesto?->aporte_contraparte ?? 0, 2, '.', ',')) !!}</div>
                                @else
                                    <div class="input-field-multiline-static">{{ number_format($proyecto->presupuesto?->aporte_contraparte ?? 0, 2, '.', ',') }}</div>
                                @endif
                            </td>
                        </tr>
                        <tr>
                            <td class="sub-headert" colspan="16">Aporte fondos internacionales</td>
                            <td class="full-width" colspan="3">
                                @if (!empty($isPdf))
                                    <div class="pdf-text-block">{!! $renderPdfText(number_format($proyecto->presupuesto?->aporte_internacionales ?? 0, 2, '.', ',')) !!}</div>
                                @else
                                    <div class="input-field-multiline-static">{{ number_format($proyecto->presupuesto?->aporte_internacionales ?? 0, 2, '.', ',') }}</div>
                                @endif
                            </td>
                        </tr>
                        <tr>
                            <td class="sub-headert" colspan="16">Aporte de otras universidades</td>
                            <td class="full-width" colspan="3">
                                @if (!empty($isPdf))
                                    <div class="pdf-text-block">{!! $renderPdfText(number_format($proyecto->presupuesto?->aporte_otras_universidades ?? 0, 2, '.', ',')) !!}</div>
                                @else
                                    <div class="input-field-multiline-static">{{ number_format($proyecto->presupuesto?->aporte_otras_universidades ?? 0, 2, '.', ',') }}</div>
                                @endif
                            </td>
                        </tr>
                        <tr>
                            <td class="sub-headert" colspan="16">Aporte de los beneficiarios (comunidad)</td>
                            <td class="full-width" colspan="3">
                                @if (!empty($isPdf))
                                    <div class="pdf-text-block">{!! $renderPdfText(number_format($proyecto->presupuesto?->aporte_comunidad ?? 0, 2, '.', ',')) !!}</div>
                                @else
                                    <div class="input-field-multiline-static">{{ number_format($proyecto->presupuesto?->aporte_comunidad ?? 0, 2, '.', ',') }}</div>
                                @endif
                            </td>
                        </tr>
                        <tr>
                            <td class="sub-headert" colspan="16">Otros aportes</td>
                            <td class="full-width" colspan="3">
                                @if (!empty($isPdf))
                                    <div class="pdf-text-block">{!! $renderPdfText(number_format($proyecto->presupuesto?->otros_aportes ?? 0, 2, '.', ',')) !!}</div>
                                @else
                                    <div class="input-field-multiline-static">{{ number_format($proyecto->presupuesto?->otros_aportes ?? 0, 2, '.', ',') }}</div>
                                @endif
                            </td>
                        </tr>
                        @php
                            $totalOtrasAportaciones = ($proyecto->presupuesto?->aporte_contraparte ?? 0) +
                                ($proyecto->presupuesto?->aporte_internacionales ?? 0) +
                                ($proyecto->presupuesto?->aporte_otras_universidades ?? 0) +
                                ($proyecto->presupuesto?->aporte_comunidad ?? 0) +
                                ($proyecto->presupuesto?->otros_aportes ?? 0);
                        @endphp
                        <tr>
                            <td class="sub-headeri" colspan="16">Total otras aportaciones</td>
                            <td class="full-width" colspan="3">
                                @if (!empty($isPdf))
                                    <div class="pdf-text-block">{!! $renderPdfText(number_format($totalOtrasAportaciones, 2, '.', ',')) !!}</div>
                                @else
                                    <div class="input-field-multiline-static">{{ number_format($totalOtrasAportaciones, 2, '.', ',') }}</div>
                                @endif
                            </td>
                        </tr>
                        <tr>
                            <td class="sub-headeri" colspan="16">TOTAL PROYECTO (Aporte institucional + otras aportaciones)</td>
                            <td class="full-width" colspan="3">
                                @if (!empty($isPdf))
                                    <div class="pdf-text-block">{!! $renderPdfText(number_format(($totalAporteInstitucionalFicha) + $totalOtrasAportaciones, 2, '.', ',')) !!}</div>
                                @else
                                    <div class="input-field-multiline-static">{{ number_format(($totalAporteInstitucionalFicha) + $totalOtrasAportaciones, 2, '.', ',') }}</div>
                                @endif
                            </td>
                        </tr>
                    </table>
                    @if ($esVoluntariado)
                        <p style="margin-top:6px; font-size:0.9em;"><strong>Nota:</strong> El aporte de la institución contraparte y de la comunidad deberá ser certificada al finalizar el proyecto mediante documento de declaración firmada por el representante legal de la entidad contraparte y/o comunidad. De no poder contarse con este documento, no se deberá de detallar este dato.</p>
                    @endif
                </div>

                {{-- FIRMAS --}}
                <div class="section3 section-signatures">
                    @include('components.fichas.firmas-fijas-proyecto', [
                        'proyecto' => $proyecto,
                        'isPdf' => $isPdf ?? false,
                        'tituloFirmas' => $esVoluntariado ? $numSec('firmas') . 'FIRMAS' : 'VIII. FIRMAS',
                        'esVoluntariado' => $esVoluntariado,
                    ])
                </div>

                @if (!$esVoluntariado)
                {{-- DOCUMENTOS ADJUNTOS --}}
                <div class="section4 section-documents">
                    <div class="section-title">DOCUMENTOS ADJUNTOS A LA FICHA</div>
                    @php
                        $anexosPorCodigo = $proyecto->anexos
                            ->filter(fn ($anexo) => filled($anexo->tipoAnexo?->codigo))
                            ->groupBy(fn ($anexo) => $anexo->tipoAnexo->codigo);
                        $detallesOtros = $anexosPorCodigo
                            ->get(\App\Models\Proyecto\TipoAnexo::CODIGO_OTROS, collect())
                            ->pluck('detalle')
                            ->map(fn ($detalle) => trim((string) $detalle))
                            ->filter()
                            ->unique()
                            ->implode('; ');
                        $documentosAdjuntos = collect([
                            [
                                'numero' => 1,
                                'codigo' => \App\Models\Proyecto\TipoAnexo::CODIGO_CARTA_SOLICITUD,
                                'descripcion' => 'Carta de solicitud del proyecto firmada por el representante legal de la contraparte',
                            ],
                            [
                                'numero' => 2,
                                'codigo' => \App\Models\Proyecto\TipoAnexo::CODIGO_CONVENIO_CARTA,
                                'descripcion' => 'Convenio/ carta de intenciones firmada entre la UNAH y contraparte',
                            ],
                            [
                                'numero' => 3,
                                'codigo' => \App\Models\Proyecto\TipoAnexo::CODIGO_OFICIO_REMISION,
                                'descripcion' => 'Oficio de remisión del Decano/Director Centro Regional',
                            ],
                            [
                                'numero' => 4,
                                'codigo' => \App\Models\Proyecto\TipoAnexo::CODIGO_OTROS,
                                'descripcion' => 'Otros (detallar)'.($detallesOtros !== '' ? ': '.$detallesOtros : ''),
                            ],
                        ])->map(fn (array $documento) => array_merge($documento, [
                            'adjunto' => $anexosPorCodigo->has($documento['codigo']),
                        ]));
                    @endphp
                    <table class="table_datos5">
                        <tr>
                            <th class="header" colspan="1">No</th>
                            <th class="header" colspan="10">Descripción</th>
                            <th class="header" colspan="4">Si</th>
                            <th class="header" colspan="4">No</th>
                        </tr>
                        @foreach ($documentosAdjuntos as $documento)
                            <tr>
                                <td class="sub-header" colspan="1">{{ $documento['numero'] }}</td>
                                <td class="full-width" colspan="10">{{ $documento['descripcion'] }}</td>
                                <td class="full-width" colspan="4">
                                    @if (!empty($isPdf)){!! $pdfCheck($documento['adjunto']) !!}@else<input disabled type="checkbox" class="checkbox-field" @checked($documento['adjunto'])>@endif
                                </td>
                                <td class="full-width" colspan="4">
                                    @if (!empty($isPdf)){!! $pdfCheck(! $documento['adjunto']) !!}@else<input disabled type="checkbox" class="checkbox-field" @checked(! $documento['adjunto'])>@endif
                                </td>
                            </tr>
                        @endforeach
                    </table>
                    
                    <div class="documents-note">
                        <p><strong>Nota:</strong> El documento 1 o el documento 2 (cualquiera de los dos) es obligatorio. El documento 3 es obligatorio.</p>
                    </div>
                </div>

                {{-- ANEXOS DEL SISTEMA --}}
                <div class="section4 section-annexes">
                    <div class="section-title">XI. ANEXOS</div>
                    <table class="table_datos5">
                        <tr>
                            <th class="header" colspan="19">Anexos registrados en el sistema</th>
                        </tr>
                        @forelse ($proyecto->anexos as $anexo)
                            <tr>
                            <td class="full-width
                                " colspan="8">
                                @if (!empty($isPdf))
                                    <div class="pdf-text-block">{!! $renderPdfText('ANEXO DEL PROYECTO') !!}</div>
                                @else
                                    <div class="input-field-multiline-static">ANEXO DEL PROYECTO</div>
                                @endif
                           </td>
                            <td class="full-width" colspan="11">
                                @if (empty($isPdf))
                                    <div x-data="{ open: false }">
                                        <button type="button" @click="open = true"
                                            class="inline-flex items-center gap-1.5 rounded-lg bg-blue-600 px-3 py-1.5 text-sm font-medium text-white hover:bg-blue-500 transition">
                                            Ver anexo
                                        </button>
                                        <div x-show="open" x-cloak class="fixed inset-0 z-50 overflow-y-auto" role="dialog" aria-modal="true">
                                            <div class="fixed inset-0 bg-black/60" @click.self="open = false"></div>
                                            <div class="relative flex min-h-full items-start justify-center p-4">
                                                <div class="relative w-full max-w-7xl bg-white dark:bg-gray-900 rounded-xl shadow-2xl my-4">
                                                    <div class="flex items-center justify-between border-b border-gray-200 dark:border-gray-700 px-6 py-3">
                                                        <span class="text-sm font-semibold text-gray-900 dark:text-white">Anexo</span>
                                                        <button type="button" @click="open = false"
                                                            class="inline-flex items-center justify-center rounded-lg p-1.5 text-gray-500 hover:bg-gray-100 dark:hover:bg-gray-800 transition"
                                                            aria-label="Cerrar">
                                                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="h-5 w-5">
                                                                <path d="M6.28 5.22a.75.75 0 0 0-1.06 1.06L8.94 10l-3.72 3.72a.75.75 0 1 0 1.06 1.06L10 11.06l3.72 3.72a.75.75 0 1 0 1.06-1.06L11.06 10l3.72-3.72a.75.75 0 0 0-1.06-1.06L10 8.94 6.28 5.22Z"/>
                                                            </svg>
                                                        </button>
                                                    </div>
                                                    <div class="p-0">
                                                        <template x-if="open">
                                                            <iframe src="{{ Storage::url($anexo->documento_url) }}"
                                                                style="width: 100%; height: 85vh; border: none;"></iframe>
                                                        </template>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <a href="{{ Storage::url($anexo->documento_url) }}" download
                                        class="inline-flex items-center gap-1.5 rounded-lg bg-gray-200 px-3 py-1.5 text-sm font-medium text-gray-700 hover:bg-gray-300 transition">
                                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="h-4 w-4">
                                            <path d="M10.75 2.75a.75.75 0 0 0-1.5 0v8.614L6.295 8.235a.75.75 0 1 0-1.09 1.03l4.25 4.5a.75.75 0 0 0 1.09 0l4.25-4.5a.75.75 0 0 0-1.09-1.03l-2.955 3.129V2.75Z"/>
                                            <path d="M3.5 12.75a.75.75 0 0 0-1.5 0v2.5A2.75 2.75 0 0 0 4.75 18h10.5A2.75 2.75 0 0 0 18 15.25v-2.5a.75.75 0 0 0-1.5 0v2.5c0 .69-.56 1.25-1.25 1.25H4.75c-.69 0-1.25-.56-1.25-1.25v-2.5Z"/>
                                        </svg>
                                        Descargar
                                    </a>
                                @else
                                    <span>Anexo adjunto</span>
                                @endif
                            </td>
                            </tr>
                        @empty
                            <tr>
                                <td class="full-width" colspan="19">
                                    @if (!empty($isPdf))
                                        <div class="pdf-text-block">{!! $renderPdfText('No hay anexos registrados en este momento') !!}</div>
                                    @else
                                        <div class="input-field-multiline-static">No hay anexos registrados en este momento</div>
                                    @endif
                                </td>
                            </tr>
                        @endforelse
                    </table>
                </div>
                @endif

            </div>
        </div>
    </div>


@unless($embebido ?? false)
</body>


</html>
@endunless
