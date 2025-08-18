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
                            <th class="full-width1" colspan="7">Integrantes actuales del equipo universitario (incluye originales y agregados posterior a la fecha de registro)</th>
                        </tr>
                        <tr>
                            <th class="full-width2" colspan="7">Empleados</th>
                        </tr>
                        <tr>
                            <td class="sub-header" colspan="2">Nombre Completo:</td>
                            <td class="sub-header">No. de empleado/a:</td>
                            <td class="sub-header">Correo electrónico:</td>
                            <td class="sub-header">Categoria:</td>
                            <td class="sub-header">Departamento:</td>
                        </tr>
                        @php
                            // Obtener bajas de ESTA ficha específica
                            $bajasEstaFicha = $fichaActualizacion->equipoEjecutorBajas
                                ->where('tipo_integrante', 'empleado')
                                ->pluck('integrante_id');
                                
                            // Obtener bajas que ocurrieron DESPUÉS de esta ficha (fichas posteriores)
                            $bajasPosteriores = \App\Models\Proyecto\EquipoEjecutorBaja::where('proyecto_id', $proyecto->id)
                                ->where('tipo_integrante', 'empleado')
                                ->whereHas('fichaActualizacion', function($query) use ($fichaActualizacion) {
                                    $query->where('created_at', '>', $fichaActualizacion->created_at);
                                })
                                ->where('estado_baja', 'aplicada')
                                ->pluck('integrante_id');
                                
                            // Los integrantes activos son: todos los actuales + los que se dieron de baja posteriormente 
                            // pero excluyendo los que se dieron de baja en esta ficha
                            $integrantesActivosEnEseMomento = $proyecto->integrantes->filter(function ($integrante) use ($bajasEstaFicha) {
                                return !$bajasEstaFicha->contains($integrante->id);
                            });
                            
                            // Agregar los que fueron dados de baja en fichas posteriores (estaban activos en esta ficha)
                            $integrantesDadosBajaPosteriormente = \App\Models\Personal\Empleado::whereIn('id', $bajasPosteriores)->get();
                            
                            // Obtener IDs de integrantes actuales para evitar duplicados
                            $idsIntegrantesActuales = $integrantesActivosEnEseMomento->pluck('id')->toArray();
                            
                            // Filtrar solo los que no están ya en la lista actual
                            $integrantesFaltantes = $integrantesDadosBajaPosteriormente->filter(function($empleado) use ($idsIntegrantesActuales) {
                                return !in_array($empleado->id, $idsIntegrantesActuales);
                            });
                            
                            // Combinar ambos grupos para obtener el estado completo de esta ficha
                            $todosLosIntegrantesEnEseMomento = $integrantesActivosEnEseMomento->merge($integrantesFaltantes);
                            
                            // Separar entre originales y agregados después
                            $integrantesOriginales = $todosLosIntegrantesEnEseMomento->filter(function ($integrante) use ($proyecto) {
                                $fechaRegistro = $proyecto->fecha_registro;
                                $fechaIncorporacion = $integrante->pivot->created_at ?? null;
                                
                                if (!$fechaRegistro || !$fechaIncorporacion) {
                                    return true; // Si no hay fecha, asumimos que es original
                                }
                                
                                return $fechaIncorporacion->lte($fechaRegistro);
                            });
                            
                            $integrantesPosteriores = $todosLosIntegrantesEnEseMomento->filter(function ($integrante) use ($proyecto) {
                                $fechaRegistro = $proyecto->fecha_registro;
                                $fechaIncorporacion = isset($integrante->pivot) ? $integrante->pivot->created_at : null;
                                
                                if (!$fechaRegistro || !$fechaIncorporacion) {
                                    return false;
                                }
                                
                                return $fechaIncorporacion->gt($fechaRegistro);
                            });
                        @endphp

                        @forelse ($todosLosIntegrantesEnEseMomento as $integrante)
                            @php
                                $esNuevo = $integrantesPosteriores->contains('id', $integrante->id);
                                $fechaIncorporacion = isset($integrante->pivot) ? $integrante->pivot->created_at : null;
                            @endphp
                            <tr style="{{ $esNuevo ? 'background-color: #e8f5e8;' : '' }}">
                                <td class="full-width" colspan="2">
                                    <input disabled type="text" class="input-field"
                                        placeholder="Ingrese el nombre completo"
                                        value="{{ $integrante->nombre_completo }}{{ $esNuevo ? ' (NUEVO)' : '' }}" disabled>
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
                                <td class="full-width" colspan="7">
                                    <input disabled type="text" class="input-field"
                                        placeholder="Ingrese el departamento" value="No hay integrantes activos en el equipo" disabled>
                                </td>
                            </tr>
                        @endforelse
                        <tr>
                            <th class="full-width2" colspan="7">Estudiantes</th>
                        </tr>
                        <tr>
                            <td class="sub-header" colspan="3">Nombre Completo:</td>
                            <td class="sub-header">No. de cuenta</td>
                            <td class="sub-header">Correo electrónico</td>
                            <td class="sub-header">Tipo participacion:</td>
                        </tr>
                        @php
                            // Obtener bajas de estudiantes de ESTA ficha específica
                            $estudiantesBajaEstaFicha = $fichaActualizacion->equipoEjecutorBajas
                                ->where('tipo_integrante', 'estudiante')
                                ->pluck('integrante_id');
                                
                            // Obtener bajas de estudiantes que ocurrieron DESPUÉS de esta ficha
                            $estudiantesBajasPosteriores = \App\Models\Proyecto\EquipoEjecutorBaja::where('proyecto_id', $proyecto->id)
                                ->where('tipo_integrante', 'estudiante')
                                ->whereHas('fichaActualizacion', function($query) use ($fichaActualizacion) {
                                    $query->where('created_at', '>', $fichaActualizacion->created_at);
                                })
                                ->where('estado_baja', 'aplicada')
                                ->pluck('integrante_id');
                                
                            // Estudiantes activos en el momento de esta ficha
                            $estudiantesActivosEnEseMomento = $proyecto->estudiante_proyecto->filter(function ($integrante) use ($estudiantesBajaEstaFicha) {
                                return !$estudiantesBajaEstaFicha->contains($integrante->estudiante_id);
                            });
                            
                            // Agregar estudiantes que fueron dados de baja posteriormente
                            $estudiantesDadosBajaPosteriormente = \App\Models\Estudiante\Estudiante::whereIn('id', $estudiantesBajasPosteriores)->get();
                            
                            // Obtener IDs de estudiantes actuales para evitar duplicados
                            $idsEstudiantesActuales = $estudiantesActivosEnEseMomento->pluck('estudiante_id')->toArray();
                            
                            // Crear relaciones pivot simuladas para estudiantes faltantes
                            $estudiantesFaltantes = $estudiantesDadosBajaPosteriormente->filter(function($estudiante) use ($idsEstudiantesActuales) {
                                return !in_array($estudiante->id, $idsEstudiantesActuales);
                            })->map(function($estudiante) {
                                return (object)[
                                    'estudiante_id' => $estudiante->id,
                                    'estudiante' => $estudiante,
                                    'created_at' => null,
                                    'tipo_participacion_estudiante' => 'Participante' // Valor por defecto
                                ];
                            });
                            
                            // Combinar para obtener el estado completo en el momento de esta ficha
                            $todosLosEstudiantesEnEseMomento = $estudiantesActivosEnEseMomento->merge($estudiantesFaltantes);
                            
                            // Separar entre originales y agregados después
                            $estudiantesPosteriores = $todosLosEstudiantesEnEseMomento->filter(function ($integrante) use ($proyecto) {
                                $fechaRegistro = $proyecto->fecha_registro;
                                $fechaIncorporacion = $integrante->created_at ?? null;
                                
                                if (!$fechaRegistro || !$fechaIncorporacion) {
                                    return false;
                                }
                                
                                return $fechaIncorporacion->gt($fechaRegistro);
                            });
                        @endphp

                        @forelse ($todosLosEstudiantesEnEseMomento as $integrante)
                            @php
                                $esNuevo = $estudiantesPosteriores->contains('estudiante_id', $integrante->estudiante_id ?? $integrante->id);
                                $fechaIncorporacion = $integrante->created_at ?? null;
                                $estudiante = $integrante->estudiante ?? $integrante;
                            @endphp
                            <tr style="{{ $esNuevo ? 'background-color: #e8f5e8;' : '' }}">
                                <td class="full-width" colspan="3">
                                    <input disabled type="text" class="input-field"
                                        placeholder="Ingrese el departamento"
                                        value="{{ $estudiante->user->name }}{{ $esNuevo ? ' (NUEVO)' : '' }}" disabled>
                                </td>

                                <td class="full-width" colspan="1">
                                    <input disabled type="text" class="input-field"
                                        placeholder="Ingrese el departamento"
                                        value="{{ $estudiante->cuenta }}" disabled>
                                </td>
                                <td class="full-width" colspan="1">
                                    <input disabled type="text" class="input-field"
                                        placeholder="Ingrese el departamento"
                                        value="{{ $estudiante->user->email }}" disabled>
                                </td>
                                <td class="full-width" colspan="1">
                                    <input disabled type="text" class="input-field"
                                        placeholder="Ingrese el departamento"
                                        value="{{ $integrante->tipo_participacion_estudiante ?? 'Participante' }}" disabled>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td class="full-width" colspan="7">
                                    <input disabled type="text" class="input-field"
                                        placeholder="Ingrese el departamento" value="No hay estudiantes activos en el equipo" disabled>
                                </td>
                            </tr>
                        @endforelse
                        <tr>
                            <th class="full-width2" colspan="7">Integrantes Internacionales</th>
                        </tr>
                        <tr>
                            <td class="sub-header" colspan="2">Nombre Completo:</td>
                            <td class="sub-header">Pasaporte:</td>
                            <td class="sub-header">Correo electrónico:</td>
                            <td class="sub-header">País:</td>
                            <td class="sub-header">Universidad/Institucion:</td>
                        </tr>
                        @php
                            // Obtener bajas de integrantes internacionales de ESTA ficha específica
                            $integrantesInternacionalesBajaEstaFicha = $fichaActualizacion->equipoEjecutorBajas
                                ->where('tipo_integrante', 'integrante_internacional')
                                ->pluck('integrante_id');
                                
                            // Obtener bajas que ocurrieron DESPUÉS de esta ficha
                            $integrantesInternacionalesBajasPosteriores = \App\Models\Proyecto\EquipoEjecutorBaja::where('proyecto_id', $proyecto->id)
                                ->where('tipo_integrante', 'integrante_internacional')
                                ->whereHas('fichaActualizacion', function($query) use ($fichaActualizacion) {
                                    $query->where('created_at', '>', $fichaActualizacion->created_at);
                                })
                                ->where('estado_baja', 'aplicada')
                                ->pluck('integrante_id');
                                
                            // Integrantes internacionales activos en el momento de esta ficha
                            $integrantesInternacionalesActivosEnEseMomento = $proyecto->integrantesInternacionales->filter(function ($integrante) use ($integrantesInternacionalesBajaEstaFicha) {
                                return !$integrantesInternacionalesBajaEstaFicha->contains($integrante->id);
                            });
                            
                            // Agregar los que fueron dados de baja posteriormente
                            $integrantesInternacionalesDadosBajaPosteriormente = \App\Models\Proyecto\IntegranteInternacional::whereIn('id', $integrantesInternacionalesBajasPosteriores)->get();
                            
                            // Obtener IDs actuales para evitar duplicados
                            $idsIntegrantesInternacionalesActuales = $integrantesInternacionalesActivosEnEseMomento->pluck('id')->toArray();
                            
                            // Filtrar solo los que no están ya en la lista actual
                            $integrantesInternacionalesFaltantes = $integrantesInternacionalesDadosBajaPosteriormente->filter(function($integrante) use ($idsIntegrantesInternacionalesActuales) {
                                return !in_array($integrante->id, $idsIntegrantesInternacionalesActuales);
                            });
                            
                            // Combinar para obtener el estado completo
                            $todosLosIntegrantesInternacionalesEnEseMomento = $integrantesInternacionalesActivosEnEseMomento->merge($integrantesInternacionalesFaltantes);
                            
                            // Separar entre originales y agregados después
                            $integrantesInternacionalesPosteriores = $todosLosIntegrantesInternacionalesEnEseMomento->filter(function ($integrante) use ($proyecto) {
                                $fechaRegistro = $proyecto->fecha_registro;
                                $fechaIncorporacion = isset($integrante->pivot) ? $integrante->pivot->created_at : null;
                                
                                if (!$fechaRegistro || !$fechaIncorporacion) {
                                    return false;
                                }
                                
                                return $fechaIncorporacion->gt($fechaRegistro);
                            });
                        @endphp

                        @forelse ($todosLosIntegrantesInternacionalesEnEseMomento as $integrante)
                            @php
                                $esNuevo = $integrantesInternacionalesPosteriores->contains('id', $integrante->id);
                                $fechaIncorporacion = isset($integrante->pivot) ? $integrante->pivot->created_at : null;
                            @endphp
                            <tr style="{{ $esNuevo ? 'background-color: #e8f5e8;' : '' }}">
                                <td class="full-width" colspan="2">
                                    <input disabled type="text" class="input-field"
                                        placeholder="Ingrese el nombre completo"
                                        value="{{ $integrante->nombre_completo }}{{ $esNuevo ? ' (NUEVO)' : '' }}" disabled>
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
                                <td class="full-width" colspan="7">
                                    <input disabled type="text" class="input-field"
                                        placeholder="Aqui van los integrantes internacionales" value="No hay integrantes internacionales activos en el equipo" disabled>
                                </td>
                            </tr>
                        @endforelse

                        <tr>
                            <th class="full-width2" colspan="3" rowspan="1">Fecha de incorporación del proyecto</th> 
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
                                    placeholder="Ingrese el resumen">{{ $fichaActualizacion->motivo_responsabilidades_nuevos ?? '' }}</textarea>
                            </td>
                        </tr>
                    </table>
                </div>

                <div class="section2">
                    <table class="table_datos1">
                    <!-- TABLA DE INTEGRANTES DADOS DE BAJA -->
                        <tr>
                            <th class="full-width1" colspan="6">Integrantes del equipo que se dieron de baja posterior a la fecha de registro</th>
                        </tr>
                        
                        <!-- EMPLEADOS DADOS DE BAJA -->
                        @php
                            // SOLO las bajas de empleados asociadas a ESTA ficha específica
                            $empleadosBaja = $fichaActualizacion->equipoEjecutorBajas->where('tipo_integrante', 'empleado');
                        @endphp
                        
                        <tr>
                            <th class="full-width2" colspan="6">Empleados</th>
                        </tr>
                        <tr>
                            <td class="sub-header">Nombre Completo:</td>
                            <td class="sub-header">No. de empleado/a:</td>
                            <td class="sub-header">Correo electrónico:</td>
                            <td class="sub-header">Categoria:</td>
                            <td class="sub-header">Motivo de Baja:</td>
                        </tr>

                        @forelse($empleadosBaja as $integranteBaja)
                        <tr style="background-color: #ffe6e6;">
                            <td class="full-width" colspan="1">
                                <input disabled type="text" class="input-field"
                                    value="{{ $integranteBaja->empleado?->nombre_completo ?? 'N/A' }}" disabled>
                            </td>
                            <td class="full-width" colspan="1">
                                <input disabled type="text" class="input-field"
                                    value="{{ $integranteBaja->empleado?->numero_empleado ?? 'N/A' }}" disabled>
                            </td>
                            <td class="full-width" colspan="1">
                                <input disabled type="text" class="input-field"
                                    value="{{ $integranteBaja->empleado?->user?->email ?? 'N/A' }}" disabled>
                            </td>
                            <td class="full-width" colspan="1">
                                <input disabled type="text" class="input-field"
                                    value="{{ $integranteBaja->empleado?->categoria?->nombre ?? 'N/A' }}" disabled>
                            </td>
                            <td class="full-width" colspan="1">
                                <input disabled type="text" class="input-field"
                                    value="{{ $integranteBaja->motivo_baja ?? 'N/A' }}" disabled>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td class="full-width" colspan="6">
                                <input disabled type="text" class="input-field"
                                    placeholder="No hay empleados dados de baja" value="No hay empleados dados de baja" disabled>
                            </td>
                        </tr>
                        @endforelse

                        <!-- ESTUDIANTES DADOS DE BAJA -->
                        @php
                            // SOLO las bajas de estudiantes asociadas a ESTA ficha específica
                            $estudiantesBaja = $fichaActualizacion->equipoEjecutorBajas->where('tipo_integrante', 'estudiante');
                        @endphp

                        <tr>
                            <th class="full-width2" colspan="6">Estudiantes</th>
                        </tr>
                        <tr>
                            <td class="sub-header">Nombre Completo:</td>
                            <td class="sub-header">No. de cuenta</td>
                            <td class="sub-header">Correo electrónico</td>
                            <td class="sub-header">Tipo participacion:</td>
                            <td class="sub-header">Motivo de Baja:</td>
                        </tr>

                        @forelse($estudiantesBaja as $integranteBaja)
                        <tr style="background-color: #ffe6e6;">
                            <td class="full-width" colspan="1">
                                <input disabled type="text" class="input-field"
                                    value="{{ $integranteBaja->estudiante?->user?->name ?? 'N/A' }}" disabled>
                            </td>
                            <td class="full-width" colspan="1">
                                <input disabled type="text" class="input-field"
                                    value="{{ $integranteBaja->estudiante?->cuenta ?? 'N/A' }}" disabled>
                            </td>
                            <td class="full-width" colspan="1">
                                <input disabled type="text" class="input-field"
                                    value="{{ $integranteBaja->estudiante?->user?->email ?? 'N/A' }}" disabled>
                            </td>
                            <td class="full-width" colspan="1">
                                <input disabled type="text" class="input-field"
                                    value="{{ $integranteBaja->rol_anterior ?? 'N/A' }}" disabled>
                            </td>
                            <td class="full-width" colspan="1">
                                <input disabled type="text" class="input-field"
                                    value="{{ $integranteBaja->motivo_baja ?? 'N/A' }}" disabled>
                            </td> 
                        </tr>
                        @empty
                        <tr>
                            <td class="full-width" colspan="6">
                                <input disabled type="text" class="input-field"
                                    placeholder="No hay estudiantes dados de baja" value="No hay estudiantes dados de baja" disabled>
                            </td>
                        </tr>
                        @endforelse

                        <!-- INTEGRANTES INTERNACIONALES DADOS DE BAJA -->
                        @php
                            // SOLO las bajas de integrantes internacionales asociadas a ESTA ficha específica
                            $internacionalesBaja = $fichaActualizacion->equipoEjecutorBajas->where('tipo_integrante', 'integrante_internacional');
                        @endphp

                        <tr>
                            <th class="full-width2" colspan="6">Integrantes Internacionales</th>
                        </tr>
                        <tr>
                            <td class="sub-header">Nombre Completo:</td>
                            <td class="sub-header">Pasaporte:</td>
                            <td class="sub-header">Correo electrónico:</td>
                            <td class="sub-header">País:</td>
                            <td class="sub-header">Motivo de Baja:</td>
                        </tr>

                        @forelse($internacionalesBaja as $integranteBaja)
                        <tr style="background-color: #ffe6e6;">
                            <td class="full-width" colspan="1">
                                <input disabled type="text" class="input-field"
                                    value="{{ $integranteBaja->integranteInternacional?->nombre_completo ?? 'N/A' }}" disabled>
                            </td>
                            <td class="full-width" colspan="1">
                                <input disabled type="text" class="input-field"
                                    value="{{ $integranteBaja->integranteInternacional?->documento_identidad ?? 'N/A' }}" disabled>
                            </td>
                            <td class="full-width" colspan="1">
                                <input disabled type="text" class="input-field"
                                    value="{{ $integranteBaja->integranteInternacional?->email ?? 'N/A' }}" disabled>
                            </td>
                            <td class="full-width" colspan="1">
                                <input disabled type="text" class="input-field"
                                    value="{{ $integranteBaja->integranteInternacional?->pais ?? 'N/A' }}" disabled>
                            </td>
                            <td class="full-width" colspan="1">
                                <input disabled type="text" class="input-field"
                                    value="{{ $integranteBaja->motivo_baja ?? 'N/A' }}" disabled>
                            </td>
                          
                        </tr>
                        @empty
                        <tr>
                            <td class="full-width" colspan="6">
                                <input disabled type="text" class="input-field"
                                    placeholder="No hay integrantes internacionales dados de baja" value="No hay integrantes internacionales dados de baja" disabled>
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
                                    placeholder="Ingrese el resumen">{{ $fichaActualizacion->motivo_razones_cambio ?? '' }}</textarea>
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
                                        <input disabled type="text" class="input-field" value="{{ $fichaActualizacion->fecha_ampliacion ? \Carbon\Carbon::parse($fichaActualizacion->fecha_ampliacion)->format('d') : '' }}">
                                    </div>
                                    <div class="date-part">
                                        <span class="date-label">Mes</span>
                                        <input disabled type="text" class="input-field" value="{{ $fichaActualizacion->fecha_ampliacion ? \Carbon\Carbon::parse($fichaActualizacion->fecha_ampliacion)->format('m') : '' }}">
                                    </div>
                                    <div class="date-part">
                                        <span class="date-label">Año</span>
                                        <input disabled type="text" class="input-field" value="{{ $fichaActualizacion->fecha_ampliacion ? \Carbon\Carbon::parse($fichaActualizacion->fecha_ampliacion)->format('Y') : '' }}">
                                    </div>
                                </div>
                            </td>
                        </tr> 
                         <tr>
                            <th class="motivos" colspan="19">Motivos por los cuales se extiende la fecha de ejecución del proyecto (incluir las fechas de evaluación del proyecto)</th>
                        </tr>
                        <tr>
                            <td class="full-width" colspan="19">
                                <textarea disabled id="motivo_ampliacion" name="motivo_ampliacion" cols="30" rows="6" class="input-field"
                                    placeholder="Motivos por los cuales se extiende la fecha de ejecución del proyecto">{{ $fichaActualizacion->motivo_ampliacion ?? '' }}</textarea>
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

                    <!-- SECCIÓN DE ESTADO DE LA FICHA DE ACTUALIZACIÓN -->
                    <div class="section-title">ESTADO ACTUAL DE LA FICHA DE ACTUALIZACIÓN</div>
                    <table class="table_datos1">
                        <tr>
                            <th class="full-width1">Estado Actual:</th>
                            <td class="full-width" colspan="2">
                                <input disabled type="text" class="input-field"
                                    value="{{ $fichaActualizacion->obtenerUltimoEstado() ? $fichaActualizacion->obtenerUltimoEstado()->tipoestado->nombre : 'Sin estado definido' }}" 
                                    style="background-color: {{ $fichaActualizacion->obtenerUltimoEstado() && $fichaActualizacion->obtenerUltimoEstado()->tipoestado->nombre == 'Aprobado' ? '#d4edda' : ($fichaActualizacion->obtenerUltimoEstado() && $fichaActualizacion->obtenerUltimoEstado()->tipoestado->nombre == 'Rechazado' ? '#f8d7da' : '#fff3cd') }}" 
                                    disabled>
                            </td>
                            <th class="full-width1">Fecha del Estado:</th>
                            <td class="full-width" colspan="2">
                                <input disabled type="text" class="input-field"
                                    value="{{ $fichaActualizacion->obtenerUltimoEstado() ? $fichaActualizacion->obtenerUltimoEstado()->created_at->format('d/m/Y H:i') : 'N/A' }}" disabled>
                            </td>
                        </tr>
                        @if($fichaActualizacion->obtenerUltimoEstado() && $fichaActualizacion->obtenerUltimoEstado()->comentario)
                        <tr>
                            <th class="full-width1">Comentarios del Estado:</th>
                            <td class="full-width" colspan="5">
                                <textarea disabled class="input-field" rows="3">{{ $fichaActualizacion->obtenerUltimoEstado()->comentario }}</textarea>
                            </td>
                        </tr>
                        @endif
                        <tr>
                            <th class="full-width1">Progreso de Firmas:</th>
                            <td class="full-width" colspan="5">
                                @php
                                    $firmasTotal = $fichaActualizacion->firma_proyecto()->count();
                                    $firmasAprobadas = $fichaActualizacion->firma_proyecto()->where('estado_revision', 'Aprobado')->count();
                                    $porcentaje = $firmasTotal > 0 ? round(($firmasAprobadas / $firmasTotal) * 100) : 0;
                                @endphp
                                <input disabled type="text" class="input-field"
                                    value="{{ $firmasAprobadas }}/{{ $firmasTotal }} firmas aprobadas ({{ $porcentaje }}%)" 
                                    style="background-color: {{ $porcentaje == 100 ? '#d4edda' : ($porcentaje > 50 ? '#fff3cd' : '#f8d7da') }}"
                                    disabled>
                            </td>
                        </tr>
                    </table>
            </div>
        </div>
    </x-filament::section>


</body>


</html>
