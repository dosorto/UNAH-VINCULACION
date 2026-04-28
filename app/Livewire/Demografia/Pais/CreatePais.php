<?php

namespace App\Livewire\Demografia\Pais;

use App\Models\Demografia\Pais;
use App\Support\Notification;
use Illuminate\Contracts\View\View;
use Livewire\Component;

class CreatePais extends Component
{
    public string $codigo_area = '';
    public string $codigo_iso = '';
    public string $codigo_iso_numerico = '';
    public string $codigo_iso_alpha_2 = '';
    public string $nombre = '';
    public string $gentilicio = '';

    protected array $rules = [
        'codigo_area'          => 'required|numeric',
        'codigo_iso'           => 'required|string|max:10',
        'codigo_iso_numerico'  => 'required|numeric',
        'codigo_iso_alpha_2'   => 'required|string|max:5',
        'nombre'               => 'required|string|max:100',
        'gentilicio'           => 'required|string|max:100',
    ];

    protected array $messages = [
        'codigo_area.required'         => 'El código de área es obligatorio.',
        'codigo_iso.required'          => 'El código ISO es obligatorio.',
        'codigo_iso_numerico.required' => 'El código ISO numérico es obligatorio.',
        'codigo_iso_alpha_2.required'  => 'El código ISO alpha 2 es obligatorio.',
        'nombre.required'              => 'El nombre es obligatorio.',
        'gentilicio.required'          => 'El gentilicio es obligatorio.',
    ];

    public function create(): void
    {
        $data = $this->validate();

        Pais::create($data);

        Notification::make()
            ->title('¡Éxito!')
            ->body('País creado correctamente.')
            ->success()
            ->send();

        $this->reset(['codigo_area', 'codigo_iso', 'codigo_iso_numerico', 'codigo_iso_alpha_2', 'nombre', 'gentilicio']);
    }

    public function render(): View
    {
        return view('livewire.demografia.create-pais');
    }
}
