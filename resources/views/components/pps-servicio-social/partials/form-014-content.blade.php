{{-- Cuerpo del FORM-DVUS-014 (secciones I–IX). Recibe del padre:
     $registro (objeto de fields), $checked, y los helpers $dato, $cb, $fechaParte. --}}

{{-- ===================== I. INFORMACIÓN GENERAL ===================== --}}
<div class="section">
    <div class="section-bar">I. Información general</div>
    <table class="grid">
        <colgroup>
            <col style="width:44px;">
            <col style="width:34%;">
            <col><col><col>
        </colgroup>
        <tr>
            <td class="num" rowspan="2">1</td>
            <td class="lbl" rowspan="2">Fecha de registro</td>
            <td class="subhdr">Año</td>
            <td class="subhdr">Mes</td>
            <td class="subhdr">Día</td>
        </tr>
        <tr>
            <td class="data center">{{ $fechaParte($fechaRegistro, 'anio') }}</td>
            <td class="data center">{{ $fechaParte($fechaRegistro, 'mes') }}</td>
            <td class="data center">{{ $fechaParte($fechaRegistro, 'dia') }}</td>
        </tr>
        <tr>
            <td class="num">2</td>
            <td class="lbl">Facultad /Centro Universitario Regional/Instituto Tecnológico</td>
            <td class="data" colspan="3">{!! $dato($registro->facultad_centro) !!}</td>
        </tr>
        <tr>
            <td class="num">3</td>
            <td class="lbl">Carrera</td>
            <td class="data" colspan="3">{!! $dato($registro->carrera) !!}</td>
        </tr>
    </table>
</div>

{{-- ===================== II. DATOS DEL ESTUDIANTE ===================== --}}
<div class="section">
    <div class="section-bar">II. Datos del estudiante</div>
    <table class="grid">
        <colgroup>
            <col style="width:44px;">
            <col style="width:42%;">
            <col>
        </colgroup>
        <tr><td class="num">4</td><td class="lbl">Número de Cuenta</td><td class="data">{!! $dato($registro->numero_cuenta) !!}</td></tr>
        <tr><td class="num">5</td><td class="lbl">Nombre completo (exactamente como aparece en la tarjeta de identidad)</td><td class="data">{!! $dato($registro->nombre_estudiante) !!}</td></tr>
        <tr><td class="num">6</td><td class="lbl">Número de celular</td><td class="data">{!! $dato($registro->celular_estudiante) !!}</td></tr>
        <tr><td class="num">7</td><td class="lbl">Correo electrónico institucional</td><td class="data">{!! $dato($registro->correo_institucional) !!}</td></tr>
        <tr><td class="num">8</td><td class="lbl">Correo electrónico personal</td><td class="data">{!! $dato($registro->correo_personal) !!}</td></tr>
    </table>
</div>

{{-- ============ III. INFORMACIÓN DE LA PRÁCTICA / SERVICIO SOCIAL ============ --}}
<div class="section">
    <div class="section-bar">III. Información de la práctica profesional / servicio social</div>
    <table class="grid">
        <colgroup>
            <col style="width:44px;">
            <col style="width:32%;">
            <col><col><col><col><col><col>
        </colgroup>
        <tr>
            <td class="num">9</td>
            <td class="lbl">Tipo</td>
            <td class="cas" colspan="6">
                <span class="opt">{!! $cb($checked['tipo_pps']['pps'] ?? false) !!} Práctica Profesional Supervisada</span>
                <span class="opt">{!! $cb($checked['tipo_pps']['servicio_social'] ?? false) !!} Servicio Social</span>
            </td>
        </tr>
        <tr>
            <td class="num" rowspan="3">10</td>
            <td class="lbl" rowspan="3">Fecha de ejecución de la PPS / servicio social</td>
            <td class="subhdr" colspan="3">Fecha de inicio</td>
            <td class="subhdr" colspan="3">Fecha de finalización</td>
        </tr>
        <tr>
            <td class="subhdr">Día</td><td class="subhdr">Mes</td><td class="subhdr">Año</td>
            <td class="subhdr">Día</td><td class="subhdr">Mes</td><td class="subhdr">Año</td>
        </tr>
        <tr>
            <td class="data center">{{ $fechaParte($registro->fecha_inicio, 'dia') }}</td>
            <td class="data center">{{ $fechaParte($registro->fecha_inicio, 'mes') }}</td>
            <td class="data center">{{ $fechaParte($registro->fecha_inicio, 'anio') }}</td>
            <td class="data center">{{ $fechaParte($registro->fecha_finalizacion, 'dia') }}</td>
            <td class="data center">{{ $fechaParte($registro->fecha_finalizacion, 'mes') }}</td>
            <td class="data center">{{ $fechaParte($registro->fecha_finalizacion, 'anio') }}</td>
        </tr>
    </table>
