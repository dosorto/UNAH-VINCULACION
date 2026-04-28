<?php

namespace App\Livewire\Demografia\Ciudad;

use App\Models\Demografia\Ciudad;
use App\Models\Demografia\Municipio;
use App\Support\Notification;
use Illuminate\Contracts\View\View;
use Livewire\Component;

class CreateCiudad extends Component
{
    public ?int $municipio_id = null;
    public string $nombre = '';
    public string $codigo_postal = '';

    protected array $rules = [
        'municipio_id'  => 'required|exists:municipios,id',
        'nombre'        => 'required|string|max:100',
        'codigo_postal' => 'required|string|max:20',
    ];

    protected array $messages = [
        'municipio_id.required'  => 'Selecciona un municipio.',
        'nombre.required'        => 'El nombre es obligatorio.',
        'codigo_postal.required' => 'El código postal es obligatorio.',
    ];

    public function create(): void
    {
        $data = $this->validate();

        Ciudad::create($data);

        Notification::make()
            ->title('¡Éxito!')
            ->body('Ciudad creada correctamente.')
            ->success()
            ->send();

        $this->reset(['municipio_id', 'nombre', 'codigo_postal']);
    }

    public function render(): View
    {
        return view('livewire.demografia.ciudad.create-ciudad', [
            'municipios' => Municipio::orderBy('nombre')->pluck('nombre', 'id'),
        ]);
    }
}
