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
                                $empleadoId = $component->getState()['empleado_id'];
                                $livewire = $component->getLivewire();
                                
                                // Obtener el proyecto_id desde el record del formulario
                                $proyectoId = $livewire->record->id ?? $livewire->getRecord()->id;
                                
                                // Crear registro en tabla de bajas
                                EquipoEjecutorBaja::create([
                                    'proyecto_id' => $proyectoId,
                                    'integrante_id' => $empleadoId,
                                    'tipo_integrante' => 'empleado',
                                    'rol_anterior' => 'Integrante',
                                    'motivo_baja' => $data['motivo_baja'],
                                    'fecha_baja' => now(),
                                    'usuario_baja_id' => auth()->id(),
                                ]);
                                
                                // Eliminar la relación empleado-proyecto para que desaparezca de la vista
                                \App\Models\Personal\EmpleadoProyecto::where('proyecto_id', $proyectoId)
                                    ->where('empleado_id', $empleadoId)
                                    ->delete();
                                
                                // Notificación
                                Notification::make()
                                    ->title('Integrante dado de baja')
                                    ->body('El integrante ha sido trasladado a la tabla de bajas.')
                                    ->success()
                                    ->send();
                                    
                                // Refrescar el formulario
                                $livewire->dispatch('refresh-form');
                            }),
                    ])->columnSpanFull(),
                ])
                ->relationship()
                ->columnSpanFull()
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
                                $estudianteId = $component->getState()['estudiante_id'];
                                $livewire = $component->getLivewire();
                                
                                // Obtener el proyecto_id desde el record del formulario
                                $proyectoId = $livewire->record->id ?? $livewire->getRecord()->id;
                                
                                // Crear registro en tabla de bajas
                                EquipoEjecutorBaja::create([
                                    'proyecto_id' => $proyectoId,
                                    'integrante_id' => $estudianteId,
                                    'tipo_integrante' => 'estudiante',
                                    'rol_anterior' => 'Estudiante',
                                    'motivo_baja' => $data['motivo_baja'],
                                    'fecha_baja' => now(),
                                    'usuario_baja_id' => auth()->id(),
                                ]);
                                
                                // Eliminar la relación estudiante-proyecto para que desaparezca de la vista
                                \App\Models\Estudiante\EstudianteProyecto::where('proyecto_id', $proyectoId)
                                    ->where('estudiante_id', $estudianteId)
                                    ->delete();

                                Notification::make()
                                    ->title('Estudiante dado de baja')
                                    ->body('El estudiante ha sido trasladado a la tabla de bajas.')
                                    ->success()
                                    ->send();
                                    
                                // Refrescar el formulario
                                $livewire->dispatch('refresh-form');
                            }),
                    ])->columnSpanFull(),
                ])
                ->label('Estudiantes')
                ->relationship()
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
                                $integranteInternacionalId = $component->getState()['integrante_internacional_id'];
                                $livewire = $component->getLivewire();
                                
                                // Obtener el proyecto_id desde el record del formulario
                                $proyectoId = $livewire->record->id ?? $livewire->getRecord()->id;
                                
                                // Crear registro en tabla de bajas
                                EquipoEjecutorBaja::create([
                                    'proyecto_id' => $proyectoId,
                                    'integrante_id' => $integranteInternacionalId,
                                    'tipo_integrante' => 'integrante_internacional',
                                    'rol_anterior' => 'Cooperante Internacional',
                                    'motivo_baja' => $data['motivo_baja'],
                                    'fecha_baja' => now(),
                                    'usuario_baja_id' => auth()->id(),
                                ]);
                                
                                // Eliminar la relación integrante-internacional-proyecto para que desaparezca de la vista
                                \App\Models\Proyecto\IntegranteInternacionalProyecto::where('proyecto_id', $proyectoId)
                                    ->where('integrante_internacional_id', $integranteInternacionalId)
                                    ->delete();

                                Notification::make()
                                    ->title('Integrante internacional dado de baja')
                                    ->body('El integrante internacional ha sido trasladado a la tabla de bajas.')
                                    ->success()
                                    ->send();
                                    
                                // Refrescar el formulario
                                $livewire->dispatch('refresh-form');
                            }),
                    ])->columnSpanFull(),
                ])
                ->relationship()
                ->columnSpanFull()
                ->defaultItems(0)
                ->itemLabel('Integrante Internacional')
                ->addActionLabel('Agregar integrante internacional')
                ->grid(2),

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
                                            \App\Models\Personal\EmpleadoProyecto::create([
                                                'proyecto_id' => $proyectoId,
                                                'empleado_id' => $record->integrante_id,
                                                'rol' => $record->rol_anterior ?? 'Integrante',
                                            ]);
                                        } elseif ($record->tipo_integrante === 'estudiante') {
                                            \App\Models\Estudiante\EstudianteProyecto::create([
                                                'proyecto_id' => $proyectoId,
                                                'estudiante_id' => $record->integrante_id,
                                                'tipo_participacion_estudiante' => 'Voluntariado', // Valor por defecto
                                            ]);
                                        } elseif ($record->tipo_integrante === 'integrante_internacional') {
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
                        ->reorderable(false)
                        ->columns(4)
                        ->columnSpanFull()
                        ->defaultItems(0),
                ])
                ->columnSpanFull(),
        ];
    }
}
