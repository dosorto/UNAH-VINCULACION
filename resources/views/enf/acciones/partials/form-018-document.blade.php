@php
    use Illuminate\Support\HtmlString;
    use Illuminate\Support\Facades\Storage;
    use Illuminate\Support\Str;

    $isPdf = $isPdf ?? false;
    $lugar = $accion->lugaresEjecucion->first();
    $beneficiarios = $accion->beneficiarios;
    $coordinador = $accion->equipo->firstWhere('rol', 'Coordinador de la accion');
    $sistematizador = $accion->equipo->firstWhere('rol', 'Responsable de sistematizacion');
    $docentes = $accion->equipo->where('rol', 'Docente UNAH')->values();
    $consultoresNacionales = $accion->equipo->where('rol', 'Consultor nacional')->values();
    $consultoresInternacionales = $accion->equipo->where('rol', 'Consultor internacional')->values();
    $contraparte = $accion->contrapartes->first();
    $ingresos = $accion->presupuestos->firstWhere('tipo', 'ingresos');
    $egresos = $accion->presupuestos->firstWhere('tipo', 'egresos');
    $aporteUnah = $accion->presupuestos->firstWhere('tipo', 'aporte_unah');
    $ingresosTotal = (float) ($ingresos?->detalles?->sum('total') ?? 0);
    $egresosTotal = (float) ($egresos?->detalles?->sum('total') ?? 0);
    $aporteTotal = (float) ($aporteUnah?->detalles?->sum('total') ?? 0);
    $catalogosPorTipo = $accion->accionCatalogos
        ->groupBy(fn ($item) => $item->tipo ?: ($item->catalogo?->tipo ?? ''))
        ->map(fn ($items) => $items
            ->flatMap(fn ($item) => [$item->catalogo?->nombre, $item->valor_texto])
            ->filter()
            ->values());
    $participacionPorTipo = $accion->participacionUniversitaria->keyBy('tipo_participacion');

    $blank = fn ($value) => filled($value) ? $value : '';
    $money = fn ($value) => 'L '.number_format((float) $value, 2);
    $normalize = fn ($value) => Str::of(Str::ascii((string) $value))->lower()->toString();
    $hasText = fn ($value, string $needle) => str_contains($normalize($value), $normalize($needle));
    $matchesAnyText = function ($value, $needles) use ($normalize): bool {
        $normalizedValue = $normalize($value);

        foreach ((array) $needles as $needle) {
            if ($needle !== '' && str_contains($normalizedValue, $normalize($needle))) {
                return true;
            }
        }

        return false;
    };
    $hasCatalog = function (string $tipo, $needles) use ($catalogosPorTipo, $matchesAnyText) {
        return ($catalogosPorTipo[$tipo] ?? collect())->contains(
            fn ($name) => $matchesAnyText($name, $needles)
        );
    };
    $hasSeparatedPlatformCatalogs = ($catalogosPorTipo['plataforma_teledocencia'] ?? collect())->isNotEmpty()
        || ($catalogosPorTipo['plataforma_campus_virtual'] ?? collect())->isNotEmpty();
    $platformText = (string) (($lugar?->descripcion_plataformas ?: $lugar?->direccion) ?? '');
    $markPlatform = function (string $tipo, string $needle) use ($hasCatalog, $hasText, $hasSeparatedPlatformCatalogs, $platformText) {
        if ($hasCatalog($tipo, $needle)) {
            return 'X';
        }

        if (! $hasSeparatedPlatformCatalogs && ($hasCatalog('plataforma', $needle) || $hasText($platformText, $needle))) {
            return 'X';
        }

        return '';
    };
    $catalogText = fn (string $tipo) => ($catalogosPorTipo[$tipo] ?? collect())->implode(', ');
    $markCatalog = fn (string $tipo, $needles) => $hasCatalog($tipo, $needles) ? 'X' : '';
    $markText = fn ($value, string $needle) => $hasText($value, $needle) ? 'X' : '';
    $rows = function ($collection, int $min) {
        $items = collect($collection)->values();
        while ($items->count() < $min) {
            $items->push(null);
        }
        return $items;
    };
    $memberName = fn ($member) => $member ? ($member->nombre_completo ?: ($member->empleado?->nombre_completo ?? '')) : '';
    $signatureName = function ($needles, string $fallback = '') use ($accion, $matchesAnyText): string {
        $firma = $accion->firmas->first(
            fn ($item) => $matchesAnyText($item->rol_firma ?? '', $needles)
        );

        return $firma?->nombre_firmante ?: ($firma?->empleado?->nombre_completo ?? $fallback);
    };
    $firmaCoordinadorAccion = $signatureName(
        ['Coordinador de la acción por la UNAH', 'Coordinador de la accion por la UNAH'],
        $memberName($coordinador)
    );
    $firmaJefeUnidad = $signatureName('Jefe de la Unidad Académica que lidera la acción');
    $firmaComiteLocal = $signatureName(['Coordinador(a) del Comité Local', 'Coordinador del Comité Local']);
    $firmaDecanoDirector = $signatureName('Decano(a) o Director(a) del Centro Regional');
    $participacion = function (string $tipo, string $campo = 'cantidad') use ($participacionPorTipo) {
        $item = $participacionPorTipo->get($tipo);
        if (! $item) {
            return '';
        }
        if ($campo === 'hombres' && preg_match('/Hombres:\s*(\d+)/', (string) $item->descripcion, $match)) {
            return $match[1];
        }
        if ($campo === 'mujeres' && preg_match('/Mujeres:\s*(\d+)/', (string) $item->descripcion, $match)) {
            return $match[1];
        }
        return $item->{$campo} ?? '';
    };
    $detallePresupuesto = function ($presupuesto, $needles) use ($matchesAnyText) {
        return collect($presupuesto?->detalles ?? [])->first(
            fn ($detalle) => $matchesAnyText($detalle->rubro ?? '', $needles)
        );
    };
    $solicitud = $accion->fecha_solicitud ?? $accion->created_at;
    $modalidadTexto = $lugar?->modalidad_ejecucion ?: ($accion->modalidad?->nombre ?? '');
    $modalidadNormalizada = $normalize($modalidadTexto);
    $markModalidad = function (string $needle) use ($modalidadNormalizada) {
        if ($needle === 'semi') {
            return str_contains($modalidadNormalizada, 'semi') ? 'X' : '';
        }
        if ($needle === 'teledocencia') {
            return str_contains($modalidadNormalizada, 'teledocencia') || str_contains($modalidadNormalizada, 'sincronico') ? 'X' : '';
        }
        if ($needle === 'virtual') {
            return (str_contains($modalidadNormalizada, '100') || str_contains($modalidadNormalizada, 'virtual'))
                && ! str_contains($modalidadNormalizada, 'semi')
                && ! str_contains($modalidadNormalizada, 'teledocencia')
                && ! str_contains($modalidadNormalizada, 'sincronico')
                    ? 'X'
                    : '';
        }
        if ($needle === 'presencial') {
            return str_contains($modalidadNormalizada, 'presencial') && ! str_contains($modalidadNormalizada, 'semi') ? 'X' : '';
        }

        return '';
    };
    $assetUrl = fn (string $path) => $isPdf ? 'file://'.public_path($path) : asset($path);
    $headerUrl = $assetUrl('images/enf/form-018-header.png');
    $watermarkUrl = $assetUrl('images/enf/form-018-watermark.png');
    $footerUrl = $assetUrl('images/enf/form-018-footer.png');
    $shellClass = $isPdf ? 'is-pdf' : 'screen-document';

    $tipoAccionOpciones = [
        ['label' => 'Proyecto de educación continua', 'needles' => ['Proyecto de educacion continua', 'Programa de educacion continua']],
        ['label' => 'Diplomado', 'needles' => 'Diplomado', 'hint' => '(80 a 250 horas máximo)'],
        ['label' => 'Congreso', 'needles' => 'Congreso', 'hint' => '(2 a 5 días consecutivos, mínimo 6 horas por día)'],
        ['label' => 'Seminario', 'needles' => 'Seminario', 'hint' => '(5 a 29 horas máximo)'],
    ];
    $modalidadOpciones = [
        ['label' => 'Presencial', 'needle' => 'presencial'],
        ['label' => 'Semi presencial (Virtual + presencial)', 'needle' => 'semi'],
        ['label' => '100% virtual', 'needle' => 'virtual'],
        ['label' => 'Virtual sincrónico (teledocencia)', 'needle' => 'teledocencia'],
    ];
    $perfilOpciones = ['Egresados UNAH', 'Funcionarios publicos', 'Estudiantes universitarios', 'Empresa privada de servicios', 'Sociedad civil', 'Lideres comunitarios', 'ONG', 'Profesionales universitarios otros IES', 'Sector productivo', 'Academicos'];
    $edadOpciones = ['14-18', '19-25', '26-40', '41-55', '56-70', 'Mayores de 70'];
    $condicionOpciones = ['Mestizos', 'Grupos etnicos', 'Poblacion vulnerable', 'Personas con discapacidad', 'Desplazados por violencia', 'Otro'];
    $plataformasTeledocencia = ['Teams', 'Zoom', 'Meet', 'Webex', 'Otro'];
    $plataformasCampus = ['Campus Virtual UNAH', 'Moodle', 'Classroom Google', 'Teams', 'Otro'];
    $antecedentesOpciones = ['Iniciativa de la unidad academica', 'Solicitud externa privada', 'Solicitud de Secretaria de Estado', 'Solicitud de gobierno local', 'Alianza con otras universidades', 'Solicitud de ONG', 'Solicitud de patronatos', 'Solicitud de sector financiero', 'Solicitud de sector productivo', 'Otros'];
    $contraparteOpciones = ['Secretaria de Estado', 'Gobierno Municipal', 'Sector productivo', 'Entidades financieras', 'Sector privado de servicios', 'Organizaciones gremiales', 'Sociedad civil organizada', 'Sector academico', 'Organismos internacionales', 'Unidad de la UNAH'];
    $inciso = fn (int $index) => chr(97 + $index).') ';
    $ingresoRubrosForm018 = [
        ['label' => 'Cuotas de inscripción', 'needles' => ['Cuotas de inscripción']],
        ['label' => 'Mensualidades / módulos', 'needles' => ['Mensualidades / módulos']],
        ['label' => 'Gestión de becas (donaciones)', 'needles' => ['Gestión de becas', 'becas']],
        ['label' => 'Otros', 'needles' => ['Otros']],
    ];
    $egresoRubrosForm018 = [
        ['label' => 'Pago de personal docente', 'needles' => ['Pago de personal docente']],
        ['label' => 'Gastos de materiales y suministros', 'needles' => ['Gastos de materiales y suministros', 'Materiales y suministros']],
        ['label' => 'Gastos de movilización (transporte, pasajes)', 'needles' => ['Gastos de movilización', 'Movilización']],
        ['label' => 'Gastos de manutención y hospedaje', 'needles' => ['Gastos de manutención y hospedaje', 'Manutención y hospedaje']],
        ['label' => 'Costos administrativos / Financieros', 'needles' => ['Costos administrativos', 'Financieros']],
        ['label' => 'Otros gastos', 'needles' => ['Otros gastos']],
    ];
    $aporteUnahRubrosForm018 = [
        ['label' => 'Horas de participación del personal docente del equipo ejecutor de la acción', 'needles' => ['Horas de participación del personal docente del equipo ejecutor de la acción']],
        ['label' => 'Horas de participación estudiantes', 'needles' => ['Horas de participación estudiantes']],
        ['label' => 'Costos indirectos depreciación de equipo (3% de la suma de los incisos a) y b) anteriores)', 'needles' => ['Costos indirectos depreciación de equipo']],
        ['label' => 'Costos indirectos servicios públicos ((3% de la suma de los incisos a) y b) anteriores))', 'needles' => ['Costos indirectos servicios públicos']],
    ];
    $objetivosEspecificosTexto = $accion->objetivosEspecificos
        ->sortBy('orden')
        ->values()
        ->map(fn ($objetivo, $index) => ($index + 1).'. '.trim((string) $objetivo->descripcion))
        ->implode("\n");
    $alineamientoTexto = trim((string) $accion->alineamiento_reforma.' '.($accion->ejesUnah->isNotEmpty() ? '| Ejes: '.$accion->ejesUnah->pluck('nombre')->implode(', ') : ''));
    $scrollField = function ($value, string $heightClass) use ($isPdf): HtmlString {
        $classes = 'form018-scroll-field '.$heightClass;
        $text = e((string) $value);

        if ($isPdf) {
            return new HtmlString('<div class="'.$classes.' form018-pdf-field">'.$text.'</div>');
        }

        return new HtmlString('<textarea class="'.$classes.'" data-form018-autosize readonly>'.$text.'</textarea>');
    };
    $resultadoParts = function ($resultado) {
        if (! $resultado) {
            return ['', ''];
        }

        [$tipo, $descripcion] = str((string) $resultado->resultado)->explode(': ', 2)->pad(2, null)->all();

        return $descripcion === null
            ? ['', trim((string) $tipo)]
            : [trim((string) $tipo), trim((string) $descripcion)];
    };
    $resultadoTipoKey = function ($resultado) use ($resultadoParts, $normalize): string {
        [$tipo] = $resultadoParts($resultado);
        $tipo = $normalize($tipo);

        if (str_contains($tipo, 'mediano')) {
            return 'mediano';
        }

        if (str_contains($tipo, 'largo') || str_contains($tipo, 'impacto')) {
            return 'largo';
        }

        return 'corto';
    };
    $objetivosPorId = $accion->objetivosEspecificos->keyBy('id');
    $resultadosPorPlazo = $accion->resultados
        ->sortBy('orden')
        ->groupBy(fn ($resultado) => $resultadoTipoKey($resultado));
    $odsSortKey = function ($ods): int {
        preg_match('/^\s*(\d+)/', (string) ($ods?->nombre ?? ''), $matches);

        return (int) ($matches[1] ?? $ods?->id ?? 0);
    };
    $metaSortKey = function ($meta): string {
        $numbers = collect(preg_split('/\D+/', (string) ($meta?->numero_meta ?? '')))
            ->filter(fn ($part) => $part !== '')
            ->map(fn ($part) => (int) $part)
            ->values();

        return sprintf(
            '%02d.%02d.%02d.%06d',
            $numbers->get(0, (int) ($meta?->ods_id ?? 0)),
            $numbers->get(1, 0),
            $numbers->get(2, 0),
            (int) ($meta?->id ?? 0)
        );
    };
    $odsSeleccionados = $accion->ods
        ->filter(fn ($ods) => filled($ods?->id))
        ->unique('id')
        ->sortBy($odsSortKey)
        ->values();
    $metasSeleccionadas = $accion->metasContribuye
        ->filter(fn ($meta) => filled($meta?->id))
        ->unique('id')
        ->sortBy($metaSortKey)
        ->values();
    $metasPorOds = $metasSeleccionadas->groupBy('ods_id');
