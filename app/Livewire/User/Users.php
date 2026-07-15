<?php

namespace App\Livewire\User;

use App\Models\User;
use App\Services\User\UserManagementService;
use App\Support\Notification;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Livewire\Component;
use Livewire\WithPagination;
use Spatie\Permission\Models\Role;

class Users extends Component
{
    use WithPagination;

    public string $search = '';
    public string $filterRoleId = '';
    public string $filterActiveRoleId = '';
    public string $filterProfile = '';
    public string $filterAccess = '';

    public bool $createModal = false;
    public string $create_name = '';
    public string $create_email = '';
    public string $create_password = '';
    public string $create_password_confirmation = '';
    public array $create_roles = [];
    public ?int $create_active_role_id = null;

    public bool $editModal = false;
    public ?int $editId = null;
    public string $edit_name = '';
    public string $edit_email = '';
    public string $edit_original_email = '';
    public bool $edit_has_microsoft = false;
    public bool $confirm_microsoft_email_change = false;

    public bool $rolesModal = false;
    public ?int $rolesUserId = null;
    public string $rolesUserName = '';
    public array $originalRoleIds = [];
    public array $manage_roles = [];
    public ?int $manage_active_role_id = null;
    public bool $confirm_roles_removal = false;
    public bool $confirm_administrative_removal = false;

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingFilterRoleId(): void
    {
        $this->resetPage();
    }

    public function updatingFilterActiveRoleId(): void
    {
        $this->resetPage();
    }

    public function updatingFilterProfile(): void
    {
        $this->resetPage();
    }

    public function updatingFilterAccess(): void
    {
        $this->resetPage();
    }

    public function resetFilters(): void
    {
        $this->reset(['search', 'filterRoleId', 'filterActiveRoleId', 'filterProfile', 'filterAccess']);
        $this->resetPage();
    }

    public function openCreate(): void
    {
        $this->authorizeUserManagement();
        $this->resetErrorBag();
        $this->reset([
            'create_name', 'create_email', 'create_password', 'create_password_confirmation',
            'create_roles', 'create_active_role_id',
        ]);
        $this->createModal = true;
    }

