<?php

namespace App\Livewire\Proyectos\Vinculacion;

use App\Models\Proyecto\DocumentoProyecto;
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

use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Tables\Columns\TextColumn;

use Filament\Tables\Columns\SelectColumn;

use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Hidden;

class VerDocumentosProyectoList extends Component implements HasForms, HasTable
{
    use InteractsWithForms;
    use InteractsWithTable;

    public Proyecto $proyecto;

    public function mount(Proyecto $proyecto): void
    {
        $this->proyecto = $proyecto;
    }


    public function table(Table $table): Table
    {
     
        return $table
            ->query(
                $this->proyecto
                    ->documentos()
                    ->getQuery()
            )
            ->columns([
                TextColumn::make('tipo_documento')
                    ->label('Tipo de Documento'),
            ])
            ->filters([
                //
            ])
            ->actions([
                //
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    //
                ]),
            ])
            ->headerActions([
                    
            ]);
    }

    public function render(): View
    {
        return view('livewire.proyectos.vinculacion.ver-documentos-proyecto-list');
    }
}
