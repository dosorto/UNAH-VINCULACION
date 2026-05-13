<?php

namespace App\Livewire\Demografia\Municipio;

use App\Models\Demografia\Departamento;
use App\Models\Demografia\Municipio;
use App\Support\Notification;
use Illuminate\Contracts\View\View;
use Livewire\Component;

class CreateMunicipio extends Component
{
    public ?int $departamento_id = null;
    public string $nombre = '';
    public string $codigo_municipio = '';

    protected array $rules = [
        'departamento_id'  => 'required|exists:departamento,id',
        'nombre'           => 'required|string|max:100',
        'codigo_municipio' => 'nullable|numeric',
    ];

    protected array $messages = [
        'departamento_id.required' => 'Selecciona un departamento.',
        'nombre.required'          => 'El nombre es obligatorio.',
        'codigo_municipio.numeric' => 'El código del municipio debe ser numérico.',
    ];

    public function create(): void
    {
        $data = $this->validate();
        $data['codigo_municipio'] = $data['codigo_municipio'] ?: null;

        Municipio::create($data);

        Notification::make()
            ->title('¡Éxito!')
            ->body('Municipio creado correctamente.')
            ->success()
            ->send();

        $this->reset(['departamento_id', 'nombre', 'codigo_municipio']);
    }

    public function render(): View
    {
        return view('livewire.demografia.municipio.create-municipio', [
            'departamentos' => Departamento::orderBy('nombre')->pluck('nombre', 'id'),
        ]);
    }
}
