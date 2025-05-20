
<div class="flex flex-col md:flex-row lg:flex-row gap-4">
    @push('styles')
        <link rel="stylesheet" href="{{ asset('css/app/fichaHistorial.css') }}">
    @endpush
    @php
use Carbon\Carbon;
$diasTranscurridos = intval(Carbon::parse($proyecto->created_at)->diffInDays(now()));
$projectStartDate = Carbon::parse($proyecto->created_at);
    @endphp
    <div class="w-full md:w-3/5 lg:w-2/3">
        <h1 class="text-2xl font-bold dark:text-white text-gray-900 mb-4">
            Ficha de proyecto: {{ $proyecto->estado->tipoestado->nombre }}
        </h1>
         @if ($proyecto->documento_intermedio() && $proyecto->documento_intermedio()->documento_url != null)
        <x-filament::section collapsible collapsed persist-collapsed id="user-details">
            <x-slot name="heading">
                Informe Intermedio, Estado: {{ $proyecto->documento_intermedio()->estado->tipoestado->nombre }}
            </x-slot>
            <x-slot name="description">
                {{ $proyecto->documento_intermedio()->estado->comentario }}
            </x-slot>
            <embed src="{{ asset('storage/' . $proyecto->documento_intermedio()->documento_url) }}"
                type="application/pdf" width="100%" height="600px" />
        </x-filament::section>
    @endif
    @if ($proyecto->documento_final() && $proyecto->documento_final()->documento_url != null)
        <x-filament::section collapsible collapsed persist-collapsed id="user-details">
            <x-slot name="heading">
                Informe Final, Estado: {{ $proyecto->documento_final()->estado->tipoestado->nombre }}
            </x-slot>
            <x-slot name="description">
                {{ $proyecto->documento_final()->estado->comentario }}
            </x-slot>
            <h1>Visualizador de PDF</h1>
            <embed src="{{ asset('storage/' . $proyecto->documento_final()->documento_url) }}" type="application/pdf"
                width="100%" height="600px" />
        </x-filament::section>
    @endif

    <section collapsible collapsed persist-collapsed id="user-details">
        <div style="display: flex; justify-content: center; margin-top: 20px; background-color: white;">
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

                                <input disabled type="text" class="input-field"
                                    placeholder="Ingrese el nombre del proyecto"
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
                                <input disabled type="checkbox" class="No"
                                    @if ($proyecto->modalidad?->nombre == 'Unidisciplinar') checked @endif>
                            </td>
                            <td class="sub-header1" colspan="1">Multidisciplinar<br>
                                <input disabled type="checkbox" class="No"
                                    @if ($proyecto->modalidad?->nombre == 'Multidisciplinar') checked @endif>
                            </td>
                            <td class="sub-header1" colspan="1">Interdisciplinar <br>
                                <input disabled type="checkbox" class="No"
                                    @if ($proyecto->modalidad?->nombre == 'Interdisciplinar') checked @endif>
                            </td>
                            <td class="sub-header1" colspan="1">Transdisciplinar<br>
                                <input disabled type="checkbox" class="No"
                                    @if ($proyecto->modalidad?->nombre == 'Transdisciplinar') checked @endif>
                            </td>
                        </tr>
                        <tr>
                            <th class="full-width1" rowspan="3">Coordinador/a del Proyecto:</th>
                            <td class="sub-header">Nombre Completo:</td>
                            <td class="full-width" colspan="1">
                                <input disabled type="text" class="input-field"
                                    placeholder="Ingrese el nombre completo"
                                    value="{{ $proyecto->coordinador->nombre_completo }}" disabled>
                            </td>
                            <td class="sub-header">No. de empleado:</td>
                            <td class="full-width" colspan="">
                                <input disabled type="text" class="input-field"
                                    placeholder="Ingrese el número de empleado"
                                    value="{{ $proyecto->coordinador->numero_empleado }}" disabled>
                            </td>
                        </tr>
                        <tr>
                            <td class="sub-header">Correo electrónico:</td>
                            <td class="full-width" colspan="1">
                                <input disabled type="text" class="input-field"
                                    placeholder="Ingrese el correo electrónico"
                                    value="{{ $proyecto->coordinador->user->email }}" disabled>
                            </td>
                            <td class="sub-header">Celular:</td>
                            <td class="full-width" colspan="1">
                                <input disabled type="email" class="input-field"
                                    placeholder="Ingrese el número de celular"
                                    value="{{ $proyecto->coordinador->celular }}" disabled>
                            </td>
                        </tr>
                        <tr>
                            <td class="sub-header">Categoria:</td>
                            <td class="full-width" colspan="1">
                                <input disabled type="email" class="input-field"
                                    placeholder="Ingrese el número de celular"
                                    value="{{ $proyecto->coordinador->categoria->nombre }}" disabled>
                            </td>
                            <td class="sub-header">Departamento:</td>
                            <td class="full-width" colspan="1">
                                <input disabled type="email" class="input-field"
                                    placeholder="Ingrese el número de celular"
                                    value="{{ $proyecto->coordinador->departamento_academico->nombre }}" disabled>
                            </td>
                        </tr>
                        <tr>
                            <th class="header" colspan="6">Integrantes del equipo universitario (Agregar o quitar
                                líneas de ser necesario)</th>
                        </tr>
                        <tr>
                            <th class="header" colspan="6">Docentes</th>
                        </tr>
                        <tr>
                            <td class="sub-header">Nombre Completo:</td>
                            <td class="sub-header">No. de empleado/a:</td>
                            <td class="sub-header">Correo electrónico:</td>
                            <td class="sub-header">Categoria:</td>
                            <td class="sub-header">Departamento:</td>
                        </tr>

                        @forelse ($proyecto->integrantes as $integrante)
                            <tr>
                                <td class="full-width" colspan="1">
                                    <input disabled type="text" class="input-field"
                                        placeholder="Ingrese el nombre completo"
                                        value="{{ $integrante->nombre_completo }}" disabled>
                                </td>
                                <td class="full-width" colspan="1">
                                    <input disabled type="text" class="input-field"
                                        placeholder="Ingrese el número de empleado"
                                        value="{{ $integrante->numero_empleado }}" disabled>
                                </td>
                                <td class="full-width" colspan="1">
                                    <input disabled type="text" class="input-field"
                                        placeholder="Ingrese el correo electrónico"
                                        value="{{ $integrante->user->email }}" disabled>
                                </td>
                                <td class="full-width" colspan="1">
                                    <input disabled type="text" class="input-field"
                                        placeholder="Ingrese la categoría"
                                        value="{{ $integrante->categoria->nombre }}" disabled>
                                </td>
                                <td class="full-width" colspan="1">
                                    <input disabled type="text" class="input-field"
                                        placeholder="Ingrese el departamento"
                                        value="{{ $integrante->departamento_academico->nombre }}" disabled>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td class="full-width
                                " colspan="6">
                                    <input disabled type="text" class="input-field"
                                        placeholder="Ingrese el departamento" value="No hay docentes" disabled>
                                </td>
                            </tr>
                        @endforelse
                        <tr>
                            <th class="header" colspan="6">Estudiantes</th>
                        </tr>
                        <tr>
                            <td class="sub-header" colspan="2">Nombre Completo:</td>
                            <td class="sub-header">No. de cuenta</td>
                            <td class="sub-header">Correo electrónico</td>
                            <td class="sub-header">Tipo participacion:</td>
                        </tr>
                        @forelse ($proyecto->estudiante_proyecto as $integrante)
                            <tr>
                                <td class="full-width" colspan="2">
                                    <input disabled type="text" class="input-field"
                                        placeholder="Ingrese el departamento"
                                        value="{{ $integrante->estudiante->user->name }}" disabled>
                                </td>

                                <td class="full-width
                                " colspan="1">
                                    <input disabled type="text" class="input-field"
                                        placeholder="Ingrese el departamento"
                                        value="{{ $integrante->estudiante->cuenta }}" disabled>
                                </td>
                                <td class="full-width
                                " colspan="1">
                                    <input disabled type="text" class="input-field"
                                        placeholder="Ingrese el departamento"
                                        value="{{ $integrante->estudiante->user->email }}" disabled>
                                </td>
                                <td class="full-width
                                " colspan="1">
                                    <input disabled type="text" class="input-field"
                                        placeholder="Ingrese el departamento"
                                        value="{{ $integrante->tipo_participacion_estudiante }}" disabled>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td class="full-width
                                " colspan="6">
                                    <input disabled type="text" class="input-field"
                                        placeholder="Ingrese el departamento" value="No hay estudiantes" disabled>
                                </td>
                            </tr>
                        @endforelse
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
                        @forelse ($proyecto->entidad_contraparte as $entidad)
                            <tr>
                                <td class="header
                                " colspan="5">
                                    <input disabled type="text" class="input-field"
                                        placeholder="Ingrese el departamento" value="# Entidad Contraparte" disabled>
                                </td>
                            </tr>
                            <tr>
                                <td class="sub-header">Nombre de la entidad:</td>
                                <td class="full-width" colspan="4">
                                    <input disabled type="text" class="input-field"
                                        placeholder="Ingrese el nombre de la entidad" value="{{ $entidad->nombre }}"
                                        disabled>
                                </td>
                            </tr>
                            <tr>
                                <td class="sub-header" rowspan="2">Nombre del contacto directo</td>
                                <td class="full-width" colspan="1" rowspan="2">
                                    <input disabled type="text" class="input-field"
                                        placeholder="Ingrese el nombre de la entidad"
                                        value="{{ $entidad->nombre_contacto }}" disabled>
                                </td>
                                <td class="sub-header" rowspan="1">Correo electrónico:</td>
                                <td class="full-width" colspan="1">
                                    <input disabled type="text" class="input-field"
                                        placeholder="Ingrese el nombre de la entidad" value="{{ $entidad->correo }}"
                                        disabled>
                                </td>
                            <tr>
                                <td class="sub-header" rowspan="1">Teléfono:</td>
                                <td class="full-width" colspan="1">
                                    <input disabled type="text" class="input-field"
                                        placeholder="Ingrese el nombre de la entidad"
                                        value="{{ $entidad->telefono }}" disabled>
                                </td>
                            </tr>
                            </tr>
                            <tr>
                                <td class="sub-header">Rol o aporte en el proyecto:</td>
                                <td class="full-width" colspan="3">
                                    <input disabled type="text" class="input-field"
                                        placeholder="Ingrese el nombre de la entidad" value="{{ $entidad->aporte }}"
                                        disabled>
                                </td>


                            </tr>
                            <tr>
                                <td class="sub-header" colspan="5">Instrumentos de formalización de alianza (Si
                                    hubiese):</td>
                            </tr>
                            <tr>
                                @forelse ($entidad->instrumento_formalizacion as $instrumento)
                            <tr>
                                <td class="full-width
                                    " colspan="3">
                                    <input disabled type="text" class="input-field"
                                        placeholder="Ingrese el departamento" value="Documento de formalización"
                                        disabled>
                                </td>
                                <td class="full-width
                                    " colspan="2">
                                    <x-filament::modal width="7xl" :close-button="true" :close-by-escaping="false">
                                        <x-slot name="heading">
                                            Documento de formalización
                                        </x-slot>
                                        <x-slot name="trigger">
                                            <x-filament::button>
                                                Ver documento
                                            </x-filament::button>
                                        </x-slot>


                                        <iframe src="{{ Storage::url($instrumento->documento_url) }}"
                                            style="width: 100%; height: 85vh; border: none;"></iframe>

                                    </x-filament::modal>
                                    <x-filament::button>
                                        <a href="{{ Storage::url($instrumento->documento_url) }}" download
                                            style="text-decoration: none; color: inherit;">
                                            Descargar
                                        </a>
                                    </x-filament::button>

                                </td>
                            </tr>
                        @empty
                            <td class="full-width
                                    " colspan="5">
                                <input disabled type="text" class="input-field"
                                    placeholder="Ingrese el departamento" value="No hay instrumentos de formalización"
                                    disabled>
                            </td>
                        @endforelse
                        </tr>
                    @empty
                        <tr>
                            <td class="full-width
                                " colspan="5">
                                <input disabled type="text" class="input-field"
                                    placeholder="Ingrese el departamento" value="No hay entidades contraparte"
                                    disabled>
                            </td>
                        </tr>
                        @endforelse
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
                                <textarea disabled id="resumen" name="resumen" cols="30" rows="6" class="input-field"
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
                                <textarea disabled cols="30" rows="6" class="input-field" placeholder="Ingrese el resumen"> {{ old('objetivo_general', $proyecto->objetivo_general) }}
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
                                <textarea disabled cols="30" rows="6" class="input-field" placeholder="Ingrese el resumen"> {{ old('objetivos_especificos', $proyecto->objetivos_especificos) }}
                                </textarea>
                            </td>
                        </tr>
                        <tr>
                            <th class="header" colspan="9" rowspan="2"> 5. Fecha de ejecución
                            </th>
                            <td class="sub-header" colspan="2" rowspan="2"> Fecha de inicio </td>
                            <td class="full-width" colspan="1"><input disabled type="text" class="input-field"
                                    placeholder="Ingrese el día"
                                    value="{{ \Carbon\Carbon::parse($proyecto->fecha_inicio)->format('d') }}"
                                    disabled>
                            </td>
                            <td class="full-width" colspan="1"><input disabled type="text" class="input-field"
                                    placeholder="Ingrese el mes"
                                    value="{{ \Carbon\Carbon::parse($proyecto->fecha_inicio)->format('m') }}"
                                    disabled>
                            </td>
                            <td class="full-width" colspan="1"><input disabled type="text" class="input-field"
                                    placeholder="Ingrese el día"
                                    value="{{ \Carbon\Carbon::parse($proyecto->fecha_inicio)->format('Y') }}"
                                    disabled>
                            </td>
                            <td class="sub-header" colspan="2" rowspan="2"> Fecha de Finalización</td>
                            <td class="full-width" colspan="1"><input disabled type="text" class="input-field"
                                    placeholder="Ingrese el día"
                                    value="{{ \Carbon\Carbon::parse($proyecto->fecha_finalizacion)->format('d') }}"
                                    disabled>
                            </td>
                            <td class="full-width" colspan="1"><input disabled type="text" class="input-field"
                                    placeholder="Ingrese el mes"
                                    value="{{ \Carbon\Carbon::parse($proyecto->fecha_finalizacion)->format('m') }}"
                                    disabled>
                            </td>
                            <td class="full-width" colspan="1"><input disabled type="text" class="input-field"
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
                            <td class="full-width" colspan="1"><input disabled type="text" class="input-field"
                                    placeholder="Ingrese el día"
                                    value="{{ \Carbon\Carbon::parse($proyecto->evaluacion_intermedia)->format('d') }}"
                                    disabled>
                            </td>
                            <td class="full-width" colspan="1"><input disabled type="text" class="input-field"
                                    placeholder="Ingrese el mes"
                                    value="{{ \Carbon\Carbon::parse($proyecto->evaluacion_intermedia)->format('m') }}"
                                    disabled>
                            </td>
                            <td class="full-width" colspan="1"><input disabled type="text" class="input-field"
                                    placeholder="Ingrese el día"
                                    value="{{ \Carbon\Carbon::parse($proyecto->evaluacion_intermedia)->format('Y') }}"
                                    disabled>
                            </td>
                            <td class="sub-header" colspan="2" rowspan="2"> Evaluación Final</td>
                            <td class="full-width" colspan="1"><input disabled type="text" class="input-field"
                                    placeholder="Ingrese el día"
                                    value="{{ \Carbon\Carbon::parse($proyecto->evaluacion_final)->format('d') }}"
                                    disabled>
                            </td>
                            <td class="full-width" colspan="1"><input disabled type="text" class="input-field"
                                    placeholder="Ingrese el mes"
                                    value="{{ \Carbon\Carbon::parse($proyecto->evaluacion_final)->format('m') }}"
                                    disabled>
                            </td>
                            <td class="full-width" colspan="1"><input disabled type="text" class="input-field"
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
                                <input disabled type="text" class="input-field" placeholder="Ingrese el día"
                                    value="{{ floor($proyecto->poblacion_participante) }}" disabled>
                            </td>
                        </tr>
                        <tr>
                            <td class="sub-header" colspan="12">8. Modalidad de ejecución (marcar con una X)</td>
                            <td class="full-width" colspan="3">
                                Distancia <br>
                                <input disabled type="checkbox" class="no"
                                    @if ($proyecto->modalidad_ejecucion == 'Distancia') checked @endif>
                            </td>
                            <td class="full-width" colspan="2">
                                Presencial <br>
                                <input disabled type="checkbox" class="no"
                                    @if ($proyecto->modalidad_ejecucion == 'Presencial') checked @endif>
                            </td>
                            <td class="full-width" colspan="3">
                                Bimodal <br>
                                <input disabled type="checkbox" class="no"
                                    @if ($proyecto->modalidad_ejecucion == 'Bimodal') checked @endif>
                            </td>

                        </tr>
                        <tr>
                            <th class="header" colspan="19">9. En caso de proyectos presenciales, indicar el lugar
                                donde
                                se ejecuta: </th>
                        </tr>

                        <tr>

                            <td class="sub-header" colspan="4">Departamentos</td>
                            <td class="full-width" colspan="15" rowspan="1">
                                @forelse ($proyecto->departamento as $departamento)
                                    <input disabled type="text" class="input-field"
                                        placeholder="Ingrese el nombre de la entidad"
                                        value="{{ $departamento->nombre }}" disabled>

                                @empty
                                    <input disabled type="text" class="input-field"
                                        placeholder="Ingrese el nombre de la entidad" value="No hay departamentos"
                                        disabled>
                                @endforelse
                            </td>

                        </tr>

                        <tr>
                            <td class="sub-header" colspan="4">Municipios:</td>
                            <td class="full-width" colspan="15">

                                @forelse ($proyecto->municipio as $municipio)
                                    <input disabled type="text" class="input-field"
                                        placeholder="Ingrese el nombre de la entidad"
                                        value="{{ $municipio->nombre }}" disabled>
                                @empty
                                    <input disabled type="text" class="input-field"
                                        placeholder="Ingrese el nombre de la entidad" value="No hay municipios"
                                        disabled>
                                @endforelse
                            </td>


                        </tr>
                        <tr>
                            <td class="sub-header" colspan="4">Aldea/caserío/<br>barrio/colonia:</td>
                            <td class="full-width" colspan="15">
                                <input disabled type="text" class="input-field"
                                    placeholder="Ingrese el nombre de la entidad" value="{{ $proyecto->aldea }}"
                                    disabled>
                            </td>
                        </tr>
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

                            <td class="sub-header3" colspan="4"> Fecha Inicio</td>
                            <td class="sub-header3" colspan="4"> Horas</td>
                            <td class="sub-header3" colspan="4"> Fecha Fin</td>
                            <td class="sub-header3" colspan="5"> responsables</td>
                            <td class="sub-header3" colspan="5"> Accion</td>
                        </tr>
                        <tr>
                            @forelse ($proyecto->actividades as $actividad)
                        <tr>
                            <td class="s3" colspan="4"> {{ $actividad->fecha_inicio }}</td>
                            <td class="s" colspan="4"> {{ $actividad->horas }}</td>
                            <td class="s3" colspan="4"> {{ $actividad->fecha_finalizacion }}</td>
                            <td class="" colspan="5">
                                @forelse ($actividad->empleados as $responsable)
                                    <input disabled type="text" class="input-field"
                                        placeholder="Ingrese el nombre de la entidad"
                                        value="{{ $responsable->nombre_completo }}" disabled>

                                @empty
                                @endforelse

                            </td>
                            <td class="" colspan="5">
                                <x-filament::modal width="7xl" :close-button="true" :close-by-escaping="false">
                                    <x-slot name="heading">
                                        Actividad
                                    </x-slot>
                                    <x-slot name="trigger">
                                        <x-filament::button>
                                            Ver Actividad
                                        </x-filament::button>
                                    </x-slot>

                                    <div class="activity-container">
                                        <div class="activity-header">Detalles de la Actividad</div>
                                        <div class="activity-body">
                                            <div class="row">
                                                <div class="column"><strong>Fecha de Inicio:</strong>
                                                    {{ $actividad->fecha_inicio }}</div>
                                                <div class="column"><strong>Fecha de Finalización:</strong>
                                                    {{ $actividad->fecha_finalizacion }}</div>
                                            </div>
                                            <div class="column"><strong>Horas:</strong> {{ $actividad->horas }}</div>
                                            <div class="highlight"><strong>Responsables:</strong>
                                                @forelse ($actividad->empleados as $responsable)
                                                    <div>
                                                        <div>{{ $responsable->nombre_completo }}</div>
                                                    </div>
                                                @empty
                                                    No asignado
                                                @endforelse
                                            </div>
                                            <div class="large-box"><strong>Descripción:</strong>
                                                {{ $actividad->descripcion }}</div>
                                            <div class="large-box"><strong>Objetivos:</strong>
                                                {{ $actividad->objetivos }}</div>
                                            <div class="large-box"><strong>Resultados:</strong>
                                                {{ $actividad->resultados }}</div>
                                        </div>
                                    </div>

                                </x-filament::modal>

                            </td>
                        </tr>
                    @empty
                        <td class="full-width
                                " colspan="19">
                            <input disabled type="text" class="input-field" placeholder="Ingrese el departamento"
                                value="No hay actividades" disabled>

                        </td>
                        @endforelse
                        </tr>

                        <tr>
                            <th class="header" colspan="19">11. Resultados esperados </th>
                        </tr>
                        <tr>
                            <td class="full-width" colspan="19">
                                <textarea disabled name="motivos" id="motivos" cols="30" rows="6" class="input-field"
                                    placeholder="Ingrese el resumen">{{ old('resumen', $proyecto->resultados_esperados) }}</textarea>
                            </td>
                        </tr>
                        <tr>
                            <th class="header" colspan="19"> 12. Indicadores de medicion de resultados</th>
                        </tr>
                        <tr>
                            <td class="full-width" colspan="19">
                                <textarea disabled name="motivos" id="motivos" cols="30" rows="6" class="input-field"
                                    placeholder="Ingrese el resumen">{{ old('resumen', $proyecto->indicadores_medicion_resultados) }}</textarea>
                            </td>
                        </tr>
                        <tr>
                            <th class="header" colspan="19"> 13. Presupuesto del Proyecto</th>
                        </tr>
                        <tr>
                            <td class="sub-header" colspan="13">Aportes de estudiantes:</td>
                            <td class="full-width" colspan="6">
                                <input disabled type="text" class="input-field"
                                    value="{{ $proyecto->presupuesto->aporte_estudiantes }}"
                                    placeholder="Ingrese el nombre de la entidad">
                            </td>
                        <tr>
                            <td class="sub-header" colspan="13">Aportes de profesores:</td>
                            <td class="full-width" colspan="6">
                                <input disabled type="text" class="input-field"
                                    value="{{ $proyecto->presupuesto->aporte_profesores }}"
                                    placeholder="Ingrese el nombre de la entidad">
                            </td>
                        </tr>
                        <tr>
                            <td class="sub-header" colspan="13">Aporte académico de la UNAH:</td>
                            <td class="full-width" colspan="6">
                                <input disabled type="text" class="input-field"
                                    value="{{ $proyecto->presupuesto->aporte_academico_unah }}"
                                    placeholder="Ingrese el nombre de la entidad">
                            </td>
                        <tr>
                            <td class="sub-header" colspan="13">Aporte transporte UNAH:</td>
                            <td class="full-width" colspan="6">
                                <input disabled type="text" class="input-field"
                                    value="{{ $proyecto->presupuesto->aporte_transporte_unah }}"
                                    placeholder="Ingrese el nombre de la entidad">
                            </td>
                        </tr>
                        <tr>
                            <td class="sub-header" colspan="13">Otros Aportes:</td>
                            <td class="full-width" colspan="6">
                                <input disabled type="text" class="input-field"
                                    value="{{ $proyecto->presupuesto->otros_aportes }}"
                                    placeholder="Ingrese el nombre de la entidad">
                            </td>
                        <tr>
                            <td class="sub-header4" colspan="13">TOTAL UNAH:</td>
                            <td class="full-width" colspan="6">
                                <input disabled type="text" class="input-field"
                                    value="{{ $proyecto->presupuesto->aporte_estudiantes +
    $proyecto->presupuesto->aporte_profesores +
    $proyecto->presupuesto->aporte_academico_unah +
    $proyecto->presupuesto->aporte_transporte_unah +
    $proyecto->presupuesto->otros_aportes }}"
                                    placeholder="Ingrese el nombre de la entidad">
                            </td>
                        </tr>
                        <tr>
                            <td class="sub-header" colspan="13">Aporte de la contraparte:</td>
                            <td class="full-width" colspan="6">
                                <input disabled type="text" class="input-field"
                                    value="{{ $proyecto->presupuesto->aporte_contraparte }}"
                                    placeholder="Ingrese el nombre de la entidad">
                            </td>
                        </tr>
                        <tr>
                            <td class="sub-header" colspan="13">Aporte de la comunidad:</td>
                            <td class="full-width" colspan="6">
                                <input disabled type="text" class="input-field"
                                    value="{{ $proyecto->presupuesto->aporte_comunidad }}"
                                    placeholder="Ingrese el nombre de la entidad">
                            </td>
                        <tr>
                        <tr>
                            <td class="sub-header4" colspan="13">TOTAL CONTRAPARTE:</td>
                            <td class="full-width" colspan="6">
                                <input disabled type="text" class="input-field"
                                    value="{{ $proyecto->presupuesto->aporte_contraparte + $proyecto->presupuesto->aporte_comunidad }}"
                                    placeholder="Ingrese el nombre de la entidad">
                            </td>
                        </tr>
                        <tr>
                            <td class="sub-header4" colspan="13">MONTO TOTAL DEL PROYECTO:</td>
                            <td class="full-width" colspan="6">
                                <input disabled type="text" class="input-field"
                                    value="{{ $proyecto->presupuesto->aporte_estudiantes +
    $proyecto->presupuesto->aporte_profesores +
    $proyecto->presupuesto->aporte_academico_unah +
    $proyecto->presupuesto->aporte_transporte_unah +
    $proyecto->presupuesto->otros_aportes +
    $proyecto->presupuesto->aporte_contraparte +
    $proyecto->presupuesto->aporte_comunidad }}"
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

                        @forelse ($proyecto->superavit as $superavit)
                            <tr>
                                <td class="full-width
                                " colspan="14">
                                    <input disabled type="text" class="input-field"
                                        placeholder="Ingrese el departamento" value="{{ $superavit->inversion }}"
                                        disabled>
                                </td>
                                <td class="full-width
                                " colspan="5">
                                    <input disabled type="text" class="input-field"
                                        placeholder="Ingrese el departamento" value="{{ $superavit->monto }}"
                                        disabled>
                                </td>

                            </tr>

                        @empty
                            <tr>
                                <td class="full-width
                            " colspan="19">
                                    <input disabled type="text" class="input-field"
                                        placeholder="Ingrese el departamento" value="No hay superavit" disabled>
                                </td>
                            </tr>
                        @endforelse


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
                                <input disabled type="text" class="input-field"
                                    placeholder="Ingrese el nombre del coordinador"
                                    value="{{ optional(optional($proyecto->firma_coodinador_proyecto()->first())->empleado)->nombre_completo }}"
                                    disabled>


                            </td>
                            <td class="full-width " colspan="1">Nombre:</td>
                            <td class="full-width " colspan="1">
                                <input disabled type="text" class="input-field"
                                    value="{{ optional(optional($proyecto->firma_proyecto_jefe()->first())->empleado)->nombre_completo }}"
                                    disabled>
                            </td>
                        </tr>
                        <tr>
                            <td class="full-width" colspan="2" style="height: 200px; width: 200px;">
                                <img src="{{ Storage::url(optional(optional($proyecto->firma_coodinador_proyecto()->first())->sello)->ruta_storage) }}"
                                    alt="" width="200px">
                                <img src="{{ Storage::url(optional(optional($proyecto->firma_coodinador_proyecto()->first())->firma)->ruta_storage) }}"
                                    alt="" width="200px">
                            </td>
                            <td class="full-width" colspan="2" style="height: 200px; width: 200px;">
                                <img src="{{ Storage::url(optional(optional($proyecto->firma_proyecto_jefe()->first())->sello)->ruta_storage) }}"
                                    alt="" width="200px">
                                <img src="{{ Storage::url(optional(optional($proyecto->firma_proyecto_jefe()->first())->firma)->ruta_storage) }}"
                                    alt="" width="200px">
                            </td>
                        </tr>

                        <tr>
                            <th class="header" colspan="2">Firma del profesor/a responsable del proyecto
                                <br>
                                <p>
                                    {{ optional($proyecto->firma_coodinador_proyecto->first())->fecha_firma
    ? \Carbon\Carbon::parse(optional($proyecto->firma_coodinador_proyecto->first())->fecha_firma)->translatedFormat(
        'l d F Y h:i:s A',
    )
    : '' }}
                                </p>
                            </th>
                            <th class="header" colspan="2">Firma y sello del Jefe/a del Departamento
                                <br>
                                <p>
                                    {{ optional($proyecto->firma_proyecto_jefe->first())->fecha_firma
    ? \Carbon\Carbon::parse(optional($proyecto->firma_proyecto_jefe->first())->fecha_firma)->translatedFormat(
        'l d F Y h:i:s A',
    )
    : '' }}
                                </p>
                            </th>
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
                                <input disabled type="text" class="input-field"
                                    value="{{ optional(optional($proyecto->firma_proyecto_enlace()->first())->empleado)->nombre_completo }}"
                                    disabled>
                            </td>
                            <td class="full-width" colspan="1">Nombre:</td>
                            <td class="full-width" colspan="1">
                                <input disabled type="text" class="input-field"
                                    placeholder="Ingrese el nombre del responsable"
                                    value="{{ optional(optional($proyecto->firma_proyecto_decano()->first())->empleado)->nombre_completo }}"
                                    disabled>
                            </td>
                        </tr>
                        <tr>
                            <td class="full-width" colspan="2" style="height: 200px; width: 200px;">
                                <img src="{{ Storage::url(optional(optional($proyecto->firma_proyecto_enlace()->first())->sello)->ruta_storage) }}"
                                    alt="" width="200px">
                                <img src="{{ Storage::url(optional(optional($proyecto->firma_proyecto_enlace()->first())->firma)->ruta_storage) }}"
                                    alt="" width="200px">
                            </td>
                            <td class="full-width" colspan="2" style="height: 200px; width: 200px;">
                                <img src="{{ Storage::url(optional(optional($proyecto->firma_proyecto_decano()->first())->sello)->ruta_storage) }}"
                                    alt="" width="200px">
                                <img src="{{ Storage::url(optional(optional($proyecto->firma_proyecto_decano()->first())->firma)->ruta_storage) }}"
                                    alt="" width="200px">
                            </td>
                        </tr>

                        <tr>
                            <th class="header" colspan="2">Firma del Coordinador(a) del Comité de Vinculación <br>
                                de
                                la Facultad o Campus Universitario
                                <br>
                                <p>
                                    {{ optional($proyecto->firma_proyecto_enlace->first())->fecha_firma
    ? \Carbon\Carbon::parse(optional($proyecto->firma_proyecto_enlace->first())->fecha_firma)->translatedFormat(
        'l d F Y h:i:s A',
    )
    : '' }}
                                </p>
                            </th>
                            <th class="header" colspan="2">Firma y sello del Decano(a) o Director(a)
                                <br>

                                <p>
                                    {{ optional($proyecto->firma_proyecto_decano->first())->fecha_firma
    ? \Carbon\Carbon::parse(optional($proyecto->firma_proyecto_decano->first())->fecha_firma)->translatedFormat(
        'l d F Y h:i:s A',
    )
    : '' }}
                                </p>

                            </th>
                        </tr>
                    </table>
                </div>
                <div class="section4">
                    <div class="section-title">V. ANEXOS. </div>
                    <table class="table_datos5">
                        <tr>
                            <th class="header" colspan="19">
                                1. Categorias de proyectos de vinculación con la sociedad
                            </th>
                        </tr>
                        <tr>

                            @forelse ($proyecto->categoria as $categoria)
                        <tr>
                            <td class="full-width
                                        " colspan="19">
                                <input disabled type="text" class="input-field"
                                    placeholder="Ingrese el departamento" value="{{ $categoria->nombre }}" disabled>
                            </td>
                        </tr>

                    @empty
                        <tr>
                            <td class="full-width 
                                        " colspan="19">
                                <input disabled type="text" class="input-field"
                                    placeholder="Ingrese el departamento" value="No hay categorias" disabled>
                            </td>
                        </tr>
                        @endforelse


                        </tr>
                    </table>
                    <table class="table_datos5">
                        <tr>
                            <th class="header" colspan="19">2. ODS en el que se enmarca el proyecto: Utilizar el
                                documento Agenda 20/45 y objetivos de desarrollo sostenible. </th>
                        </tr>
                        <tr>
                            @forelse ($proyecto->ods as $ods)
                        <tr>
                            <td class="full-width
                                " colspan="19">
                                <input disabled type="text" class="input-field"
                                    placeholder="Ingrese el departamento" value="{{ $ods->nombre }}" disabled>
                            </td>
                        </tr>
                    @empty

                        <td class="full-width
                                " colspan="19">
                            <input disabled type="text" class="input-field" placeholder="Ingrese el departamento"
                                value="No hay ODS registrados en este momento" disabled>
                        </td>
                        @endforelse

                        </tr>
                    </table>

                    <table class="table_datos5">
                        <tr>
                            <th class="header" colspan="19">3. Anexos </th>
                        </tr>
                        <tr>
                            @forelse ($proyecto->anexos as $anexo)
                        <tr>
                            <td class="full-width
                                " colspan="8">
                                <input disabled type="text" class="input-field"
                                    placeholder="Ingrese el departamento" value="ANEXO DEL PROYECTO" disabled>
                           </td>
                            <td class="full-width" colspan="11">
                                <x-filament::modal width="7xl" :close-button="true" :close-by-escaping="false">
                                    <x-slot name="heading">
                                        Anexo
                                    </x-slot>
                                    <x-slot name="trigger">
                                        <x-filament::button>
                                            Ver anexo
                                        </x-filament::button>
                                    </x-slot>
                                    <iframe src="{{ Storage::url($anexo->documento_url) }}"
                                        style="width: 100%; height: 85vh; border: none;"></iframe>
                                </x-filament::modal>
                                <x-filament::button>
                                    <a href="{{ Storage::url($anexo->documento_url) }}" download
                                        style="text-decoration: none; color: inherit;">
                                        Descargar
                                    </a>
                                </x-filament::button>

                            </td>
                        </tr>
                    @empty
                        <td class="full-width
                                " colspan="19">
                            <input disabled type="text" class="input-field" placeholder="Ingrese el departamento"
                                value="No hay anexos registrados en este momento" disabled>
                        </td>
                        @endforelse
                        </tr>
                    </table>


                    <div class="section-title" style="margin-top: 20px;">VI. DATOS DE REGISTRO. </div>
                    <table class="table_datos5">
                        <tr>
                            <td class="sub-header" colspan="1">Responsable de revisión</td>
                            <td class="full-width" colspan="10">
                                <input disabled type="text" class="input-field"
                                    value="{{ optional(optional($proyecto->firma_revisor_vinculacion()->first())->empleado)->nombre_completo }}"
                                    placeholder="Ingrese el día">
                            </td>
                        </tr>
                    </table>
                    <table class="table_datos5">
                        <tr>
                            <td class="sub-header" colspan="1">Director de Vinculación</td>
                            <td class="full-width" colspan="10">
                                <input disabled type="text" class="input-field"
                                    value="{{ optional(optional($proyecto->firma_director_vinculacion()->first())->empleado)->nombre_completo }}"
                                    placeholder="Ingrese el día">
                            </td>
                        </tr>
                    </table>
                    <table class="table_datos5">
                        <td class="sub-header" colspan="1">Fecha de Aprobación</td>
                        <td class="full-width" colspan="6">
                            <input disabled type="text" class="input-field"
                                value="{{ $proyecto->fecha_aprobacion }}" placeholder="Ingrese el día">
                        </td>
                        <td class="sub-header" colspan="1">Fecha de Registro</td>
                        <td class="full-width" colspan="6">
                            <input disabled type="text" class="input-field"
                                value="{{ $proyecto->fecha_registro }}" placeholder="Ingrese el día">
                        </td>
                        </tr>
                    </table>
                    <table class="table_datos7">
                        <td class="sub-header" colspan="1">No. de Libro </td>
                        <td class="full-width" colspan="1">
                            <input disabled type="text" class="input-field"
                                value="{{ $proyecto->numero_libro }}" placeholder="Ingrese el día">
                        </td>
                        <td class="sub-header" colspan="1">No. de Tomo </td>
                        <td class="full-width" colspan="1">
                            <input disabled type="text" class="input-field" value="{{ $proyecto->numero_tomo }}"
                                placeholder="Ingrese el día">
                        </td>
                        <td class="sub-header" colspan="1">No. de Folio </td>
                        <td class="full-width" colspan="1">
                            <input disabled type="text" class="input-field"
                                value="{{ $proyecto->numero_folio }}" placeholder="Ingrese el día">
                        </td>
                        </tr>
                    </table>

                    <div class="header-box"> <input disabled type="text" class="input-field"
                            value="{{ $proyecto->numero_dictamen }}" placeholder="Ingrese No. dictamen de Proyecto">
                        <p class="header-text">No. dictamen de Proyecto</p>
                    </div>
                </div>
            </div>
        </div>
    </section>
    </div>
   {{-- filepath: c:\Users\acxel\Desktop\Desarrollo\Git Repos\UNAH-VINCULACION\resources\views\livewire\docente\proyectos\historial-proyecto.blade.php --}}

