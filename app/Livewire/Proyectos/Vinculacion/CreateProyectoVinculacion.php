<?php

namespace App\Livewire\Proyectos\Vinculacion;

use App\Models\Proyecto\Proyecto;
use Filament\Forms;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Livewire\Component;
use Illuminate\Contracts\View\View;

class CreateProyectoVinculacion extends Component implements HasForms
{
    use InteractsWithForms;

    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill();
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('nombre_proyecto')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('carrera_facultad_centro_id')
                    ->numeric(),
                Forms\Components\TextInput::make('entidad_academica_id')
                    ->numeric(),
                Forms\Components\TextInput::make('coordinador_id')
                    ->required()
                    ->numeric(),
                Forms\Components\TextInput::make('ods_id')
                    ->required()
                    ->numeric(),
                Forms\Components\TextInput::make('modalidad_id')
                    ->required()
                    ->numeric(),
                Forms\Components\TextInput::make('categoria_id')
                    ->required()
                    ->numeric(),
                Forms\Components\TextInput::make('municipio_id')
                    ->required()
                    ->numeric(),
                Forms\Components\TextInput::make('departamento_id')
                    ->required()
                    ->numeric(),
                Forms\Components\TextInput::make('ciudad_id')
                    ->required()
                    ->numeric(),
                Forms\Components\TextInput::make('aldea_id')
                    ->required()
                    ->numeric(),
                Forms\Components\TextInput::make('resumen')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('objetivo_general')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('objetivos_especificos')
                    ->required()
                    ->maxLength(255),
                Forms\Components\DatePicker::make('fecha_inicio')
                    ->required(),
                Forms\Components\DatePicker::make('fecha_finalizacion')
                    ->required(),
                Forms\Components\DatePicker::make('evaluacion_intermedia')
                    ->required(),
                Forms\Components\DatePicker::make('evaluacion_final')
                    ->required(),
                Forms\Components\TextInput::make('poblacion_participante')
                    ->required()
                    ->numeric(),
                Forms\Components\TextInput::make('modalidad_ejecucion')
                    ->required(),
                Forms\Components\TextInput::make('resultados_esperados')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('indicadores_medicion_resultados')
                    ->required()
                    ->maxLength(255),
            ])
            ->statePath('data')
            ->model(Proyecto::class);
    }

    public function create(): void
    {
        $data = $this->form->getState();

        $record = Proyecto::create($data);

        $this->form->model($record)->saveRelationships();
    }

    public function render(): View
    {
        return view('livewire.proyectos.vinculacion.create-proyecto-vinculacion')
        ->layout('components.panel.modulos.modulo-proyectos');
    }
}