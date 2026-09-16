@php
    $isPdf = $isPdf ?? false;
    $assetUrl = fn (string $path) => $isPdf ? 'file://'.public_path($path) : asset($path);
    $pageChromeUrl = $assetUrl('images/enf/form-016-page.png');
    $certificado = $accion->certificado;
    $lugar = $accion->lugaresEjecucion->first();
    $beneficiarios = $accion->beneficiarios;
    $contraparte = $accion->contrapartes->first();
    $coordinador = $accion->equipo->firstWhere('rol', 'Coordinador de la accion');
    $docentes = $accion->equipo
        ->whereIn('rol', ['Docente UNAH', 'Consultor nacional', 'Consultor internacional'])
        ->values();
    $catalogosPorTipo = $accion->accionCatalogos->groupBy('tipo');
    $presupuestosPorTipo = $accion->presupuestos->keyBy('tipo');
    $firmasPorRol = $accion->firmas->keyBy('rol_firma');

    $value = fn ($value, $fallback = '') => filled($value) ? $value : ($fallback ?? '');
    $date = fn ($value) => filled($value) ? \Carbon\Carbon::parse($value)->format('d/m/Y') : '';
    $time = fn ($value) => filled($value) ? substr((string) $value, 0, 5) : '';
    $money = fn ($value) => number_format((float) $value, 2);
    $checkbox = fn (bool $checked) => new \Illuminate\Support\HtmlString(
        '<span class="form016-checkbox">'.($checked ? 'X' : '&nbsp;').'</span>'
    );
    $catalogNames = function (string $tipo) use ($catalogosPorTipo) {
        return $catalogosPorTipo->get($tipo, collect())
            ->map(fn ($item) => $item->catalogo?->nombre)
            ->filter()
            ->values();
    };
    $hasCatalog = function (string $tipo, string $needle) use ($catalogNames) {
        return $catalogNames($tipo)
            ->contains(fn ($name) => str($name)->ascii()->lower()->contains(str($needle)->ascii()->lower()));
    };
    $normalizeText = fn ($value) => str($value)->ascii()->lower()->replace([' ', '-', '_', '/', '(', ')'], '')->toString();
    $modalidadTexto = $normalizeText(collect([$lugar?->modalidad_ejecucion, $accion->modalidad?->nombre])->filter()->implode(' '));
    $platformPresencialText = $normalizeText($catalogNames('plataforma_presencial')->implode(' '));
    $platformDistanciaText = $normalizeText(collect([
        $lugar?->plataforma,
        $lugar?->descripcion_plataformas,
        $lugar?->url_acceso,
        $catalogNames('plataforma_distancia')->implode(' '),
    ])->filter()->implode(' '));
    $hasPlatformPresencial = fn (string $needle) => str_contains($platformPresencialText, $normalizeText($needle));
    $hasPlatformDistancia = fn (string $needle) => str_contains($platformDistanciaText, $normalizeText($needle));
    $budgetRows = function (string $tipo, array $defaults) use ($presupuestosPorTipo) {
        $detalles = $presupuestosPorTipo->get($tipo)?->detalles ?? collect();

        return collect($defaults)->map(function ($rubro, $index) use ($detalles) {
            $detalle = $detalles->first(fn ($item) => str($item->rubro)->lower()->contains(str($rubro)->lower()))
                ?? $detalles->values()->get($index);

            return [
                'rubro' => $detalle?->rubro ?: $rubro,
                'cantidad' => $detalle?->cantidad,
                'costo_unitario' => $detalle?->costo_unitario,
                'total' => $detalle?->total,
            ];
        });
    };
    $days = collect((array) ($certificado?->dias_imparticion ?? []))->map(fn ($day) => (string) $day);
    $shellClass = $isPdf ? 'is-pdf' : 'screen-document';
    $openPage = fn (int $page) => new \Illuminate\Support\HtmlString(
        '<section class="form016-page">'.
            '<img class="form016-page-chrome" src="'.e($pageChromeUrl).'" alt="">'.
            '<div class="form016-header-contact">vinculacion.sociedad@unah.edu.hn<br>Tel. 2216-7070 Ext. 110576<br><br><span>formaciontecnologica@unah.edu.hn</span><br>Tel: 2216-7008/2216-6100<br>Ext: 110615 &ndash; 110617<br>110186 &ndash; 110192</div>'.
            '<main class="form016-main">'.
                '<div class="form016-page-number">'.$page.'</div>'
    );
    $closePage = new \Illuminate\Support\HtmlString('</main></section>');
@endphp