</div>

<div class="section section--page-break">
    <table class="grid">
        <colgroup>
            <col style="width:44px;">
            <col style="width:32%;">
            <col><col><col><col><col><col>
        </colgroup>
        <tr>
            <td class="num">11</td>
            <td class="lbl">Tipo de instrumento que formaliza la PPS / SS</td>
            <td class="cas" colspan="6">
                <span class="opt-block">{!! $cb($checked['instrumento']['carta_formal'] ?? false) !!} Carta formal de solicitud a la unidad académica</span>
                <span class="opt-block">{!! $cb($checked['instrumento']['carta_intenciones'] ?? false) !!} Carta de intenciones con la UNAH</span>
                <span class="opt-block">{!! $cb($checked['instrumento']['convenio_marco'] ?? false) !!} Convenio marco con la UNAH</span>
            </td>
        </tr>
        <tr>
            <td class="num">12</td>
            <td class="lbl">Territorio de ejecución</td>
            <td class="cas" colspan="6">
                <span class="opt">{!! $cb($checked['territorio']['nacional'] ?? false) !!} Nacional</span>
                <span class="opt">{!! $cb($checked['territorio']['internacional'] ?? false) !!} Internacional</span>
            </td>
        </tr>
    </table>
</div>

{{-- ============ IV. DATOS TERRITORIALES ============ --}}
<div class="section">
    <div class="section-bar">IV. Datos territoriales de la PPS / servicio social</div>
    <table class="grid">
        <colgroup>
            <col style="width:40px;">
            <col style="width:28%;">
            <col><col><col><col>
        </colgroup>
        <tr>
            <td class="num" rowspan="2">13</td>
            <td class="lbl" rowspan="2">Modalidad</td>
            <td class="subhdr">100% presencial</td>
            <td class="subhdr">Híbrida (presencial + teletrabajo)</td>
            <td class="subhdr" colspan="2">Teletrabajo</td>
        </tr>
        <tr>
            <td class="data center">{!! $cb($checked['modalidad']['presencial'] ?? false) !!}</td>
            <td class="data center">{!! $cb($checked['modalidad']['hibrida'] ?? false) !!}</td>
            <td class="data center" colspan="2">{!! $cb($checked['modalidad']['teletrabajo'] ?? false) !!}</td>
        </tr>
        <tr><td class="subbar" colspan="6">14.&nbsp;&nbsp;Práctica presencial</td></tr>
        <tr>
            <td class="num">14.1</td>
            <td class="lbl-g">Región</td>
            <td class="data" colspan="4">{!! $dato($registro->region) !!}</td>
        </tr>
        <tr><td class="num">14.2</td><td class="lbl-g">País</td><td class="data" colspan="4">{!! $dato($registro->pais) !!}</td></tr>
        <tr><td class="num">14.3</td><td class="lbl-g">Departamento / provincia</td><td class="data" colspan="4">{!! $dato($registro->departamento ?: $registro->departamento_provincia) !!}</td></tr>
        <tr><td class="num">14.4</td><td class="lbl-g">Municipio</td><td class="data" colspan="4">{!! $dato($registro->municipio) !!}</td></tr>
        <tr><td class="num">14.5</td><td class="lbl-g">Aldea (incluye ciudad)</td><td class="data" colspan="4">{!! $dato($registro->aldea_ciudad) !!}</td></tr>
        <tr><td class="num">14.6</td><td class="lbl-g">Caserío</td><td class="data" colspan="4">{!! $dato($registro->caserio) !!}</td></tr>

        <tr><td class="subbar" colspan="6">15.&nbsp;&nbsp;Práctica en modalidad teletrabajo</td></tr>
        <tr><td class="num">15.1</td><td class="lbl-g">País de la sede principal</td><td class="data" colspan="4">{!! $dato($registro->pais_sede_principal) !!}</td></tr>
        <tr><td class="num">15.2</td><td class="lbl-g">Departamento / provincia sede principal</td><td class="data" colspan="4">{!! $dato($registro->departamento_provincia_sede_principal) !!}</td></tr>
        <tr><td class="num">15.3</td><td class="lbl-g">Municipio</td><td class="data" colspan="4">{!! $dato($registro->municipio_sede_principal) !!}</td></tr>
        <tr><td class="num">15.4</td><td class="lbl-g">Aldea / ciudad</td><td class="data" colspan="4">{!! $dato($registro->aldea_ciudad_sede_principal) !!}</td></tr>

        <tr><td class="subbar" colspan="6">16.&nbsp;&nbsp;Distribución de la jornada</td></tr>
        <tr>
            <td class="lbl-g" colspan="2">Horas presenciales</td>
            <td class="data center">{!! $dato($registro->horas_presenciales) !!}</td>
            <td class="lbl-g">Horas teletrabajo</td>
            <td class="data center" colspan="2">{!! $dato($registro->horas_teletrabajo) !!}</td>
        </tr>
    </table>
