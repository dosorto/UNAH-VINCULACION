<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>FORM-DVUS-015/016</title>
    <style>
        @page {
            size: letter portrait;
            margin: 11mm 9mm;
        }

        body {
            color: #111827;
            font-family: "DejaVu Sans", sans-serif;
            font-size: 9.2px;
            line-height: 1.25;
            margin: 0;
        }

        table {
            border-collapse: collapse;
            width: 100%;
        }

        th,
        td {
            border: 1px solid #2f2f2f;
            padding: 4px 5px;
            vertical-align: top;
        }

        .header-table td {
            border: 0;
            padding: 0 0 5px;
        }

        .logo {
            height: 58px;
            max-width: 430px;
        }

        .contact {
            color: #0f3d73;
            font-size: 8.5px;
            font-weight: bold;
            text-align: right;
        }

        .form-code {
            border: 1px solid #2f2f2f;
            font-size: 11px;
            font-weight: bold;
            padding: 5px;
            text-align: center;
        }

        .main-title {
            font-size: 12.5px;
            font-weight: bold;
            line-height: 1.25;
            padding-top: 6px;
            text-align: center;
            text-transform: uppercase;
        }

        .section-title {
            background: #e5e7eb;
            border: 1px solid #2f2f2f;
            font-size: 10px;
            font-weight: bold;
            margin-top: 7px;
            padding: 5px 6px;
            text-transform: uppercase;
        }

        .num {
            text-align: center;
            width: 28px;
        }

        .label {
            font-weight: bold;
            width: 43%;
        }

        .value {
            min-height: 14px;
        }

        .muted {
            color: #4b5563;
        }

        .options {
            line-height: 1.7;
        }

        .box {
            border: 1px solid #111827;
            display: inline-block;
            font-size: 8px;
            font-weight: bold;
            height: 10px;
            line-height: 10px;
            margin-right: 4px;
            text-align: center;
            width: 10px;
        }

        .signature-table td {
            height: 72px;
            text-align: center;
            width: 33.33%;
        }

        .signature-line {
            border-top: 1px solid #111827;
            margin: 32px auto 5px;
            width: 82%;
        }

        .documents-table th {
            background: #f3f4f6;
            font-weight: bold;
            text-align: center;
        }

        .center {
            text-align: center;
        }

        .avoid-break {
            page-break-inside: avoid;
        }
    </style>
</head>
<body>
@php
    $valor = function ($campo): string {
        return filled($campo) ? (string) $campo : '____________________________';
    };

    $fecha = function ($campo): string {
        return $campo ? $campo->format('d/m/Y') : '____/____/______';
    };

    $normalizar = function ($campo): string {
        return \Illuminate\Support\Str::of((string) $campo)
            ->ascii()
            ->lower()
            ->trim()
            ->toString();
    };

    $checkboxMarcado = function ($campo, string $opcion) use ($normalizar): string {
        return $normalizar($campo) === $normalizar($opcion) ? 'X' : '';
    };

    $checkboxSiNo = fn (bool $valor, bool $opcion): string => $valor === $opcion ? 'X' : '';
@endphp

<table class="header-table">
    <tr>
        <td style="width: 68%;">
            @if(file_exists(public_path('images/Image/Imagen1.jpg')))
                <img class="logo" src="{{ public_path('images/Image/Imagen1.jpg') }}" alt="UNAH">
            @else
                <strong>UNIVERSIDAD NACIONAL AUTONOMA DE HONDURAS</strong>
            @endif
        </td>
        <td class="contact" style="width: 32%;">
            vinculacion.sociedad@unah.edu.hn<br>
            Tel. 2216-7070 Ext. 110576
        </td>
    </tr>
</table>

<div class="form-code">FORM-DVUS-015/016</div>
<div class="main-title">
    Formulario de Registro de Práctica Profesional Supervisada o Servicio Social
</div>

<div class="section-title">I. Información general</div>
<table>
    <tr>
        <td class="num">1</td>
        <td class="label">Facultad / Centro Universitario Regional / Instituto Tecnológico</td>
        <td class="value">{{ $valor($registro->facultad_centro) }}</td>
    </tr>
    <tr>
        <td class="num">2</td>
        <td class="label">Carrera</td>
        <td class="value">{{ $valor($registro->carrera) }}</td>
    </tr>
</table>