    public function store(UserManagementService $service): void
    {
        $this->authorizeUserManagement();
        $this->create_email = $this->normalizeEmail($this->create_email);

        $validated = $this->validate([
            'create_name' => ['required', 'string', 'max:255'],
            'create_email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')],
            'create_password' => ['required', 'string', 'min:8', 'confirmed'],
            'create_roles' => ['required', 'array', 'min:1'],
            'create_roles.*' => [
                'integer',
                'distinct',
                Rule::exists('roles', 'id')->where(fn (Builder $query): Builder => $query->where('guard_name', 'web')),
            ],
            'create_active_role_id' => [
                'nullable',
                'integer',
                Rule::exists('roles', 'id')->where(fn (Builder $query): Builder => $query->where('guard_name', 'web')),
            ],
        ]);

        $service->createUser([
            'name' => $validated['create_name'],
            'email' => $validated['create_email'],
            'password' => $validated['create_password'],
            'role_ids' => array_map('intval', $validated['create_roles']),
            'active_role_id' => $validated['create_active_role_id'],
        ], auth()->user());

        $this->createModal = false;
        Notification::make()->title('Usuario creado')->body('La cuenta, sus roles y el rol activo fueron guardados correctamente.')->success()->send();
    }

    public function openEdit(int $id): void
    {
        $this->authorizeUserManagement();
        $this->resetErrorBag();
        $user = User::query()->findOrFail($id);

        $this->editId = $user->id;
        $this->edit_name = (string) $user->name;
        $this->edit_email = (string) $user->email;
        $this->edit_original_email = (string) $user->email;
        $this->edit_has_microsoft = filled($user->microsoft_id);
        $this->confirm_microsoft_email_change = false;
        $this->editModal = true;
    }

    public function saveIdentity(UserManagementService $service): void
    {
        $this->authorizeUserManagement();
        $user = User::query()->findOrFail($this->editId);
        $this->edit_email = $this->normalizeEmail($this->edit_email);
        $emailChanged = $this->edit_email !== $this->normalizeEmail((string) $user->email);
        $hasMicrosoftIdentity = filled($user->microsoft_id);

        $validated = $this->validate([
            'edit_name' => ['required', 'string', 'max:255'],
            'edit_email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'confirm_microsoft_email_change' => [
                Rule::requiredIf($hasMicrosoftIdentity && $emailChanged),
                Rule::excludeIf(! $hasMicrosoftIdentity || ! $emailChanged),
                'accepted',
            ],
        ]);

        $service->updateIdentity($user, [
            'name' => $validated['edit_name'],
            'email' => $validated['edit_email'],
        ]);

        $this->editModal = false;
        Notification::make()->title('Usuario actualizado')->body('Se actualizaron únicamente el nombre y el correo.')->success()->send();
    }

    public function openRoles(int $id): void
    {
        $this->authorizeUserManagement();
        $this->resetErrorBag();
        $user = User::query()->with('roles:id,name,guard_name')->findOrFail($id);

        $this->rolesUserId = $user->id;
        $this->rolesUserName = (string) $user->name;
        $this->originalRoleIds = $user->roles->pluck('id')->map(fn ($roleId) => (int) $roleId)->all();
        $this->manage_roles = $this->originalRoleIds;
        $this->manage_active_role_id = $user->active_role_id;
        $this->confirm_roles_removal = false;
        $this->confirm_administrative_removal = false;
        $this->rolesModal = true;
    }

    public function saveRoles(UserManagementService $service): void
    {
        $this->authorizeUserManagement();
        $user = User::query()->findOrFail($this->rolesUserId);

        $validated = $this->validate([
            'manage_roles' => ['required', 'array', 'min:1'],
            'manage_roles.*' => [
                'integer',
                'distinct',
                Rule::exists('roles', 'id')->where(fn (Builder $query): Builder => $query->where('guard_name', 'web')),
            ],
            'manage_active_role_id' => [
                'nullable',
                'integer',
                Rule::exists('roles', 'id')->where(fn (Builder $query): Builder => $query->where('guard_name', 'web')),
            ],
            'confirm_roles_removal' => ['boolean'],
            'confirm_administrative_removal' => ['boolean'],
        ]);

        $difference = $service->roleDifference($user, $validated['manage_roles']);
        if ($difference['removed_ids'] !== [] && ! $this->confirm_roles_removal) {
            throw ValidationException::withMessages([
                'confirm_roles_removal' => 'Confirme explícitamente que desea retirar los roles indicados.',
            ]);
        }

        $service->syncUserRoles(
            $user,
            array_map('intval', $validated['manage_roles']),
            $validated['manage_active_role_id'],
            auth()->user(),
            $this->confirm_administrative_removal,
        );

        $this->rolesModal = false;
        Notification::make()->title('Roles actualizados')->body('Los roles y el rol activo se guardaron de forma segura.')->success()->send();
    }

    public function render(): View
    {
        $records = User::query()
            ->with([
                'roles' => fn ($query) => $query->where('guard_name', 'web')->orderBy('name'),
                'empleado:id,user_id',
                'estudiante:id,user_id',
            ])
            ->when($this->search !== '', fn ($query) => $query->where(fn ($searchQuery) => $searchQuery
                ->where('name', 'like', '%'.$this->search.'%')
                ->orWhere('email', 'like', '%'.$this->search.'%')
            ))
            ->when($this->filterRoleId !== '', fn ($query) => $query->whereHas('roles', fn ($roleQuery) => $roleQuery->where('roles.id', (int) $this->filterRoleId)))
            ->when($this->filterActiveRoleId !== '', fn ($query) => $query->where('active_role_id', (int) $this->filterActiveRoleId))
            ->when($this->filterProfile === 'empleado', fn ($query) => $query->whereHas('empleado')->whereDoesntHave('estudiante'))
            ->when($this->filterProfile === 'estudiante', fn ($query) => $query->whereHas('estudiante')->whereDoesntHave('empleado'))
            ->when($this->filterProfile === 'ambos', fn ($query) => $query->whereHas('empleado')->whereHas('estudiante'))
            ->when($this->filterProfile === 'sin_perfil', fn ($query) => $query->whereDoesntHave('empleado')->whereDoesntHave('estudiante'))
            ->when($this->filterAccess === 'valido', fn ($query) => $query
                ->whereNotNull('active_role_id')
                ->whereHas('roles', fn ($roleQuery) => $roleQuery->whereColumn('roles.id', 'users.active_role_id'))
            )
            ->when($this->filterAccess === 'sin_roles', fn ($query) => $query->whereDoesntHave('roles'))
            ->when($this->filterAccess === 'sin_rol_activo', fn ($query) => $query
                ->whereHas('roles')
                ->where(fn ($accessQuery) => $accessQuery
                    ->whereNull('active_role_id')
                    ->orWhereDoesntHave('roles', fn ($roleQuery) => $roleQuery->whereColumn('roles.id', 'users.active_role_id'))
                )
            )
            ->orderBy('name')
            ->paginate(15);

        $allRoles = Role::query()
            ->where('guard_name', 'web')
            ->with('permissions:id,name,guard_name')
            ->orderBy('name')
            ->get(['id', 'name', 'guard_name']);

        $addedRoleNames = $allRoles->whereIn('id', array_diff(array_map('intval', $this->manage_roles), $this->originalRoleIds))->pluck('name');
        $removedRoleNames = $allRoles->whereIn('id', array_diff($this->originalRoleIds, array_map('intval', $this->manage_roles)))->pluck('name');

        return view('livewire.user.users', compact('records', 'allRoles', 'addedRoleNames', 'removedRoleNames'));
    }

    private function authorizeUserManagement(): void
    {
        Gate::authorize('usuarios.usuarios');
    }

    private function normalizeEmail(string $email): string
    {
        return mb_strtolower(trim($email));
    }
}