</div>

{{-- ============ V. ALCANCES ============ --}}
<div class="section section--page-break">
    <div class="section-bar">V. Alcances de la PPS / servicio social</div>
    <table class="grid">
        <colgroup>
            <col style="width:44px;">
            <col style="width:24%;">
            <col>
            <col style="width:24%;">
            <col>
        </colgroup>
        <tr>
            <td class="num">17</td>
            <td class="lbl">Descripción del tipo de PPS</td>
            <td class="data">{!! $dato($registro->descripcion_tipo_pps) !!}</td>
            <td class="lbl">Descripción de las Horas del tipo de PPS/SS. (Se detalla el total de las horas en número entero)</td>
            <td class="data">{!! $dato($registro->descripcion_horas_tipo_pps_ss ?: $registro->total_horas) !!}</td>
        </tr>
        <tr>
            <td class="num">18</td>
            <td class="lbl">Nombre del departamento o área en el que se realizará la PPS/SS (Ejemplo: Departamento de Contabilidad, Gerencia de Recursos Humanos, etc)</td>
            <td class="data" colspan="3">{!! $dato($registro->area_realizacion) !!}</td>
        </tr>
        <tr>
            <td class="num">19</td>
            <td class="lbl">Resumen de las responsabilidades y tareas que realizará</td>
            <td class="data" colspan="3">{!! $dato($registro->resumen_responsabilidades) !!}</td>
        </tr>
    </table>
</div>

{{-- ============ VI. INFORMACIÓN DE LA INSTITUCIÓN / EMPRESA ============ --}}
<div class="section">
    <div class="section-bar">VI. Información de la institución / empresa</div>
    <table class="grid">
        <colgroup>
            <col style="width:44px;">
            <col style="width:36%;">
            <col>
        </colgroup>
        <tr>
            <td class="num">20</td>
            <td class="lbl">Nacionalidad</td>
            <td class="cas">
                <span class="opt">{!! $cb($checked['institucion_nacionalidad']['nacional'] ?? false) !!} Nacional</span>
                <span class="opt">{!! $cb($checked['institucion_nacionalidad']['pais'] ?? false) !!} País: {!! $dato($registro->institucion_pais) !!}</span>
            </td>
        </tr>
        <tr><td class="num">21</td><td class="lbl">Nombre completo de la institución / organización:</td><td class="data">{!! $dato($registro->nombre_institucion) !!}</td></tr>
        <tr><td class="num">22</td><td class="lbl">Breve descripción de los compromisos asumidos por la institución / organización.</td><td class="data">{!! $dato($registro->compromisos_institucion) !!}</td></tr>
        <tr><td class="num">23</td><td class="lbl">Dirección exacta de la sede principal</td><td class="data">{!! $dato($registro->direccion_institucion) !!}</td></tr>
        <tr><td class="num">24</td><td class="lbl">Nombre completo del representante legal:</td><td class="data">{!! $dato($registro->representante_legal) !!}</td></tr>
    </table>
</div>

