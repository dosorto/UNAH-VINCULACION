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
                            ->form([
                                Textarea::make('motivo_baja')
                                    ->label('Motivo de la baja')
                                    ->required()
                                    ->placeholder('Describa el motivo por el cual se da de baja al integrante...')
                                    ->rows(3),
                            ])
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
                                
                                // Crear registro de baja PENDIENTE (NO eliminar del equipo aún)
                                EquipoEjecutorBaja::create([
                                    'proyecto_id' => $proyectoId,
                                    'integrante_id' => $empleadoId,
                                    'tipo_integrante' => 'empleado',
                                    'nombre_integrante' => $empleadoProyecto->empleado->nombre_completo,
                                    'rol_anterior' => $empleadoProyecto->rol ?? 'Integrante',
                                    'motivo_baja' => $data['motivo_baja'],
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
                                    
                                // Refrescar el formulario
                                $livewire->dispatch('refresh-form');
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
                                
                                return \App\Models\Personal\Empleado::where('user_id', '!=', auth()->id())
                                    ->whereNotIn('id', $empleadosEnProyecto)
                                    ->pluck('nombre_completo', 'id');
                            })
                            ->getSearchResultsUsing(function (string $search, $livewire) {
                                $proyectoId = $livewire->record->id ?? $livewire->getRecord()->id;
                                
                                // Obtener IDs de empleados que ya están en el proyecto
                                $empleadosEnProyecto = \App\Models\Personal\EmpleadoProyecto::where('proyecto_id', $proyectoId)
                                    ->pluck('empleado_id')
                                    ->toArray();
                                
                                return \App\Models\Personal\Empleado::where('user_id', '!=', auth()->id())
                                    ->whereNotIn('id', $empleadosEnProyecto)
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
                        Textarea::make('motivo_incorporacion')
                            ->label('Motivo de incorporación')
                            ->required()
                            ->placeholder('Describa las razones y responsabilidades del nuevo integrante...')
                            ->rows(3),
                    ])
                    ->action(function (array $data, $component) {
                        $livewire = $component->getLivewire();
                        $proyectoId = $livewire->record->id ?? $livewire->getRecord()->id;
                        
                        // Obtener información del empleado
                        $empleado = \App\Models\Personal\Empleado::find($data['empleado_id']);
                        
                        // Crear solicitud pendiente
                        EquipoEjecutorNuevo::create([
                            'proyecto_id' => $proyectoId,
                            'integrante_id' => $data['empleado_id'],
                            'tipo_integrante' => 'empleado',
                            'nombre_integrante' => $empleado->nombre_completo,
                            'rol_nuevo' => $data['rol'],
                            'motivo_incorporacion' => $data['motivo_incorporacion'],
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
                            ->form([
                                Textarea::make('motivo_baja')
                                    ->label('Motivo de la baja')
                                    ->required()
                                    ->placeholder('Describa el motivo por el cual se da de baja al estudiante...')
                                    ->rows(3),
                            ])
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
                                    ->with('estudiante.user')
                                    ->first();
                                
                                if (!$estudianteProyecto) {
                                    Notification::make()
                                        ->title('Error')
                                        ->body('No se encontró la relación estudiante-proyecto.')
                                        ->danger()
                                        ->send();
                                    return;
                                }
                                
                                // Crear registro de baja PENDIENTE (NO eliminar del equipo aún)
                                EquipoEjecutorBaja::create([
                                    'proyecto_id' => $proyectoId,
                                    'integrante_id' => $estudianteId,
                                    'tipo_integrante' => 'estudiante',
                                    'nombre_integrante' => $estudianteProyecto->estudiante->user->name ?? $estudianteProyecto->estudiante->nombre . ' ' . $estudianteProyecto->estudiante->apellido,
                                    'rol_anterior' => $estudianteProyecto->tipo_participacion_estudiante ?? 'Estudiante',
                                    'motivo_baja' => $data['motivo_baja'],
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
                                    
                                // Refrescar el formulario
                                $livewire->dispatch('refresh-form');
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
                                
                                $estudiantes = \App\Models\Estudiante\Estudiante::whereNotIn('id', $estudiantesEnProyecto)->get();
                                
                                $opciones = [];
                                foreach ($estudiantes as $estudiante) {
                                    $opciones[$estudiante->id] = $estudiante->cuenta . ' - ' . $estudiante->nombre . ' ' . $estudiante->apellido;
                                }
                                
                                return $opciones;
                            })
                            ->getSearchResultsUsing(function (string $search, $livewire) {
                                $proyectoId = $livewire->record->id ?? $livewire->getRecord()->id;
                                
                                // Obtener IDs de estudiantes que ya están en el proyecto
                                $estudiantesEnProyecto = \App\Models\Estudiante\EstudianteProyecto::where('proyecto_id', $proyectoId)
                                    ->pluck('estudiante_id')
                                    ->toArray();
                                
                                $estudiantes = \App\Models\Estudiante\Estudiante::whereNotIn('id', $estudiantesEnProyecto)
                                    ->where(function ($query) use ($search) {
                                        $query->where('cuenta', 'like', "%{$search}%")
                                              ->orWhere('nombre', 'like', "%{$search}%")
                                              ->orWhere('apellido', 'like', "%{$search}%");
                                    })
                                    ->limit(50)
                                    ->get();
                                
                                $opciones = [];
                                foreach ($estudiantes as $estudiante) {
                                    $opciones[$estudiante->id] = $estudiante->cuenta . ' - ' . $estudiante->nombre . ' ' . $estudiante->apellido;
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
                        Textarea::make('motivo_incorporacion')
                            ->label('Motivo de incorporación')
                            ->required()
                            ->placeholder('Describa las razones y responsabilidades del nuevo estudiante...')
                            ->rows(3),
                    ])
                    ->action(function (array $data, $component) {
                        $livewire = $component->getLivewire();
                        $proyectoId = $livewire->record->id ?? $livewire->getRecord()->id;
                        
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
                        
                        // Crear solicitud pendiente
                        EquipoEjecutorNuevo::create([
                            'proyecto_id' => $proyectoId,
                            'integrante_id' => $data['estudiante_id'],
                            'tipo_integrante' => 'estudiante',
                            'nombre_integrante' => $estudiante->nombre . ' ' . $estudiante->apellido,
                            'rol_nuevo' => $data['tipo_participacion_estudiante'],
                            'motivo_incorporacion' => $data['motivo_incorporacion'],
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
                            ->form([
                                Textarea::make('motivo_baja')
                                    ->label('Motivo de la baja')
                                    ->required()
                                    ->placeholder('Describa el motivo por el cual se da de baja al integrante internacional...')
                                    ->rows(3),
                            ])
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
                                
                                // Crear registro de baja PENDIENTE (NO eliminar del equipo aún)
                                EquipoEjecutorBaja::create([
                                    'proyecto_id' => $proyectoId,
                                    'integrante_id' => $integranteInternacionalId,
                                    'tipo_integrante' => 'integrante_internacional',
                                    'nombre_integrante' => $integranteProyecto->integranteInternacional->nombre_completo,
                                    'rol_anterior' => $integranteProyecto->rol ?? 'Integrante Internacional',
                                    'motivo_baja' => $data['motivo_baja'],
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
                                    
                                // Refrescar el formulario
                                $livewire->dispatch('refresh-form');
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
                                
                                $integrantes = \App\Models\Proyecto\IntegranteInternacional::whereNotIn('id', $internacionalesEnProyecto)->get();
                                
                                $opciones = [];
                                foreach ($integrantes as $integrante) {
                                    $opciones[$integrante->id] = "{$integrante->nombre_completo} ({$integrante->pais} - {$integrante->institucion})";
                                }
                                
                                return $opciones;
                            })
                            ->getSearchResultsUsing(function (string $search, $livewire) {
                                $proyectoId = $livewire->record->id ?? $livewire->getRecord()->id;
                                
                                // Obtener IDs de integrantes internacionales que ya están en el proyecto
                                $internacionalesEnProyecto = \App\Models\Proyecto\IntegranteInternacionalProyecto::where('proyecto_id', $proyectoId)
                                    ->pluck('integrante_internacional_id')
                                    ->toArray();
                                
                                $integrantes = \App\Models\Proyecto\IntegranteInternacional::whereNotIn('id', $internacionalesEnProyecto)
                                    ->where(function ($query) use ($search) {
                                        $query->where('nombre_completo', 'like', "%{$search}%")
                                              ->orWhere('pais', 'like', "%{$search}%")
                                              ->orWhere('institucion', 'like', "%{$search}%");
                                    })
                                    ->limit(50)
                                    ->get();
                                
                                $opciones = [];
                                foreach ($integrantes as $integrante) {
                                    $opciones[$integrante->id] = "{$integrante->nombre_completo} ({$integrante->pais} - {$integrante->institucion})";
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
                        Textarea::make('motivo_incorporacion')
                            ->label('Motivo de incorporación')
                            ->required()
                            ->placeholder('Describa las razones y responsabilidades del nuevo integrante internacional...')
                            ->rows(3),
                    ])
                    ->action(function (array $data, $component) {
                        $livewire = $component->getLivewire();
                        $proyectoId = $livewire->record->id ?? $livewire->getRecord()->id;
                        
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
                        
                        // Crear solicitud pendiente
                        EquipoEjecutorNuevo::create([
                            'proyecto_id' => $proyectoId,
                            'integrante_id' => $data['integrante_internacional_id'],
                            'tipo_integrante' => 'integrante_internacional',
                            'nombre_integrante' => $integrante->nombre_completo,
                            'rol_nuevo' => 'Integrante Internacional',
                            'motivo_incorporacion' => $data['motivo_incorporacion'],
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
                                
                            Textarea::make('motivo_incorporacion')
                                ->label('Motivo de Incorporación')
                                ->disabled()
                                ->rows(2)
                                ->columnSpan(2),
                                
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
                                            
                                        // Refrescar el formulario
                                        $livewire->dispatch('refresh-form');
                                    }),
                            ])->columnSpanFull(),
                        ])
                        ->relationship('equipoEjecutorNuevos')
                        ->addable(false)
                        ->deletable(false)
                        ->reorderable(false)
                        ->columns(4)
                        ->columnSpanFull()
                        ->defaultItems(0)
                        ->live(),
                ])
                ->columnSpanFull(),

            
            // Motivos generales de cambios en el equipo
            Fieldset::make('Motivos de Cambios en el Equipo')
                ->schema([
                    Textarea::make('motivo_responsabilidades_nuevos')
                        ->label('Motivos del cambio de integrantes de equipo (Describa las responsabilidades de los nuevos miembros)')
                        ->placeholder('Describa las responsabilidades específicas que tendrán los nuevos miembros del equipo...')
                        ->rows(4)
                        ->columnSpanFull()
                        ->visible(function (Get $get, $livewire) {
                            // Mostrar solo si se han agregado nuevos integrantes durante la edición
                            $empleados = $get('empleado_proyecto') ?? [];
                            $estudiantes = $get('estudiante_proyecto') ?? [];
                            $internacionales = $get('integrante_internacional_proyecto') ?? [];
                            
                            // Contar integrantes agregados en esta sesión
                            $totalActual = count($empleados) + count($estudiantes) + count($internacionales);
                            
                            // Obtener los integrantes originales del proyecto
                            if (isset($livewire->record)) {
                                $empleadosOriginales = $livewire->record->empleado_proyecto->count();
                                $estudiantesOriginales = $livewire->record->estudiante_proyecto->count();
                                $internacionalesOriginales = $livewire->record->integrante_internacional_proyecto->count();
                                $totalOriginal = $empleadosOriginales + $estudiantesOriginales + $internacionalesOriginales;
                                
                                return $totalActual > $totalOriginal;
                            }
                            
                            return $totalActual > 0;
                        }),
                    
                    Textarea::make('motivo_razones_cambio')
                        ->label('Motivos del cambio de integrantes de equipo (Describa las razones por las cuales se cambia al equipo)')
                        ->placeholder('Explique las razones que justifican los cambios en la composición del equipo ejecutor...')
                        ->rows(4)
                        ->columnSpanFull()
                        ->visible(function (Get $get, $livewire) {
                            // Mostrar solo si hay integrantes dados de baja pendientes
                            if (isset($livewire->record)) {
                                $bajasPendientes = \App\Models\Proyecto\EquipoEjecutorBaja::where('proyecto_id', $livewire->record->id)
                                    ->where('estado_baja', 'pendiente')
                                    ->count();
                                return $bajasPendientes > 0;
                            }
                            return false;
                        }),
                ])
                ->columnSpanFull()
                ->visible(function (Get $get, $livewire) {
                    // Mostrar el fieldset solo si hay cambios (nuevos o bajas)
                    $empleados = $get('empleado_proyecto') ?? [];
                    $estudiantes = $get('estudiante_proyecto') ?? [];
                    $internacionales = $get('integrante_internacional_proyecto') ?? [];
                    
                    $totalActual = count($empleados) + count($estudiantes) + count($internacionales);
                    
                    // Verificar si hay bajas pendientes
                    $hayBajas = false;
                    if (isset($livewire->record)) {
                        $bajasPendientes = \App\Models\Proyecto\EquipoEjecutorBaja::where('proyecto_id', $livewire->record->id)
                            ->where('estado_baja', 'pendiente')
                            ->count();
                        $hayBajas = $bajasPendientes > 0;
                        
                        // Verificar si hay nuevos integrantes
                        $empleadosOriginales = $livewire->record->empleado_proyecto->count();
                        $estudiantesOriginales = $livewire->record->estudiante_proyecto->count();
                        $internacionalesOriginales = $livewire->record->integrante_internacional_proyecto->count();
                        $totalOriginal = $empleadosOriginales + $estudiantesOriginales + $internacionalesOriginales;
                        
                        return ($totalActual > $totalOriginal) || $hayBajas;
                    }
                    
                    return $totalActual > 0;
                }),

            // Sección de equipo dado de baja
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
                                
                            Textarea::make('motivo_baja')
                                ->label('Motivo de Baja')
                                ->disabled()
                                ->rows(2)
                                ->columnSpan(2),
                                
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
                                            
                                        // Refrescar el formulario
                                        $livewire->dispatch('refresh-form');
                                    }),
                            ])->columnSpanFull(),
                        ])
                        ->relationship('equipoEjecutorBajas')
                        ->addable(false)
                        ->deletable(false)
                        ->reorderable(false)
                        ->columns(4)
                        ->columnSpanFull()
                        ->defaultItems(0)
                        ->live(),
                ])
                ->columnSpanFull(),
        ];
    }
}