<div class="section-title">II. Datos del estudiante</div>
<table>
    <tr><td class="num">3</td><td class="label">Número de cuenta</td><td>{{ $valor($registro->numero_cuenta) }}</td></tr>
    <tr><td class="num">4</td><td class="label">Nombre completo</td><td>{{ $valor($registro->nombre_estudiante) }}</td></tr>
    <tr><td class="num">5</td><td class="label">Número de celular</td><td>{{ $valor($registro->celular_estudiante) }}</td></tr>
    <tr><td class="num">6</td><td class="label">Correo electrónico institucional</td><td>{{ $valor($registro->correo_institucional) }}</td></tr>
    <tr><td class="num">7</td><td class="label">Correo electrónico personal</td><td>{{ $valor($registro->correo_personal) }}</td></tr>
</table>

<div class="section-title">III. Información de la Práctica Profesional / Servicio Social</div>
<table>
    <tr>
        <td class="num">8</td>
        <td class="label">Tipo</td>
        <td class="options">
            <span class="box">{{ $checkboxMarcado($registro->tipo_pps_ss, 'Práctica Profesional Supervisada') }}</span> Práctica Profesional Supervisada<br>
            <span class="box">{{ $checkboxMarcado($registro->tipo_pps_ss, 'Servicio Social') }}</span> Servicio Social
        </td>
    </tr>
    <tr><td class="num">9</td><td class="label">Fecha de inicio</td><td>{{ $fecha($registro->fecha_inicio) }}</td></tr>
    <tr><td class="num">10</td><td class="label">Fecha de finalización</td><td>{{ $fecha($registro->fecha_finalizacion) }}</td></tr>
    <tr>
        <td class="num">11</td>
        <td class="label">Tipo de instrumento que formaliza la PPS / SS</td>
        <td class="options">
            <span class="box">{{ $checkboxMarcado($registro->tipo_instrumento, 'Carta formal de solicitud a la unidad académica') }}</span> Carta formal de solicitud a la unidad académica<br>
            <span class="box">{{ $checkboxMarcado($registro->tipo_instrumento, 'Carta de intenciones con la UNAH') }}</span> Carta de intenciones con la UNAH<br>
            <span class="box">{{ $checkboxMarcado($registro->tipo_instrumento, 'Convenio marco con la UNAH') }}</span> Convenio marco con la UNAH
        </td>
    </tr>
    <tr>
        <td class="num">12</td>
        <td class="label">Territorio de ejecución</td>
        <td class="options">
            <span class="box">{{ $checkboxMarcado($registro->territorio_ejecucion, 'Nacional') }}</span> Nacional
            &nbsp;&nbsp;
            <span class="box">{{ $checkboxMarcado($registro->territorio_ejecucion, 'Internacional') }}</span> Internacional
        </td>
    </tr>
</table>

<div class="section-title">IV. Datos territoriales de la PPS/SS de tipo nacional</div>
<table>
    <tr><td class="num">13</td><td class="label">Departamento</td><td>{{ $valor($registro->departamento) }}</td></tr>
    <tr><td class="num">14</td><td class="label">Municipio</td><td>{{ $valor($registro->municipio) }}</td></tr>
    <tr><td class="num">15</td><td class="label">Aldea / ciudad</td><td>{{ $valor($registro->aldea_ciudad) }}</td></tr>
    <tr><td class="num">16</td><td class="label">Caserío</td><td>{{ $valor($registro->caserio) }}</td></tr>
</table>

<div class="section-title">V. Alcance de la PPS/Servicio Social</div>
<table>
    <tr><td class="num">17</td><td class="label">Descripción del tipo de PPS</td><td>{{ $valor($registro->descripcion_tipo_pps) }}</td></tr>
    <tr><td class="num">18</td><td class="label">Total de horas</td><td>{{ $valor($registro->total_horas) }}</td></tr>
    <tr><td class="num">19</td><td class="label">Departamento o área donde se realizará</td><td>{{ $valor($registro->area_realizacion) }}</td></tr>
    <tr><td class="num">20</td><td class="label">Resumen de responsabilidades y tareas</td><td>{{ $valor($registro->resumen_responsabilidades) }}</td></tr>
    <tr>
        <td class="num">21</td>
        <td class="label">Modalidad de ejecución</td>
        <td class="options">
            <span class="box">{{ $checkboxMarcado($registro->modalidad_ejecucion, 'Presencial') }}</span> Presencial
            &nbsp;&nbsp;
            <span class="box">{{ $checkboxMarcado($registro->modalidad_ejecucion, '100% virtual') }}</span> 100% virtual
            &nbsp;&nbsp;
            <span class="box">{{ $checkboxMarcado($registro->modalidad_ejecucion, 'Híbrida') }}</span> Híbrida
        </td>
    </tr>