<style>
    @page {
        size: letter portrait;
        margin: 0;
    }

    .form016-shell {
        --form016-screen-scale: 1;
        color: #000;
        container-type: inline-size;
        font-family: "Arial Narrow", Arial, sans-serif;
        font-size: 10pt;
        line-height: 1.05;
        overflow-x: hidden;
        width: 100%;
    }

    .form016-shell * {
        box-sizing: border-box;
        font-size: inherit;
        letter-spacing: 0;
    }

    .form016-shell.screen-document {
        display: grid;
        gap: 72px;
        justify-items: center;
    }

    .form016-page {
        background: #fff;
        max-width: none;
        height: 11in;
        min-height: 11in;
        overflow: hidden;
        page-break-after: auto;
        page-break-before: auto;
        page-break-inside: avoid;
        position: relative;
        transform-origin: top center;
        width: 8.5in;
    }

    .form016-page + .form016-page {
        page-break-before: always;
    }

    .form016-shell.is-pdf .form016-page {
        height: 11in;
        min-height: 11in;
        overflow: hidden;
        page-break-after: always;
        page-break-before: auto;
        page-break-inside: avoid;
    }

    .form016-shell.is-pdf .form016-page:last-child {
        page-break-after: auto;
    }

    .form016-shell.screen-document .form016-page {
        box-shadow: 0 10px 30px rgba(15, 23, 42, .14);
        zoom: var(--form016-screen-scale);
    }

    .form016-shell.screen-document .form016-page:not(:last-child) {
        margin-bottom: 20px;
    }

    .form016-page-chrome {
        height: 11in;
        left: 0;
        object-fit: fill;
        pointer-events: none;
        position: absolute;
        top: 0;
        width: 8.5in;
        z-index: 0;
    }

    .form016-header-contact {
        color: #6f7f9f;
        font-family: Arial, sans-serif;
        font-size: 6pt;
        font-weight: 700;
        line-height: 1.13;
        pointer-events: none;
        position: absolute;
        right: 0.36in;
        text-align: right;
        top: 0.25in;
        z-index: 1;
    }

    .form016-header-contact span {
        color: #315ec9;
        text-decoration: underline;
    }

    .form016-main {
        margin: 1.28in 1in 0;
        position: relative;
        width: 6.5in;
        z-index: 1;
    }

    .form016-code {
        background: #002060;
        color: #fff;
        font-family: Arial, sans-serif;
        font-size: 13.5pt;
        font-weight: 800;
        height: 0.24in;
        line-height: 0.24in;
        margin: 0 0 0.12in 0.52in;
        padding: 0 0.01in 0 0;
        text-align: right;
        width: 5.98in;
    }

    .form016-title {
        font-family: Arial, sans-serif;
        font-size: 13.5pt;
        font-weight: 800;
        line-height: 1.02;
        margin: 0 0 0.17in 0.55in;
        text-align: center;
        width: 5.9in;
    }

    .form016-page-number {
        border: 1px solid #7ea0cf;
        border-radius: 50%;
        color: #002060;
        font-family: Arial, sans-serif;
        font-size: 8pt;
        height: 0.25in;
        line-height: 0.23in;
        position: absolute;
        right: -0.34in;
        text-align: center;
        top: -0.18in;
        width: 0.25in;
        z-index: 2;
    }

    .form016-section {
        color: #002060;
        font-family: Arial, sans-serif;
        font-size: 12pt;
        font-weight: 800;
        line-height: 1.1;
        margin: 0.1in 0 0.1in;
        text-transform: uppercase;
    }

    .form016-table {
        border-collapse: collapse;
        table-layout: fixed;
        width: 100%;
        margin-bottom: 0.06in;
    }

    .form016-table th,
    .form016-table td {
        border: 0.5pt solid #6f6f6f;
        padding: 0.025in 0.045in;
        vertical-align: middle;
        min-height: 0.18in;
        word-break: break-word;
    }

    .form016-blue {
        background: #002060;
        color: #fff;
        font-weight: 800;
    }

    .form016-blue span {
        color: inherit;
    }

    .form016-small {
        font-size: 7pt;
        font-weight: 700;
        line-height: 1;
    }

    .form016-compact td,
    .form016-compact th {
        padding-bottom: 0.015in;
        padding-top: 0.015in;
    }

    .form016-note {
        font-size: 7.5pt;
        font-weight: 400;
        line-height: 1.1;
        text-transform: none;
    }

    .form016-academic-field {
        height: 0.72in;
        vertical-align: top !important;
        white-space: pre-wrap;
    }

    .form016-signature-box {
        height: 0.24in;
        vertical-align: top !important;
    }

    .form016-signature-large {
        height: 0.76in;
        vertical-align: top !important;
    }

    .form016-signature-spacer {
        height: 2.35in;
    }

    .form016-firmas-title {
        color: #002060;
        font-family: Arial, sans-serif;
        font-size: 8pt;
        font-weight: 800;
        line-height: 1;
        margin: 0 0 0.18in 0.18in;
        text-transform: uppercase;
    }

    .form016-num {
        text-align: center;
        vertical-align: top !important;
        width: 5%;
    }

    .form016-blue-label {
        vertical-align: top !important;
    }

    .form016-tall {
        height: 0.43in;
    }

    .form016-career-row {
        height: 0.38in;
    }

    .form016-block-row {
        height: 0.42in;
    }

    .form016-academic-row {
        height: 0.34in;
    }

    .form016-blue-title {
        font-size: 11.8pt;
        line-height: 1.1;
        vertical-align: top !important;
    }

    .form016-center-blue-title {
        text-align: center;
        vertical-align: middle !important;
    }

    .form016-gray {
        background: #d9d9d9;
        font-weight: 800;
    }

    .form016-center {
        text-align: center;
    }

    .form016-right {
        text-align: right;
    }

    .form016-large {
        min-height: 0.48in;
    }

    .form016-signature {
        height: 0.78in;
        vertical-align: top !important;
    }

    .form016-file-actions {
        align-items: center;
        display: flex;
        gap: 4px;
        justify-content: center;
    }

    .form016-file-button {
        background: #2563eb;
        border-radius: 4px;
        color: #fff;
        display: inline-block;
        font-family: Arial, sans-serif;
        font-size: 7.5pt;
        font-weight: 700;
        line-height: 1;
        padding: 5px 7px;
        text-decoration: none;
    }

    .form016-checkbox {
        border: 1px solid #111827;
        display: inline-block;
        font-family: Arial, sans-serif;
        font-size: 9pt;
        font-weight: 900;
        height: 0.105in;
        line-height: 0.095in;
        margin: 0 0.045in;
        text-align: center;
        vertical-align: middle;
        width: 0.105in;
    }

    @media print {
        body {
            background: #fff !important;
        }

        .form016-page {
            box-shadow: none;
            height: 11in;
            margin-bottom: 0 !important;
            overflow: hidden;
            page-break-after: always;
            page-break-inside: avoid;
            zoom: 1 !important;
            width: 8.5in;
            min-height: 11in;
        }

        .form016-page:last-child {
            page-break-after: auto;
        }
    }
</style>

