<?php


namespace App\Livewire\Personal\Perfil;

use App\Livewire\Personal\Empleado\Formularios\FormularioEmpleado;
use Filament\Forms;
use Livewire\Component;
use Filament\Forms\Form;
use App\Models\Personal\Empleado;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Section;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Forms\Components\FileUpload;
use Filament\Actions\Contracts\HasActions;
use App\Models\Personal\FirmaSelloEmpleado;
use Filament\Forms\Components\Actions\Action;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Forms\Get;
use Filament\Forms\Set;

// importar modelo role de spatie
use Spatie\Permission\Models\Role;

use App\Models\User;


class EditPerfilDocente extends Component implements HasForms, HasActions
{
    use InteractsWithForms;
    use InteractsWithActions;

    public ?array $data = [];
    public ?array $data_firma = [];
    public User $record;

    public function mount(): void
    {
        $this->record = auth()->user();
        $this->form->fill($this->record->attributesToArray());
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([

                Section::make('Perfil de Empleado')
                    ->schema(
                        FormularioEmpleado::form(disableTipoEmpleado: true)
                    )
                    ->visible($this->record->empleado->firma()->exists())
                    // deshabilitar si el usuario no tiene el permiso de 'docente-cambiar-datos-personales'
                    ->disabled(!auth()->user()->can('cambiar-datos-personales')),


                // Section para la firma
                Section::make('Firma (Requerido)')
                    ->description('Visualizar o agregar una nueva Firma.')
                    ->model($this->record->empleado)
                    ->headerActions([
                        Action::make('create')
                            ->label('Crear Nueva Firma')
                            ->icon('heroicon-c-arrow-up-on-square')
                            ->form([
                                // ...
                                Hidden::make('empleado_id')
                                    ->default($this->record->empleado->id),
                                FileUpload::make('ruta_storage')
                                    ->label('Firma')
                                    ->disk('public')
                                    ->directory('images/firmas')
                                    ->image()
                                    ->required(),
                                Hidden::make('tipo')
                                    ->default('firma'),
                            ])
                            ->action(function (array $data) {
                                // dd($data);
                                FirmaSelloEmpleado::create($data);
                                $this->mount();
                            })
                    ])
                    ->schema([
                        // ...
                        Repeater::make('firma')
                            ->label('')
                            ->relationship('firma')
                            ->deletable(false)
                            ->schema([
                                FileUpload::make('ruta_storage')
                                    ->label('')
                                    ->disk('public')
                                    ->directory('images/firmas')
                                    ->image()
                                    ->disabled()
                                    ->nullable(),
                                Hidden::make('tipo')
                                    ->default('firma'),
                            ])
                            ->minItems(1)
                            ->maxItems(1)
                            ->addable(false)
                            ->columns(1),
                    ]),

                // Section para el sello
                Section::make('Sello (opcional)')
                    ->description('Visualizar o agregar un nuevo Sello.')
                    ->model($this->record->empleado)
                    ->headerActions([
                        Action::make('create')
                            ->label('Crear Nuevo Sello')
                            ->icon('heroicon-c-arrow-up-on-square')
                            ->form([
                                // ...
                                Hidden::make('empleado_id')
                                    ->default($this->record->empleado->id),
                                FileUpload::make('ruta_storage')
                                    ->label('Sello')
                                    ->disk('public')
                                    ->directory('images/firmas')
                                    ->image()
                                    ->required(),
                                Hidden::make('tipo')
                                    ->default('sello'),
                            ])
                            ->action(function (array $data) {
                                // dd($data);
                                FirmaSelloEmpleado::create($data);
                                $this->mount();
                            })
                    ])
                    ->schema([
                        // ...
                        Repeater::make('sello')
                            ->label('')
                            ->relationship()
                            ->deletable(false)
                            ->schema([
                                FileUpload::make('ruta_storage')
                                    ->label('')
                                    ->disk('public')
                                    ->directory('images/firmas')
                                    ->image()
                                    ->disabled()
                                    ->nullable(),
                                Hidden::make('tipo')
                                    ->default('sello'),
                            ])
                            ->minItems(0)
                            ->maxItems(1)
                            ->defaultItems(1)
                            ->addable(false)
                            ->columns(1),
                    ]),
                    

                // Section para códigos de proyectos de investigación
                Section::make('Registro de proyectos de vinculación previos al sistema')
                    ->description($this->getCodigosInvestigacionDescription())
                    ->headerActions([
                        Action::make('agregar_codigo')
                            ->label('Agregar Proyecto')
                            ->icon('heroicon-c-plus')
                            ->color('primary')
                            ->form([
                                TextInput::make('codigo_proyecto')
                                    ->label('Código del Proyecto')
                                    ->required()
                                    ->maxLength(50)
                                    ->placeholder('Ej: PI-2024-001')
                                    ->rule('unique:empleado_codigos_investigacion,codigo_proyecto,NULL,id,empleado_id,' . $this->record->empleado->id),
                                TextInput::make('nombre_proyecto')
                                    ->label('Nombre del Proyecto')
                                    ->required()
                                    ->maxLength(255)
                                    ->placeholder('Nombre descriptivo del proyecto'),
                                Select::make('rol_docente')
                                    ->label('Participación en el Proyecto')
                                    ->required()
                                    ->options([
                                        'coordinador' => 'Coordinador',
                                        'integrante' => 'Integrante',
                                    ])
                                    ->placeholder('Seleccione el tipo de participación'),
                                Textarea::make('descripcion')
                                    ->label('Descripción (Opcional)')
                                    ->rows(3)
                                    ->placeholder('Breve descripción del proyecto'),
                                Select::make('año')
                                    ->label('Año del Proyecto')
                                    ->options(collect(range(date('Y') - 10, date('Y') + 2))->mapWithKeys(fn($year) => [$year => $year]))
                                    ->default(date('Y'))
                                    ->required(),
                            ])
                            ->action(function (array $data) {
                                // Verificar si el código ya existe en la tabla proyecto
                                $proyectoExistente = \App\Models\Proyecto\Proyecto::where('codigo_proyecto', $data['codigo_proyecto'])->first();
                                
                                if ($proyectoExistente) {
                                    // Verificar si el empleado ya está registrado en ese proyecto
                                    $empleadoYaRegistrado = \App\Models\Personal\EmpleadoProyecto::where('empleado_id', $this->record->empleado->id)
                                        ->where('proyecto_id', $proyectoExistente->id)
                                        ->exists();
                                    
                                    if ($empleadoYaRegistrado) {
                                        Notification::make()
                                            ->title('Código ya registrado')
                                            ->body('Este código de proyecto ya está registrado y usted ya participa en él. No es necesario agregarlo nuevamente.')
                                            ->warning()
                                            ->send();
                                        return;
                                    } else {
                                        Notification::make()
                                            ->title('Código de proyecto existente')
                                            ->body('Este código corresponde a un proyecto existente en la base de datos. Su solicitud será verificada para confirmar su participación.')
                                            ->info()
                                            ->send();
                                    }
                                }
                                
                                try {
                                    $this->record->empleado->codigosInvestigacion()->create([
                                        'codigo_proyecto' => $data['codigo_proyecto'],
                                        'nombre_proyecto' => $data['nombre_proyecto'],
                                        'rol_docente' => $data['rol_docente'],
                                        'descripcion' => $data['descripcion'] ?? null,
                                        'año' => $data['año'],
                                        'estado_verificacion' => 'pendiente'
                                    ]);
                                    
                                    Notification::make()
                                        ->title('Código agregado')
                                        ->body('El código de investigación ha sido agregado exitosamente.')
                                        ->success()
                                        ->send();
                                        
                                    // Recargar la página para actualizar la vista
                                    return redirect()->route('mi_perfil');
                                } catch (\Exception $e) {
                                    Notification::make()
                                        ->title('Error')
                                        ->body('No se pudo agregar el código. Es posible que ya exista.')
                                        ->danger()
                                        ->send();
                                }
                            })
                    ])
                    ->schema($this->getCodigosInvestigacionSchema())
                    ->visible($this->record->empleado->tipo_empleado === 'docente')
            ])
            ->statePath('data')
            ->model($this->record);
    }

