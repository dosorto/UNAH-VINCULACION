<?php

namespace App\Livewire\Configuracion\JornadaLaboral;

use App\Models\JornadaLaboral;
use App\Support\Notification;
use Illuminate\Contracts\View\View;
use Livewire\Component;
use Livewire\WithPagination;

class JornadaLaboralList extends Component
{
    use WithPagination;

    public bool $showTrashed = false;

    public bool $createModal = false;
    public string $create_hora_inicio = '';
    public string $create_hora_fin = '';
    public bool $create_activo = true;
    public int $create_orden = 0;

    public bool $editModal = false;
    public ?int $editId = null;
    public string $edit_hora_inicio = '';
    public string $edit_hora_fin = '';
    public bool $edit_activo = true;
    public int $edit_orden = 0;

    public function openCreate(): void
    {
        $this->reset(['create_hora_inicio', 'create_hora_fin', 'create_activo', 'create_orden']);
        $this->create_activo = true;
        $this->createModal = true;
    }

    public function store(): void
    {
        $this->validate([
            'create_hora_inicio' => 'required|date_format:H:i',
            'create_hora_fin'    => 'required|date_format:H:i|after:create_hora_inicio',
            'create_activo'      => 'boolean',
            'create_orden'       => 'nullable|integer|min:0',
        ]);

        JornadaLaboral::create([
            'hora_inicio' => $this->create_hora_inicio,
            'hora_fin'    => $this->create_hora_fin,
            'activo'      => $this->create_activo,
            'orden'       => $this->create_orden ?: 0,
        ]);

        $this->createModal = false;
        Notification::make()->title('Jornada laboral creada.')->success()->send();
    }

    public function openEdit(int $id): void
    {
        $jornada = JornadaLaboral::findOrFail($id);
        $this->editId           = $id;
        $this->edit_hora_inicio = substr((string) $jornada->hora_inicio, 0, 5);
        $this->edit_hora_fin    = substr((string) $jornada->hora_fin, 0, 5);
        $this->edit_activo      = $jornada->activo;
        $this->edit_orden       = $jornada->orden;
        $this->editModal        = true;
    }

    public function save(): void
    {
        $this->validate([
            'edit_hora_inicio' => 'required|date_format:H:i',
            'edit_hora_fin'    => 'required|date_format:H:i|after:edit_hora_inicio',
            'edit_activo'      => 'boolean',
            'edit_orden'       => 'nullable|integer|min:0',
        ]);

        JornadaLaboral::findOrFail($this->editId)->update([
            'hora_inicio' => $this->edit_hora_inicio,
            'hora_fin'    => $this->edit_hora_fin,
            'activo'      => $this->edit_activo,
            'orden'       => $this->edit_orden ?: 0,
        ]);

        $this->editModal = false;
        Notification::make()->title('Jornada laboral actualizada.')->success()->send();
    }

    public function delete(int $id): void
    {
        JornadaLaboral::findOrFail($id)->delete();
        Notification::make()->title('Jornada laboral eliminada.')->success()->send();
    }

    public function restore(int $id): void
    {
        JornadaLaboral::withTrashed()->findOrFail($id)->restore();
        Notification::make()->title('Jornada laboral restaurada.')->success()->send();
    }

    private function recordsQuery()
    {
        return JornadaLaboral::when($this->showTrashed, fn ($q) => $q->withTrashed())
            ->orderBy('orden')
            ->orderBy('hora_inicio');
    }

    public function render(): View
    {
        $records = $this->recordsQuery()->paginate(10);

        return view('livewire.configuracion.jornada-laboral.jornada-laboral-list', compact('records'));
    }
}
