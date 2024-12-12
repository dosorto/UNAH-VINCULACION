<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Document</title>
    <link rel="stylesheet" href="{{ asset('css/app/fichaVinculacion.css') }}">
</head>

<body>
    @if ($proyecto->documento_intermedio() && $proyecto->documento_intermedio()->documento_url != null)
        <x-filament::section collapsible collapsed persist-collapsed id="user-details">
            <x-slot name="heading">
                Informe Intermedio, Estado: {{$proyecto->documento_intermedio()->estado->tipoestado->nombre}}
            </x-slot>
            <x-slot name="description">
                {{$proyecto->documento_intermedio()->estado->comentario}}
            </x-slot>
            <embed src="{{ asset('storage/' . $proyecto->documento_intermedio()->documento_url) 
                
                }}" type="application/pdf" width="100%"
                height="600px" />
        </x-filament::section>
    @endif
    @if ($proyecto->documento_final() && $proyecto->documento_final()->documento_url != null)
        <x-filament::section collapsible collapsed persist-collapsed id="user-details">
            <x-slot name="heading">
                Informe Final, Estado: {{$proyecto->documento_final()->estado->tipoestado->nombre}}
            </x-slot>
            <x-slot name="description">
                {{$proyecto->documento_final()->estado->comentario}}
            </x-slot>
            <h1>Visualizador de PDF</h1>
            <embed src="{{ asset('storage/' . $proyecto->documento_final()->documento_url) }}" type="application/pdf"
                width="100%" height="600px" />
        </x-filament::section>
    @endif
 
    <x-filament::section collapsible collapsed persist-collapsed id="user-details">
        <x-slot name="heading">
            Ficha del proyecto
        </x-slot>

        <div>
            <div class="container">
                <div class="header">
                    <div class="logo-space">
                        <img src="{{ asset('images/Image/Imagen1.jpg') }}" width="500px" height="120px"
                            alt="Escudo de la UNAH">
                        <div class="contact-info">
                            <a href="vinculacion.sociedad@unah.edu.hn">vinculacion.sociedad@unah.edu.hn</a><br>
                            Tel. 2216-7070 Ext. 110576
                        </div>
                    </div>
                    <h1>FORMULARIO DE REGISTRO DE PROYECTO DE VINCULACIÓN</h1>
                </div>

                <div class="section1">
                    <div class="section-title">I. INFORMACIÓN GENERAL DEL PROYECTO </div>
                    <table class="table_datos1">
                        <tr>
                            <th class="full-width1">Nombre del Proyecto:</th>
                            <td class="full-width" colspan="5">

                                <input type="text" class="input-field" placeholder="Ingrese el nombre del proyecto"
                                    value="{{ $proyecto->nombre_proyecto }}" disabled>

                            </td>
                        </tr>
                        <tr>
                            <th class="full-width1" rowspan="2">Unidad Académica:</th>
                            <td class="sub-header" colspan="1">Facultad/Campus Universitario</td>
                            <td class="full-width" colspan="4">
                                <ul>
                                    @foreach ($proyecto->facultades_centros as $centro)
                                        <li> {{ $centro->nombre }} </li>
                                    @endforeach
                                </ul>
                            </td>
                        </tr>
                        <tr>
                            <td class="sub-header" colspan="1">Departamento</td>
                            <td class="full-width" colspan="4">
                                <ul>
                                    @foreach ($proyecto->departamentos_academicos as $departamento)
                                        <li>{{ $departamento->nombre }}</li>
                                    @endforeach
                                </ul>
                            </td>
                        </tr>
                        <tr>
                            <th class="full-width1" rowspan="1">Modalidad</th>
                            <td class="sub-header1" colspan="1">Unidisciplinar <br>
                                <input type="checkbox" class="No" @if ($proyecto->modalidad?->nombre == 'Unidisciplinar') checked @endif>
                            </td>
                            <td class="sub-header1" colspan="1">Multidisciplinar<br>
                                <input type="checkbox" class="No" @if ($proyecto->modalidad?->nombre == 'Multidisciplinar') checked @endif>
                            </td>
                            <td class="sub-header1" colspan="1">Interdisciplinar <br>
                                <input type="checkbox" class="No" @if ($proyecto->modalidad?->nombre == 'Interdisciplinar') checked @endif>
                            </td>
                            <td class="sub-header1" colspan="1">Transdisciplinar<br>
                                <input type="checkbox" class="No" @if ($proyecto->modalidad?->nombre == 'Transdisciplinar') checked @endif>
                            </td>
                        </tr>
                        <tr>
                            <th class="full-width1" rowspan="3">Coordinador/a del Proyecto:</th>
                            <td class="sub-header">Nombre Completo:</td>
                            <td class="full-width" colspan="1">
                                <input type="text" class="input-field" placeholder="Ingrese el nombre completo"
                                    value="{{ $proyecto->coordinador->nombre_completo }}" disabled>
                            </td>
                            <td class="sub-header">No. de empleado:</td>
                            <td class="full-width" colspan="">
                                <input type="text" class="input-field" placeholder="Ingrese el número de empleado"
                                    value="{{ $proyecto->coordinador->numero_empleado }}" disabled>
                            </td>
                        </tr>
                        <tr>
                            <td class="sub-header">Correo electrónico:</td>
                            <td class="full-width" colspan="1">
                                <input type="text" class="input-field" placeholder="Ingrese el correo electrónico"
                                    value="{{ $proyecto->coordinador->user->email }}" disabled>
                            </td>
                            <td class="sub-header">Celular:</td>
                            <td class="full-width" colspan="1">
                                <input type="email" class="input-field" placeholder="Ingrese el número de celular"
                                    value="{{ $proyecto->coordinador->celular }}" disabled>
                            </td>
                        </tr>
                        <tr>
                            <td class="sub-header">Categoria:</td>
                            <td class="full-width" colspan="1">
                                <input type="email" class="input-field" placeholder="Ingrese el número de celular"
                                    value="{{ $proyecto->coordinador->categoria->nombre }}" disabled>
                            </td>
                            <td class="sub-header">Departamento:</td>
                            <td class="full-width" colspan="1">
                                <input type="email" class="input-field" placeholder="Ingrese el número de celular"
                                    value="{{ $proyecto->coordinador->departamento_academico->nombre }}" disabled>
                            </td>
                        </tr>
                        <tr>
                            <th class="header" colspan="6">Integrantes del equipo universitario (Agregar o quitar
                                líneas de ser necesario)</th>
                        </tr>
                        <tr>
                            <td class="sub-header">Nombre Completo:</td>
                            <td class="sub-header">No. de empleado/a:</td>
                            <td class="sub-header">Correo electrónico:</td>
                            <td class="sub-header">Categoria:</td>
                            <td class="sub-header">Departamento:</td>
                        </tr>

                        @foreach ($proyecto->integrantes as $integrante)
                            <tr>
                                <td class="full-width" colspan="1">
                                    <input type="text" class="input-field"
                                        placeholder="Ingrese el nombre completo"
                                        value="{{ $integrante->nombre_completo }}" disabled>
                                </td>
                                <td class="full-width" colspan="1">
                                    <input type="text" class="input-field"
                                        placeholder="Ingrese el número de empleado"
                                        value="{{ $integrante->numero_empleado }}" disabled>
                                </td>
                                <td class="full-width" colspan="1">
                                    <input type="text" class="input-field"
                                        placeholder="Ingrese el correo electrónico"
                                        value="{{ $integrante->user->email }}" disabled>
                                </td>
                                <td class="full-width" colspan="1">
                                    <input type="text" class="input-field" placeholder="Ingrese la categoría"
                                        value="{{ $integrante->categoria->nombre }}" disabled>
                                </td>
                                <td class="full-width" colspan="1">
                                    <input type="text" class="input-field" placeholder="Ingrese el departamento"
                                        value="{{ $integrante->departamento_academico->nombre }}" disabled>
                                </td>
                            </tr>
                        @endforeach

                        <tr>
                            <td class="sub-header" rowspan="2">Participación de estudiantes:</td>
                            <td class="full-width" colspan="1"> Cantidad <br>
                                <input type="number" class="input-field1"
                                    value="{{ $proyecto->estudiante_proyecto->count() }}" disabled>
                            </td>
                            <td class="sub-header" rowspan="2">Tipo de participación:</td>
                            <td class="full-width" colspan="2">
                                Voluntariado <input type="checkbox" class="No"
                                    @if ($proyecto->tipo_participacion_estudiante == 'Voluntario') checked @endif> <br>
                                Práctica de asignatura <input type="checkbox" class="No"
                                    @if ($proyecto->tipo_participacion_estudiante == 'Practica') checked @endif><br>
                                Práctica profesional o servicio social <input type="checkbox" class="No"
                                    @if ($proyecto->tipo_participacion_estudiante == 'Profesional') checked @endif><br>
                            </td>
                        </tr>
                    </table>
                </div>
                <div class="section2">
                    <div class="section-title">II. INFORMACIÓN DE LA ENTIDAD CONTRAPARTE DEL PROYECTO (en caso de
                        contar
                        con una contraparte). </div>
                    <table class="table_datos1">
                        <tr>
                            <th class="header" colspan="5">En caso de que la contraparte sea nacional (añadir una
                                tabla
                                de información por cada una de las contrapartes)</th>
                        </tr>
                        @foreach ($proyecto->entidad_contraparte as $entidad)
                            <tr>
                                <td class="sub-header">Nombre de la entidad:</td>
                                <td class="full-width" colspan="4">
                                    <input type="text" class="input-field"
                                        placeholder="Ingrese el nombre de la entidad" value="{{ $entidad->nombre }}"
                                        disabled>
                                </td>
                            </tr>
                            <tr>
                                <td class="sub-header" rowspan="2">Nombre del contacto directo</td>
                                <td class="full-width" colspan="1" rowspan="2">
                                    <input type="text" class="input-field"
                                        placeholder="Ingrese el nombre de la entidad"
                                        value="{{ $entidad->nombre_contacto }}" disabled>
                                </td>
                                <td class="sub-header" rowspan="1">Correo electrónico:</td>
                                <td class="full-width" colspan="1">
                                    <input type="text" class="input-field"
                                        placeholder="Ingrese el nombre de la entidad" value="{{ $entidad->correo }}"
                                        disabled>
                                </td>
                            <tr>
                                <td class="sub-header" rowspan="1">Teléfono:</td>
                                <td class="full-width" colspan="1">
                                    <input type="text" class="input-field"
                                        placeholder="Ingrese el nombre de la entidad"
                                        value="{{ $entidad->telefono }}" disabled>
                                </td>
                            </tr>
                            </tr>
                            <tr>
                                <td class="sub-header">Rol o aporte en el proyecto:</td>
                                <td class="full-width" colspan="1">
                                    <input type="text" class="input-field"
                                        placeholder="Ingrese el nombre de la entidad" value="{{ $entidad->aporte }}"
                                        disabled>
                                </td>
                                <td class="sub-header">Instrumento de formalización de alianza (Si hubiese):</td>
                                <td class="full-width" colspan="1">
                                    <input type="text" class="input-field"
                                        placeholder="Ingrese el nombre de la entidad">
                                </td>
                            </tr>
                        @endforeach
                    </table>

                </div>
                <div class="section2">
                    <div class="section-title">III. DATOS DEL PROYECTO. </div>
                    <table class="table_datos3">
                        <tr>
                            <th class="header" colspan="19">1. Resumen del proyecto: (Enunciar un breve resumen
                                descriptivo con los antecedentes del problema de intervención,
                                justificación y que se pretende cambiar con relación a dicho problema de intervención a
                                través del proyecto) De 150 a 250 palabras</th>
                        </tr>
                        <tr>
                            <td class="full-width" colspan="19">
                                <textarea id="resumen" name="resumen" cols="30" rows="6" class="input-field"
                                    placeholder="Ingrese el resumen">{{ old('resumen', $proyecto->resumen) }}</textarea>
                            </td>
                        </tr>

                        </tr>
                        <tr>
                            <th class="header" colspan="19">2. Objetivo General (El objetivo debe de responder al
                                para
                                qué se va a desarrollar el proyecto teniendo como objetivo la población meta)</th>
                        </tr>
                        <tr>
                            <td class="full-width" colspan="19">
                                <textarea cols="30" rows="6" class="input-field" placeholder="Ingrese el resumen"> {{ old('objetivo_general', $proyecto->objetivo_general) }}
                                </textarea>
                            </td>
                        </tr>
                        <tr>
                            <td class="header" colspan="19">3. Objetivos Específicos (Los objetivos específicos
                                deben
                                estar
                                relacionados con los resultados que esperan obtener en el proyecto)</th>
                        </tr>
                        <tr>
                            <td class="full-width" colspan="19">
                                <textarea cols="30" rows="6" class="input-field" placeholder="Ingrese el resumen"> {{ old('objetivos_especificos', $proyecto->objetivos_especificos) }}
                                </textarea>
                            </td>
                        </tr>
                        <tr>
                            <th class="header" colspan="9" rowspan="2"> 5. Fecha de ejecución
                            </th>
                            <td class="sub-header" colspan="2" rowspan="2"> Fecha de inicio </td>
                            <td class="full-width" colspan="1"><input type="text" class="input-field"
                                    placeholder="Ingrese el día"
                                    value="{{ \Carbon\Carbon::parse($proyecto->fecha_inicio)->format('d') }}"
                                    disabled>
                            </td>
                            <td class="full-width" colspan="1"><input type="text" class="input-field"
                                    placeholder="Ingrese el mes"
                                    value="{{ \Carbon\Carbon::parse($proyecto->fecha_inicio)->format('m') }}"
                                    disabled>
                            </td>
                            <td class="full-width" colspan="1"><input type="text" class="input-field"
                                    placeholder="Ingrese el día"
                                    value="{{ \Carbon\Carbon::parse($proyecto->fecha_inicio)->format('Y') }}"
                                    disabled>
                            </td>
                            <td class="sub-header" colspan="2" rowspan="2"> Fecha de Finalización</td>
                            <td class="full-width" colspan="1"><input type="text" class="input-field"
                                    placeholder="Ingrese el día"
                                    value="{{ \Carbon\Carbon::parse($proyecto->fecha_finalizacion)->format('d') }}"
                                    disabled>
                            </td>
                            <td class="full-width" colspan="1"><input type="text" class="input-field"
                                    placeholder="Ingrese el mes"
                                    value="{{ \Carbon\Carbon::parse($proyecto->fecha_finalizacion)->format('m') }}"
                                    disabled>
                            </td>
                            <td class="full-width" colspan="1"><input type="text" class="input-field"
                                    placeholder="Ingrese el día"
                                    value="{{ \Carbon\Carbon::parse($proyecto->fecha_finalizacion)->format('Y') }}"
                                    disabled>
                            </td>
                        </tr>
                        <tr>
                            <td class="full-width" colspan="1">Día</td>
                            <td class="full-width" colspan="1">Mes</td>
                            <td class="full-width" colspan="1">Año</td>
                            <td class="full-width" colspan="1">Día</td>
                            <td class="full-width" colspan="1">Mes</td>
                            <td class="full-width" colspan="1">Año</td>
                        </tr>
                        <tr>
                            <th class="header" colspan="9" rowspan="2"> 6. Fecha de evaluación
                            </th>
                            <td class="sub-header" colspan="2" rowspan="2"> Evaluación intermedia </td>
                            <td class="full-width" colspan="1"><input type="text" class="input-field"
                                    placeholder="Ingrese el día"
                                    value="{{ \Carbon\Carbon::parse($proyecto->evaluacion_intermedia)->format('d') }}"
                                    disabled>
                            </td>
                            <td class="full-width" colspan="1"><input type="text" class="input-field"
                                    placeholder="Ingrese el mes"
                                    value="{{ \Carbon\Carbon::parse($proyecto->evaluacion_intermedia)->format('m') }}"
                                    disabled>
                            </td>
                            <td class="full-width" colspan="1"><input type="text" class="input-field"
                                    placeholder="Ingrese el día"
                                    value="{{ \Carbon\Carbon::parse($proyecto->evaluacion_intermedia)->format('Y') }}"
                                    disabled>
                            </td>
                            <td class="sub-header" colspan="2" rowspan="2"> Evaluación Final</td>
                            <td class="full-width" colspan="1"><input type="text" class="input-field"
                                    placeholder="Ingrese el día"
                                    value="{{ \Carbon\Carbon::parse($proyecto->evaluacion_final)->format('d') }}"
                                    disabled>
                            </td>
                            <td class="full-width" colspan="1"><input type="text" class="input-field"
                                    placeholder="Ingrese el mes"
                                    value="{{ \Carbon\Carbon::parse($proyecto->evaluacion_final)->format('m') }}"
                                    disabled>
                            </td>
                            <td class="full-width" colspan="1"><input type="text" class="input-field"
                                    placeholder="Ingrese el día"
                                    value="{{ \Carbon\Carbon::parse($proyecto->evaluacion_final)->format('Y') }}"
                                    disabled>
                            </td>
                        </tr>
                        <tr>
                            <td class="full-width" colspan="1">Día</td>
                            <td class="full-width" colspan="1">Mes</td>
                            <td class="full-width" colspan="1">Año</td>
                            <td class="full-width" colspan="1">Día</td>
                            <td class="full-width" colspan="1">Mes</td>
                            <td class="full-width" colspan="1">Año</td>
                        </tr>
                        <tr>
                            <td class="sub-header" colspan="13">7. Población beneficiada (número aproximado) </td>
                            <td class="full-width" colspan="6">
                                <input type="text" class="input-field" placeholder="Ingrese el día"
                                    value="{{ floor($proyecto->poblacion_participante) }}" disabled>
                            </td>
                        </tr>
                        <tr>
                            <td class="sub-header" colspan="12">8. Modalidad de ejecución (marcar con una X)</td>
                            <td class="full-width" colspan="3">
                                Distancia <br>
                                <input type="checkbox" class="no"
                                    @if ($proyecto->modalidad_ejecucion == 'Distancia') checked @endif>
                            </td>
                            <td class="full-width" colspan="2">
                                Presencial <br>
                                <input type="checkbox" class="no"
                                    @if ($proyecto->modalidad_ejecucion == 'Presencial') checked @endif>
                            </td>
                            <td class="full-width" colspan="3">
                                Bimodal <br>
                                <input type="checkbox" class="no"
                                    @if ($proyecto->modalidad_ejecucion == 'Bimodal') checked @endif>
                            </td>

                        </tr>
                        <tr>
                            <th class="header" colspan="19">9. En caso de proyectos presenciales, indicar el lugar
                                donde
                                se ejecuta: </th>
                        </tr>
                        @if (!empty($proyecto->departamento->nombre)) ||
                            !empty($proyecto->ciudad->nombre)
                            <tr>
                                @if (!empty($proyecto->departamento->nombre))
                                    <td class="sub-header" colspan="2">Departamento</td>
                                    <td class="full-width" colspan="11" rowspan="1">
                                        <input type="text" class="input-field"
                                            placeholder="Ingrese el nombre de la entidad"
                                            value="{{ $proyecto->departamento->nombre }}" disabled>
                                    </td>
                                @endif
                                @if (!empty($proyecto->ciudad->nombre))
                                    <td class="sub-header" colspan="1">Ciudad:</td>
                                    <td class="full-width" colspan="5">
                                        <input type="text" class="input-field"
                                            placeholder="Ingrese el nombre de la entidad"
                                            value="{{ $proyecto->ciudad->nombre }}" disabled>
                                    </td>
                                @endif
                            </tr>
                        @endif

                        @if (!empty($proyecto->municipio->nombre) || !empty($proyecto->aldea))
                            <tr>
                                @if (!empty($proyecto->municipio->nombre))
                                    <td class="sub-header" colspan="2">Municipio:</td>
                                    <td class="full-width" colspan="11">
                                        <input type="text" class="input-field"
                                            placeholder="Ingrese el nombre de la entidad"
                                            value="{{ $proyecto->municipio->nombre }}" disabled>
                                    </td>
                                @endif

                                @if (!empty($proyecto->aldea))
                                    <td class="sub-header" colspan="1">Aldea/caserío/<br>barrio/colonia:</td>
                                    <td class="full-width" colspan="5">
                                        <input type="text" class="input-field"
                                            placeholder="Ingrese el nombre de la entidad"
                                            value="{{ $proyecto->aldea }}" disabled>
                                    </td>
                                @endif
                            </tr>
                        @endif
                        </tr>
                        <tr>
                            <th class="header" colspan="19">10. Descripción de actividades del proyecto (Descripción
                                de
                                todas las actividades enmarcadas en el proyecto, las cuales pueden ser, entre otras, la
                                negociación inicial,la organización de los equipos de trabajo, la planificación, el
                                desarrollo de actividades de
                                capacitación y fortalecimiento, el seguimiento, la evaluación, la sistematización y la
                                divulgación). </th>
                        </tr>
                        <tr>
                            <td class="sub-header3" colspan="19"> Cronogramama de actividades</td>
                        </tr>
                        <tr>
                            <td class="sub-header3" colspan="11"> Actividades</td>
                            <td class="sub-header3" colspan="3"> Fecha de ejecución (tentativa)</td>
                            <td class="sub-header3" colspan="5"> Responsable</td>
                        </tr>
                        <tr>
                            <td class="full-width" colspan="11"><input type="text" class="input-field"
                                    placeholder="Ingrese el día">
                            </td>
                            <td class="full-width" colspan="3"><input type="text" class="input-field"
                                    placeholder="Ingrese el mes">
                            </td>
                            <td class="full-width" colspan="5"><input type="text" class="input-field"
                                    placeholder="Ingrese el día">
                            </td>
                        </tr>
                        <tr>
                            <td class="full-width" colspan="11"><input type="text" class="input-field"
                                    placeholder="Ingrese el día"></td>
                            <td class="full-width" colspan="3"><input type="text" class="input-field"
                                    placeholder="Ingrese el mes"></td>
                            <td class="full-width" colspan="5"><input type="text" class="input-field"
                                    placeholder="Ingrese el día"></td>
                        </tr>
                        <tr>
                            <td class="full-width" colspan="11"><input type="text" class="input-field"
                                    placeholder="Ingrese el día"></td>
                            <td class="full-width" colspan="3"><input type="text" class="input-field"
                                    placeholder="Ingrese el mes"></td>
                            <td class="full-width" colspan="5"><input type="text" class="input-field"
                                    placeholder="Ingrese el día"></td>
                        </tr>
                        <tr>
                            <th class="header" colspan="19">11. Resultados esperados </th>
                        </tr>
                        <tr>
                            <td class="full-width" colspan="19">
                                <textarea name="motivos" id="motivos" cols="30" rows="6" class="input-field"
                                    placeholder="Ingrese el resumen">{{ old('resumen', $proyecto->resultados_esperados) }}</textarea>
                            </td>
                        </tr>
                        <tr>
                            <th class="header" colspan="19"> 12. Indicadores de medicion de resultados</th>
                        </tr>
                        <tr>
                            <td class="full-width" colspan="19">
                                <textarea name="motivos" id="motivos" cols="30" rows="6" class="input-field"
                                    placeholder="Ingrese el resumen">{{ old('resumen', $proyecto->indicadores_medicion_resultados) }}</textarea>
                            </td>
                        </tr>
                        <tr>
                            <th class="header" colspan="19"> 13. Presupuesto del Proyecto</th>
                        </tr>
                        <tr>
                            <td class="sub-header" colspan="13">Aportes de estudiantes:</td>
                            <td class="full-width" colspan="6">
                                <input type="text" class="input-field"
                                    placeholder="Ingrese el nombre de la entidad">
                            </td>
                        <tr>
                            <td class="sub-header" colspan="13">Aportes de profesores:</td>
                            <td class="full-width" colspan="6">
                                <input type="text" class="input-field"
                                    placeholder="Ingrese el nombre de la entidad">
                            </td>
                        </tr>
                        <tr>
                            <td class="sub-header" colspan="13">Aporte académico de la UNAH:</td>
                            <td class="full-width" colspan="6">
                                <input type="text" class="input-field"
                                    placeholder="Ingrese el nombre de la entidad">
                            </td>
                        <tr>
                            <td class="sub-header" colspan="13">Aporte transporte UNAH:</td>
                            <td class="full-width" colspan="6">
                                <input type="text" class="input-field"
                                    placeholder="Ingrese el nombre de la entidad">
                            </td>
                        </tr>
                        <tr>
                            <td class="sub-header" colspan="13">Otros Aportes:</td>
                            <td class="full-width" colspan="6">
                                <input type="text" class="input-field"
                                    placeholder="Ingrese el nombre de la entidad">
                            </td>
                        <tr>
                            <td class="sub-header4" colspan="13">TOTAL UNAH:</td>
                            <td class="full-width" colspan="6">
                                <input type="text" class="input-field"
                                    placeholder="Ingrese el nombre de la entidad">
                            </td>
                        </tr>
                        <tr>
                            <td class="sub-header" colspan="13">Aporte de la contraparte:</td>
                            <td class="full-width" colspan="6">
                                <input type="text" class="input-field"
                                    placeholder="Ingrese el nombre de la entidad">
                            </td>
                        </tr>
                        <tr>
                            <td class="sub-header" colspan="13">Aporte de la comunidad:</td>
                            <td class="full-width" colspan="6">
                                <input type="text" class="input-field"
                                    placeholder="Ingrese el nombre de la entidad">
                            </td>
                        <tr>
                        <tr>
                            <td class="sub-header4" colspan="13">TOTAL CONTRAPARTE:</td>
                            <td class="full-width" colspan="6">
                                <input type="text" class="input-field"
                                    placeholder="Ingrese el nombre de la entidad">
                            </td>
                        </tr>
                        <tr>
                            <td class="sub-header4" colspan="13">MONTO TOTAL DEL PROYECTO:</td>
                            <td class="full-width" colspan="6">
                                <input type="text" class="input-field"
                                    placeholder="Ingrese el nombre de la entidad">
                            </td>
                        </tr>
                        <tr>
                            <th class="header" colspan="19"> En caso de superávit o rentabilidad en el proyecto,
                                haga un
                                desglose detallado
                                en que se va a invertir el superávit según las normas de ejecución presupuestaria de la
                                UNAH
                            </th>
                        </tr>
                        <tr>
                            <td class="sub-header3" colspan="14">Inversión:</td>
                            <td class="sub-header3" colspan="5">Monto:</td>
                        </tr>
                        <tr>
                            <td class="full-width" colspan="14"><input type="text" class="input-field"
                                    placeholder="Ingrese el nombre de la entidad">
                            </td>
                            <td class="full-width " colspan="5"><input type="text" class="input-field"
                                    placeholder="Ingrese el nombre de la entidad">
                            </td>
                        </tr>
                        <tr>
                            <td class="full-width" colspan="14"><input type="text" class="input-field"
                                    placeholder="Ingrese el nombre de la entidad"></td>
                            <td class="full-width " colspan="5"><input type="text" class="input-field"
                                    placeholder="Ingrese el nombre de la entidad"></td>
                        </tr>
                        <tr>
                            <td class="full-width" colspan="14"><input type="text" class="input-field"
                                    placeholder="Ingrese el nombre de la entidad"></td>
                            <td class="full-width " colspan="5"><input type="text" class="input-field"
                                    placeholder="Ingrese el nombre de la entidad"></td>
                        </tr>
                    </table>
                </div>
                <div class="section3">
                    <div class="section-title">IV. FIRMAS. </div>
                    <table class="table_datos4">
                        <tr>
                            <td class="sub-header" colspan="2">Coordinador del proyecto por la UNAH</td>
                            <td class="sub-header" colspan="2">Jeje del Departamento</td>
                        </tr>
                        <tr>
                            <td class="full-width" colspan="1"> Nombre:</td>
                            <td class="full-width" colspan="1">
                                <input type="text" class="input-field"
                                    placeholder="Ingrese el nombre del coordinador"
                                    value="{{ optional(optional($proyecto->firma_coodinador_proyecto()->first())->empleado)->nombre_completo }}"
                                    disabled>
                            </td>
                            <td class="full-width " colspan="1">Nombre:</td>
                            <td class="full-width " colspan="1">
                                <input type="text" class="input-field"
                                    value="{{ optional(optional($proyecto->firma_proyecto_jefe()->first())->empleado)->nombre_completo }}"
                                    disabled>
                            </td>
                        </tr>
                        <tr>
                            <td class="full-width" colspan="2" style="height: 200px; width: 200px;">
                                <img src="{{ optional(optional($proyecto->firma_coodinador_proyecto()->first())->sello)->ruta_storage }}"
                                    alt="" width="200px">
                                <img src="{{ optional(optional($proyecto->firma_coodinador_proyecto()->first())->firma)->ruta_storage }}"
                                    alt="" width="200px">
                            </td>
                            <td class="full-width" colspan="2" style="height: 200px; width: 200px;">
                                <img src="{{ optional(optional($proyecto->firma_proyecto_jefe()->first())->sello)->ruta_storage }}"
                                    alt="" width="200px">
                                <img src="{{ optional(optional($proyecto->firma_proyecto_jefe()->first())->firma)->ruta_storage }}"
                                    alt="" width="200px">
                            </td>
                        </tr>

                        <tr>
                            <th class="header" colspan="2">Firma del profesor/a responsable del proyecto</th>
                            <th class="header" colspan="2">Firma y sello del Jefe/a del Departamento</th>
                        </tr>
                    </table>
                    <table class="table_datos4">
                        <tr>
                            <td class="sub-header" colspan="2">Coordinador(a) del Comité de Vinculación <br> de la
                                facultad o Campus Universitario</td>
                            <td class="sub-header" colspan="2">Decano(a) o Director(a) del Campus Universitario
                            </td>
                        </tr>
                        <tr>
                            <td class="full-width" colspan="1"> Nombre:</td>
                            <td class="full-width" colspan="1">
                                <input type="text" class="input-field"
                                    value="{{ optional(optional($proyecto->firma_proyecto_enlace()->first())->empleado)->nombre_completo }}"
                                    disabled>
                            </td>
                            <td class="full-width" colspan="1">Nombre:</td>
                            <td class="full-width" colspan="1">
                                <input type="text" class="input-field"
                                    placeholder="Ingrese el nombre del responsable"
                                    value="{{ optional(optional($proyecto->firma_proyecto_decano()->first())->empleado)->nombre_completo }}"
                                    disabled>
                            </td>
                        </tr>
                        <tr>
                            <td class="full-width" colspan="2" style="height: 200px; width: 200px;">
                                <img src="{{ optional(optional($proyecto->firma_proyecto_enlace()->first())->sello)->ruta_storage }}"
                                    alt="" width="200px">
                                <img src="{{ optional(optional($proyecto->firma_proyecto_enlace()->first())->firma)->ruta_storage }}"
                                    alt="" width="200px">
                            </td>
                            <td class="full-width" colspan="2" style="height: 200px; width: 200px;">
                                <img src="{{ optional(optional($proyecto->firma_proyecto_decano()->first())->sello)->ruta_storage }}"
                                    alt="" width="200px">
                                <img src="{{ optional(optional($proyecto->firma_proyecto_decano()->first())->firma)->ruta_storage }}"
                                    alt="" width="200px">
                            </td>
                        </tr>

                        <tr>
                            <th class="header" colspan="2">Firma del Coordinador(a) del Comité de Vinculación <br>
                                de
                                la Facultad o Campus Universitario</th>
                            <th class="header" colspan="2">Firma y sello del Decano(a) o Director(a)</th>
                        </tr>
                    </table>
                </div>
                <div class="section4">
                    <div class="section-title">V. ANEXOS. </div>
                    <table class="table_datos5">
                        <tr>
                            <td class="sub-header" colspan="1">Categorías de proyectos de vinculación</td>
                            <td class="full-width" colspan="1">Educación No Formal y/o Continua <br>
                                <input type="checkbox" class="no">
                            </td>
                            <td class="full-width" colspan="1">APS <br>
                                <input type="checkbox" class="no">
                            </td>
                            <td class="full-width" colspan="1">Desarrollo Regional <br>
                                <input type="checkbox" class="no">
                            </td>
                            <td class="full-width" colspan="1">Desarrollo local <br>
                                <input type="checkbox" class="no">
                            </td>
                            <td class="full-width" colspan="1">Investigación-acción-participación <br>
                                <input type="checkbox" class="no">
                            </td>
                            <td class="full-width" colspan="1">Asesoría técnico-científica <br>
                                <input type="checkbox" class="no">
                            </td>
                            <td class="full-width" colspan="1">Artísticos-culturales <br>
                                <input type="checkbox" class="no">
                            </td>
                            <td class="full-width" colspan="1">Otras áreas <br>
                                <input type="checkbox" class="no">
                            </td>
                        </tr>
                    </table>
                    <table class="table_datos5">
                        <tr>
                            <th class="header" colspan="19">1. ODS en el que se enmarca el proyecto: Utilizar el
                                documento Agenda 20/45 y objetivos de desarrollo sostenible. </th>
                        </tr>
                        <tr>
                            <td class="full-width" colspan="19">
                                <textarea name="motivos" id="motivos" cols="30" rows="6" class="input-field"
                                    placeholder="Ingrese el resumen"></textarea>
                            </td>
                        </tr>
                    </table>
                    <table class="table_datos5">
                        <tr>
                            <td class="sub-header" colspan="1">Responsable de revisión</td>
                            <td class="full-width" colspan="10">
                                <input type="text" class="input-field" value="{{ $proyecto->responsable_revision->nombre_completo }}" placeholder="Ingrese el día">
                            </td>
                        </tr>
                    </table>
                    <table class="table_datos5">
                        <td class="sub-header" colspan="1">Fecha de Aprobación</td>
                        <td class="full-width" colspan="6">
                            <input type="text" class="input-field" value="{{ $proyecto->fecha_aprobacion }}" placeholder="Ingrese el día">
                        </td>
                        <td class="sub-header" colspan="1">Fecha de Registro</td>
                        <td class="full-width" colspan="6">
                            <input type="text" class="input-field" value="{{ $proyecto->fecha_registro }}" placeholder="Ingrese el día">
                        </td>
                        </tr>
                    </table>
                    <table class="table_datos7">
                        <td class="sub-header" colspan="1">No. de Libro </td>
                        <td class="full-width" colspan="1">
                            <input type="text" class="input-field" value="{{ $proyecto->numero_libro }}" placeholder="Ingrese el día">
                        </td>
                        <td class="sub-header" colspan="1">No. de Tomo </td>
                        <td class="full-width" colspan="1">
                            <input type="text" class="input-field" value="{{ $proyecto->numero_tomo }}" placeholder="Ingrese el día">
                        </td>
                        <td class="sub-header" colspan="1">No. de Folio </td>
                        <td class="full-width" colspan="1">
                            <input type="text" class="input-field" value="{{ $proyecto->numero_folio }}" placeholder="Ingrese el día">
                        </td>
                        </tr>
                    </table>

                    <div class="header-box"> <input type="text" class="input-field" value="{{ $proyecto->numero_dictamen }}"
                            placeholder="Ingrese No. dictamen de Proyecto">
                        <p class="header-text">No. dictamen de Proyecto</p>
                    </div>
                </div>
            </div>
        </div>
    </x-filament::section>


</body>

</html>