</table>

<div class="section-title">VI. Información de la institución / organización donde se realiza la PPS – Servicio Social</div>
<table>
    <tr><td class="num">22</td><td class="label">Nombre completo de la institución / organización</td><td>{{ $valor($registro->nombre_institucion) }}</td></tr>
    <tr><td class="num">23</td><td class="label">Breve descripción de los compromisos asumidos por la institución / organización</td><td>{{ $valor($registro->compromisos_institucion) }}</td></tr>
    <tr><td class="num">24</td><td class="label">Dirección exacta de la sede principal</td><td>{{ $valor($registro->direccion_institucion) }}</td></tr>
    <tr><td class="num">25</td><td class="label">Nombre completo del representante legal</td><td>{{ $valor($registro->representante_legal) }}</td></tr>
    <tr><td class="num">26</td><td class="label">Número de teléfono</td><td>{{ $valor($registro->telefono_representante) }}</td></tr>
    <tr><td class="num">27</td><td class="label">Correo electrónico del departamento de recursos humanos</td><td>{{ $valor($registro->correo_rrhh) }}</td></tr>
    <tr>
        <td class="num">28</td>
        <td class="label">Tipo de institución / organización</td>
        <td class="options">
            <span class="box">{{ $checkboxMarcado($registro->tipo_institucion, 'Gobierno Nacional') }}</span> Gobierno Nacional
            &nbsp;&nbsp;<span class="box">{{ $checkboxMarcado($registro->tipo_institucion, 'Gobierno Municipal') }}</span> Gobierno Municipal<br>
            <span class="box">{{ $checkboxMarcado($registro->tipo_institucion, 'ONG') }}</span> ONG
            &nbsp;&nbsp;<span class="box">{{ $checkboxMarcado($registro->tipo_institucion, 'Sociedad civil organizada') }}</span> Sociedad civil organizada<br>
            <span class="box">{{ $checkboxMarcado($registro->tipo_institucion, 'Sector Privado') }}</span> Sector Privado
            &nbsp;&nbsp;<span class="box">{{ $checkboxMarcado($registro->tipo_institucion, 'Internacional') }}</span> Internacional
        </td>
    </tr>
    <tr>
        <td class="num">29</td>
        <td class="label">Sector al que pertenece la institución / organización</td>
        <td class="options">
            <span class="box">{{ $checkboxMarcado($registro->sector_institucion, 'Agricultura, alimentación y silvicultura') }}</span> Agricultura, alimentación y silvicultura<br>
            <span class="box">{{ $checkboxMarcado($registro->sector_institucion, 'Energía y minería') }}</span> Energía y minería<br>
            <span class="box">{{ $checkboxMarcado($registro->sector_institucion, 'Producción') }}</span> Producción<br>
            <span class="box">{{ $checkboxMarcado($registro->sector_institucion, 'Sectores de servicios privados') }}</span> Sectores de servicios privados<br>
            <span class="box">{{ $checkboxMarcado($registro->sector_institucion, 'Infraestructura, construcción y sectores relacionados') }}</span> Infraestructura, construcción y sectores relacionados<br>
            <span class="box">{{ $checkboxMarcado($registro->sector_institucion, 'Educación e investigación') }}</span> Educación e investigación<br>
            <span class="box">{{ $checkboxMarcado($registro->sector_institucion, 'Servicios y función públicos') }}</span> Servicios y función públicos<br>
            <span class="box">{{ $checkboxMarcado($registro->sector_institucion, 'Transporte, transporte marítimo y aéreo') }}</span> Transporte, transporte marítimo y aéreo
        </td>
    </tr>
</table>

