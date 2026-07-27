@php
    use Illuminate\Support\Str;

    $isPdf = $isPdf ?? false;
    $informe = $informe ?? $accion->informeFinal;
    $esForm018 = ($accion->codigo_formulario ?? null) === 'FORM-DVUS-018';
    $certificado = $accion->certificado;
    $lugar = $accion->lugaresEjecucion->first();
    $beneficiarios = $accion->beneficiarios;
    $participantesFinales = collect($informe?->participantesFinales ?? []);
    $accionesEjecutadas = collect($informe?->accionesEjecutadas ?? []);
    $accionesNoEjecutadas = collect($informe?->accionesNoEjecutadas ?? []);
    $catalogosPorTipo = $accion->accionCatalogos
        ->groupBy(fn ($item) => $item->tipo ?: ($item->catalogo?->tipo ?? ''))
        ->map(fn ($items) => $items->flatMap(fn ($item) => [$item->catalogo?->nombre, $item->valor_texto])->filter()->values());
    $presupuestos = $accion->presupuestos->keyBy('tipo');
    $ingresos = $presupuestos->get('ingresos');
    $egresos = $presupuestos->get('egresos');
    $aporteUnah = $presupuestos->get('aporte_unah');

    $assetUrl = fn (string $path) => $isPdf ? 'file://'.public_path($path) : asset($path);
    $headerUrl = $assetUrl('images/enf/form-018-header.png');
    $watermarkUrl = $assetUrl('images/enf/form-018-watermark.png');
    $footerUrl = $assetUrl('images/enf/form-018-footer.png');
    $fecha = fn ($value) => $value ? \Illuminate\Support\Carbon::parse($value)->format('d/m/Y') : '';
    $money = fn ($value) => 'L '.number_format((float) $value, 2, '.', ',');
    $num = fn ($value) => (string) number_format((float) $value, 0, '.', ',');
    $pendiente = fn ($value) => filled($value) ? $value : 'Pendiente';
    $normalizar = fn ($value) => Str::of(Str::ascii((string) $value))->lower()->toString();
    $catalogText = fn (string $tipo) => ($catalogosPorTipo[$tipo] ?? collect())->implode(', ');
    $hasCatalog = fn (string $tipo, string $needle) => ($catalogosPorTipo[$tipo] ?? collect())
        ->contains(fn ($value) => str_contains($normalizar($value), $normalizar($needle)));
    $mark = fn (bool $checked) => $checked ? 'X' : '';
    $countSexo = function ($rows, string $sexo) use ($normalizar): int {
        $needles = $sexo === 'hombres' ? ['masculino', 'm', 'hombre'] : ['femenino', 'f', 'mujer'];

        return collect($rows)->filter(fn ($row) => in_array($normalizar($row->sexo ?? ''), $needles, true))->count();
    };
    $totalParticipantesPlanificados = (int) ($beneficiarios?->total ?? 0);
    $finalizaron = $participantesFinales->count();
    $certificados = $participantesFinales->where('certificado_emitido', true)->count();
    $stat = fn (string $field) => (int) ($informe?->{$field} ?? 0);
    $statPair = fn (string $prefix) => [
        'hombres' => $stat($prefix.'_hombres'),
        'mujeres' => $stat($prefix.'_mujeres'),
        'total' => $stat($prefix.'_hombres') + $stat($prefix.'_mujeres'),
    ];
    $inscritos = $statPair('inscritos');
    $noPresentaron = $statPair('no_presentaron');
    $abandonaron = $statPair('abandonaron');
    $reprobaron = $statPair('reprobaron');
    $aprobaron = $statPair('aprobaron');
    $graduadosUnah = $statPair('graduados_unah');
    $totalPresupuesto = fn ($presupuesto) => (float) collect($presupuesto?->detalles ?? [])->sum(fn ($row) => (float) $row->total);
    $totalIngresos = $totalPresupuesto($ingresos);
    $totalEgresos = $totalPresupuesto($egresos);
    $totalAporteUnah = $totalPresupuesto($aporteUnah);
    $rows = function ($collection, int $min) {
        $items = collect($collection)->values();
        while ($items->count() < $min) {
            $items->push(null);
        }
        return $items;
    };
    $equipoPorRol = fn ($needles) => $accion->equipo->filter(function ($row) use ($needles, $normalizar) {
        $rol = $normalizar($row->rol);
        foreach ((array) $needles as $needle) {
            if (str_contains($rol, $normalizar($needle))) {
                return true;
            }
        }
        return false;
    })->values();
    $coordinador = $accion->equipo->firstWhere('rol', 'Coordinador de la accion') ?: $accion->equipo->firstWhere('es_coordinador', true);
    $docentes = $equipoPorRol(['Docente UNAH', 'Consultor nacional', 'Consultor internacional']);
    $voluntarios = $accion->equipo
        ->reject(fn ($row) => $coordinador && (int) $row->id === (int) $coordinador->id)
        ->reject(fn ($row) => $docentes->contains('id', $row->id))
        ->values();
    $odsTexto = $accion->ods->pluck('nombre')->filter()->implode('; ');
    $metasTexto = $accion->metasContribuye->map(fn ($meta) => trim(($meta->numero_meta ?? '').' '.($meta->descripcion ?? '')))->filter()->implode('; ');
    $tituloInforme = $esForm018 ? 'INFORME FINAL DE PROYECTO DE EDUCACION NO FORMAL' : 'INFORME FINAL DE CERTIFICADO UNIVERSITARIO DE EDUCACION NO FORMAL';
    $participantesTitulo = $esForm018 ? 'Participantes finales del proyecto' : 'Participantes que finalizaron y obtuvieron acreditacion';
    $modalidadCierreLabel = $esForm018 ? 'Tipo de accion ejecutada' : 'Modalidad de acreditacion';
    $modalidadCierreValor = $informe?->modalidad_acreditacion ?: ($esForm018 ? ($accion->tipoAccion?->nombre ?: $accion->modalidad?->nombre) : ($certificado?->figuraAcreditacion?->nombre ?: 'Pendiente'));
