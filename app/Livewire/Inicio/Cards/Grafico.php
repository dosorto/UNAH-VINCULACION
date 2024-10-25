<?php

namespace App\Livewire\Inicio\Cards;

use Filament\Widgets\ChartWidget;

class Grafico extends ChartWidget
{
    protected static ?string $heading = 'Chart';

    protected function getData(): array
    {
        return [
            //
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }
}