<div class="form016-shell {{ $shellClass }}">
    {!! $openPage(1) !!}
    <div class="form016-code">FORM-DVUS-016</div>
    <div class="form016-title">FORMULARIO DE REGISTRO DE CERTIFICADOS UNIVERSITARIOS<br>/EDUCACION NO FORMAL</div>

    <div class="form016-section">I.&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; INFORMACION GENERAL DEL CERTIFICADO UNIVERSITARIO</div>
    @php
        $carrerasCertificado = collect($certificado?->carreras ?? [])->values();
        $totalFilasCarreras = max(3, $carrerasCertificado->count());
    @endphp
    <table class="form016-table">
        <colgroup>
            <col style="width: 5%">
            <col style="width: 29%">
            <col style="width: 16.5%">
            <col style="width: 16.5%">
            <col style="width: 16.5%">
            <col style="width: 16.5%">
        </colgroup>
        <tr>
            <td class="form016-blue form016-num" rowspan="2">1.</td>
            <td class="form016-blue form016-blue-label" rowspan="2">Fecha de solicitud de registro</td>
            <td class="form016-blue form016-center" colspan="2">Año</td>
            <td class="form016-blue form016-center">Mes</td>
            <td class="form016-blue form016-center">Dia</td>
        </tr>
        <tr>
            <td class="form016-center" colspan="2">{{ $accion->fecha_solicitud?->format('Y') }}</td>
            <td class="form016-center">{{ $accion->fecha_solicitud?->format('m') }}</td>
            <td class="form016-center">{{ $accion->fecha_solicitud?->format('d') }}</td>
        </tr>
        <tr>
            <td class="form016-blue form016-num">2.</td>
            <td class="form016-blue form016-blue-label">Nombre completo del Certificado</td>
            <td colspan="4">{{ $value($certificado?->nombre_certificado, $accion->nombre_accion) }}</td>
        </tr>
        <tr>
            <td class="form016-blue form016-num">3.</td>
            <td class="form016-blue form016-blue-label">Código de Certificado<br><span>(Asignado por la DAFT)</span></td>
            <td colspan="4">{{ $value($certificado?->codigo_certificado) }}</td>
        </tr>
        <tr class="form016-tall">
            <td class="form016-blue form016-num">4.</td>
            <td class="form016-blue form016-blue-label">Número de edición del certificado Universitario.</td>
            <td colspan="4">{{ $value($accion->numero_edicion) }}</td>
        </tr>
        <tr class="form016-tall">
            <td class="form016-blue form016-num">5.</td>
            <td class="form016-blue form016-blue-label">Tipo de Certificado:</td>
            <td class="form016-gray">Básico</td>
            <td class="form016-center">{{ $checkbox(str($certificado?->tipoCertificado?->nombre)->ascii()->lower()->contains('basico')) }}</td>
            <td class="form016-gray">Avanzado</td>
            <td class="form016-center">{{ $checkbox(str($certificado?->tipoCertificado?->nombre)->ascii()->lower()->contains('avanzado')) }}</td>
        </tr>
    </table>

    <table class="form016-table">
        <colgroup>
            <col style="width: 5%">
            <col style="width: 29%">
            <col style="width: 36%">
            <col style="width: 30%">
        </colgroup>
        <tr>
            <td class="form016-blue form016-num" rowspan="{{ $totalFilasCarreras + 1 }}">6.</td>
            <td class="form016-blue form016-blue-label" rowspan="{{ $totalFilasCarreras + 1 }}">
                Carreras aprobadas por Consejo Universitario<br>
                (Planes de estudios relacionados con el Certificado Universitario)
            </td>
            <td class="form016-gray form016-center">Nombre de las Carreras</td>
            <td class="form016-gray form016-center">No. Acuerdos de Consejo Universitario</td>
        </tr>
        @for ($i = 0; $i < $totalFilasCarreras; $i++)
            @php $carrera = $carrerasCertificado->get($i); @endphp
            <tr class="form016-career-row">
                <td>{{ $value($carrera?->nombre_carrera, $carrera?->carrera?->nombre) }}</td>
                <td>{{ $value($carrera?->acuerdo_consejo_universitario) }}</td>
            </tr>
        @endfor
    </table>

    @php
        $espaciosCertificado = $accion->espaciosAprendizaje->values();
        $totalFilasEspacios = max(6, $espaciosCertificado->count());
    @endphp
    <table class="form016-table">
        <tr>
            <td class="form016-blue" colspan="5">7.&nbsp;&nbsp;&nbsp; Información general del Certificado Universitario</td>
        </tr>
        <tr>
            <td class="form016-gray form016-center" style="width: 8%">N°</td>
            <td class="form016-gray form016-center" style="width: 42%">Nombre asignatura</td>
            <td class="form016-gray form016-center" style="width: 18%">Codigo</td>
            <td class="form016-gray form016-center" style="width: 16%">No. de creditos</td>
            <td class="form016-gray form016-center" style="width: 16%">No. de horas</td>
        </tr>
        @for ($i = 0; $i < $totalFilasEspacios; $i++)
            @php $espacio = $espaciosCertificado->get($i); @endphp
            <tr>
                <td class="form016-center">{{ $i + 1 }}</td>
                <td>{{ $value($espacio?->nombre) }}</td>
                <td class="form016-center">{{ $value($espacio?->codigo) }}</td>
                <td class="form016-center">{{ $value($espacio?->creditos) }}</td>
                <td class="form016-center">{{ $value($espacio?->horas) }}</td>
            </tr>
        @endfor
    </table>

    <table class="form016-table">
        <colgroup>
            <col style="width: 56%">
            <col style="width: 12%">
            <col style="width: 32%">
        </colgroup>
        <tr class="form016-block-row">
            <td class="form016-blue form016-blue-title">
                8.&nbsp;&nbsp; Unidad académica responsable<br>
                (Facultad/Centro Universitario Regional/Instituto Tecnológico Superior)<br>
                <em>Escuela, Departamento Académico.</em>
            </td>
            <td class="form016-blue form016-blue-title form016-center-blue-title" colspan="2">9.&nbsp;&nbsp; Carga horaria en créditos académicos</td>
        </tr>
        <tr class="form016-academic-row">
            <td rowspan="3">
                {{ $value($accion->unidad_academica_responsable_texto, $accion->centroFacultad?->nombre) }}<br>
                {{ $value($accion->escuela_departamento_texto, $accion->departamentoAcademico?->nombre) }}
            </td>
            <td class="form016-gray">Horas<br>teóricas</td>
            <td class="form016-center">{{ $value($accion->horas_teoricas, 0) }}</td>
        </tr>
        <tr class="form016-academic-row">
            <td class="form016-gray">Horas<br>practicas</td>
            <td class="form016-center">{{ $value($accion->horas_practicas, 0) }}</td>
        </tr>
        <tr class="form016-academic-row">
            <td class="form016-gray">Total Horas:</td>
            <td class="form016-center">{{ $value($accion->total_horas, 0) }}</td>
        </tr>
    </table>

    {!! $closePage !!}
    {!! $openPage(2) !!}

    <table class="form016-table">
        <tr><td class="form016-blue" colspan="6">10.&nbsp;&nbsp; Cupos Programados: (Máximo)</td></tr>
        <tr class="form016-block-row">
            <td class="form016-gray" style="width: 18%">Mujeres</td>
            <td class="form016-center" style="width: 17%">{{ $value($beneficiarios?->mujeres, 0) }}</td>
            <td class="form016-gray" style="width: 18%">Hombres</td>
            <td class="form016-center" style="width: 17%">{{ $value($beneficiarios?->hombres, 0) }}</td>
            <td class="form016-gray" style="width: 18%">Total</td>
            <td class="form016-center" style="width: 12%">{{ $value($beneficiarios?->total, 0) }}</td>
        </tr>
    </table>

    <table class="form016-table">
        <tr><td class="form016-blue" colspan="6">11.&nbsp;&nbsp; Período de ejecución</td></tr>
        <tr>
            <td class="form016-gray form016-center" colspan="2">Fecha de inicio</td>
            <td class="form016-gray form016-center" colspan="2">Fecha de finalización</td>
            <td class="form016-gray form016-center" colspan="2">Vigencia del Certificado</td>
        </tr>
        <tr class="form016-block-row">
            <td class="form016-center" colspan="2">{{ $date($accion->fecha_inicio) }}</td>
            <td class="form016-center" colspan="2">{{ $date($accion->fecha_finalizacion) }}</td>
            <td class="form016-center" colspan="2">{{ $value($certificado?->vigencia_certificado) }}</td>
        </tr>
        <tr>
            <td class="form016-gray form016-center" colspan="3">Fecha de emisión: (fecha máxima de emisión del certificado)</td>
            <td class="form016-gray form016-center" colspan="3">Indique el número de PAC al que pertenece, año</td>
        </tr>
        <tr class="form016-block-row">
            <td class="form016-center" colspan="3">{{ $date($certificado?->fecha_emision_maxima) }}</td>
            <td class="form016-center" colspan="3">{{ $value($certificado?->pac_certificado) }}</td>
        </tr>
    </table>

    <table class="form016-table">
        <tr>
            <td class="form016-gray" rowspan="2" style="width: 22%">Horario</td>
            <td class="form016-gray form016-center" style="width: 39%">Hora de inicio</td>
            <td class="form016-gray form016-center" style="width: 39%">Hora de finalización</td>
        </tr>
        <tr>
            <td class="form016-center">{{ $time($certificado?->hora_inicio) }}</td>
            <td class="form016-center">{{ $time($certificado?->hora_finalizacion) }}</td>
        </tr>
    </table>

    <table class="form016-table">
        <colgroup>
            <col style="width: 22%">
            <col style="width: 11.14%">
            <col style="width: 11.14%">
            <col style="width: 11.14%">
            <col style="width: 11.14%">
            <col style="width: 11.14%">
            <col style="width: 11.14%">
            <col style="width: 11.16%">
        </colgroup>
        <tr>
            <td class="form016-gray" rowspan="2">Días de impartición</td>
            @foreach (['Do', 'Lu', 'Ma', 'Mi', 'Ju', 'Vi', 'Sa'] as $dia)
                <td class="form016-gray form016-center">{{ $dia }}</td>
            @endforeach
        </tr>
        <tr>
            @foreach (['Do', 'Lu', 'Ma', 'Mi', 'Ju', 'Vi', 'Sa'] as $dia)
                <td class="form016-center">{{ $checkbox($days->contains($dia)) }}</td>
            @endforeach
        </tr>
        <tr><td class="form016-blue form016-center-blue-title" colspan="8">12.&nbsp;&nbsp; Modalidad de ejecución</td></tr>
    </table>

    <table class="form016-table">
        <colgroup>
            <col style="width: 12.5%">
            <col style="width: 12.5%">
            <col style="width: 12.5%">
            <col style="width: 12.5%">
            <col style="width: 12.5%">
            <col style="width: 12.5%">
            <col style="width: 12.5%">
            <col style="width: 12.5%">
        </colgroup>
        <tr class="form016-center">
            <td>Presencial</td>
            <td colspan="2">Semi presencial (Virtual + presencial)</td>
            <td>100%<br>virtual</td>
            <td colspan="4">Virtual sincr&oacute;nico (Teledocencia)</td>
        </tr>
        <tr class="form016-center">
            <td>{{ $checkbox(str_contains($modalidadTexto, 'presencial') && ! str_contains($modalidadTexto, 'semipresencial')) }}</td>
            <td colspan="2">{{ $checkbox(str_contains($modalidadTexto, 'semipresencial')) }}</td>
            <td>{{ $checkbox(str_contains($modalidadTexto, '100%virtual') || $modalidadTexto === 'virtual') }}</td>
            <td colspan="4">{{ $checkbox(str_contains($modalidadTexto, 'virtualsincronico') || str_contains($modalidadTexto, 'teledocencia')) }}</td>
        </tr>
        <tr>
            <td class="form016-gray" colspan="2"><strong>Lugar de impartici&oacute;n</strong><br><em>(presencial/semipresencial)</em></td>
            <td class="form016-gray form016-center">No de Aula:</td>
            <td>{{ $value($lugar?->aula) }}</td>
            <td class="form016-gray form016-center">Edificio:</td>
            <td>{{ $value($lugar?->edificio) }}</td>
            <td class="form016-gray form016-center">Centro:</td>
            <td>{{ $value($lugar?->centro, $lugar?->nombre_lugar) }}</td>
        </tr>
        <tr>
            <td class="form016-gray form016-center" colspan="8">Descripci&oacute;n de las plataformas que se utilizar&aacute;n para la modalidad Semipresencial, Virtual y Teledocencia<br>(teletrabajo en los casos que aplique)</td>
        </tr>
        <tr>
            <td class="form016-gray" colspan="2" rowspan="2">Plataformas para la modalidad presencial (Si aplica)</td>
            <td class="form016-gray form016-center">Teams</td>
            <td class="form016-gray form016-center">Zoom</td>
            <td class="form016-gray form016-center">Meet</td>
            <td class="form016-gray form016-center">Webex</td>
            <td class="form016-gray form016-center" colspan="2">Otro</td>
        </tr>
        <tr class="form016-center">
            <td>{{ $checkbox($hasPlatformPresencial('Teams') || $hasCatalog('plataforma_presencial', 'Teams')) }}</td>
            <td>{{ $checkbox($hasPlatformPresencial('Zoom') || $hasCatalog('plataforma_presencial', 'Zoom')) }}</td>
            <td>{{ $checkbox($hasPlatformPresencial('Meet') || $hasCatalog('plataforma_presencial', 'Meet')) }}</td>
            <td>{{ $checkbox($hasPlatformPresencial('Webex') || $hasCatalog('plataforma_presencial', 'Webex')) }}</td>
            <td colspan="2">{{ $checkbox($catalogNames('plataforma_presencial')->diff(['Teams', 'Zoom', 'Meet', 'Webex'])->isNotEmpty()) }}</td>
        </tr>
        <tr>
            <td class="form016-gray" colspan="2" rowspan="2">Plataformas para la modalidad a distancia (Si aplica)</td>
            <td class="form016-gray form016-center">Campus<br>virtual UNAH</td>
            <td class="form016-gray form016-center">Moodle</td>
            <td class="form016-gray form016-center">Classroom Google</td>
            <td class="form016-gray form016-center">Teams</td>
            <td class="form016-gray form016-center" colspan="2">Otro</td>
        </tr>
        <tr class="form016-center">
            <td>{{ $checkbox($hasPlatformDistancia('Campus virtual UNAH') || $hasCatalog('plataforma_distancia', 'Campus')) }}</td>
            <td>{{ $checkbox($hasPlatformDistancia('Moodle') || $hasCatalog('plataforma_distancia', 'Moodle')) }}</td>
            <td>{{ $checkbox($hasPlatformDistancia('Classroom Google') || $hasCatalog('plataforma_distancia', 'Classroom')) }}</td>
            <td>{{ $checkbox($hasPlatformDistancia('Teams') || $hasCatalog('plataforma_distancia', 'Teams')) }}</td>
            <td colspan="2">{{ $checkbox($catalogNames('plataforma_distancia')->diff(['Campus virtual UNAH', 'Moodle', 'Classroom Google', 'Teams'])->isNotEmpty()) }}</td>
        </tr>
    </table>

    <table class="form016-table">
        <colgroup>
            <col style="width: 24%">
            <col style="width: 23%">
            <col style="width: 15%">
            <col style="width: 23%">
            <col style="width: 15%">
        </colgroup>
        <tr><td class="form016-blue" colspan="5">13.&nbsp;&nbsp; Antecedentes de la acci&oacute;n. <em>(Indicar el origen para el dise&ntilde;o y puesta en marcha de la acci&oacute;n del programa de formaci&oacute;n)</em></td></tr>
        @foreach ([
            ['Iniciativa de la unidad academica', 'ONG'],
            ['Solicitud externa privada', 'Patronatos'],
            ['Secretaria de Estado', 'Sector financiero'],
            ['Gobiernos locales', 'Sector productivo'],
            ['Universidades', 'Otros'],
        ] as [$left, $right])
            <tr>
                <td class="form016-gray">{{ $left }}</td>
                <td class="form016-center">{{ $checkbox($hasCatalog('antecedente', $left === 'Gobiernos locales' ? 'Gobierno local' : $left)) }}</td>
                <td class="form016-gray">{{ $right }}</td>
                <td class="form016-center" colspan="2">{{ $checkbox($hasCatalog('antecedente', $right)) }}</td>
            </tr>
        @endforeach
    </table>

    {!! $closePage !!}
    {!! $openPage(3) !!}

    <div class="form016-section">II.&nbsp;&nbsp;&nbsp;&nbsp; PERFIL DE LOS BENEFICIARIOS (PARTICIPANTES)</div>
    <table class="form016-table">
        <colgroup>
            <col style="width: 42%">
            <col style="width: 58%">
        </colgroup>
        <tr><td class="form016-blue" colspan="2">14.&nbsp;&nbsp; Grado acad&eacute;mico requerido:</td></tr>
        @foreach (['Titulo de Educacion Media', 'Titulo Universitario', 'Acreditar experiencia comprobada en el area'] as $grado)
            <tr>
                <td class="form016-gray">{{ $grado }}</td>
                <td class="form016-center">{{ $checkbox($hasCatalog('grado_academico', $grado)) }}</td>
            </tr>
        @endforeach
    </table>
    <table class="form016-table">
        <colgroup>
            <col style="width: 27%">
            <col style="width: 14%">
            <col style="width: 30%">
            <col style="width: 29%">
        </colgroup>
        <tr><td class="form016-blue" colspan="4">15.&nbsp;&nbsp; Perfil de los principales participantes al que est&aacute; orientado el programa de formaci&oacute;n</td></tr>
        @foreach ([
            ['Egresados(as) UNAH', 'Lideres comunitarios'],
            ['Funcionarios publicos', 'ONG'],
            ['Estudiantes universitarios', 'Profesionales universitarios otros IES'],
            ['Empresa privada de servicios', 'Sector productivo'],
            ['Sociedad civil', 'Academicos'],
        ] as [$left, $right])
            <tr>
                <td class="form016-gray">{{ $left }}</td>
                <td class="form016-center">{{ $checkbox($hasCatalog('perfil_participante', $left === 'Egresados(as) UNAH' ? 'Egresados UNAH' : $left)) }}</td>
                <td class="form016-gray">{{ $right }}</td>
                <td class="form016-center">{{ $checkbox($hasCatalog('perfil_participante', $right)) }}</td>
            </tr>
        @endforeach
    </table>

    {!! $closePage !!}
    {!! $openPage(4) !!}

    <div class="form016-section">III.&nbsp;&nbsp;&nbsp; EQUIPO DOCENTE DEL CERTIFICADO</div>
    <table class="form016-table form016-compact">
        <colgroup>
            <col style="width: 23%">
            <col style="width: 24%">
            <col style="width: 17%">
            <col style="width: 36%">
        </colgroup>
        <tr><td class="form016-blue" colspan="4">16.&nbsp;&nbsp; Coordinador/a del Certificado Universitario COORDINACION</td></tr>
        <tr>
            <td class="form016-gray">Nombre Completo:</td>
            <td colspan="3">{{ $value($coordinador?->nombre_completo) }}</td>
        </tr>
        <tr>
            <td class="form016-gray">No. de empleado</td>
            <td>{{ $value($coordinador?->numero_empleado) }}</td>
            <td class="form016-gray">Identidad:</td>
            <td>{{ $value($coordinador?->identidad) }}</td>
        </tr>
        <tr>
            <td class="form016-gray" colspan="2">Correo electr&oacute;nico:</td>
            <td class="form016-gray">Celular:</td>
            <td>{{ $value($coordinador?->celular) }}</td>
        </tr>
        <tr>
            <td colspan="2">{{ $value($coordinador?->correo) }}</td>
            <td colspan="2">&nbsp;</td>
        </tr>
        <tr>
            <td class="form016-gray">Categor&iacute;a:</td>
            <td class="form016-gray" colspan="3">Departamento al que pertenece:</td>
        </tr>
        <tr>
            <td>{{ $value($coordinador?->categoria) }}</td>
            <td colspan="3">{{ $value($coordinador?->departamento) }}</td>
        </tr>
    </table>

    <table class="form016-table form016-compact">
        <colgroup>
            <col style="width: 29%">
            <col style="width: 23.66%">
            <col style="width: 23.67%">
            <col style="width: 23.67%">
        </colgroup>
        <tr><td class="form016-blue" colspan="4">17.&nbsp;&nbsp; EQUIPO DOCENTE</td></tr>
        <tr>
            <td class="form016-gray" rowspan="2">Cantidad de equipo docente</td>
            <td class="form016-gray form016-center">Docentes de la UNAH</td>
            <td class="form016-gray form016-center">Consultor nacional</td>
            <td class="form016-gray form016-center">Consultor internacional</td>
        </tr>
        <tr class="form016-center">
            <td>{{ $docentes->filter(fn ($row) => $row->rol === 'Docente UNAH' || ($row->perfil_docente ?: '') === 'Profesor de la UNAH')->count() }}</td>
            <td>{{ $docentes->filter(fn ($row) => $row->rol === 'Consultor nacional' || ($row->perfil_docente ?: '') === 'Consultor Nacional')->count() }}</td>
            <td>{{ $docentes->filter(fn ($row) => $row->rol === 'Consultor internacional' || ($row->perfil_docente ?: '') === 'Consultor Internacional')->count() }}</td>
        </tr>
    </table>

    @for ($i = 0; $i < max(1, $docentes->count()); $i++)
        @php $docente = $docentes->get($i); @endphp
        <table class="form016-table form016-compact">
            <colgroup>
                <col style="width: 29%">
                <col style="width: 22%">
                <col style="width: 24%">
                <col style="width: 25%">
            </colgroup>
            <tr><td class="form016-blue form016-center" colspan="4">SECCION {{ str_pad($i + 1, 2, '0', STR_PAD_LEFT) }}<br><span class="form016-small">(De abrirse m&aacute;s de 1 secci&oacute;n, agregar m&aacute;s tablas de ser necesario)</span></td></tr>
            <tr>
                <td class="form016-gray" colspan="4">Perfil del docente</td>
            </tr>
            <tr class="form016-center">
                <td class="form016-gray">Profesor de la UNAH</td>
                <td class="form016-gray">Consultor Nacional</td>
                <td class="form016-gray" colspan="2">Consultor Internacional</td>
            </tr>
            <tr class="form016-center">
                <td>{{ $checkbox(($docente?->perfil_docente ?: $docente?->rol) === 'Profesor de la UNAH' || $docente?->rol === 'Docente UNAH') }}</td>
                <td>{{ $checkbox(($docente?->perfil_docente ?: $docente?->rol) === 'Consultor Nacional' || $docente?->rol === 'Consultor nacional') }}</td>
                <td colspan="2">{{ $checkbox(($docente?->perfil_docente ?: $docente?->rol) === 'Consultor Internacional' || $docente?->rol === 'Consultor internacional') }}</td>
            </tr>
            <tr>
                <td class="form016-gray form016-center" colspan="4">Datos del docente</td>
            </tr>
            <tr>
                <td class="form016-gray">Nombre completo:</td>
                <td colspan="3">{{ $value($docente?->nombre_completo) }}</td>
            </tr>
            <tr>
                <td class="form016-gray">Nombre del espacio de aprendizaje que impartir&aacute;:</td>
                <td colspan="3">{{ $value($docente?->espacio_aprendizaje) }}</td>
            </tr>
            <tr>
                <td class="form016-gray">N&uacute;mero de empleado / n&uacute;mero de identificaci&oacute;n</td>
                <td>{{ collect([$docente?->numero_empleado, $docente?->identidad])->filter()->implode(' / ') }}</td>
                <td class="form016-gray">Categor&iacute;a docente</td>
                <td>{{ $value($docente?->categoria) }}</td>
            </tr>
            <tr>
                <td class="form016-gray">Correo electr&oacute;nico</td>
                <td colspan="3">{{ $value($docente?->correo) }}</td>
            </tr>
            <tr>
                <td class="form016-gray">Departamento acad&eacute;mico al que pertenece:</td>
                <td colspan="3">{{ $value($docente?->departamento) }}</td>
            </tr>
            <tr>
                <td class="form016-gray">&Uacute;ltimo t&iacute;tulo acad&eacute;mico obtenido:</td>
                <td colspan="3">{{ $value($docente?->ultimo_titulo) }}</td>
            </tr>
            <tr>
                <td class="form016-gray">Pa&iacute;s de procedencia</td>
                <td colspan="3">{{ $value($docente?->pais_procedencia, $docente?->nacionalidad) }}</td>
            </tr>
            <tr>
                <td class="form016-gray">Universidad de procedencia</td>
                <td colspan="3">{{ $value($docente?->universidad_procedencia) }}</td>
            </tr>
            <tr class="form016-center">
                <td class="form016-gray" rowspan="2">Tipo de asignaci&oacute;n acad&eacute;mica<br><span class="form016-small">(solo para personal de la UNAH)</span></td>
                <td class="form016-gray" colspan="2">Carga acad&eacute;mica del PAC</td>
                <td class="form016-gray">Contrataci&oacute;n jornada contraria</td>
            </tr>
            <tr class="form016-center">
                <td>S&iacute; {{ $checkbox((bool) $docente?->carga_academica_pac) }}</td>
                <td>No {{ $checkbox(! (bool) $docente?->carga_academica_pac) }}</td>
                <td>S&iacute; {{ $checkbox((bool) $docente?->contratacion_jornada_contraria) }} &nbsp;&nbsp; No {{ $checkbox(! (bool) $docente?->contratacion_jornada_contraria) }}</td>
            </tr>
        </table>
    @endfor

    {!! $closePage !!}
    {!! $openPage(5) !!}

    <div class="form016-section">IV.&nbsp;&nbsp;&nbsp; INFORMACION DE LA ENTIDAD CONTRAPARTE</div>
    <table class="form016-table">
        <colgroup>
            <col style="width: 33%">
            <col style="width: 16%">
            <col style="width: 33%">
            <col style="width: 18%">
        </colgroup>
        <tr>
            <td class="form016-blue">19.&nbsp;&nbsp; LA ACTIVIDAD TIENE CONTRAPARTE</td>
            <td class="form016-blue form016-center">SI<br>{{ $checkbox((bool) $contraparte) }}</td>
            <td class="form016-blue form016-center" colspan="2">NO<br>{{ $checkbox(! $contraparte) }}</td>
        </tr>
        <tr><td class="form016-gray form016-center" colspan="4">PERFIL DE LA ENTIDAD CONTRAPARTE <span class="form016-small">(En los casos que aplique)</span></td></tr>
        @foreach ([
            ['Secretaria de Estado', 'Organizaciones gremiales'],
            ['Gobierno Municipal', 'Sociedad civil organizada'],
            ['Sector productivo', 'Sector academico'],
            ['Entidades financieras', 'Organismos internacionales'],
            ['Sector privado de servicios', 'Unidad de la UNAH'],
        ] as [$left, $right])
            <tr>
                <td class="form016-gray">{{ $left }}</td>
                <td class="form016-center">{{ $checkbox(str($contraparte?->tipoContraparte?->nombre)->ascii()->lower()->contains(str($left)->ascii()->lower())) }}</td>
                <td class="form016-gray">{{ $right }}</td>
                <td class="form016-center">{{ $checkbox(str($contraparte?->tipoContraparte?->nombre)->ascii()->lower()->contains(str($right)->ascii()->lower())) }}</td>
            </tr>
        @endforeach
    </table>

    <table class="form016-table">
        <colgroup>
            <col style="width: 28%">
            <col style="width: 28%">
            <col style="width: 22%">
            <col style="width: 22%">
        </colgroup>
        <tr>
            <td class="form016-gray">25.Nombre de la contraparte</td>
            <td colspan="3">{{ $value($contraparte?->nombre) }}</td>
        </tr>
        <tr>
            <td class="form016-gray">RTN / identificaci&oacute;n internacional</td>
            <td colspan="3">{{ $value($contraparte?->rtn) }}</td>
        </tr>
        <tr>
            <td class="form016-gray">26.Nombre del contacto directo</td>
            <td>{{ $value($contraparte?->representante) }}</td>
            <td class="form016-gray">Correo electr&oacute;nico</td>
            <td>{{ $value($contraparte?->correo) }}</td>
        </tr>
        <tr>
            <td class="form016-gray">27.Cargo del contacto de la contraparte</td>
            <td>{{ $value($contraparte?->cargo_contacto) }}</td>
            <td class="form016-gray">Tel&eacute;fono</td>
            <td>{{ $value($contraparte?->telefono) }}</td>
        </tr>
        <tr>
            <td class="form016-gray">28.Direcci&oacute;n exacta de la sede principal</td>
            <td colspan="3">{{ $value($contraparte?->direccion) }}</td>
        </tr>
        <tr>
            <td class="form016-gray" rowspan="2">29.Tipo de instrumento que da lugar a la alianza</td>
            <td class="form016-gray form016-center">Carta formal de solicitud a la unidad acad&eacute;mica</td>
            <td class="form016-gray form016-center">Tipo de instrumento que da lugar a la alianza</td>
            <td class="form016-gray form016-center">Carta formal de solicitud a la unidad acad&eacute;mica</td>
        </tr>
        <tr class="form016-center">
            <td>{{ $checkbox(str($contraparte?->instrumentoAlianza?->nombre)->ascii()->lower()->contains('carta formal de solicitud')) }}</td>
            <td>{{ $checkbox(filled($contraparte?->instrumentoAlianza?->nombre) && ! str($contraparte?->instrumentoAlianza?->nombre)->ascii()->lower()->contains('carta formal de solicitud')) }}</td>
            <td>{{ $checkbox(false) }}</td>
        </tr>
        <tr>
            <td class="form016-gray">30.Breve descripci&oacute;n de los compromisos asumidos por la contraparte</td>
            <td colspan="3" class="form016-large">{{ $value($contraparte?->compromisos) }}</td>
        </tr>
    </table>

    {!! $closePage !!}
    {!! $openPage(6) !!}

    <div class="form016-section">V.&nbsp;&nbsp;&nbsp;&nbsp; INFORMACION ACADEMICA DEL CERTIFICADO <span class="form016-note">(La informaci&oacute;n se transcribir&aacute; tal como aparece en el programa de estudio).<br>Nota: al finalizar la ficha, se deber&aacute; adjuntar copia de las descripciones m&iacute;nimas del plan de estudios oficial, foliado y sellado por la Secretar&iacute;a General de la UNAH</span></div>
    <table class="form016-table">
        <tr><td class="form016-blue">20.&nbsp;&nbsp; RESUMEN DEL CERTIFICADO:</td></tr>
        <tr><td class="form016-gray">20.1 Resultados de Aprendizaje (Redactar los resultados de aprendizaje, considerando los contenidos de los espacios de aprendizaje que integran el Certificado Universitario)</td></tr>
        <tr><td class="form016-academic-field">{{ $value($accion->resumen) }}</td></tr>
        <tr><td class="form016-gray">20.2 Impacto que se desea generar con la implementaci&oacute;n del Certificado; (cambios mencione tres como m&iacute;nimo y en 100 palabras)</td></tr>
        <tr><td class="form016-academic-field">{{ $value($accion->impacto_esperado) }}</td></tr>
        <tr><td class="form016-gray">20.3 Resumen de la log&iacute;stica que emplear&aacute; para el desarrollo de la actividad; (recursos materiales) papeler&iacute;a, salones, transporte otros</td></tr>
        <tr><td class="form016-academic-field">{{ $value($accion->logistica) }}</td></tr>
    </table>

    {!! $closePage !!}
    {!! $openPage(7) !!}

    <div class="form016-section">VI.&nbsp;&nbsp;&nbsp; DETALLE DEL PRESUPUESTO</div>
    <table class="form016-table form016-compact">
        <colgroup>
            <col style="width: 58%">
            <col style="width: 21%">
            <col style="width: 21%">
        </colgroup>
        <tr>
            <td class="form016-blue form016-center">Obtendr&aacute; ingresos por la actividad</td>
            <td class="form016-blue form016-center">SI</td>
            <td class="form016-blue form016-center">NO</td>
        </tr>
        <tr class="form016-center">
            <td>&nbsp;</td>
            <td>{{ $checkbox((bool) $accion->genera_ingresos) }}</td>
            <td>{{ $checkbox(! $accion->genera_ingresos) }}</td>
        </tr>
    </table>

    @php $ingresoRows = $budgetRows('ingresos', ['Cuotas de inscripción', 'Gestión de becas (donaciones)', 'Otros (describir brevemente)']); @endphp
    <table class="form016-table form016-compact">
        <colgroup>
            <col style="width: 58%">
            <col style="width: 12%">
            <col style="width: 15%">
            <col style="width: 15%">
        </colgroup>
        <tr><td class="form016-blue" colspan="4">3.&nbsp;&nbsp; Presupuesto de ingresos (manifestado en lempiras)</td></tr>
        <tr>
            <td class="form016-blue form016-center">Concepto</td>
            <td class="form016-blue form016-center">Cantidad</td>
            <td class="form016-blue form016-center">Costo<br>unitario</td>
            <td class="form016-blue form016-center">Costo Total</td>
        </tr>
        @foreach ($ingresoRows as $index => $row)
            <tr>
                <td>{{ chr(97 + $index) }})&nbsp;&nbsp;{{ $row['rubro'] }}</td>
                <td class="form016-center">{{ filled($row['cantidad']) ? $money($row['cantidad']) : '' }}</td>
                <td class="form016-center">{{ filled($row['costo_unitario']) ? $money($row['costo_unitario']) : '' }}</td>
                <td class="form016-center">{{ filled($row['total']) ? $money($row['total']) : '' }}</td>
            </tr>
        @endforeach
        <tr>
            <td class="form016-blue form016-right" colspan="3"><u>Total Ingresos</u></td>
            <td class="form016-center">{{ $money($presupuestosPorTipo->get('ingresos')?->monto_solicitado ?? 0) }}</td>
        </tr>
    </table>

    @php $egresoRows = $budgetRows('egresos', ['Pago de conferencistas / facilitadores', 'Gastos de materiales y suministros', 'Gastos de movilización (transporte, pasajes)', 'Gastos de manutención y hospedaje', 'Costos administrativos / Financieros', 'Otros']); @endphp
    <table class="form016-table form016-compact">
        <colgroup>
            <col style="width: 58%">
            <col style="width: 12%">
            <col style="width: 15%">
            <col style="width: 15%">
        </colgroup>
        <tr><td class="form016-blue" colspan="4">4.&nbsp;&nbsp; Presupuesto de egresos (manifestado en lempiras)</td></tr>
        <tr>
            <td class="form016-blue">Concepto</td>
            <td class="form016-blue form016-center">Cantidad</td>
            <td class="form016-blue form016-center">Costo<br>unitario</td>
            <td class="form016-blue form016-center">Costo Total</td>
        </tr>
        @foreach ($egresoRows as $index => $row)
            <tr>
                <td>{{ chr(97 + $index) }})&nbsp;&nbsp;{{ $row['rubro'] }}</td>
                <td class="form016-center">{{ filled($row['cantidad']) ? $money($row['cantidad']) : '' }}</td>
                <td class="form016-center">{{ filled($row['costo_unitario']) ? $money($row['costo_unitario']) : '' }}</td>
                <td class="form016-center">{{ filled($row['total']) ? $money($row['total']) : '' }}</td>
            </tr>
        @endforeach
        <tr>
            <td class="form016-blue form016-right" colspan="3"><u>Total egresos</u></td>
            <td class="form016-center">{{ $money($presupuestosPorTipo->get('egresos')?->monto_solicitado ?? 0) }}</td>
        </tr>
        <tr>
            <td class="form016-blue form016-center" colspan="3">Excedente que se espera lograr de la de la actividad (ingresos menos los egresos)</td>
            <td class="form016-center">{{ $money(($presupuestosPorTipo->get('ingresos')?->monto_solicitado ?? 0) - ($presupuestosPorTipo->get('egresos')?->monto_solicitado ?? 0)) }}</td>
        </tr>
    </table>

    <table class="form016-table form016-compact">
        <tr>
            <td class="form016-blue" style="width: 24%">5.&nbsp;&nbsp; Breve descripci&oacute;n en qu&eacute; se destinar&aacute; el excedente de la actividad</td>
            <td>{{ $value($accion->descripcion_excedente) }}</td>
        </tr>
        <tr>
            <td class="form016-blue">6.&nbsp;&nbsp; Mecanismo de administraci&oacute;n de la acci&oacute;n</td>
            <td>FUNDAUNAH {{ $checkbox(str($accion->mecanismo_administracion)->lower()->contains('fundaunah')) }} &nbsp;&nbsp;&nbsp;&nbsp; Tesorer&iacute;a de la UNAH {{ $checkbox(str($accion->mecanismo_administracion)->lower()->contains('tesorer')) }}</td>
        </tr>
    </table>

    {!! $closePage !!}
    {!! $openPage(8) !!}

    @php
        $aporteLabels = [
            'Personal docente (horas de trabajo en la actividad)',
            'Horas de participación de los estudiantes',
            'Horas de participación de voluntarios',
            'Útiles y materiales de oficina',
            'Costos indirectos depreciación de equipo (3% de la suma de los conceptos a), b) c) anteriores',
            'Costos indirectos servicios públicos (3% de la suma de los conceptos a), b) y c) anteriores',
        ];
        $aporteRows = $budgetRows('aporte_unah', $aporteLabels);
    @endphp
    <table class="form016-table form016-compact">
        <colgroup>
            <col style="width: 58%">
            <col style="width: 12%">
            <col style="width: 15%">
            <col style="width: 15%">
        </colgroup>
        <tr><td class="form016-blue" colspan="4">7.&nbsp;&nbsp; Aporte de la UNAH <span class="form016-small">(Esta tabla se completa siempre)</span></td></tr>
        <tr>
            <td class="form016-blue form016-center">Concepto</td>
            <td class="form016-blue form016-center">Cantidad</td>
            <td class="form016-blue form016-center">Costo<br>unitario</td>
            <td class="form016-blue form016-center">Costo Total</td>
        </tr>
        @foreach ($aporteRows as $index => $row)
            <tr>
                <td>{{ chr(97 + $index) }})&nbsp;&nbsp;{{ $aporteLabels[$index] ?? $row['rubro'] }}</td>
                <td class="form016-center">{{ filled($row['cantidad']) ? $money($row['cantidad']) : '' }}</td>
                <td class="form016-center">{{ filled($row['costo_unitario']) ? $money($row['costo_unitario']) : '' }}</td>
                <td class="form016-center">{{ filled($row['total']) ? $money($row['total']) : '' }}</td>
            </tr>
        @endforeach
        <tr>
            <td class="form016-blue form016-right" colspan="3"><u>Total aporte UNAH</u></td>
            <td class="form016-center">{{ $money($presupuestosPorTipo->get('aporte_unah')?->monto_solicitado ?? 0) }}</td>
        </tr>
    </table>

    <div class="form016-firmas-title">VII.&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; FIRMAS</div>
    <table class="form016-table">
        <colgroup>
            <col style="width: 50%">
            <col style="width: 50%">
        </colgroup>
        <tr>
            <td class="form016-blue form016-center">Jefe de Departamento</td>
            <td class="form016-blue form016-center">Comit&eacute; de vinculaci&oacute;n</td>
        </tr>
        <tr>
            <td class="form016-signature-box">Nombre:</td>
            <td class="form016-signature-box">Firma:</td>
        </tr>
        <tr><td class="form016-signature-spacer" colspan="2">&nbsp;</td></tr>
        <tr><td class="form016-blue form016-center" colspan="2">Decano (a) o Director (a) del Centro Regional</td></tr>
        <tr><td colspan="2">Nombre:</td></tr>
        <tr><td class="form016-signature-large" colspan="2">&nbsp;</td></tr>
        <tr><td class="form016-gray form016-center" colspan="2">Nombre, firma y sello</td></tr>
    </table>

    {!! $closePage !!}
</div>

@if (! $isPdf)
    <script>
        (() => {
            const shells = document.querySelectorAll('.form016-shell.screen-document');
            const pageWidth = 8.5 * 96;
            const maxScale = 1.42;

            const resize = (shell) => {
                const availableWidth = shell.clientWidth;
                const scale = Math.min(maxScale, Math.max(0.35, (availableWidth - 2) / pageWidth));
                shell.style.setProperty('--form016-screen-scale', scale.toFixed(4));
            };

            shells.forEach((shell) => {
                resize(shell);

                if ('ResizeObserver' in window) {
                    new ResizeObserver(() => resize(shell)).observe(shell);
                }
            });

            window.addEventListener('resize', () => shells.forEach(resize));
        })();
    </script>
@endif