<div class="w-full md:w-2/5 lg:w-1/3">
    <h1 class="text-2xl font-bold dark:text-white text-gray-900 mb-4">
        Historial de movimientos
    </h1>
    <p class="text-gray-700 mb-2">
            Días desde la creación del proyecto: <span class="font-bold">{{ $diasTranscurridos }}</span>
        </p>
    @if(count($estados) > 0)
        <ol class="relative border-s border-yellow-600">
            @foreach($estados as $index => $estado)
                <li class="{{ $index < count($estados) - 1 ? 'mb-10' : '' }} ms-4">
                    <div class="absolute w-3 h-3 bg-yellow-600 rounded-full mt-1.5 -start-1.5 border border-white"></div>
                    <div class="flex items-center">
                        <time class="text-sm font-normal leading-none text-yellow-600">
                            {{ Carbon::parse($estado->created_at)->format('d') }} de {{ Carbon::parse($estado->created_at)->translatedFormat('F') }} del {{ Carbon::parse($estado->created_at)->format('Y') }}
                        </time>
                        <span class="ml-2 px-2 py-0.5 text-xs font-medium rounded-full {{ $estado->estadoable_type === \App\Models\Proyecto\Proyecto::class ? 'bg-blue-100 text-blue-800' : 'bg-green-100 text-green-800' }}">
                            @if($estado->estadoable_type === \App\Models\Proyecto\Proyecto::class)
                                Proyecto
                            @elseif($estado->estadoable_type === \App\Models\Proyecto\DocumentoProyecto::class)
                                Informe/Documento
                            @else
                                Otro
                            @endif
                        </span>
                    </div>
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-200 mt-2">
                        {{ $estado->tipoestado->nombre ?? 'Cambio de estado' }}
                    </h3>
                    <p class="mb-4 text-base font-normal text-gray-500 dark:text-gray-400">
                        @if($estado->empleado)
                            {{ $estado->empleado->nombre_completo }} 
                        @endif
                        {{ $estado->comentario ? '— ' . $estado->comentario : '' }}
                    </p>
                </li>
            @endforeach
        </ol>
    @else
        <div class="flex items-center justify-center h-full">
            <p class="text-gray-500">No hay movimientos registrados para este proyecto.</p>
        </div>
    @endif
</div>
</div>