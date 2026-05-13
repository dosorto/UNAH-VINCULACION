<?php

namespace App\Livewire\Personal\Contacto;

use App\Models\Personal\Contacto;
use App\Services\Correos\EmailBuilder;
use App\Support\Notification;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Mail;
use Livewire\Component;
use Livewire\WithPagination;

class ListContactos extends Component
{
    use WithPagination;

    public string $search = '';

    public bool $viewModal = false;
    public ?int $viewId = null;

    public bool $respondModal = false;
    public ?int $respondId = null;
    public string $respuestaMensaje = '';

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function openView(int $id): void
    {
        $this->viewId   = $id;
        $this->viewModal = true;
    }

    public function openResponder(int $id): void
    {
        $this->respondId     = $id;
        $this->respuestaMensaje = '';
        $this->respondModal  = true;
    }

    public function responder(): void
    {
        $this->validate(['respuestaMensaje' => 'required|string']);

        $contacto = Contacto::findOrFail($this->respondId);

        $correo = (new EmailBuilder())
            ->setEstadoNombre('')
            ->setEmpleadoNombre($contacto->nombres . ' ' . $contacto->apellidos)
            ->setNombreProyecto('')
            ->setActionUrl(route('home'))
            ->setLogoUrl(asset('images/logo_nuevo.png'))
            ->setAppName('NEXO-UNAH')
            ->setMensaje($this->respuestaMensaje)
            ->setSubject('Respuesta a su consulta')
            ->build();

        Mail::to($contacto->email)->queue($correo);

        $this->respondModal = false;
        Notification::make()->title('Correo enviado correctamente.')->success()->send();
    }

    public function render(): View
    {
        $records = Contacto::when($this->search, fn($q) =>
                $q->where('nombres', 'like', '%'.$this->search.'%')
                  ->orWhere('apellidos', 'like', '%'.$this->search.'%')
                  ->orWhere('email', 'like', '%'.$this->search.'%')
            )
            ->latest()
            ->paginate(15);

        $viewContacto    = $this->viewId    ? Contacto::find($this->viewId)    : null;
        $respondContacto = $this->respondId ? Contacto::find($this->respondId) : null;

        return view('livewire.personal.contacto.list-contactos', compact('records', 'viewContacto', 'respondContacto'));
    }
}