<div class="section section--page-break">
    <table class="grid">
        <colgroup>
            <col style="width:44px;">
            <col style="width:36%;">
            <col>
        </colgroup>
        <tr><td class="num">25</td><td class="lbl">Número de teléfono:</td><td class="data">{!! $dato($registro->telefono_representante) !!}</td></tr>
        <tr><td class="num">26</td><td class="lbl">Correo electrónico del departamento de recursos humanos</td><td class="data">{!! $dato($registro->correo_rrhh) !!}</td></tr>
        <tr>
            <td class="num">27</td>
            <td class="lbl">Tipo de institución / organización</td>
            <td class="cas">
                <span class="opt">{!! $cb($checked['tipo_institucion']['gobierno_nacional'] ?? false) !!} Gobierno Nacional</span>
                <span class="opt">{!! $cb($checked['tipo_institucion']['gobierno_municipal'] ?? false) !!} Gobierno Municipal</span>
                <span class="opt">{!! $cb($checked['tipo_institucion']['ong'] ?? false) !!} ONG</span>
                <span class="opt">{!! $cb($checked['tipo_institucion']['sociedad_civil'] ?? false) !!} Sociedad civil organizada</span>
                <span class="opt">{!! $cb($checked['tipo_institucion']['sector_privado'] ?? false) !!} Sector Privado</span>
                <span class="opt">{!! $cb($checked['tipo_institucion']['internacional'] ?? false) !!} Internacional</span>
            </td>
        </tr>
        <tr>
            <td class="num">28</td>
            <td class="lbl">Sector al que pertenece la institución / organización</td>
            <td class="cas">
                <span class="opt">{!! $cb($checked['sector_institucion']['agricultura'] ?? false) !!} Agricultura, alimentación y silvicultura</span>
                <span class="opt">{!! $cb($checked['sector_institucion']['energia_mineria'] ?? false) !!} Energía y minería</span>
                <span class="opt">{!! $cb($checked['sector_institucion']['produccion'] ?? false) !!} Producción</span>
                <span class="opt">{!! $cb($checked['sector_institucion']['servicios_privados'] ?? false) !!} Sectores de servicios privados</span>
                <span class="opt">{!! $cb($checked['sector_institucion']['infraestructura'] ?? false) !!} Infraestructura, construcción y sectores relacionados</span>
                <span class="opt">{!! $cb($checked['sector_institucion']['educacion'] ?? false) !!} Educación e investigación</span>
                <span class="opt">{!! $cb($checked['sector_institucion']['servicios_publicos'] ?? false) !!} Servicios y función públicos</span>
                <span class="opt">{!! $cb($checked['sector_institucion']['transporte'] ?? false) !!} Transporte, transporte marítimo y aéreo</span>
            </td>
        </tr>
        <tr><td class="subbar" colspan="3">29.&nbsp;&nbsp;Información del jefe directo de la PPS/SS</td></tr>
        <tr><td class="num">29.1</td><td class="lbl-g">Nombre completo del contacto directo (Jefe directo de la PPS / servicio social)</td><td class="data">{!! $dato($registro->nombre_jefe_directo) !!}</td></tr>
        <tr><td class="num">29.2</td><td class="lbl-g">Número de celular del contacto directo (Jefe directo de la PPS / servicio social)</td><td class="data">{!! $dato($registro->celular_jefe_directo) !!}</td></tr>
        <tr><td class="num">29.3</td><td class="lbl-g">Correo Electrónico del contacto directo (Jefe directo de la PPS / servicio social)</td><td class="data">{!! $dato($registro->correo_jefe_directo) !!}</td></tr>
        <tr><td class="num">29.4</td><td class="lbl-g">Cargo del jefe directo de la PPS / servicio social</td><td class="data">{!! $dato($registro->cargo_jefe_directo) !!}</td></tr>
        <tr><td class="num">29.5</td><td class="lbl-g">Grado académico del jefe directo de la PPS / SS</td><td class="data">{!! $dato($registro->grado_academico_jefe_directo) !!}</td></tr>
    </table>
</div>

{{-- ============ VII. DOCENTE SUPERVISOR(A) ============ --}}
<div class="section">
    <div class="section-bar">VII. Información del(a) docente supervisor(a) de la PPS – SS</div>
    <table class="grid">
        <colgroup>
            <col style="width:44px;">
            <col style="width:42%;">
            <col>
        </colgroup>
        <tr><td class="num">30</td><td class="lbl">Nombre Completo del Supervisor/a de la PPS</td><td class="data">{!! $dato($registro->nombre_docente_supervisor) !!}</td></tr>
        <tr><td class="num">31</td><td class="lbl">No. de empleado/a del Supervisor/a de la PPS</td><td class="data">{!! $dato($registro->numero_empleado_docente) !!}</td></tr>
    </table>
