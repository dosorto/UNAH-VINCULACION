<?php

namespace App\Livewire\Ticket;

use App\Models\Ticket\Ticket;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\WithPagination;

class HistorialTicket extends Component
{
    use WithPagination;

    public string $filtroTipo = '';
    public bool $viewModal = false;
    public ?int $viewTicketId = null;

    public function openView(int $id): void
    {
        $this->viewTicketId = $id;
        $this->viewModal    = true;
    }

    public function render(): View
    {
        $user = Auth::user();
        $records = Ticket::with('mensajes')
            ->where('estado', 'cerrado')
            ->when($user->empleado?->tipo_empleado === 'docente', fn($q) => $q->where('user_id', $user->id))
            ->when($this->filtroTipo, fn($q) => $q->where('tipo_ticket', $this->filtroTipo))
            ->latest()
            ->paginate(15);

        $ticket = $this->viewTicketId ? Ticket::with('mensajes.user')->find($this->viewTicketId) : null;

        return view('livewire.ticket.historial-ticket', compact('records', 'ticket'));
    }
}