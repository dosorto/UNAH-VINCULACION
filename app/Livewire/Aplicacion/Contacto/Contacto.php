<?php

namespace App\Livewire\Aplicacion\Contacto;

use App\Models\Personal\Contacto as PersonalContacto;
use Filament\Forms;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Livewire\Component;
use Illuminate\Contracts\View\View;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Forms\Components\Textarea;


use App\Services\Correos\EmailBuilder;
use Illuminate\Support\Facades\Mail;
use phpseclib3\File\ASN1\Maps\PersonalName;

class Contacto extends Component implements HasForms
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
                TextInput::make('nombres')
                    ->label('Nombres')
                    ->required()
                    ->maxLength(255),
                TextInput::make('apellidos')
                    ->label('Apellidos')
                    ->required()
                    ->maxLength(255),
                TextInput::make('institucion')
                    ->label('Institucion')
                    ->required()
                    ->maxLength(255),
                TextInput::make('email')
                    ->label('Email')
                    ->required()
                    ->email()
                    ->maxLength(255),
                TextInput::make('telefono')
                    ->label('Teléfono')
                    ->required()
                    ->tel()
                    ->maxLength(15),

                Textarea::make('mensaje')
                    ->columnSpanFull()

                    ->rows(5)
                    ->required()
                    ->cols(15)

            ])
            ->columns(2)
            ->statePath('data');
    }

    private function enviarCorreo(PersonalContacto $contacto): void
    {
        $correo = (new EmailBuilder())
            ->setEstadoNombre("")
            ->setEmpleadoNombre($contacto->nombres . ' ' . $contacto->apellidos)
            ->setNombreProyecto("")
            ->setActionUrl(route('home'))
            ->setLogoUrl(asset('images/logo_nuevo.png'))
            ->setAppName('NEXO-UNAH')
            ->setMensaje("Gracias por ponerse en contacto con nosotros. Daremos respuesta a su consulta lo más pronto posible.")
            ->setSubject('Confirmación de envío de consulta')
            ->build();

        Mail::to($contacto->email)->queue($correo);
    }

    public function submit(): void
    {
        $data = $this->form->getState();
        $contacto = PersonalContacto::create($data);
        $this->enviarCorreo($contacto);
        Notification::make()
            ->title('¡Éxito!')
            ->body('su mensaje ha sido enviado correctamente.')
            ->success()
            ->send();

        $this->js('location.reload();');

        //
    }

    public function render(): View
    {
        return view('livewire.aplicacion.contacto.contacto');
    }
}