</div>

<div class="section section--page-break">
    <table class="grid">
        <colgroup>
            <col style="width:44px;">
            <col style="width:42%;">
            <col>
        </colgroup>
        <tr><td class="num">32</td><td class="lbl">Número de celular del Supervisor/a de la PPS</td><td class="data">{!! $dato($registro->celular_docente) !!}</td></tr>
        <tr><td class="num">33</td><td class="lbl">Correo Electrónico del Supervisor/a de la PPS</td><td class="data">{!! $dato($registro->correo_docente) !!}</td></tr>
        <tr><td class="num">34</td><td class="lbl">Categoría del Supervisor/a de la PPS</td><td class="data">{!! $dato($registro->categoria_docente) !!}</td></tr>
        <tr><td class="num">35</td><td class="lbl">Departamento al que pertenece el Supervisor/a de la PPS</td><td class="data">{!! $dato($registro->departamento_docente) !!}</td></tr>
        <tr><td class="num">36</td><td class="lbl">Jornada Laboral</td><td class="data">{!! $dato($registro->jornada_laboral_docente) !!}</td></tr>
        <tr><td class="num">37</td><td class="lbl">Ubicación del cubículo en la UNAH</td><td class="data">{!! $dato($registro->ubicacion_cubiculo_docente) !!}</td></tr>
    </table>
</div>

{{-- ============ VIII. FIRMAS ============ --}}
<div class="section">
    <div class="section-bar">VIII. Firmas</div>
    <table class="grid sign">
        <tr>
            <td class="subhdr">Coordinador(a) de la carrera</td>
            <td class="subhdr">Supervisor(a) de la PPS / SS</td>
            <td class="subhdr">Estudiante que realiza la PPS / SS</td>
        </tr>
        <tr>
            <td>
                Nombre: {!! $dato($firmas['coordinador']['nombre'] ?? null) !!}
                @if(!empty($firmas['coordinador']['src']))<img class="signature" src="{{ $firmas['coordinador']['src'] }}" alt="Firma del coordinador">@endif
                <div class="sline"></div>
                <div class="scap">Firma del(a) coordinador(a) de la carrera</div>
            </td>
            <td>
                Nombre: {!! $dato($registro->nombre_docente_supervisor) !!}
                @if(!empty($firmas['supervisor']['src']))<img class="signature" src="{{ $firmas['supervisor']['src'] }}" alt="Firma del supervisor">@endif
                <div class="sline"></div>
                <div class="scap">Firma del(a) supervisor(a) de la PPS / SS</div>
            </td>
            <td>
                Nombre: {!! $dato($registro->nombre_estudiante) !!}
                @if(!empty($firmas['estudiante']['src']))<img class="signature" src="{{ $firmas['estudiante']['src'] }}" alt="Firma del estudiante">@endif
                <div class="sline"></div>
                <div class="scap">Firma del(a) estudiante que realiza la PPS / SS</div>
            </td>
        </tr>
    </table>
</div>

{{-- ============ IX. DOCUMENTOS ADJUNTOS ============ --}}
<div class="section">
    <div class="section-bar">IX. Documentos adjuntos a la ficha</div>
    <table class="grid">
        <colgroup>
            <col style="width:44px;">
            <col>
            <col style="width:42px;">
            <col style="width:42px;">
        </colgroup>
        <tr>
            <td class="subhdr">No</td>
            <td class="subhdr">Descripción</td>
            <td class="subhdr">Si</td>
            <td class="subhdr">No</td>
        </tr>
        <tr>
            <td class="center">1</td>
            <td class="data">Carta de formalización de la PPS firmada por la contraparte</td>
            <td class="center">{!! $cb((bool) $registro->adjunta_carta_formalizacion) !!}</td>
            <td class="center">{!! $cb(!$registro->adjunta_carta_formalizacion) !!}</td>
        </tr>
        <tr>
            <td class="center">2</td>
            <td class="data">Convenio marco entre la UNAH y entidad (en el caso de tenerse)</td>
            <td class="center">{!! $cb((bool) $registro->adjunta_convenio_marco) !!}</td>
            <td class="center">{!! $cb(!$registro->adjunta_convenio_marco) !!}</td>
        </tr>
    </table>
</div>
