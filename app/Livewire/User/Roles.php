<?php

namespace App\Livewire\User;

use App\Support\Notification;
use Illuminate\Contracts\View\View;
use Livewire\Component;
use Livewire\WithPagination;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class Roles extends Component
{
    use WithPagination;

    public string $search = '';

    public bool $createModal = false;
    public string $create_name = '';
    public array $create_permissions = [];

    public bool $editModal = false;
    public ?int $editId = null;
    public string $edit_name = '';
    public array $edit_permissions = [];

    private array $protectedRoles = [
        'admin', 'docente', 'admin_centro_facultad', 'estudiante', 'Validador',
    ];

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function isProtected(string $name): bool
    {
        return in_array($name, $this->protectedRoles);
    }

    public function openCreate(): void
    {
        $this->reset(['create_name', 'create_permissions']);
        $this->createModal = true;
    }

    public function store(): void
    {
        $this->validate([
            'create_name'          => 'required|string|max:255|unique:roles,name',
            'create_permissions'   => 'array',
            'create_permissions.*' => 'exists:permissions,id',
        ]);

        $role = Role::create(['name' => $this->create_name, 'guard_name' => 'web']);
        $role->syncPermissions($this->create_permissions);

        $this->createModal = false;
        Notification::make()->title('Rol creado.')->success()->send();
    }

    public function openEdit(int $id): void
    {
        $role = Role::with('permissions')->findOrFail($id);
        $this->editId           = $id;
        $this->edit_name        = $role->name;
        $this->edit_permissions = $role->permissions->pluck('id')->toArray();
        $this->editModal        = true;
    }

    public function save(): void
    {
        $this->validate([
            'edit_name'          => 'required|string|max:255|unique:roles,name,'.$this->editId,
            'edit_permissions'   => 'array',
            'edit_permissions.*' => 'exists:permissions,id',
        ]);

        $role = Role::findOrFail($this->editId);
        $role->update(['name' => $this->edit_name]);
        $role->syncPermissions($this->edit_permissions);

        $this->editModal = false;
        Notification::make()->title('Rol actualizado.')->success()->send();
    }

    public function delete(int $id): void
    {
        $role = Role::withCount('users')->findOrFail($id);

        if ($this->isProtected($role->name) || $role->users_count > 0) {
            Notification::make()->title('No se puede eliminar este rol.')->danger()->send();
            return;
        }

        $role->delete();
        Notification::make()->title('Rol eliminado.')->success()->send();
    }

    public function render(): View
    {
        $records = Role::withCount('users')
            ->when($this->search, fn($q) =>
                $q->where('name', 'like', '%'.$this->search.'%')
            )
            ->orderBy('name')
            ->paginate(15);

        return view('livewire.user.roles', [
            'records'     => $records,
            'permissions' => Permission::orderBy('name')->get(['id', 'name']),
        ]);
    }
}
