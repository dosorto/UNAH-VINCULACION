@php
    $isPdf = $isPdf ?? false;
    $assetUrl = fn (string $path) => $isPdf ? 'file://'.public_path($path) : asset($path);
    $headerUrl = $assetUrl('images/enf/form-018-header.png');
    $watermarkUrl = $assetUrl('images/enf/form-018-watermark.png');
    $footerUrl = $assetUrl('images/enf/form-018-footer.png');
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
            '<header class="form016-header-row">'.
                '<img class="form016-header-brand" src="'.e($headerUrl).'" alt="UNAH VRA Dirección de Vinculación Universidad Sociedad">'.
                '<div class="form016-daft">Dirección<br>Académica<br>de Formación Tecnológica</div>'.
                '<div class="form016-contact">'.
                    'vinculacion.sociedad@unah.edu.hn<br>'.
                    'Tel. 2216-7070 Ext. 110576<br><br>'.
                    '<span>formaciontecnologica@unah.edu.hn</span><br>'.
                    'Tel: 2216-7008/2216-6100<br>'.
                    'Ext: 110615 - 110617<br>'.
                    '110186 - 110192'.
                '</div>'.
                '<div class="form016-yellow-strip"></div>'.
            '</header>'.
            '<img class="form016-watermark" src="'.e($watermarkUrl).'" alt="">'.
            '<img class="form016-footer" src="'.e($footerUrl).'" alt="">'.
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
        font-size: 10.2pt;
        line-height: 1.08;
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
        gap: 18px;
        justify-items: center;
    }

    .form016-page {
        background: #fff;
        max-width: none;
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
        overflow: visible;
        page-break-inside: auto;
    }

    .form016-shell.screen-document .form016-page {
        box-shadow: 0 10px 30px rgba(15, 23, 42, .14);
        zoom: var(--form016-screen-scale);
    }

    .form016-header-brand {
        height: auto;
        display: block;
        left: 0.28in;
        position: absolute;
        top: 0.28in;
        width: 4.98in;
    }

    .form016-header-row {
        height: 1.5in;
        position: relative;
        z-index: 2;
    }

    .form016-daft {
        border-left: 2px solid #8a96a8;
        color: #8b98aa;
        font-family: Arial, Helvetica, sans-serif;
        font-size: 9.2pt;
        font-weight: 800;
        left: 5.26in;
        line-height: 1.02;
        padding-left: 0.13in;
        position: absolute;
        top: 0.43in;
        width: 1.18in;
    }

    .form016-contact {
        color: #8b98aa;
        font-family: Arial, Helvetica, sans-serif;
        font-size: 5.9pt;
        font-weight: 800;
        line-height: 1.18;
        position: absolute;
        right: 0.42in;
        text-align: right;
        top: 0.32in;
        white-space: nowrap;
        width: 1.56in;
    }

    .form016-contact span {
        color: #0000ee;
        text-decoration: underline;
    }

    .form016-yellow-strip {
        background: #ffc000;
        height: 1.18in;
        position: absolute;
        right: 0.05in;
        top: 0.08in;
        width: 0.2in;
    }

    .form016-watermark {
        opacity: .24;
        pointer-events: none;
        position: absolute;
        right: -.52in;
        top: 4.2in;
        width: 5.25in;
        z-index: 0;
    }

    .form016-footer {
        bottom: .27in;
        height: auto;
        left: .85in;
        position: absolute;
        width: 5.2in;
        z-index: 2;
    }

    .form016-shell.is-pdf .form016-footer {
        display: none !important;
    }

    .form016-main {
        margin-left: 1in;
        position: relative;
        width: 7in;
        z-index: 1;
    }

    .form016-code {
        background: #002060;
        color: #fff;
        font-family: Arial, sans-serif;
        font-size: 15pt;
        font-weight: 800;
        height: 0.22in;
        line-height: 0.22in;
        margin: 0 0.48in 0.14in 0.46in;
        padding: 0 0.05in 0;
        text-align: right;
    }

    .form016-title {
        font-family: Arial, sans-serif;
        font-size: 15pt;
        font-weight: 800;
        line-height: 1;
        margin: 0 0 0.18in;
        text-align: center;
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
        right: -0.18in;
        text-align: center;
        top: -0.28in;
        width: 0.25in;
        z-index: 2;
    }

    .form016-section {
        color: #002060;
        font-family: Arial, sans-serif;
        font-size: 12pt;
        font-weight: 800;
        line-height: 1.1;
        margin: 0.06in 0 0.16in;
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
        border: 0.7px solid #6f6f6f;
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
        margin: 0 0.055in;
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
            zoom: 1 !important;
            width: 8.5in;
            min-height: 11in;
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

    {!! $closePage !!}
    {!! $openPage(2) !!}

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
        <tr>
            @foreach (['Presencial', 'Semi presencial', '100% virtual', 'Virtual sincronico'] as $modalidad)
                <td class="form016-center">{{ $modalidad }}<br>{{ $checkbox(str($lugar?->modalidad_ejecucion)->ascii()->lower()->contains(str($modalidad)->ascii()->lower()->replace('sincronico', 'sincronico'))) }}</td>
            @endforeach
        </tr>
        <tr>
            <td class="form016-gray">Lugar de imparticion</td>
            <td>{{ $value($lugar?->nombre_lugar) }}</td>
            <td class="form016-gray">No. Aula / Edificio / Centro</td>
            <td>{{ collect([$lugar?->aula, $lugar?->edificio, $lugar?->centro])->filter()->implode(' / ') }}</td>
        </tr>
        <tr>
            <td class="form016-gray">Descripcion de plataformas</td>
            <td colspan="3">{{ $value($lugar?->descripcion_plataformas) }}</td>
        </tr>
        <tr>
            <td class="form016-gray">Plataformas presencial</td>
            <td colspan="3">{{ $catalogNames('plataforma_presencial')->implode(', ') }}</td>
        </tr>
        <tr>
            <td class="form016-gray">Plataformas a distancia</td>
            <td colspan="3">{{ $catalogNames('plataforma_distancia')->implode(', ') }}</td>
        </tr>
    </table>

    <table class="form016-table">
        <tr><td class="form016-blue" colspan="4">13.&nbsp;&nbsp; Antecedentes de la acción</td></tr>
        @foreach (array_chunk(['Iniciativa de la unidad academica', 'Solicitud externa privada', 'Secretaria de Estado', 'Gobierno local', 'Universidades', 'ONG', 'Patronatos', 'Sector financiero', 'Sector productivo', 'Otros'], 2) as $row)
            <tr>
                @foreach ($row as $item)
                    <td>{{ $item }}</td>
                    <td class="form016-center">{{ $checkbox($hasCatalog('antecedente', $item)) }}</td>
                @endforeach
            </tr>
        @endforeach
    </table>

    <div class="form016-section">II.&nbsp;&nbsp;&nbsp;&nbsp; PERFIL DE LOS BENEFICIARIOS (PARTICIPANTES)</div>
    <table class="form016-table">
        <tr>
            <td class="form016-blue" style="width: 35%">14.&nbsp;&nbsp; Grado académico requerido</td>
            <td>
                @foreach (['Titulo de Educacion Media', 'Titulo Universitario', 'Acreditar experiencia comprobada en el area'] as $grado)
                    <span style="display:inline-block;margin-right:18px">{{ $grado }} {{ $checkbox($hasCatalog('grado_academico', $grado)) }}</span>
                @endforeach
            </td>
        </tr>
        <tr>
            <td class="form016-blue">15.&nbsp;&nbsp; Perfil de los principales participantes</td>
            <td>{{ $catalogNames('perfil_participante')->implode(', ') }}{{ $accion->descripcion_participantes ? ' - '.$accion->descripcion_participantes : '' }}</td>
        </tr>
    </table>

    {!! $closePage !!}
    {!! $openPage(3) !!}

    <div class="form016-section">III.&nbsp;&nbsp;&nbsp; EQUIPO DOCENTE DEL CERTIFICADO</div>
    <table class="form016-table">
        <tr><td class="form016-blue" colspan="4">16.&nbsp;&nbsp; Coordinador/a del Certificado Universitario</td></tr>
        <tr>
            <td class="form016-gray">Nombre completo</td>
            <td>{{ $value($coordinador?->nombre_completo) }}</td>
            <td class="form016-gray">No. de empleado</td>
            <td>{{ $value($coordinador?->numero_empleado) }}</td>
        </tr>
        <tr>
            <td class="form016-gray">Identidad</td>
            <td>{{ $value($coordinador?->identidad) }}</td>
            <td class="form016-gray">Correo / Celular</td>
            <td>{{ collect([$coordinador?->correo, $coordinador?->celular])->filter()->implode(' / ') }}</td>
        </tr>
        <tr>
            <td class="form016-gray">Categoria</td>
            <td>{{ $value($coordinador?->categoria) }}</td>
            <td class="form016-gray">Departamento</td>
            <td>{{ $value($coordinador?->departamento) }}</td>
        </tr>
    </table>

    @for ($i = 0; $i < max(2, $docentes->count()); $i++)
        @php $docente = $docentes->get($i); @endphp
        <table class="form016-table">
            <tr><td class="form016-blue" colspan="4">{{ 17 + $i }}.&nbsp;&nbsp; SECCION {{ str_pad($i + 1, 2, '0', STR_PAD_LEFT) }} - Datos del docente</td></tr>
            <tr>
                <td class="form016-gray">Perfil del docente</td>
                <td colspan="3">
                    Profesor UNAH {{ $checkbox(($docente?->perfil_docente ?: $docente?->rol) === 'Profesor de la UNAH' || $docente?->rol === 'Docente UNAH') }}
                    &nbsp;&nbsp; Consultor Nacional {{ $checkbox(($docente?->perfil_docente ?: $docente?->rol) === 'Consultor Nacional' || $docente?->rol === 'Consultor nacional') }}
                    &nbsp;&nbsp; Consultor Internacional {{ $checkbox(($docente?->perfil_docente ?: $docente?->rol) === 'Consultor Internacional' || $docente?->rol === 'Consultor internacional') }}
                </td>
            </tr>
            <tr>
                <td class="form016-gray">Nombre completo</td>
                <td>{{ $value($docente?->nombre_completo) }}</td>
                <td class="form016-gray">Espacio de aprendizaje</td>
                <td>{{ $value($docente?->espacio_aprendizaje) }}</td>
            </tr>
            <tr>
                <td class="form016-gray">No. empleado / identidad</td>
                <td>{{ collect([$docente?->numero_empleado, $docente?->identidad])->filter()->implode(' / ') }}</td>
                <td class="form016-gray">Correo</td>
                <td>{{ $value($docente?->correo) }}</td>
            </tr>
            <tr>
                <td class="form016-gray">Categoria / Departamento</td>
                <td>{{ collect([$docente?->categoria, $docente?->departamento])->filter()->implode(' / ') }}</td>
                <td class="form016-gray">Titulo / pais / universidad</td>
                <td>{{ collect([$docente?->ultimo_titulo, $docente?->pais_procedencia, $docente?->universidad_procedencia])->filter()->implode(' / ') }}</td>
            </tr>
            <tr>
                <td class="form016-gray">Asignacion academica</td>
                <td colspan="3">Carga academica del PAC {{ $checkbox((bool) $docente?->carga_academica_pac) }} &nbsp;&nbsp; Contratacion jornada contraria {{ $checkbox((bool) $docente?->contratacion_jornada_contraria) }}</td>
            </tr>
        </table>
    @endfor

    <div class="form016-section">IV.&nbsp;&nbsp;&nbsp; INFORMACION DE LA ENTIDAD CONTRAPARTE</div>
    <table class="form016-table">
        <tr>
            <td class="form016-blue">19.&nbsp;&nbsp; LA ACTIVIDAD TIENE CONTRAPARTE</td>
            <td>SI {{ $checkbox((bool) $contraparte) }}</td>
            <td>NO {{ $checkbox(! $contraparte) }}</td>
        </tr>
        <tr>
            <td class="form016-gray">Perfil de la entidad contraparte</td>
            <td colspan="2">{{ $contraparte?->tipoContraparte?->nombre }}</td>
        </tr>
        <tr>
            <td class="form016-gray">Nombre de la contraparte</td>
            <td colspan="2">{{ $value($contraparte?->nombre) }}</td>
        </tr>
        <tr>
            <td class="form016-gray">RTN / identificacion internacional</td>
            <td colspan="2">{{ $value($contraparte?->rtn) }}</td>
        </tr>
        <tr>
            <td class="form016-gray">Contacto / cargo</td>
            <td colspan="2">{{ collect([$contraparte?->representante, $contraparte?->cargo_contacto])->filter()->implode(' / ') }}</td>
        </tr>
        <tr>
            <td class="form016-gray">Correo / telefono</td>
            <td colspan="2">{{ collect([$contraparte?->correo, $contraparte?->telefono])->filter()->implode(' / ') }}</td>
        </tr>
        <tr>
            <td class="form016-gray">Direccion exacta</td>
            <td colspan="2">{{ $value($contraparte?->direccion) }}</td>
        </tr>
        <tr>
            <td class="form016-gray">Instrumento de alianza</td>
            <td colspan="2">{{ $contraparte?->instrumentoAlianza?->nombre }}</td>
        </tr>
        <tr>
            <td class="form016-gray">Compromisos asumidos</td>
            <td colspan="2" class="form016-large">{{ $value($contraparte?->compromisos) }}</td>
        </tr>
    </table>

    {!! $closePage !!}
    {!! $openPage(4) !!}

    <div class="form016-section">V.&nbsp;&nbsp;&nbsp;&nbsp; INFORMACION ACADEMICA DEL CERTIFICADO</div>
    <table class="form016-table">
        <tr><td class="form016-blue">20.1 Resultados de Aprendizaje</td></tr>
        <tr><td class="form016-large">{{ $value($accion->resumen) }}</td></tr>
        <tr><td class="form016-blue">20.2 Impacto esperado</td></tr>
        <tr><td class="form016-large">{{ $value($accion->impacto_esperado) }}</td></tr>
        <tr><td class="form016-blue">20.3 Resumen de la logistica</td></tr>
        <tr><td class="form016-large">{{ $value($accion->logistica) }}</td></tr>
        <tr><td class="form016-blue">20.4 Requisitos de emision del certificado</td></tr>
        <tr><td>{{ $value($certificado?->requisitos_emision) }}</td></tr>
    </table>

    {!! $closePage !!}
    {!! $openPage(5) !!}

    <div class="form016-section">VI.&nbsp;&nbsp;&nbsp; DETALLE DEL PRESUPUESTO</div>
    <table class="form016-table">
        <tr>
            <td class="form016-blue">21.&nbsp;&nbsp; Obtendrá ingresos por la actividad</td>
            <td>SI {{ $checkbox((bool) $accion->genera_ingresos) }}</td>
            <td>NO {{ $checkbox(! $accion->genera_ingresos) }}</td>
        </tr>
    </table>

    @foreach ([
        'ingresos' => ['Presupuesto de ingresos', ['Cuotas de inscripción', 'Gestión de becas', 'Otros']],
        'egresos' => ['Presupuesto de egresos', ['Pago de conferencistas / facilitadores', 'Gastos de materiales y suministros', 'Gastos de movilización', 'Gastos de manutención y hospedaje', 'Costos administrativos / Financieros', 'Otros']],
        'aporte_unah' => ['Aporte de la UNAH', ['Personal docente', 'Horas de participación de los estudiantes', 'Horas de participación de voluntarios', 'Útiles y materiales de oficina', 'Costos indirectos depreciación de equipo', 'Costos indirectos servicios públicos']],
    ] as $tipo => [$titulo, $rubros])
        @php $rows = $budgetRows($tipo, $rubros); @endphp
        @if ($tipo === 'aporte_unah')
            {!! $closePage !!}
            {!! $openPage(6) !!}
        @endif
        <table class="form016-table">
            <tr><td class="form016-blue" colspan="4">{{ ['ingresos' => '22', 'egresos' => '23', 'aporte_unah' => '24'][$tipo] }}.&nbsp;&nbsp; {{ $titulo }} (manifestado en lempiras)</td></tr>
            <tr>
                <td class="form016-gray">Concepto</td>
                <td class="form016-gray form016-center">Cantidad</td>
                <td class="form016-gray form016-center">Costo unitario</td>
                <td class="form016-gray form016-center">Costo Total</td>
            </tr>
            @foreach ($rows as $row)
                <tr>
                    <td>{{ $row['rubro'] }}</td>
                    <td class="form016-center">{{ filled($row['cantidad']) ? $money($row['cantidad']) : '' }}</td>
                    <td class="form016-center">{{ filled($row['costo_unitario']) ? $money($row['costo_unitario']) : '' }}</td>
                    <td class="form016-center">{{ filled($row['total']) ? $money($row['total']) : '' }}</td>
                </tr>
            @endforeach
            <tr>
                <td class="form016-right form016-gray" colspan="3">Total {{ $titulo }}</td>
                <td class="form016-center">{{ $money($presupuestosPorTipo->get($tipo)?->monto_solicitado ?? 0) }}</td>
            </tr>
        </table>
    @endforeach

    <table class="form016-table">
        <tr>
            <td class="form016-blue" style="width: 35%">25.&nbsp;&nbsp; Breve descripción en que se destinará el excedente</td>
            <td>{{ $value($accion->descripcion_excedente) }}</td>
        </tr>
        <tr>
            <td class="form016-blue">26.&nbsp;&nbsp; Mecanismo de administración de la acción</td>
            <td>FUNDAUNAH {{ $checkbox(str($accion->mecanismo_administracion)->lower()->contains('fundaunah')) }} &nbsp;&nbsp; Tesoreria de la UNAH {{ $checkbox(str($accion->mecanismo_administracion)->lower()->contains('tesorer')) }}</td>
        </tr>
    </table>

    <div class="form016-section">VII.&nbsp;&nbsp; FIRMAS</div>
    <table class="form016-table">
        <tr>
            <td class="form016-gray form016-center">Jefe de Departamento</td>
            <td class="form016-gray form016-center">Comite de vinculacion</td>
            <td class="form016-gray form016-center">Decano(a) o Director(a) del Centro Regional</td>
        </tr>
        <tr>
            <td class="form016-signature">Nombre: {{ $firmasPorRol->get('Jefe de Departamento')?->nombre_firmante }}<br><br>Firma:</td>
            <td class="form016-signature">Nombre: {{ $firmasPorRol->get('Comité de vinculación')?->nombre_firmante ?? $firmasPorRol->get('Comite de vinculacion')?->nombre_firmante }}<br><br>Firma:</td>
            <td class="form016-signature">Nombre: {{ $firmasPorRol->get('Decano(a) o Director(a) del Centro Regional')?->nombre_firmante }}<br><br>Nombre, firma y sello:</td>
        </tr>
    </table>

    <div class="form016-section">VIII.&nbsp;&nbsp; DOCUMENTOS ADJUNTOS A LA FICHA</div>
    <table class="form016-table">
        <tr>
            <td class="form016-blue form016-center" style="width: 7%">No</td>
            <td class="form016-blue form016-center">Descripcion</td>
            <td class="form016-blue form016-center" style="width: 8%">Si</td>
            <td class="form016-blue form016-center" style="width: 8%">No</td>
            @unless ($isPdf)
                <td class="form016-blue form016-center" style="width: 20%">Archivo</td>
            @endunless
        </tr>
        @forelse ($accion->documentos->values() as $documento)
            @php
                $tieneArchivo = filled($documento->ruta) && $documento->ruta !== 'pendiente';
                $documentoUrl = $tieneArchivo ? \Illuminate\Support\Facades\Storage::url($documento->ruta) : null;
            @endphp
            <tr>
                <td class="form016-center">{{ $loop->iteration }}</td>
                <td>{{ $documento->nombre ?: ($documento->descripcion ?: 'Documento adjunto') }}</td>
                <td class="form016-center">X</td>
                <td class="form016-center"></td>
                @unless ($isPdf)
                    <td class="form016-center">
                        @if ($documentoUrl)
                            <div class="form016-file-actions">
                                <a href="{{ $documentoUrl }}" target="_blank" rel="noopener" class="form016-file-button">Ver</a>
                                <a href="{{ $documentoUrl }}" download class="form016-file-button">Descargar</a>
                            </div>
                        @else
                            Pendiente
                        @endif
                    </td>
                @endunless
            </tr>
        @empty
            <tr>
                <td class="form016-center">1</td>
                <td>Descripciones minimas del plan de estudios oficial</td>
                <td class="form016-center"></td>
                <td class="form016-center">X</td>
                @unless ($isPdf)
                    <td></td>
                @endunless
            </tr>
        @endforelse
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
