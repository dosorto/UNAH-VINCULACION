<?php

namespace App\Livewire\Constancia;

use App\Models\Constancia\Constancia;
use Filament\Forms;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Livewire\Component;
use Illuminate\Contracts\View\View;

use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;

class BuscarConstancia extends Component implements HasForms
{
    use InteractsWithForms;

    public ?array $data = [];

    public function mount()
    {
        $this->form->fill();
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([

TextInput::make('codigo')
            ])
            ->statePath('data');
    }

    public function submit()
    {
        $data = $this->form->getState();
        // buscar la constancias con el codigo si lo encuentra redireccionar a la ruta sino
        // mandar la notificacion que no lo encontro

        $Constancia = Constancia::where('hash', $data['codigo'])
        ->first();

        if($Constancia)
            return redirect()->route('constancia.show', $Constancia->id);
        else
           Notification::make()
            ->title('Error')
            ->body('NO SE ENCONTRO REGISTRO CON ESE CODIGO')
            ->danger()
            ->send();


        //
    }

    public function render(): View
    {
        return view('livewire.constancia.buscar-constancia');
    }
}