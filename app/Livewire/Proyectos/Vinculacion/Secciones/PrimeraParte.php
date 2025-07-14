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
                ->relationship(name: 'modalidad', titleAttribute: 'nombre')
                ->required()
                ->preload(),
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
                ->required()
                ->preload(),
            // actividades
        ];
    }
}