<div class="section-title">VII. Información del jefe directo de la PPS/SS</div>
<table>
    <tr><td class="num">30</td><td class="label">Nombre completo del contacto directo</td><td>{{ $valor($registro->nombre_jefe_directo) }}</td></tr>
    <tr><td class="num">31</td><td class="label">Número de celular del contacto directo</td><td>{{ $valor($registro->celular_jefe_directo) }}</td></tr>
    <tr><td class="num">32</td><td class="label">Correo electrónico del contacto directo</td><td>{{ $valor($registro->correo_jefe_directo) }}</td></tr>
    <tr><td class="num">33</td><td class="label">Cargo del jefe directo</td><td>{{ $valor($registro->cargo_jefe_directo) }}</td></tr>
    <tr><td class="num">34</td><td class="label">Grado académico del jefe directo</td><td>{{ $valor($registro->grado_academico_jefe_directo) }}</td></tr>
</table>

<div class="section-title">VIII. Información del(a) docente supervisor(a) de la PPS - SS</div>
<table>
    <tr><td class="num">35</td><td class="label">Nombre completo del supervisor/a</td><td>{{ $valor($registro->nombre_docente_supervisor) }}</td></tr>
    <tr><td class="num">36</td><td class="label">No. de empleado/a</td><td>{{ $valor($registro->numero_empleado_docente) }}</td></tr>
    <tr><td class="num">37</td><td class="label">Número de celular</td><td>{{ $valor($registro->celular_docente) }}</td></tr>
    <tr><td class="num">38</td><td class="label">Correo electrónico</td><td>{{ $valor($registro->correo_docente) }}</td></tr>
    <tr><td class="num">39</td><td class="label">Categoría</td><td>{{ $valor($registro->categoria_docente) }}</td></tr>
    <tr><td class="num">40</td><td class="label">Departamento al que pertenece</td><td>{{ $valor($registro->departamento_docente) }}</td></tr>
    <tr><td class="num">41</td><td class="label">Jornada laboral</td><td>{{ $valor($registro->jornada_laboral_docente) }}</td></tr>
    <tr><td class="num">42</td><td class="label">Ubicación del cubículo en la UNAH</td><td>{{ $valor($registro->ubicacion_cubiculo_docente) }}</td></tr>
</table>

<div class="section-title avoid-break">IX. Firmas</div>
<table class="signature-table avoid-break">
    <tr>
        <td>
            <strong>Coordinador(a) de la carrera</strong><br><br>
            Nombre: __________________________
            <div class="signature-line"></div>
            Línea de firma
        </td>
        <td>
            <strong>Supervisor(a) de la PPS / SS</strong><br><br>
            Nombre: {{ $valor($registro->nombre_docente_supervisor) }}
            <div class="signature-line"></div>
            Línea de firma
        </td>
        <td>
            <strong>Estudiante que realiza la PPS / SS</strong><br><br>
            Nombre: {{ $valor($registro->nombre_estudiante) }}
            <div class="signature-line"></div>
            Línea de firma
        </td>
    </tr>
</table>

<div class="section-title avoid-break">X. Documentos adjuntos a la ficha</div>
<table class="documents-table avoid-break">
    <tr>
        <th style="width: 34px;">No</th>
        <th>Descripción</th>
        <th style="width: 44px;">Sí</th>
        <th style="width: 44px;">No</th>
    </tr>
    <tr>
        <td class="center">1</td>
        <td>Carta de formalización de la PPS firmada por la contraparte</td>
        <td class="center"><span class="box">{{ $checkboxSiNo((bool) $registro->adjunta_carta_formalizacion, true) }}</span></td>
        <td class="center"><span class="box">{{ $checkboxSiNo((bool) $registro->adjunta_carta_formalizacion, false) }}</span></td>
    </tr>
    <tr>
        <td class="center">2</td>
        <td>Convenio marco entre la UNAH y entidad</td>
        <td class="center"><span class="box">{{ $checkboxSiNo((bool) $registro->adjunta_convenio_marco, true) }}</span></td>
        <td class="center"><span class="box">{{ $checkboxSiNo((bool) $registro->adjunta_convenio_marco, false) }}</span></td>
    </tr>
</table>

<p class="muted" style="margin-top: 9px;">
    Registro generado desde NEXO. Código: {{ $registro->codigo_registro ?: '#' . $registro->id }}.
    Fecha de aprobación: {{ $registro->fecha_revision ? $registro->fecha_revision->format('d/m/Y H:i') : 'No registrada' }}.
</p>
</body>
</html>
