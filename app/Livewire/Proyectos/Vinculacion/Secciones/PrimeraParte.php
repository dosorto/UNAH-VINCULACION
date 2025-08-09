<?php

namespace App\Livewire\Proyectos\Vinculacion\Secciones;

use App\Livewire\Proyectos\Vinculacion\Formularios\FormularioDocente;
use App\Livewire\Proyectos\Vinculacion\Formularios\FormularioEstudiante;
use App\Livewire\Proyectos\Vinculacion\Formularios\FormularioIntegranteInternacional;

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

use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Fieldset;
use Filament\Forms\Components\DatePicker;
use Illuminate\Database\Eloquent\Builder;
use Filament\Forms\Set;


class PrimeraParte
{
    public static function form(): array
    {
        return [
            TextInput::make('nombre_proyecto')
                ->label('Nombre del proyecto')
                ->minLength(2)
                ->maxLength(255)
                ->columnSpanFull()
                ->required()
                ->maxLength(255),
            Select::make('modalidad_id')
                ->label('Modalidad')
                ->columnSpanFull()
                ->relationship('modalidad','nombre')
                ->required()
                ->preload(),
            //categoria del proyecto
            Select::make('categoria')
                ->label('Categoría del Proyecto')
                ->multiple()
                ->searchable()
                ->columnSpanFull()
                ->relationship('categoria', 'nombre')
                ->required()
                ->preload(),

            Select::make('ejes_prioritarios_unah')
                ->label('Alineamiento con ejes prioritarios de la UNAH')
                ->multiple()
                ->searchable()
                ->relationship('ejes_prioritarios_unah', 'nombre')
                ->required()
                ->preload(),

           /* Forms\Components\Radio::make('ejes_prioritarios')
                ->label('Alineamiento con ejes prioritarios de la UNAH')
                ->options([
                            'Desarrollo_económico_social' => 'Desarrollo económico y social',
                            'Democracia_gobernabilidad' => 'Democracia y gobernabilidad',
                            'Población_condiciones_de_vida' => 'Población y condiciones de vida',
                            'Ambiente_biodiversidad_desarrollo' => 'Ambiente, biodiversidad y desarrollo',
                        ])
                        ->inline()
                        ->required()
                        ->columnSpanFull(),    */   

            Select::make('facultades_centros')
                ->label('Facultades o Centros')
                ->searchable()
                ->multiple()
                ->live()
                ->relationship(name: 'facultades_centros', titleAttribute: 'nombre')
                ->afterStateUpdated(function (Set $set) {
                    $set('departamentos_academicos', null);
                })
                ->required()
                ->preload(),
            Select::make('departamentos_academicos')
                ->label('Departamentos Académicos')
                ->searchable()
                ->multiple()
                ->relationship(
                    name: 'departamentos_academicos',
                    titleAttribute: 'nombre',
                    modifyQueryUsing: fn($query, Get $get) => $query->whereIn('centro_facultad_id', $get('facultades_centros'))
                )
                ->visible(fn(Get $get) => !empty($get('facultades_centros')))
                ->live()
                ->afterStateUpdated(function (Set $set) {
                    $set('carreras', null);
                })
                ->required()
                ->preload(),

            Select::make('carreras')
                ->label('Carreras')
                ->multiple()
                ->searchable()
                ->live()
                ->relationship(
                    name: 'carreras',
                    titleAttribute: 'nombre',
                    modifyQueryUsing: function ($query, Get $get) {
                        $departamentosSeleccionados = $get('departamentos_academicos');
                        $facultadesSeleccionadas = $get('facultades_centros');
                        
                        if (!empty($departamentosSeleccionados)) {
                            // Filtrar carreras que pertenecen a los departamentos seleccionados
                            return $query->whereHas('departamentosAcademicos', function ($subQuery) use ($departamentosSeleccionados) {
                                $subQuery->whereIn('departamento_academico.id', $departamentosSeleccionados);
                            });
                        }
                        
                        // Si no hay departamentos seleccionados, mostrar todas las carreras de las facultades seleccionadas
                        if (!empty($facultadesSeleccionadas)) {
                            return $query->whereIn('facultad_centro_id', $facultadesSeleccionadas);
                        }
                        
                        return $query;
                    }
                )
                ->visible(fn(Get $get) => !empty($get('departamentos_academicos')))
                ->required()
                ->preload(),



            Forms\Components\TextArea::make('programa_pertenece')
                ->label('Programa al que pertenece')
                ->minLength(2)
                ->maxLength(255)
                ->columnSpan(1)
                ->required(),

            Forms\Components\TextArea::make('lineas_investigacion_academica')
                ->label('Líneas de investigación de la unidad académica')
                ->minLength(2)
                ->maxLength(255)
                ->columnSpan(1)
                ->required(),

            // Sección de ODS y Metas
            Fieldset::make('Objetivos de Desarrollo Sostenible (ODS)')
                ->columns(1)
                ->schema([
                    Select::make('ods')
                        ->label('Objetivos de Desarrollo Sostenible')
                        ->multiple()
                        ->searchable()
                        ->relationship('ods', 'nombre')
                        ->live()
                        ->afterStateUpdated(function (Set $set, $state) {
                            // Limpiar las metas cuando cambien los ODS
                            $set('metasContribuye', []);
                        })
                        ->preload()
                        ->helperText('Seleccione los ODS a los que contribuye el proyecto')
                        ->required(),

                    Select::make('metasContribuye')
                        ->label('Metas de ODS')
                        ->multiple()
                        ->searchable()
                        ->relationship(
                            name: 'metasContribuye',
                            titleAttribute: 'descripcion',
                            modifyQueryUsing: fn($query, Get $get) => $query
                                ->whereIn('ods_id', $get('ods') ?? [])
                                ->orderBy('ods_id')
                                ->orderBy('numero_meta')
                        )
                        ->getOptionLabelFromRecordUsing(fn($record) => "Meta {$record->numero_meta}: {$record->descripcion}")
                        ->visible(fn(Get $get) => !empty($get('ods')))
                        ->preload()
                        ->helperText('Seleccione las metas específicas de los ODS que el proyecto abordará')
                        ->required(),
                ])
                ->columnSpanFull(),
               
                Fieldset::make('Fechas')
                ->columns(2)
                ->schema([
                    DatePicker::make('fecha_inicio')
                        ->label('Fecha de inicio')
                        ->columnSpan(1)
                        ->required(),
                    DatePicker::make('fecha_finalizacion')
                        ->label('Fecha de finalización')
                        ->columnSpan(1)
                        ->required(),
                  /*  DatePicker::make('evaluacion_intermedia')
                        ->label('Evaluación intermedia')
                        ->columnSpan(1)
                        ->required(),
                    DatePicker::make('evaluacion_final')
                        ->label('Evaluación final')
                        ->columnSpan(1)
                        ->required(),*/
                ])
                ->columnSpanFull()
                ->label('Fechas'),
            // actividades
        ];
    }
}