@endphp

<style>
    html,
    body {
        margin: 0;
        padding: 0;
    }

    .form018-shell {
        --form-blue: #002060;
        --form-gray: #d9d9d9;
        --form-line: #bfbfbf;
        --form-yellow: #ffc000;
        --form018-screen-scale: 1;
        color: #000;
        container-type: inline-size;
        font-family: Arial, Helvetica, sans-serif;
        line-height: 1.08;
        overflow-x: hidden;
        width: 100%;
    }

    .form018-shell * {
        box-sizing: border-box;
    }

    .form018-shell.screen-document {
        display: grid;
        gap: 18px;
        justify-items: center;
    }

    .form018-page {
        position: relative;
        width: 8.5in;
        max-width: none;
        min-height: 11in;
        overflow: hidden;
        background: #fff;
        page-break-after: auto;
        page-break-before: auto;
        page-break-inside: avoid;
        transform-origin: top center;
    }

    .form018-page + .form018-page {
        page-break-before: always;
    }

    .form018-shell.is-pdf .form018-page {
        overflow: visible;
        page-break-inside: auto;
    }

    .form018-shell.screen-document .form018-page {
        box-shadow: 0 10px 30px rgba(15, 23, 42, .14);
        zoom: var(--form018-screen-scale);
    }

    .form018-shell.screen-document .form018-auto-row {
        height: auto !important;
    }

    .form018-page:last-child {
        page-break-after: auto;
    }

    .form018-header {
        position: relative;
        z-index: 2;
        height: 1.3in;
        padding: .18in .34in 0 .32in;
    }

    .form018-header img {
        display: block;
        position: absolute;
        top: .18in;
        left: .32in;
        width: 5.55in;
        height: auto;
    }

    .form018-contact {
        position: absolute;
        top: .3in;
        right: .55in;
        width: 2in;
        color: #002060;
        font-size: 7px;
        font-weight: 700;
        line-height: 1.22;
        text-align: right;
        white-space: nowrap;
    }

    .form018-yellow-strip {
        position: absolute;
        top: .11in;
        right: .08in;
        width: .08in;
        height: .64in;
        background: #ffc000;
    }

    .form018-watermark {
        position: absolute;
        z-index: 0;
        top: 4.2in;
        right: -.52in;
        width: 4.6in;
        opacity: .24;
        pointer-events: none;
    }

    .form018-footer {
        position: absolute;
        z-index: 2;
        left: .85in;
        bottom: .27in;
        width: 5.2in;
        height: auto;
    }

    .form018-shell.is-pdf .form018-footer {
        display: none !important;
    }

    .form018-main {
        position: relative;
        z-index: 1;
        width: 6.5in;
        margin: 0 auto;
    }

    .form018-title-block {
        margin-top: .04in;
        text-align: center;
        text-transform: uppercase;
    }

    .form018-code-bar {
        width: 4.95in;
        height: .17in;
        margin-left: auto;
        padding: 1px 4px 0 0;
        background: #002060;
        color: #fff;
        font-size: 11px;
        font-weight: 700;
        line-height: .16in;
        text-align: right;
    }

    .form018-title {
        margin: .08in 0 .16in;
        font-size: 12px;
        font-weight: 700;
        line-height: 1.3;
    }

    .form018-section {
        display: flex;
        gap: .22in;
        margin: .08in 0 .32in .15in;
        color: #002060;
        font-size: 10.4px;
        font-weight: 700;
        text-transform: uppercase;
    }

    .form018-section.tight {
        margin-bottom: .08in;
    }

    .form018-table {
        width: 100%;
        border-collapse: collapse;
        table-layout: fixed;
        font-size: 9.2px;
    }

    .form018-table + .form018-table,
    .form018-block + .form018-table,
    .form018-table + .form018-block {
        margin-top: 0;
    }

    .form018-table th,
    .form018-table td {
        min-height: 27px;
        border: 1px solid #bfbfbf;
        padding: 3px 4px;
        vertical-align: middle;
        overflow-wrap: anywhere;
        word-break: break-word;
    }

    .form018-table th {
        font-weight: 700;
        text-align: center;
    }

    .form018-blue {
        background: #002060;
        color: #fff;
        font-weight: 700;
    }

    .form018-gray {
        background: #d9d9d9;
        color: #000;
        font-weight: 700;
    }

    .form018-light {
        background: #f2f2f2;
        color: #000;
        font-weight: 700;
    }

    .form018-docx-wide {
        width: 6.89in;
        margin-left: -.2in;
    }

    .form018-docx-beneficiarios {
        width: 6.62in;
        margin-left: -.06in;
    }

    .form018-docx-table4 {
        width: 6.61in;
        margin-left: -.06in;
    }

    .form018-docx-table5,
    .form018-docx-table6,
    .form018-docx-table7,
    .form018-docx-table8 {
        width: 6.61in;
        margin-left: -.06in;
    }

    .form018-docx-table9 {
        width: 7.8in;
        margin-left: -.65in;
    }

    .form018-docx-signatures {
        width: 6.7in;
        margin-left: -.1in;
    }

    .form018-docx-documents {
        width: 6.49in;
    }

    .form018-file-actions {
        display: flex;
        flex-wrap: wrap;
        justify-content: center;
        gap: 3px;
    }

    .form018-file-button {
        display: inline-block;
        border: 1px solid var(--form-blue);
        border-radius: 2px;
        background: #fff;
        color: var(--form-blue);
        font-size: 8px;
        font-weight: 700;
        line-height: 1;
        padding: 3px 5px;
        text-decoration: none;
    }

    .form018-file-button:hover {
        background: #eef4ff;
    }

    .form018-docx-table5 tr {
        height: 22px !important;
    }

    .form018-docx-table4 th,
    .form018-docx-table4 td,
    .form018-docx-table5 th,
    .form018-docx-table5 td,
    .form018-docx-table6 th,
    .form018-docx-table6 td,
    .form018-docx-table7 th,
    .form018-docx-table7 td,
    .form018-docx-table8 th,
    .form018-docx-table8 td,
    .form018-docx-table9 th,
    .form018-docx-table9 td,
    .form018-docx-signatures th,
    .form018-docx-signatures td,
    .form018-docx-documents th,
    .form018-docx-documents td {
        font-size: inherit;
        line-height: 1;
        min-height: 0;
        padding: 1px 3px;
    }

    .form018-docx-table5 th,
    .form018-docx-table5 td {
        line-height: .98;
    }

    .form018-docx-table5 .form018-small {
        font-size: inherit;
        line-height: inherit;
    }

    .form018-scroll-cell {
        padding: 0 !important;
        vertical-align: top !important;
    }

    .form018-scroll-field {
        display: block;
        width: 100%;
        min-width: 0;
        margin: 0;
        border: 0;
        outline: 0;
        background: transparent;
        color: inherit;
        font-family: Arial, Helvetica, sans-serif;
        font-size: 11px;
        line-height: 1.2;
        overflow: auto;
        overflow-x: hidden;
        padding: 3px 5px;
        resize: vertical;
        white-space: pre-wrap;
        word-break: break-word;
    }

    .form018-scroll-field:focus {
        box-shadow: inset 0 0 0 1px #94a3b8;
    }

    .form018-pdf-field {
        overflow: visible;
        page-break-inside: auto;
        resize: none;
        white-space: pre-wrap;
    }

    .form018-shell.is-pdf .form018-table,
    .form018-shell.is-pdf .form018-table tbody,
    .form018-shell.is-pdf .form018-table tr,
    .form018-shell.is-pdf .form018-table td,
    .form018-shell.is-pdf .form018-scroll-cell {
        page-break-inside: auto;
    }

    .form018-shell.is-pdf .form018-auto-row {
        height: auto !important;
        page-break-inside: auto;
    }

    .form018-shell.is-pdf .form018-scroll-field {
        height: auto !important;
        max-height: none !important;
        min-height: .16in;
        overflow: visible;
        font-size: 10px;
        line-height: 1.18;
        padding: 3px 5px;
    }

    .form018-scroll-resumen {
        height: 108px;
        min-height: 42px;
        max-height: 320px;
    }

    .form018-scroll-definicion {
        height: 101px;
        min-height: 42px;
        max-height: 320px;
    }

    .form018-scroll-md {
        height: 30px;
        min-height: 18px;
        max-height: 240px;
    }

    .form018-scroll-sm {
        height: 22px;
        min-height: 16px;
        max-height: 160px;
    }

    .form018-scroll-contraparte {
        height: 35px;
        min-height: 24px;
        max-height: 260px;
    }

    .form018-scroll-xs {
        height: 18px;
        min-height: 16px;
        max-height: 180px;
    }

    .form018-docx-table4 .form018-mark,
    .form018-docx-table5 .form018-mark,
    .form018-docx-table6 .form018-mark,
    .form018-docx-table7 .form018-mark,
    .form018-docx-table8 .form018-mark,
    .form018-docx-table9 .form018-mark,
    .form018-docx-documents .form018-mark {
        height: auto;
        line-height: 1.05;
    }

    .form018-numbered {
        color: #fff;
        font-weight: 700;
        text-align: left;
    }

    .form018-numbered .num {
        display: inline-block;
        width: 24px;
        padding-right: 3px;
        text-align: left;
    }

    .form018-center {
        text-align: center;
    }

    .form018-right {
        text-align: right;
    }

    .form018-small {
        font-size: 8px;
        line-height: 1.05;
    }

    .form018-tiny {
        font-size: 7.3px;
        line-height: 1.04;
    }

    .form018-mark {
        height: 23px;
        font-size: 11px;
        font-weight: 700;
        text-align: center;
    }

    .form018-fill {
        min-height: 23px;
    }

    .form018-textarea {
        min-height: .58in;
        line-height: 1.18;
    }

    .form018-large-area {
        min-height: .9in;
        line-height: 1.18;
    }

    .form018-signature {
        height: .48in;
        vertical-align: bottom !important;
    }

    .form018-note {
        margin: .06in 0 0;
        font-size: 7.5px;
    }

    .form018-page .page-start {
        margin-top: .08in;
    }

    @media print {
        @page {
            size: letter;
            margin: 0;
        }

        .form018-shell.screen-document {
            display: block;
        }

        .form018-shell.screen-document .form018-page {
            box-shadow: none;
            zoom: 1 !important;
        }
    }
