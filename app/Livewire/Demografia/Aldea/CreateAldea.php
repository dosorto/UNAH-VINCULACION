<?php

namespace App\Livewire\Demografia\Aldea;

use App\Models\Demografia\Aldea;
use App\Models\Demografia\Municipio;
use App\Support\Notification;
use Illuminate\Contracts\View\View;
use Livewire\Component;

class CreateAldea extends Component
{
    public ?int $municipio_id = null;
    public string $nombre = '';

    protected array $rules = [
        'municipio_id' => 'required|exists:municipios,id',
        'nombre'       => 'required|string|max:100',
    ];

    protected array $messages = [
        'municipio_id.required' => 'Selecciona un municipio.',
        'nombre.required'       => 'El nombre es obligatorio.',
    ];

    public function create(): void
    {
        $data = $this->validate();

        Aldea::create($data);

        Notification::make()
            ->title('¡Éxito!')
            ->body('Aldea creada correctamente.')
            ->success()
            ->send();

        $this->reset(['municipio_id', 'nombre']);
    }

    public function render(): View
    {
        return view('livewire.demografia.aldea.create-aldea', [
            'municipios' => Municipio::orderBy('nombre')->pluck('nombre', 'id'),
        ]);
    }
}
