<?php

namespace App\Livewire\Ticket;

use App\Models\Ticket\Ticket;
use Filament\Tables;
use Filament\Forms;
use Livewire\Component;
use Illuminate\Support\Facades\Auth;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Actions\Action;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Filament\Forms\Contracts\HasForms;
use Filament\Support\Enums\IconPosition;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Select;

class HistorialTicket extends Component implements HasForms, HasTable
{
    use Tables\Concerns\InteractsWithTable;
    use Forms\Concerns\InteractsWithForms;

    public $ticketSeleccionado;
    public array $ticketsModalAbiertos = [];
    public $filtroTipoTicket = null;

    public function table(Table $table): Table
    {
        return $table
            ->query(function () {
                $query = Ticket::query()
                    ->with('mensajes')
                    ->where('estado', 'cerrado')
                    ->latest();

                if (auth()->user()?->empleado?->tipo_empleado === 'docente') {
                    $query->where('user_id', auth()->id());
                }

                return $query;
            })
            ->columns([
                TextColumn::make('tipo_ticket')
                    ->label('Tipo de Ticket')
                    ->searchable()
                    ->toggleable(),
                TextColumn::make('asunto')
                    ->label('Asunto')
                    ->searchable()
                    ->toggleable(),
                TextColumn::make('estado')
                    ->label('Estado')
                    ->badge()
                    ->toggleable(),
                TextColumn::make('created_at')
                    ->label('Fecha')
                    ->dateTime('d/m/Y H:i')
                    ->toggleable(),
            ])
            ->filters([
                Filter::make('filtrar_tipo_ticket')
                    ->form([
                        Select::make('tipo_ticket')
                            ->label('Tipo de Ticket')
                            ->options([
                                'Soporte Tecnico' => 'Soporte Técnico',
                                'Sugerencia' => 'Sugerencia',
                                'Consulta General' => 'Consulta General',
                                'Otro' => 'Otro',
                            ])
                    ])
                    ->query(function ($query, array $data) {
                        if (!empty($data['tipo_ticket'])) {
                            $query->where('tipo_ticket', $data['tipo_ticket']);
                        }
                        return $query;
                    }),
            ], layout: FiltersLayout::AboveContent)

            ->actions([
                Action::make('ver_mensaje')
                    ->label('Ver mensaje')
                    ->icon('heroicon-o-eye')
                    ->button()
                    ->color('primary')
                    ->iconPosition(IconPosition::Before)
                    ->visible(fn (Ticket $record) => $record->mensajes && $record->mensajes->isNotEmpty())
                    ->modalHeading('')
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel(false)
                    ->modalContent(fn (Ticket $record) => view('livewire.ticket.mensajes', [
                        'ticket' => $record->load('mensajes.user'),
                    ])),
            ])
            ->headerActions([
                
            ]);
    }

    public function abrirModal(Ticket $ticket)
    {
        $this->ticketSeleccionado = $ticket;
        $this->ticketsModalAbiertos[$ticket->id] = true;
    }

    public function render()
    {
        return view('livewire.ticket.historial-ticket');
    }
}
