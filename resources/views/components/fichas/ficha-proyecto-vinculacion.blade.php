<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=3, user-scalable=yes">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Document</title>
    <link rel="stylesheet" href="{{ asset('css/app/fichaVinculacion.css') }}">
</head>

<body style="background-color: #f2f2f2; ">
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

    <x-filament::section collapsible collapsed persist-collapsed id="user-details">
        <x-slot name="heading">
            Ficha del proyecto
        </x-slot>

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
                    <h1>FORMULARIO DE REGISTRO DE PROYECTO DE VINCULACIÓN CON CONTRAPARTE</h1>
                </div>

                <div class="section1">
                    <div class="section-title">I. INFORMACIÓN GENERAL DEL PROYECTO </div>
                    <table class="table_datos1">
                        <tr>
                            <th class="full-width1" rowspan="2">Fecha de solicitud de registro:</th> 
                            <td class="full-width1" colspan="4">
                                <div class="date-container">
                                    <div class="date-part">
                                        <span class="date-label">Día</span>
                                         </div>
                                    <div class="date-part">
                                        <span class="date-label">Mes</span>
                                    </div>
                                    <div class="date-part">
                                        <span class="date-label">Año</span>
                                      </div>
                                </div>
                            </td>
                        </tr>
                        <tr>
                            <td class="full-width" colspan="4">
                                <div class="date-container">
                                    <div class="date-part">
                                        <input disabled type="text" class="input-field" value="{{ $proyecto->fecha_registro ? $proyecto->fecha_registro->format('d') : '' }}">
                                    </div>
                                    <div class="date-part">
                                        <input disabled type="text" class="input-field" value="{{ $proyecto->fecha_registro ? $proyecto->fecha_registro->format('m') : '' }}">
                                    </div>
                                    <div class="date-part">
                                        <input disabled type="text" class="input-field" value="{{ $proyecto->fecha_registro ? $proyecto->fecha_registro->format('Y') : '' }}">
                                    </div>
                                </div>
                            </td>
                        </tr>
                        <tr>
                            <th class="full-width1">1. Nombre del Proyecto:</th>
                            <td class="full-width" colspan="5">

                                <input disabled type="text" class="input-field"
                                    placeholder="Ingrese el nombre del proyecto"
                                    value="{{ $proyecto->nombre_proyecto }}" disabled>

                            </td>
                        </tr>
                        <tr>
                            <th class="full-width1" rowspan="5">2. Unidad Académica:</th>
                            <td class="sub-header" colspan="1">Facultad /Centro Universitario Regional/Instituto Tecnológico</td>
                            <td class="full-width" colspan="4">
                                <ul>
                                    @foreach ($proyecto->facultades_centros as $centro)
                                        <li> {{ $centro->nombre }} </li>
                                    @endforeach
                                </ul>
                            </td>
                        </tr>
                        <tr>
                            <td class="sub-header" colspan="1">Escuela, Departamento Académico, Técnicos Universitarios, Instituto de Investigación, Observatorio, Consultorio</td>
                            <td class="full-width" colspan="4">
                                <ul>
                                    @foreach ($proyecto->departamentos_academicos as $departamento)
                                        <li>{{ $departamento->nombre }}</li>
                                    @endforeach
                                </ul>
                            </td>
                        </tr>
                        <tr>
                            <td class="sub-header" colspan="1">Carreras</td>
                            <td class="full-width" colspan="4">
                                <ul>
                                    @foreach ($proyecto->carreras as $carrera)
                                        <li>{{ $carrera->nombre }}</li>
                                    @endforeach
                                </ul>
                            </td>
                        </tr>
                        <tr>
                            <td class="sub-header" colspan="1">Programa al que pertenece </td>
                            <td class="full-width" colspan="4">
                                 <input disabled type="text" class="input-field"
                                    placeholder="Ingrese el programa al que pertenece"
                                    value="{{ $proyecto->programa_pertenece }}" disabled>
                            </td>
                        </tr>
                        <tr>
                            <td class="sub-header" colspan="1">Líneas de investigación de la unidad académica</td>
                            <td class="full-width" colspan="4">
                                <input disabled type="text" class="input-field"
                                    placeholder="Ingrese el programa al que pertenece"
                                    value="{{ $proyecto->lineas_investigacion_academica }}" disabled>
                            </td>
                        </tr>
                        <tr>
                            <th class="full-width1" rowspan="1">3. Modalidad</th>
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
                            <th class="full-width1" rowspan="1">4. Alineamiento con ejes prioritarios de la UNAH</th>
                            <td class="sub-header1" colspan="1">Desarrollo económico y social <br>
                                <input disabled type="checkbox" class="No"
                                    @if ($proyecto->ejes_prioritarios_unah?->contains('nombre', 'Desarrollo económico y social')) checked @endif>
                            </td>
                            <td class="sub-header1" colspan="1">Democracia y gobernabilidad<br>
                                <input disabled type="checkbox" class="No"
                                    @if ($proyecto->ejes_prioritarios_unah?->contains('nombre', 'Democracia y gobernabilidad')) checked @endif>
                            </td>
                            <td class="sub-header1" colspan="1">Población y condiciones de vida <br>
                                <input disabled type="checkbox" class="No"
                                    @if ($proyecto->ejes_prioritarios_unah?->contains('nombre', 'Población y condiciones de vida')) checked @endif>
                            </td>
                            <td class="sub-header1" colspan="1">Ambiente, biodiversidad y desarrollo<br>
                                <input disabled type="checkbox" class="No"
                                    @if ($proyecto->ejes_prioritarios_unah?->contains('nombre', 'Ambiente, biodiversidad y desarrollo')) checked @endif>
                            </td>
                        </tr>


                        
                        <tr>
                            <th class="full-width1" rowspan="2">5. Categoría del proyecto:</th>
                            <td class="sub-header1" colspan="1">Desarrollo Local <br>
                                <input disabled type="checkbox" class="No"
                                    @if ($proyecto->categoria->contains('nombre', 'Desarrollo Local')) checked @endif>
                            </td>
                            <td class="sub-header1" colspan="1">Desarrollo Regional<br>
                                <input disabled type="checkbox" class="No"
                                    @if ($proyecto->categoria->contains('nombre', 'Desarrollo Regional')) checked @endif>
                            </td>
                            <td class="sub-header1" colspan="1">Volunt. Académico<br>
                                <input disabled type="checkbox" class="No"
                                    @if ($proyecto->categoria->contains('nombre', 'Volunt. Académico')) checked @endif>
                            </td>
                            <td class="sub-header1" colspan="1">Seguim. a egresados<br>
                                <input disabled type="checkbox" class="No"
                                    @if ($proyecto->categoria->contains('nombre', 'Seguim. a egresados')) checked @endif>
                            </td>
                        </tr>
                        <tr>
                           <td class="sub-header1" colspan="1">I + D + i <br>
                                <input disabled type="checkbox" class="No"
                                    @if ($proyecto->categoria->contains('nombre', 'I + D + i')) checked @endif>
                            </td>
                            <td class="sub-header1" colspan="1">Cultural<br>
                                <input disabled type="checkbox" class="No"
                                    @if ($proyecto->categoria->contains('nombre', 'Cultural')) checked @endif>
                            </td>
                            <td class="sub-header1" colspan="1">Comunicac<br>
                                <input disabled type="checkbox" class="No"
                                    @if ($proyecto->categoria->contains('nombre', 'Comunicac')) checked @endif>
                            </td>
                            <td class="sub-header1" colspan="1">APS<br>
                                <input disabled type="checkbox" class="No"
                                    @if ($proyecto->categoria->contains('nombre', 'APS')) checked @endif>
                            </td>
                        </tr>
                        
                        
                        <!-- FECHAS DE EJECUCION  -->
                        <tr>
                            <th class="full-width1" rowspan="1">6. Fechas de ejecución del proyecto:</th>
                            <td class="sub-header" colspan="1">Fecha de inicio:</td>   
                            <td class="full-width" colspan="1">
                                <div class="date-container">
                                    <div class="date-part">
                                        <span class="date-label">Día</span>
                                        <input disabled type="text" class="input-field" value="{{ $proyecto->fecha_inicio ? $proyecto->fecha_inicio->format('d') : '' }}">
                                    </div>
                                    <div class="date-part">
                                        <span class="date-label">Mes</span>
                                        <input disabled type="text" class="input-field" value="{{ $proyecto->fecha_inicio ? $proyecto->fecha_inicio->format('m') : '' }}">
                                    </div>
                                    <div class="date-part">
                                        <span class="date-label">Año</span>
                                        <input disabled type="text" class="input-field" value="{{ $proyecto->fecha_inicio ? $proyecto->fecha_inicio->format('Y') : '' }}">
                                    </div>
                                </div>
                            </td>
                            <td class="sub-header" colspan="1">Fecha de finalización:</td> 
                            <td class="full-width" colspan="1">
                                <div class="date-container">
                                    <div class="date-part">
                                        <span class="date-label">Día</span>
                                        <input disabled type="text" class="input-field" value="{{ $proyecto->fecha_finalizacion ? $proyecto->fecha_finalizacion->format('d') : '' }}">
                                    </div>
                                    <div class="date-part">
                                        <span class="date-label">Mes</span>
                                        <input disabled type="text" class="input-field" value="{{ $proyecto->fecha_finalizacion ? $proyecto->fecha_finalizacion->format('m') : '' }}">
                                    </div>
                                    <div class="date-part">
                                        <span class="date-label">Año</span>
                                        <input disabled type="text" class="input-field" value="{{ $proyecto->fecha_finalizacion ? $proyecto->fecha_finalizacion->format('Y') : '' }}">
                                    </div>
                                </div>
                            </td>
                        </tr> 
                   
                        <!-- TABLA DE BENEFICIARIOS DIRECTOS -->
                        <tr>
                            <th class="full-width1" rowspan="5" colspan="1">7. Beneficiarios directos (número aproximado)</th>
                           <td class="sub-header" colspan="1">Hombres</td>
                            <td class="full-width" colspan="4">
                               <input type="text" class="input-field" placeholder="0"
                                    value="{{ $proyecto->hombres }}" disabled>
                            </td>
                        </tr>
                        <tr>
                            <td class="sub-header" colspan="1">Mujeres</td>
                            <td class="full-width" colspan="4">
                               <input type="text" class="input-field" placeholder="0"
                                    value="{{ $proyecto->mujeres }}" disabled>
                            </td>
                        </tr>
                      <!--  <tr>
                            <td class="sub-header" colspan="1">Otros (indicar número y tipo)</td>
                            <td class="full-width" colspan="4">
                               <input type="text" class="input-field" placeholder="0"
                                    value="{{ $proyecto->otros }}" disabled>
                            </td>
                        </tr> -->
                        <tr>
                            <td class="sub-header" rowspan="2" colspan="1">Indicar tipo de etnia</td>
                            <td class="sub-header" colspan="1">Indígena</td>
                            <td class="sub-header" colspan="1">Afrodescendiente</td>
                            <td class="sub-header" colspan="1">Mestizo</td>
                        </tr>
                        <tr>
                            <td class="full-width" colspan="1">
                                <div class="date-container">
                                    <div class="date-part">
                                        <span class="date-label">Hombres</span>
                                        <input disabled type="text" class="input-field" value="{{$proyecto->indigenas_hombres}}" placeholder="0">
                                    </div>
                                    <div class="date-part">
                                        <span class="date-label">Mujeres</span>
                                        <input disabled type="text" class="input-field" value="{{$proyecto->indigenas_mujeres}}" placeholder="0">
                                    </div>
                                </div>
                            </td>
                            <td class="full-width" colspan="1">
                                <div class="date-container">
                                    <div class="date-part">
                                        <span class="date-label">Hombres</span>
                                        <input disabled type="text" class="input-field" value="{{$proyecto->afroamericanos_hombres}}" placeholder="0">
                                    </div>
                                    <div class="date-part">
                                        <span class="date-label">Mujeres</span>
                                        <input disabled type="text" class="input-field" value="{{$proyecto->afroamericanos_mujeres}}" placeholder="0">
                                    </div>
                                </div>
                            </td>
                            <td class="full-width" colspan="1">
                                <div class="date-container">
                                    <div class="date-part">
                                        <span class="date-label">Hombres</span>
                                        <input disabled type="text" class="input-field" value="{{$proyecto->mestizos_hombres}}" placeholder="0">
                                    </div>
                                    <div class="date-part">
                                        <span class="date-label">Mujeres</span>
                                        <input disabled type="text" class="input-field" value="{{$proyecto->mestizos_mujeres}}" placeholder="0">
                                    </div>
                                </div>
                            </td>
                        </tr>
                         <tr>
                            <td class="sub-header" colspan="2">7. Población beneficiada (número aproximado) </td>
                            <td class="full-width" colspan="3">
                                <input disabled type="text" class="input-field" placeholder="Ingrese el día"
                                    value="{{ floor($proyecto->poblacion_participante) }}" disabled>
                            </td>
                        </tr>
        
                    <!-- Sitio de ejecución del proyecto --> 
                    <tr>
                        <th class="full-width1" colspan="6">8. Sitio de ejecución del proyecto</th>
                    </tr>
                    <tr>
                            <td class="sub-header" colspan="2">Modalidad de ejecución (marcar con una X)</td>
                            <td class="full-width" colspan="1">
                                Distancia <br>
                                <input disabled type="checkbox" class="no"
                                    @if ($proyecto->modalidad_ejecucion == 'Distancia') checked @endif>
                            </td>
                            <td class="full-width" colspan="1">
                                Presencial <br>
                                <input disabled type="checkbox" class="no"
                                    @if ($proyecto->modalidad_ejecucion == 'Presencial') checked @endif>
                            </td>
                            <td class="full-width" colspan="1">
                                Bimodal <br>
                                <input disabled type="checkbox" class="no"
                                    @if ($proyecto->modalidad_ejecucion == 'Bimodal') checked @endif>
                            </td>

                        </tr>
                    <tr>
                        <td class="sub-header" colspan="1">Departamento</td>
                        <td class="full-width" colspan="1">
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
                        <td class="sub-header" colspan="1">Aldea (incluye ciudad)</td>
                        <td class="full-width" colspan="2">
                            <input disabled type="text" class="input-field"
                                    placeholder="Ingrese el nombre de la entidad" value="{{ $proyecto->aldea }}"
                                    disabled>
                        </td>
                    </tr>
                    <tr>
                        <td class="sub-header" colspan="1">Municipio</td>
                        <td class="full-width" colspan="1">
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
                        <td class="sub-header" colspan="1">Caserío</td>
                        <td class="full-width" colspan="2">
                            @if(is_array($proyecto->caserio) && count($proyecto->caserio) > 0)
                                @foreach($proyecto->caserio as $caserio)
                                    <input disabled type="text" class="input-field"
                                        placeholder="Ingrese el caserío" value="{{ $caserio }}" disabled>
                                @endforeach
                            @else
                                <input disabled type="text" class="input-field"
                                    placeholder="Ingrese el caserío" value="{{ $proyecto->caserio ?? '' }}" disabled>
                            @endif
                        </td>
                    </tr>
                    <tr>
                        <td class="sub-header" colspan="1">Región</td>
                        <td class="full-width" colspan="1">
                            @if(is_array($proyecto->region) && count($proyecto->region) > 0)
                                @foreach($proyecto->region as $region)
                                    <input disabled type="text" class="input-field"
                                        placeholder="Ingrese la región" value="{{ $region }}" disabled>
                                @endforeach
                            @else
                                <input disabled type="text" class="input-field"
                                    placeholder="Ingrese la región" value="{{ $proyecto->region ?? '' }}" disabled>
                            @endif
                        </td>
                        <td class="sub-header" colspan="1">País</td>
                        <td class="full-width" colspan="2">
                            @if(is_array($proyecto->pais) && count($proyecto->pais) > 0)
                                @foreach($proyecto->pais as $pais)
                                    <input disabled type="text" class="input-field"
                                        placeholder="Ingrese el país" value="{{ $pais }}" disabled>
                                @endforeach
                            @else
                                <input disabled type="text" class="input-field"
                                    placeholder="Ingrese el país" value="{{ $proyecto->pais ?? '' }}" disabled>
                            @endif
                        </td>
                    </tr>

                    <!-- TABLA DE PRESUPUESTO DEL PROYECTO -->
                     <tr>
                            <th class="full-width1" colspan="19"> 13. Presupuesto del Proyecto</th>
                        </tr>
                        <tr>
                            <td class="sub-header" colspan="2">Aporte académico de la UNAH:</td>
                            <td class="full-width" colspan="6">
                                <input disabled type="text" class="input-field"
                                    value="{{ number_format($proyecto->total_aporte_institucional ?? 0, 2, '.', ',') }}"
                                    placeholder="Ingrese el nombre de la entidad">
                            </td>
                        </tr>
                        <tr>
                            <td class="sub-header" colspan="2">Aporte de la contraparte:</td>
                            <td class="full-width" colspan="6">
                                <input disabled type="text" class="input-field"
                                    value="{{ number_format($proyecto->presupuesto?->aporte_contraparte ?? 0, 2, '.', ',') }}"
                                    placeholder="Ingrese el nombre de la entidad">
                            </td>
                        </tr>
                        <tr>
                            <td class="sub-header" colspan="2">Aporte de la comunidad:</td>
                            <td class="full-width" colspan="6">
                                <input disabled type="text" class="input-field"
                                    value="{{ number_format($proyecto->presupuesto?->aporte_comunidad ?? 0, 2, '.', ',') }}"
                                    placeholder="Ingrese el nombre de la entidad">
                            </td>
                        </tr>

                        <tr>
                            <td class="sub-header" colspan="2">Aporte fondos internacionales</td>
                            <td class="full-width" colspan="6">
                                <input disabled type="text" class="input-field"
                                    value="{{ number_format($proyecto->presupuesto?->aporte_internacionales ?? 0, 2, '.', ',') }}"
                                    placeholder="Ingrese el nombre de la entidad">
                            </td>
                        <tr>
                            <td class="sub-header" colspan="2">Aportes de otras universidades:</td>
                            <td class="full-width" colspan="6">
                                <input disabled type="text" class="input-field"
                                    value="{{ number_format($proyecto->presupuesto?->aporte_otras_universidades ?? 0, 2, '.', ',') }}"
                                    placeholder="Ingrese el nombre de la entidad">
                            </td>
                        </tr>

                        <tr>
                            <td class="sub-header" colspan="2">Otros Aportes:</td>
                            <td class="full-width" colspan="6">
                                <input disabled type="text" class="input-field"
                                    value="{{ number_format($proyecto->presupuesto?->otros_aportes ?? 0, 2, '.', ',') }}"
                                    placeholder="Ingrese el nombre de la entidad">
                            </td>
                        </tr>
                        
                        
                        <tr>
                            <td class="sub-header4" colspan="2">TOTAL CONTRAPARTE:</td>
                            <td class="full-width" colspan="6">
                                <input disabled type="text" class="input-field"
                                    value="{{ number_format(($proyecto->presupuesto?->aporte_contraparte ?? 0) + ($proyecto->presupuesto?->aporte_comunidad ?? 0), 2, '.', ',') }}"
                                    placeholder="Ingrese el nombre de la entidad">
                            </td>
                        </tr>
                        <tr>
                            <td class="sub-header4" colspan="2">MONTO TOTAL DEL PROYECTO:</td>
                            <td class="full-width" colspan="6">
                                <input disabled type="text" class="input-field"
                                    value="{{ number_format(
                                        ($proyecto->presupuesto?->aporte_internacionales ?? 0) +
                                        ($proyecto->total_aporte_institucional ?? 0) +
                                        ($proyecto->presupuesto?->aporte_otras_universidades ?? 0) +
                                        ($proyecto->presupuesto?->otros_aportes ?? 0) +
                                        ($proyecto->presupuesto?->aporte_contraparte ?? 0) +
                                        ($proyecto->presupuesto?->aporte_comunidad ?? 0), 2, '.', ','
                                    ) }}"
                                    placeholder="Ingrese el nombre de la entidad">
                            </td>
                        </tr>
                        <tr>
                            <th class="full-width1" colspan="19"> En caso de superávit o rentabilidad en el proyecto,
                                haga un
                                desglose detallado
                                en que se va a invertir el superávit según las normas de ejecución presupuestaria de la
                                UNAH
                            </th>
                        </tr>
                        <tr>
                            <td class="sub-header" colspan="3">Inversión:</td>
                            <td class="sub-header" colspan="2">Monto:</td>
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

                <div class="section2">
                    <div class="section-title">II. EQUIPO EJECUTOR DEL PROYECTO. </div>
                    <table class="table_datos1">
                        <!-- TABLA COORDINADOR DEL PROYECTO -->
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

                    <!-- TABLA DE INTEGRANTES DEL EQUIPO UNIVERSITARIO -->
                        <tr>
                            <th class="full-width1" colspan="6">Integrantes del equipo docente permanente tiempo completo
                                (Agregar más líneas de ser necesario)</th>
                        </tr>
                        <tr>
                            <th class="full-width1" colspan="2">Cantidad de integrantes empleados:</th>
                            <td class="full-width" colspan="4">
                                <input disabled type="text" class="input-field"
                                    placeholder="Ingrese el número de empleados"
                                    value="{{ $proyecto->integrantes->count() }}" disabled>
                               
                            </td>
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
                        <tr>
                            <th class="full-width1" colspan="6">Integrantes del equipo de cooperación internacional
                                (Agregar más líneas de ser necesario)</th>
                        </tr>
                        <tr>
                            <td class="sub-header">Nombre Completo:</td>
                            <td class="sub-header">Pasaporte:</td>
                            <td class="sub-header">Correo electrónico:</td>
                            <td class="sub-header">País:</td>
                            <td class="sub-header">Universidad/Institucion:</td>
                        </tr>
                        @forelse ($proyecto->integrantesInternacionales as $integrante)
                            <tr>
                                <td class="full-width" colspan="1">
                                    <input disabled type="text" class="input-field"
                                        placeholder="Ingrese el nombre completo"
                                        value="{{ $integrante->nombre_completo }}" disabled>
                                </td>
                                <td class="full-width" colspan="1">
                                    <input disabled type="text" class="input-field"
                                        placeholder="Ingrese el número de empleado"
                                        value="{{ $integrante->documento_identidad }}" disabled>
                                </td>
                                <td class="full-width" colspan="1">
                                    <input disabled type="text" class="input-field"
                                        placeholder="Ingrese el correo electrónico"
                                        value="{{ $integrante->email }}" disabled>
                                </td>
                                <td class="full-width" colspan="1">
                                    <input disabled type="text" class="input-field"
                                        placeholder="Ingrese la categoría"
                                        value="{{ $integrante->pais }}" disabled>
                                </td>
                                <td class="full-width" colspan="1">
                                    <input disabled type="text" class="input-field"
                                        placeholder="Ingrese el departamento"
                                        value="{{ $integrante->institucion }}" disabled>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td class="full-width
                                " colspan="6">
                                    <input disabled type="text" class="input-field"
                                        placeholder="Aqui van los integrantes internacionales" value="No hay integrantes" disabled>
                                </td>
                            </tr>
                        @endforelse
                    </table>

                </div>

                <!-- SECCIÓN DE CUANTIFICACIÓN DEL TRABAJO VOLUNTARIO -->
                <div class="section3">
                    <div class="section-title">III. CUANTIFICACIÓN TRABAJO VOLUNTARIO </div>
                    <table class="table_datos1">
                        <!-- PARTICIPACIÓN DE ESTUDIANTES -->
                        <tr>
                            <th class="full-width1" rowspan="5">Participación de estudiantes</th>
                            <td class="sub-header" colspan="1">TOTAL</td>
                            <td class="sub-header" colspan="6">Desglose del tipo de participación de estudiantes:</td>
                        </tr>
                        <tr>
                            <td class="sub-header" colspan="1">Hombres</td>
                            <td class="sub-header" colspan="2" rowspan="2">Práctica Profesional</td>
                            <td class="sub-header" colspan="2" rowspan="2">Servicio Social o PPS</td>
                            <td class="sub-header" colspan="2" rowspan="2">Voluntariado</td>
                        </tr>
                        <tr>
                            <td class="full-width" colspan="1">
                                <input disabled type="text" class="input-field" value="{{ $proyecto->estudiantes_hombres }}" disabled>
                            </td>
                        </tr>
                        <tr>
                            <td class="sub-header" colspan="1">Mujeres</td>
                            <td class="sub-header" colspan="1">Hombres</td>
                            <td class="sub-header" colspan="1">Mujeres</td>
                            <td class="sub-header" colspan="1">Hombres</td>
                            <td class="sub-header" colspan="1">Mujeres</td>
                            <td class="sub-header" colspan="1">Hombres</td>
                            <td class="sub-header" colspan="1">Mujeres</td>
                        </tr>
                        <tr>
                            <td class="full-width" colspan="1">
                                <input disabled type="text" class="input-field" value="{{ $proyecto->estudiantes_mujeres }}" disabled>
                            </td>
                            <td class="full-width" colspan="1">
                                <input disabled type="text" class="input-field" value="{{ $proyecto->getEstudiantesPorTipo('Practica Profesional', 'Masculino') }}" disabled>
                            </td>
                            <td class="full-width" colspan="1">
                                <input disabled type="text" class="input-field" value="{{ $proyecto->getEstudiantesPorTipo('Practica Profesional', 'Femenino') }}" disabled>
                            </td>
                            <td class="full-width" colspan="1">
                                <input disabled type="text" class="input-field" value="{{ $proyecto->getEstudiantesPorTipo('Servicio Social o PPS', 'Masculino') }}" disabled>
                            </td>
                            <td class="full-width" colspan="1">
                                <input disabled type="text" class="input-field" value="{{ $proyecto->getEstudiantesPorTipo('Servicio Social o PPS', 'Femenino') }}" disabled>
                            </td>
                            <td class="full-width" colspan="1">
                                <input disabled type="text" class="input-field" value="{{ $proyecto->getEstudiantesPorTipo('Voluntariado', 'Masculino') }}" disabled>
                            </td>
                            <td class="full-width" colspan="1">
                                <input disabled type="text" class="input-field" value="{{ $proyecto->getEstudiantesPorTipo('Voluntariado', 'Femenino') }}" disabled>
                            </td>
                        </tr>

                        <!-- PARTICIPACIÓN DE PERSONAL DOCENTE -->
                        <tr>
                            <th class="full-width1" rowspan="5">Participación de personal docente</th>
                            <td class="sub-header" colspan="1">TOTAL</td>
                            <td class="sub-header" colspan="6">Desglose del tipo de participación de personal docente:</td>
                        </tr>
                        <tr>
                            <td class="sub-header" colspan="1">Hombres</td>
                            <td class="sub-header" colspan="2" rowspan="2">Profesores x hora</td>
                            <td class="sub-header" colspan="2" rowspan="2">Profesores horarios</td>
                            <td class="sub-header" colspan="2" rowspan="2">Profesores permanentes</td>
                        </tr>
                        <tr>
                            <td class="full-width" colspan="1">
                                <input disabled type="text" class="input-field" value="{{ $proyecto->docentes_hombres }}" disabled>
                            </td>
                        </tr>
                        <tr>
                            <td class="sub-header" colspan="1">Mujeres</td>
                            <td class="sub-header" colspan="1">Hombres</td>
                            <td class="sub-header" colspan="1">Mujeres</td>
                            <td class="sub-header" colspan="1">Hombres</td>
                            <td class="sub-header" colspan="1">Mujeres</td>
                            <td class="sub-header" colspan="1">Hombres</td>
                            <td class="sub-header" colspan="1">Mujeres</td>
                        </tr>
                        <tr>
                            <td class="full-width" colspan="1">
                                <input disabled type="text" class="input-field" value="{{ $proyecto->docentes_mujeres }}" disabled>
                            </td>
                            <td class="full-width" colspan="1">
                                <input disabled type="text" class="input-field" value="{{ $proyecto->getDocentesPorCategoria('Profesores x hora', 'Masculino') }}" disabled>
                            </td>
                            <td class="full-width" colspan="1">
                                <input disabled type="text" class="input-field" value="{{ $proyecto->getDocentesPorCategoria('Profesores x hora', 'Femenino') }}" disabled>
                            </td>
                            <td class="full-width" colspan="1">
                                <input disabled type="text" class="input-field" value="{{ $proyecto->getDocentesPorCategoria('Profesores horarios', 'Masculino') }}" disabled>
                            </td>
                            <td class="full-width" colspan="1">
                                <input disabled type="text" class="input-field" value="{{ $proyecto->getDocentesPorCategoria('Profesores horarios', 'Femenino') }}" disabled>
                            </td>
                            <td class="full-width" colspan="1">
                                <input disabled type="text" class="input-field" value="{{ $proyecto->getDocentesPorCategoria('permanente', 'Masculino') }}" disabled>
                            </td>
                            <td class="full-width" colspan="1">
                                <input disabled type="text" class="input-field" value="{{ $proyecto->getDocentesPorCategoria('permanente', 'Femenino') }}" disabled>
                            </td>
                        </tr>

                        <!-- PARTICIPACIÓN DE PERSONAL ADMINISTRATIVO -->
                        <tr>
                            <th class="full-width1" rowspan="5">Participación de personal administrativo</th>
                            <td class="sub-header" colspan="1">TOTAL</td>
                            <td class="sub-header" colspan="6">Desglose del tipo de participación de personal administrativo:</td>
                        </tr>
                        <tr>
                            <td class="sub-header" colspan="1">Hombres</td>
                            <td class="sub-header" colspan="2" rowspan="2">Administrativo</td>
                            <td class="sub-header" colspan="2" rowspan="2">Servicios</td>
                            <td class="sub-header" colspan="2" rowspan="2">Asistentes técnicos laboratorios / Instructores</td>
                        </tr>
                        <tr>
                            <td class="full-width" colspan="1">
                                <input disabled type="text" class="input-field" value="{{ $proyecto->administrativos_hombres }}" disabled>
                            </td>
                        </tr>
                        <tr>
                            <td class="sub-header" colspan="1">Mujeres</td>
                            <td class="sub-header" colspan="1">Hombres</td>
                            <td class="sub-header" colspan="1">Mujeres</td>
                            <td class="sub-header" colspan="1">Hombres</td>
                            <td class="sub-header" colspan="1">Mujeres</td>
                            <td class="sub-header" colspan="1">Hombres</td>
                            <td class="sub-header" colspan="1">Mujeres</td>
                        </tr>
                        <tr>
                            <td class="full-width" colspan="1">
                                <input disabled type="text" class="input-field" value="{{ $proyecto->administrativas_mujeres }}" disabled>
                            </td>
                            <td class="full-width" colspan="1">
                                <input disabled type="text" class="input-field" value="{{ $proyecto->getAdministrativosPorTipo('Administrativo', 'Masculino') }}" disabled>
                            </td>
                            <td class="full-width" colspan="1">
                                <input disabled type="text" class="input-field" value="{{ $proyecto->getAdministrativosPorTipo('Administrativo', 'Femenino') }}" disabled>
                            </td>
                            <td class="full-width" colspan="1">
                                <input disabled type="text" class="input-field" value="{{ $proyecto->getAdministrativosPorTipo('Servicios', 'Masculino') }}" disabled>
                            </td>
                            <td class="full-width" colspan="1">
                                <input disabled type="text" class="input-field" value="{{ $proyecto->getAdministrativosPorTipo('Servicios', 'Femenino') }}" disabled>
                            </td>
                            <td class="full-width" colspan="1">
                                <input disabled type="text" class="input-field" value="{{ $proyecto->getAdministrativosPorTipo('Asistentes técnicos laboratorios / Instructores', 'Masculino') }}" disabled>
                            </td>
                            <td class="full-width" colspan="1">
                                <input disabled type="text" class="input-field" value="{{ $proyecto->getAdministrativosPorTipo('Asistentes técnicos laboratorios / Instructores', 'Femenino') }}" disabled>
                            </td>
                        </tr>
                    </table>
                </div>
                <!-- TABLA DE ENTIDAD CONTRAPARTE -->
                <div class="section4">
                    <div class="section-title">IV. ENTIDAD CONTRAPARTE</div>
                    <table class="table_datos2">
                        <tr>
                            <th class="header" colspan="7">En caso de que la contraparte sea nacional (añadir una
                                tabla
                                de información por cada una de las contrapartes)</th>
                        </tr>
                        @forelse ($proyecto->entidad_contraparte as $entidad)
                            <tr>
                                <td class="sub-header">Nombre de la contraparte:</td>
                                <td class="full-width" colspan="6">
                                    <input disabled type="text" class="input-field"
                                        placeholder="Ingrese el nombre de la entidad" value="{{ $entidad->nombre }}"
                                        disabled>
                                </td>
                            </tr>
                            <tr>
                                <td class="sub-header" rowspan="1">Tipo de contraparte:</td>
                                <td class="sub-header1" colspan="1">Gobierno Nacional <br>
                                    <input disabled type="checkbox" class="No"
                                        @if ($entidad->tipo_entidad == 'gobierno_nacional') checked @endif>
                                </td>
                                <td class="sub-header1" colspan="1">Gobierno Municipal<br>
                                    <input disabled type="checkbox" class="No"
                                        @if ($entidad->tipo_entidad == 'gobierno_municipal') checked @endif>
                                </td>
                                <td class="sub-header1" colspan="1">ONG<br>
                                    <input disabled type="checkbox" class="No"
                                        @if ($entidad->tipo_entidad == 'ong') checked @endif>
                                </td>
                                <td class="sub-header1" colspan="1">Sociedad Civil Organizada<br>
                                    <input disabled type="checkbox" class="No"
                                        @if ($entidad->tipo_entidad == 'sociedad_civil') checked @endif>
                                </td>
                                <td class="sub-header1" colspan="1">Sector Privado<br>
                                    <input disabled type="checkbox" class="No"
                                        @if ($entidad->tipo_entidad == 'sector_privado') checked @endif>
                                </td>
                                <td class="sub-header1" colspan="1">Internacional<br>
                                    <input disabled type="checkbox" class="No"
                                        @if ($entidad->tipo_entidad == 'internacional') checked @endif>
                                </td>
                            </tr>
                            <tr>
                                <td class="sub-header" rowspan="1">Nombre del contacto directo</td>
                                <td class="full-width" colspan="3">
                                    <input disabled type="text" class="input-field"
                                        placeholder="Ingrese el nombre del contacto"
                                        value="{{ $entidad->nombre_contacto }}" disabled>
                                </td>
                                <td class="sub-header" colspan="1">Correo Electrónico</td>
                                <td class="full-width" colspan="2">
                                    <input disabled type="text" class="input-field"
                                        placeholder="Ingrese el correo electrónico" value="{{ $entidad->correo }}"
                                        disabled>
                                </td>
                            </tr>
                            <tr>
                                <td class="sub-header" colspan="1">Cargo del contacto del proyecto</td>
                                <td class="full-width" colspan="3">
                                    <input disabled type="text" class="input-field"
                                        placeholder="Ingrese el cargo del contacto" value="{{ $entidad->cargo_contacto ?? '' }}"
                                        disabled>
                                </td>
                                <td class="sub-header" colspan="1">Teléfono</td>
                                <td class="full-width" colspan="2">
                                    <input disabled type="text" class="input-field"
                                        placeholder="Ingrese el teléfono"
                                        value="{{ $entidad->telefono }}" disabled>
                                </td>
                            </tr>
                            <tr>
                                <td class="sub-header" colspan="1">Tipo de instrumento que da lugar a la alianza</td>
                                <td class="sub-header1" colspan="2">Carta formal de solicitud a la unidad académica <br>
                                    <input disabled type="checkbox" class="No"
                                        @if ($entidad->instrumento_formalizacion->contains('tipo_documento', 'carta_formal_solicitud')) checked @endif>
                                </td>
                                <td class="sub-header1" colspan="2">Carta de intenciones con la UNAH<br>
                                    <input disabled type="checkbox" class="No"
                                        @if ($entidad->instrumento_formalizacion->contains('tipo_documento', 'carta_intenciones')) checked @endif>
                                </td>
                                <td class="sub-header1" colspan="2">Convenio marco con la UNAH<br>
                                    <input disabled type="checkbox" class="No"
                                        @if ($entidad->instrumento_formalizacion->contains('tipo_documento', 'convenio_marco')) checked @endif>
                                </td>
                            </tr>
                            <tr>
                                <td class="sub-header" colspan="1">Breve descripción de los compromisos asumidos por la contraparte</td>
                                <td class="full-width" colspan="6">
                                    <textarea disabled class="input-field" rows="3" placeholder="Describa los compromisos">{{ $entidad->descripcion_acuerdos ?? '' }}</textarea>
                                </td>
                            </tr>
                            <tr>
                                <td class="sub-header" colspan="7">Instrumentos de formalización de alianza (Si
                                    hubiese):</td>
                            </tr>
                            <tr>
                                @forelse ($entidad->instrumento_formalizacion as $instrumento)
                            <tr>
                                <td class="full-width
                                    " colspan="4">
                                    <input disabled type="text" class="input-field"
                                        placeholder="Tipo de documento" value="{{ $instrumento->tipo_documento_display }}"
                                        disabled>
                                </td>
                                <td class="full-width
                                    " colspan="3">
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
                                    " colspan="7">
                                <input disabled type="text" class="input-field"
                                    placeholder="Ingrese el departamento" value="No hay instrumentos de formalización"
                                    disabled>
                            </td>
                        @endforelse
                        </tr>
                    @empty
                        <tr>
                            <td class="full-width
                                " colspan="7">
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
                            <th class="header" colspan="19">1. Descripción del proyecto: (Explicar brevemente en qué consiste el proyecto, 
                                los antecedentes que dieron su origen y la importancia que tiene para los objetivos estratégicos de la UNAH) De 150 a 250 palabras</th>
                        </tr>
                        <tr>
                            <td class="full-width" colspan="19">
                                <textarea disabled id="resumen" name="resumen" cols="30" rows="6" class="input-field"
                                    placeholder="Ingrese el resumen">{{ old('resumen', $proyecto->resumen) }}</textarea>
                            </td>
                        </tr>

                        </tr>
                        <tr>
                            <th class="header" colspan="19">2. Descripción de las participantes del proyecto (Descripción breve de las unidades académicas participantes y su alineamiento con la estrategia de 
                                vinculación de la unidad. También se realizará una breve descripción de las contrapartes participantes, a qué se dedican y cómo se alinea el proyecto a los planes estratégicos)</th>
                        </tr>
                        <tr>
                            <td class="full-width" colspan="19">
                                <textarea disabled cols="30" rows="6" class="input-field"
                                    placeholder="Descripción de participantes">{{ $proyecto->descripcion_participantes ?? '' }}</textarea>
                            </td>
                        </tr>

                        <tr>
                            <th class="header" colspan="19">3. Definición del problema:  Breve descripción del problema que se desea resolver, indicando línea base que se tendrá en 
                                consideración para la definición de los resultados del proyecto</th>
                        </tr>
                        <tr>
                            <td class="full-width" colspan="19">
                                <textarea disabled cols="30" rows="6" class="input-field"
                                    placeholder="Definición del problema">{{ $proyecto->definicion_problema ?? '' }}</textarea>
                            </td>
                        </tr>
                        
                        <tr>
                            <td class="header" colspan="19">8. Impacto que se desea generar en el proyecto</th>
                        </tr>
                        <tr>
                            <td class="full-width" colspan="19">
                                <textarea disabled cols="30" rows="6" class="input-field" 
                                    placeholder="Impacto deseado">{{ $proyecto->impacto_deseado ?? '' }}</textarea>
                            </td>
                        </tr>
                        <tr>
                            <td class="header" colspan="19">9. Objetivos de Desarrollo Sostenible (ODS) a los que se contribuye: Indicar el o los 
                                ODS a los que pretende contribuir el proyecto y las metas correspondientes. Para esta descripción deberá basarse en el documento de ODS 
                                que puede consultar en el siguiente enlace: <a style="" href="https://www.un.org/sustainabledevelopment/es/objetivos-de-desarrollo-sostenible/">Objetivos y metas de desarrollo sostenible - Desarrollo Sostenible </a></th>
                        </tr>
                        <tr>
                            <td class="sub-header" colspan="3">Cantidad ODS:</td>
                             <td class="sub-header" colspan="3">Descripción de ODS (Nombre y número):</td>
                              <td class="sub-header" colspan="3">Metas a las que contribuye:</td>
                        </tr>
                        @forelse ($proyecto->ods as $ods)
                            <tr>
                                <td class="sub-header" colspan="3">ODS {{ $loop->iteration }}:</td>
                                <td class="full-width" colspan="3">
                                    <input disabled type="text" class="input-field" 
                                        value="{{ $ods->nombre }}" placeholder="ODS">
                                </td>
                                <td class="full-width" colspan="3">
                                    <textarea disabled class="input-field" rows="2" placeholder="Metas a las que contribuye">{{ $proyecto->metasContribuye->where('ods_id', $ods->id)->count() > 0 ? $proyecto->metasContribuye->where('ods_id', $ods->id)->map(function($meta) { return 'Meta ' . $meta->numero_meta . ': ' . $meta->descripcion; })->implode("\n") : 'Sin metas específicas registradas' }}</textarea>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td class="full-width" colspan="19">
                                    <textarea disabled cols="30" rows="6" class="input-field" 
                                        placeholder="No hay ODS registrados">No hay Objetivos de Desarrollo Sostenible registrados para este proyecto</textarea>
                                </td>
                            </tr>
                        @endforelse

                        <tr>
                            <td class="header" colspan="19">10. Alineamiento con lo esencial de la reforma de la UNAH (detalle brevemente cómo se alinean los ejes de lo esencial de la reforma en la ejecución de este proyecto)</th>
                        </tr>
                        <tr>
                            <td class="full-width" colspan="19">
                                <textarea disabled cols="30" rows="6" class="input-field" 
                                    placeholder="Alineamiento con la reforma">{{ $proyecto->alineamiento_reforma ?? 'No hay información específica registrada para este campo' }}</textarea>
                            </td>
                        </tr>
                        <tr>
                            <td class="header" colspan="19">11. Metodología</th>
                        </tr>
                        <tr>
                            <td class="full-width" colspan="19">
                                <textarea disabled cols="30" rows="6" class="input-field" 
                                    placeholder="Metodología">{{ $proyecto->metodologia ?? '' }}</textarea>
                            </td>
                        </tr>
                        <tr>
                            <td class="header" colspan="19">12. Bibliografía</th>
                        </tr>
                        <tr>
                            <td class="full-width" colspan="19">
                                <textarea disabled cols="30" rows="6" class="input-field" 
                                    placeholder="Bibliografía">{{ $proyecto->bibliografia ?? '' }}</textarea>
                            </td>
                        </tr>
                    </table>
                </div>

                <!-- SECCIÓN DE MARCO LOGICO DEL PROYECTO -->
                    <div class="section2">
                    <div class="section-title">I. RESUMEN MARCO LÓGICO DEL PROYECTO. </div>
                    <table class="table_datos3">
                        {{-- Fila del Objetivo General --}}
                        <tr>
                            <td class="header" colspan="4">Objetivo general:</td>
                            <td class="full-width" colspan="15">
                                <textarea disabled cols="30" rows="3" class="input-field">{{ $proyecto->objetivo_general ?? 'Sin objetivo general especificado' }}</textarea>
                            </td>
                        </tr>
                        {{-- Fila de headers --}}
                        <tr>
                            <td class="header" colspan="4">Objetivo específico</td>
                            <td class="header" colspan="5">Resultado</td>
                            <td class="header" colspan="5">Indicador de resultado</td>
                            <td class="header" colspan="5">Medio de verificación</td>
                        </tr>
                        {{-- Filas de datos --}}
                        @if($proyecto->objetivosEspecificos->count() > 0)
                            @foreach($proyecto->objetivosEspecificos as $indexObj => $objetivo)
                                @if($objetivo->resultados->count() > 0)
                                    @foreach($objetivo->resultados as $indexRes => $resultado)
                                        <tr>
                                            {{-- Objetivo específico solo en la primera fila de cada objetivo --}}
                                            @if($indexRes == 0)
                                                <td class="full-width" colspan="4" rowspan="{{ $objetivo->resultados->count() }}">
                                                    <textarea disabled cols="30" rows="{{ max(3, $objetivo->resultados->count() * 2) }}" class="input-field">{{ $objetivo->descripcion }}</textarea>
                                                </td>
                                            @endif
                                            
                                            {{-- Resultado, indicador y medio de verificación en cada fila --}}
                                            <td class="full-width" colspan="5">
                                                <textarea disabled cols="30" rows="3" class="input-field">{{ $resultado->nombre_resultado ?? 'Sin resultado especificado' }}</textarea>
                                            </td>
                                            <td class="full-width" colspan="5">
                                                <textarea disabled cols="30" rows="3" class="input-field">{{ $resultado->nombre_indicador ?? 'Sin indicador especificado' }}</textarea>
                                            </td>
                                            <td class="full-width" colspan="5">
                                                <textarea disabled cols="30" rows="3" class="input-field">{{ $resultado->nombre_medio_verificacion ?? 'Sin medio de verificación especificado' }}</textarea>
                                            </td>
                                        </tr>
                                    @endforeach
                                @else
                                    {{-- Objetivo sin resultados --}}
                                    <tr>
                                        <td class="full-width" colspan="4">
                                            <textarea disabled cols="30" rows="3" class="input-field">{{ $objetivo->descripcion }}</textarea>
                                        </td>
                                        <td class="full-width" colspan="5">
                                            <textarea disabled cols="30" rows="3" class="input-field">Sin resultados registrados</textarea>
                                        </td>
                                        <td class="full-width" colspan="5">
                                            <textarea disabled cols="30" rows="3" class="input-field">Sin indicadores registrados</textarea>
                                        </td>
                                        <td class="full-width" colspan="5">
                                            <textarea disabled cols="30" rows="3" class="input-field">Sin medios de verificación registrados</textarea>
                                        </td>
                                    </tr>
                                @endif
                            @endforeach
                        @else
                            {{-- Sin objetivos específicos --}}
                            <tr>
                                <td class="full-width" colspan="4">
                                    <textarea disabled cols="30" rows="4" class="input-field">Sin objetivos específicos registrados</textarea>
                                </td>
                                <td class="full-width" colspan="5">
                                    <textarea disabled cols="30" rows="3" class="input-field">Sin resultados registrados</textarea>
                                </td>
                                <td class="full-width" colspan="5">
                                    <textarea disabled cols="30" rows="3" class="input-field">Sin indicadores registrados</textarea>
                                </td>
                                <td class="full-width" colspan="5">
                                    <textarea disabled cols="30" rows="3" class="input-field">Sin medios de verificación registrados</textarea>
                                </td>
                            </tr>
                        @endif
                    </table>
                </div>

                <!-- SECCIÓN DE DETALLE DEL PRESUPUESTO -->
                <div class="section2">
                    <div class="section-title">II. DETALLE DEL PRESUPUESTO</div>
                    <table class="table_datos3">
                        <tr>
                            <td class="header" colspan="19">6.1. Aporte institucional (manifestado en lempiras)</td>
                        </tr>
                        <tr>
                            <td class="header" colspan="7">Concepto</td>
                            <td class="header" colspan="3">Unidad</td>
                            <td class="header" colspan="3">Cantidad</td>
                            <td class="header" colspan="3">Costo Unitario</td>
                            <td class="header" colspan="3">Costo Total</td>
                        </tr>
                        
                        @php
                            // Crear un array asociativo para fácil acceso a los conceptos
                            $conceptos = collect($proyecto->aporteInstitucional)->keyBy('concepto');
                        @endphp
                        
                        <!-- Horas de trabajo docentes -->
                        <tr>
                            <td class="sub-header" colspan="7">a) Horas de trabajo docentes</td>
                            <td class="sub-header" colspan="3">Hra/profesores</td>
                            <td class="full-width" colspan="3">
                                <input disabled type="text" class="input-field" 
                                    value="{{ $conceptos['horas_trabajo_docentes']?->cantidad ?? '' }}">
                            </td>
                            <td class="full-width" colspan="3">
                                <input disabled type="text" class="input-field" 
                                    value="{{ $conceptos['horas_trabajo_docentes']?->costo_unitario ?? '' }}">
                            </td>
                            <td class="full-width" colspan="3">
                                <input disabled type="text" class="input-field" 
                                    value="{{ $conceptos['horas_trabajo_docentes']?->costo_total ?? '' }}">
                            </td>
                        </tr>
                        
                        <!-- Horas de trabajo estudiantes -->
                        <tr>
                            <td class="sub-header" colspan="7">b) Horas de trabajo estudiantes</td>
                            <td class="sub-header" colspan="3">Hra/estudiantes</td>
                            <td class="full-width" colspan="3">
                                <input disabled type="text" class="input-field" 
                                    value="{{ $conceptos['horas_trabajo_estudiantes']?->cantidad ?? '' }}">
                            </td>
                            <td class="full-width" colspan="3">
                                <input disabled type="text" class="input-field" 
                                    value="{{ $conceptos['horas_trabajo_estudiantes']?->costo_unitario ?? '' }}">
                            </td>
                            <td class="full-width" colspan="3">
                                <input disabled type="text" class="input-field" 
                                    value="{{ $conceptos['horas_trabajo_estudiantes']?->costo_total ?? '' }}">
                            </td>
                        </tr>
                        
                        <!-- Gastos de movilización -->
                        <tr>
                            <td class="sub-header" colspan="7">c) Gastos de movilización</td>
                            <td class="sub-header" colspan="3">Global</td>
                            <td class="full-width" colspan="3">
                                <input disabled type="text" class="input-field" 
                                    value="{{ $conceptos['gastos_movilizacion']?->cantidad ?? '' }}">
                            </td>
                            <td class="full-width" colspan="3">
                                <input disabled type="text" class="input-field" 
                                    value="{{ $conceptos['gastos_movilizacion']?->costo_unitario ?? '' }}">
                            </td>
                            <td class="full-width" colspan="3">
                                <input disabled type="text" class="input-field" 
                                    value="{{ $conceptos['gastos_movilizacion']?->costo_total ?? '' }}">
                            </td>
                        </tr>
                        
                        <!-- Útiles y materiales de oficina -->
                        <tr>
                            <td class="sub-header" colspan="7">d) Útiles y materiales de oficina</td>
                            <td class="sub-header" colspan="3">Global</td>
                            <td class="full-width" colspan="3">
                                <input disabled type="text" class="input-field" 
                                    value="{{ $conceptos['utiles_materiales_oficina']?->cantidad ?? '' }}">
                            </td>
                            <td class="full-width" colspan="3">
                                <input disabled type="text" class="input-field" 
                                    value="{{ $conceptos['utiles_materiales_oficina']?->costo_unitario ?? '' }}">
                            </td>
                            <td class="full-width" colspan="3">
                                <input disabled type="text" class="input-field" 
                                    value="{{ $conceptos['utiles_materiales_oficina']?->costo_total ?? '' }}">
                            </td>
                        </tr>
                        
                        <!-- Gastos de impresión -->
                        <tr>
                            <td class="sub-header" colspan="7">e) Gastos de impresión</td>
                            <td class="sub-header" colspan="3">Global</td>
                            <td class="full-width" colspan="3">
                                <input disabled type="text" class="input-field" 
                                    value="{{ $conceptos['gastos_impresion']?->cantidad ?? '' }}">
                            </td>
                            <td class="full-width" colspan="3">
                                <input disabled type="text" class="input-field" 
                                    value="{{ $conceptos['gastos_impresion']?->costo_unitario ?? '' }}">
                            </td>
                            <td class="full-width" colspan="3">
                                <input disabled type="text" class="input-field" 
                                    value="{{ $conceptos['gastos_impresion']?->costo_total ?? '' }}">
                            </td>
                        </tr>
                        
                        <!-- Costos indirectos por infraestructura -->
                        <tr>
                            <td class="sub-header" colspan="7">f) Costos indirectos por infraestructura universidad (depreciación de equipo, calculado sobre la sumatoria de los conceptos a – e)</td>
                            <td class="sub-header" colspan="3">%</td>
                            <td class="full-width" colspan="3">
                                <input disabled type="text" class="input-field" 
                                    value="{{ $conceptos['costos_indirectos_infraestructura']?->cantidad ?? '' }}">
                            </td>
                            <td class="full-width" colspan="3">
                                <input disabled type="text" class="input-field" 
                                    value="{{ $conceptos['costos_indirectos_infraestructura']?->costo_unitario ?? '' }}">
                            </td>
                            <td class="full-width" colspan="3">
                                <input disabled type="text" class="input-field" 
                                    value="{{ $conceptos['costos_indirectos_infraestructura']?->costo_total ?? '' }}">
                            </td>
                        </tr>
                        
                        <!-- Costos indirectos por servicios públicos -->
                        <tr>
                            <td class="sub-header" colspan="7">g) Costos indirectos por servicios públicos (internet, electricidad, otros, calculado sobre la sumatoria de los conceptos a – e)</td>
                            <td class="sub-header" colspan="3">%</td>
                            <td class="full-width" colspan="3">
                                <input disabled type="text" class="input-field" 
                                    value="{{ $conceptos['costos_indirectos_servicios']?->cantidad ?? '' }}">
                            </td>
                            <td class="full-width" colspan="3">
                                <input disabled type="text" class="input-field" 
                                    value="{{ $conceptos['costos_indirectos_servicios']?->costo_unitario ?? '' }}">
                            </td>
                            <td class="full-width" colspan="3">
                                <input disabled type="text" class="input-field" 
                                    value="{{ $conceptos['costos_indirectos_servicios']?->costo_total ?? '' }}">
                            </td>
                        </tr>
                        
                        <!-- Fila de totales y aportes -->
                        <tr>
                            <td class="sub-headeri" colspan="16">Total aporte institucional</td>
                            <td class="full-width" colspan="3">
                                <input disabled type="text" class="input-field" 
                                    value="{{ number_format($proyecto->total_aporte_institucional ?? 0, 2, '.', ',') }}">
                            </td>
                        </tr>
                        <tr>
                            <td class="sub-headert" colspan="16">Aporte de la contraparte</td>
                            <td class="full-width" colspan="3">
                                <input disabled type="text" class="input-field" 
                                    value="{{ number_format($proyecto->presupuesto?->aporte_contraparte ?? 0, 2, '.', ',') }}">
                            </td>
                        </tr>
                        <tr>
                            <td class="sub-headert" colspan="16">Aporte fondos internacionales</td>
                            <td class="full-width" colspan="3">
                                <input disabled type="text" class="input-field" 
                                    value="{{ number_format($proyecto->presupuesto?->aporte_internacionales ?? 0, 2, '.', ',') }}">
                            </td>
                        </tr>
                        <tr>
                            <td class="sub-headert" colspan="16">Aporte de otras universidades</td>
                            <td class="full-width" colspan="3">
                                <input disabled type="text" class="input-field" 
                                    value="{{ number_format($proyecto->presupuesto?->aporte_otras_universidades ?? 0, 2, '.', ',') }}">
                            </td>
                        </tr>
                        <tr>
                            <td class="sub-headert" colspan="16">Aporte de los beneficiarios (comunidad)</td>
                            <td class="full-width" colspan="3">
                                <input disabled type="text" class="input-field" 
                                    value="{{ number_format($proyecto->presupuesto?->aporte_comunidad ?? 0, 2, '.', ',') }}">
                            </td>
                        </tr>
                        <tr>
                            <td class="sub-headert" colspan="16">Otros aportes</td>
                            <td class="full-width" colspan="3">
                                <input disabled type="text" class="input-field" 
                                    value="{{ number_format($proyecto->presupuesto?->otros_aportes ?? 0, 2, '.', ',') }}">
                            </td>
                        </tr>
                        <tr>
                            <td class="sub-headeri" colspan="16">Total del presupuesto del proyecto</td>
                            <td class="full-width" colspan="3">
                                <input disabled type="text" class="input-field" 
                                     value="{{ number_format(
                                        ($proyecto->presupuesto->aporte_internacionales ?? 0) +
                                        ($proyecto->total_aporte_institucional ?? 0) +
                                        ($proyecto->presupuesto->aporte_otras_universidades ?? 0) +
                                        ($proyecto->presupuesto->otros_aportes ?? 0) +
                                        ($proyecto->presupuesto->aporte_contraparte ?? 0) +
                                        ($proyecto->presupuesto->aporte_comunidad ?? 0), 2, '.', ','
                                    ) }}">
                            </td>
                        </tr>
                    </table>
                </div>

                <div class="section2">
                    <div class="section-title">III. CRONOGRAMA DE LAS ACTIVIDADES DEL PROYECTO</div>
                    <table class="table_datos3">
                        <!-- CRONOGRAMA DE ACTIVIDADES -->
                        <tr>
                            <th class="header" colspan="19">10. DDescripción de actividades del proyecto (Descripción de todas las actividades enmarcadas en el proyecto, las cuales pueden ser, 
                                entre otras, la negociación inicial, la organización de los equipos de trabajo, 
                                la planificación, el desarrollo de actividades de capacitación y fortalecimiento, 
                                presentación de informe intermedio o parciales, presentación del informe final, proceso de evaluación, proceso de sistematización,
                                publicación de artículo, otras acciones de divulgación) </th>
                        </tr>
                        <tr>
                            <td class="sub-header3" colspan="19"> Cronogramama de actividades</td>
                        </tr>
                        <tr>
                            <td class="sub-header3" colspan="4"> Actividades</td>
                            <td class="sub-header3" colspan="4"> Fecha de ejecución</td>
                            <td class="sub-header3" colspan="5"> Responsables</td>
                            <td class="sub-header3" colspan="5"> Accion</td>
                        </tr>
                        <tr>
                            @forelse ($proyecto->actividades as $actividad)
                        <tr>
                            <td class="s3" colspan="4"> {{ $actividad->descripcion }}</td>
                            <td class="s3" colspan="4"> {{ $actividad->fecha_inicio }} - {{ $actividad->fecha_finalizacion }}</td>
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
                                                    {{ $actividad->fecha_inicio }} - {{ $actividad->fecha_finalizacion }}</div>
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
                    </table>
                </div>  

                <!-- SECCIÓN DE FIRMAS -->
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
                                     <br>
                                    <p>
                                        {{ optional($proyecto->firma_coodinador_proyecto->first())->fecha_firma
                                            ? \Carbon\Carbon::parse(optional($proyecto->firma_coodinador_proyecto->first())->fecha_firma)->translatedFormat(
                                                'l d F Y h:i:s A',
                                            )
                                            : '' }}
                                    </p>
                            </td>
                            <td class="full-width" colspan="2" style="height: 200px; width: 200px;">
                                <img src="{{ Storage::url(optional(optional($proyecto->firma_proyecto_jefe()->first())->sello)->ruta_storage) }}"
                                    alt="" width="200px">
                                <img src="{{ Storage::url(optional(optional($proyecto->firma_proyecto_jefe()->first())->firma)->ruta_storage) }}"
                                    alt="" width="200px">
                                     <br>
                                    <p>
                                        {{ optional($proyecto->firma_proyecto_jefe->first())->fecha_firma
                                            ? \Carbon\Carbon::parse(optional($proyecto->firma_proyecto_jefe->first())->fecha_firma)->translatedFormat(
                                                'l d F Y h:i:s A',
                                            )
                                            : '' }}
                                    </p>
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
                                     <br>
                                    <p>
                                        {{ optional($proyecto->firma_proyecto_enlace->first())->fecha_firma
                                            ? \Carbon\Carbon::parse(optional($proyecto->firma_proyecto_enlace->first())->fecha_firma)->translatedFormat(
                                                'l d F Y h:i:s A',
                                            )
                                            : '' }}
                                    </p>
                            </td>
                            <td class="full-width" colspan="2" style="height: 200px; width: 200px;">
                                <img src="{{ Storage::url(optional(optional($proyecto->firma_proyecto_decano()->first())->sello)->ruta_storage) }}"
                                    alt="" width="200px">
                                <img src="{{ Storage::url(optional(optional($proyecto->firma_proyecto_decano()->first())->firma)->ruta_storage) }}"
                                    alt="" width="200px">
                                     <br>
                                    <p>
                                        {{ optional($proyecto->firma_proyecto_decano->first())->fecha_firma
                                            ? \Carbon\Carbon::parse(optional($proyecto->firma_proyecto_decano->first())->fecha_firma)->translatedFormat(
                                                'l d F Y h:i:s A',
                                            )
                                            : '' }}
                                    </p>
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

                <!-- SECCIÓN DE DOCUMENTOS ADJUNTOS A LA FICHA 
                <div class="section4">
                    <div class="section-title">V. DOCUMENTOS ADJUNTOS A LA FICHA. </div>
                    <table class="table_datos5">
                        <tr>
                            <th class="header" colspan="1">No</th>
                            <th class="header" colspan="10">Descripción</th>
                            <th class="header" colspan="4">Si</th>
                            <th class="header" colspan="4">No</th>
                        </tr>
                        <tr>
                            <td class="sub-header" colspan="1">1</td>
                            <td class="full-width" colspan="10">Carta de solicitud del proyecto firmada por el representante legal de la contraparte</td>
                            <td class="full-width" colspan="4">
                                <input disabled type="checkbox" class="checkbox-field">
                            </td>
                            <td class="full-width" colspan="4">
                                <input disabled type="checkbox" class="checkbox-field">
                            </td>
                        </tr>
                        <tr>
                            <td class="sub-header" colspan="1">2</td>
                            <td class="full-width" colspan="10">Convenio/ carta de intenciones firmada entre la UNAH y contraparte</td>
                            <td class="full-width" colspan="4">
                                <input disabled type="checkbox" class="checkbox-field">
                            </td>
                            <td class="full-width" colspan="4">
                                <input disabled type="checkbox" class="checkbox-field">
                            </td>
                        </tr>
                        <tr>
                            <td class="sub-header" colspan="1">3</td>
                            <td class="full-width" colspan="10">Oficio de remisión del Decano/Director Centro Regional</td>
                            <td class="full-width" colspan="4">
                                <input disabled type="checkbox" class="checkbox-field">
                            </td>
                            <td class="full-width" colspan="4">
                                <input disabled type="checkbox" class="checkbox-field">
                            </td>
                        </tr>
                        <tr>
                            <td class="sub-header" colspan="1">4</td>
                            <td class="full-width" colspan="10">Otros (detallar)</td>
                            <td class="full-width" colspan="4">
                                <input disabled type="checkbox" class="checkbox-field">
                            </td>
                            <td class="full-width" colspan="4">
                                <input disabled type="checkbox" class="checkbox-field">
                            </td>
                        </tr>
                    </table>
                    
                    <div style="margin-top: 15px;">
                        <p><strong>Nota:</strong></p>
                        <p>- El documento 1 / documento 2 (cualquiera de los dos) es obligatorio</p>
                        <p>- El documento 3 es obligatorio</p>
                    </div>
                </div> -->

                <!-- SECCIÓN DE ANEXOS Y DATOS DE REGISTRO -->
                <div class="section4">
                    <div class="section-title">VI. ANEXOS. </div>
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

                    <!-- DATOS DE REGISTRO 
                <div class="section-title" style="margin-top: 20px;">VII. DATOS DE REGISTRO. </div>
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
                </div> -->
            </div>
        </div>
    </x-filament::section>


</body>


</html>
