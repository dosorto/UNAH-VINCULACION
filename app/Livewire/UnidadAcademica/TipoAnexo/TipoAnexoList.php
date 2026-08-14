<?php

namespace App\Livewire\UnidadAcademica\TipoAnexo;

use App\Models\Proyecto\TipoAnexo;
use App\Support\Notification;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;
use Livewire\Component;
use Livewire\WithPagination;

class TipoAnexoList extends Component
{
    use WithPagination;

    public string $search = '';

    public bool $showTrashed = false;

    public bool $createModal = false;

    public string $create_codigo = '';

    public string $create_nombre = '';

    public bool $create_requiere_detalle = false;

    public bool $create_activo = true;

    public $create_orden = 0;

    public bool $editModal = false;

    public ?int $editId = null;

    public string $edit_codigo = '';

    public string $edit_nombre = '';

    public bool $edit_requiere_detalle = false;

    public bool $edit_activo = true;

    public $edit_orden = 0;

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingShowTrashed(): void
    {
        $this->resetPage();
    }

    public function openCreate(): void
    {
        $this->reset([
            'create_codigo',
            'create_nombre',
            'create_requiere_detalle',
            'create_activo',
            'create_orden',
        ]);

        $this->create_activo = true;
        $this->create_orden = (int) TipoAnexo::max('orden') + 1;
        $this->resetValidation();
        $this->createModal = true;
    }

    public function store(): void
    {
        $this->create_codigo = $this->normalizeCodigo($this->create_codigo);

        $validated = $this->validate([
            'create_codigo' => ['required', 'string', 'max:80', 'regex:/^[a-z0-9_]+$/', 'unique:tipos_anexo,codigo'],
            'create_nombre' => ['required', 'string', 'max:255'],
            'create_requiere_detalle' => ['boolean'],
            'create_activo' => ['boolean'],
            'create_orden' => ['required', 'integer', 'min:0', 'max:65535'],
        ], [], [
            'create_codigo' => 'código',
            'create_nombre' => 'nombre',
            'create_requiere_detalle' => 'requiere detalle',
            'create_activo' => 'activo',
            'create_orden' => 'orden',
        ]);

        TipoAnexo::create([
            'codigo' => $validated['create_codigo'],
            'nombre' => trim($validated['create_nombre']),
            'requiere_detalle' => $validated['create_requiere_detalle'],
            'activo' => $validated['create_activo'],
            'orden' => (int) $validated['create_orden'],
        ]);

        $this->createModal = false;
        Notification::make()->title('Tipo de anexo creado.')->success()->send();
    }

    public function openEdit(int $id): void
    {
        $tipo = TipoAnexo::findOrFail($id);

        $this->editId = $tipo->id;
        $this->edit_codigo = $tipo->codigo;
        $this->edit_nombre = $tipo->nombre;
        $this->edit_requiere_detalle = $tipo->requiere_detalle;
        $this->edit_activo = $tipo->activo;
        $this->edit_orden = $tipo->orden;
        $this->resetValidation();
        $this->editModal = true;
    }

    public function save(): void
    {
        $tipo = TipoAnexo::findOrFail($this->editId);
        $esTipoBase = in_array($tipo->codigo, array_column(TipoAnexo::TIPOS_BASE, 'codigo'), true);

        $validated = $this->validate([
            'edit_nombre' => ['required', 'string', 'max:255'],
            'edit_requiere_detalle' => ['boolean'],
            'edit_activo' => ['boolean'],
            'edit_orden' => ['required', 'integer', 'min:0', 'max:65535'],
        ], [], [
            'edit_nombre' => 'nombre',
            'edit_requiere_detalle' => 'requiere detalle',
            'edit_activo' => 'activo',
            'edit_orden' => 'orden',
        ]);

        $tipo->update([
            'nombre' => trim($validated['edit_nombre']),
            'requiere_detalle' => $esTipoBase
                ? $tipo->codigo === TipoAnexo::CODIGO_OTROS
                : $validated['edit_requiere_detalle'],
            'activo' => $esTipoBase ? true : $validated['edit_activo'],
            'orden' => (int) $validated['edit_orden'],
        ]);

        $this->editModal = false;
        Notification::make()->title('Tipo de anexo actualizado.')->success()->send();
    }

    public function delete(int $id): void
    {
        $tipo = TipoAnexo::findOrFail($id);

        if (in_array($tipo->codigo, array_column(TipoAnexo::TIPOS_BASE, 'codigo'), true)) {
            Notification::make()
                ->title('No se puede eliminar el tipo institucional')
                ->body('Los cuatro tipos requeridos por la ficha deben permanecer disponibles.')
                ->warning()
                ->send();

            return;
        }

        if ($tipo->anexos()->exists()) {
            Notification::make()
                ->title('No se puede eliminar el tipo de anexo')
                ->body('Está asignado a uno o más anexos. Puede desactivarlo para impedir nuevas cargas.')
                ->warning()
                ->send();

            return;
        }

        $tipo->delete();
        Notification::make()->title('Tipo de anexo eliminado.')->success()->send();
    }

    public function restore(int $id): void
    {
        TipoAnexo::withTrashed()->findOrFail($id)->restore();
        Notification::make()->title('Tipo de anexo restaurado.')->success()->send();
    }

    private function normalizeCodigo(string $codigo): string
    {
        return (string) Str::of($codigo)
            ->ascii()
            ->lower()
            ->replaceMatches('/[^a-z0-9]+/', '_')
            ->trim('_');
    }

    private function recordsQuery(): Builder
    {
        return TipoAnexo::query()
            ->withCount('anexos')
            ->when($this->showTrashed, fn (Builder $query) => $query->withTrashed())
            ->when(trim($this->search) !== '', function (Builder $query): void {
                $term = '%'.trim($this->search).'%';
                $query->where(function (Builder $nested) use ($term): void {
                    $nested->where('nombre', 'like', $term)
                        ->orWhere('codigo', 'like', $term);
                });
            })
            ->orderBy('orden')
            ->orderBy('nombre');
    }

    public function render(): View
    {
        $records = $this->recordsQuery()->paginate(10);

        return view('livewire.unidad-academica.tipo-anexo.tipo-anexo-list', compact('records'));
    }
}
