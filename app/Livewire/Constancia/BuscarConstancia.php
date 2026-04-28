<?php

namespace App\Livewire\Constancia;

use App\Models\Constancia\Constancia;
use App\Support\Notification;
use Illuminate\Contracts\View\View;
use Livewire\Component;

class BuscarConstancia extends Component
{
    public string $codigo = '';

    public function submit(): void
    {
        $this->validate([
            'codigo' => 'required|string',
        ]);

        $constancia = Constancia::where('hash', $this->codigo)->first();

        if ($constancia) {
            Notification::make()->title('Éxito')->body('Se encontró un registro con ese código.')->success()->send();
            $this->redirect(route('verificacion_constancia', $this->codigo));
            return;
        }

        Notification::make()->title('Error')->body('No se encontró registro con ese código.')->danger()->send();
    }

    public function render(): View
    {
        return view('livewire.constancia.buscar-constancia');
    }
}
