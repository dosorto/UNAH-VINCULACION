<?php

namespace App\Livewire\Proyectos\Vinculacion;

use App\Models\Proyecto\Proyecto;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Tables;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Livewire\Component;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;

use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Forms\Components\Select;

use App\Models\UnidadAcademica\FacultadCentro;
use App\Models\UnidadAcademica\DepartamentoAcademico;

use Filament\Tables\Filters\Filter;
use Filament\Forms\Get;
use Filament\Tables\Actions\Action;
use Filament\Support\Enums\MaxWidth;
use Filament\Tables\Columns\Layout\Split;
use App\Models\Estado\TipoEstado;


use pxlrbt\FilamentExcel\Actions\Tables\ExportBulkAction;
use pxlrbt\FilamentExcel\Actions\Tables\ExportAction;
use pxlrbt\FilamentExcel\Exports\ExcelExport;


class CategoriaProyectoSelector extends Component 
{
    public function render(): View
    {
        return view('livewire.proyectos.vinculacion.categorias-proyecto-selector')
            ;//->layout('components.panel.modulos.modulo-proyectos');
    }
}