</style>

<div class="form018-shell {{ $shellClass }}">
    <section class="form018-page">
        <header class="form018-header">
            <img src="{{ $headerUrl }}" alt="UNAH VRA Dirección de Vinculación Universidad Sociedad">
            <div class="form018-contact">vinculacion.sociedad@unah.edu.hn<br>Tel. 2216-7070&nbsp; Ext. 110576</div>
            <div class="form018-yellow-strip"></div>
        </header>
        <img class="form018-watermark" src="{{ $watermarkUrl }}" alt="">
        <img class="form018-footer" src="{{ $footerUrl }}" alt="">

        <main class="form018-main">
            <div class="form018-title-block">
                <div class="form018-code-bar">FORM-DVUS-018</div>
                <div class="form018-title">FORMULARIO DE REGISTRO DE PROYECTOS<br>DE EDUCACIÓN NO FORMAL</div>
            </div>

            <div class="form018-section">
                <span>I.</span>
                <span>INFORMACIÓN GENERAL DE LA ACCIÓN</span>
            </div>

            <table class="form018-table form018-docx-wide">
                <colgroup>
                    @foreach ([1809, 133, 155, 935, 312, 349, 319, 319, 200, 36, 1143, 7, 65, 149, 52, 573, 359, 286, 554, 192, 290, 264, 1421] as $width)
                        <col style="width: {{ $width / 9922 * 100 }}%;">
                    @endforeach
                </colgroup>
                <tr>
                    <td class="form018-blue form018-numbered" rowspan="2" colspan="2"><span class="num">1.</span>Fecha de solicitud de registro</td>
                    <td class="form018-blue form018-center" colspan="8">Año</td>
                    <td class="form018-blue form018-center" colspan="8">Mes</td>
                    <td class="form018-blue form018-center" colspan="5">Día</td>
                </tr>
                <tr>
                    <td class="form018-center form018-fill" colspan="8">{{ $solicitud?->format('Y') }}</td>
                    <td class="form018-center" colspan="8">{{ $solicitud?->format('m') }}</td>
                    <td class="form018-center" colspan="5">{{ $solicitud?->format('d') }}</td>
                </tr>
                <tr style="height: 56.7px;">
                    <td class="form018-blue form018-numbered" colspan="2"><span class="num">2.</span>Nombre de la acción</td>
                    <td colspan="21">{{ $accion->nombre_accion }}</td>
                </tr>
                <tr style="height: 30.3px;">
                    <td class="form018-blue form018-numbered" rowspan="4" colspan="2"><span class="num">3.</span>Tipo de acción de Educación No Formal<br>(marcar)</td>
                    <td class="form018-blue form018-center" colspan="12">{{ $tipoAccionOpciones[0]['label'] }}</td>
                    <td class="form018-blue form018-center" colspan="9">{{ $tipoAccionOpciones[1]['label'] }}<br><span class="form018-small">{{ $tipoAccionOpciones[1]['hint'] }}</span></td>
                </tr>
                <tr style="height: 30.3px;">
                    <td class="form018-mark" colspan="12">{{ $markCatalog('tipo_accion_enf', $tipoAccionOpciones[0]['needles']) }}</td>
                    <td class="form018-mark" colspan="9">{{ $markCatalog('tipo_accion_enf', $tipoAccionOpciones[1]['needles']) }}</td>
                </tr>
                <tr style="height: 30.3px;">
                    <td class="form018-blue form018-center form018-small" colspan="12">{{ $tipoAccionOpciones[2]['label'] }}<br>{{ $tipoAccionOpciones[2]['hint'] }}</td>
                    <td class="form018-blue form018-center form018-small" colspan="9">{{ $tipoAccionOpciones[3]['label'] }}<br>{{ $tipoAccionOpciones[3]['hint'] }}</td>
                </tr>
                <tr style="height: 33.3px;">
                    <td class="form018-mark" colspan="12">{{ $markCatalog('tipo_accion_enf', $tipoAccionOpciones[2]['needles']) }}</td>
                    <td class="form018-mark" colspan="9">{{ $markCatalog('tipo_accion_enf', $tipoAccionOpciones[3]['needles']) }}</td>
                </tr>
                <tr>
                    <td class="form018-blue form018-numbered" rowspan="3" colspan="2"><span class="num">4.</span>Resolución de la VRA</td>
                    <td class="form018-gray" colspan="21">En caso de los diplomados, indicar el número de resolución de aprobación del programa de formación</td>
                </tr>
                <tr>
                    <td class="form018-blue" colspan="11">No de resolución programa original</td>
                    <td class="form018-blue" colspan="10">No de resolución última actualización</td>
                </tr>
                <tr>
                    <td colspan="11">{{ $blank($accion->resolucion_original ?: $accion->resolucion_vra) }}</td>
                    <td colspan="10">{{ $blank($accion->resolucion_actualizacion) }}</td>
                </tr>
                <tr style="height: 27.6px;">
                    <td class="form018-blue form018-numbered" rowspan="3" colspan="2"><span class="num">5.</span>Unidad(s) Académica(s)</td>
                    <td class="form018-light" colspan="7">Facultad /Centro Universitario Regional/Instituto Tecnológico</td>
                    <td colspan="14">{{ $accion->centroFacultad?->nombre ?? $accion->unidad_academica_responsable_texto }}</td>
                </tr>
                <tr style="height: 27.6px;">
                    <td class="form018-light" colspan="7">Escuela, Departamento Académico, Técnicos Universitarios, Instituto de Investigación, Observatorio, Consultorio, otros</td>
                    <td colspan="14">{{ $accion->departamentoAcademico?->nombre ?? $accion->escuela_departamento_texto }}</td>
                </tr>
                <tr style="height: 27.6px;">
                    <td class="form018-light" colspan="7">Carrera</td>
                    <td colspan="14">{{ $accion->carrera?->nombre }}</td>
                </tr>
                <tr style="height: 27.7px;">
                    <td class="form018-blue form018-numbered" colspan="9"><span class="num">6.</span>Número de edición de proceso ENF:</td>
                    <td colspan="14">{{ $blank($accion->numero_edicion) }}</td>
                </tr>
                <tr style="height: 20.6px;">
                    <td class="form018-blue form018-numbered" rowspan="3" colspan="2"><span class="num">7.</span>Fecha de ejecución</td>
                    <td class="form018-blue form018-center" colspan="9">Fecha de inicio</td>
                    <td class="form018-blue form018-center" colspan="12">Fecha de finalización</td>
                </tr>
                <tr style="height: 20.6px;">
                    <td class="form018-blue form018-center" colspan="2">Día</td>
                    <td class="form018-blue form018-center" colspan="4">Mes</td>
                    <td class="form018-blue form018-center" colspan="3">Año</td>
                    <td class="form018-blue form018-center" colspan="6">Día</td>
                    <td class="form018-blue form018-center" colspan="5">Mes</td>
                    <td class="form018-blue form018-center">Año</td>
                </tr>
                <tr style="height: 20.6px;">
                    <td class="form018-center" colspan="2">{{ $accion->fecha_inicio?->format('d') }}</td>
                    <td class="form018-center" colspan="4">{{ $accion->fecha_inicio?->format('m') }}</td>
                    <td class="form018-center" colspan="3">{{ $accion->fecha_inicio?->format('Y') }}</td>
                    <td class="form018-center" colspan="6">{{ $accion->fecha_finalizacion?->format('d') }}</td>
                    <td class="form018-center" colspan="5">{{ $accion->fecha_finalizacion?->format('m') }}</td>
                    <td class="form018-center">{{ $accion->fecha_finalizacion?->format('Y') }}</td>
                </tr>
            </table>
        </main>
    </section>

    <section class="form018-page">
        <header class="form018-header">
            <img src="{{ $headerUrl }}" alt="UNAH VRA Dirección de Vinculación Universidad Sociedad">
            <div class="form018-contact">vinculacion.sociedad@unah.edu.hn<br>Tel. 2216-7070&nbsp; Ext. 110576</div>
            <div class="form018-yellow-strip"></div>
        </header>
        <img class="form018-watermark" src="{{ $watermarkUrl }}" alt="">
        <img class="form018-footer" src="{{ $footerUrl }}" alt="">

        <main class="form018-main page-start">
            <table class="form018-table form018-docx-wide">
                <colgroup>
                    @foreach ([1809, 133, 155, 935, 312, 349, 319, 319, 200, 36, 1143, 7, 65, 149, 52, 573, 359, 286, 554, 192, 290, 264, 1421] as $width)
                        <col style="width: {{ $width / 9922 * 100 }}%;">
                    @endforeach
                </colgroup>
                <tr style="height: 26.2px;">
                    <td class="form018-blue form018-numbered" rowspan="2" colspan="2"><span class="num">8.</span>Modalidad de ejecución</td>
                    <td class="form018-blue form018-center form018-small" colspan="4">Presencial</td>
                    <td class="form018-blue form018-center form018-small" colspan="6">Semi presencial (Virtual + presencial)</td>
                    <td class="form018-blue form018-center form018-small" colspan="7">100% virtual</td>
                    <td class="form018-blue form018-center form018-small" colspan="4">Virtual sincrónico (teledocencia)</td>
                </tr>
                <tr style="height: 26.2px;">
                    <td class="form018-mark" colspan="4">{{ $markModalidad('presencial') }}</td>
                    <td class="form018-mark" colspan="6">{{ $markModalidad('semi') }}</td>
                    <td class="form018-mark" colspan="7">{{ $markModalidad('virtual') }}</td>
                    <td class="form018-mark" colspan="4">{{ $markModalidad('teledocencia') }}</td>
                </tr>
                <tr style="height: 26.2px;">
                    <td class="form018-blue form018-numbered" rowspan="2" colspan="2"><span class="num">9.</span>Duración</td>
                    <td class="form018-gray form018-center" colspan="7">Horas Teóricas</td>
                    <td class="form018-gray form018-center" colspan="9">Horas Prácticas</td>
                    <td class="form018-gray form018-center" colspan="5">Total Horas</td>
                </tr>
                <tr style="height: 26.2px;">
                    <td class="form018-center" colspan="7">{{ $blank($accion->horas_teoricas) }}</td>
                    <td class="form018-center" colspan="9">{{ $blank($accion->horas_practicas) }}</td>
                    <td class="form018-center" colspan="5">{{ $blank($accion->total_horas) }}</td>
                </tr>
                <tr style="height: 26.2px;">
                    <td class="form018-blue form018-numbered" rowspan="3" colspan="2"><span class="num">10.</span>Lugar de realización actividades presenciales</td>
                    <td colspan="7"><strong>Campus</strong></td>
                    <td colspan="14">{{ $lugar?->campus?->nombre_campus }}</td>
                </tr>
                <tr style="height: 26.2px;">
                    <td colspan="7"><strong>Aula / Auditorio</strong></td>
                    <td colspan="14">{{ $lugar?->aula }}</td>
                </tr>
                <tr style="height: 26.2px;">
                    <td colspan="7"><strong>Edificio</strong></td>
                    <td colspan="14">{{ $lugar?->edificio }}</td>
                </tr>
                <tr style="height: 15.2px;">
                    <td class="form018-blue form018-numbered" colspan="23"><span class="num">11.</span>Descripción de las plataformas que se utilizarán para la modalidad virtual y tele docencia (en los casos que aplique): {{ $lugar?->descripcion_plataformas ?: $lugar?->direccion }}</td>
                </tr>
                <tr style="height: 15.2px;">
                    <td class="form018-blue form018-center" rowspan="2">Teledocencia</td>
                    <td class="form018-blue form018-center" colspan="4">Teams</td>
                    <td class="form018-blue form018-center" colspan="4">Zoom</td>
                    <td class="form018-blue form018-center" colspan="7">Meet</td>
                    <td class="form018-blue form018-center" colspan="5">Webex</td>
                    <td class="form018-blue form018-center" colspan="2">Otro</td>
                </tr>
                <tr style="height: 15.2px;">
                    <td class="form018-mark" colspan="4">{{ $markPlatform('plataforma_teledocencia', 'Teams') }}</td>
                    <td class="form018-mark" colspan="4">{{ $markPlatform('plataforma_teledocencia', 'Zoom') }}</td>
                    <td class="form018-mark" colspan="7">{{ $markPlatform('plataforma_teledocencia', 'Meet') }}</td>
                    <td class="form018-mark" colspan="5">{{ $markPlatform('plataforma_teledocencia', 'Webex') }}</td>
                    <td class="form018-mark" colspan="2">{{ $markPlatform('plataforma_teledocencia', 'Otro') }}</td>
                </tr>
                <tr style="height: 15.2px;">
                    <td class="form018-blue form018-center" rowspan="2">Campus virtual</td>
                    <td class="form018-blue form018-center" colspan="4">Campus virtual UNAH</td>
                    <td class="form018-blue form018-center" colspan="4">Moodle</td>
                    <td class="form018-blue form018-center" colspan="7">Classroom Google</td>
                    <td class="form018-blue form018-center" colspan="5">Teams</td>
                    <td class="form018-blue form018-center" colspan="2">Otro</td>
                </tr>
                <tr style="height: 15.2px;">
                    <td class="form018-mark" colspan="4">{{ $markPlatform('plataforma_campus_virtual', 'Campus Virtual UNAH') }}</td>
                    <td class="form018-mark" colspan="4">{{ $markPlatform('plataforma_campus_virtual', 'Moodle') }}</td>
                    <td class="form018-mark" colspan="7">{{ $markPlatform('plataforma_campus_virtual', 'Classroom Google') }}</td>
                    <td class="form018-mark" colspan="5">{{ $markPlatform('plataforma_campus_virtual', 'Teams') }}</td>
                    <td class="form018-mark" colspan="2">{{ $markPlatform('plataforma_campus_virtual', 'Otro') }}</td>
                </tr>
                <tr style="height: 15.2px;">
                    <td class="form018-blue form018-numbered" colspan="23"><span class="num">12.</span>Antecedentes de la acción. Indicar el origen para el diseño y puesta en marcha de la acción del programa de formación</td>
                </tr>
                <tr style="height: 15.2px;">
                    <td class="form018-blue form018-center form018-small" colspan="3">Iniciativa de la unidad académica</td>
                    <td class="form018-blue form018-center form018-small" colspan="4">Solicitud externa privada</td>
                    <td class="form018-blue form018-center form018-small" colspan="8">Solicitud de Secretaría de Estado</td>
                    <td class="form018-blue form018-center form018-small" colspan="5">Solicitud de gobierno local</td>
                    <td class="form018-blue form018-center form018-small" colspan="3">Alianza con otras universidades</td>
                </tr>
                <tr style="height: 15.2px;">
                    <td class="form018-mark" colspan="3">{{ $markCatalog('antecedente', 'Iniciativa de la unidad academica') }}</td>
                    <td class="form018-mark" colspan="4">{{ $markCatalog('antecedente', 'Solicitud externa privada') }}</td>
                    <td class="form018-mark" colspan="8">{{ $markCatalog('antecedente', 'Solicitud de Secretaria de Estado') }}</td>
                    <td class="form018-mark" colspan="5">{{ $markCatalog('antecedente', 'Solicitud de gobierno local') }}</td>
                    <td class="form018-mark" colspan="3">{{ $markCatalog('antecedente', 'Alianza con otras universidades') }}</td>
                </tr>
                <tr style="height: 15.2px;">
                    <td class="form018-blue form018-center form018-small" colspan="3">Solicitud de ONG</td>
                    <td class="form018-blue form018-center form018-small" colspan="4">Solicitud de patronatos</td>
                    <td class="form018-blue form018-center form018-small" colspan="8">Solicitud de sector financiero</td>
                    <td class="form018-blue form018-center form018-small" colspan="5">Solicitud de sector productivo</td>
                    <td class="form018-blue form018-center form018-small" colspan="3">Otros</td>
                </tr>
                <tr style="height: 15.2px;">
                    <td class="form018-mark" colspan="3">{{ $markCatalog('antecedente', 'Solicitud de ONG') }}</td>
                    <td class="form018-mark" colspan="4">{{ $markCatalog('antecedente', 'Solicitud de patronatos') }}</td>
                    <td class="form018-mark" colspan="8">{{ $markCatalog('antecedente', 'Solicitud de sector financiero') }}</td>
                    <td class="form018-mark" colspan="5">{{ $markCatalog('antecedente', 'Solicitud de sector productivo') }}</td>
                    <td class="form018-mark" colspan="3">{{ $markCatalog('antecedente', 'Otros') }}</td>
                </tr>
            </table>

            <div class="form018-section tight">
                <span>II.</span>
                <span>PERFIL DE LOS BENEFICIARIOS (PARTICIPANTES)</span>
            </div>
            <table class="form018-table form018-docx-beneficiarios">
                <colgroup>
                    @foreach ([1805, 1918, 1922, 1949, 1939] as $width)
                        <col style="width: {{ $width / 9533 * 100 }}%;">
                    @endforeach
                </colgroup>
                <tr style="height: 30.3px;">
                    <td class="form018-blue form018-numbered" colspan="5"><span class="num">13.</span>Perfil de los principales participantes al que está orientado el programa de formación (Marcar con una “x” todos los que correspondan)</td>
                </tr>
                <tr style="height: 15.2px;">
                    @foreach (array_slice($perfilOpciones, 0, 5) as $option)
                        <td class="form018-blue form018-center form018-small">{{ $option }}</td>
                    @endforeach
                </tr>
                <tr style="height: 15.2px;">
                    @foreach (array_slice($perfilOpciones, 0, 5) as $option)
                        <td class="form018-mark">{{ $markCatalog('perfil_participante', $option) }}</td>
                    @endforeach
                </tr>
                <tr style="height: 15.2px;">
                    @foreach (array_slice($perfilOpciones, 5, 5) as $option)
                        <td class="form018-blue form018-center form018-small">{{ $option }}</td>
                    @endforeach
                </tr>
                <tr style="height: 15.2px;">
                    @foreach (array_slice($perfilOpciones, 5, 5) as $option)
                        <td class="form018-mark">{{ $markCatalog('perfil_participante', $option) }}</td>
                    @endforeach
                </tr>
                <tr style="height: 15.2px;">
                    <td class="form018-blue form018-numbered" colspan="2"><span class="num">14.</span>Cupos programados</td>
                    <td class="form018-blue form018-center" colspan="3">Total</td>
                </tr>
                <tr style="height: 15.2px;">
                    <td class="form018-blue" colspan="2"></td>
                    <td class="form018-center" colspan="3">{{ $beneficiarios?->total ?? '' }}</td>
                </tr>
                <tr style="height: 15.2px;">
                    <td class="form018-blue form018-small form018-numbered" rowspan="4" colspan="2"><span class="num">15.</span>Edad de los participantes deseados (Marcar con una “x” todos los que correspondan)</td>
                    <td class="form018-blue form018-center form018-small">Entre 14 - 18 años</td>
                    <td class="form018-blue form018-center form018-small">Entre 19 - 25 años</td>
                    <td class="form018-blue form018-center form018-small">Entre 26 - 40 años</td>
                </tr>
                <tr style="height: 15.2px;">
                    <td class="form018-mark">{{ $markCatalog('rango_edad', '14-18') }}</td>
                    <td class="form018-mark">{{ $markCatalog('rango_edad', '19-25') }}</td>
                    <td class="form018-mark">{{ $markCatalog('rango_edad', '26-40') }}</td>
                </tr>
                <tr style="height: 15.2px;">
                    <td class="form018-blue form018-center form018-small">Entre 41 - 55 años</td>
                    <td class="form018-blue form018-center form018-small">Entre 56 - 70 años</td>
                    <td class="form018-blue form018-center form018-small">Mayores de 70 años</td>
                </tr>
                <tr style="height: 15.2px;">
                    <td class="form018-mark">{{ $markCatalog('rango_edad', '41-55') }}</td>
                    <td class="form018-mark">{{ $markCatalog('rango_edad', '56-70') }}</td>
                    <td class="form018-mark">{{ $markCatalog('rango_edad', 'Mayores de 70') }}</td>
                </tr>
                <tr style="height: 15.2px;">
                    <td class="form018-blue form018-small form018-numbered" rowspan="4" colspan="2"><span class="num">16.</span>De acuerdo con los objetivos, indique el perfil de los participantes por condición social para quienes está dirigida la oferta de educación no formal y continua (marcar con una “x” todos los que correspondan)</td>
                    <td class="form018-blue form018-center form018-small">Mestizos</td>
                    <td class="form018-blue form018-center form018-small">Grupos étnicos</td>
                    <td class="form018-blue form018-center form018-small">Población vulnerable</td>
                </tr>
                <tr style="height: 15.2px;">
                    <td class="form018-mark">{{ $markCatalog('condicion_social', 'Mestizos') }}</td>
                    <td class="form018-mark">{{ $markCatalog('condicion_social', 'Grupos etnicos') }}</td>
                    <td class="form018-mark">{{ $markCatalog('condicion_social', 'Poblacion vulnerable') }}</td>
                </tr>
                <tr style="height: 15.2px;">
                    <td class="form018-blue form018-center form018-small">Personas con discapacidades</td>
                    <td class="form018-blue form018-center form018-small">Desplazados por violencia</td>
                    <td class="form018-blue form018-center form018-small">Otro</td>
                </tr>
                <tr style="height: 15.2px;">
                    <td class="form018-mark">{{ $markCatalog('condicion_social', 'Personas con discapacidad') }}</td>
                    <td class="form018-mark">{{ $markCatalog('condicion_social', 'Desplazados por violencia') }}</td>
                    <td class="form018-mark">{{ $markCatalog('condicion_social', 'Otro') }}</td>
                </tr>
            </table>
        </main>
    </section>

    <section class="form018-page">
        <header class="form018-header">
            <img src="{{ $headerUrl }}" alt="UNAH VRA Dirección de Vinculación Universidad Sociedad">
            <div class="form018-contact">vinculacion.sociedad@unah.edu.hn<br>Tel. 2216-7070&nbsp; Ext. 110576</div>
            <div class="form018-yellow-strip"></div>
        </header>
        <img class="form018-watermark" src="{{ $watermarkUrl }}" alt="">
        <img class="form018-footer" src="{{ $footerUrl }}" alt="">

        <main class="form018-main page-start">
            <div class="form018-section tight">
                <span>III.</span>
                <span>EQUIPO EJECUTOR DEL PROYECTO</span>
            </div>
            <table class="form018-table">
                <tr><td class="form018-blue form018-center" colspan="12">EQUIPO DE DIRECCIÓN</td></tr>
                <tr>
                    <td class="form018-blue form018-numbered" rowspan="3" colspan="3"><span class="num">17.</span>Coordinador/a de la Acción:</td>
                    <td colspan="5">Nombre Completo: {{ $memberName($coordinador) }}</td>
                    <td colspan="4">No. de empleado/a: {{ $coordinador?->numero_empleado }}</td>
                </tr>
                <tr>
                    <td colspan="5">Correo electrónico: {{ $coordinador?->correo }}</td>
                    <td colspan="4">Celular: {{ $coordinador?->celular }}</td>
                </tr>
                <tr>
                    <td colspan="5">Categoría: {{ $coordinador?->categoria }}</td>
                    <td colspan="4">Departamento al que pertenece: {{ $coordinador?->departamento }}</td>
                </tr>
                <tr>
                    <td class="form018-blue form018-numbered" rowspan="3" colspan="3"><span class="num">18.</span>Responsable de sistematización<br><span class="form018-small">(aplica congresos, programa de educación continua, diplomados)</span></td>
                    <td colspan="5">Nombre Completo: {{ $memberName($sistematizador) }}</td>
                    <td colspan="4">No. de empleado/a: {{ $sistematizador?->numero_empleado }}</td>
                </tr>
                <tr>
                    <td colspan="5">Correo electrónico: {{ $sistematizador?->correo }}</td>
                    <td colspan="4">Celular: {{ $sistematizador?->celular }}</td>
                </tr>
                <tr>
                    <td colspan="5">Categoría: {{ $sistematizador?->categoria }}</td>
                    <td colspan="4">Departamento al que pertenece: {{ $sistematizador?->departamento }}</td>
                </tr>
            </table>

            <table class="form018-table">
                <tr><td class="form018-blue form018-center" colspan="12">EQUIPO DOCENTE DE LA UNAH (Agregar más líneas de ser necesario)</td></tr>
                <tr>
                    <th class="form018-blue">N°</th>
                    <th class="form018-blue" colspan="3">Nombre Completo</th>
                    <th class="form018-blue" colspan="2">No. empleado/a</th>
                    <th class="form018-blue" colspan="2">Correo electrónico</th>
                    <th class="form018-blue">Categoría</th>
                    <th class="form018-blue" colspan="2">Departamento al que pertenece</th>
                    <th class="form018-blue">Jornada laboral</th>
                </tr>
                @foreach ($docentes as $index => $docente)
                    <tr>
                        <td class="form018-blue form018-center">{{ $index + 1 }}</td>
                        <td colspan="3">{{ $memberName($docente) }}</td>
                        <td colspan="2">{{ $docente?->numero_empleado }}</td>
                        <td colspan="2">{{ $docente?->correo }}</td>
                        <td>{{ $docente?->categoria }}</td>
                        <td colspan="2">{{ $docente?->departamento }}</td>
                        <td>{{ $docente?->jornada_laboral }}</td>
                    </tr>
                @endforeach
            </table>

            <table class="form018-table">
                <tr><td class="form018-blue form018-center" colspan="12">CONSULTORES NACIONALES (agregar más líneas en caso de ser necesario)</td></tr>
                <tr>
                    <th class="form018-blue">N°</th>
                    <th class="form018-blue" colspan="4">Nombre Completo</th>
                    <th class="form018-blue" colspan="3">Profesión</th>
                    <th class="form018-blue" colspan="3">Correo electrónico</th>
                    <th class="form018-blue">Horas contratadas</th>
                </tr>
                @foreach ($consultoresNacionales as $index => $consultor)
                    <tr>
                        <td class="form018-blue form018-center">{{ $index + 1 }}</td>
                        <td colspan="4">{{ $memberName($consultor) }}</td>
                        <td colspan="3">{{ $consultor?->profesion }}</td>
                        <td colspan="3">{{ $consultor?->correo }}</td>
                        <td class="form018-center">{{ $consultor?->horas_dedicadas }}</td>
                    </tr>
                @endforeach
            </table>

            <table class="form018-table">
                <tr><td class="form018-blue form018-center" colspan="12">CONSULTORES INTERNACIONALES (agregar más líneas en caso de ser necesario)</td></tr>
                <tr>
                    <th class="form018-blue">N°</th>
                    <th class="form018-blue" colspan="4">Nombre Completo</th>
                    <th class="form018-blue" colspan="3">Nacionalidad</th>
                    <th class="form018-blue" colspan="3">Correo electrónico</th>
                    <th class="form018-blue">Horas contratadas</th>
                </tr>
                @foreach ($consultoresInternacionales as $index => $consultor)
                    <tr>
                        <td class="form018-blue form018-center">{{ $index + 1 }}</td>
                        <td colspan="4">{{ $memberName($consultor) }}</td>
                        <td colspan="3">{{ $consultor?->nacionalidad }}</td>
                        <td colspan="3">{{ $consultor?->correo }}</td>
                        <td class="form018-center">{{ $consultor?->horas_dedicadas }}</td>
                    </tr>
                @endforeach
            </table>
        </main>
    </section>

    <section class="form018-page">
        <header class="form018-header">
            <img src="{{ $headerUrl }}" alt="UNAH VRA Dirección de Vinculación Universidad Sociedad">
            <div class="form018-contact">vinculacion.sociedad@unah.edu.hn<br>Tel. 2216-7070&nbsp; Ext. 110576</div>
            <div class="form018-yellow-strip"></div>
        </header>
        <img class="form018-watermark" src="{{ $watermarkUrl }}" alt="">
        <img class="form018-footer" src="{{ $footerUrl }}" alt="">

        <main class="form018-main page-start">
            <div class="form018-section tight">
                <span>IV.</span>
                <span>PARTICIPACIÓN DE LA COMUNIDAD UNIVERSITARIA EN LA EJECUCIÓN DE LA ACCIÓN</span>
            </div>
            <table class="form018-table form018-docx-table4">
                <colgroup>
                    @foreach ([1869, 1055, 223, 1107, 1069, 796, 328, 871, 208, 857, 309, 834] as $width)
                        <col style="width: {{ $width / 9526 * 100 }}%;">
                    @endforeach
                </colgroup>
                <tr style="height: 24.9px;">
                    <td class="form018-blue form018-numbered" rowspan="5"><span class="num">19.</span>Participación de estudiantes de grado / posgrado</td>
                    <td class="form018-blue form018-center" colspan="2">TOTAL</td>
                    <td class="form018-blue form018-center" colspan="9">Desglose del tipo de participación de estudiantes:</td>
                </tr>
                <tr>
                    <td class="form018-blue form018-center" colspan="2">Hombres</td>
                    <td class="form018-gray form018-center" rowspan="2" colspan="2">Práctica de asignatura</td>
                    <td class="form018-gray form018-center" rowspan="2" colspan="4">Servicio Social o PPS</td>
                    <td class="form018-gray form018-center" rowspan="2" colspan="3">Voluntariado</td>
                </tr>
                <tr style="height: 23.9px;"><td colspan="2" class="form018-center">{{ $participacion('Estudiantes de grado / posgrado', 'hombres') }}</td></tr>
                <tr style="height: 27.1px;">
                    <td class="form018-blue form018-center" colspan="2">Mujeres</td>
                    <td class="form018-gray form018-center">Hombres</td>
                    <td class="form018-gray form018-center">Mujeres</td>
                    <td class="form018-gray form018-center" colspan="2">Hombres</td>
                    <td class="form018-gray form018-center" colspan="2">Mujeres</td>
                    <td class="form018-gray form018-center" colspan="2">Hombres</td>
                    <td class="form018-gray form018-center">Mujeres</td>
                </tr>
                <tr style="height: 30.3px;">
                    <td colspan="2" class="form018-center">{{ $participacion('Estudiantes de grado / posgrado', 'mujeres') }}</td>
                    <td class="form018-center">{{ $participacion('Práctica de asignatura', 'hombres') }}</td>
                    <td class="form018-center">{{ $participacion('Práctica de asignatura', 'mujeres') }}</td>
                    <td class="form018-center" colspan="2">{{ $participacion('Servicio Social o PPS', 'hombres') }}</td>
                    <td class="form018-center" colspan="2">{{ $participacion('Servicio Social o PPS', 'mujeres') }}</td>
                    <td class="form018-center" colspan="2">{{ $participacion('Voluntariado', 'hombres') }}</td>
                    <td class="form018-center">{{ $participacion('Voluntariado', 'mujeres') }}</td>
                </tr>
                <tr style="height: 24.9px;">
                    <td class="form018-blue form018-numbered" rowspan="7"><span class="num">20.</span>Personal docente</td>
                    <td class="form018-blue form018-center" colspan="2">TOTAL</td>
                    <td class="form018-blue form018-center" colspan="9">Desglose del tipo de participación de personal docente:</td>
                </tr>
                <tr>
                    <td class="form018-blue form018-center" colspan="2">Hombres</td>
                    <td class="form018-blue form018-center" rowspan="2" colspan="2">Profesores x hora</td>
                    <td class="form018-blue form018-center" rowspan="2" colspan="4">Profesores horarios</td>
                    <td class="form018-blue form018-center" rowspan="2" colspan="3">Profesores permanentes</td>
                </tr>
                <tr><td colspan="2" class="form018-center">{{ $participacion('Personal docente', 'hombres') }}</td></tr>
                <tr style="height: 31.3px;">
                    <td class="form018-blue form018-center" colspan="2">Mujeres</td>
                    <td class="form018-gray form018-center">Hombres</td>
                    <td class="form018-gray form018-center">Mujeres</td>
                    <td class="form018-gray form018-center" colspan="2">Hombres</td>
                    <td class="form018-gray form018-center" colspan="2">Mujeres</td>
                    <td class="form018-gray form018-center" colspan="2">Hombres</td>
                    <td class="form018-gray form018-center">Mujeres</td>
                </tr>
                @for ($i = 0; $i < 3; $i++)
                    <tr style="height: 16.9px;">
                        <td colspan="2" class="form018-center">{{ $i === 0 ? $participacion('Personal docente', 'mujeres') : '' }}</td>
                        <td class="form018-center">{{ $i === 0 ? $participacion('Profesores por hora', 'hombres') : '' }}</td>
                        <td class="form018-center">{{ $i === 0 ? $participacion('Profesores por hora', 'mujeres') : '' }}</td>
                        <td class="form018-center" colspan="2">{{ $i === 0 ? $participacion('Profesores horarios', 'hombres') : '' }}</td>
                        <td class="form018-center" colspan="2">{{ $i === 0 ? $participacion('Profesores horarios', 'mujeres') : '' }}</td>
                        <td class="form018-center" colspan="2">{{ $i === 0 ? $participacion('Profesores permanentes', 'hombres') : '' }}</td>
                        <td class="form018-center">{{ $i === 0 ? $participacion('Profesores permanentes', 'mujeres') : '' }}</td>
                    </tr>
                @endfor
                <tr style="height: 24.9px;">
                    <td class="form018-blue form018-numbered" rowspan="5"><span class="num">21.</span>Personal administrativo</td>
                    <td class="form018-blue form018-center" colspan="2">TOTAL</td>
                    <td class="form018-blue form018-center" colspan="9">Desglose del tipo de participación de estudiantes:</td>
                </tr>
                <tr style="height: 20.9px;">
                    <td class="form018-blue form018-center" colspan="2">Hombres</td>
                    <td class="form018-blue form018-center" rowspan="2" colspan="2">Administrativo</td>
                    <td class="form018-blue form018-center" rowspan="2" colspan="4">Servicios</td>
                    <td class="form018-blue form018-center" rowspan="2" colspan="3">Asistentes técnicos laboratorios / Instructores</td>
                </tr>
                <tr><td colspan="2" class="form018-center">{{ $participacion('Personal administrativo', 'hombres') }}</td></tr>
                <tr style="height: 31.3px;">
                    <td class="form018-blue form018-center" colspan="2">Mujeres</td>
                    <td class="form018-gray form018-center">Hombres</td>
                    <td class="form018-gray form018-center">Mujeres</td>
                    <td class="form018-gray form018-center" colspan="2">Hombres</td>
                    <td class="form018-gray form018-center" colspan="2">Mujeres</td>
                    <td class="form018-gray form018-center" colspan="2">Hombres</td>
                    <td class="form018-gray form018-center">Mujeres</td>
                </tr>
                <tr style="height: 16.9px;">
                    <td colspan="2" class="form018-center">{{ $participacion('Personal administrativo', 'mujeres') }}</td>
                    <td class="form018-center">{{ $participacion('Administrativo servicios', 'hombres') }}</td>
                    <td class="form018-center">{{ $participacion('Administrativo servicios', 'mujeres') }}</td>
                    <td class="form018-center" colspan="2">{{ $participacion('Administrativo servicios', 'hombres') }}</td>
                    <td class="form018-center" colspan="2">{{ $participacion('Administrativo servicios', 'mujeres') }}</td>
                    <td class="form018-center" colspan="2">{{ $participacion('Asistentes técnicos laboratorios / instructores', 'hombres') }}</td>
                    <td class="form018-center">{{ $participacion('Asistentes técnicos laboratorios / instructores', 'mujeres') }}</td>
                </tr>
                <tr style="height: 16.9px;">
                    <td class="form018-blue form018-numbered" rowspan="5"><span class="num">22.</span>Detalle de la práctica de asignatura / posgrado</td>
                    <td class="form018-blue form018-center" rowspan="2">Código</td>
                    <td class="form018-blue form018-center" rowspan="2" colspan="4">Nombre de la asignatura / posgrado</td>
                    <td class="form018-blue form018-center" rowspan="2" colspan="2">Período académico</td>
                    <td class="form018-blue form018-center" colspan="4">Matrícula</td>
                </tr>
                <tr style="height: 16.9px;">
                    <td class="form018-blue form018-center" colspan="2">Hombres</td>
                    <td class="form018-blue form018-center" colspan="2">Mujeres</td>
                </tr>
                @foreach ($rows($accion->practicasAsignatura, 3) as $practica)
                    <tr style="height: 22.7px;">
                        <td>{{ $practica?->codigo_asignatura }}</td>
                        <td colspan="4">{{ $practica?->asignatura?->nombre ?? $practica?->nombre_asignatura }}</td>
                        <td colspan="2">{{ $practica?->periodoAcademico?->nombre ?? $practica?->periodo_academico_texto }}</td>
                        <td class="form018-center" colspan="2">{{ $practica?->matricula_hombres }}</td>
                        <td class="form018-center" colspan="2">{{ $practica?->matricula_mujeres }}</td>
                    </tr>
                @endforeach
            </table>

            <div class="form018-section tight">
                <span>V.</span>
                <span>INFORMACIÓN DE LA ENTIDAD CONTRAPARTE</span>
            </div>
            <table class="form018-table form018-docx-table5">
                <colgroup>
                    @foreach ([1931, 669, 1238, 1015, 890, 1356, 410, 139, 1876] as $width)
                        <col style="width: {{ $width / 9524 * 100 }}%;">
                    @endforeach
                </colgroup>
                <tr style="height: 25.3px;">
                    <td class="form018-blue form018-numbered" rowspan="2" colspan="5"><span class="num">23.</span>LA ACTIVIDAD TIENE CONTRAPARTE</td>
                    <td class="form018-blue form018-center" colspan="3">SI</td>
                    <td class="form018-blue form018-center">NO</td>
                </tr>
                <tr style="height: 25.3px;">
                    <td class="form018-mark" colspan="3">{{ $contraparte ? 'X' : '' }}</td>
                    <td class="form018-mark">{{ $contraparte ? '' : 'X' }}</td>
                </tr>
                <tr style="height: 25.3px;"><td class="form018-blue form018-numbered" colspan="9"><span class="num">24.</span>PERFIL DE LA ENTIDAD CONTRAPARTE (En los casos que aplique)</td></tr>
                <tr style="height: 25.3px;">
                    <td class="form018-blue form018-center form018-small">Secretaría de Estado</td>
                    <td class="form018-blue form018-center form018-small" colspan="2">Gobierno Municipal</td>
                    <td class="form018-blue form018-center form018-small" colspan="2">Sector productivo</td>
                    <td class="form018-blue form018-center form018-small" colspan="3">Entidades financieras</td>
                    <td class="form018-blue form018-center form018-small">Sector privado de servicios</td>
                </tr>
                <tr style="height: 25.3px;">
                    <td class="form018-mark">{{ $hasText($contraparte?->tipoContraparte?->nombre, 'Secretaria de Estado') ? 'X' : '' }}</td>
                    <td class="form018-mark" colspan="2">{{ $hasText($contraparte?->tipoContraparte?->nombre, 'Gobierno Municipal') ? 'X' : '' }}</td>
                    <td class="form018-mark" colspan="2">{{ $hasText($contraparte?->tipoContraparte?->nombre, 'Sector productivo') ? 'X' : '' }}</td>
                    <td class="form018-mark" colspan="3">{{ $hasText($contraparte?->tipoContraparte?->nombre, 'Entidades financieras') ? 'X' : '' }}</td>
                    <td class="form018-mark">{{ $hasText($contraparte?->tipoContraparte?->nombre, 'Sector privado de servicios') ? 'X' : '' }}</td>
                </tr>
                <tr style="height: 25.3px;">
                    <td class="form018-blue form018-center form018-small">Organizaciones gremiales</td>
                    <td class="form018-blue form018-center form018-small" colspan="2">Sociedad civil organizada</td>
                    <td class="form018-blue form018-center form018-small" colspan="2">Sector académico</td>
                    <td class="form018-blue form018-center form018-small" colspan="3">Organismos internacionales</td>
                    <td class="form018-blue form018-center form018-small">Unidad de la UNAH</td>
                </tr>
                <tr style="height: 25.3px;">
                    <td class="form018-mark">{{ $hasText($contraparte?->tipoContraparte?->nombre, 'Organizaciones gremiales') ? 'X' : '' }}</td>
                    <td class="form018-mark" colspan="2">{{ $hasText($contraparte?->tipoContraparte?->nombre, 'Sociedad civil organizada') ? 'X' : '' }}</td>
                    <td class="form018-mark" colspan="2">{{ $hasText($contraparte?->tipoContraparte?->nombre, 'Sector academico') ? 'X' : '' }}</td>
                    <td class="form018-mark" colspan="3">{{ $hasText($contraparte?->tipoContraparte?->nombre, 'Organismos internacionales') ? 'X' : '' }}</td>
                    <td class="form018-mark">{{ $hasText($contraparte?->tipoContraparte?->nombre, 'Unidad de la UNAH') ? 'X' : '' }}</td>
                </tr>
                <tr style="height: 25.3px;"><td class="form018-blue form018-numbered" colspan="2"><span class="num">25.</span>Nombre de la contraparte</td><td colspan="7">{{ $contraparte?->nombre }}</td></tr>
                <tr style="height: 46.7px;"><td class="form018-blue form018-numbered" colspan="2"><span class="num">26.</span>Nombre del contacto directo</td><td colspan="7">{{ $contraparte?->representante }}</td></tr>
                <tr style="height: 37.3px;"><td class="form018-blue form018-numbered" colspan="2"><span class="num">27.</span>Cargo del contacto de la contraparte</td><td colspan="7">{{ $contraparte?->cargo_contacto }}</td></tr>
                <tr style="height: 37.3px;">
                    <td class="form018-blue form018-numbered" rowspan="2" colspan="2"><span class="num">28.</span>Datos de contacto</td>
                    <td class="form018-light form018-center" colspan="5">Correo electrónico</td>
                    <td class="form018-light form018-center" colspan="2">No. Teléfono</td>
                </tr>
                <tr style="height: 37.3px;"><td colspan="5">{{ $contraparte?->correo }}</td><td colspan="2">{{ $contraparte?->telefono }}</td></tr>
                <tr style="height: 37.3px;"><td class="form018-blue form018-numbered" colspan="2"><span class="num">29.</span>Dirección exacta de la sede principal</td><td colspan="7">{{ $contraparte?->direccion }}</td></tr>
                <tr style="height: 37.3px;">
                    <td class="form018-blue form018-numbered" rowspan="2" colspan="2"><span class="num">30.</span>Tipo de instrumento que da lugar a la alianza</td>
                    <td class="form018-light form018-center" colspan="2">Carta formal de solicitud a la unidad académica</td>
                    <td class="form018-light form018-center" colspan="2">Carta de intenciones con la UNAH</td>
                    <td class="form018-light form018-center" colspan="3">Convenio marco con la UNAH</td>
                </tr>
                <tr style="height: 37.3px;">
                    <td class="form018-mark" colspan="2">{{ $hasText($contraparte?->instrumentoAlianza?->nombre, 'Carta formal') ? 'X' : '' }}</td>
                    <td class="form018-mark" colspan="2">{{ $hasText($contraparte?->instrumentoAlianza?->nombre, 'Carta de intenciones') ? 'X' : '' }}</td>
                    <td class="form018-mark" colspan="3">{{ $hasText($contraparte?->instrumentoAlianza?->nombre, 'Convenio marco') ? 'X' : '' }}</td>
                </tr>
                <tr class="form018-auto-row" style="height: 37.3px;"><td class="form018-blue form018-numbered" colspan="2"><span class="num">31.</span>Breve descripción de los compromisos asumidos por la contraparte</td><td class="form018-scroll-cell" colspan="7">{{ $scrollField($contraparte?->compromisos, 'form018-scroll-contraparte') }}</td></tr>
            </table>
        </main>
    </section>

    <section class="form018-page">
        <header class="form018-header">
            <img src="{{ $headerUrl }}" alt="UNAH VRA Dirección de Vinculación Universidad Sociedad">
            <div class="form018-contact">vinculacion.sociedad@unah.edu.hn<br>Tel. 2216-7070&nbsp; Ext. 110576</div>
            <div class="form018-yellow-strip"></div>
        </header>
        <img class="form018-watermark" src="{{ $watermarkUrl }}" alt="">
        <img class="form018-footer" src="{{ $footerUrl }}" alt="">

        <main class="form018-main page-start">
            <div class="form018-section tight">
                <span>VI.</span>
                <span>INFORMACIÓN DE LA ACCIÓN</span>
            </div>
            <table class="form018-table form018-docx-table6">
                <colgroup>
                    @foreach ([949, 272, 3541, 406, 1063, 3293] as $width)
                        <col style="width: {{ $width / 9524 * 100 }}%;">
                    @endforeach
                </colgroup>
                <tr style="height: 25.9px;"><td class="form018-blue form018-numbered" colspan="6"><span class="num">32.</span>Resumen de la acción. Explicar brevemente  en qué consiste la acción, los antecedentes que dieron su origen y la importancia que tiene para los objetivos estratégicos de la UNAH. (Resumen no más de 5 líneas)</td></tr>
                <tr class="form018-auto-row" style="height: 118.4px;"><td class="form018-scroll-cell" colspan="6">{{ $scrollField($accion->resumen, 'form018-scroll-resumen') }}</td></tr>
                <tr style="height: 18.3px;"><td class="form018-blue form018-numbered" colspan="6"><span class="num">33.</span>Definición del problema:  Breve descripción del problema que se desea resolver, indicando línea base que se tendrá en consideración para la definición de los resultados de la acción (no más de 5 líneas)</td></tr>
                <tr class="form018-auto-row" style="height: 111.5px;"><td class="form018-scroll-cell" colspan="6">{{ $scrollField($accion->definicion_problema, 'form018-scroll-definicion') }}</td></tr>
                <tr><td class="form018-blue form018-numbered" colspan="6"><span class="num">34.</span>Objetivo general</td></tr>
                <tr class="form018-auto-row"><td class="form018-scroll-cell" colspan="6">{{ $scrollField($accion->objetivo_general, 'form018-scroll-md') }}</td></tr>
                <tr><td class="form018-blue form018-numbered" colspan="6"><span class="num">35.</span>Objetivos específicos</td></tr>
                <tr class="form018-auto-row"><td class="form018-scroll-cell" colspan="6">{{ $scrollField($objetivosEspecificosTexto ?: ' ', 'form018-scroll-md') }}</td></tr>
                <tr><td class="form018-blue form018-numbered" colspan="6"><span class="num">36.</span>RESULTADOS ESPERADOS El indicador de resultado es una medida específica y observable que permite evaluar el grado de cumplimiento de los resultados que se han planteado. Sirven para evaluar en qué medida y calidad se lograron los objetivos del proyecto. Hay tres tipos de resultados: 1) corto plazo, que son los productos que se obtendrán con el programa de formación, 2) los de mediano plazo: que son los efectos que alcanzará el programa de formación y 3) los de largo plazo: resultados de impacto. Se recomienda 2 resultados por objetivo específico, como máximo</td></tr>
                <tr><td class="form018-gray" colspan="6">Resultados de corto plazo del proyecto. Debe de plantearse resultados para cada objetivo específico. Son los productos que se lograrán a corto plazo</td></tr>
                <tr>
                    <td class="form018-gray form018-center">OE</td>
                    <td class="form018-gray form018-center" colspan="3">Descripción del resultado de corto plazo</td>
                    <td class="form018-gray form018-center" colspan="2">Medio de verificación (indicador)</td>
                </tr>
                @foreach ($rows($resultadosPorPlazo->get('corto', collect()), 6) as $resultado)
                    @php
                        $resultadoPartes = $resultadoParts($resultado);
                        $objetivo = $resultado ? $objetivosPorId->get($resultado->enf_objetivo_especifico_id) : null;
                    @endphp
                    <tr class="form018-auto-row">
                        <td class="form018-scroll-cell">{{ $scrollField($objetivo?->orden ? (string) $objetivo->orden : '', 'form018-scroll-sm') }}</td>
                        <td class="form018-scroll-cell" colspan="3">{{ $scrollField($resultadoPartes[1] ?? '', 'form018-scroll-sm') }}</td>
                        <td class="form018-scroll-cell" colspan="2">{{ $scrollField($resultado?->indicador, 'form018-scroll-sm') }}</td>
                    </tr>
                @endforeach
                <tr><td class="form018-gray" colspan="6">Indicadores de mediano plazo. Son los efectos que se esperan alcanzar de la acción, es decir, la transformación esperada en la población beneficiada. Presentar como mínimo 1 resultado</td></tr>
                <tr>
                    <td class="form018-gray form018-center" colspan="3">Descripción del resultado</td>
                    <td class="form018-gray form018-center" colspan="3">Medio de verificación (indicador)</td>
                </tr>
                @foreach ($rows($resultadosPorPlazo->get('mediano', collect()), 5) as $resultado)
                    @php $resultadoPartes = $resultadoParts($resultado); @endphp
                    <tr class="form018-auto-row">
                        <td class="form018-scroll-cell" colspan="3">{{ $scrollField($resultadoPartes[1] ?? '', 'form018-scroll-sm') }}</td>
                        <td class="form018-scroll-cell" colspan="3">{{ $scrollField($resultado?->indicador, 'form018-scroll-sm') }}</td>
                    </tr>
                @endforeach
                <tr><td class="form018-gray" colspan="6">Impacto que se desea generar en el proyecto. Debe de expresar los indicadores de impacto de la acción. Presentar como mínimo 1 resultado</td></tr>
                <tr>
                    <td class="form018-gray form018-center" colspan="3">Descripción del resultado de largo plazo</td>
                    <td class="form018-gray form018-center" colspan="3">Medio de verificación (indicador con el que se evaluará)</td>
                </tr>
                @foreach ($rows($resultadosPorPlazo->get('largo', collect()), 5) as $resultado)
                    @php $resultadoPartes = $resultadoParts($resultado); @endphp
                    <tr class="form018-auto-row">
                        <td class="form018-scroll-cell" colspan="3">{{ $scrollField($resultadoPartes[1] ?? '', 'form018-scroll-sm') }}</td>
                        <td class="form018-scroll-cell" colspan="3">{{ $scrollField($resultado?->indicador, 'form018-scroll-sm') }}</td>
                    </tr>
                @endforeach
                <tr style="height: 18.3px;"><td class="form018-blue form018-numbered" colspan="6"><span class="num">37.</span>Objetivos de Desarrollo Sostenible (ODS) a los que se contribuye: Indicar el o los ODS a los que pretende contribuir la acción y las metas correspondientes. Para esta descripción deberá basarse en el documento de ODS que puede consultar en el siguiente enlace: Objetivos y metas de desarrollo sostenible - Desarrollo Sostenible</td></tr>
                <tr style="height: 18.3px;">
                    <td class="form018-blue form018-center" colspan="2">Total, ODS</td>
                    <td class="form018-blue form018-center" colspan="3">Descripción de ODS (Nombre y número)</td>
                    <td class="form018-blue form018-center">Metas a las que contribuye</td>
                </tr>
                @foreach ($rows($odsSeleccionados, 4) as $i => $ods)
                    @php
                        $metasDelOds = $ods
                            ? $metasPorOds->get($ods->id, collect())
                                ->map(fn ($meta) => trim(($meta->numero_meta ?? '').' '.($meta->descripcion ?? '')))
                                ->implode('; ')
                            : '';
                    @endphp
                    <tr class="form018-auto-row" style="height: 18.3px;">
                        <td class="form018-center" colspan="2">{{ $i === 0 ? $odsSeleccionados->count() : '' }}</td>
                        <td class="form018-scroll-cell" colspan="3">{{ $scrollField($ods?->nombre ?? '', 'form018-scroll-xs') }}</td>
                        <td class="form018-scroll-cell">{{ $scrollField($metasDelOds, 'form018-scroll-xs') }}</td>
                    </tr>
                @endforeach
                <tr style="height: 18.3px;"><td class="form018-blue form018-numbered" colspan="6"><span class="num">38.</span>Alineamiento con lo esencial de la reforma de la UNAH (detalle brevemente cómo se alinean los ejes de lo esencial de la reforma en la ejecución de la acción)</td></tr>
                <tr class="form018-auto-row" style="height: 18.3px;"><td class="form018-scroll-cell" colspan="6">{{ $scrollField($alineamientoTexto, 'form018-scroll-xs') }}</td></tr>
                <tr style="height: 18.3px;"><td class="form018-blue form018-numbered" colspan="6"><span class="num">39.</span>Resumen de la logística que empleará para el desarrollo de la actividad</td></tr>
                <tr class="form018-auto-row" style="height: 18.3px;"><td class="form018-scroll-cell" colspan="6">{{ $scrollField($accion->logistica, 'form018-scroll-xs') }}</td></tr>
            </table>
        </main>
    </section>

    <section class="form018-page">
        <header class="form018-header">
            <img src="{{ $headerUrl }}" alt="UNAH VRA Dirección de Vinculación Universidad Sociedad">
            <div class="form018-contact">vinculacion.sociedad@unah.edu.hn<br>Tel. 2216-7070&nbsp; Ext. 110576</div>
            <div class="form018-yellow-strip"></div>
        </header>
        <img class="form018-watermark" src="{{ $watermarkUrl }}" alt="">
        <img class="form018-footer" src="{{ $footerUrl }}" alt="">

        <main class="form018-main page-start">
            <div class="form018-section tight">
                <span>VII.</span>
                <span>DETALLE DEL PRESUPUESTO</span>
            </div>
            <table class="form018-table form018-docx-table7">
                <colgroup>
                    @foreach ([2580, 1737, 781, 63, 893, 175, 1137, 215, 210, 1733] as $width)
                        <col style="width: {{ $width / 9524 * 100 }}%;">
                    @endforeach
                </colgroup>
                <tr>
                    <td class="form018-blue" rowspan="2" colspan="3">Obtendrá ingresos por el desarrollo de la actividad:</td>
                    <td class="form018-blue form018-center" colspan="4">Si</td>
                    <td class="form018-blue form018-center" colspan="3">No</td>
                </tr>
                <tr style="height: 30.3px;">
                    <td class="form018-mark" colspan="4">{{ $accion->genera_ingresos ? 'X' : '' }}</td>
                    <td class="form018-mark" colspan="3">{{ $accion->genera_ingresos ? '' : 'X' }}</td>
                </tr>
                <tr><td class="form018-blue form018-numbered" colspan="10"><span class="num">40.</span>Presupuesto de ingresos (manifestado en lempiras)</td></tr>
                <tr>
                    <td class="form018-blue form018-center" colspan="4">Concepto</td>
                    <td class="form018-blue form018-center" colspan="2">Cantidad</td>
                    <td class="form018-blue form018-center" colspan="2">Costo unitario</td>
                    <td class="form018-blue form018-center" colspan="2">Costo Total</td>
                </tr>
                @foreach ($ingresoRubrosForm018 as $index => $rubro)
                    @php $detalle = $detallePresupuesto($ingresos, $rubro['needles']); @endphp
                    <tr>
                        <td class="form018-light" colspan="4">{{ $inciso($index).$rubro['label'] }}</td>
                        <td class="form018-center" colspan="2">{{ $detalle?->cantidad }}</td>
                        <td colspan="2">{{ $detalle ? $money($detalle->costo_unitario) : '' }}</td>
                        <td colspan="2">{{ $detalle ? $money($detalle->total) : '' }}</td>
                    </tr>
                @endforeach
                <tr><td class="form018-blue form018-right" colspan="8">Total Ingresos</td><td colspan="2">{{ $money($ingresosTotal) }}</td></tr>
                <tr><td class="form018-blue form018-numbered" colspan="10"><span class="num">41.</span>Presupuesto de egresos (manifestado en lempiras)</td></tr>
                <tr>
                    <td class="form018-blue form018-center" colspan="4">Concepto</td>
                    <td class="form018-blue form018-center" colspan="2">Cantidad</td>
                    <td class="form018-blue form018-center" colspan="2">Costo unitario</td>
                    <td class="form018-blue form018-center" colspan="2">Costo Total</td>
                </tr>
                @foreach ($egresoRubrosForm018 as $index => $rubro)
                    @php $detalle = $detallePresupuesto($egresos, $rubro['needles']); @endphp
                    <tr>
                        <td class="form018-light" colspan="4">{{ $inciso($index).$rubro['label'] }}</td>
                        <td class="form018-center" colspan="2">{{ $detalle?->cantidad }}</td>
                        <td colspan="2">{{ $detalle ? $money($detalle->costo_unitario) : '' }}</td>
                        <td colspan="2">{{ $detalle ? $money($detalle->total) : '' }}</td>
                    </tr>
                @endforeach
                <tr><td class="form018-blue form018-right" colspan="8">Total egresos</td><td colspan="2">{{ $money($egresosTotal) }}</td></tr>
                <tr><td class="form018-blue form018-right" colspan="8">Excedente de la actividad (ingresos menos los egresos)</td><td colspan="2">{{ $money($ingresosTotal - $egresosTotal) }}</td></tr>
                <tr><td class="form018-blue form018-numbered"><span class="num">42.</span>Breve descripción en qué se destinará el excedente de la actividad</td><td colspan="9">{{ $accion->descripcion_excedente }}</td></tr>
                <tr>
                    <td class="form018-blue form018-numbered"><span class="num">43.</span>Mecanismo de administración de la acción</td>
                    <td class="form018-gray form018-center">FUNDAUNAH</td>
                    <td class="form018-mark" colspan="3">{{ $hasText($accion->mecanismo_administracion, 'FUNDAUNAH') ? 'X' : '' }}</td>
                    <td class="form018-gray form018-center" colspan="4">Tesorería de la UNAH</td>
                    <td class="form018-mark">{{ $hasText($accion->mecanismo_administracion, 'Tesorer') ? 'X' : '' }}</td>
                </tr>
            </table>

            <table class="form018-table form018-docx-table8">
                <colgroup>
                    @foreach ([5160, 1069, 1352, 1943] as $width)
                        <col style="width: {{ $width / 9524 * 100 }}%;">
                    @endforeach
                </colgroup>
                <tr><td class="form018-blue form018-numbered" colspan="4"><span class="num">44.</span>Aportación de la UNAH (Se calcula el aporte de la UNAH, a través de la participación del personal y estudiantes de la unidad académica organizadora, así como utilización de la infraestructura y servicios públicos de la universidad)</td></tr>
                <tr>
                    <td class="form018-blue form018-center">Concepto</td>
                    <td class="form018-blue form018-center">Cantidad</td>
                    <td class="form018-blue form018-center">Costo unitario</td>
                    <td class="form018-blue form018-center">Costo Total</td>
                </tr>
                @foreach ($aporteUnahRubrosForm018 as $index => $rubro)
                    @php $detalle = $detallePresupuesto($aporteUnah, $rubro['needles']); @endphp
                    <tr>
                        <td class="form018-light">{{ $inciso($index).$rubro['label'] }}</td>
                        <td class="form018-center">{{ $detalle?->cantidad }}</td>
                        <td>{{ $detalle ? $money($detalle->costo_unitario) : '' }}</td>
                        <td>{{ $detalle ? $money($detalle->total) : '' }}</td>
                    </tr>
                @endforeach
                <tr><td class="form018-blue form018-right" colspan="3">Total aporte UNAH</td><td>{{ $money($aporteTotal) }}</td></tr>
            </table>
        </main>
    </section>

    <section class="form018-page">
        <header class="form018-header">
            <img src="{{ $headerUrl }}" alt="UNAH VRA Dirección de Vinculación Universidad Sociedad">
            <div class="form018-contact">vinculacion.sociedad@unah.edu.hn<br>Tel. 2216-7070&nbsp; Ext. 110576</div>
            <div class="form018-yellow-strip"></div>
        </header>
        <img class="form018-watermark" src="{{ $watermarkUrl }}" alt="">
        <img class="form018-footer" src="{{ $footerUrl }}" alt="">

        <main class="form018-main page-start">
            <div class="form018-section tight">
                <span>VIII.</span>
                <span>CRONOGRAMA DE LAS ACTIVIDADES DE LA ACCIÓN</span>
            </div>
            <table class="form018-table form018-docx-table9">
                <colgroup>
                    @foreach ([3597, 2091, 1846, 2273, 1424] as $width)
                        <col style="width: {{ $width / 11231 * 100 }}%;">
                    @endforeach
                </colgroup>
                <tr style="height: 48.9px;"><td class="form018-blue form018-numbered" colspan="5"><span class="num">45.</span>DESCRIPCIÓN DE ACTIVIDADES DEL PROYECTO (Descripción de todas las actividades enmarcadas en el proyecto, las cuales pueden ser, entre otras, la negociación inicial, la organización de los equipos de trabajo, la planificación, el desarrollo de actividades de capacitación y fortalecimiento, presentación de informe intermedio o parciales, presentación del informe final, proceso de evaluación, proceso de sistematización, publicación de artículo, otras acciones de divulgación)</td></tr>
                <tr style="height: 24.9px;"><td class="form018-gray form018-center" colspan="5">Cronograma de actividades</td></tr>
                <tr style="height: 24.5px;">
                    <td class="form018-light form018-center">Actividad</td>
                    <td class="form018-light form018-center">Producto</td>
                    <td class="form018-light form018-center">Fecha de ejecución</td>
                    <td class="form018-light form018-center">Responsable</td>
                    <td class="form018-light form018-center">Horas requeridas</td>
                </tr>
                @foreach ($accion->cronograma as $item)
                    <tr style="height: 37.3px;">
                        <td>{{ $item?->actividad }}</td>
                        <td>{{ $item?->producto }}</td>
                        <td>{{ $item?->fecha_inicio?->format('d/m/Y') }}</td>
                        <td>{{ $item?->responsable_texto }}</td>
                        <td class="form018-center">{{ $item?->horas_requeridas }}</td>
                    </tr>
                @endforeach
            </table>
            <p class="form018-note">Observación: El equipo ejecutor debe de elaborar una bitácora del proyecto que recoja todas las evidencias de su desarrollo. Esta bitácora se presentará junto al informe final de la acción.</p>

            <div class="form018-section tight">
                <span>IX.</span>
                <span>FIRMAS</span>
            </div>
            <table class="form018-table form018-docx-signatures">
                <colgroup><col style="width: 48.39%;"><col style="width: 51.61%;"></colgroup>
                <tr>
                    <td class="form018-blue form018-center">Coordinador de la acción por la UNAH</td>
                    <td class="form018-blue form018-center">Jefe de la Unidad Académica que lidera la acción (jefe de departamento, director de escuela)</td>
                </tr>
                <tr>
                    <td>Nombre: {{ $firmaCoordinadorAccion }}</td>
                    <td>Nombre: {{ $firmaJefeUnidad }}</td>
                </tr>
                <tr>
                    <td class="form018-signature"></td>
                    <td class="form018-signature"></td>
                </tr>
                <tr>
                    <td class="form018-gray form018-center">Firma del profesor/a responsable de la acción</td>
                    <td class="form018-gray form018-center">Firma del Jefe/a de la Unidad Académica que lidera la acción</td>
                </tr>
            </table>

            <table class="form018-table form018-docx-signatures">
                <colgroup><col style="width: 48.39%;"><col style="width: 51.61%;"></colgroup>
                <tr>
                    <td class="form018-blue form018-center">Coordinador(a) del Comité de Vinculación de la Facultad o Unidad de Vinculación del Centro Regional</td>
                    <td class="form018-blue form018-center">Decano(a) o Director(a) del Centro Regional</td>
                </tr>
                <tr>
                    <td>Nombre: {{ $firmaComiteLocal }}</td>
                    <td>Nombre: {{ $firmaDecanoDirector }}</td>
                </tr>
                <tr>
                    <td class="form018-signature"></td>
                    <td class="form018-signature"></td>
                </tr>
                <tr>
                    <td class="form018-gray form018-center">Firma del coordinador del Comité Local</td>
                    <td class="form018-gray form018-center">Firma y sello del Decano(a) o Director(a)</td>
                </tr>
            </table>

            <div class="form018-section tight">
                <span>X.</span>
                <span>DOCUMENTOS ADJUNTOS A LA FICHA</span>
            </div>
            <table class="form018-table form018-docx-documents">
                @php
                    $documentColumnWidths = $isPdf ? [1102, 6040, 1102, 1106] : [820, 4560, 820, 820, 2330];
                    $documentColumnTotal = array_sum($documentColumnWidths);
                @endphp
                <colgroup>
                    @foreach ($documentColumnWidths as $width)
                        <col style="width: {{ $width / $documentColumnTotal * 100 }}%;">
                    @endforeach
                </colgroup>
                <tr>
                    <th class="form018-blue">No</th>
                    <th class="form018-blue">Descripción</th>
                    <th class="form018-blue">Si</th>
                    <th class="form018-blue">No</th>
                    @unless ($isPdf)
                        <th class="form018-blue">Archivo</th>
                    @endunless
                </tr>
                @foreach (['Oficio de remisión del Decano/Director Centro Regional', 'Documento perfil del programa de formación', 'Otros (detallar)'] as $index => $documento)
                    @php
                        $documentoClave = str_replace('Otros (detallar)', 'Otros', $documento);
                        $documentoAdjunto = $accion->documentos->first(
                            fn ($item) => str_contains($normalize($item->nombre ?? ''), $normalize($documentoClave))
                        );
                        $tieneDocumento = (bool) $documentoAdjunto;
                        $tieneArchivo = $documentoAdjunto && filled($documentoAdjunto->ruta) && $documentoAdjunto->ruta !== 'pendiente';
                        $documentoUrl = $tieneArchivo ? Storage::url($documentoAdjunto->ruta) : null;
                    @endphp
                    <tr>
                        <td class="form018-center">{{ $index + 1 }}</td>
                        <td>{{ $documento }}</td>
                        <td class="form018-center">{{ $tieneDocumento ? 'X' : '' }}</td>
                        <td class="form018-center">{{ $tieneDocumento ? '' : 'X' }}</td>
                        @unless ($isPdf)
                            <td class="form018-center">
                                @if ($documentoUrl)
                                    <div class="form018-file-actions">
                                        <a href="{{ $documentoUrl }}" target="_blank" rel="noopener" class="form018-file-button">Ver</a>
                                        <a href="{{ $documentoUrl }}" download class="form018-file-button">Descargar</a>
                                    </div>
                                @elseif ($tieneDocumento)
                                    <span class="form018-small">Pendiente</span>
                                @endif
                            </td>
                        @endunless
                    </tr>
                @endforeach
            </table>
            <p class="form018-note">Nota: El documento 1 es obligatorio.</p>
        </main>
    </section>
