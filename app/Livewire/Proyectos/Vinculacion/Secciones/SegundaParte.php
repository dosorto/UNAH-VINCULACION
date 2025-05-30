<?php

namespace App\Livewire\Proyectos\Vinculacion\Secciones;

use Filament\Forms;

use Filament\Forms\Components\Toggle;

use Filament\Forms\Components\Repeater;

use Filament\Forms\Components\FileUpload;


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
                        ->columnSpan(1)
                        ->required(),
                    Toggle::make('es_internacional')
                        ->inline(false)
                        ->label('Internacional')
                        ->default(false)
                        ->columnSpan(1),
                    Forms\Components\TextInput::make('nombre_contacto')
                        ->label('Nombre del contacto')
                        ->minLength(2)
                        ->maxLength(255)
                        ->columnSpan(1)
                        ->required(),
                    Forms\Components\TextInput::make('telefono')
                        ->minLength(2)
                        ->maxLength(255)
                        ->label('Telefono')
                        ->columnSpan(1)
                        ->required(),
                    Forms\Components\TextInput::make('correo')
                        ->minLength(2)
                        ->maxLength(255)
                        ->label('Correo de contacto')
                        ->columnSpanFull()
                        ->required(),
                    Repeater::make('instrumento_formalizacion')
                        ->schema([
                            FileUpload::make('documento_url')
                                ->label('Documentos de Formalizacion')
                                ->disk('public')
                                ->directory('instrumento_formalizacion')
                                ->required()
                        ])
                        ->label('Instrumentos de formalización')
                        ->columnSpanFull()
                        ->relationship(),
                    Forms\Components\TextInput::make('aporte')
                        ->minLength(2)
                        ->maxLength(255)
                        ->label('Aporte')
                        ->columnSpan(1)
                        ->required()
                        ->default(0),


                ])
                ->label('Entidades contraparte')
                ->relationship()
                ->itemLabel('Entidad contraparte')
                ->columns(2)
                ->defaultItems(0)
                ->addActionLabel('Agregar entidad contraparte')
        ];
    }
}
