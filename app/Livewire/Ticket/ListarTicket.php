<?php

namespace App\Livewire\Ticket;

use App\Models\Ticket\Ticket;
use App\Models\Ticket\TicketSugerencia;
use Filament\Tables;
use Filament\Forms;
use Livewire\Component;
use Illuminate\Support\Facades\Auth;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Actions\Action;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Forms\Components\Grid;
use Filament\Support\Enums\IconPosition;
use Filament\Notifications\Notification;

class ListarTicket extends Component implements HasForms, HasTable
{
    use Tables\Concerns\InteractsWithTable;
    use Forms\Concerns\InteractsWithForms;

    public $ticketSeleccionado;
    public $nuevoMensaje = '';
    public ?array $data = [];
    public array $ticketsModalAbiertos = [];

    public function table(Table $table): Table
    {
        return $table
            ->query(function () {
                $user = auth()->user();

                $query = Ticket::query()
                    ->with('mensajes')
                    ->where('estado', '!=', 'cerrado')
                    ->orderByRaw("FIELD(estado, 'en proceso', 'abierto', 'cerrado')")
                    ->latest();

                if ($user && $user->empleado && $user->empleado->tipo_empleado === 'docente') {
                    $query->where('user_id', $user->id);
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
                Filter::make('filtrar_tickets')
                    ->form([
                        Grid::make(2)
                            ->schema([
                                Select::make('tipo_ticket')
                                    ->label('Tipo de Ticket')
                                    ->options([
                                        'Soporte Tecnico' => 'Soporte Técnico',
                                        'Sugerencia' => 'Sugerencia',
                                        'Consulta General' => 'Consulta General',
                                        'Otro' => 'Otro',
                                    ]),
                                Select::make('estado')
                                    ->label('Estado')
                                    ->options([
                                        'abierto' => 'Abierto',
                                        'en proceso' => 'En Proceso',
                                    ]),
                            ])
                    ])
                    ->query(function ($query, array $data) {
                        if (!empty($data['tipo_ticket'])) {
                            $query->where('tipo_ticket', $data['tipo_ticket']);
                        }
                        if (!empty($data['estado'])) {
                            $query->where('estado', $data['estado']);
                        }
                        return $query;
                    })
            ], layout: FiltersLayout::AboveContent)
            ->actions([
                Action::make('ver_mensaje')
                    ->label('Ver mensaje')
                    ->icon(fn (Ticket $record) => $this->debeMostrarIconoNuevoMensaje($record) ? 'heroicon-o-bell-alert' : 'heroicon-o-eye')
                    ->tooltip(fn (Ticket $record) => $this->debeMostrarIconoNuevoMensaje($record) ? 'Nuevo mensaje sin leer' : 'Ver mensaje')
                    ->button()
                    ->color('primary')
                    ->iconPosition(IconPosition::Before)
                    ->visible(fn (Ticket $record) => $record->mensajes && $record->mensajes->isNotEmpty())
                    ->badge(fn (Ticket $record) => $this->debeMostrarIconoNuevoMensaje($record) ? '!' : null)
                    ->badgeColor('danger')
                    ->mountUsing(fn (Ticket $record) => $this->abrirModal($record))
                    ->modalHeading('')
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel(false)
                    ->modalContent(fn ($record) =>
                            $record
                                ? view('livewire.ticket.mensajes', ['ticket' => $record->load('mensajes.user')])
                                : null
                        )
            ])
            ->headerActions([
                Action::make('ver_historial')
                    ->label('Historial de Tickets')
                    ->icon('heroicon-o-archive-box')
                    ->url(route('historialTicket'))
                    ->color('success'),

                Action::make('crear_ticket')
                    ->label('Nuevo Ticket')
                    ->icon('heroicon-o-plus')
                    ->color('info')
                    ->button()
                    ->visible(fn () => auth()->user()?->can('tickets-ver-modulo')) 
                    ->mountUsing(fn () => $this->abrirFormulario())
                    ->modalHeading('Nuevo Ticket')
                    ->modalSubmitActionLabel('Enviar')
                    ->action(fn () => $this->crearTicket())
                    ->form(fn () => $this->form(new Form($this)))
            ]);
    }

    public function abrirModal(Ticket $ticket)
    {
        $this->ticketSeleccionado = $ticket;
        $this->nuevoMensaje = '';
        $this->ticketsModalAbiertos[$ticket->id] = true;
    }

    public function enviarMensaje()
    {
        if (!$this->ticketSeleccionado || $this->nuevoMensaje === '') {
            return;
        }

        if ($this->ticketSeleccionado->estado === 'cerrado') {
            return;
        }

        $userId = Auth::id();
        $esAdmin = Auth::user()?->hasRole('admin');

        TicketSugerencia::create([
            'ticket_id' => $this->ticketSeleccionado->id,
            'user_id' => $userId,
            'mensaje' => $this->nuevoMensaje,
            'estado' => 'abierto',
        ]);

        if ($esAdmin) {
            $yaRespondioAdmin = TicketSugerencia::where('ticket_id', $this->ticketSeleccionado->id)
                ->whereHas('user.roles', function ($query) {
                    $query->where('name', 'admin');
                })
                ->where('user_id', '!=', $userId)
                ->exists();

            if (!$yaRespondioAdmin && $this->ticketSeleccionado->estado === 'abierto') {
                $this->ticketSeleccionado->estado = 'en proceso';
                $this->ticketSeleccionado->save();
            }
        }

        $this->ticketSeleccionado->refresh();
        $this->dispatch('$refresh');
        $this->nuevoMensaje = '';
    }

    public function tieneMensajesNuevos(Ticket $ticket): bool
    {
        return $ticket->mensajes->contains(function ($mensaje) {
            return $mensaje->user_id !== Auth::id();
        });
    }

    public function debeMostrarIconoNuevoMensaje(Ticket $ticket): bool
    {
        if ($ticket->estado === 'cerrado') return false;
        if (isset($this->ticketsModalAbiertos[$ticket->id])) return false;
        $ultimoMensaje = $ticket->mensajes->last();
        if (!$ultimoMensaje) return false;
        return $ultimoMensaje->user_id !== Auth::id();
    }

    public function finalizarTicket()
    {
        if ($this->ticketSeleccionado && Auth::user()?->hasRole('admin')) {
            $this->ticketSeleccionado->estado = 'cerrado';
            $this->ticketSeleccionado->save();

            Notification::make()
                ->title('Ticket Finalizado')
                ->body('El ticket ha sido movido al historial.')
                ->success()
                ->send();

            // Cerrar modal correctamente
            $this->dispatch('closeModal');

            $this->ticketSeleccionado = null;
            $this->dispatch('$refresh');
        }
    }


    public function abrirFormulario()
    {
        $this->form->fill();
    }

    public function crearTicket()
    {
        $validated = $this->form->getState();

        $ticket = Ticket::create([
            'user_id' => Auth::id(),
            'tipo_ticket' => $validated['tipo_ticket'],
            'asunto' => $validated['asunto'],
            'estado' => 'abierto',
        ]);

        TicketSugerencia::create([
            'ticket_id' => $ticket->id,
            'user_id' => Auth::id(),
            'mensaje' => $validated['mensaje'],
            'estado' => 'abierto',
        ]);

        Notification::make()
            ->title('¡Éxito!')
            ->body('Se ha creado el ticket correctamente')
            ->success()
            ->send();

        $this->data = [];
        $this->dispatch('closeModal');
        $this->dispatch('$refresh');
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Select::make('tipo_ticket')
                    ->label('Tipo de Ticket')
                    ->options([
                        'Soporte Tecnico' => 'Soporte Técnico',
                        'Sugerencia' => 'Sugerencia',
                        'Consulta General' => 'Consulta General',
                        'Otro' => 'Otro',
                    ])
                    ->required(),
                Textarea::make('asunto')
                    ->label('Asunto')
                    ->required()
                    ->rows(2),
                Textarea::make('mensaje')
                    ->label('Mensaje')
                    ->required()
                    ->rows(4),
            ])
            ->statePath('data');
    }

    public function render()
    {
        return view('livewire.ticket.listar-ticket');
    }
}
