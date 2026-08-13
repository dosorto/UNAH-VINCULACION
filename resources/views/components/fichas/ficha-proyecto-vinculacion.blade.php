<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=3, user-scalable=yes">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>FORM-DVUS-001 - Registro de Proyectos de Vinculación</title>
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
            background-color: #001b44;
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

        .signature-image-cell img {
            display: block;
            width: auto;
            max-width: 200px;
            max-height: 110px;
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
                background: #001b44 !important;
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
</head>

<body style="background-color: #f2f2f2; ">
    @php
        $renderPdfText = static function ($value, string $fallback = '') {
            $text = filled($value) ? (string) $value : $fallback;

            return nl2br(e($text));
        };
        $pdfCheck = static fn (bool $checked) => new \Illuminate\Support\HtmlString(
            '<span class="pdf-check'.($checked ? ' is-checked' : '').'">'.($checked ? 'X' : '&nbsp;').'</span>'
        );
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
        @include('components.fichas.partials.form-dvus-001-header', ['isPdf' => true])
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
                    @include('components.fichas.partials.form-dvus-001-header', ['isPdf' => false])
                @endif

                {{-- INFORMACIÓN GENERAL --}}
                <div class="section1">
                    <div class="section-title">I. INFORMACIÓN GENERAL DEL PROYECTO </div>
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
                        @endphp
                        <tr>
                            <th class="full-width1">1. Fecha de solicitud de registro:</th>
                            <td class="full-width date-cell" colspan="5">
                                <table class="date-inner-table">
                                    <tr>
                                        <th>Día</th>
                                        <th>Mes</th>
                                        <th>Año</th>
                                    </tr>
                                    <tr>
                                        <td>{{ $fechaRegistro ? $fechaRegistro->format('d') : '' }}</td>
                                        <td>{{ $fechaRegistro ? $fechaRegistro->format('m') : '' }}</td>
                                        <td>{{ $fechaRegistro ? $fechaRegistro->format('Y') : '' }}</td>
                                    </tr>
                                </table>
                            </td>
                        </tr>
                        <tr>
                            <th class="full-width1">2. Nombre del Proyecto:</th>
                            <td class="full-width" colspan="5">

                                @if (!empty($isPdf))
                                    <div class="pdf-text-block">{!! $renderPdfText($proyecto->nombre_proyecto) !!}</div>
                                @else
                                    <div class="input-field-multiline-static">{{ $proyecto->nombre_proyecto }}</div>
                                @endif
                            </td>
                        </tr>
                        <tr>
                            <th class="full-width1" rowspan="3">3. Unidad Académica:</th>
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
                            <td class="sub-header" colspan="1">Carreras</td>
                            <td class="full-width" colspan="4">
                                <ul>
                                    @foreach ($proyecto->carreras as $carrera)
                                        <li>{{ $carrera->nombre }}</li>
                                    @endforeach
                                </ul>
                            </td>
                        </tr>
                        <tr>
                            <th class="full-width1" rowspan="1">4. Modalidad</th>
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
                            <th class="full-width1" rowspan="3">5. Alineamiento institucional</th>
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

                        <!-- FECHAS DE EJECUCION  -->
                        @php
                            $fechaInicio = $proyecto->fecha_inicio;
                            $fechaFinalizacion = $proyecto->fecha_finalizacion;
                        @endphp
                        <tr>
                            <th class="full-width1" rowspan="1">6. Fecha de ejecución</th>
                            <td class="full-width date-cell" colspan="5" style="padding:0 !important;">
                                <table style="width:100%; border-collapse:collapse; table-layout:fixed;">
                                    <tr>
                                        <th colspan="3" style="width:50%; background-color:#001b44; color:#fff; border:1px solid #000; padding:3px 5px; text-align:center;">Fecha de inicio</th>
                                        <th colspan="3" style="width:50%; background-color:#001b44; color:#fff; border:1px solid #000; padding:3px 5px; text-align:center;">Fecha de finalización</th>
                                    </tr>
                                    <tr>
                                        <th style="width:16.6667%; background-color:#001b44; color:#fff; border:1px solid #000; padding:3px 5px; text-align:center;">Día</th>
                                        <th style="width:16.6667%; background-color:#001b44; color:#fff; border:1px solid #000; padding:3px 5px; text-align:center;">Mes</th>
                                        <th style="width:16.6667%; background-color:#001b44; color:#fff; border:1px solid #000; padding:3px 5px; text-align:center;">Año</th>
                                        <th style="width:16.6667%; background-color:#001b44; color:#fff; border:1px solid #000; padding:3px 5px; text-align:center;">Día</th>
                                        <th style="width:16.6667%; background-color:#001b44; color:#fff; border:1px solid #000; padding:3px 5px; text-align:center;">Mes</th>
                                        <th style="width:16.6667%; background-color:#001b44; color:#fff; border:1px solid #000; padding:3px 5px; text-align:center;">Año</th>
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
                   
                        <!-- TABLA DE BENEFICIARIOS DIRECTOS -->
                        <tr>
                            <th class="full-width1" rowspan="3">7. Descripción de los beneficiarios</th>
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
                                        <td style="border:1px solid #000; padding:3px 5px; text-align:center;">{{ ($proyecto->indigenas_hombres ?? 0) > 0 ? 'X' : '' }}</td>
                                        <td style="border:1px solid #000; padding:3px 5px; text-align:center;">{{ ($proyecto->indigenas_mujeres ?? 0) > 0 ? 'X' : '' }}</td>
                                        <td style="border:1px solid #000; padding:3px 5px; text-align:center;">{{ ($proyecto->afroamericanos_hombres ?? 0) > 0 ? 'X' : '' }}</td>
                                        <td style="border:1px solid #000; padding:3px 5px; text-align:center;">{{ ($proyecto->afroamericanos_mujeres ?? 0) > 0 ? 'X' : '' }}</td>
                                        <td style="border:1px solid #000; padding:3px 5px; text-align:center;">{{ ($proyecto->mestizos_hombres ?? 0) > 0 ? 'X' : '' }}</td>
                                        <td style="border:1px solid #000; padding:3px 5px; text-align:center;">{{ ($proyecto->mestizos_mujeres ?? 0) > 0 ? 'X' : '' }}</td>
                                    </tr>
                                </table>
                            </td>
                        </tr>
        
                    </table>

                    <!-- Sitio de ejecución del proyecto -->
                    <table class="table_datos1 pdf-table pdf-section-avoid-break section-site-execution">
                    <tr>
                        <th class="full-width1" colspan="6" style="text-align:left !important;">8. Sitio de ejecución del proyecto</th>
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
                        <td class="sub-header" colspan="1">Aldea (incluye ciudad)</td>
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

                </div>

                {{-- EQUIPO EJECUTOR --}}
                <div class="section2">
                    @php
                        $coordinador = optional($proyecto->coordinador_proyecto->first())->empleado;
                    @endphp
                    <div class="section-title">II. EQUIPO EJECUTOR DEL PROYECTO. </div>
                    <table class="table_datos1">
                        <!-- TABLA COORDINADOR DEL PROYECTO -->
                        <tr>
                            <th class="full-width1" rowspan="3">9. Coordinador/a del Proyecto:</th>
                            <td class="sub-header">Nombre Completo:</td>
                            <td class="full-width" colspan="2">
                                @if (!empty($isPdf))
                                    <div class="pdf-text-block">{!! $renderPdfText($coordinador?->nombre_completo, 'No especificado') !!}</div>
                                @else
                                    <div class="input-field-multiline-static">{{ $coordinador?->nombre_completo ?? 'No especificado' }}</div>
                                @endif
                            </td>
                            <td class="sub-header">No. de empleado:</td>
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
                            <td class="sub-header">Categoria:</td>
                            <td class="full-width" colspan="2">
                                @if (!empty($isPdf))
                                    <div class="pdf-text-block">{!! $renderPdfText($coordinador?->categoria?->nombre, 'No especificado') !!}</div>
                                @else
                                    <div class="input-field-multiline-static">{{ $coordinador?->categoria?->nombre ?? 'No especificado' }}</div>
                                @endif
                            </td>
                            <td class="sub-header">Departamento:</td>
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
                            <th class="full-width1" colspan="8" style="text-align:left !important; padding-left:8px;">10. Integrantes del equipo docente permanente tiempo completo
                                (Agregar más líneas de ser necesario)</th>
                        </tr>
                        <tr>
                            <td class="sub-header" style="background-color:#001b44; color:#fff; font-weight:bold; text-align:center; font-style:normal; width:5%; white-space:nowrap; padding-left:2px; padding-right:2px;">N°</td>
                            <td class="sub-header" style="background-color:#001b44; color:#fff; font-weight:bold; text-align:center; font-style:normal;">Nombre Completo:</td>
                            <td class="sub-header" style="background-color:#001b44; color:#fff; font-weight:bold; text-align:center; font-style:normal;">No. de empleado/a:</td>
                            <td class="sub-header" style="background-color:#001b44; color:#fff; font-weight:bold; text-align:center; font-style:normal;">Correo electrónico:</td>
                            <td class="sub-header" style="background-color:#001b44; color:#fff; font-weight:bold; text-align:center; font-style:normal;">Categoria:</td>
                            <td class="sub-header" style="background-color:#001b44; color:#fff; font-weight:bold; text-align:center; font-style:normal;">Departamento al que pertenece:</td>
                            <td class="sub-header" style="background-color:#001b44; color:#fff; font-weight:bold; text-align:center; font-style:normal;">Tiempo de participación en el proyecto (estimado en horas)</td>
                            <td class="sub-header" style="background-color:#001b44; color:#fff; font-weight:bold; text-align:center; font-style:normal;">Productos que tendrá a su cargo</td>
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
                       
                        <tr>
                            <th class="full-width1" colspan="8" style="text-align:left !important; padding-left:8px;">11. Docentes internacionales participantes en el proyecto
                                (Agregar más líneas de ser necesario)</th>
                        </tr>
                        <tr>
                            <td class="sub-header" colspan="1" style="background-color:#001b44; color:#fff; font-weight:bold; text-align:center; font-style:normal; width:5%; white-space:nowrap; padding-left:2px; padding-right:2px;">N°</td>
                            <td class="sub-header" colspan="2" style="background-color:#001b44; color:#fff; font-weight:bold; text-align:center; font-style:normal;">Nombre Completo:</td>
                            <td class="sub-header" colspan="1" style="background-color:#001b44; color:#fff; font-weight:bold; text-align:center; font-style:normal;">Pasaporte:</td>
                            <td class="sub-header" colspan="2" style="background-color:#001b44; color:#fff; font-weight:bold; text-align:center; font-style:normal;">Correo electrónico:</td>
                            <td class="sub-header" colspan="1" style="background-color:#001b44; color:#fff; font-weight:bold; text-align:center; font-style:normal;">País:</td>
                            <td class="sub-header" colspan="1" style="background-color:#001b44; color:#fff; font-weight:bold; text-align:center; font-style:normal;">Universidad/Institucion:</td>
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

                    $practicasAsignatura = $estudianteParticipaciones
                        ->filter(fn ($item) => $normalizarTipoParticipacion($item->tipo_participacion_estudiante ?? '') === 'practica' && $item->asignatura_id);
                    $filasPractica = max($practicasAsignatura->count(), 4);
                    $practicasIndexadas = $practicasAsignatura->values();
                @endphp

                {{-- PARTICIPACIÓN DE ESTUDIANTES Y VOLUNTARIOS --}}
                <div class="section3">
                    <div class="section-title">III. PARTICIPACIÓN DE ESTUDIANTES Y VOLUNTARIOS</div>
                    <table class="table_datos1">
                        <!-- 12. PARTICIPACIÓN DE ESTUDIANTES UNAH -->
                        <tr>
                            <th class="full-width1" rowspan="4">12. Participación de estudiantes UNAH</th>
                            <td colspan="8" style="background-color:#001b44; color:#fff; font-weight:bold; font-style:italic; text-align:center;">Desglose del tipo de participación de estudiantes (cantidad)</td>
                        </tr>
                        <tr>
                            <td class="sub-header" colspan="2" style="text-align:center;">Práctica de asignatura / posgrado</td>
                            <td class="sub-header" colspan="2" style="text-align:center;">Servicio social o PPS</td>
                            <td class="sub-header" colspan="2" style="text-align:center;">Voluntariado</td>
                            <td class="sub-header" colspan="2" style="text-align:center;"><u>Total</u> estudiantes</td>
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
                            <th class="full-width1" rowspan="4">13. Voluntariado personal de la UNAH</th>
                            <td colspan="8" style="background-color:#001b44; color:#fff; font-weight:bold; font-style:italic; text-align:center;">Desglose del tipo de participación de personal de la UNAH (cantidad)</td>
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
                        <tr>
                            <th class="full-width1" rowspan="4">14. Voluntariado internacional</th>
                            <td colspan="8" style="background-color:#001b44; color:#fff; font-weight:bold; font-style:italic; text-align:center;">Desglose del voluntariado internacional (cantidad)</td>
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
                            <td class="full-width"></td>
                            <td class="full-width"></td>
                            <td class="full-width"></td>
                            <td class="full-width"></td>
                            <td class="full-width" colspan="2"></td>
                            <td class="full-width" colspan="2"></td>
                        </tr>

                        <!-- 15. DETALLE DE LA PRÁCTICA DE ASIGNATURA/POSGRADO -->
                        <tr>
                            <th class="full-width1" rowspan="{{ 2 + $filasPractica }}">15. Detalle de la Práctica de asignatura/posgrado estudiantes UNAH</th>
                            <td colspan="1" rowspan="2" style="background-color:#001b44; color:#fff; font-weight:bold; font-style:normal; text-align:center;">Código</td>
                            <td colspan="3" rowspan="2" style="background-color:#001b44; color:#fff; font-weight:bold; font-style:normal; text-align:center;">Nombre</td>
                            <td colspan="2" rowspan="2" style="background-color:#001b44; color:#fff; font-weight:bold; font-style:normal; text-align:center;">Periodo académico</td>
                            <td colspan="2" style="background-color:#001b44; color:#fff; font-weight:bold; font-style:normal; text-align:center;">Matrícula</td>
                        </tr>
                        <tr>
                            <td style="background-color:#001b44; color:#fff; font-weight:bold; font-style:normal; text-align:center;">Hombres</td>
                            <td style="background-color:#001b44; color:#fff; font-weight:bold; font-style:normal; text-align:center;">Mujeres</td>
                        </tr>
                        @for ($i = 0; $i < $filasPractica; $i++)
                            @php $practica = $practicasIndexadas->get($i); @endphp
                            <tr>
                                <td class="full-width" colspan="1">{{ $practica?->asignatura?->codigo ?? '' }}</td>
                                <td class="full-width" colspan="3">{{ $practica?->asignatura?->nombre ?? '' }}</td>
                                <td class="full-width" colspan="2">{{ $practica?->periodo_academico_id ?? '' }}</td>
                                <td class="full-width" style="text-align:center;">{{ $practica?->cantidad_estudiantes_hombres ?? '' }}</td>
                                <td class="full-width" style="text-align:center;">{{ $practica?->cantidad_estudiantes_mujeres ?? '' }}</td>
                            </tr>
                        @endfor
                    </table>
                </div>
                {{-- ENTIDAD CONTRAPARTE --}}
                <div class="section4">
                    <div class="section-title">IV. INFORMACIÓN DE LA ENTIDAD CONTRAPARTE DEL PROYECTO (OBLIGATORIO)</div>
                    <table class="table_datos2">
                        <tr>
                            <th class="header" colspan="7">En caso de que la contraparte sea nacional (añadir una
                                tabla
                                de información por cada una de las contrapartes)</th>
                        </tr>
                        @forelse ($proyecto->entidad_contraparte_proyecto()->with('entidadContraparte')->with('instrumentoFormalizacion')->get() as $pivot)
                            @php $entidad = $pivot->entidadContraparte; @endphp
                            <tr>
                                <td style="background-color:#001b44; color:#fff; font-weight:bold; font-style:italic;">16. Nombre de la contraparte:</td>
                                <td class="full-width" colspan="6">
                                    @if (!empty($isPdf))
                                        <div class="pdf-text-block">{!! $renderPdfText($entidad->nombre) !!}</div>
                                    @else
                                        <div class="input-field-multiline-static">{{ $entidad->nombre }}</div>
                                    @endif
                                </td>
                            </tr>
                            <tr>
                                <td class="sub-header" rowspan="1">RTN:</td>
                                <td class="full-width" colspan="6">
                                    @if (!empty($isPdf))
                                        <div class="pdf-text-block">{!! $renderPdfText($entidad->rtn ?? '') !!}</div>
                                    @else
                                        <div class="input-field-multiline-static">{{ $entidad->rtn ?? '' }}</div>
                                    @endif
                                </td>
                            </tr>
                            <tr>
                                <td rowspan="1" style="background-color:#001b44; color:#fff; font-weight:bold; font-style:italic;">17. Tipo de contraparte:</td>
                                <td class="sub-header1 pdf-choice" colspan="1">Gobierno Nacional <br>
                                    @if (!empty($isPdf)){!! $pdfCheck($entidad->tipo_entidad == 'gobierno_nacional') !!}@else<input disabled type="checkbox" class="No" @if ($entidad->tipo_entidad == 'gobierno_nacional') checked @endif>@endif
                                </td>
                                <td class="sub-header1 pdf-choice" colspan="1">Gobierno Municipal<br>
                                    @if (!empty($isPdf)){!! $pdfCheck($entidad->tipo_entidad == 'gobierno_municipal') !!}@else<input disabled type="checkbox" class="No" @if ($entidad->tipo_entidad == 'gobierno_municipal') checked @endif>@endif
                                </td>
                                <td class="sub-header1 pdf-choice" colspan="1">ONG<br>
                                    @if (!empty($isPdf)){!! $pdfCheck($entidad->tipo_entidad == 'ong') !!}@else<input disabled type="checkbox" class="No" @if ($entidad->tipo_entidad == 'ong') checked @endif>@endif
                                </td>
                                <td class="sub-header1 pdf-choice" colspan="1">Sociedad Civil Organizada<br>
                                    @if (!empty($isPdf)){!! $pdfCheck($entidad->tipo_entidad == 'sociedad_civil') !!}@else<input disabled type="checkbox" class="No" @if ($entidad->tipo_entidad == 'sociedad_civil') checked @endif>@endif
                                </td>
                                <td class="sub-header1 pdf-choice" colspan="1">Sector Privado<br>
                                    @if (!empty($isPdf)){!! $pdfCheck($entidad->tipo_entidad == 'sector_privado') !!}@else<input disabled type="checkbox" class="No" @if ($entidad->tipo_entidad == 'sector_privado') checked @endif>@endif
                                </td>
                                <td class="sub-header1 pdf-choice" colspan="1">Internacional<br>
                                    @if (!empty($isPdf)){!! $pdfCheck($entidad->tipo_entidad == 'internacional') !!}@else<input disabled type="checkbox" class="No" @if ($entidad->tipo_entidad == 'internacional') checked @endif>@endif
                                </td>
                            </tr>
                            <tr>
                                <td rowspan="1" style="background-color:#001b44; color:#fff; font-weight:bold; font-style:italic;">18. Nombre del contacto directo</td>
                                <td class="full-width" colspan="3">
                                    @if (!empty($isPdf))
                                        <div class="pdf-text-block">{!! $renderPdfText($entidad->nombre_contacto) !!}</div>
                                    @else
                                        <div class="input-field-multiline-static">{{ $entidad->nombre_contacto }}</div>
                                    @endif
                                </td>
                                <td class="sub-header" colspan="1">Correo Electrónico</td>
                                <td class="full-width" colspan="2">
                                    @if (!empty($isPdf))
                                        <div class="pdf-text-block">{!! $renderPdfText($entidad->correo) !!}</div>
                                    @else
                                        <div class="input-field-multiline-static">{{ $entidad->correo }}</div>
                                    @endif
                                </td>
                            </tr>
                            <tr>
                                <td colspan="1" style="background-color:#001b44; color:#fff; font-weight:bold; font-style:italic;">19. Cargo del contacto del proyecto</td>
                                <td class="full-width" colspan="3">
                                    @if (!empty($isPdf))
                                        <div class="pdf-text-block">{!! $renderPdfText($entidad->cargo_contacto ?? '') !!}</div>
                                    @else
                                        <div class="input-field-multiline-static">{{ $entidad->cargo_contacto ?? '' }}</div>
                                    @endif
                                </td>
                                <td class="sub-header" colspan="1">Teléfono</td>
                                <td class="full-width" colspan="2">
                                    @if (!empty($isPdf))
                                        <div class="pdf-text-block">{!! $renderPdfText($entidad->telefono) !!}</div>
                                    @else
                                        <div class="input-field-multiline-static">{{ $entidad->telefono }}</div>
                                    @endif
                                </td>
                            </tr>
                            <tr>
                                <td colspan="1" style="background-color:#001b44; color:#fff; font-weight:bold; font-style:italic;">20. Tipo de instrumento que da lugar a la alianza</td>
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
                                <td colspan="1" style="background-color:#001b44; color:#fff; font-weight:bold; font-style:italic;">21. Breve descripción de los compromisos asumidos por la contraparte</td>
                                <td class="full-width" colspan="6">
                                    @if (!empty($isPdf))
                                        <div class="pdf-text-block">{!! $renderPdfText($pivot->descripcion_acuerdos ?? '') !!}</div>
                                    @else
                                        <textarea disabled class="input-field" rows="3" placeholder="Describa los compromisos">{{ $pivot->descripcion_acuerdos ?? '' }}</textarea>
                                    @endif
                                </td>
                            </tr>
                            <tr>
                                <td class="sub-header" colspan="7">Instrumentos de formalización de alianza (Si
                                    hubiese):</td>
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
                            <th class="header" colspan="19" style="text-align:left !important;">22. DESCRIPCIÓN DE LOS ANTECEDENTES DEL PROYECTO: (Explicar brevemente los antecedentes que dieron su origen y 
                                la importancia que tiene para los objetivos estratégicos de la UNAH)</th>
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
                            <th class="header" colspan="19" style="text-align:left !important;">23. DESCRIPCIÓN DE LOS PARTICIPANTES DEL PROYECTO (En esta sección se hace una breve
                                descripción de los alcances de la participación de los actores del proyecto. En el caso de la participación de
                                la UNAH, se describirá de manera sucinta, cómo se articula el proyecto de vinculación con las funciones de
                                la docencia (participación de asignaturas) y/o la investigación (si participa un grupo de investigación, o se
                                generan insumos de una investigación en marcha))</th>
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
                            <th class="header" colspan="19" style="text-align:left !important;">24.	DEFINICIÓN DEL PROBLEMA:  Breve descripción del problema que se desea resolver, indicando línea base que se tendrá en consideración 
                                para la definición de los resultados del proyecto. La línea base debe representarse con datos y debe de describirse las causas del problema identificado</th>
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
                            <th class="header" colspan="19" style="text-align:left !important;">25. OBJETIVO GENERAL (El objetivo debe estar basado en la población participante del proyecto)</th>
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
                            <th class="header" colspan="19" style="text-align:left !important;">26. OBJETIVOS ESPECÍFICOS (Los objetivos específicos deben estar relacionados con los resultados que esperan obtener en el proyecto)</th>
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
                            <th class="header" colspan="19">27. RESULTADOS DEL PROYECTO
                                El indicador de resultado es una medida específica y observable que permite evaluar el grado de cumplimiento
                                de los resultados que se han planteado. Sirven para evaluar en qué medida y calidad se lograron los objetivos
                                del proyecto. Hay tres tipos de resultados: 1) corto plazo, que son los productos que se obtendrán con el
                                proyecto, 2) los de mediano plazo: que son los efectos que alcanzará el proyecto y 3) los de largo plazo:
                                resultados de impacto.</th>
                        </tr>

                        <tr>
                            <td class="header" colspan="19" style="text-align:left !important;">a) Resultados de corto plazo del proyecto. Debe de plantearse resultados para cada objetivo específico. Son los productos que se lograrán a corto plazo</td>
                        </tr>
                        <tr>
                            <td class="sub-header" colspan="2" style="font-style:normal; font-weight:bold; text-align:center;">OE<sup>i</sup></td>
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
                            <td class="header" colspan="19" style="text-align:left !important;">b) Resultados de mediano plazo. Son los efectos que se esperan alcanzar del proyecto, es decir, la transformación esperada en la población beneficiada</td>
                        </tr>
                        <tr>
                            <td class="sub-header" colspan="9" style="font-style:normal; font-weight:bold; text-align:center;">Descripción del resultado</td>
                            <td class="sub-header" colspan="10" style="font-style:normal; font-weight:bold; text-align:center;">Medio de verificación (indicador)</td>
                        </tr>
                        @php
                            $resultadosMedianoPlazo = $proyecto->objetivosEspecificos->flatMap(fn($objetivoEsp) => $objetivoEsp->resultados->where('plazo', 'mediano_plazo'));
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
                            <td class="header" colspan="19" style="text-align:left !important;">c) Impacto que se desea generar en el proyecto (Debe de expresar los indicadores de impacto del proyecto)</td>
                        </tr>
                        <tr>
                            <td class="sub-header" colspan="9" style="font-style:normal; font-weight:bold; text-align:center;">Descripción del resultado de largo plazo</td>
                            <td class="sub-header" colspan="10" style="font-style:normal; font-weight:bold; text-align:center;">Medio de verificación (indicador con el que se evaluará su cumplimiento)</td>
                        </tr>
                        @php
                            $resultadosLargoPlazo = $proyecto->objetivosEspecificos->flatMap(fn($objetivoEsp) => $objetivoEsp->resultados->where('plazo', 'largo_plazo'));
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
                            <td class="header" colspan="19" style="text-align:left !important;">28. OBJETIVOS DE DESARROLLO SOSTENIBLE (ODS) A LOS QUE SE CONTRIBUYE: Indicar el o los
                                ODS a los que pretende contribuir el proyecto y las metas correspondientes. Para esta descripción deberá basarse
                                en el documento de ODS que puede consultar en el siguiente enlace:</th>
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
                        @forelse ($proyecto->ods as $ods)
                            <tr>
                                <td class="full-width" colspan="9">
                                    @if (!empty($isPdf))
                                        <div class="pdf-text-block">{!! $renderPdfText($ods->nombre) !!}</div>
                                    @else
                                        <div class="input-field-multiline-static">{{ $ods->nombre }}</div>
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
                            <td class="header" colspan="19">29. ALINEAMIENTO CON LO ESENCIAL DE LA REFORMA DE LA UNAH (detalle brevemente cómo se alinean los ejes de lo esencial de la reforma en la ejecución de este proyecto, en resumen, describa qué competencias relacionadas con los ejes de lo esencial de la reforma adquirirán los estudiantes con la participación en este proyecto.</th>
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
                        <tr>
                            <td class="header" colspan="19" style="text-align:left !important;">30. METODOLOGÍA</th>
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
                            <td class="header" colspan="19" style="text-align:left !important;">31. BIBLIOGRAFÍA</th>
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

                {{-- CRONOGRAMA --}}
                <div class="section2">
                    <div class="section-title">VI. CRONOGRAMA DE LAS ACTIVIDADES DEL PROYECTO</div>
                    <table class="table_datos3">
                        <thead>
                            <tr>
                                <th class="header" colspan="19" style="text-align:left !important;">32. DESCRIPCIÓN DE ACTIVIDADES DEL PROYECTO (Descripción de todas las actividades enmarcadas en
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
                                    <td class="s3" colspan="4">{{ $actividad->fecha_inicio }} - {{ $actividad->fecha_finalizacion }}</td>
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
                    <div class="section-title">VII. DETALLE DEL PRESUPUESTO</div>
                    <table class="table_datos3">
                        <tr>
                            <td class="header" colspan="19" style="text-align:left !important;">33. APORTE INSTITUCIONAL (manifestado en lempiras)</td>
                        </tr>
                        <tr>
                            <td class="header" colspan="7">Concepto</td>
                            <td class="header" colspan="3">Unidad</td>
                            <td class="header" colspan="3">Cantidad</td>
                            <td class="header" colspan="3">Costo Unitario</td>
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
                            $cantidadIndirecta = round($baseAporteInstitucional->sum('cantidad') * 0.05, 2);
                            $costoUnitarioIndirecto = round($baseAporteInstitucional->sum('costo_unitario') * 0.05, 2);
                            $costoTotalIndirecto = round($cantidadIndirecta * $costoUnitarioIndirecto, 2);
                            $infraestructura = $conceptos->get('costos_indirectos_infraestructura');
                            $servicios = $conceptos->get('costos_indirectos_servicios');
                        @endphp
                        
                        <!-- Horas de trabajo docentes -->
                        <tr>
                            <td class="sub-header" colspan="7">a) Horas de trabajo docentes</td>
                            <td class="sub-header" colspan="3">Hra/profesores</td>
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
                            <td class="sub-header" colspan="3">Hra/estudiantes</td>
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
                            <td class="sub-header" colspan="7">f) Costos indirectos por infraestructura universidad (depreciación de equipo, calculado sobre la sumatoria de los conceptos a – e)</td>
                            <td class="sub-header" colspan="3">%</td>
                            <td class="full-width" colspan="3">
                                @if (!empty($isPdf))
                                    <div class="pdf-text-block">{!! $renderPdfText(number_format($infraestructura?->cantidad ?? $cantidadIndirecta, 2, '.', ',')) !!}</div>
                                @else
                                    <div class="input-field-multiline-static">{{ number_format($infraestructura?->cantidad ?? $cantidadIndirecta, 2, '.', ',') }}</div>
                                @endif
                            </td>
                            <td class="full-width" colspan="3">
                                @if (!empty($isPdf))
                                    <div class="pdf-text-block">{!! $renderPdfText(number_format($infraestructura?->costo_unitario ?? $costoUnitarioIndirecto, 2, '.', ',')) !!}</div>
                                @else
                                    <div class="input-field-multiline-static">{{ number_format($infraestructura?->costo_unitario ?? $costoUnitarioIndirecto, 2, '.', ',') }}</div>
                                @endif
                            </td>
                            <td class="full-width" colspan="3">
                                @if (!empty($isPdf))
                                    <div class="pdf-text-block">{!! $renderPdfText(number_format($infraestructura?->costo_total ?? $costoTotalIndirecto, 2, '.', ',')) !!}</div>
                                @else
                                    <div class="input-field-multiline-static">{{ number_format($infraestructura?->costo_total ?? $costoTotalIndirecto, 2, '.', ',') }}</div>
                                @endif
                            </td>
                        </tr>
                        
                        <!-- Costos indirectos por servicios públicos -->
                        <tr>
                            <td class="sub-header" colspan="7">g) Costos indirectos por servicios públicos (internet, electricidad, otros, calculado sobre la sumatoria de los conceptos a – e)</td>
                            <td class="sub-header" colspan="3">%</td>
                            <td class="full-width" colspan="3">
                                @if (!empty($isPdf))
                                    <div class="pdf-text-block">{!! $renderPdfText(number_format($servicios?->cantidad ?? $cantidadIndirecta, 2, '.', ',')) !!}</div>
                                @else
                                    <div class="input-field-multiline-static">{{ number_format($servicios?->cantidad ?? $cantidadIndirecta, 2, '.', ',') }}</div>
                                @endif
                            </td>
                            <td class="full-width" colspan="3">
                                @if (!empty($isPdf))
                                    <div class="pdf-text-block">{!! $renderPdfText(number_format($servicios?->costo_unitario ?? $costoUnitarioIndirecto, 2, '.', ',')) !!}</div>
                                @else
                                    <div class="input-field-multiline-static">{{ number_format($servicios?->costo_unitario ?? $costoUnitarioIndirecto, 2, '.', ',') }}</div>
                                @endif
                            </td>
                            <td class="full-width" colspan="3">
                                @if (!empty($isPdf))
                                    <div class="pdf-text-block">{!! $renderPdfText(number_format($servicios?->costo_total ?? $costoTotalIndirecto, 2, '.', ',')) !!}</div>
                                @else
                                    <div class="input-field-multiline-static">{{ number_format($servicios?->costo_total ?? $costoTotalIndirecto, 2, '.', ',') }}</div>
                                @endif
                            </td>
                        </tr>
                        
                        <!-- Fila de totales y aportes -->
                        <tr>
                            <td class="sub-headeri" colspan="16">Total aporte institucional</td>
                            <td class="full-width" colspan="3">
                                @if (!empty($isPdf))
                                    <div class="pdf-text-block">{!! $renderPdfText(number_format($proyecto->total_aporte_institucional ?? 0, 2, '.', ',')) !!}</div>
                                @else
                                    <div class="input-field-multiline-static">{{ number_format($proyecto->total_aporte_institucional ?? 0, 2, '.', ',') }}</div>
                                @endif
                            </td>
                        </tr>
                        <tr>
                            <td class="header" colspan="19" style="text-align:left !important;">34. OTRAS APORTACIONES (Manifestado en lempiras)</td>
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
                                    <div class="pdf-text-block">{!! $renderPdfText(number_format(($proyecto->total_aporte_institucional ?? 0) + $totalOtrasAportaciones, 2, '.', ',')) !!}</div>
                                @else
                                    <div class="input-field-multiline-static">{{ number_format(($proyecto->total_aporte_institucional ?? 0) + $totalOtrasAportaciones, 2, '.', ',') }}</div>
                                @endif
                            </td>
                        </tr>
                    </table>
                </div>

                {{-- FIRMAS --}}
                <div class="section3 section-signatures">
                    @include('components.fichas.firmas-dinamicas', ['proyecto' => $proyecto, 'isPdf' => $isPdf ?? false])
                </div>

                {{-- DOCUMENTOS ADJUNTOS --}}
                <div class="section4 section-documents">
                    <div class="section-title">DOCUMENTOS ADJUNTOS A LA FICHA</div>
                    <table class="table_datos5">
                        <tr>
                            <th class="header" colspan="1">No</th>
                            <th class="header" colspan="10">Descripción</th>
                            <th class="header" colspan="4">Si</th>
                            <th class="header" colspan="4">No</th>
                        </tr>
                        <tr>
                            <td class="sub-header" colspan="1">1</td>
                            <td class="full-width" colspan="10">Carta de solicitud del proyecto firmada por el representante legal de la contraparte</td>
                            <td class="full-width" colspan="4">
                                @if (!empty($isPdf)){!! $pdfCheck(false) !!}@else<input disabled type="checkbox" class="checkbox-field">@endif
                            </td>
                            <td class="full-width" colspan="4">
                                @if (!empty($isPdf)){!! $pdfCheck(false) !!}@else<input disabled type="checkbox" class="checkbox-field">@endif
                            </td>
                        </tr>
                        <tr>
                            <td class="sub-header" colspan="1">2</td>
                            <td class="full-width" colspan="10">Convenio/ carta de intenciones firmada entre la UNAH y contraparte</td>
                            <td class="full-width" colspan="4">
                                @if (!empty($isPdf)){!! $pdfCheck(false) !!}@else<input disabled type="checkbox" class="checkbox-field">@endif
                            </td>
                            <td class="full-width" colspan="4">
                                @if (!empty($isPdf)){!! $pdfCheck(false) !!}@else<input disabled type="checkbox" class="checkbox-field">@endif
                            </td>
                        </tr>
                        <tr>
                            <td class="sub-header" colspan="1">3</td>
                            <td class="full-width" colspan="10">Oficio de remisión del Decano/Director Centro Regional</td>
                            <td class="full-width" colspan="4">
                                @if (!empty($isPdf)){!! $pdfCheck(false) !!}@else<input disabled type="checkbox" class="checkbox-field">@endif
                            </td>
                            <td class="full-width" colspan="4">
                                @if (!empty($isPdf)){!! $pdfCheck(false) !!}@else<input disabled type="checkbox" class="checkbox-field">@endif
                            </td>
                        </tr>
                        <tr>
                            <td class="sub-header" colspan="1">4</td>
                            <td class="full-width" colspan="10">Otros (detallar)</td>
                            <td class="full-width" colspan="4">
                                @if (!empty($isPdf)){!! $pdfCheck(false) !!}@else<input disabled type="checkbox" class="checkbox-field">@endif
                            </td>
                            <td class="full-width" colspan="4">
                                @if (!empty($isPdf)){!! $pdfCheck(false) !!}@else<input disabled type="checkbox" class="checkbox-field">@endif
                            </td>
                        </tr>
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
        </div>
    </div>


</body>


</html>
