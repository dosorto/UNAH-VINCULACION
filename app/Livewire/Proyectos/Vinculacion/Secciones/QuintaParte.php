<?php

namespace  App\Livewire\Proyectos\Vinculacion\Secciones;


use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\FileUpload;

use Filament\Forms\Components\Select;
class QuintaParte
{
    public static function form(): array
    {
        return [

            Select::make('categoria_id')
                ->label('Categoría')
                ->multiple()
                ->relationship(name: 'categoria', titleAttribute: 'nombre')
                ->required()
                ->preload(),

            Repeater::make('anexos')
                ->label('Anexos')
                ->schema([
                    FileUpload::make('documento_url')
                        ->label('')
                        ->disk('public')
                        ->directory('anexos')
                        ->required()
                ])
                ->relationship()
                ->defaultItems(0)
                ->grid(2)
                ->columns(1),
        ];
    }
}
