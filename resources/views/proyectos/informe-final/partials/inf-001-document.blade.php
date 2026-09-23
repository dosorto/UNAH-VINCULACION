@php
    $isPdf = $isPdf ?? false;
    $esBorrador = $esBorrador ?? true;
    $firmas = $firmas ?? ['coordinador' => null, 'jefe' => null, 'enlace' => null, 'decano' => null];
    $coordinadorProyecto = $coordinadorProyecto ?? null;
    $beneficiarios = $informe->beneficiarios;
    $activos = fn ($rows) => $rows->filter(fn ($row) => ($row->estado_participacion ?? 'activo') === 'activo')->values();
    $equipoEjecutor = $activos($informe->equipoDocente);
    $coordinador = $equipoEjecutor->firstWhere('es_coordinador', true);
    $docentes = $equipoEjecutor->reject(fn ($row) => $row->es_coordinador)->values();
    $cooperacion = $activos($informe->cooperacion);
    $estudiantes = $activos($informe->estudiantes);
    $voluntarios = $activos($informe->voluntarios);
    $gruposEstudiantesObservados = $informe->gruposEstudiantes->filter(function ($grupo) use ($estudiantes) {
        $registrados = $estudiantes->where('informe_final_grupo_estudiante_id', $grupo->id);
        $hombresRegistrados = $registrados->where('sexo', 'Masculino')->count();
        $mujeresRegistradas = $registrados->where('sexo', 'Femenino')->count();
        $tienePendientes = $hombresRegistrados < $grupo->hombres_planificados || $mujeresRegistradas < $grupo->mujeres_planificadas;
        return $tienePendientes && filled($grupo->observacion_no_cumplimiento);
    });
    // VI: solo las actividades realizadas (ejecutadas o parciales); las no ejecutadas van en VII.
    $actividades = $informe->actividades->where('origen', 'planificada')->whereIn('estado', ['ejecutada', 'parcial'])->values();
    $catalogoX = \App\Support\InformeFinal\ConceptosPresupuestoInf001::class;
    $marcasInternasOrigen = ['registro_proyecto', 'contrapartes_proyecto'];
    // XI. Firmas: [título, pie] de cada cuadro, tal como aparecen en el formato.
    $cuadrosFirmaInf = [
        'coordinador' => ['Coordinador del proyecto por la UNAH', 'Firma del profesor/a responsable del proyecto'],
        'jefe' => ['Jefe de la Unidad Académica que lidera el proyecto', 'Firma del Jefe/a de la Unidad Académica que lidera el proyecto'],
        'enlace' => ['Coordinador(a) del Comité de Vinculación de la Facultad o Unidad de Vinculación del Centro Regional', 'Firma del coordinador del Comité Local'],
        'decano' => ['Decano(a) o Director(a) del Centro Regional', 'Firma y sello del Decano(a) o Director(a)'],
    ];
    $tiposContraparteFormato = ['gobierno_nacional' => 'Gobierno Nacional', 'gobierno_municipal' => 'Gobierno Municipal', 'ong' => 'ONG', 'sociedad_civil' => 'Sociedad civil organizada', 'sector_privado' => 'Sector Privado', 'internacional' => 'Internacional'];
    $tiposInstrumentoFormato = ['carta_formal' => 'Carta formal de solicitud a la unidad académica', 'carta_intenciones' => 'Carta de intenciones con la UNAH', 'convenio_marco' => 'Convenio marco con la UNAH'];
    $tiposAnexoFormato = ['materiales' => 'Material generado por el proyecto', 'encuestas' => 'Formularios de encuestas', 'procesamiento' => 'Informes de procesamiento de datos', 'fotografias' => 'Fotografías (enlace a carpeta digital)', 'videos' => 'Videos cortos del proyecto', 'difusion' => 'Evidencias de difusión', 'bitacoras' => 'Bitácoras de estudiantes', 'asistencia' => 'Listas de asistencia', 'manuales' => 'Manuales', 'guias' => 'Guías', 'actas' => 'Actas', 'otros' => 'Otros'];
    /** Filas del apartado X por fuente: [código oficial => filas] y las filas sin concepto oficial. */
    $filasX = function (string $fuente) use ($informe, $catalogoX) {
        $filas = $informe->presupuestoDetalles->where('fuente', $fuente)
            ->map(fn ($fila) => ['fila' => $fila, 'codigo' => $catalogoX::codigoDeFila($fuente, $fila->concepto_codigo, $fila->concepto)]);
        return [$filas->whereNotNull('codigo')->groupBy('codigo'), $filas->whereNull('codigo')->pluck('fila')];
    };
    $normalizar = fn ($value) => mb_strtolower((string) $value);
    $marcar = fn ($value, string $needle) => str_contains($normalizar($value), $normalizar($needle)) ? '☒' : '☐';
    $fecha = fn ($value) => blank($value) ? '' : ($value instanceof \DateTimeInterface ? $value->format('d/m/Y') : \Illuminate\Support\Carbon::parse($value)->format('d/m/Y'));
    $moneda = fn ($value) => 'L '.number_format((float) $value, 2, '.', ',');
    $porcentaje = fn ($value) => number_format((float) $value, 2, '.', ',').'%';
    $tipoEstudiante = fn ($tipo) => \App\Support\InformeFinal\ParticipacionEstudiantil::codigo((string) $tipo);
    $asignaturaEstudiante = fn ($row) => $row->grupo?->asignatura
        ? collect([$row->grupo->asignatura->codigo, $row->grupo->asignatura->nombre])->filter()->implode(' - ').($row->grupo->periodo_academico ? ' · '.$row->grupo->periodo_academico : '')
        : '';
    $tipoVoluntario = ['profesor_hora' => 'PH', 'pas' => 'PAS', 'profesor_permanente' => 'PP', 'egresado' => 'EGR'];
    $hombres = fn ($rows) => $rows->filter(fn ($row) => in_array($normalizar($row->sexo), ['masculino', 'm'], true))->count();
    $mujeres = fn ($rows) => $rows->filter(fn ($row) => in_array($normalizar($row->sexo), ['femenino', 'f'], true))->count();
    $empleadosActivos = $equipoEjecutor->pluck('empleado_id')->filter()->map(fn ($id) => (int) $id);
    $estudiantesActivos = $estudiantes->pluck('id')->map(fn ($id) => (int) $id);
    $voluntariosActivos = $voluntarios->pluck('id')->map(fn ($id) => (int) $id);
    $responsable = function ($actividad) use ($empleadosActivos, $estudiantesActivos, $voluntariosActivos) {
        $participantes = $actividad->participantes->filter(fn ($row) => match ($row->tipo) {
            'docente' => $empleadosActivos->contains((int) $row->empleado_id),
            'estudiante' => $estudiantesActivos->contains((int) $row->informe_final_estudiante_id),
            'voluntario' => $voluntariosActivos->contains((int) $row->informe_final_voluntario_id),
            'externo' => true,
            default => false,
        });
        return $participantes->firstWhere('es_responsable', true)?->nombre
            ?: $participantes->first()?->nombre
            ?: ($actividad->participantes->isEmpty() ? $actividad->responsable : '');
    };
    $muestra = max(0, (int) $informe->valoracion_muestra);
    $instrumentosContraparte = $informe->anexos->where('categoria', 'instrumento_contraparte')->sortBy('orden')->values();
    $documentosGenerales = $informe->anexos->where('categoria', 'documento_general')->sortBy('orden')->values();
    $fotografias = $informe->anexos->where('categoria', 'fotografia')->sortBy('orden')->values();
    $rutaAnexo = function ($row) use ($isPdf) {
        $ruta = $row->archivo ?: $row->enlace;
        if (! $ruta) return null;
        if (filter_var($ruta, FILTER_VALIDATE_URL)) return $ruta;

        $rutaNormalizada = ltrim((string) $ruta, '/');
        if (str_starts_with($rutaNormalizada, 'storage/')) {
            $rutaNormalizada = substr($rutaNormalizada, strlen('storage/'));
        }

        $rutaDisco = storage_path('app/public/'.$rutaNormalizada);
        if (is_file($rutaDisco) || \Illuminate\Support\Facades\Storage::disk('public')->exists($rutaNormalizada)) {
            return $isPdf ? 'file://'.$rutaDisco : '/storage/'.$rutaNormalizada;
        }

        $rutaPublica = public_path('storage/'.$rutaNormalizada);
        if (is_file($rutaPublica)) {
            return $isPdf ? 'file://'.$rutaPublica : '/storage/'.$rutaNormalizada;
        }

        return null;
    };
