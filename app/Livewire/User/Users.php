<?php

namespace App\Livewire\User;

use App\Models\User;
use Illuminate\Contracts\View\View;
use Livewire\Component;
use Livewire\WithPagination;
use App\Support\Notification;
use Spatie\Permission\Models\Role;

class Users extends Component
{
    use WithPagination;

    public string $search = '';
    public bool $createModal = false;
    public string $create_name = '';
    public string $create_email = '';
    public string $create_password = '';
    public ?int $create_role_id = null;

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function render(): View
    {
        $records = User::when($this->search, fn($q) =>
                $q->where('name', 'like', '%'.$this->search.'%')
                  ->orWhere('email', 'like', '%'.$this->search.'%')
            )
            ->orderBy('name')
            ->paginate(15);

        $roles = Role::orderBy('name')->get(['id', 'name']);

        return view('livewire.user.users', compact('records', 'roles'));
    }

    public function openCreate(): void
    {
        $this->reset(['create_name', 'create_email', 'create_password', 'create_role_id']);
        $this->createModal = true;
    }

    public function store(): void
    {
        $this->validate([
            'create_name' => 'required|string|max:255',
            'create_email' => 'required|email|unique:users,email',
            'create_password' => 'required|string|min:3',
            'create_role_id' => 'nullable|exists:roles,id',
        ]);

        $user = User::create([
            'name' => $this->create_name,
            'email' => $this->create_email,
            'password' => bcrypt($this->create_password),
        ]);

        if ($this->create_role_id) {
            $role = Role::find($this->create_role_id);
            if ($role) {
                $user->assignRole($role->name);
                $user->update(['active_role_id' => $role->id]);
            }
        }

        $this->createModal = false;
        Notification::make()->title('Usuario creado.')->success()->send();
    }
}
