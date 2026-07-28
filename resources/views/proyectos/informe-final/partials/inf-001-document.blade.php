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
    $actividades = $informe->actividades->where('origen', 'planificada')->values();
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
    $unidades = $informe->presupuestoDetalles->where('fuente', 'UNAH')->values();
    $contraparte = $informe->presupuestoDetalles->where('fuente', 'CONTRAPARTE')->values();
    $muestra = max(0, (int) $informe->valoracion_muestra);
    $instrumentosContraparte = $informe->anexos->where('categoria', 'instrumento_contraparte')->sortBy('orden')->values();
    $documentosGenerales = $informe->anexos->where('categoria', 'documento_general')->sortBy('orden')->values();
    $fotografias = $informe->anexos->where('categoria', 'fotografia')->sortBy('orden')->values();
    $rutaAnexo = function ($row) use ($isPdf) {
        $ruta = $row->archivo ?: $row->enlace;
        if (! $ruta) return null;
        if (filter_var($ruta, FILTER_VALIDATE_URL)) return $ruta;
        return $isPdf ? storage_path('app/public/'.$ruta) : \Illuminate\Support\Facades\Storage::disk('public')->url($ruta);
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
        <tr><td class="inf-label">Carrera</td><td colspan="2">{{ $informe->carrera }}</td></tr>
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
    <table class="inf-table inf-avoid"><tr><th colspan="4" class="inf-header-blue">10. Sitio de ejecución del proyecto</th></tr><tr><td class="inf-label">Departamento</td><td>{{ $informe->departamento_territorial }}</td><td class="inf-label">Aldea (incluye ciudad)</td><td>{{ $informe->aldea_ciudad }}</td></tr><tr><td class="inf-label">Municipio</td><td>{{ $informe->municipio }}</td><td class="inf-label">Caserío</td><td>{{ $informe->caserio }}</td></tr><tr><td class="inf-label">Región</td><td>{{ $informe->region }}</td><td class="inf-label">País</td><td>{{ $informe->pais }}</td></tr></table>

    <h2 class="inf-section-title">II. Equipo ejecutor del proyecto</h2>
    <h3 class="inf-subtitle">A. Coordinador/a del proyecto</h3>
    <table class="inf-table inf-avoid"><tr><td class="inf-label">Nombre completo</td><td>{{ $coordinador?->nombre }}</td><td class="inf-label">N.º de empleado/a</td><td>{{ $coordinador?->numero_empleado }}</td></tr><tr><td class="inf-label">Correo electrónico</td><td>{{ $coordinador?->correo }}</td><td class="inf-label">Celular</td><td>{{ $coordinadorProyecto?->celular }}</td></tr><tr><td class="inf-label">Categoría</td><td>{{ $coordinador?->categoria }}</td><td class="inf-label">Horas dedicadas</td><td>{{ $coordinador?->horas_dedicadas }}</td></tr><tr><td class="inf-label">Departamento al que pertenece</td><td colspan="3">{{ $coordinador?->departamento }}</td></tr></table>

    <h3 class="inf-subtitle">B. Integrantes del equipo docente permanente tiempo completo</h3>
    <table class="inf-table inf-small"><thead><tr><th colspan="7">Total de profesores(as) integrantes principales: {{ $docentes->count() }}</th></tr><tr><th style="width:5%">N.º</th><th>Nombre completo</th><th>N.º empleado/a</th><th>Correo electrónico</th><th>Categoría</th><th>Departamento</th><th>Horas</th></tr></thead><tbody>@forelse($docentes as $row)<tr><td class="inf-center">{{ $loop->iteration }}</td><td>{{ $row->nombre }}</td><td>{{ $row->numero_empleado }}</td><td>{{ $row->correo }}</td><td>{{ $row->categoria }}</td><td>{{ $row->departamento }}</td><td>{{ $row->horas_dedicadas }}</td></tr>@empty<tr><td colspan="7" class="inf-empty">Sin integrantes adicionales registrados.</td></tr>@endforelse</tbody></table>

    <h3 class="inf-subtitle">C. Integrantes del equipo de cooperación internacional / otras universidades</h3>
    <table class="inf-table inf-small"><thead><tr><th colspan="7">Cantidad de integrantes: {{ $cooperacion->count() }}</th></tr><tr><th style="width:5%">N.º</th><th>Nombre completo</th><th>Pasaporte</th><th>Correo electrónico</th><th>País</th><th>Universidad</th><th>Horas</th></tr></thead><tbody>@forelse($cooperacion as $row)<tr><td class="inf-center">{{ $loop->iteration }}</td><td>{{ $row->nombre }}</td><td>{{ $row->pasaporte }}</td><td>{{ $row->correo }}</td><td>{{ $row->pais }}</td><td>{{ $row->universidad }}</td><td>{{ $row->horas_dedicadas }}</td></tr>@empty<tr><td colspan="7" class="inf-empty">Sin cooperación internacional registrada.</td></tr>@endforelse</tbody></table>

    <h2 class="inf-section-title">III. Cuantificación de participación de estudiantes</h2>
    <table class="inf-table inf-small inf-avoid"><tr><th rowspan="2">Participación de estudiantes</th><th colspan="2">Total</th><th colspan="6">Desglose del tipo de participación</th></tr><tr><th>Hombres</th><th>Mujeres</th><th colspan="2">Práctica de asignatura</th><th colspan="2">Servicio Social o PPS</th><th colspan="2">Voluntariado</th></tr><tr class="inf-center"><td>Expresado en números</td><td>{{ $hombres($estudiantes) }}</td><td>{{ $mujeres($estudiantes) }}</td><td>H {{ $hombres($estudiantes->where('tipo_participacion', 'practica_asignatura')) }}</td><td>M {{ $mujeres($estudiantes->where('tipo_participacion', 'practica_asignatura')) }}</td><td>H {{ $hombres($estudiantes->where('tipo_participacion', 'pps_servicio_social')) }}</td><td>M {{ $mujeres($estudiantes->where('tipo_participacion', 'pps_servicio_social')) }}</td><td>H {{ $hombres($estudiantes->where('tipo_participacion', 'voluntariado')) }}</td><td>M {{ $mujeres($estudiantes->where('tipo_participacion', 'voluntariado')) }}</td></tr></table>
    <table class="inf-table inf-small"><thead><tr><th style="width:5%">N.º</th><th>Nombre completo</th><th>Tipo<br>ASIG / PPS / VOL</th><th>Asignatura / período</th><th>N.º de cuenta</th><th>Carrera</th><th>Horas</th></tr></thead><tbody>@forelse($estudiantes as $row)<tr><td class="inf-center">{{ $loop->iteration }}</td><td>{{ $row->nombre }}</td><td>{{ $tipoEstudiante($row->tipo_participacion) }}</td><td>{{ $asignaturaEstudiante($row) }}</td><td>{{ $row->numero_cuenta }}</td><td>{{ $row->carrera }}</td><td>{{ $row->horas_dedicadas }}</td></tr>@empty<tr><td colspan="7" class="inf-empty">Sin estudiantes participantes registrados.</td></tr>@endforelse</tbody></table>
    @foreach($gruposEstudiantesObservados as $grupo)<p class="inf-muted inf-avoid"><strong>Observación complementaria — {{ \App\Support\InformeFinal\ParticipacionEstudiantil::etiqueta($grupo->tipo_participacion) }}@if($grupo->asignatura) · {{ $grupo->asignatura->codigo }} - {{ $grupo->asignatura->nombre }}@endif:</strong> {{ $grupo->observacion_no_cumplimiento }}</p>@endforeach
    <p class="inf-muted">Nota: Se debe adjuntar bitácora de cada estudiante, con firma de aprobación del coordinador del proyecto o encargado de supervisión.</p>

    <h2 class="inf-section-title">IV. Cuantificación de participación de voluntarios</h2>
    <table class="inf-table inf-small inf-avoid"><tr><th rowspan="2">Participación de voluntarios</th><th colspan="2">Total</th><th colspan="8">Desglose del tipo de participación</th></tr><tr><th>Hombres</th><th>Mujeres</th><th colspan="2">Profesores por hora (PH)</th><th colspan="2">Personal administrativo (PAS)</th><th colspan="2">Profesores permanentes (PP)</th><th colspan="2">Egresados(as) (EGR)</th></tr><tr class="inf-center"><td>Expresado en números</td><td>{{ $hombres($voluntarios) }}</td><td>{{ $mujeres($voluntarios) }}</td>@foreach(['profesor_hora','pas','profesor_permanente','egresado'] as $tipo)<td>H {{ $hombres($voluntarios->where('tipo',$tipo)) }}</td><td>M {{ $mujeres($voluntarios->where('tipo',$tipo)) }}</td>@endforeach</tr></table>
    <table class="inf-table inf-small"><thead><tr><th style="width:5%">N.º</th><th>Nombre completo</th><th>Tipo<br>PH / PAS / PP / EGR</th><th>N.º de identidad</th><th>Departamento al que pertenece</th><th>Horas dedicadas</th></tr></thead><tbody>@forelse($voluntarios as $row)<tr><td class="inf-center">{{ $loop->iteration }}</td><td>{{ $row->nombre }}</td><td>{{ $tipoVoluntario[$row->tipo] ?? $row->tipo }}</td><td>{{ $row->identidad }}</td><td>{{ $row->departamento }}</td><td>{{ $row->horas_dedicadas }}</td></tr>@empty<tr><td colspan="6" class="inf-empty">Sin voluntarios registrados.</td></tr>@endforelse</tbody></table>
    @if(filled($informe->observacion_voluntarios_no_incorporados))<p class="inf-muted inf-avoid"><strong>Observación complementaria sobre voluntarios:</strong> {{ $informe->observacion_voluntarios_no_incorporados }}</p>@endif

    <h2 class="inf-section-title">V. Información de la entidad contraparte del proyecto</h2>
    <p class="inf-muted">Si existe más de una contraparte, se presenta una tabla por cada una.</p>
    @forelse($informe->contrapartes as $row)
        <table class="inf-table inf-avoid"><tr><td class="inf-label" style="width:30%">El proyecto se ejecutó con apoyo de una o más contrapartes</td><td colspan="3">{{ $row->existe_apoyo ? '☒ Sí  ☐ No' : '☐ Sí  ☒ No' }}</td></tr><tr><td class="inf-label">Nombre de la contraparte</td><td colspan="3">{{ $row->nombre }}</td></tr><tr><td class="inf-label">Tipo de contraparte</td><td colspan="3">{{ str_replace('_',' ',(string)$row->tipo) }}</td></tr><tr><td class="inf-label">Nombre del contacto directo</td><td>{{ $row->contacto }}</td><td class="inf-label">Correo electrónico / teléfono</td><td>{{ $row->correo }}<br>{{ $row->telefono }}</td></tr><tr><td class="inf-label">Cargo del contacto</td><td>{{ $row->cargo }}</td><td class="inf-label">Territorio</td><td>{{ $row->territorio }}</td></tr><tr><td class="inf-label">Instrumento que da lugar a la alianza</td><td colspan="3">{{ str_replace('_',' ',(string)$row->tipo_instrumento) }}</td></tr><tr><td class="inf-label">Compromisos asumidos</td><td colspan="3" class="inf-narrative">{{ $row->compromisos_asumidos }}</td></tr><tr><td class="inf-label">Compromisos cumplidos</td><td colspan="3" class="inf-narrative">{{ $row->compromisos_cumplidos }}</td></tr><tr><td class="inf-label">Documento de respaldo</td><td>{{ $row->documento_respaldo }}</td><td class="inf-label">Aporte monetario / especie</td><td>{{ $moneda($row->aporte_monetario) }} / {{ $moneda($row->aporte_especie) }}</td></tr></table>
    @empty
        <table class="inf-table"><tr><td class="inf-label">El proyecto se ejecutó con apoyo de una o más contrapartes</td><td>☐ Sí &nbsp; ☒ No</td></tr></table>
    @endforelse

    <div class="inf-page-break"></div>
    <h2 class="inf-section-title">VI. Informe de ejecución de las acciones planificadas</h2>
    <table class="inf-table inf-avoid"><tr><td class="inf-label" style="width:24%">Objetivo general</td><td class="inf-narrative">{{ $informe->objetivo_general }}</td></tr></table>
    @forelse($informe->resultados as $resultado)
        <table class="inf-table inf-avoid"><tr><th colspan="2" class="inf-header-blue">Resultado {{ $loop->iteration }}</th></tr><tr><td class="inf-label" style="width:28%">Objetivo específico</td><td>{{ $resultado->objetivo_especifico }}</td></tr><tr><td class="inf-label">Planificado</td><td>{{ $resultado->resultado_planificado }}</td></tr><tr><td class="inf-label">Indicador / meta</td><td>{{ $resultado->indicador_propuesto }}@if($resultado->meta_numerica !== null) · {{ $resultado->meta_numerica }} {{ $resultado->unidad_medida }}@endif</td></tr><tr><td class="inf-label">Alcanzado</td><td>{{ $resultado->valor_alcanzado }} {{ $resultado->unidad_medida }} · {{ $porcentaje($resultado->porcentaje_cumplimiento) }} · {{ str_replace('_',' ',$resultado->estado) }}</td></tr><tr><td class="inf-label">Producto logrado</td><td class="inf-narrative">{{ $resultado->producto_logrado }}</td></tr></table>
    @empty<p class="inf-empty">Sin resultados registrados.</p>@endforelse
    <h3 class="inf-subtitle">Detalle de las actividades realizadas</h3>
    <table class="inf-table inf-small"><thead><tr><th>Actividad realizada</th><th>Responsable principal de la ejecución</th><th>Período de ejecución</th><th>Medio de verificación</th></tr></thead><tbody>@forelse($actividades as $actividad)<tr><td>{{ $actividad->actividad_realizada ?: $actividad->actividad_planificada }}</td><td>{{ $responsable($actividad) }}</td><td>{{ $fecha($actividad->fecha_inicial) }} — {{ $fecha($actividad->fecha_final) }}</td><td>{{ $actividad->medio_verificacion }}</td></tr>@empty<tr><td colspan="4" class="inf-empty">Sin actividades registradas.</td></tr>@endforelse</tbody></table>

    <h2 class="inf-section-title">VII. Reporte de acciones planificadas que no fueron ejecutadas</h2>
    <table class="inf-table inf-small"><thead><tr><th>Resultado previsto</th><th>Actividad planificada</th><th>Breve explicación</th><th>Afectación al proyecto</th></tr></thead><tbody>@forelse($informe->accionesNoEjecutadas as $row)<tr><td>{{ $row->resultado_previsto }}</td><td>{{ $row->actividad_planificada }}</td><td>{{ $row->explicacion }}</td><td>{{ $row->afectacion_proyecto }}</td></tr>@empty<tr><td colspan="4" class="inf-empty">No se registraron acciones no ejecutadas.</td></tr>@endforelse</tbody></table>

    <h2 class="inf-section-title">VIII. Reporte de acciones emergentes</h2>
    <table class="inf-table inf-small"><thead><tr><th>Producto logrado</th><th>Actividad realizada</th><th>Breve justificación</th><th>Responsables de la ejecución</th></tr></thead><tbody>@forelse($informe->accionesEmergentes as $row)<tr><td>{{ $row->producto_logrado }}</td><td>{{ $row->actividad_realizada }}</td><td>{{ $row->justificacion }}</td><td>{{ $row->responsables }}</td></tr>@empty<tr><td colspan="4" class="inf-empty">No se registraron acciones emergentes.</td></tr>@endforelse</tbody></table>

    <div class="inf-page-break"></div>
    <h2 class="inf-section-title">IX. Reflexión</h2>
    <table class="inf-table"><tr><td class="inf-label" style="width:32%">Descripción de las dificultades</td><td class="inf-narrative">{{ $informe->dificultades }}</td></tr><tr><td class="inf-label">Acciones realizadas para afrontar las dificultades</td><td class="inf-narrative">{{ $informe->acciones_dificultades }}</td></tr><tr><td class="inf-label">Lecciones aprendidas</td><td class="inf-narrative">{{ $informe->lecciones_aprendidas }}</td></tr><tr><td class="inf-label">Buenas prácticas</td><td class="inf-narrative">{{ $informe->buenas_practicas }}</td></tr><tr><td class="inf-label">Problema inicial identificado</td><td class="inf-narrative">{{ $informe->problema_inicial }}</td></tr><tr><td class="inf-label">Cambios logrados con el proyecto</td><td class="inf-narrative">{{ $informe->transformacion_lograda }}</td></tr></table>
    <h3 class="inf-subtitle">Aportes a los Objetivos de Desarrollo Sostenible</h3>
    <table class="inf-table inf-small"><thead><tr><th>ODS</th><th>Meta</th><th>Aporte</th><th>Evidencia</th></tr></thead><tbody>@forelse($informe->ods as $row)<tr><td>{{ $row->ods?->nombre }}</td><td>{{ $row->meta_ods }}</td><td>{{ $row->descripcion_aporte }}</td><td>{{ $row->evidencia }}</td></tr>@empty<tr><td colspan="4" class="inf-empty">Sin ODS registrados.</td></tr>@endforelse</tbody></table>
    <table class="inf-table"><tr><td class="inf-label" style="width:32%">Mecanismos aplicados para garantizar la sostenibilidad</td><td class="inf-narrative">{{ $informe->mecanismos_sostenibilidad }}</td></tr><tr><td class="inf-label">Acciones ejecutadas por la contraparte</td><td class="inf-narrative">{{ $informe->acciones_contraparte_sostenibilidad }}</td></tr><tr><td class="inf-label">Desafíos</td><td class="inf-narrative">{{ $informe->desafios }}</td></tr><tr><td class="inf-label">Respuesta a lo esencial de la reforma universitaria</td><td class="inf-narrative">{{ $informe->respuesta_reforma_universitaria }}</td></tr><tr><td class="inf-label">Recomendaciones</td><td class="inf-narrative">{{ $informe->recomendaciones }}</td></tr><tr><td class="inf-label">Bibliografía utilizada</td><td class="inf-narrative">{{ $informe->bibliografia }}</td></tr></table>
    <h3 class="inf-subtitle">Resultados de la valoración del proyecto por la comunidad beneficiada</h3>
    <table class="inf-table inf-small"><tr><th>Total beneficiarios</th><th>Total muestra</th><th>Excelente</th><th>Muy buena</th><th>Regular</th><th>Mala</th></tr><tr class="inf-center"><td>{{ $informe->valoracion_total_beneficiarios }}</td><td>{{ $muestra }}</td><td>{{ $informe->valoracion_excelente }} ({{ $porcentaje($muestra ? $informe->valoracion_excelente/$muestra*100 : 0) }})</td><td>{{ $informe->valoracion_muy_buena }} ({{ $porcentaje($muestra ? $informe->valoracion_muy_buena/$muestra*100 : 0) }})</td><td>{{ $informe->valoracion_regular }} ({{ $porcentaje($muestra ? $informe->valoracion_regular/$muestra*100 : 0) }})</td><td>{{ $informe->valoracion_mala }} ({{ $porcentaje($muestra ? $informe->valoracion_mala/$muestra*100 : 0) }})</td></tr><tr><td class="inf-label">Observaciones</td><td colspan="5" class="inf-narrative">{{ $informe->observaciones_finales }}</td></tr></table>

    <div class="inf-page-break"></div>
    <h2 class="inf-section-title">X. Ejecución presupuestaria</h2>
    <h3 class="inf-subtitle">Aporte de la UNAH (manifestado en lempiras)</h3>
    <table class="inf-table inf-small"><thead><tr><th>Concepto</th><th>Unidad</th><th>Cantidad</th><th>Costo unitario</th><th>Costo total</th></tr></thead><tbody>@forelse($unidades as $row)<tr><td>{{ $row->concepto }}</td><td>{{ $row->unidad ?: 'Global' }}</td><td>{{ $row->cantidad }}</td><td>{{ $moneda($row->costo_unitario) }}</td><td>{{ $moneda($row->costo_total) }}</td></tr>@empty<tr><td colspan="5" class="inf-empty">Sin detalles de aporte UNAH.</td></tr>@endforelse<tr><td colspan="4" class="inf-right inf-label">Costos indirectos por infraestructura (3%)</td><td>{{ $moneda($informe->infraestructura_unah) }}</td></tr><tr><td colspan="4" class="inf-right inf-label">Costos indirectos por servicios públicos (3%)</td><td>{{ $moneda($informe->servicios_unah) }}</td></tr><tr><td colspan="4" class="inf-right inf-header-gray">Total aporte institucional</td><td class="inf-header-gray">{{ $moneda($informe->total_unah) }}</td></tr></tbody></table>
    <h3 class="inf-subtitle">Aporte de la contraparte (manifestado en lempiras)</h3>
    <table class="inf-table inf-small"><thead><tr><th>Concepto</th><th>Unidad</th><th>Cantidad</th><th>Costo unitario</th><th>Costo total</th><th>Origen de los fondos</th></tr></thead><tbody>@forelse($contraparte as $row)<tr><td>{{ $row->concepto }}</td><td>{{ $row->unidad ?: 'Global' }}</td><td>{{ $row->cantidad }}</td><td>{{ $moneda($row->costo_unitario) }}</td><td>{{ $moneda($row->costo_total) }}</td><td>{{ $row->origen_fondos }}</td></tr>@empty<tr><td colspan="6" class="inf-empty">Sin detalles de aporte de contraparte.</td></tr>@endforelse<tr><td colspan="4" class="inf-right inf-label">Total aporte de las contrapartes</td><td colspan="2">{{ $moneda($informe->total_contraparte) }}</td></tr><tr><td colspan="4" class="inf-right inf-label">Aporte de los beneficiarios</td><td colspan="2">{{ $moneda($informe->aporte_beneficiarios) }}</td></tr><tr><td colspan="4" class="inf-right inf-label">Otros aportes</td><td colspan="2">{{ $moneda($informe->otros_aportes) }}</td></tr><tr><td colspan="4" class="inf-right inf-header-gray">Total ejecución</td><td colspan="2" class="inf-header-gray">{{ $moneda($informe->ejecucion_total) }}</td></tr></tbody></table>

    <h2 class="inf-section-title">XI. Firmas</h2>
    <table class="inf-table inf-avoid"><tr><th class="inf-header-blue">Coordinador del proyecto por la UNAH</th><th class="inf-header-blue">Jefe de la Unidad Académica que lidera el proyecto</th></tr><tr><td class="inf-signature-cell">@if(data_get($firmas,'coordinador.sello'))<img src="{{ data_get($firmas,'coordinador.sello') }}" alt="">@endif @if(data_get($firmas,'coordinador.firma'))<img src="{{ data_get($firmas,'coordinador.firma') }}" alt="">@endif<div class="inf-signature-name">@if(data_get($firmas,'coordinador.nombre'))Nombre: {{ data_get($firmas,'coordinador.nombre') }}<br>Cargo: {{ data_get($firmas,'coordinador.cargo') }}<br>Fecha: {{ $fecha(data_get($firmas,'coordinador.fecha')) }}@endif</div></td><td class="inf-signature-cell">@if(data_get($firmas,'jefe.sello'))<img src="{{ data_get($firmas,'jefe.sello') }}" alt="">@endif @if(data_get($firmas,'jefe.firma'))<img src="{{ data_get($firmas,'jefe.firma') }}" alt="">@endif<div class="inf-signature-name">@if(data_get($firmas,'jefe.nombre'))Nombre: {{ data_get($firmas,'jefe.nombre') }}<br>Cargo: {{ data_get($firmas,'jefe.cargo') }}<br>Fecha: {{ $fecha(data_get($firmas,'jefe.fecha')) }}@endif</div></td></tr><tr><th class="inf-header-blue">Coordinador(a) del Comité de Vinculación</th><th class="inf-header-blue">Decano(a) o Director(a) del Centro Regional</th></tr><tr><td class="inf-signature-cell">@if(data_get($firmas,'enlace.sello'))<img src="{{ data_get($firmas,'enlace.sello') }}" alt="">@endif @if(data_get($firmas,'enlace.firma'))<img src="{{ data_get($firmas,'enlace.firma') }}" alt="">@endif<div class="inf-signature-name">@if(data_get($firmas,'enlace.nombre'))Nombre: {{ data_get($firmas,'enlace.nombre') }}<br>Cargo: {{ data_get($firmas,'enlace.cargo') }}<br>Fecha: {{ $fecha(data_get($firmas,'enlace.fecha')) }}@endif</div></td><td class="inf-signature-cell">@if(data_get($firmas,'decano.sello'))<img src="{{ data_get($firmas,'decano.sello') }}" alt="">@endif @if(data_get($firmas,'decano.firma'))<img src="{{ data_get($firmas,'decano.firma') }}" alt="">@endif<div class="inf-signature-name">@if(data_get($firmas,'decano.nombre'))Nombre: {{ data_get($firmas,'decano.nombre') }}<br>Cargo: {{ data_get($firmas,'decano.cargo') }}<br>Fecha: {{ $fecha(data_get($firmas,'decano.fecha')) }}@endif</div></td></tr></table>

    <h2 class="inf-section-title">XII. Anexos</h2>
    <p>Deberán adjuntarse, entre otros: material generado por el proyecto; formularios de encuestas; informes de procesamiento de datos; fotografías; videos cortos; y evidencias de difusión de las acciones del proyecto.</p>
    <h3 class="inf-subtitle">Instrumentos de formalización y respaldos de contraparte</h3>
    <table class="inf-table inf-small"><thead><tr><th style="width:6%">N.º</th><th>Contraparte</th><th>Instrumento o respaldo</th><th style="width:24%">Archivo</th></tr></thead><tbody>@forelse($instrumentosContraparte as $row)<tr><td class="inf-center">{{ $loop->iteration }}</td><td>{{ $row->contraparte?->nombre }}</td><td>{{ $row->descripcion }}</td><td>{{ $row->nombre_archivo ?: ($row->archivo ? basename($row->archivo) : 'Pendiente') }}</td></tr>@empty<tr><td colspan="4" class="inf-empty">Sin instrumentos de contraparte registrados.</td></tr>@endforelse</tbody></table>
    <h3 class="inf-subtitle">Documentos generales</h3>
    <table class="inf-table inf-small"><thead><tr><th style="width:6%">N.º</th><th style="width:16%">Tipo</th><th>Descripción</th><th style="width:14%">Fecha</th><th style="width:24%">Referencia</th></tr></thead><tbody>@forelse($documentosGenerales as $row)<tr><td class="inf-center">{{ $loop->iteration }}</td><td>{{ $row->tipo }}</td><td>{{ $row->descripcion }}</td><td>{{ $fecha($row->fecha) }}</td><td>{{ $row->enlace ?: ($row->nombre_archivo ?: ($row->archivo ? basename($row->archivo) : '')) }}</td></tr>@empty<tr><td colspan="5" class="inf-empty">Sin documentos generales registrados.</td></tr>@endforelse</tbody></table>
    <h3 class="inf-subtitle">Fotografías del proyecto</h3>
    <div class="inf-photo-grid">@forelse($fotografias as $row)<div class="inf-photo-card">@if($rutaAnexo($row))<img src="{{ $rutaAnexo($row) }}" alt="">@endif<strong>{{ $row->nombre_archivo ?: 'Fotografía '.$loop->iteration }}</strong><br>{{ $row->descripcion ?: 'Sin descripción' }}@if($row->fecha)<br>{{ $fecha($row->fecha) }}@endif</div>@empty<p class="inf-empty">Sin fotografías registradas.</p>@endforelse</div>
</main>