@endphp

<style>
    @page { margin: 0; }
    * { box-sizing: border-box; }
    html, body { margin: 0; color: #111; font-family: Arial, "Liberation Sans", "DejaVu Sans", sans-serif; font-size: 8pt; line-height: 1.2; }
    .enf-final-page { position: relative; min-height: 11in; padding: 78pt 30pt 44pt; background: #fff; page-break-after: always; }
    .enf-final-page:last-child { page-break-after: auto; }
    .enf-final-header { position: absolute; top: 16pt; left: 30pt; right: 30pt; height: 52pt; }
    .enf-final-header img { width: 300pt; height: auto; }
    .enf-final-contact { position: absolute; top: 8pt; right: 0; color: #002060; font-size: 7pt; font-weight: 700; line-height: 1.22; text-align: right; }
    .enf-final-strip { position: absolute; top: -4pt; right: -18pt; width: 7pt; height: 54pt; background: #ffc000; }
    .enf-final-watermark { position: absolute; top: 330pt; right: -34pt; width: 265pt; opacity: .18; z-index: 0; }
    .enf-final-footer { position: absolute; left: 80pt; bottom: 18pt; width: 315pt; }
    .enf-final-content { position: relative; z-index: 1; }
    .enf-final-title { margin: 0 0 10pt; padding: 6pt 8pt; background: #002060; color: #fff; font-size: 10pt; text-align: center; text-transform: uppercase; }
    .enf-section { margin: 10pt 0 5pt; padding: 4pt 6pt; background: #002060; color: #fff; font-size: 8.5pt; font-weight: 700; text-transform: uppercase; page-break-after: avoid; }
    .enf-subtitle { margin: 7pt 0 3pt; color: #002060; font-size: 8pt; font-weight: 700; page-break-after: avoid; }
    .enf-table { width: 100%; margin: 0 0 6pt; border-collapse: collapse; table-layout: fixed; }
    .enf-table tr { page-break-inside: avoid; }
    .enf-table th, .enf-table td { border: .55pt solid #7f8790; padding: 3pt 3.5pt; vertical-align: top; overflow-wrap: anywhere; }
    .enf-table th { background: #f2f2f2; color: #002060; font-weight: 700; text-align: left; }
    .enf-blue { background: #002060 !important; color: #fff !important; font-weight: 700; }
    .enf-gray { background: #d9d9d9 !important; color: #111 !important; font-weight: 700; }
    .enf-label { background: #f2f2f2; color: #111; font-weight: 700; }
    .enf-center { text-align: center !important; }
    .enf-right { text-align: right !important; }
    .enf-small { font-size: 7pt; }
    .enf-muted { color: #596273; font-size: 7pt; }
    .enf-empty { color: #596273; font-style: italic; }
    .enf-field { min-height: 24pt; white-space: pre-wrap; }
    .enf-signature { height: 70pt; vertical-align: bottom !important; text-align: center; }
    @if(! $isPdf)
        .enf-final-page { padding-top: 82pt; }
    @endif
</style>

@php
    $pageChrome = function () use ($headerUrl, $watermarkUrl, $footerUrl, $isPdf) {
        echo '<header class="enf-final-header"><img src="'.e($headerUrl).'" alt="UNAH VRA DVUS"><div class="enf-final-contact">vinculacion.sociedad@unah.edu.hn<br>Tel. 2216-7070 Ext. 110576<br>educacionnoformal@unah.edu.hn</div><div class="enf-final-strip"></div></header>';
        echo '<img class="enf-final-watermark" src="'.e($watermarkUrl).'" alt="">';
        if (! $isPdf) echo '<img class="enf-final-footer" src="'.e($footerUrl).'" alt="">';
    };
@endphp

<section class="enf-final-page">
    @php $pageChrome(); @endphp
    <main class="enf-final-content">
        <h1 class="enf-final-title">{{ $tituloInforme }}</h1>

        <div class="enf-section">I. Informacion general del informe</div>
        <table class="enf-table">
            <tr><td class="enf-blue" style="width: 28%">Nombre de quien presenta el informe</td><td>{{ $pendiente($informe?->aprobadoPor?->nombre_completo ?? $coordinador?->nombre_completo) }}</td><td class="enf-blue" style="width: 18%">Fecha de elaboracion</td><td>{{ $fecha($informe?->fecha_presentacion ?? now()) }}</td></tr>
            <tr><td class="enf-blue">Estado del informe</td><td>{{ $informe?->estado ?? 'Borrador sin registro final' }}</td><td class="enf-blue">Fecha de aprobacion</td><td>{{ $fecha($informe?->fecha_aprobacion) }}</td></tr>
        </table>

        <div class="enf-section">II. Informacion general de la accion de Educacion No Formal</div>
        <table class="enf-table enf-small">
            <tr><td class="enf-blue" style="width: 24%">Tipo de accion</td><td colspan="3">{{ $catalogText('tipo_accion_enf') ?: ($accion->tipoAccion?->nombre ?? 'Educacion No Formal') }}</td></tr>
            <tr><td class="enf-blue">Nombre de la accion</td><td colspan="3">{{ $accion->nombre_accion }}</td></tr>
            <tr><td class="enf-label">Codigo / registro</td><td>{{ $accion->numero_registro ?: ($certificado?->codigo_certificado ?: 'Pendiente') }}</td><td class="enf-label">Resolucion VRA</td><td>{{ $accion->resolucion_vra ?: 'No aplica' }}</td></tr>
            <tr><td class="enf-label">Resolucion programa original</td><td>{{ $accion->resolucion_original ?: 'No aplica' }}</td><td class="enf-label">Resolucion ultima actualizacion</td><td>{{ $accion->resolucion_actualizacion ?: 'No aplica' }}</td></tr>
            <tr><td class="enf-blue" rowspan="4">Unidad academica</td><td class="enf-label">Facultad / Centro Regional / Instituto</td><td colspan="2">{{ $accion->unidad_academica_responsable_texto ?: $accion->centroFacultad?->nombre }}</td></tr>
            <tr><td class="enf-label">Escuela / Departamento</td><td colspan="2">{{ $accion->escuela_departamento_texto ?: $accion->departamentoAcademico?->nombre }}</td></tr>
            <tr><td class="enf-label">Carrera</td><td colspan="2">{{ $accion->carrera?->nombre }}</td></tr>
            <tr><td class="enf-label">Programa de vinculacion</td><td colspan="2">{{ $accion->programa_vinculacion ?? '' }}</td></tr>
            <tr><td class="enf-blue">Modalidad</td><td colspan="3">{{ $accion->modalidad?->nombre ?: ($lugar?->modalidad_ejecucion ?: 'Pendiente') }}</td></tr>
        </table>
        <table class="enf-table enf-small">
            <tr><th colspan="5" class="enf-blue">Fecha de ejecucion y duracion</th></tr>
            <tr><th>Inicio</th><th>Finalizacion</th><th>Horas teoricas</th><th>Horas practicas</th><th>Total horas</th></tr>
            <tr class="enf-center"><td>{{ $fecha($accion->fecha_inicio) }}</td><td>{{ $fecha($accion->fecha_finalizacion) }}</td><td>{{ $num($accion->horas_teoricas) }}</td><td>{{ $num($accion->horas_practicas) }}</td><td>{{ $num($accion->total_horas ?: $accion->horas_teoricas + $accion->horas_practicas) }}</td></tr>
        </table>
        <table class="enf-table enf-small">
            <tr><th colspan="4" class="enf-blue">Sitio de ejecucion de la accion</th></tr>
            <tr><td class="enf-label">Departamento</td><td>{{ $lugar?->departamento?->nombre }}</td><td class="enf-label">Municipio</td><td>{{ $lugar?->municipio?->nombre }}</td></tr>
            <tr><td class="enf-label">Aldea / caserio</td><td>{{ $lugar?->aldea_caserio }}</td><td class="enf-label">Pais</td><td>Honduras</td></tr>
            <tr><td class="enf-label">Direccion exacta</td><td colspan="3">{{ $lugar?->direccion ?: $lugar?->nombre_lugar }}</td></tr>
            <tr><td class="enf-label">Plataformas</td><td colspan="3">{{ $lugar?->descripcion_plataformas ?: collect([$lugar?->plataforma, $lugar?->url_acceso])->filter()->implode(' / ') }}</td></tr>
        </table>
    </main>
</section>

<section class="enf-final-page">
    @php $pageChrome(); @endphp
    <main class="enf-final-content">
        <div class="enf-section">III. Equipo ejecutor de la accion</div>
        <table class="enf-table">
            <tr><th colspan="4" class="enf-blue">Coordinador/a de la accion</th></tr>
            <tr><td class="enf-label">Nombre completo</td><td>{{ $coordinador?->nombre_completo }}</td><td class="enf-label">No. empleado/a</td><td>{{ $coordinador?->numero_empleado }}</td></tr>
            <tr><td class="enf-label">Correo electronico</td><td>{{ $coordinador?->correo }}</td><td class="enf-label">Celular</td><td>{{ $coordinador?->celular }}</td></tr>
            <tr><td class="enf-label">Categoria</td><td>{{ $coordinador?->categoria }}</td><td class="enf-label">Departamento</td><td>{{ $coordinador?->departamento }}</td></tr>
        </table>
        <table class="enf-table enf-small">
            <thead><tr><th colspan="7" class="enf-blue">Integrantes del equipo docente / conferencistas</th></tr><tr><th style="width:5%">No.</th><th>Nombre</th><th>Correo</th><th>Pais</th><th>Grado academico</th><th>Universidad</th><th>Tipo participacion</th></tr></thead>
            <tbody>
                @forelse($docentes as $row)
                    <tr><td class="enf-center">{{ $loop->iteration }}</td><td>{{ $row->nombre_completo }}</td><td>{{ $row->correo }}</td><td>{{ $row->pais_procedencia ?: $row->nacionalidad }}</td><td>{{ $row->ultimo_titulo ?: $row->profesion }}</td><td>{{ $row->universidad_procedencia }}</td><td>{{ $row->perfil_docente ?: $row->rol }}</td></tr>
                @empty
                    <tr><td colspan="7" class="enf-empty">Sin docentes/conferencistas registrados.</td></tr>
                @endforelse
            </tbody>
        </table>
        <table class="enf-table enf-small">
            <thead><tr><th colspan="6" class="enf-blue">Detalle del equipo de voluntariado</th></tr><tr><th style="width:5%">No.</th><th>Nombre</th><th>Identificacion</th><th>Correo</th><th>Categoria</th><th>Tiempo trabajado</th></tr></thead>
            <tbody>
                @forelse($voluntarios as $row)
                    <tr><td class="enf-center">{{ $loop->iteration }}</td><td>{{ $row->nombre_completo }}</td><td>{{ $row->identidad ?: $row->numero_empleado }}</td><td>{{ $row->correo }}</td><td>{{ $row->rol }}</td><td>{{ $row->horas_dedicadas }}</td></tr>
                @empty
                    <tr><td colspan="6" class="enf-empty">Sin voluntariado registrado.</td></tr>
                @endforelse
            </tbody>
        </table>

        <div class="enf-section">IV. Beneficiarios y participantes</div>
        <table class="enf-table enf-small">
            <tr><th colspan="3" class="enf-blue">Beneficiarios directos programados</th><th colspan="3" class="enf-blue">Participantes que finalizaron</th></tr>
            <tr><th>Hombres</th><th>Mujeres</th><th>Total</th><th>Hombres</th><th>Mujeres</th><th>Total</th></tr>
            <tr class="enf-center"><td>{{ $beneficiarios?->hombres ?? 0 }}</td><td>{{ $beneficiarios?->mujeres ?? 0 }}</td><td>{{ $totalParticipantesPlanificados }}</td><td>{{ $countSexo($participantesFinales, 'hombres') }}</td><td>{{ $countSexo($participantesFinales, 'mujeres') }}</td><td>{{ $finalizaron }}</td></tr>
        </table>
        <table class="enf-table enf-small">
            <tr><th>No.</th><th>Concepto</th><th>Hombres</th><th>Mujeres</th><th>Total</th><th>Observacion</th></tr>
            <tr><td>1</td><td>Personas matriculadas / inscritas</td><td>{{ $inscritos['hombres'] }}</td><td>{{ $inscritos['mujeres'] }}</td><td>{{ $inscritos['total'] }}</td><td>Registro real de cierre.</td></tr>
            <tr><td>2</td><td>No se presentaron</td><td>{{ $noPresentaron['hombres'] }}</td><td>{{ $noPresentaron['mujeres'] }}</td><td>{{ $noPresentaron['total'] }}</td><td></td></tr>
            <tr><td>3</td><td>Abandonaron</td><td>{{ $abandonaron['hombres'] }}</td><td>{{ $abandonaron['mujeres'] }}</td><td>{{ $abandonaron['total'] }}</td><td></td></tr>
            <tr><td>4</td><td>Reprobaron</td><td>{{ $reprobaron['hombres'] }}</td><td>{{ $reprobaron['mujeres'] }}</td><td>{{ $reprobaron['total'] }}</td><td></td></tr>
            <tr><td>5</td><td>Aprobaron / participaron en toda la actividad</td><td>{{ $aprobaron['hombres'] }}</td><td>{{ $aprobaron['mujeres'] }}</td><td>{{ $aprobaron['total'] }}</td><td>{{ $esForm018 ? 'Registro real de cierre.' : $certificados.' con certificado emitido.' }}</td></tr>
            <tr><td>6</td><td>No. de graduados UNAH que aprobaron la actividad</td><td>{{ $graduadosUnah['hombres'] }}</td><td>{{ $graduadosUnah['mujeres'] }}</td><td>{{ $graduadosUnah['total'] }}</td><td></td></tr>
        </table>
        <table class="enf-table enf-small">
            <thead><tr><th colspan="5" class="enf-blue">{{ $participantesTitulo }}</th></tr><tr><th style="width:5%">No.</th><th>Nombre</th><th>Correo electronico</th><th>Telefono / documento</th><th>{{ $esForm018 ? 'Estado' : 'Codigo certificado' }}</th></tr></thead>
            <tbody>
                @forelse($participantesFinales as $row)
                    <tr><td class="enf-center">{{ $loop->iteration }}</td><td>{{ $row->nombre_completo }}</td><td>{{ $row->correo }}</td><td>{{ $row->documento_identidad }}</td><td>{{ $esForm018 ? 'Finalizo' : ($row->codigo_certificado ?: ($row->certificado_emitido ? 'Emitido' : 'Pendiente')) }}</td></tr>
                @empty
                    <tr><td colspan="5" class="enf-empty">Sin participantes finales registrados.</td></tr>
                @endforelse
            </tbody>
        </table>
    </main>
</section>

<section class="enf-final-page">
    @php $pageChrome(); @endphp
    <main class="enf-final-content">
        <div class="enf-section">V. Informe de ejecucion de las acciones planificadas</div>
        <table class="enf-table">
            <tr><td class="enf-label" style="width: 24%">Resumen ejecutivo</td><td class="enf-field">{{ $informe?->resumen_ejecutivo ?: $accion->resumen }}</td></tr>
            <tr><td class="enf-label">Resultados obtenidos</td><td class="enf-field">{{ $informe?->resultados_obtenidos }}</td></tr>
            <tr><td class="enf-label">Limitaciones</td><td class="enf-field">{{ $informe?->limitaciones }}</td></tr>
        </table>
        <table class="enf-table enf-small">
            <thead><tr><th>No.</th><th>Objetivo planteado</th><th>Resultado previsto</th><th>Accion desarrollada</th><th>Resultados / observaciones</th></tr></thead>
            <tbody>
                @forelse($accionesEjecutadas as $row)
                    <tr><td class="enf-center">{{ $loop->iteration }}</td><td>{{ $accion->objetivo_general }}</td><td>{{ $row->actividad }}</td><td>{{ $row->actividad }}</td><td>{{ $row->resultados ?: $row->observaciones }}</td></tr>
                @empty
                    @forelse($accion->cronograma as $row)
                        <tr><td class="enf-center">{{ $loop->iteration }}</td><td>{{ $accion->objetivo_general }}</td><td>{{ $row->producto }}</td><td>{{ $row->actividad }}</td><td>{{ $row->estado ?: 'Planificada' }}</td></tr>
                    @empty
                        <tr><td colspan="5" class="enf-empty">Sin acciones planificadas registradas.</td></tr>
                    @endforelse
                @endforelse
            </tbody>
        </table>
        <table class="enf-table">
            <tr><td class="enf-blue" style="width: 32%">Contenido curricular de la accion</td><td class="enf-field">Indique si se realizaron cambios en el contenido teorico o metodologico: {{ $informe?->contenido_curricular_cambios ?: 'Pendiente' }}</td></tr>
            <tr><td class="enf-blue">Cronograma de desarrollo de la accion</td><td class="enf-field">Indique cambios del cronograma: {{ $informe?->cronograma_cambios ?: 'Pendiente' }}</td></tr>
            <tr><td class="enf-blue">{{ $modalidadCierreLabel }}</td><td>{{ $modalidadCierreValor }}</td></tr>
            <tr><td class="enf-blue">Seguimiento</td><td class="enf-field">{{ $informe?->seguimiento_sistematizacion ?: 'Pendiente' }}</td></tr>
        </table>

        <div class="enf-section">VI. Reporte de acciones planificadas que no fueron ejecutadas</div>
        <table class="enf-table enf-small">
            <thead><tr><th>Resultado previsto</th><th>Actividad planificada</th><th>Breve explicacion</th><th>Afectacion a la accion</th><th>Fecha reprogramacion</th></tr></thead>
            <tbody>
                @forelse($accionesNoEjecutadas as $row)
                    <tr><td>{{ $row->actividad }}</td><td>{{ $row->actividad }}</td><td>{{ $row->motivo }}</td><td>{{ $row->acciones_correctivas }}</td><td>{{ $fecha($row->fecha_reprogramacion) }}</td></tr>
                @empty
                    <tr><td colspan="5" class="enf-empty">No se registraron acciones no ejecutadas.</td></tr>
                @endforelse
            </tbody>
        </table>

        <div class="enf-section">VII. Reflexion</div>
        <table class="enf-table">
            <tr><td class="enf-label" style="width: 30%">Dificultades</td><td class="enf-field">{{ $informe?->dificultades ?: $informe?->limitaciones }}</td></tr>
            <tr><td class="enf-label">Lecciones aprendidas</td><td class="enf-field">{{ $informe?->lecciones_aprendidas }}</td></tr>
            <tr><td class="enf-label">Buenas practicas</td><td class="enf-field">{{ $informe?->buenas_practicas }}</td></tr>
            <tr><td class="enf-label">Cambios logrados</td><td class="enf-field">{{ $informe?->transformacion_lograda ?: $informe?->resultados_obtenidos }}</td></tr>
            <tr><td class="enf-label">ODS a los que se contribuyo</td><td>{{ $odsTexto ?: 'Pendiente' }}</td></tr>
            <tr><td class="enf-label">Metas a las que se contribuyo</td><td>{{ $metasTexto ?: 'Pendiente' }}</td></tr>
            <tr><td class="enf-label">Recomendaciones</td><td class="enf-field">{{ $informe?->recomendaciones }}</td></tr>
            <tr><td class="enf-label">Bibliografia</td><td class="enf-field">{{ $accion->bibliografia }}</td></tr>
        </table>
        <h3 class="enf-subtitle">Resultados de la valoracion por la comunidad beneficiada</h3>
        <table class="enf-table enf-small">
            @php $muestraValoracion = max(0, (int) ($informe?->valoracion_muestra ?? 0)); @endphp
            <tr><th>Total beneficiarios</th><th>Total muestra</th><th>Excelente</th><th>Muy buena</th><th>Regular</th><th>Mala</th></tr>
            <tr class="enf-center"><td>{{ $informe?->valoracion_total_beneficiarios ?? 0 }}</td><td>{{ $muestraValoracion }}</td><td>{{ $informe?->valoracion_excelente ?? 0 }} ({{ $muestraValoracion ? number_format(($informe?->valoracion_excelente ?? 0) / $muestraValoracion * 100, 2) : '0.00' }}%)</td><td>{{ $informe?->valoracion_muy_buena ?? 0 }} ({{ $muestraValoracion ? number_format(($informe?->valoracion_muy_buena ?? 0) / $muestraValoracion * 100, 2) : '0.00' }}%)</td><td>{{ $informe?->valoracion_regular ?? 0 }} ({{ $muestraValoracion ? number_format(($informe?->valoracion_regular ?? 0) / $muestraValoracion * 100, 2) : '0.00' }}%)</td><td>{{ $informe?->valoracion_mala ?? 0 }} ({{ $muestraValoracion ? number_format(($informe?->valoracion_mala ?? 0) / $muestraValoracion * 100, 2) : '0.00' }}%)</td></tr>
            <tr><td class="enf-label">Observaciones</td><td colspan="5" class="enf-field">{{ $informe?->observaciones_finales }}</td></tr>
        </table>
    </main>
</section>

<section class="enf-final-page">
    @php $pageChrome(); @endphp
    <main class="enf-final-content">
        <div class="enf-section">VIII. Ejecucion presupuestaria</div>
        <table class="enf-table enf-small">
            <thead><tr><th colspan="5" class="enf-blue">Ingresos (en lempiras)</th></tr><tr><th>Concepto</th><th>Unidad</th><th>Cantidad</th><th>Costo unitario</th><th>Total</th></tr></thead>
            <tbody>
                @forelse($ingresos?->detalles ?? [] as $row)
                    <tr><td>{{ $row->rubro }}</td><td>{{ $row->descripcion ?: 'Unidad' }}</td><td class="enf-center">{{ $row->cantidad }}</td><td>{{ $money($row->costo_unitario) }}</td><td>{{ $money($row->total) }}</td></tr>
                @empty
                    <tr><td colspan="5" class="enf-empty">Sin ingresos registrados.</td></tr>
                @endforelse
                <tr><td colspan="4" class="enf-right enf-gray">Total ingresos</td><td class="enf-gray">{{ $money($totalIngresos) }}</td></tr>
            </tbody>
        </table>
        <table class="enf-table enf-small">
            <thead><tr><th colspan="5" class="enf-blue">Egresos (en lempiras)</th></tr><tr><th>Concepto</th><th>Unidad</th><th>Cantidad</th><th>Costo unitario</th><th>Total</th></tr></thead>
            <tbody>
                @forelse($egresos?->detalles ?? [] as $row)
                    <tr><td>{{ $row->rubro }}</td><td>{{ $row->descripcion ?: 'Unidad' }}</td><td class="enf-center">{{ $row->cantidad }}</td><td>{{ $money($row->costo_unitario) }}</td><td>{{ $money($row->total) }}</td></tr>
                @empty
                    <tr><td colspan="5" class="enf-empty">Sin egresos registrados.</td></tr>
                @endforelse
                <tr><td colspan="4" class="enf-right enf-gray">Total egresos</td><td class="enf-gray">{{ $money($totalEgresos) }}</td></tr>
                <tr><td colspan="4" class="enf-right enf-blue">Excedente</td><td class="enf-blue">{{ $money($totalIngresos - $totalEgresos) }}</td></tr>
            </tbody>
        </table>
        <table class="enf-table enf-small">
            <thead><tr><th colspan="5" class="enf-blue">Aporte en especie de la UNAH</th></tr><tr><th>Concepto</th><th>Unidad</th><th>Cantidad</th><th>Costo unitario</th><th>Total</th></tr></thead>
            <tbody>
                @forelse($aporteUnah?->detalles ?? [] as $row)
                    <tr><td>{{ $row->rubro }}</td><td>{{ $row->descripcion ?: 'Global' }}</td><td class="enf-center">{{ $row->cantidad }}</td><td>{{ $money($row->costo_unitario) }}</td><td>{{ $money($row->total) }}</td></tr>
                @empty
                    <tr><td colspan="5" class="enf-empty">Sin aporte UNAH registrado.</td></tr>
                @endforelse
                <tr><td colspan="4" class="enf-right enf-gray">Total aporte institucional</td><td class="enf-gray">{{ $money($totalAporteUnah) }}</td></tr>
            </tbody>
        </table>

        <div class="enf-section">IX. Firmas</div>
        <table class="enf-table">
            <tr><th class="enf-blue">Coordinador de la accion por la UNAH</th><th class="enf-blue">Jefe de la Unidad Academica que lidera la accion</th></tr>
            <tr><td class="enf-signature">Nombre: {{ $coordinador?->nombre_completo }}<br><br>Firma del profesor/a responsable de la accion</td><td class="enf-signature">Nombre:<br><br>Firma del Jefe/a de la Unidad Academica</td></tr>
            <tr><th class="enf-blue">Coordinador(a) del Comite de Vinculacion</th><th class="enf-blue">Decano(a) o Director(a) del Centro Regional</th></tr>
            <tr><td class="enf-signature">Nombre:<br><br>Firma del coordinador del Comite Local</td><td class="enf-signature">Nombre:<br><br>Firma y sello del Decano(a) o Director(a)</td></tr>
        </table>

        <div class="enf-section">X. Anexos</div>
        <p class="enf-muted">Requisito indispensable para el cierre: listados de asistencia, ayudas memoria, bases de encuestas, informes de resultados, manuales, fotografias, afiches, agendas, correspondencia y cualquier respaldo de la accion.</p>
        <table class="enf-table enf-small">
            <thead><tr><th style="width: 6%">No.</th><th>Documento</th><th>Tipo</th><th>Estado</th></tr></thead>
            <tbody>
                @forelse($accion->documentos as $row)
                    <tr><td class="enf-center">{{ $loop->iteration }}</td><td>{{ $row->nombre }}</td><td>{{ $row->tipo_documento }}</td><td>{{ $row->ruta && $row->ruta !== 'pendiente' ? 'Adjunto' : 'Pendiente' }}</td></tr>
                @empty
                    <tr><td colspan="4" class="enf-empty">Sin anexos/documentos registrados.</td></tr>
                @endforelse
            </tbody>
        </table>
    </main>
</section>
