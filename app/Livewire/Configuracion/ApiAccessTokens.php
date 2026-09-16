<?php

namespace App\Livewire\Configuracion;

use App\Models\ApiAccessScope;
use App\Models\ApiAccessToken;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Livewire\Component;

class ApiAccessTokens extends Component
{
    public bool $showForm = false;
    public ?int $editingId = null;
    public string $nombre = '';
    public ?string $expira_en = null;
    public array $scopes = [];

    public function mount(): void
    {
        $this->authorizeAccess();
    }

    private function authorizeAccess(): void
    {
        abort_unless(auth()->user()?->activeRole?->hasPermissionTo('configuracion.integraciones-api'), 403);
    }

    public function cerrar(): void
    {
        $this->dispatch('api-token-dismissed');
        $this->reset(['editingId', 'nombre', 'expira_en', 'scopes', 'showForm']);
    }

    public function nuevo(): void
    {
        $this->authorizeAccess();
        $this->cerrar();
        $this->showForm = true;
    }

    public function editar(int $id): void
    {
        $this->authorizeAccess();
        $this->cerrar();
        $token = ApiAccessToken::with('scopes')->findOrFail($id);
        $this->editingId = $token->id;
        $this->nombre = $token->nombre;
        $this->expira_en = $token->expira_en?->format('Y-m-d');
        $this->scopes = $token->scopes->pluck('id')->map(fn ($id) => (string) $id)->all();
        $this->showForm = true;
    }

    public function guardar(): void
    {
        $this->authorizeAccess();
        // Livewire serializa un campo de fecha vacío como cadena vacía; la base
        // de datos espera NULL para representar un token sin expiración.
        $this->expira_en = blank($this->expira_en) ? null : $this->expira_en;
        $this->validate([
            'nombre' => 'required|string|max:120',
            'expira_en' => 'nullable|date|after:today',
            'scopes' => 'array',
            'scopes.*' => ['integer', 'distinct', Rule::exists('api_access_scopes', 'id')->where('activo', true)],
        ]);

        if ($this->editingId) {
            $token = ApiAccessToken::findOrFail($this->editingId);
            DB::transaction(function () use ($token) {
                $token->update(['nombre' => $this->nombre, 'expira_en' => $this->expira_en]);
                $token->scopes()->sync($this->scopes);
                activity()->performedOn($token)->causedBy(auth()->user())
                    ->withProperties(['prefijo' => $token->prefijo, 'alcances' => $this->scopes])->log('Token API actualizado');
            });
            $this->cerrar();
            return;
        }

        $raw = 'nexo_'.Str::random(48);
        DB::transaction(function () use ($raw) {
            $token = ApiAccessToken::create([
                'nombre' => $this->nombre,
                'prefijo' => substr($raw, 0, 12),
                'token_hash' => ApiAccessToken::hashToken($raw),
                'created_by' => auth()->id(),
                'expira_en' => $this->expira_en,
            ]);
            $token->scopes()->sync($this->scopes);
            activity()->performedOn($token)->causedBy(auth()->user())
                ->withProperties(['prefijo' => $token->prefijo, 'alcances' => $this->scopes])->log('Token API creado');
        });
        $this->cerrar();
        // Entrega única como efecto: el secreto nunca pertenece al snapshot público.
        $this->dispatch('api-token-created', token: $raw);
    }

    public function revocar(int $id): void
    {
        $this->authorizeAccess();
        $this->cerrar();
        $token = ApiAccessToken::findOrFail($id);
        if ($token->revocado_en) {
            return;
        }
        $token->update(['revocado_en' => now()]);
        activity()->performedOn($token)->causedBy(auth()->user())
            ->withProperties(['prefijo' => $token->prefijo])->log('Token API revocado');
    }

    public function render()
    {
        $this->authorizeAccess();
        return view('livewire.configuracion.api-access-tokens', [
            'tokens' => ApiAccessToken::with('scopes')->latest()->get(),
            'catalogoScopes' => ApiAccessScope::where('activo', true)->get(),
        ]);
    }
}
