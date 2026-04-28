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
        'departamento_id'  => 'required|exists:departamentos,id',
        'nombre'           => 'required|string|max:100',
        'codigo_municipio' => 'required|string|max:20',
    ];

    protected array $messages = [
        'departamento_id.required' => 'Selecciona un departamento.',
        'nombre.required'          => 'El nombre es obligatorio.',
        'codigo_municipio.required'=> 'El código del municipio es obligatorio.',
    ];

    public function create(): void
    {
        $data = $this->validate();

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
