<?php

namespace App\Livewire\Ticket;

use App\Models\Ticket\Ticket;
use App\Models\Ticket\TicketSugerencia;
use App\Support\Notification;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\WithPagination;

class ListarTicket extends Component
{
    use WithPagination;

    public string $search = '';
    public string $filtroTipo = '';
    public string $filtroEstado = '';

    public bool $createModal = false;
    public string $create_tipo_ticket = '';
    public string $create_asunto = '';
    public string $create_mensaje = '';

    public bool $viewModal = false;
    public ?int $viewTicketId = null;
    public string $nuevoMensaje = '';

    public array $ticketsModalAbiertos = [];

    public function updatingSearch(): void { $this->resetPage(); }

    public function openCreate(): void
    {
        $this->reset(['create_tipo_ticket', 'create_asunto', 'create_mensaje']);
        $this->createModal = true;
    }

    public function crearTicket(): void
    {
        $this->validate([
            'create_tipo_ticket' => 'required|string',
            'create_asunto'      => 'required|string|max:500',
            'create_mensaje'     => 'required|string',
        ]);

        $ticket = Ticket::create([
            'user_id'     => Auth::id(),
            'tipo_ticket' => $this->create_tipo_ticket,
            'asunto'      => $this->create_asunto,
            'estado'      => 'abierto',
        ]);

        TicketSugerencia::create([
            'ticket_id' => $ticket->id,
            'user_id'   => Auth::id(),
            'mensaje'   => $this->create_mensaje,
            'estado'    => 'abierto',
        ]);

        $this->createModal = false;
        Notification::make()->title('Ticket creado correctamente.')->success()->send();
    }

    public function openView(int $id): void
    {
        $this->viewTicketId = $id;
        $this->nuevoMensaje = '';
        $this->ticketsModalAbiertos[$id] = true;
        $this->viewModal = true;
    }

    public function enviarMensaje(): void
    {
        if (!$this->viewTicketId || $this->nuevoMensaje === '') return;

        $ticket = Ticket::find($this->viewTicketId);
        if (!$ticket || $ticket->estado === 'cerrado') return;

        TicketSugerencia::create([
            'ticket_id' => $ticket->id,
            'user_id'   => Auth::id(),
            'mensaje'   => $this->nuevoMensaje,
            'estado'    => 'abierto',
        ]);

        $esAdmin = Auth::user()?->can('tickets.administrar');
        if ($esAdmin && $ticket->estado === 'abierto') {
            $ticket->update(['estado' => 'en proceso']);
        }

        $this->nuevoMensaje = '';
    }

    public function finalizarTicket(): void
    {
        if (!$this->viewTicketId || !Auth::user()?->can('tickets.administrar')) return;

        Ticket::findOrFail($this->viewTicketId)->update(['estado' => 'cerrado']);

        Notification::make()->title('Ticket finalizado.')->success()->send();
        $this->viewModal = false;
        $this->viewTicketId = null;
    }

    public function debeMostrarAlerta(Ticket $ticket): bool
    {
        if ($ticket->estado === 'cerrado') return false;
        if (isset($this->ticketsModalAbiertos[$ticket->id])) return false;
        $ultimo = $ticket->mensajes->last();
        return $ultimo && $ultimo->user_id !== Auth::id();
    }

    public function render(): View
    {
        $user = Auth::user();
        $query = Ticket::with('mensajes')
            ->where('estado', '!=', 'cerrado')
            ->when(!$user->can('tickets.administrar'), fn($q) => $q->where('user_id', $user->id))
            ->when($this->filtroTipo,   fn($q) => $q->where('tipo_ticket', $this->filtroTipo))
            ->when($this->filtroEstado, fn($q) => $q->where('estado', $this->filtroEstado))
            ->latest();

        $ticket = $this->viewTicketId ? Ticket::with('mensajes.user')->find($this->viewTicketId) : null;

        return view('livewire.ticket.listar-ticket', [
            'records' => $query->paginate(15),
            'ticket'  => $ticket,
        ]);
    }
}