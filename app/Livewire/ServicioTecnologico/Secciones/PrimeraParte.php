<?php

namespace App\Livewire\ServicioTecnologico\Secciones;

use App\Livewire\Proyectos\Vinculacion\Formularios\FormularioDocente;
use App\Livewire\Proyectos\Vinculacion\Formularios\FormularioEstudiante;
use App\Livewire\Proyectos\Vinculacion\Formularios\FormularioIntegranteInternacional;

use Filament\Forms;
use App\Models\User;
use Filament\Forms\Get;

use App\Models\Personal\Empleado;

use App\Models\Estudiante\Estudiante;
use App\Models\UnidadAcademica\FacultadCentro;
use App\Models\UnidadAcademica\DepartamentoAcademico;
use App\Models\UnidadAcademica\Carrera;
use App\Models\Demografia\Municipio;
use App\Models\Demografia\Departamento;
use App\Models\Demografia\Ciudad;
use App\Models\Demografia\Aldea;

use App\Models\Proyecto\Modalidad;
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
            TextInput::make('nombre_accion')
                ->label('Nombre de la acción')
                ->minLength(2)
                ->maxLength(255)
                ->columnSpanFull()
                ->required()
                ->maxLength(255),
            
            Select::make('centrosFacultades')
                ->label('Facultades o Centros')
                ->searchable()
                ->multiple()
                ->live()
                ->relationship(name: 'centrosFacultades', titleAttribute: 'nombre')
                ->afterStateUpdated(function (Set $set) {
                    $set('departamentos_academicos', null);
                })
                ->required()
                ->preload(),
            Select::make('departamentosAcademicos')
                ->label('Departamentos Académicos')
                ->searchable()
                ->multiple()
                ->relationship(
                    name: 'departamentosAcademicos',
                    titleAttribute: 'nombre',
                    modifyQueryUsing: fn($query, Get $get) => $query->whereIn('centro_facultad_id', $get('centrosFacultades'))
                )
                ->visible(fn(Get $get) => !empty($get('centrosFacultades')))
                ->live()
                ->required()
                ->preload(),
            Select::make('modalidad_id')
                ->label('Modalidad')
                ->columnSpanFull()
                ->relationship(name: 'modalidad', titleAttribute: 'nombre')
                ->required()
                ->preload(),
                          
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
                ])
                ->columnSpanFull()
                ->label('Fechas'),

        ];
    }
}
