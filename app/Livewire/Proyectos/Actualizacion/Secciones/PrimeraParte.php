<?php

namespace App\Livewire\Proyectos\Actualizacion\Secciones;

use App\Livewire\Proyectos\Vinculacion\Formularios\FormularioDocente;
use App\Livewire\Proyectos\Vinculacion\Formularios\FormularioEstudiante;
use App\Livewire\Proyectos\Vinculacion\Formularios\FormularioIntegranteInternacional;
use App\Models\Proyecto\EquipoEjecutorBaja;
use App\Models\Proyecto\EquipoEjecutorNuevo;

use Filament\Forms;
use App\Models\User;
use Filament\Forms\Get;

use App\Models\Personal\Empleado;
use App\Models\Proyecto\IntegranteInternacional;

use App\Models\Estudiante\Estudiante;
use App\Models\UnidadAcademica\FacultadCentro;
use Faker\Provider\ar_EG\Text;
use Filament\Forms\Components\Hidden;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Fieldset;
use Filament\Forms\Components\DatePicker;

use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\TextInput;
use Illuminate\Database\Eloquent\Builder;
use Filament\Forms\Set;
use Filament\Forms\Components\Actions\Action;
use Filament\Forms\Components\Actions;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Hash;

class PrimeraParte
{
    public static function form(): array
    {
        return [
            Repeater::make('coordinador_proyecto')
                ->label('Coordinador')
                ->schema([
                    Select::make('empleado_id')
                    ->label('')
                    ->required()
                    ->searchable(['nombre_completo', 'numero_empleado'])
                    ->relationship(name: 'empleado', titleAttribute: 'nombre_completo')
                    ->default(fn() => optional(Empleado::where('user_id', auth()->id())->first())->id)
                        ->disabled(),
                    Hidden::make('rol')
                        ->default('Coordinador'),
                    Hidden::make('empleado_id')
                ])
                ->columnSpanFull()
                ->relationship()
                ->minItems(1)
                ->maxItems(1)
                ->deletable(false)
                ->defaultItems(1),
                
            Repeater::make('empleado_proyecto')
                ->label('Integrantes ')
                ->schema([
                    Select::make('empleado_id')
                        ->label('Integrante')
                        ->distinct()
                        ->searchable(['nombre_completo', 'numero_empleado'])
                        ->relationship(
                            name: 'empleado',
                            titleAttribute: 'nombre_completo',
                            // QUITAR EL ID DEL USUARIO LOGUEADO DE LA LISTA DE EMPLEADOS
                            modifyQueryUsing: fn(Builder $query) =>  $query->where('user_id', '!=', auth()->id())
                        )
                        ->disabled()
                        ->required(),
                    TextInput::make('rol')
                        ->label('Rol')
                        ->disabled()
                        ->required()
                        ->default('Integrante'),
                    Hidden::make('rol')
                        ->default('Integrante'),
                    Actions::make([
                        Action::make('dar_baja')
                            ->label('Dar de Baja')
                            ->icon('heroicon-o-user-minus')
                            ->color('danger')
                            ->requiresConfirmation()
                            ->modalHeading('¿Dar de baja al integrante?')
                            ->modalDescription('Esta acción trasladará al integrante a la tabla de integrantes dados de baja.')
                            ->modalSubmitActionLabel('Confirmar Baja')
                            ->action(function (array $data, $component) {
                                $record = $component->getRecord();
                                
                                // Obtener el ID del empleado de manera más robusta
                                $empleadoId = null;
                                if (is_array($record)) {
                                    $empleadoId = $record['empleado_id'] ?? null;
                                } elseif (is_object($record)) {
                                    if (isset($record->empleado_id)) {
                                        $empleadoId = $record->empleado_id;
                                    } elseif (isset($record->empleado)) {
                                        $empleadoId = $record->empleado->id ?? null;
                                    }
                                }
                                
                                // Si aún es un objeto, obtener el ID
                                if (is_object($empleadoId) && isset($empleadoId->id)) {
                                    $empleadoId = $empleadoId->id;
                                }
                                
                                if (!$empleadoId) {
                                    Notification::make()
                                        ->title('Error')
                                        ->body('No se pudo obtener el ID del empleado.')
                                        ->danger()
                                        ->send();
                                    return;
                                }
                                
                                $livewire = $component->getLivewire();
                                
                                // Obtener el proyecto_id desde el record del formulario
                                $proyectoId = $livewire->record->id ?? $livewire->getRecord()->id;
                                
                                // Obtener información del empleado antes de eliminarlo
                                $empleadoProyecto = \App\Models\Personal\EmpleadoProyecto::where('proyecto_id', $proyectoId)
                                    ->where('empleado_id', $empleadoId)
                                    ->with('empleado')
                                    ->first();
                                
                                if (!$empleadoProyecto) {
                                    Notification::make()
                                        ->title('Error')
                                        ->body('No se encontró la relación empleado-proyecto.')
                                        ->danger()
                                        ->send();
                                    return;
                                }
                                
                                // Obtener el nombre del empleado de forma segura
                                $nombreEmpleado = '';
                                if ($empleadoProyecto->empleado && $empleadoProyecto->empleado->nombre_completo) {
                                    $nombreEmpleado = $empleadoProyecto->empleado->nombre_completo;
                                } else {
                                    $nombreEmpleado = 'Empleado ID: ' . $empleadoId;
                                }
                                
                                // Crear registro de baja PENDIENTE (NO eliminar del equipo aún)
                                EquipoEjecutorBaja::create([
                                    'proyecto_id' => $proyectoId,
                                    'integrante_id' => $empleadoId,
                                    'tipo_integrante' => 'empleado',
                                    'nombre_integrante' => $nombreEmpleado,
                                    'rol_anterior' => $empleadoProyecto->rol ?? 'Integrante',
                                    'motivo_baja' => 'Baja solicitada durante actualización',
                                    'fecha_baja' => now(),
                                    'usuario_baja_id' => auth()->id(),
                                    'estado_baja' => 'pendiente',
                                    'ficha_actualizacion_id' => null, // Se asignará cuando se cree la ficha
                                ]);
                                
                                // NO eliminar del equipo ejecutor aquí - se hará cuando se apruebe la ficha
                                
                                // Notificación
                                Notification::make()
                                    ->title('Solicitud de baja registrada')
                                    ->body('La solicitud de baja ha sido registrada. Se aplicará cuando la ficha de actualización sea aprobada.')
                                    ->success()
                                    ->send();
                            })
                            ->after(function () {
                                // Esto refrescará el componente después de la acción
                                return;
                            }),
                    ])->columnSpanFull(),
                ])
                ->relationship()
                ->columnSpanFull()
                ->deletable(false)
                ->addable(false)
                ->defaultItems(fn($livewire) => $livewire->record ? $livewire->record->empleado_proyecto->count() : 0)
                ->itemLabel('Empleado')
                ->grid(2)
                ->live(),
                
            // Botón para solicitar nuevo empleado
            Actions::make([
                Action::make('solicitar_empleado')
                    ->label('Agregar Nuevo Empleado')
                    ->icon('heroicon-o-user-plus')
                    ->color('success')
                    ->requiresConfirmation()
                    ->modalHeading('Agregar Nuevo Empleado')
                    ->modalDescription('Complete la información para agregar un nuevo empleado al proyecto.')
                    ->modalSubmitActionLabel('Agregar')
                    ->form([
                        Select::make('empleado_id')
                            ->label('Empleado')
                            ->required()
                            ->searchable(['nombre_completo', 'numero_empleado'])
                            ->options(function ($livewire) {
                                $proyectoId = $livewire->record->id ?? $livewire->getRecord()->id;
                                
                                // Obtener IDs de empleados que ya están en el proyecto
                                $empleadosEnProyecto = \App\Models\Personal\EmpleadoProyecto::where('proyecto_id', $proyectoId)
                                    ->pluck('empleado_id')
                                    ->toArray();
                                
                                // Obtener IDs de empleados dados de baja (incluyendo pendientes y aplicadas)
                                $empleadosDadosBaja = \App\Models\Proyecto\EquipoEjecutorBaja::where('proyecto_id', $proyectoId)
                                    ->where('tipo_integrante', 'empleado')
                                    ->whereIn('estado_baja', ['pendiente', 'aplicada'])
                                    ->pluck('integrante_id')
                                    ->toArray();
                                
                                // Obtener IDs de empleados con solicitudes pendientes de agregar
                                $empleadosConSolicitudPendiente = \App\Models\Proyecto\EquipoEjecutorNuevo::where('proyecto_id', $proyectoId)
                                    ->where('tipo_integrante', 'empleado')
                                    ->where('estado_incorporacion', 'pendiente')
                                    ->pluck('integrante_id')
                                    ->toArray();
                                
                                return \App\Models\Personal\Empleado::where('user_id', '!=', auth()->id())
                                    ->whereNotIn('id', $empleadosEnProyecto)
                                    ->whereNotIn('id', $empleadosDadosBaja)
                                    ->whereNotIn('id', $empleadosConSolicitudPendiente)
                                    ->pluck('nombre_completo', 'id');
                            })
                            ->getSearchResultsUsing(function (string $search, $livewire) {
                                $proyectoId = $livewire->record->id ?? $livewire->getRecord()->id;
                                
                                // Obtener IDs de empleados que ya están en el proyecto
                                $empleadosEnProyecto = \App\Models\Personal\EmpleadoProyecto::where('proyecto_id', $proyectoId)
                                    ->pluck('empleado_id')
                                    ->toArray();
                                
                                // Obtener IDs de empleados dados de baja (incluyendo pendientes y aplicadas)
                                $empleadosDadosBaja = \App\Models\Proyecto\EquipoEjecutorBaja::where('proyecto_id', $proyectoId)
                                    ->where('tipo_integrante', 'empleado')
                                    ->whereIn('estado_baja', ['pendiente', 'aplicada'])
                                    ->pluck('integrante_id')
                                    ->toArray();
                                
                                // Obtener IDs de empleados con solicitudes pendientes de agregar
                                $empleadosConSolicitudPendiente = \App\Models\Proyecto\EquipoEjecutorNuevo::where('proyecto_id', $proyectoId)
                                    ->where('tipo_integrante', 'empleado')
                                    ->where('estado_incorporacion', 'pendiente')
                                    ->pluck('integrante_id')
                                    ->toArray();
                                
                                return \App\Models\Personal\Empleado::where('user_id', '!=', auth()->id())
                                    ->whereNotIn('id', $empleadosEnProyecto)
                                    ->whereNotIn('id', $empleadosDadosBaja)
                                    ->whereNotIn('id', $empleadosConSolicitudPendiente)
                                    ->where(function ($query) use ($search) {
                                        $query->where('nombre_completo', 'like', "%{$search}%")
                                              ->orWhere('numero_empleado', 'like', "%{$search}%");
                                    })
                                    ->limit(50)
                                    ->pluck('nombre_completo', 'id');
                            })
                            ->helperText('Si no encuentra el empleado en la lista, puede pedirle a ese empleado crear su cuenta en NEXO'),
                        TextInput::make('rol')
                            ->label('Rol')
                            ->disabled()
                            ->required()
                            ->default('Integrante'),
                        Hidden::make('rol')
                        ->default('Integrante'),
                    ])
                    ->action(function (array $data, $component) {
                        $livewire = $component->getLivewire();
                        $proyectoId = $livewire->record->id ?? $livewire->getRecord()->id;
                        
                        // Verificar que el empleado no tenga una baja pendiente o aplicada
                        $tieneBajaPendiente = \App\Models\Proyecto\EquipoEjecutorBaja::where('proyecto_id', $proyectoId)
                            ->where('tipo_integrante', 'empleado')
                            ->where('integrante_id', $data['empleado_id'])
                            ->whereIn('estado_baja', ['pendiente', 'aplicada'])
                            ->exists();

                        if ($tieneBajaPendiente) {
                            Notification::make()
                                ->title('Error')
                                ->body('Este empleado tiene una baja pendiente o ya fue dado de baja. No se puede agregar.')
                                ->danger()
                                ->send();
                            return;
                        }

                        // Verificar que no haya una solicitud pendiente para este empleado
                        $tieneSolicitudPendiente = \App\Models\Proyecto\EquipoEjecutorNuevo::where('proyecto_id', $proyectoId)
                            ->where('tipo_integrante', 'empleado')
                            ->where('integrante_id', $data['empleado_id'])
                            ->where('estado_incorporacion', 'pendiente')
                            ->exists();

                        if ($tieneSolicitudPendiente) {
                            Notification::make()
                                ->title('Error')
                                ->body('Ya existe una solicitud pendiente para este empleado.')
                                ->danger()
                                ->send();
                            return;
                        }
                        
                        // Obtener información del empleado
                        $empleado = \App\Models\Personal\Empleado::find($data['empleado_id']);
                        
                        // Crear solicitud pendiente
                        EquipoEjecutorNuevo::create([
                            'proyecto_id' => $proyectoId,
                            'integrante_id' => $data['empleado_id'],
                            'tipo_integrante' => 'empleado',
                            'nombre_integrante' => $empleado->nombre_completo,
                            'rol_nuevo' => $data['rol'],
                            'motivo_incorporacion' => 'Incorporación solicitada durante actualización',
                            'fecha_solicitud' => now(),
                            'usuario_solicitud_id' => auth()->id(),
                            'estado_incorporacion' => 'pendiente',
                        ]);
                        
                        Notification::make()
                            ->title('Solicitud enviada')
                            ->body('La solicitud de incorporación del empleado ha sido registrada. Se aplicará cuando la ficha de actualización sea aprobada.')
                            ->success()
                            ->send();
                            
                        $livewire->dispatch('refresh-form');
                    }),
            ])->columnSpanFull(),
                
            Repeater::make('estudiante_proyecto')
                ->schema([
                    Select::make('estudiante_id')
                        ->label('Estudiante')
                        ->required()
                        ->searchable(['cuenta', 'nombre', 'apellido'])
                        ->relationship(
                            name: 'estudiante',
                            titleAttribute: 'cuenta'
                        )
                        ->disabled()
                        ->required(),
                    Select::make('tipo_participacion_estudiante')
                        ->label('Tipo de participación')
                        ->required()
                        ->options([
                            'Practica Profesional' => 'Práctica Profesional',
                            'Servicio Social o PPS' => 'Servicio Social o PPS',
                            'Voluntariado' => 'Voluntariado',
                        ])
                        ->disabled()
                        ->required(),
                    Actions::make([
                        Action::make('dar_baja_estudiante')
                            ->label('Dar de Baja')
                            ->icon('heroicon-o-user-minus')
                            ->color('danger')
                            ->requiresConfirmation()
                            ->modalHeading('¿Dar de baja al estudiante?')
                            ->modalDescription('Esta acción trasladará al estudiante a la tabla de integrantes dados de baja.')
                            ->modalSubmitActionLabel('Confirmar Baja')
                            ->action(function (array $data, $component) {
                                $record = $component->getRecord();
                                
                                // Obtener el ID del estudiante de manera más robusta
                                $estudianteId = null;
                                if (is_array($record)) {
                                    $estudianteId = $record['estudiante_id'] ?? null;
                                } elseif (is_object($record)) {
                                    if (isset($record->estudiante_id)) {
                                        $estudianteId = $record->estudiante_id;
                                    } elseif (isset($record->estudiante)) {
                                        $estudianteId = $record->estudiante->id ?? null;
                                    }
                                }
                                
                                // Si aún es un objeto, obtener el ID
                                if (is_object($estudianteId) && isset($estudianteId->id)) {
                                    $estudianteId = $estudianteId->id;
                                }
                                
                                if (!$estudianteId) {
                                    Notification::make()
                                        ->title('Error')
                                        ->body('No se pudo obtener el ID del estudiante.')
                                        ->danger()
                                        ->send();
                                    return;
                                }
                                
                                $livewire = $component->getLivewire();
                                
                                // Obtener el proyecto_id desde el record del formulario
                                $proyectoId = $livewire->record->id ?? $livewire->getRecord()->id;
                                
                                // Obtener información del estudiante antes de eliminarlo
                                $estudianteProyecto = \App\Models\Estudiante\EstudianteProyecto::where('proyecto_id', $proyectoId)
                                    ->where('estudiante_id', $estudianteId)
                                    ->with('estudiante')
                                    ->first();
                                
                                if (!$estudianteProyecto) {
                                    Notification::make()
                                        ->title('Error')
                                        ->body('No se encontró la relación estudiante-proyecto.')
                                        ->danger()
                                        ->send();
                                    return;
                                }
                                
                                // Obtener el nombre del estudiante de forma segura
                                $nombreEstudiante = '';
                                if ($estudianteProyecto->estudiante) {
                                    if ($estudianteProyecto->estudiante->nombre && $estudianteProyecto->estudiante->apellido) {
                                        $nombreEstudiante = $estudianteProyecto->estudiante->nombre . ' ' . $estudianteProyecto->estudiante->apellido;
                                    } elseif ($estudianteProyecto->estudiante->cuenta) {
                                        $nombreEstudiante = 'Estudiante ' . $estudianteProyecto->estudiante->cuenta;
                                    } else {
                                        $nombreEstudiante = 'Estudiante ID: ' . $estudianteProyecto->estudiante->id;
                                    }
                                } else {
                                    $nombreEstudiante = 'Estudiante ID: ' . $estudianteId;
                                }
                                
                                // Crear registro de baja PENDIENTE (NO eliminar del equipo aún)
                                EquipoEjecutorBaja::create([
                                    'proyecto_id' => $proyectoId,
                                    'integrante_id' => $estudianteId,
                                    'tipo_integrante' => 'estudiante',
                                    'nombre_integrante' => $nombreEstudiante,
                                    'rol_anterior' => $estudianteProyecto->tipo_participacion_estudiante ?? 'Estudiante',
                                    'motivo_baja' => 'Baja solicitada durante actualización',
                                    'fecha_baja' => now(),
                                    'usuario_baja_id' => auth()->id(),
                                    'estado_baja' => 'pendiente',
                                    'ficha_actualizacion_id' => null, // Se asignará cuando se cree la ficha
                                ]);
                                
                                // NO eliminar del equipo ejecutor aquí - se hará cuando se apruebe la ficha

                                Notification::make()
                                    ->title('Solicitud de baja registrada')
                                    ->body('La solicitud de baja del estudiante ha sido registrada. Se aplicará cuando la ficha de actualización sea aprobada.')
                                    ->success()
                                    ->send();
                            })
                            ->after(function () {
                                // Esto refrescará el componente después de la acción
                                return;
                            }),
                    ])->columnSpanFull(),
                ])
                ->label('Estudiantes')
                ->relationship()
                ->deletable(false)
                ->addable(false)
                ->defaultItems(fn($livewire) => $livewire->record ? $livewire->record->estudiante_proyecto->count() : 0)
                ->columnSpanFull()
                ->grid(2)
                ->live(),
                
            // Botón para solicitar nuevo estudiante
            Actions::make([
                Action::make('solicitar_estudiante')
                    ->label('Agregar Nuevo Estudiante')
                    ->icon('heroicon-o-user-plus')
                    ->color('success')
                    ->requiresConfirmation()
                    ->modalHeading('Agregar Nuevo Estudiante')
                    ->modalDescription('Complete la información para agregar un nuevo estudiante al proyecto.')
                    ->modalSubmitActionLabel('Agregar')
                    ->form([
                        Select::make('estudiante_id')
                            ->label('Estudiante')
                            ->required()
                            ->searchable(['cuenta', 'nombre', 'apellido'])
                            ->options(function ($livewire) {
                                $proyectoId = $livewire->record->id ?? $livewire->getRecord()->id;
                                
                                // Obtener IDs de estudiantes que ya están en el proyecto
                                $estudiantesEnProyecto = \App\Models\Estudiante\EstudianteProyecto::where('proyecto_id', $proyectoId)
                                    ->pluck('estudiante_id')
                                    ->toArray();
                                
                                // Obtener IDs de estudiantes dados de baja (incluyendo pendientes y aplicadas)
                                $estudiantesDadosBaja = \App\Models\Proyecto\EquipoEjecutorBaja::where('proyecto_id', $proyectoId)
                                    ->where('tipo_integrante', 'estudiante')
                                    ->whereIn('estado_baja', ['pendiente', 'aplicada'])
                                    ->pluck('integrante_id')
                                    ->toArray();
                                
                                // Obtener IDs de estudiantes con solicitudes pendientes de agregar
                                $estudiantesConSolicitudPendiente = \App\Models\Proyecto\EquipoEjecutorNuevo::where('proyecto_id', $proyectoId)
                                    ->where('tipo_integrante', 'estudiante')
                                    ->where('estado_incorporacion', 'pendiente')
                                    ->pluck('integrante_id')
                                    ->toArray();
                                
                                $estudiantes = \App\Models\Estudiante\Estudiante::whereNotIn('id', $estudiantesEnProyecto)
                                    ->whereNotIn('id', $estudiantesDadosBaja)
                                    ->whereNotIn('id', $estudiantesConSolicitudPendiente)
                                    ->get();
                                
                                $opciones = [];
                                foreach ($estudiantes as $estudiante) {
                                    // Verificar que sea un modelo válido
                                    if (is_object($estudiante) && isset($estudiante->id) && isset($estudiante->cuenta) && isset($estudiante->nombre) && isset($estudiante->apellido)) {
                                        $opciones[$estudiante->id] = $estudiante->cuenta . ' - ' . $estudiante->nombre . ' ' . $estudiante->apellido;
                                    }
                                }
                                
                                return $opciones;
                            })
                            ->getSearchResultsUsing(function (string $search, $livewire) {
                                $proyectoId = $livewire->record->id ?? $livewire->getRecord()->id;
                                
                                // Obtener IDs de estudiantes que ya están en el proyecto
                                $estudiantesEnProyecto = \App\Models\Estudiante\EstudianteProyecto::where('proyecto_id', $proyectoId)
                                    ->pluck('estudiante_id')
                                    ->toArray();
                                
                                // Obtener IDs de estudiantes dados de baja (incluyendo pendientes y aplicadas)
                                $estudiantesDadosBaja = \App\Models\Proyecto\EquipoEjecutorBaja::where('proyecto_id', $proyectoId)
                                    ->where('tipo_integrante', 'estudiante')
                                    ->whereIn('estado_baja', ['pendiente', 'aplicada'])
                                    ->pluck('integrante_id')
                                    ->toArray();
                                
                                // Obtener IDs de estudiantes con solicitudes pendientes de agregar
                                $estudiantesConSolicitudPendiente = \App\Models\Proyecto\EquipoEjecutorNuevo::where('proyecto_id', $proyectoId)
                                    ->where('tipo_integrante', 'estudiante')
                                    ->where('estado_incorporacion', 'pendiente')
                                    ->pluck('integrante_id')
                                    ->toArray();
                                
                                $estudiantes = \App\Models\Estudiante\Estudiante::whereNotIn('id', $estudiantesEnProyecto)
                                    ->whereNotIn('id', $estudiantesDadosBaja)
                                    ->whereNotIn('id', $estudiantesConSolicitudPendiente)
                                    ->where(function ($query) use ($search) {
                                        $query->where('cuenta', 'like', "%{$search}%")
                                              ->orWhere('nombre', 'like', "%{$search}%")
                                              ->orWhere('apellido', 'like', "%{$search}%");
                                    })
                                    ->limit(50)
                                    ->get();
                                
                                $opciones = [];
                                foreach ($estudiantes as $estudiante) {
                                    // Verificar que sea un modelo válido
                                    if (is_object($estudiante) && isset($estudiante->id) && isset($estudiante->cuenta) && isset($estudiante->nombre) && isset($estudiante->apellido)) {
                                        $opciones[$estudiante->id] = $estudiante->cuenta . ' - ' . $estudiante->nombre . ' ' . $estudiante->apellido;
                                    }
                                }
                                
                                return $opciones;
                            })
                            ->createOptionForm(
                                FormularioEstudiante::form()
                            )
                            ->createOptionUsing(function (array $data) {
                                // Crear estudiante, opcionalmente con user_id
                                $estudianteData = $data;
                                
                                // Si se proporciona email, crear usuario asociado
                                if (isset($data['email']) && !empty($data['email'])) {
                                    $user = \App\Models\User::create([
                                        'name' => $data['nombre'] . ' ' . $data['apellido'],
                                        'email' => $data['email'],
                                        'password' => Hash::make('password123'), // Password temporal
                                        'email_verified_at' => now(),
                                    ]);
                                    
                                    // Asignar rol de estudiante
                                    $user->assignRole('Estudiante');
                                    
                                    $estudianteData['user_id'] = $user->id;
                                }
                                
                                // Remover email del array ya que no va en la tabla estudiante
                                unset($estudianteData['email']);
                                
                                return \App\Models\Estudiante\Estudiante::create($estudianteData);
                            })
                            ->helperText('Si no encuentra el estudiante en la lista, puede crear uno nuevo usando el botón "+"'),
                        Select::make('tipo_participacion_estudiante')
                            ->label('Tipo de participación')
                            ->required()
                            ->options([
                                'Practica Profesional' => 'Práctica Profesional',
                                'Servicio Social o PPS' => 'Servicio Social o PPS',
                                'Voluntariado' => 'Voluntariado',
                            ])
                            ->default('Voluntariado'),
                    ])
                    ->action(function (array $data, $component) {
                        $livewire = $component->getLivewire();
                        $proyectoId = $livewire->record->id ?? $livewire->getRecord()->id;
                        
                        // Validar que se proporcione el ID del estudiante
                        if (empty($data['estudiante_id'])) {
                            Notification::make()
                                ->title('Error')
                                ->body('Debe seleccionar un estudiante.')
                                ->danger()
                                ->send();
                            return;
                        }

                        // Verificar que el estudiante no tenga una baja pendiente o aplicada
                        $tieneBajaPendiente = \App\Models\Proyecto\EquipoEjecutorBaja::where('proyecto_id', $proyectoId)
                            ->where('tipo_integrante', 'estudiante')
                            ->where('integrante_id', $data['estudiante_id'])
                            ->whereIn('estado_baja', ['pendiente', 'aplicada'])
                            ->exists();

                        if ($tieneBajaPendiente) {
                            Notification::make()
                                ->title('Error')
                                ->body('Este estudiante tiene una baja pendiente o ya fue dado de baja. No se puede agregar.')
                                ->danger()
                                ->send();
                            return;
                        }

                        // Verificar que no haya una solicitud pendiente para este estudiante
                        $tieneSolicitudPendiente = \App\Models\Proyecto\EquipoEjecutorNuevo::where('proyecto_id', $proyectoId)
                            ->where('tipo_integrante', 'estudiante')
                            ->where('integrante_id', $data['estudiante_id'])
                            ->where('estado_incorporacion', 'pendiente')
                            ->exists();

                        if ($tieneSolicitudPendiente) {
                            Notification::make()
                                ->title('Error')
                                ->body('Ya existe una solicitud pendiente para este estudiante.')
                                ->danger()
                                ->send();
                            return;
                        }
                        
                        // Obtener información del estudiante
                        $estudiante = \App\Models\Estudiante\Estudiante::find($data['estudiante_id']);
                        
                        if (!$estudiante) {
                            Notification::make()
                                ->title('Error')
                                ->body('No se pudo encontrar el estudiante seleccionado.')
                                ->danger()
                                ->send();
                            return;
                        }

                        // Verificar que sea un modelo individual y no una colección
                        if (is_array($estudiante) || $estudiante instanceof \Illuminate\Support\Collection) {
                            Notification::make()
                                ->title('Error')
                                ->body('Error en la consulta del estudiante. Contacte al administrador.')
                                ->danger()
                                ->send();
                            return;
                        }

                        // Obtener el nombre del estudiante de forma segura
                        $nombreCompleto = '';
                        if (isset($estudiante->nombre) && isset($estudiante->apellido) && $estudiante->nombre && $estudiante->apellido) {
                            $nombreCompleto = $estudiante->nombre . ' ' . $estudiante->apellido;
                        } elseif (isset($estudiante->cuenta) && $estudiante->cuenta) {
                            $nombreCompleto = 'Estudiante ' . $estudiante->cuenta;
                        } else {
                            $nombreCompleto = 'Estudiante ID: ' . $estudiante->id;
                        }
                        
                        // Crear solicitud pendiente
                        EquipoEjecutorNuevo::create([
                            'proyecto_id' => $proyectoId,
                            'integrante_id' => $data['estudiante_id'],
                            'tipo_integrante' => 'estudiante',
                            'nombre_integrante' => $nombreCompleto,
                            'rol_nuevo' => $data['tipo_participacion_estudiante'],
                            'motivo_incorporacion' => 'Incorporación solicitada durante actualización',
                            'fecha_solicitud' => now(),
                            'usuario_solicitud_id' => auth()->id(),
                            'estado_incorporacion' => 'pendiente',
                        ]);
                        
                        Notification::make()
                            ->title('Solicitud enviada')
                            ->body('La solicitud de incorporación del estudiante ha sido registrada. Se aplicará cuando la ficha de actualización sea aprobada.')
                            ->success()
                            ->send();
                            
                        $livewire->dispatch('refresh-form');
                    }),
            ])->columnSpanFull(),
                
            Repeater::make('integrante_internacional_proyecto')
                ->label('Integrantes de cooperación internacional')
                ->schema([
                    Select::make('integrante_internacional_id')
                        ->label('Integrante Internacional')
                        ->disabled()
                        ->searchable(['nombre_completo', 'pais', 'institucion'])
                        ->options(function ($state, $livewire) {
                            if (!$state) return [];
                            
                            $integrante = \App\Models\Proyecto\IntegranteInternacional::find($state);
                            if (!$integrante) return [];
                            
                            return [$integrante->id => "{$integrante->nombre_completo} ({$integrante->pais} - {$integrante->institucion})"];
                        })
                        ->required(),
                    TextInput::make('rol')
                        ->label('Rol')
                        ->disabled()
                        ->required()
                        ->default('Integrante Internacional'),
                    Hidden::make('rol')
                        ->default('Integrante Internacional'),
                    Actions::make([
                        Action::make('dar_baja_internacional')
                            ->label('Dar de Baja')
                            ->icon('heroicon-o-user-minus')
                            ->color('danger')
                            ->requiresConfirmation()
                            ->modalHeading('¿Dar de baja al integrante internacional?')
                            ->modalDescription('Esta acción trasladará al integrante internacional a la tabla de integrantes dados de baja.')
                            ->modalSubmitActionLabel('Confirmar Baja')
                            ->action(function (array $data, $component) {
                                $record = $component->getRecord();
                                
                                // Obtener el ID del integrante internacional de manera más robusta
                                $integranteInternacionalId = null;
                                if (is_array($record)) {
                                    $integranteInternacionalId = $record['integrante_internacional_id'] ?? null;
                                } elseif (is_object($record)) {
                                    if (isset($record->integrante_internacional_id)) {
                                        $integranteInternacionalId = $record->integrante_internacional_id;
                                    } elseif (isset($record->integranteInternacional)) {
                                        $integranteInternacionalId = $record->integranteInternacional->id ?? null;
                                    }
                                }
                                
                                // Si aún es un objeto, obtener el ID
                                if (is_object($integranteInternacionalId) && isset($integranteInternacionalId->id)) {
                                    $integranteInternacionalId = $integranteInternacionalId->id;
                                }
                                
                                if (!$integranteInternacionalId) {
                                    Notification::make()
                                        ->title('Error')
                                        ->body('No se pudo obtener el ID del integrante internacional.')
                                        ->danger()
                                        ->send();
                                    return;
                                }
                                
                                $livewire = $component->getLivewire();
                                
                                // Obtener el proyecto_id desde el record del formulario
                                $proyectoId = $livewire->record->id ?? $livewire->getRecord()->id;
                                
                                // Obtener información del integrante internacional antes de eliminarlo
                                $integranteProyecto = \App\Models\Proyecto\IntegranteInternacionalProyecto::where('proyecto_id', $proyectoId)
                                    ->where('integrante_internacional_id', $integranteInternacionalId)
                                    ->with('integranteInternacional')
                                    ->first();
                                
                                if (!$integranteProyecto) {
                                    Notification::make()
                                        ->title('Error')
                                        ->body('No se encontró la relación integrante internacional-proyecto.')
                                        ->danger()
                                        ->send();
                                    return;
                                }
                                
                                // Obtener el nombre del integrante internacional de forma segura
                                $nombreInternacional = '';
                                if ($integranteProyecto->integranteInternacional && $integranteProyecto->integranteInternacional->nombre_completo) {
                                    $nombreInternacional = $integranteProyecto->integranteInternacional->nombre_completo;
                                } else {
                                    $nombreInternacional = 'Integrante Internacional ID: ' . $integranteInternacionalId;
                                }
                                
                                // Crear registro de baja PENDIENTE (NO eliminar del equipo aún)
                                EquipoEjecutorBaja::create([
                                    'proyecto_id' => $proyectoId,
                                    'integrante_id' => $integranteInternacionalId,
                                    'tipo_integrante' => 'integrante_internacional',
                                    'nombre_integrante' => $nombreInternacional,
                                    'rol_anterior' => $integranteProyecto->rol ?? 'Integrante Internacional',
                                    'motivo_baja' => 'Baja solicitada durante actualización',
                                    'fecha_baja' => now(),
                                    'usuario_baja_id' => auth()->id(),
                                    'estado_baja' => 'pendiente',
                                    'ficha_actualizacion_id' => null, // Se asignará cuando se cree la ficha
                                ]);
                                
                                // NO eliminar del equipo ejecutor aquí - se hará cuando se apruebe la ficha

                                Notification::make()
                                    ->title('Solicitud de baja registrada')
                                    ->body('La solicitud de baja del integrante internacional ha sido registrada. Se aplicará cuando la ficha de actualización sea aprobada.')
                                    ->success()
                                    ->send();
                                $livewire->dispatch('refresh-form');
                            })
                            ->after(function () {
                                // Esto refrescará el componente después de la acción
                                return;
                            }),
                    ])->columnSpanFull(),
                ])
                ->label('Integrantes de cooperación internacional')
                ->relationship()
                ->columnSpanFull()
                ->deletable(false)
                ->addable(false)
                ->defaultItems(fn($livewire) => $livewire->record ? $livewire->record->integrante_internacional_proyecto->count() : 0)
                ->grid(2)
                ->live(),
                
            // Botón para solicitar nuevo integrante internacional
            Actions::make([
                Action::make('solicitar_internacional')
                    ->label('Agregar Nuevo Integrante Internacional')
                    ->icon('heroicon-o-user-plus')
                    ->color('success')
                    ->requiresConfirmation()
                    ->modalHeading('Agregar Nuevo Integrante Internacional')
                    ->modalDescription('Complete la información para agregar un nuevo integrante internacional al proyecto.')
                    ->modalSubmitActionLabel('Agregar')
                    ->form([
                        Select::make('integrante_internacional_id')
                            ->label('Integrante Internacional')
                            ->required()
                            ->searchable(['nombre_completo', 'pais', 'institucion'])
                            ->options(function ($livewire) {
                                $proyectoId = $livewire->record->id ?? $livewire->getRecord()->id;
                                
                                // Obtener IDs de integrantes internacionales que ya están en el proyecto
                                $internacionalesEnProyecto = \App\Models\Proyecto\IntegranteInternacionalProyecto::where('proyecto_id', $proyectoId)
                                    ->pluck('integrante_internacional_id')
                                    ->toArray();
                                
                                // Obtener IDs de integrantes internacionales dados de baja (incluyendo pendientes y aplicadas)
                                $internacionalesDadosBaja = \App\Models\Proyecto\EquipoEjecutorBaja::where('proyecto_id', $proyectoId)
                                    ->where('tipo_integrante', 'integrante_internacional')
                                    ->whereIn('estado_baja', ['pendiente', 'aplicada'])
                                    ->pluck('integrante_id')
                                    ->filter() // Filtrar valores null
                                    ->toArray();
                                
                                // Obtener IDs de integrantes internacionales con solicitudes pendientes de agregar
                                $internacionalesConSolicitudPendiente = \App\Models\Proyecto\EquipoEjecutorNuevo::where('proyecto_id', $proyectoId)
                                    ->where('tipo_integrante', 'integrante_internacional')
                                    ->where('estado_incorporacion', 'pendiente')
                                    ->pluck('integrante_id')
                                    ->filter() // Filtrar valores null
                                    ->toArray();
                                
                                $query = \App\Models\Proyecto\IntegranteInternacional::query();
                                
                                if (!empty($internacionalesEnProyecto)) {
                                    $query->whereNotIn('id', $internacionalesEnProyecto);
                                }
                                
                                if (!empty($internacionalesDadosBaja)) {
                                    $query->whereNotIn('id', $internacionalesDadosBaja);
                                }
                                
                                if (!empty($internacionalesConSolicitudPendiente)) {
                                    $query->whereNotIn('id', $internacionalesConSolicitudPendiente);
                                }
                                
                                $integrantes = $query->get();
                                
                                $opciones = [];
                                foreach ($integrantes as $integrante) {
                                    // Verificar que el integrante sea un modelo válido
                                    if (is_object($integrante) && isset($integrante->id) && isset($integrante->nombre_completo)) {
                                        $opciones[$integrante->id] = "{$integrante->nombre_completo} ({$integrante->pais} - {$integrante->institucion})";
                                    }
                                }
                                
                                return $opciones;
                            })
                            ->getSearchResultsUsing(function (string $search, $livewire) {
                                $proyectoId = $livewire->record->id ?? $livewire->getRecord()->id;
                                
                                // Obtener IDs de integrantes internacionales que ya están en el proyecto
                                $internacionalesEnProyecto = \App\Models\Proyecto\IntegranteInternacionalProyecto::where('proyecto_id', $proyectoId)
                                    ->pluck('integrante_internacional_id')
                                    ->toArray();
                                
                                // Obtener IDs de integrantes internacionales dados de baja (incluyendo pendientes y aplicadas)
                                $internacionalesDadosBaja = \App\Models\Proyecto\EquipoEjecutorBaja::where('proyecto_id', $proyectoId)
                                    ->where('tipo_integrante', 'integrante_internacional')
                                    ->whereIn('estado_baja', ['pendiente', 'aplicada'])
                                    ->pluck('integrante_id')
                                    ->filter() // Filtrar valores null
                                    ->toArray();
                                
                                // Obtener IDs de integrantes internacionales con solicitudes pendientes de agregar
                                $internacionalesConSolicitudPendiente = \App\Models\Proyecto\EquipoEjecutorNuevo::where('proyecto_id', $proyectoId)
                                    ->where('tipo_integrante', 'integrante_internacional')
                                    ->where('estado_incorporacion', 'pendiente')
                                    ->pluck('integrante_id')
                                    ->filter() // Filtrar valores null
                                    ->toArray();
                                
                                $query = \App\Models\Proyecto\IntegranteInternacional::query();
                                
                                if (!empty($internacionalesEnProyecto)) {
                                    $query->whereNotIn('id', $internacionalesEnProyecto);
                                }
                                
                                if (!empty($internacionalesDadosBaja)) {
                                    $query->whereNotIn('id', $internacionalesDadosBaja);
                                }
                                
                                if (!empty($internacionalesConSolicitudPendiente)) {
                                    $query->whereNotIn('id', $internacionalesConSolicitudPendiente);
                                }
                                
                                $query->where(function ($subQuery) use ($search) {
                                    $subQuery->where('nombre_completo', 'like', "%{$search}%")
                                          ->orWhere('pais', 'like', "%{$search}%")
                                          ->orWhere('institucion', 'like', "%{$search}%");
                                });
                                
                                $integrantes = $query->limit(50)->get();
                                
                                $opciones = [];
                                foreach ($integrantes as $integrante) {
                                    // Verificar que el integrante sea un modelo válido
                                    if (is_object($integrante) && isset($integrante->id) && isset($integrante->nombre_completo)) {
                                        $opciones[$integrante->id] = "{$integrante->nombre_completo} ({$integrante->pais} - {$integrante->institucion})";
                                    }
                                }
                                
                                return $opciones;
                            })
                            ->createOptionForm(
                                FormularioIntegranteInternacional::form()
                            )
                            ->createOptionUsing(function (array $data) {
                                return \App\Models\Proyecto\IntegranteInternacional::create($data);
                            })
                            ->helperText('Si no encuentra el integrante en la lista, puede crear uno nuevo usando el botón "+"'),
                    ])
                    ->action(function (array $data, $component) {
                        $livewire = $component->getLivewire();
                        $proyectoId = $livewire->record->id ?? $livewire->getRecord()->id;
                        
                        // Verificar que el integrante internacional no tenga una baja pendiente o aplicada
                        $tieneBajaPendiente = \App\Models\Proyecto\EquipoEjecutorBaja::where('proyecto_id', $proyectoId)
                            ->where('tipo_integrante', 'integrante_internacional')
                            ->where('integrante_id', $data['integrante_internacional_id'])
                            ->whereIn('estado_baja', ['pendiente', 'aplicada'])
                            ->exists();

                        if ($tieneBajaPendiente) {
                            Notification::make()
                                ->title('Error')
                                ->body('Este integrante internacional tiene una baja pendiente o ya fue dado de baja. No se puede agregar.')
                                ->danger()
                                ->send();
                            return;
                        }

                        // Verificar que no haya una solicitud pendiente para este integrante internacional
                        $tieneSolicitudPendiente = \App\Models\Proyecto\EquipoEjecutorNuevo::where('proyecto_id', $proyectoId)
                            ->where('tipo_integrante', 'integrante_internacional')
                            ->where('integrante_id', $data['integrante_internacional_id'])
                            ->where('estado_incorporacion', 'pendiente')
                            ->exists();

                        if ($tieneSolicitudPendiente) {
                            Notification::make()
                                ->title('Error')
                                ->body('Ya existe una solicitud pendiente para este integrante internacional.')
                                ->danger()
                                ->send();
                            return;
                        }

                        // Validar que se proporcione el ID del integrante internacional
                        if (empty($data['integrante_internacional_id'])) {
                            Notification::make()
                                ->title('Error')
                                ->body('Debe seleccionar un integrante internacional.')
                                ->danger()
                                ->send();
                            return;
                        }
                        
                        // Obtener información del integrante internacional
                        $integrante = \App\Models\Proyecto\IntegranteInternacional::find($data['integrante_internacional_id']);
                        
                        if (!$integrante) {
                            Notification::make()
                                ->title('Error')
                                ->body('No se pudo encontrar el integrante internacional seleccionado.')
                                ->danger()
                                ->send();
                            return;
                        }

                        // Verificar que el integrante sea un modelo individual, no una colección
                        if (is_array($integrante) || $integrante instanceof \Illuminate\Support\Collection) {
                            Notification::make()
                                ->title('Error')
                                ->body('Error en la consulta del integrante internacional. Contacte al administrador.')
                                ->danger()
                                ->send();
                            return;
                        }

                        // Obtener el nombre del integrante de forma segura
                        $nombreCompleto = '';
                        if (isset($integrante->nombre_completo) && !empty($integrante->nombre_completo)) {
                            $nombreCompleto = $integrante->nombre_completo;
                        } else {
                            $nombreCompleto = 'Integrante Internacional ID: ' . $integrante->id;
                        }
                        
                        // Crear solicitud pendiente
                        EquipoEjecutorNuevo::create([
                            'proyecto_id' => $proyectoId,
                            'integrante_id' => $data['integrante_internacional_id'],
                            'tipo_integrante' => 'integrante_internacional',
                            'nombre_integrante' => $nombreCompleto,
                            'rol_nuevo' => 'Integrante Internacional',
                            'motivo_incorporacion' => 'Incorporación solicitada durante actualización',
                            'fecha_solicitud' => now(),
                            'usuario_solicitud_id' => auth()->id(),
                            'estado_incorporacion' => 'pendiente',
                        ]);
                        
                        Notification::make()
                            ->title('Solicitud enviada')
                            ->body('La solicitud de incorporación del integrante internacional ha sido registrada. Se aplicará cuando la ficha de actualización sea aprobada.')
                            ->success()
                            ->send();
                            
                        $livewire->dispatch('refresh-form');
                    }),
            ])->columnSpanFull(),

            // Motivos generales de cambios en el equipo
            Fieldset::make('Motivos de Cambios en el Equipo')
                ->schema([
                    Textarea::make('motivo_responsabilidades_nuevos')
                        ->label('Motivos del cambio de integrantes de equipo (Describa las responsabilidades de los nuevos miembros)')
                        ->placeholder('Describa las responsabilidades específicas que tendrán los nuevos miembros del equipo...')
                        ->rows(4)
                        ->columnSpanFull()
                        ->required()
                        ->visible(function (Get $get, $livewire) {
                            // Mostrar si hay solicitudes pendientes de nuevos integrantes
                            if (isset($livewire->record)) {
                                $nuevosPendientes = \App\Models\Proyecto\EquipoEjecutorNuevo::where('proyecto_id', $livewire->record->id)
                                    ->where('estado_incorporacion', 'pendiente')
                                    ->whereNull('ficha_actualizacion_id')
                                    ->count();
                                return $nuevosPendientes > 0;
                            }
                            
                            return false;
                        }),
                    
                    Textarea::make('motivo_razones_cambio')
                        ->label('Motivos del cambio de integrantes de equipo (Describa las razones por las cuales se cambia al equipo)')
                        ->placeholder('Explique las razones que justifican los cambios en la composición del equipo ejecutor...')
                        ->rows(4)
                        ->columnSpanFull()
                        ->required()
                        ->visible(function (Get $get, $livewire) {
                            // Mostrar solo si hay integrantes dados de baja pendientes
                            if (isset($livewire->record)) {
                                $bajasPendientes = \App\Models\Proyecto\EquipoEjecutorBaja::where('proyecto_id', $livewire->record->id)
                                    ->where('estado_baja', 'pendiente')
                                    ->whereNull('ficha_actualizacion_id')
                                    ->count();
                                return $bajasPendientes > 0;
                            }
                            return false;
                        }),
                ])
                ->columnSpanFull()
                ->visible(function (Get $get, $livewire) {
                    // Mostrar el fieldset solo si hay cambios (nuevos o bajas)
                    if (isset($livewire->record)) {
                        // Verificar si hay bajas pendientes
                        $bajasPendientes = \App\Models\Proyecto\EquipoEjecutorBaja::where('proyecto_id', $livewire->record->id)
                            ->where('estado_baja', 'pendiente')
                            ->whereNull('ficha_actualizacion_id')
                            ->count();
                        
                        // Verificar si hay nuevos integrantes pendientes
                        $nuevosPendientes = \App\Models\Proyecto\EquipoEjecutorNuevo::where('proyecto_id', $livewire->record->id)
                            ->where('estado_incorporacion', 'pendiente')
                            ->whereNull('ficha_actualizacion_id')
                            ->count();
                        
                        return $bajasPendientes > 0 || $nuevosPendientes > 0;
                    }
                    
                    return false;
                }),

            // Sección de equipo dado de baja

            // Sección de nuevos integrantes pendientes
            Fieldset::make('Solicitudes de Nuevos Integrantes')
                ->schema([
                    Repeater::make('equipo_ejecutor_nuevos')
                        ->label('Nuevos integrantes pendientes de aprobación')
                        ->schema([
                            TextInput::make('id')
                                ->label('Nombre del Integrante')
                                ->disabled()
                                ->formatStateUsing(function ($record, $state) {
                                    if (!$record) return '';
                                    return $record->nombre_integrante;
                                })
                                ->columnSpan(2),
                                
                            TextInput::make('tipo_integrante')
                                ->label('Tipo')
                                ->disabled()
                                ->formatStateUsing(function ($state) {
                                    return match($state) {
                                        'empleado' => 'Empleado',
                                        'estudiante' => 'Estudiante',
                                        'integrante_internacional' => 'Integrante Internacional',
                                        default => 'N/A'
                                    };
                                })
                                ->columnSpan(1),
                                
                            TextInput::make('rol_nuevo')
                                ->label('Rol Propuesto')
                                ->disabled()
                                ->columnSpan(1),
                                
                            TextInput::make('fecha_solicitud')
                                ->label('Fecha de Solicitud')
                                ->disabled()
                                ->formatStateUsing(fn ($state) => $state ? \Carbon\Carbon::parse($state)->format('d/m/Y H:i') : '')
                                ->columnSpan(1),
                                
                            Actions::make([
                                Action::make('cancelar_solicitud')
                                    ->label('Cancelar Solicitud')
                                    ->icon('heroicon-o-x-mark')
                                    ->color('danger')
                                    ->requiresConfirmation()
                                    ->modalHeading('¿Cancelar solicitud de incorporación?')
                                    ->modalDescription('Esta acción eliminará la solicitud de incorporación del nuevo integrante.')
                                    ->modalSubmitActionLabel('Cancelar Solicitud')
                                    ->action(function ($record, $component) {
                                        $livewire = $component->getLivewire();
                                        
                                        // Eliminar el registro de solicitud
                                        $record->delete();
                                        
                                        Notification::make()
                                            ->title('Solicitud cancelada')
                                            ->body('La solicitud de incorporación ha sido cancelada.')
                                            ->success()
                                            ->send();
                                    })
                                    ->after(function () {
                                        // Esto refrescará el componente después de la acción
                                        return;
                                    }),
                            ])->columnSpanFull(),
                        ])
                        ->relationship('equipoEjecutorNuevos', function ($query) {
                            // Solo mostrar las solicitudes pendientes que NO han sido asociadas a una ficha de actualización
                            return $query->where('estado_incorporacion', 'pendiente')
                                        ->whereNull('ficha_actualizacion_id');
                        })->live()
                        ->addable(false)
                        ->deletable(false)
                        ->reorderable(false)
                        ->columns(3)
                        ->columnSpanFull()
                        ->defaultItems(0),
                ])
                ->columnSpanFull()
                ->live(),

            
            Fieldset::make('Equipo dado de baja')
                ->schema([
                    Repeater::make('equipo_ejecutor_bajas')
                        ->label('Integrantes dados de baja')
                        ->schema([
                            TextInput::make('id')
                                ->label('Nombre del Integrante')
                                ->disabled()
                                ->formatStateUsing(function ($record, $state) {
                                    if (!$record) return '';
                                    
                                    return $record->nombre_integrante;
                                })
                                ->columnSpan(2),
                                
                            TextInput::make('tipo_integrante')
                                ->label('Tipo')
                                ->disabled()
                                ->formatStateUsing(function ($state) {
                                    return match($state) {
                                        'empleado' => 'Empleado',
                                        'estudiante' => 'Estudiante',
                                        'integrante_internacional' => 'Integrante Internacional',
                                        default => 'N/A'
                                    };
                                })
                                ->columnSpan(1),
                                
                            TextInput::make('rol_anterior')
                                ->label('Rol Anterior')
                                ->disabled()
                                ->columnSpan(1),
                                
                            TextInput::make('fecha_baja')
                                ->label('Fecha de Baja')
                                ->disabled()
                                ->formatStateUsing(fn ($state) => $state ? \Carbon\Carbon::parse($state)->format('d/m/Y H:i') : '')
                                ->columnSpan(1),
                                
                            Actions::make([
                                Action::make('reincorporar')
                                    ->label('Reincorporar')
                                    ->icon('heroicon-o-arrow-uturn-left')
                                    ->color('success')
                                    ->requiresConfirmation()
                                    ->modalHeading('¿Reincorporar al integrante?')
                                    ->modalDescription('Esta acción volverá a agregar al integrante al equipo activo.')
                                    ->modalSubmitActionLabel('Reincorporar')
                                    ->action(function ($record, $component) {
                                        $livewire = $component->getLivewire();
                                        $proyectoId = $livewire->record->id ?? $livewire->getRecord()->id;
                                        
                                        // Reincorporar según el tipo de integrante
                                        if ($record->tipo_integrante === 'empleado') {
                                            // Crear registro en tabla de empleados
                                            \App\Models\Personal\EmpleadoProyecto::create([
                                                'proyecto_id' => $proyectoId,
                                                'empleado_id' => $record->integrante_id,
                                                'rol' => $record->rol_anterior ?? 'Integrante',
                                            ]);
                                        } elseif ($record->tipo_integrante === 'estudiante') {
                                            // Crear registro en tabla de estudiantes
                                            \App\Models\Estudiante\EstudianteProyecto::create([
                                                'proyecto_id' => $proyectoId,
                                                'estudiante_id' => $record->integrante_id,
                                                'tipo_participacion_estudiante' => $record->rol_anterior ?? 'Voluntariado',
                                            ]);
                                        } elseif ($record->tipo_integrante === 'integrante_internacional') {
                                            // Crear registro en tabla de integrantes internacionales
                                            \App\Models\Proyecto\IntegranteInternacionalProyecto::create([
                                                'proyecto_id' => $proyectoId,
                                                'integrante_internacional_id' => $record->integrante_id,
                                                'rol' => $record->rol_anterior ?? 'Integrante Internacional',
                                            ]);
                                        }
                                        
                                        // Eliminar el registro de bajas
                                        $record->delete();
                                        
                                        Notification::make()
                                            ->title('Integrante reincorporado')
                                            ->body('El integrante ha sido reincorporado al equipo ejecutor.')
                                            ->success()
                                            ->send();
                                    })
                                    ->after(function () {
                                        // Esto refrescará el componente después de la acción
                                        return;
                                    }),
                            ])->columnSpanFull(),
                        ])
                        ->relationship('equipoEjecutorBajas', function ($query) {
                            // Solo mostrar las bajas pendientes que NO han sido asociadas a una ficha de actualización
                            return $query->where('estado_baja', 'pendiente')
                                        ->whereNull('ficha_actualizacion_id');
                        })
                        ->live()
                        ->addable(false)
                        ->deletable(false)
                        ->reorderable(false)
                        ->columns(3)
                        ->columnSpanFull()
                        ->defaultItems(0),
                ])
                ->columnSpanFull()
                ->live(),
        ];
    }
}
