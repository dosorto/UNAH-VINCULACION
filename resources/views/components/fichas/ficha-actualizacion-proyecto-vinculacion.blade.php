<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=3, user-scalable=yes">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Document</title>
    <link rel="stylesheet" href="{{ asset('css/app/fichaActualizacion.css') }}">
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
            Ficha de actualización
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
                    <h1>FICHA DE ACTUALIZACIÓN DE PROYECTO DE VINCULACIÓN</h1>
                </div>

                <div class="section1">
                    <div class="section-title">I. DATOS GENERALES </div>
                    <table class="table_datos1">
                        <tr>
                            <th class="full-width1">1. Nombre del Proyecto:</th>
                            <td class="full-width" colspan="5">

                                <input disabled type="text" class="input-field"
                                    placeholder="Ingrese el nombre del proyecto"
                                    value="{{ $proyecto->nombre_proyecto }}" disabled>

                            </td>
                        </tr>

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
                    </table>
                </div>

                <div class="section2">
                    <div class="section-title">II. ACTUALIZACIÓN DE INTEGRANTES DEL EQUIPO. </div>
                    <table class="table_datos1">
                    <!-- TABLA DE INTEGRANTES DEL EQUIPO UNIVERSITARIO -->
                        <tr>
                            <th class="full-width1" colspan="6">Integrantes del equipo universitario que se unieron al proyecto, posterior a la fecha de registro (Agregue más líneas que sean necesarias)</th>
                        </tr>
                        <tr>
                            <th class="full-width2" colspan="6">Empleados</th>
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
                            <th class="full-width2" colspan="6">Estudiantes</th>
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
                            <th class="full-width2" colspan="6">Integrantes Internacionales</th>
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
                        <tr>
                            <th class="full-width2" colspan="2" rowspan="1">Fecha de incorporación del proyecto</th> 
                            <td class="full-width" colspan="3">
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
                        </tr> 
                    </table>

                </div>

                <div class="section2">
                    <table class="table_datos3">
                        <tr>
                            <th class="header" colspan="19">Motivos del cambio de integrantes de equipo (Describa las responsabilidades de los nuevos miembros)</th>
                        </tr>
                        <tr>
                            <td class="full-width" colspan="19">
                                <textarea disabled id="resumen" name="resumen" cols="30" rows="6" class="input-field"
                                    placeholder="Ingrese el resumen">{{ old('resumen', $proyecto->resumen) }}</textarea>
                            </td>
                        </tr>
                    </table>
                </div>

                <div class="section2">
                    <table class="table_datos1">
                    <!-- TABLA DE INTEGRANTES DEL EQUIPO UNIVERSITARIO -->
                        <tr>
                            <th class="full-width1" colspan="6">Escriba el nombre de los integrantes del equipo universitario que se dieron de baja, posterior a fecha de registro  (Agregue las líneas que sean necesarias)</th>
                        </tr>
                        <tr>
                            <th class="full-width2" colspan="6">Empleados</th>
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
                            <th class="full-width2" colspan="6">Estudiantes</th>
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
                            <th class="full-width2" colspan="6">Integrantes Internacionales</th>
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
                        <tr>
                            <th class="full-width2" colspan="2" rowspan="1">Fecha de retiro del proyecto</th> 
                            <td class="full-width" colspan="3">
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
                        </tr> 
                    </table>

                </div>

                <div class="section2">
                    <table class="table_datos3">
                        <tr>
                            <th class="header" colspan="19">Motivos del cambio de integrantes de equipo (Describa las razones por las cuales se cambia al equipo)</th>
                        </tr>
                        <tr>
                            <td class="full-width" colspan="19">
                                <textarea disabled id="resumen" name="resumen" cols="30" rows="6" class="input-field"
                                    placeholder="Ingrese el resumen">{{ old('resumen', $proyecto->resumen) }}</textarea>
                            </td>
                        </tr>
                    </table>
                </div>

                
                <div class="section2">
                    <div class="section-title">III.EXTENSIÓN DE TIEMPO DE EJECUCIÓN DEL PROYECTO. </div>
                    <h1 class="label-text">(En el caso de ser necesario, indique el tiempo de la extensión de la ejecución del proyecto)</h1>
                    <table class="table_datos1">
                        <tr>
                            <th class="full-width1" rowspan="1">6. Fechas de ejecución:</th>
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
                            <td class="sub-header" colspan="1">Fecha de Finalización propuesta inicialmente:</td> 
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


                        <tr>
                            <th class="full-width1" colspan="2" rowspan="1">Nueva fecha de finalización:</th> 
                            <td class="full-width" colspan="3">
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
                        </tr> 
                         <tr>
                            <th class="motivos" colspan="19">Motivos por los cuales se extiende la fecha de ejecución del proyecto (incluir las fechas de evaluación del proyecto)</th>
                        </tr>
                        <tr>
                            <td class="full-width" colspan="19">
                                <textarea disabled id="resumen" name="resumen" cols="30" rows="6" class="input-field"
                                    placeholder="Ingrese el resumen">{{ old('resumen', $proyecto->resumen) }}</textarea>
                            </td>
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
            </div>
        </div>
    </x-filament::section>


</body>


</html>
