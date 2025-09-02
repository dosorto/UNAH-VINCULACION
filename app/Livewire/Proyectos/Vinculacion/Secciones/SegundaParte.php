<?php

namespace App\Livewire\Proyectos\Vinculacion\Secciones;

use Filament\Forms;

use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;

use Filament\Forms\Components\Repeater;

use Filament\Forms\Components\FileUpload;
use Illuminate\Validation\Rules\Numeric;


class SegundaParte
{
    public static function form(): array
    {

        return  [
            Repeater::make('entidad_contraparte')
                ->schema([
                    Forms\Components\TextInput::make('nombre')
                        ->minLength(2)
                        ->maxLength(255)
                        ->label('Nombre de la entidad')
                        ->columnSpan(2)
                        ->required(),
                    Forms\Components\Radio::make('tipo_entidad')
                        ->label('Tipo de Contraparte')
                        ->options([
                            'internacional' => 'Internacional',
                            'gobierno_nacional' => 'Gobierno Nacional',
                            'gobierno_municipal' => 'Gobierno Municipal',
                            'ong' => 'ONG',
                            'sociedad_civil' => 'Sociedad civil organizada',
                            'sector_privado' => 'Sector Privado',
                        ])
                        ->inline()
                        ->required()
                        ->columnSpanFull(),
                    Forms\Components\TextInput::make('nombre_contacto')
                        ->label('Nombre del contacto')
                        ->minLength(2)
                        ->maxLength(255)
                        ->columnSpan(1)
                        ->required(),
                    Forms\Components\TextInput::make('cargo_contacto')
                        ->minLength(2)
                        ->maxLength(255)
                        ->label('Cargo del contacto')
                        ->columnSpan(1)
                        ->required(),
                    Forms\Components\TextInput::make('telefono')
                        ->numeric()
                        ->label('Telefono')
                        ->columnSpan(1)
                        ->required(),
                    Forms\Components\TextInput::make('correo')
                        ->minLength(2)
                        ->maxLength(255)
                        ->label('Correo de contacto')
                        ->columnSpan(1)
                        ->required()
                        ->email(),
                    
                    Forms\Components\Textarea::make('descripcion_acuerdos')
                        ->minLength(2)
                        ->maxLength(255)
                        ->label('Breve descripción de los compromisos asumidos por la contraparte')
                        ->columnSpanFull()
                        ->required(),

                    Repeater::make('instrumento_formalizacion')
                        ->schema([
                            Forms\Components\Select::make('tipo_documento')
                                ->label('Tipo de Documento')
                                ->options([
                                    'carta_formal_solicitud' => 'Carta formal de solicitud a la unidad académica',
                                    'carta_intenciones' => 'Carta de intenciones con la UNAH',
                                    'convenio_marco' => 'Convenio marco con la UNAH',
                                ])
                                ->required()
                                ->columnSpan(1),
                            FileUpload::make('documento_url')
                                ->label('Archivo del Documento')
                                ->disk('public')
                                ->directory('instrumento_formalizacion')
                                ->acceptedFileTypes(['application/pdf', 'image/jpeg', 'image/png', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'])
                                ->maxSize(10240) // 10MB
                                ->required()
                                ->columnSpan(1)
                        ])
                        ->label('Instrumentos de formalización')
                        ->columnSpanFull()
                        ->relationship()
                        ->columns(2)
                        ->defaultItems(0)
                        ->addActionLabel('Agregar documento'),

                ])
                ->label('Entidades contraparte')
                ->relationship()
                ->itemLabel('Entidad contraparte')
                ->columns(2)
                ->defaultItems(1)
                ->minItems(1)
                ->deletable(true)
                ->addActionLabel('Agregar otra entidad contraparte')
        ];
    }
}
