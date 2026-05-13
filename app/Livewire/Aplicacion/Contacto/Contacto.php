<?php

namespace App\Livewire\Aplicacion\Contacto;

use App\Models\Personal\Contacto as PersonalContacto;
use App\Services\Correos\EmailBuilder;
use App\Support\Notification;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Mail;
use Livewire\Component;

class Contacto extends Component
{
    public string $nombres = '';
    public string $apellidos = '';
    public string $institucion = '';
    public string $email = '';
    public string $telefono = '';
    public string $mensaje = '';

    protected array $rules = [
        'nombres'     => 'required|string|max:255',
        'apellidos'   => 'required|string|max:255',
        'institucion' => 'required|string|max:255',
        'email'       => 'required|email|max:255',
        'telefono'    => 'required|string|max:15',
        'mensaje'     => 'required|string',
    ];

    public function submit(): void
    {
        $this->validate();

        $contacto = PersonalContacto::create([
            'nombres'     => $this->nombres,
            'apellidos'   => $this->apellidos,
            'institucion' => $this->institucion,
            'email'       => $this->email,
            'telefono'    => $this->telefono,
            'mensaje'     => $this->mensaje,
        ]);

        $this->enviarCorreo($contacto);

        Notification::make()->title('Exito')->body('Su mensaje ha sido enviado correctamente.')->success()->send();

        $this->reset(['nombres', 'apellidos', 'institucion', 'email', 'telefono', 'mensaje']);
    }

    private function enviarCorreo(PersonalContacto $contacto): void
    {
        $correo = (new EmailBuilder())
            ->setEstadoNombre('')
            ->setEmpleadoNombre($contacto->nombres . ' ' . $contacto->apellidos)
            ->setNombreProyecto('')
            ->setActionUrl(route('home'))
            ->setLogoUrl(asset('images/logo_nuevo.png'))
            ->setAppName('NEXO-UNAH')
            ->setMensaje('Gracias por ponerse en contacto con nosotros. Daremos respuesta a su consulta lo mas pronto posible.')
            ->setSubject('Confirmacion de envio de consulta')
            ->build();

        Mail::to($contacto->email)->queue($correo);
    }

    public function render(): View
    {
        return view('livewire.aplicacion.contacto.contacto');
    }
}