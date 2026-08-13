<?php

namespace App\Livewire\UnidadAcademica\Categoria;

use App\Models\Personal\CategoriaEmpleado;
use App\Support\AdminCsv;
use App\Support\Notification;
use Illuminate\Contracts\View\View;
use Illuminate\Validation\Rule;
use Livewire\Component;
use Livewire\WithPagination;

class CategoriaList extends Component
{
    use WithPagination;

    public string $search = '';

    public bool $showTrashed = false;

    public bool $createModal = false;

    public string $createNombre = '';

    public string $createDescripcion = '';

    public bool $editModal = false;

    public ?int $editId = null;

    public string $editNombre = '';

    public string $editDescripcion = '';

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
        $this->resetValidation();
        $this->reset(['createNombre', 'createDescripcion']);
        $this->createModal = true;
    }

    public function store(): void
    {
        $this->createNombre = trim($this->createNombre);
        $this->createDescripcion = trim($this->createDescripcion);

        $this->validate([
            'createNombre' => ['required', 'string', 'max:255', Rule::unique('categoria', 'nombre')],
            'createDescripcion' => ['nullable', 'string', 'max:255'],
        ], [], [
            'createNombre' => 'nombre',
            'createDescripcion' => 'descripción',
        ]);

        CategoriaEmpleado::create([
            'nombre' => $this->createNombre,
            'descripcion' => $this->createDescripcion ?: null,
        ]);

        $this->createModal = false;
        Notification::make()->title('Categoría creada.')->success()->send();
    }

    public function openEdit(int $id): void
    {
        $categoria = CategoriaEmpleado::findOrFail($id);

        $this->resetValidation();
        $this->editId = $categoria->id;
        $this->editNombre = (string) $categoria->nombre;
        $this->editDescripcion = (string) ($categoria->descripcion ?? '');
        $this->editModal = true;
    }

    public function save(): void
    {
        $categoria = CategoriaEmpleado::findOrFail($this->editId);
        $this->editNombre = trim($this->editNombre);
        $this->editDescripcion = trim($this->editDescripcion);

        $this->validate([
            'editNombre' => [
                'required',
                'string',
                'max:255',
                Rule::unique('categoria', 'nombre')->ignore($categoria->id),
            ],
            'editDescripcion' => ['nullable', 'string', 'max:255'],
        ], [], [
            'editNombre' => 'nombre',
            'editDescripcion' => 'descripción',
        ]);

        $categoria->update([
            'nombre' => $this->editNombre,
            'descripcion' => $this->editDescripcion ?: null,
        ]);

        $this->editModal = false;
        Notification::make()->title('Categoría actualizada.')->success()->send();
    }

    public function delete(int $id): void
    {
        $categoria = CategoriaEmpleado::findOrFail($id);

        if ($categoria->empleados()->exists()) {
            Notification::make()
                ->title('No se puede eliminar la categoría')
                ->body('La categoría está asignada a uno o más empleados.')
                ->warning()
                ->send();

            return;
        }

        $categoria->delete();
        Notification::make()->title('Categoría eliminada.')->success()->send();
    }

    public function restore(int $id): void
    {
        CategoriaEmpleado::withTrashed()->findOrFail($id)->restore();
        Notification::make()->title('Categoría restaurada.')->success()->send();
    }

    public function exportExcel()
    {
        return AdminCsv::download('categorias-empleado-'.now()->format('Y-m-d').'.csv', [
            'Nombre',
            'Descripción',
            'Empleados asignados',
            'Estado',
        ], function () {
            foreach ($this->recordsQuery()->lazy() as $categoria) {
                yield [
                    $categoria->nombre,
                    $categoria->descripcion,
                    $categoria->empleados_count,
                    $categoria->trashed() ? 'Eliminada' : 'Activa',
                ];
            }
        });
    }

    private function recordsQuery()
    {
        return CategoriaEmpleado::query()
            ->when($this->showTrashed, fn ($query) => $query->withTrashed())
            ->withCount('empleados')
            ->when($this->search, fn ($query) => $query->where(fn ($searchQuery) => $searchQuery
                ->where('nombre', 'like', '%'.$this->search.'%')
                ->orWhere('descripcion', 'like', '%'.$this->search.'%')
            ))
            ->orderBy('nombre');
    }

    public function render(): View
    {
        return view('livewire.unidad-academica.categoria.categoria-list', [
            'records' => $this->recordsQuery()->paginate(10),
        ]);
    }
}
