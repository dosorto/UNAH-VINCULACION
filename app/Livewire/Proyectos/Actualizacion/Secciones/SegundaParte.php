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

class SegundaParte
{
    public static function form(): array
    {
        return [
            // Sección de extensión de tiempo de ejecución
            Fieldset::make('Extensión de tiempo de ejecución del proyecto')
                ->schema([
                    TextInput::make('fecha_finalizacion_actual')
                        ->label('Fecha de finalización actual del proyecto')
                        ->disabled()
                        ->dehydrated(false)
                        ->helperText('Esta es la fecha de finalización que tenía el proyecto al momento de crear esta ficha')
                        ->formatStateUsing(function ($livewire) {
                            // Si estamos editando una ficha existente, mostrar la fecha guardada
                            if (isset($livewire->record) && $livewire->record instanceof \App\Models\Proyecto\FichaActualizacion && $livewire->record->fecha_finalizacion_actual) {
                                return \Carbon\Carbon::parse($livewire->record->fecha_finalizacion_actual)->format('d/m/Y');
                            }
                            // Si estamos creando una nueva ficha, mostrar la fecha actual del proyecto
                            elseif (isset($livewire->record) && $livewire->record instanceof \App\Models\Proyecto\Proyecto && $livewire->record->fecha_finalizacion) {
                                return \Carbon\Carbon::parse($livewire->record->fecha_finalizacion)->format('d/m/Y');
                            }
                            return 'No definida';
                        })
                        ->columnSpan(1),

                    DatePicker::make('fecha_ampliacion')
                        ->label('Nueva fecha de finalización')
                        ->helperText('Seleccione la nueva fecha de finalización del proyecto')
                        ->displayFormat('d/m/Y')
                        ->columnSpan(1),

                    Textarea::make('motivo_ampliacion')
                        ->label('Motivos por los cuales se extiende la fecha de ejecución del proyecto')
                        ->helperText('Incluir las fechas de evaluación del proyecto')
                        ->rows(6)
                        ->columnSpanFull(),
                ])
                ->columns(2)
                ->columnSpanFull(),
        ];
    }
}