    //campos para subir los codigos de validacion de proyectos de investigacion

    protected function getCodigosInvestigacionSchema(): array
    {
        $codigos = $this->record->empleado->codigosInvestigacion;
        
        if ($codigos->isEmpty()) {
            return [
                Forms\Components\Placeholder::make('sin_codigos')
                    ->label('')
                    ->content(new \Illuminate\Support\HtmlString('
                        <div class="text-center py-8">
                            <svg class="mx-auto h-16 w-16 text-gray-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                            </svg>
                            <h3 class="text-lg font-medium text-gray-700 mb-2">No hay códigos registrados</h3>
                            <p class="text-gray-500">Usa el botón "Agregar Código" para registrar los códigos de proyectos de vinculación en los que has participado.</p>
                        </div>
                    '))
            ];
        }

        $components = [];
        
        foreach ($codigos as $codigo) {
            $estadoInfo = match($codigo->estado_verificacion) {
                'pendiente' => [
                    'icon' => 'heroicon-o-clock',
                    'label' => 'Pendiente de verificación',
                    'color' => 'warning',
                    'bg_class' => 'bg-yellow-100 border-yellow-300',
                    'text_class' => 'text-yellow-800'
                ],
                'verificado' => [
                    'icon' => 'heroicon-o-check-circle',
                    'label' => 'Verificado',
                    'color' => 'success',
                    'bg_class' => 'bg-green-100 border-green-300',
                    'text_class' => 'text-green-800'
                ],
                'rechazado' => [
                    'icon' => 'heroicon-o-x-circle',
                    'label' => 'Rechazado',
                    'color' => 'danger',
                    'bg_class' => 'bg-red-100 border-red-300',
                    'text_class' => 'text-red-800'
                ],
                default => [
                    'icon' => 'heroicon-o-question-mark-circle',
                    'label' => 'Estado desconocido',
                    'color' => 'gray',
                    'bg_class' => 'bg-gray-100 border-gray-300',
                    'text_class' => 'text-gray-800'
                ]
            };

            $components[] = Forms\Components\Card::make()
                ->schema([
                    Forms\Components\Grid::make(3)
                        ->schema([
                            Forms\Components\Placeholder::make('codigo_info')
                                ->label('Código del Proyecto')
                                ->content(new \Illuminate\Support\HtmlString("
                                    <div class='font-semibold text-lg text-gray-900'>{$codigo->codigo_proyecto}</div>
                                    <div class='text-sm text-gray-500'>Año: {$codigo->año}</div>
                                    <div class='text-sm text-gray-500'>Registrado: {$codigo->created_at->format('d/m/Y')}</div>
                                ")),
                            
                            Forms\Components\Placeholder::make('estado_badge')
                                ->label('Estado')
                                ->content(new \Illuminate\Support\HtmlString("
                                    <div class='inline-flex items-center px-3 py-1 rounded-full text-sm font-medium {$estadoInfo['bg_class']} {$estadoInfo['text_class']}'>
                                        <svg class='w-4 h-4 mr-1' fill='none' stroke='currentColor' viewBox='0 0 24 24'>
                                            " . $this->getHeroiconSvg($estadoInfo['icon']) . "
                                        </svg>
                                        {$estadoInfo['label']}
                                    </div>
                                ")),
                            
                            Forms\Components\Actions::make([
                                Forms\Components\Actions\Action::make('eliminar')
                                    ->label('Eliminar')
                                    ->icon('heroicon-o-trash')
                                    ->color('danger')
                                    ->size('sm')
                                    ->visible($codigo->estado_verificacion === 'pendiente')
                                    ->requiresConfirmation()
                                    ->modalHeading('Eliminar código')
                                    ->modalDescription('¿Está seguro de que desea eliminar este código de investigación? Esta acción no se puede deshacer.')
                                    ->modalSubmitActionLabel('Eliminar')
                                    ->action(function () use ($codigo) {
                                        $codigo->delete();
                                        Notification::make()
                                            ->title('Código eliminado')
                                            ->body('El código ha sido eliminado exitosamente.')
                                            ->success()
                                            ->send();
                                        
                                        return redirect()->route('mi_perfil');
                                    })
                            ])
                        ]),
                    
                    Forms\Components\Grid::make(1)
                        ->schema([
                            Forms\Components\Placeholder::make('detalles')
                                ->label('Detalles del Proyecto')
                                ->content(function () use ($codigo) {
                                    $html = '<div class="space-y-2">';
                                    
                                    if ($codigo->nombre_proyecto) {
                                        $html .= "<div class='text-black dark:text-white'><strong>Nombre:</strong> {$codigo->nombre_proyecto}</div>";
                                    }
                                    
                                    if ($codigo->rol_docente) {
                                        $rolLabel = match($codigo->rol_docente) {
                                            'coordinador' => 'Coordinador',
                                            'integrante' => 'Integrante',
                                            default => $codigo->rol_docente
                                        };
                                        $html .= "<div class='text-black dark:text-white'><strong>Rol del Docente:</strong> {$rolLabel}</div>";
                                    }
                                    
                                    if ($codigo->descripcion) {
                                        $html .= "<div><strong>Descripción:</strong> {$codigo->descripcion}</div>";
                                    }
                                    
                                    if ($codigo->observaciones_admin) {
                                        $html .= "<div class='mt-3 p-3 bg-blue-50 border border-blue-200 rounded'>";
                                        $html .= "<strong class='text-blue-800'>Observaciones del administrador:</strong><br>";
                                        $html .= "<span class='text-blue-700'>{$codigo->observaciones_admin}</span>";
                                        $html .= "</div>";
                                    }
                                    
                                    if ($codigo->verificadoPor) {
                                        $html .= "<div class='mt-3 p-3 bg-green-50 border border-green-200 rounded'>";
                                        $html .= "<strong class='text-green-800'>Verificado por:</strong> {$codigo->verificadoPor->nombre_completo}<br>";
                                        $html .= "<strong class='text-green-800'>Fecha:</strong> {$codigo->fecha_verificacion->format('d/m/Y H:i')}";
                                        $html .= "</div>";
                                    }
                                    
                                    $html .= '</div>';
                                    
                                    return new \Illuminate\Support\HtmlString($html);
                                })
                        ])
                ])
                ->extraAttributes([
                    'class' => 'shadow-sm border-0 bg-transparent'
                ]);
        }
        
        return $components;
    }

    protected function getCodigosInvestigacionDescription(): string
    {
        $codigos = $this->record->empleado->codigosInvestigacion;
        $total = $codigos->count();
        
        if ($total === 0) {
            return 'Registre los códigos de proyectos de vinculación en los que ha participado. Incluya su nombre, rol y descripción para verificación administrativa.';
        }
        
        $pendientes = $codigos->where('estado_verificacion', 'pendiente')->count();
        $verificados = $codigos->where('estado_verificacion', 'verificado')->count();
        $rechazados = $codigos->where('estado_verificacion', 'rechazado')->count();
        
        $description = "Resumen de códigos: Total: {$total}";
        
        if ($pendientes > 0) {
            $description .= " | Pendientes: {$pendientes}";
        }
        if ($verificados > 0) {
            $description .= " | Verificados: {$verificados}";
        }
        if ($rechazados > 0) {
            $description .= " | Rechazados: {$rechazados}";
        }
        
        return $description;
    }

    protected function getHeroiconSvg(string $icon): string
    {
        return match($icon) {
            'heroicon-o-clock' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 6v6l4 2m6-2a9 9 0 11-18 0 9 9 0 0118 0z"/>',
            'heroicon-o-check-circle' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>',
            'heroicon-o-x-circle' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"/>',
            'heroicon-o-question-mark-circle' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>',
            default => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 9v3m0 0v3m0-3h3m-3 0H9m12 0a9 9 0 11-18 0 9 9 0 0118 0z"/>',
        };
    }

    public function save()
    {

        $data = $this->form->getState();

        // validar que el docente tenga almenos una firma y un sello


        $this->record->update($data);

        if ($this->record->empleado->tipo_empleado === 'docente') {
            $this->record->assignRole('docente')->save();
            $this->record->active_role_id =  Role::where('name', 'docente')->first()->id;
        }


        // quitar el permiso de 'configuracion-admin-mi-perfil al usuario
        $this->record->revokePermissionTo('cambiar-datos-personales');
        $this->record->save();

        Notification::make()
            ->title('Exito!')
            ->body('Perfil  actualizado correctamente.')
            ->success()
            ->send();
        return redirect()->route('inicio');
    }

    public function render(): View
    {
        return view('livewire.personal.perfil.edit-perfil-docente', [
            'record' => $this->record,
        ]);
    }
}