</div>

@if (! $isPdf)
    <script>
        (() => {
            const shells = document.querySelectorAll('.form018-shell.screen-document');
            const pageWidth = 8.5 * 96;
            const maxScale = 1.28;

            const resize = (shell) => {
                const availableWidth = shell.clientWidth;
                const scale = Math.min(maxScale, Math.max(0.35, (availableWidth - 2) / pageWidth));
                shell.style.setProperty('--form018-screen-scale', scale.toFixed(4));
            };

            const autosizeField = (field) => {
                const styles = window.getComputedStyle(field);
                const minHeight = parseFloat(styles.minHeight) || 0;
                const maxHeight = parseFloat(styles.maxHeight) || Number.POSITIVE_INFINITY;

                field.style.height = `${minHeight}px`;
                field.style.height = `${Math.min(Math.max(field.scrollHeight, minHeight), maxHeight)}px`;
            };

            const autosizeFields = (shell) => {
                shell.querySelectorAll('[data-form018-autosize]').forEach(autosizeField);
            };

            shells.forEach((shell) => {
                resize(shell);
                autosizeFields(shell);

                if ('ResizeObserver' in window) {
                    new ResizeObserver(() => {
                        resize(shell);
                        autosizeFields(shell);
                    }).observe(shell);
                }
            });

            window.addEventListener('resize', () => {
                shells.forEach((shell) => {
                    resize(shell);
                    autosizeFields(shell);
                });
            });
        })();
    </script>
@endif