@endphp

<style>
    @page { margin: 0; }
    * { box-sizing: border-box; }
    html, body { margin: 0; color: #111; font-family: Arial, "Liberation Sans", "DejaVu Sans", sans-serif; font-size: 8pt; line-height: 1.2; }
    @if($isPdf)
        body { padding: 78pt 30pt 42pt 30pt; }
    @else
        body { padding: 0; }
    @endif
    .inf001-document { position: relative; z-index: 1; width: 100%; }
    .inf001-title { margin: 0 0 10pt; padding: 5pt 7pt; background: #002060; color: #fff; font-size: 10pt; text-align: center; text-transform: uppercase; }
    .inf-section-title { margin: 10pt 0 5pt; padding: 4pt 6pt; background: #002060; color: #fff; font-size: 8.5pt; text-transform: uppercase; page-break-after: avoid; }
    .inf-subtitle { margin: 7pt 0 3pt; color: #002060; font-size: 8pt; page-break-after: avoid; }
    .inf-table { width: 100%; margin: 0 0 6pt; border-collapse: collapse; table-layout: fixed; page-break-inside: auto; }
    .inf-table thead { display: table-header-group; }
    .inf-table tfoot { display: table-row-group; }
    .inf-table tr { page-break-inside: avoid; page-break-after: auto; }
    .inf-table th, .inf-table td { border: .55pt solid #7f8790; padding: 3pt 3.5pt; vertical-align: top; overflow-wrap: anywhere; }
    .inf-table th { background: #f2f2f2; color: #002060; font-weight: 700; text-align: left; }
    .inf-label { background: #f2f2f2; color: #111; font-weight: 700; }
    .inf-label-blue, .inf-header-blue { background: #002060 !important; color: #fff !important; font-weight: 700; }
    .inf-header-gray { background: #d9d9d9 !important; color: #111 !important; font-weight: 700; }
    .inf-center { text-align: center !important; }
    .inf-right { text-align: right !important; }
    .inf-small { font-size: 7pt; }
    .inf-nowrap { white-space: nowrap; }
    .inf-checks { line-height: 1.55; }
    .inf-narrative { min-height: 26pt; white-space: pre-wrap; }
    .inf-muted { color: #596273; font-size: 7pt; }
    .inf-empty { color: #596273; font-style: italic; }
    .inf-avoid { page-break-inside: avoid; }
    .inf-page-break { page-break-before: always; }
    .inf-signature-cell { height: 88pt; text-align: center; vertical-align: bottom !important; }
    .inf-signature-cell img { display: inline-block; max-width: 92pt; max-height: 48pt; }
    .inf-signature-name { margin-top: 3pt; padding-top: 2pt; border-top: .55pt solid #222; }
    .inf-photo-grid { width: 100%; font-size: 0; }
    .inf-photo-card { display: inline-block; width: 24%; margin: 0 1% 6pt 0; border: .55pt solid #7f8790; padding: 3pt; vertical-align: top; page-break-inside: avoid; font-size: 7pt; }
    .inf-photo-card img { display: block; width: 100%; height: 70pt; object-fit: cover; margin-bottom: 3pt; }
    @if(! $isPdf)
        .inf001-document { padding: 82pt 30pt 42pt; }
    @endif
</style>

@include('proyectos.informe-final.partials.inf-001-page-chrome')

<main class="inf001-document">
    <h1 class="inf001-title">INF-001 — Informe final de programas y proyectos de vinculación</h1>

    <h2 class="inf-section-title">I. Información general del proyecto</h2>
    <table class="inf-table">
        <tr><td class="inf-label-blue" style="width:24%">1. Nombre del Programa/Proyecto</td><td colspan="3">{{ $informe->nombre_proyecto }}</td></tr>
        <tr><td class="inf-label-blue">2. Número de registro</td><td>{{ $informe->numero_registro ?: 'Pendiente de asignación' }}</td><td class="inf-label-blue" style="width:22%">3. Fecha de registro</td><td>{{ $fecha($informe->fecha_registro) }}</td></tr>
        <tr><td class="inf-label-blue" rowspan="5">4. Unidad académica ejecutora</td><td class="inf-label">Facultad / Centro Regional / Instituto Tecnológico</td><td colspan="2">{{ $informe->facultad_centro }}</td></tr>
        <tr><td class="inf-label">Escuela / Departamento / Instituto / Observatorio / Consultorio / Centro especializado</td><td colspan="2">{{ $informe->departamento_academico }}</td></tr>
        <tr><td class="inf-label">Carrera</td><td colspan="2">{{ filled($informe->carrera) ? $informe->carrera : 'No aplica' }}</td></tr>
        <tr><td class="inf-label">Programa de vinculación</td><td colspan="2">{{ $informe->programa_vinculacion }}</td></tr>
        <tr><td class="inf-label">Línea de investigación</td><td colspan="2">{{ $informe->linea_investigacion }}</td></tr>
        <tr><td class="inf-label-blue">5. Modalidad</td><td colspan="3" class="inf-checks">{{ $marcar($informe->modalidad, 'unidisciplinar') }} Unidisciplinar &nbsp; {{ $marcar($informe->modalidad, 'multidisciplinar') }} Multidisciplinar &nbsp; {{ $marcar($informe->modalidad, 'interdisciplinar') }} Interdisciplinar &nbsp; {{ $marcar($informe->modalidad, 'transdisciplinar') }} Transdisciplinar</td></tr>
        <tr><td class="inf-label-blue">6. Alineamiento con ejes prioritarios de la UNAH</td><td colspan="3" class="inf-checks">{{ $marcar($informe->ejes_prioritarios, 'desarrollo económico') }} Desarrollo económico y social &nbsp; {{ $marcar($informe->ejes_prioritarios, 'democracia') }} Democracia y gobernabilidad &nbsp; {{ $marcar($informe->ejes_prioritarios, 'población') }} Población y condiciones de vida &nbsp; {{ $marcar($informe->ejes_prioritarios, 'ambiente') }} Ambiente, biodiversidad y desarrollo</td></tr>
        <tr><td class="inf-label-blue">7. Categoría del proyecto</td><td colspan="3" class="inf-checks inf-small">{{ $marcar($informe->categoria, 'desarrollo local') }} Desarrollo local y/o regional &nbsp; {{ $marcar($informe->categoria, 'seguimiento') }} Seguimiento a graduados &nbsp; {{ $marcar($informe->categoria, 'voluntariado') }} Voluntariado académico &nbsp; {{ $marcar($informe->categoria, 'cultura') }} Cultura &nbsp; {{ $marcar($informe->categoria, 'investigación') }} I+D+i / Investigación aplicada &nbsp; {{ $marcar($informe->categoria, 'comunicación') }} Comunicación &nbsp; {{ $marcar($informe->categoria, 'vínculos') }} Vínculos académicos</td></tr>
    </table>

    <table class="inf-table inf-avoid">
        <tr><th colspan="3" class="inf-header-blue">8. Plazo de ejecución</th></tr>
        <tr><th>Inicio</th><th>Finalización</th><th>Tiempo total del proyecto</th></tr>
        <tr class="inf-center"><td>{{ $fecha($informe->fecha_inicio) }}</td><td>{{ $fecha($informe->fecha_finalizacion) }}</td><td>{{ $informe->duracion_semanas }} semanas</td></tr>
    </table>

    <h3 class="inf-subtitle">9. Beneficiarios directos (indicar cantidades en números)</h3>
    <table class="inf-table inf-small">
        <thead><tr><th colspan="2">Cantidad por sexo</th><th colspan="8">Cantidad por rango de edad</th></tr><tr><th>Hombres</th><th>Mujeres</th><th>0–10</th><th>11–18</th><th>19–25</th><th>26–35</th><th>36–50</th><th>51–65</th><th>66–80</th><th>Mayor de 81</th></tr></thead>
        <tbody><tr class="inf-center"><td>{{ $beneficiarios?->hombres ?? 0 }}</td><td>{{ $beneficiarios?->mujeres ?? 0 }}</td><td>{{ $beneficiarios?->edad_0_10 ?? 0 }}</td><td>{{ $beneficiarios?->edad_11_18 ?? 0 }}</td><td>{{ $beneficiarios?->edad_19_25 ?? 0 }}</td><td>{{ $beneficiarios?->edad_26_35 ?? 0 }}</td><td>{{ $beneficiarios?->edad_36_50 ?? 0 }}</td><td>{{ $beneficiarios?->edad_51_65 ?? 0 }}</td><td>{{ $beneficiarios?->edad_66_80 ?? 0 }}</td><td>{{ $beneficiarios?->edad_81_mas ?? 0 }}</td></tr></tbody>
    </table>
    <table class="inf-table inf-small inf-avoid">
        <tr><th colspan="6">Cantidad por tipo de etnia</th></tr><tr><th colspan="2">Indígena</th><th colspan="2">Afrodescendiente</th><th colspan="2">Mestizo</th></tr><tr><th>Hombres</th><th>Mujeres</th><th>Hombres</th><th>Mujeres</th><th>Hombres</th><th>Mujeres</th></tr>
        <tr class="inf-center"><td>{{ $beneficiarios?->indigena_hombres ?? 0 }}</td><td>{{ $beneficiarios?->indigena_mujeres ?? 0 }}</td><td>{{ $beneficiarios?->afrodescendiente_hombres ?? 0 }}</td><td>{{ $beneficiarios?->afrodescendiente_mujeres ?? 0 }}</td><td>{{ $beneficiarios?->mestizo_hombres ?? 0 }}</td><td>{{ $beneficiarios?->mestizo_mujeres ?? 0 }}</td></tr>
    </table>
    <table class="inf-table inf-avoid"><tr><th colspan="4" class="inf-header-blue">10. Sitio de ejecución del proyecto</th></tr><tr><td class="inf-label">Departamento</td><td>{{ $informe->departamento_territorial }}</td><td class="inf-label">Aldea (incluye ciudad)</td><td>{{ $informe->aldea_ciudad }}</td></tr><tr><td class="inf-label">Municipio</td><td>{{ $informe->municipio }}</td><td class="inf-label">Caserío</td><td>{{ $informe->caserio }}</td></tr><tr><td class="inf-label">Región</td><td>{{ $informe->region }}</td><td class="inf-label">País</td><td>{{ is_array($informe->pais) ? implode(', ', $informe->pais) : $informe->pais }}</td></tr></table>

    <h2 class="inf-section-title">II. Equipo ejecutor del proyecto</h2>
    <h3 class="inf-subtitle">Coordinador/a del Proyecto</h3>
    <table class="inf-table inf-avoid"><tr><td class="inf-label">Nombre completo</td><td>{{ $coordinador?->nombre }}</td><td class="inf-label">No. de empleado/a</td><td>{{ $coordinador?->numero_empleado }}</td></tr><tr><td class="inf-label">Correo electrónico</td><td>{{ $coordinador?->correo }}</td><td class="inf-label">Celular</td><td>{{ $coordinadorProyecto?->celular }}</td></tr><tr><td class="inf-label">Categoría</td><td>{{ $coordinador?->categoria }}</td><td class="inf-label">Horas dedicadas</td><td>{{ $coordinador?->horas_dedicadas }}</td></tr><tr><td class="inf-label">Departamento al que pertenece</td><td colspan="3">{{ $coordinador?->departamento }}</td></tr></table>

    <h3 class="inf-subtitle">1. Integrantes del equipo docente permanente tiempo completo</h3>
    <table class="inf-table inf-small"><thead><tr><th colspan="7">Total de profesores(as) que participaron como integrantes principales del proyecto: {{ $docentes->count() }}</th></tr><tr><th style="width:5%">N°</th><th>Nombre completo</th><th>No. de empleado/a</th><th>Correo electrónico</th><th>Categoría</th><th>Departamento al que pertenece</th><th>Horas dedicadas</th></tr></thead><tbody>@forelse($docentes as $row)<tr><td class="inf-center">{{ $loop->iteration }}</td><td>{{ $row->nombre }}</td><td>{{ $row->numero_empleado }}</td><td>{{ $row->correo }}</td><td>{{ $row->categoria }}</td><td>{{ $row->departamento }}</td><td>{{ $row->horas_dedicadas }}</td></tr>@empty<tr><td colspan="7" class="inf-empty">Sin integrantes adicionales registrados.</td></tr>@endforelse</tbody></table>

    <h3 class="inf-subtitle">2. Integrantes del equipo de cooperación internacional / otras universidades</h3>
    <table class="inf-table inf-small"><thead><tr><th colspan="7">Cantidad de integrantes: {{ $cooperacion->count() }}</th></tr><tr><th style="width:5%">N°</th><th>Nombre completo</th><th>Pasaporte</th><th>Correo electrónico</th><th>País</th><th>Universidad</th><th>Horas dedicadas</th></tr></thead><tbody>@forelse($cooperacion as $row)<tr><td class="inf-center">{{ $loop->iteration }}</td><td>{{ $row->nombre }}</td><td>{{ $row->pasaporte }}</td><td>{{ $row->correo }}</td><td>{{ $row->pais }}</td><td>{{ $row->universidad }}</td><td>{{ $row->horas_dedicadas }}</td></tr>@empty<tr><td colspan="7" class="inf-empty">Sin cooperación internacional registrada.</td></tr>@endforelse</tbody></table>

    <h2 class="inf-section-title">III. Cuantificación de participación de estudiantes</h2>
    <table class="inf-table inf-small inf-avoid"><tr><th rowspan="2">Participación de estudiantes</th><th colspan="2">Total</th><th colspan="6">Desglose del tipo de participación de estudiantes (expresado en números)</th></tr><tr><th>Hombres</th><th>Mujeres</th><th colspan="2">Práctica de asignatura</th><th colspan="2">Servicio Social o PPS</th><th colspan="2">Voluntariado</th></tr><tr class="inf-center"><td>Expresado en números</td><td>{{ $hombres($estudiantes) }}</td><td>{{ $mujeres($estudiantes) }}</td><td>H {{ $hombres($estudiantes->where('tipo_participacion', 'practica_asignatura')) }}</td><td>M {{ $mujeres($estudiantes->where('tipo_participacion', 'practica_asignatura')) }}</td><td>H {{ $hombres($estudiantes->where('tipo_participacion', 'pps_servicio_social')) }}</td><td>M {{ $mujeres($estudiantes->where('tipo_participacion', 'pps_servicio_social')) }}</td><td>H {{ $hombres($estudiantes->where('tipo_participacion', 'voluntariado')) }}</td><td>M {{ $mujeres($estudiantes->where('tipo_participacion', 'voluntariado')) }}</td></tr></table>
    <table class="inf-table inf-small"><thead><tr><th colspan="6">Detalle de estudiantes participantes en el proyecto</th></tr><tr><th style="width:5%">N°</th><th>Nombre completo</th><th>Tipo de participación<br>ASIG / PPS / VOL</th><th>No. de cuenta</th><th>Carrera a la que pertenece</th><th>Horas dedicadas</th></tr></thead><tbody>@forelse($estudiantes as $row)<tr><td class="inf-center">{{ $loop->iteration }}</td><td>{{ $row->nombre }}</td><td>{{ $tipoEstudiante($row->tipo_participacion) }}</td><td>{{ $row->numero_cuenta }}</td><td>{{ $row->carrera }}</td><td>{{ $row->horas_dedicadas }}</td></tr>@empty<tr><td colspan="6" class="inf-empty">Sin estudiantes participantes registrados.</td></tr>@endforelse</tbody></table>
    @foreach($gruposEstudiantesObservados as $grupo)<p class="inf-muted inf-avoid"><strong>Observación complementaria — {{ \App\Support\InformeFinal\ParticipacionEstudiantil::etiqueta($grupo->tipo_participacion) }}@if($grupo->asignatura) · {{ $grupo->asignatura->codigo }} - {{ $grupo->asignatura->nombre }}@endif:</strong> {{ $grupo->observacion_no_cumplimiento }}</p>@endforeach
    <p class="inf-muted">Nota: Se debe adjuntar bitácora de cada estudiante, con firma de aprobación del(a) coordinador(a) de proyecto o encargado de supervisión.</p>

    <h2 class="inf-section-title">IV. Cuantificación de participación de voluntarios</h2>
    <table class="inf-table inf-small inf-avoid"><tr><th rowspan="2">Participación de voluntarios (comunidad universitaria)</th><th colspan="2">Total</th><th colspan="8">Desglose del tipo de participación de personal docente (expresado en números)</th></tr><tr><th>Hombres</th><th>Mujeres</th><th colspan="2">Profesores x hora y horarios (PH)</th><th colspan="2">Personal administrativo y de servicios (PAS)</th><th colspan="2">Profesores permanentes (PP)</th><th colspan="2">Egresados(as) (EGR)</th></tr><tr class="inf-center"><td>Expresado en números</td><td>{{ $hombres($voluntarios) }}</td><td>{{ $mujeres($voluntarios) }}</td>@foreach(['profesor_hora','pas','profesor_permanente','egresado'] as $tipo)<td>H {{ $hombres($voluntarios->where('tipo',$tipo)) }}</td><td>M {{ $mujeres($voluntarios->where('tipo',$tipo)) }}</td>@endforeach</tr></table>
    <table class="inf-table inf-small"><thead><tr><th colspan="6">Detalle de voluntarios participantes en el proyecto</th></tr><tr><th style="width:5%">N°</th><th>Nombre completo</th><th>Tipo de participación<br>PH / PAS / PP / EGR</th><th>No. de identidad</th><th>Departamento al que pertenece</th><th>Horas dedicadas</th></tr></thead><tbody>@forelse($voluntarios as $row)<tr><td class="inf-center">{{ $loop->iteration }}</td><td>{{ $row->nombre }}</td><td>{{ $tipoVoluntario[$row->tipo] ?? $row->tipo }}</td><td>{{ $row->identidad }}</td><td>{{ $row->departamento }}</td><td>{{ $row->horas_dedicadas }}</td></tr>@empty<tr><td colspan="6" class="inf-empty">Sin voluntarios registrados.</td></tr>@endforelse</tbody></table>
    @if(filled($informe->observacion_voluntarios_no_incorporados))<p class="inf-muted inf-avoid"><strong>Observación complementaria sobre voluntarios:</strong> {{ $informe->observacion_voluntarios_no_incorporados }}</p>@endif

    <h2 class="inf-section-title">V. Información de la entidad contraparte del proyecto</h2>
    <p class="inf-muted">(Sí existe más de una contraparte añadir una tabla de información por cada una de ellas)</p>
    @php($huboApoyoContraparte = $informe->contrapartes->contains(fn ($row) => (bool) $row->existe_apoyo))
    <table class="inf-table inf-avoid"><tr><td class="inf-label-blue" style="width:34%">El proyecto se ejecutó con apoyo de uno o más contrapartes</td><td class="inf-checks">{{ $huboApoyoContraparte ? '☒' : '☐' }} Sí &nbsp;&nbsp; {{ $huboApoyoContraparte ? '☐' : '☒' }} No</td></tr></table>
    @foreach($informe->contrapartes as $row)
        <table class="inf-table inf-avoid">
            <tr><td class="inf-label-blue" style="width:34%">Nombre de la contraparte</td><td colspan="3">{{ $row->nombre }}</td></tr>
            <tr><td class="inf-label-blue">Tipo de contraparte</td><td colspan="3" class="inf-checks inf-small">@foreach($tiposContraparteFormato as $clave => $nombre){{ $row->tipo === $clave ? '☒' : '☐' }} {{ $nombre }} &nbsp; @endforeach</td></tr>
            <tr><td class="inf-label-blue">Nombre del contacto directo</td><td>{{ $row->contacto }}</td><td class="inf-label" style="width:16%">Correo electrónico</td><td>{{ $row->correo }}</td></tr>
            <tr><td class="inf-label-blue">Cargo del contacto del proyecto</td><td>{{ $row->cargo }}</td><td class="inf-label">Teléfono</td><td>{{ $row->telefono }}</td></tr>
            <tr><td class="inf-label-blue">Tipo de instrumento que da lugar a la alianza</td><td colspan="3" class="inf-checks inf-small">@foreach($tiposInstrumentoFormato as $clave => $nombre){{ $row->tipo_instrumento === $clave ? '☒' : '☐' }} {{ $nombre }} &nbsp; @endforeach</td></tr>
            <tr><td class="inf-label-blue">Breve descripción de los compromisos que fueron asumidos por la contraparte</td><td colspan="3" class="inf-narrative">{{ $row->compromisos_asumidos }}</td></tr>
        </table>
    @endforeach

    <div class="inf-page-break"></div>
    <h2 class="inf-section-title">VI. Informe de ejecución de las acciones planificadas</h2>
    @php($objetivosEspecificos = $informe->resultados->filter(fn ($resultado) => ($resultado->plazo ?? 'corto_plazo') === 'corto_plazo')->pluck('objetivo_especifico')->filter(fn ($objetivo) => filled($objetivo))->unique()->values())
    <table class="inf-table inf-avoid"><tr><td class="inf-label-blue" style="width:24%">Objetivo general:</td><td class="inf-narrative">{{ $informe->objetivo_general }}</td></tr><tr><td class="inf-label-blue">Objetivos específicos:</td><td class="inf-narrative">@foreach($objetivosEspecificos as $objetivo){{ $loop->iteration }}. {{ $objetivo }}@if(! $loop->last)<br>@endif @endforeach</td></tr></table>
    @forelse($informe->resultados as $resultado)
        @php($actividadesResultado = $actividades->where('informe_final_resultado_id', $resultado->id))
        <table class="inf-table inf-avoid">
            <tr><th colspan="2" class="inf-header-blue inf-center">RESULTADO {{ $loop->iteration }}</th></tr>
            <tr><td class="inf-label-blue" style="width:28%">Planificado</td><td>{{ $resultado->resultado_planificado }}</td></tr>
            <tr><td class="inf-label">Indicador de resultado propuesto</td><td>{{ $resultado->indicador_propuesto }}@if($resultado->meta_numerica !== null) (meta: {{ $resultado->meta_numerica }} {{ $resultado->unidad_medida }})@endif</td></tr>
            <tr><td class="inf-label-blue">Alcanzado</td><td>@if($resultado->valor_alcanzado !== null){{ $resultado->valor_alcanzado }} {{ $resultado->unidad_medida }} ({{ $porcentaje($resultado->porcentaje_cumplimiento) }} de la meta)@endif</td></tr>
            <tr><td class="inf-label">Producto logrado (relacionado con el indicador de resultado propuesto)</td><td class="inf-narrative">{{ $resultado->producto_logrado }}</td></tr>
        </table>
        <table class="inf-table inf-small"><thead><tr><th>Detalle de las actividades realizadas</th><th style="width:20%">Responsable de la ejecución</th><th style="width:18%">Período de ejecución</th><th style="width:26%">Medio de verificación (producto obtenido)</th></tr></thead><tbody>@forelse($actividadesResultado as $actividad)<tr><td>{{ $actividad->actividad_realizada ?: $actividad->actividad_planificada }}</td><td>{{ $responsable($actividad) }}</td><td>{{ $fecha($actividad->fecha_inicial) }} — {{ $fecha($actividad->fecha_final) }}</td><td>{{ $actividad->medio_verificacion }}</td></tr>@empty<tr><td colspan="4" class="inf-empty">Sin actividades realizadas asociadas a este resultado.</td></tr>@endforelse</tbody></table>
    @empty<p class="inf-empty">Sin resultados registrados.</p>@endforelse
    @php($actividadesSinResultado = $actividades->filter(fn ($actividad) => blank($actividad->informe_final_resultado_id) || ! $informe->resultados->contains('id', $actividad->informe_final_resultado_id)))
    @if($actividadesSinResultado->isNotEmpty())
        <h3 class="inf-subtitle">Actividades realizadas sin resultado asociado</h3>
        <table class="inf-table inf-small"><thead><tr><th>Detalle de las actividades realizadas</th><th style="width:20%">Responsable de la ejecución</th><th style="width:18%">Período de ejecución</th><th style="width:26%">Medio de verificación (producto obtenido)</th></tr></thead><tbody>@foreach($actividadesSinResultado as $actividad)<tr><td>{{ $actividad->actividad_realizada ?: $actividad->actividad_planificada }}</td><td>{{ $responsable($actividad) }}</td><td>{{ $fecha($actividad->fecha_inicial) }} — {{ $fecha($actividad->fecha_final) }}</td><td>{{ $actividad->medio_verificacion }}</td></tr>@endforeach</tbody></table>
    @endif

    <h2 class="inf-section-title">VII. Reporte de acciones planificadas que no fueron ejecutadas</h2>
    <table class="inf-table inf-small"><thead><tr><th>Resultado previsto</th><th>Actividad planificada</th><th>Breve explicación del porqué no se ejecutó</th><th>Afectación al proyecto</th></tr></thead><tbody>@forelse($informe->accionesNoEjecutadas as $row)<tr><td>{{ $row->resultado_previsto }}</td><td>{{ $row->actividad_planificada }}</td><td>{{ $row->explicacion }}</td><td>{{ $row->afectacion_proyecto }}</td></tr>@empty<tr><td colspan="4" class="inf-empty">No se registraron acciones no ejecutadas.</td></tr>@endforelse</tbody></table>

    <h2 class="inf-section-title">VIII. Reporte de acciones emergentes (Actividades realizadas que no estaban originalmente planificadas)</h2>
    <table class="inf-table inf-small"><thead><tr><th>Producto logrado</th><th>Actividad realizada</th><th>Breve justificación del porqué se realizó</th><th>Responsables de la ejecución</th></tr></thead><tbody>@forelse($informe->accionesEmergentes as $row)<tr><td>{{ $row->producto_logrado }}</td><td>{{ $row->actividad_realizada }}</td><td>{{ $row->justificacion }}</td><td>{!! nl2br(e($row->responsables)) !!}</td></tr>@empty<tr><td colspan="4" class="inf-empty">No se registraron acciones emergentes.</td></tr>@endforelse</tbody></table>

    <div class="inf-page-break"></div>
    <h2 class="inf-section-title">IX. Reflexión</h2>
    <table class="inf-table inf-avoid"><tr><th colspan="2" class="inf-header-blue">1. Dificultades que se presentaron en la ejecución del proyecto</th></tr><tr><th>Descripción de las dificultades</th><th>Acciones realizadas para afrontar las dificultades</th></tr><tr><td class="inf-narrative">{{ $informe->dificultades }}</td><td class="inf-narrative">{{ $informe->acciones_dificultades }}</td></tr></table>
    <table class="inf-table inf-avoid"><tr><th class="inf-header-blue">2. Lecciones aprendidas</th></tr><tr><td class="inf-narrative">{{ $informe->lecciones_aprendidas }}</td></tr></table>
    <table class="inf-table inf-avoid"><tr><th class="inf-header-blue">3. Buenas prácticas (Resaltar aspectos que contribuirán a mejorar el proceso académico de su unidad académica a partir de la experiencia)</th></tr><tr><td class="inf-narrative">{{ $informe->buenas_practicas }}</td></tr></table>
    <table class="inf-table inf-avoid"><tr><th colspan="2" class="inf-header-blue">4. Transformación que se logró con la ejecución del proyecto</th></tr><tr><th>Problema inicial identificado</th><th>Cambios que se logró con el proyecto</th></tr><tr><td class="inf-narrative">{{ $informe->problema_inicial }}</td><td class="inf-narrative">{{ $informe->transformacion_lograda }}</td></tr></table>
    <table class="inf-table inf-small inf-avoid"><thead><tr><th colspan="2" class="inf-header-blue">5. Aportes a los objetivos de Desarrollo Sostenible</th></tr><tr><th>ODS a los que se contribuyó</th><th>Metas a las que se contribuyó</th></tr></thead><tbody>@forelse($informe->ods as $row)<tr><td>{{ $row->ods?->nombre }}</td><td>{{ $row->meta_ods }}</td></tr>@empty<tr><td colspan="2" class="inf-empty">Sin ODS registrados.</td></tr>@endforelse</tbody></table>
    <table class="inf-table inf-avoid"><tr><th class="inf-header-blue">6. Sostenibilidad del proyecto</th></tr><tr><th>Descripción de los mecanismos aplicados para garantizar la sostenibilidad del proyecto</th></tr><tr><td class="inf-narrative">{{ $informe->mecanismos_sostenibilidad }}</td></tr><tr><th>Acciones ejecutadas por la contraparte para garantizar la sostenibilidad de las acciones ejecutadas</th></tr><tr><td class="inf-narrative">{{ $informe->acciones_contraparte_sostenibilidad }}</td></tr></table>
    <table class="inf-table inf-avoid"><tr><th class="inf-header-blue">7. Desafíos</th></tr><tr><td class="inf-narrative">{{ $informe->desafios }}</td></tr></table>
    <table class="inf-table inf-avoid"><tr><th class="inf-header-blue">8. Explique brevemente cómo el proyecto respondió a lo esencial de la reforma universitaria</th></tr><tr><td class="inf-narrative">{{ $informe->respuesta_reforma_universitaria }}</td></tr></table>
    <table class="inf-table inf-avoid"><tr><th class="inf-header-blue">9. Recomendaciones</th></tr><tr><td class="inf-narrative">{{ $informe->recomendaciones }}</td></tr></table>
    <table class="inf-table inf-avoid"><tr><th class="inf-header-blue">10. Bibliografía utilizada</th></tr><tr><td class="inf-narrative">{{ $informe->bibliografia }}</td></tr></table>
    <table class="inf-table inf-small inf-avoid"><tr><th colspan="4" class="inf-header-blue">11. Resultados de la valoración del proyecto por parte de la comunidad beneficiada (Deberá de aplicarse un formulario de consulta a los beneficiados para que evalúen el proyecto)</th></tr><tr><td class="inf-label">Total beneficiarios</td><td>{{ $informe->valoracion_total_beneficiarios }}</td><td class="inf-label">Total muestra de consultas realizadas</td><td>{{ $muestra }}</td></tr><tr><th colspan="4" class="inf-center">Resultados de la encuesta (expresar los datos en porcentaje %)</th></tr><tr><th class="inf-center">Excelente</th><th class="inf-center">Muy buena</th><th class="inf-center">Regular</th><th class="inf-center">Mala</th></tr><tr class="inf-center"><td>{{ $porcentaje($muestra ? $informe->valoracion_excelente/$muestra*100 : 0) }}</td><td>{{ $porcentaje($muestra ? $informe->valoracion_muy_buena/$muestra*100 : 0) }}</td><td>{{ $porcentaje($muestra ? $informe->valoracion_regular/$muestra*100 : 0) }}</td><td>{{ $porcentaje($muestra ? $informe->valoracion_mala/$muestra*100 : 0) }}</td></tr></table>

    <div class="inf-page-break"></div>
    <h2 class="inf-section-title">X. Ejecución presupuestaria</h2>
    <h3 class="inf-subtitle">Aporte de la UNAH (manifestado en lempiras)</h3>
    @php([$unahPorCodigo, $unahSinConcepto] = $filasX('UNAH'))
    <table class="inf-table inf-small"><thead><tr><th>Concepto</th><th style="width:11%">Unidad</th><th style="width:11%">Cantidad</th><th style="width:15%">Costo unitario</th><th style="width:15%">Costo total</th></tr></thead><tbody>
        @foreach($catalogoX::UNAH as $codigo => [$letra, $concepto, $unidad])
            @php($filasConcepto = collect($unahPorCodigo->get($codigo, []))->pluck('fila'))
            <tr><td>{{ $letra }}) {{ $concepto }}</td><td class="inf-center">{{ $unidad }}</td>
            @if($catalogoX::esIndirecto($codigo))
                <td></td><td></td><td>{{ $moneda($codigo === 'costos_indirectos_infraestructura' ? $informe->infraestructura_unah : $informe->servicios_unah) }}</td>
            @elseif($filasConcepto->count() === 1)
                <td>{{ $filasConcepto->first()->cantidad }}</td><td>{{ $moneda($filasConcepto->first()->costo_unitario) }}</td><td>{{ $moneda($filasConcepto->first()->costo_total) }}</td>
            @else
                <td></td><td></td><td>{{ $filasConcepto->isEmpty() ? '' : $moneda($filasConcepto->sum(fn ($fila) => $fila->costo_total)) }}</td>
            @endif
            </tr>
        @endforeach
        @foreach($unahSinConcepto as $row)<tr><td>{{ $row->concepto }}</td><td class="inf-center">{{ $row->unidad ?: 'Global' }}</td><td>{{ $row->cantidad }}</td><td>{{ $moneda($row->costo_unitario) }}</td><td>{{ $moneda($row->costo_total) }}</td></tr>@endforeach
        <tr><td colspan="4" class="inf-right inf-header-gray">Total aporte institucional</td><td class="inf-header-gray">{{ $moneda($informe->total_unah) }}</td></tr>
    </tbody></table>
    <h3 class="inf-subtitle">Aporte de la contraparte (manifestado en lempiras)</h3>
    @php([$contrapartePorCodigo, $contraparteSinConcepto] = $filasX('CONTRAPARTE'))
    @php($origenFondos = fn ($filas) => $filas->pluck('origen_fondos')->reject(fn ($origen) => blank($origen) || in_array($origen, $marcasInternasOrigen, true))->unique()->implode('; '))
    <table class="inf-table inf-small"><thead><tr><th>Concepto</th><th style="width:9%">Unidad</th><th style="width:9%">Cantidad</th><th style="width:13%">Costo unitario</th><th style="width:13%">Costo total</th><th style="width:20%">Descripción del origen de los fondos</th></tr></thead><tbody>
        @foreach($catalogoX::CONTRAPARTE as $codigo => [$letra, $concepto, $unidad])
            @php($filasConcepto = collect($contrapartePorCodigo->get($codigo, []))->pluck('fila'))
            <tr><td>{{ $letra }}) {{ $concepto }}</td><td class="inf-center">{{ $unidad }}</td>
            @if($filasConcepto->count() === 1)
                <td>{{ $filasConcepto->first()->cantidad }}</td><td>{{ $moneda($filasConcepto->first()->costo_unitario) }}</td><td>{{ $moneda($filasConcepto->first()->costo_total) }}</td>
            @else
                <td></td><td></td><td>{{ $filasConcepto->isEmpty() ? '' : $moneda($filasConcepto->sum(fn ($fila) => $fila->costo_total)) }}</td>
            @endif
            <td>{{ $origenFondos($filasConcepto) }}</td></tr>
        @endforeach
        @foreach($contraparteSinConcepto as $row)<tr><td>{{ $row->concepto }}</td><td class="inf-center">{{ $row->unidad ?: 'Global' }}</td><td>{{ $row->cantidad }}</td><td>{{ $moneda($row->costo_unitario) }}</td><td>{{ $moneda($row->costo_total) }}</td><td>{{ $origenFondos(collect([$row])) }}</td></tr>@endforeach
        <tr><td colspan="4" class="inf-right inf-label">Total aporte de las contrapartes</td><td colspan="2">{{ $moneda($informe->total_contraparte) }}</td></tr>
        <tr><td colspan="4" class="inf-right inf-label">Aporte de los beneficiarios (comunidad)</td><td colspan="2">{{ $moneda($informe->aporte_beneficiarios) }}</td></tr>
        <tr><td colspan="4" class="inf-right inf-label">Otros aportes</td><td colspan="2">{{ $moneda($informe->otros_aportes) }}</td></tr>
        <tr><td colspan="4" class="inf-right inf-header-gray">Total Ejecución de la contraparte</td><td colspan="2" class="inf-header-gray">{{ $moneda($informe->total_ejecucion_contraparte) }}</td></tr>
    </tbody></table>
    <p class="inf-muted"><strong>Nota:</strong> La entidad contraparte deberá de presentar el reporte de gastos, desglosado, firmado y sellado por el responsable contable (gerente, tesorero, Alcalde Municipal, etc) y del representante legal de la institución, certificando la veracidad de los fondos. En el caso de no poder contar con esta carta aval, no deberá de registrarse valor alguno en el cuadro de la contraparte.</p>

    <h2 class="inf-section-title">XI. Firmas</h2>
    @foreach(array_chunk($cuadrosFirmaInf, 2, true) as $parFirmas)
        <table class="inf-table inf-avoid">
            <tr>@foreach($parFirmas as [$tituloFirma, $pieFirma])<th class="inf-header-blue inf-center" style="width:50%">{{ $tituloFirma }}</th>@endforeach</tr>
            <tr>@foreach($parFirmas as $claveFirma => $cuadro)<td>Nombre: {{ data_get($firmas, $claveFirma.'.nombre') }}</td>@endforeach</tr>
            <tr>@foreach($parFirmas as $claveFirma => $cuadro)<td class="inf-signature-cell">@if(data_get($firmas, $claveFirma.'.sello'))<img src="{{ data_get($firmas, $claveFirma.'.sello') }}" alt="">@endif @if(data_get($firmas, $claveFirma.'.firma'))<img src="{{ data_get($firmas, $claveFirma.'.firma') }}" alt="">@endif @if(data_get($firmas, $claveFirma.'.fecha'))<div class="inf-muted">Firmado: {{ $fecha(data_get($firmas, $claveFirma.'.fecha')) }}</div>@endif</td>@endforeach</tr>
            <tr>@foreach($parFirmas as [$tituloFirma, $pieFirma])<td class="inf-header-gray inf-center">{{ $pieFirma }}</td>@endforeach</tr>
        </table>
    @endforeach

    <h2 class="inf-section-title">XII. Anexos</h2>
    <p>Deberán adjuntarse como anexos, entre otros, la siguiente información:</p>
    <ol class="inf-small" style="margin:0 0 6pt 14pt; padding:0;">
        <li>Material generado por el proyecto (adjuntarse el enlace de una carpeta digital con documentos, manuales, guías, listas de asistencia, ayudas memorias de reuniones, etc)</li>
        <li>Formularios de encuestas</li>
        <li>Informes de procesamiento de datos</li>
        <li>Fotografías de todo el proceso (debe de adjuntarse el enlace de una carpeta digital con juego de fotografías de todo el proceso)</li>
        <li>Videos cortos del proyecto (en el caso de haberse realizado, se recomienda el levantamiento de esta información).</li>
        <li>Evidencias de difusión de las acciones del proyecto: presentaciones a actores externos, publicaciones en medios, difusión en redes sociales, etc).</li>
    </ol>
    <h3 class="inf-subtitle">Instrumentos de formalización y respaldos de contraparte</h3>
    <table class="inf-table inf-small"><thead><tr><th style="width:6%">N.º</th><th>Contraparte</th><th>Instrumento o respaldo</th><th style="width:24%">Archivo</th></tr></thead><tbody>@forelse($instrumentosContraparte as $row)<tr><td class="inf-center">{{ $loop->iteration }}</td><td>{{ $row->contraparte?->nombre }}</td><td>{{ $row->descripcion }}</td><td>{{ $row->nombre_archivo ?: ($row->archivo ? basename($row->archivo) : 'Pendiente') }}</td></tr>@empty<tr><td colspan="4" class="inf-empty">Sin instrumentos de contraparte registrados.</td></tr>@endforelse</tbody></table>
    <h3 class="inf-subtitle">Documentos generales</h3>
    <table class="inf-table inf-small"><thead><tr><th style="width:6%">N.º</th><th style="width:16%">Tipo</th><th>Descripción</th><th style="width:14%">Fecha</th><th style="width:24%">Referencia</th></tr></thead><tbody>@forelse($documentosGenerales as $row)<tr><td class="inf-center">{{ $loop->iteration }}</td><td>{{ $tiposAnexoFormato[$row->tipo] ?? $row->tipo }}</td><td>{{ $row->descripcion }}</td><td>{{ $fecha($row->fecha) }}</td><td>{{ $row->enlace ?: ($row->nombre_archivo ?: ($row->archivo ? basename($row->archivo) : '')) }}</td></tr>@empty<tr><td colspan="5" class="inf-empty">Sin documentos generales registrados.</td></tr>@endforelse</tbody></table>
    <h3 class="inf-subtitle">Fotografías del proyecto</h3>
    <div class="inf-photo-grid">@forelse($fotografias as $row)<div class="inf-photo-card">@if($rutaAnexo($row))<img src="{{ $rutaAnexo($row) }}" alt="">@endif<strong>{{ $row->nombre_archivo ?: 'Fotografía '.$loop->iteration }}</strong><br>{{ $row->descripcion ?: 'Sin descripción' }}@if($row->fecha)<br>{{ $fecha($row->fecha) }}@endif</div>@empty<p class="inf-empty">Sin fotografías registradas.</p>@endforelse</div>
</main>
