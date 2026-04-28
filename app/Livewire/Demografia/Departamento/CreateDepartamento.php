<?php

namespace App\Livewire\Demografia\Departamento;

use App\Models\Demografia\Departamento;
use App\Models\Demografia\Pais;
use App\Support\Notification;
use Illuminate\Contracts\View\View;
use Livewire\Component;

class CreateDepartamento extends Component
{
    public ?int $pais_id = null;
    public string $nombre = '';
    public string $codigo_departamento = '';

    protected array $rules = [
        'pais_id'            => 'required|exists:paises,id',
        'nombre'             => 'required|string|max:100',
        'codigo_departamento'=> 'required|string|max:20',
    ];

    protected array $messages = [
        'pais_id.required' => 'Selecciona un país.',
        'pais_id.exists'   => 'El país seleccionado no es válido.',
        'nombre.required'  => 'El nombre es obligatorio.',
        'codigo_departamento.required' => 'El código del departamento es obligatorio.',
    ];

    public function create(): void
    {
        $data = $this->validate();

        Departamento::create($data);

        Notification::make()
            ->title('¡Éxito!')
            ->body('Departamento creado correctamente.')
            ->success()
            ->send();

        $this->reset(['pais_id', 'nombre', 'codigo_departamento']);
    }

    public function render(): View
    {
        return view('livewire.demografia.departamento.create-departamento', [
            'paises' => Pais::orderBy('nombre')->pluck('nombre', 'id'),
        ]);
    }
}
