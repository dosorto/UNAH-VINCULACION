<?php

namespace App\Livewire\Proyectos\Actualizacion\Secciones;

use App\Livewire\Proyectos\Vinculacion\Formularios\FormularioDocente;
use App\Livewire\Proyectos\Vinculacion\Formularios\FormularioEstudiante;
use App\Livewire\Proyectos\Vinculacion\Formularios\FormularioIntegranteInternacional;
use App\Models\Proyecto\EquipoEjecutorBaja;

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
                        ->createOptionForm(
                            FormularioDocente::form()
                        )
                        ->createOptionUsing(function (array $data) {
                            // Crear el usuario primero
                            $user = User::create([
                                'email' => $data['email'] ?? 'temp_' . uniqid() . '@temp.com',
                                'name' => $data['nombre_completo'],
                                'password' => \Illuminate\Support\Facades\Hash::make('password123'), // Contraseña temporal
                            ]);

                            // Crear el empleado asociado al usuario
                            $empleado = \App\Models\Personal\Empleado::create([
                                'user_id' => $user->id,
                                'centro_facultad_id' => $data['centro_facultad_id'],
                                'departamento_academico_id' => $data['departamento_academico_id'],
                                'nombre_completo' => $data['nombre_completo'],
                                'sexo' => $data['sexo'],
                                'numero_empleado' => $data['numero_empleado'],
                                'categoria_id' => $data['categoria_id'],
                                'celular' => $data['celular'] ?? null,
                            ]);

                            return $empleado;
                        })
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
                ->defaultItems(0)
                ->itemLabel('Empleado')
                ->addActionLabel('Agregar empleado')
                ->grid(2),
                
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
                        ->createOptionForm(
                            FormularioEstudiante::form()
                        )
                        ->required()
                        ->createOptionUsing(function (array $data) {
                            $user = User::create([
                                'email' => $data['email'],
                                'name' => $data['nombre'] . ' ' . $data['apellido']
                            ]);
                            $user->estudiante()->create([
                                'user_id' => $user->id,
                                'nombre' => $data['nombre'],
                                'apellido' => $data['apellido'],
                                'cuenta' => $data['cuenta'],
                                'sexo' => $data['sexo'],
                                'centro_facultad_id' => $data['centro_facultad_id'],
                                'carrera_id' => $data['carrera_id'],
                            ]);
                        })
                        ->required(),
                    Select::make('tipo_participacion_estudiante')
                        ->label('Tipo de participación')
                        ->required()
                        ->options([
                            'Practica Profesional' => 'Práctica Profesional',
                            'Servicio Social o PPS' => 'Servicio Social o PPS',
                            'Voluntariado' => 'Voluntariado',
                        ])
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
                ->defaultItems(0)
                ->columnSpanFull()
                ->grid(2)
                ->addActionLabel('Agregar estudiante'),
                
            Repeater::make('integrante_internacional_proyecto')
                ->label('Integrantes de cooperación internacional')
                ->schema([
                    Select::make('integrante_internacional_id')
                        ->label('Integrante Internacional')
                        ->distinct()
                        ->searchable(['nombre_completo', 'pais', 'institucion'])
                        ->relationship(
                            name: 'integranteInternacional',
                            titleAttribute: 'nombre_completo'
                        )
                        ->getOptionLabelFromRecordUsing(fn ($record) => "{$record->nombre_completo} ({$record->pais} - {$record->institucion})")
                        ->createOptionForm(
                            FormularioIntegranteInternacional::form()
                        )
                        ->required()
                        ->createOptionUsing(function (array $data) {
                            return IntegranteInternacional::create($data);
                        }),
                    TextInput::make('rol')
                        ->label('Rol')
                        ->disabled()
                        ->required()
                        ->default('Cooperante Internacional'),
                    Hidden::make('rol')
                        ->default('Cooperante Internacional'),
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
                                    'rol_anterior' => $integranteProyecto->rol ?? 'Cooperante Internacional',
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
                ->relationship()
                ->columnSpanFull()
                ->deletable(false)
                ->defaultItems(0)
                ->itemLabel('Integrante Internacional')
                ->addActionLabel('Agregar integrante internacional')
                ->grid(2),

            
            // Motivos generales de cambios en el equipo
            Fieldset::make('Motivos de Cambios en el Equipo')
                ->schema([
                    Textarea::make('motivo_responsabilidades_nuevos')
                        ->label('Motivos del cambio de integrantes de equipo (Describa las responsabilidades de los nuevos miembros)')
                        ->placeholder('Describa las responsabilidades específicas que tendrán los nuevos miembros del equipo...')
                        ->rows(4)
                        ->columnSpanFull(),
                    
                    Textarea::make('motivo_razones_cambio')
                        ->label('Motivos del cambio de integrantes de equipo (Describa las razones por las cuales se cambia al equipo)')
                        ->placeholder('Explique las razones que justifican los cambios en la composición del equipo ejecutor...')
                        ->rows(4)
                        ->columnSpanFull(),
                ])
                ->columnSpanFull(),

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
                                                'rol' => $record->rol_anterior ?? 'Cooperante Internacional',
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
                        ->defaultItems(0),
                ])
                ->columnSpanFull(),
        ];
    }
}
