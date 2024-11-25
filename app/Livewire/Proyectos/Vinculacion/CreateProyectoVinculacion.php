<?php

namespace App\Livewire\Proyectos\Vinculacion;

use Livewire\Component;
use Filament\Forms\Form;
use App\Models\Estado\TipoEstado;
use App\Models\Proyecto\Proyecto;
use Illuminate\Support\HtmlString;
use App\Models\Proyecto\CargoFirma;
use Illuminate\Contracts\View\View;
use Filament\Forms\Components\Wizard;
use Illuminate\Support\Facades\Blade;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Forms\Concerns\InteractsWithForms;

use App\Livewire\Proyectos\Vinculacion\Secciones\PrimeraParte;
use App\Livewire\Proyectos\Vinculacion\Secciones\SegundaParte;
use App\Livewire\Proyectos\Vinculacion\Secciones\TerceraParte;
use App\Livewire\Proyectos\Vinculacion\Secciones\CuartaParte;
use App\Livewire\Proyectos\Vinculacion\Secciones\QuintaParte;
use App\Livewire\Proyectos\Vinculacion\Secciones\SextaParte;

// Para enviar Emails
use App\Mail\Correos\CorreoParticipacion;
use Illuminate\Support\Facades\Mail;

class CreateProyectoVinculacion extends Component implements HasForms
{
    use InteractsWithForms;

    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill();
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Wizard::make([
                    Wizard\Step::make('I.')
                        ->description('Información general del proyecto')
                        ->schema(
                            PrimeraParte::form()
                        )
                        ->columns(2),
                    Wizard\Step::make('II.')
                        ->description('INFORMACIÓN DE LA ENTIDAD CONTRAPARTE DEL PROYECTO (en caso de contar con una contraparte).')
                        ->schema(
                            SegundaParte::form(),
                        ),
                    Wizard\Step::make('III.')
                        ->description('Cronograma de actividades.')
                        ->schema(
                            TerceraParte::form(),
                        ),
                    Wizard\Step::make('IV.')
                        ->description('DATOS DEL PROYECTO')
                        ->schema(
                            CuartaParte::form(),
                        )
                        ->columns(2),
                    Wizard\Step::make('V.')
                        ->description('Anexos')
                        ->schema(
                            QuintaParte::form(),
                        ),
                    Wizard\Step::make('VI.')
                        ->description('Firmas')
                        ->schema(
                            SextaParte::form(),
                        ),
                ])->submitAction(new HtmlString(Blade::render(<<<BLADE
                <x-filament::button
                    type="submit"
                    size="sm"
                    color="info"
                >
                 Enviar a Firmar
                </x-filament::button>

                <x-filament::button
                   wire:click="borrador"
                    size="sm"
                    color="success"
                >
                 Guardar Borrador
                </x-filament::button>
            BLADE))),


            ])
            ->statePath('data')
            ->model(Proyecto::class);
    }

    public function create(): void
    {
        // dd($this->form->getState());
        $data = $this->form->getState();
        $data['fecha_registro'] = now();
        $record = Proyecto::create($data);
        $this->form->model($record)->saveRelationships();

        $firmaP = $record->firma_proyecto()->create([
            'empleado_id' => auth()->user()->empleado->id,
            'cargo_firma_id' => CargoFirma::where('nombre', 'Coordinador Proyecto')->first()->id,
            'estado_revision' => 'Aprobado',
            'firma_id' => auth()->user()->empleado->firma->id,
            'sello_id' => auth()->user()->empleado->sello->id,
            'estado_actual_id' => TipoEstado::where('nombre', 'Esperando Firma Coorinador Proyecto')->first()->id,
            'hash' => 'hash'
        ]);

        $record->estado_proyecto()->create([
            'empleado_id' => auth()->user()->empleado->id,
            'tipo_estado_id' => $firmaP->estado_actual->estado_siguiente_id,
            'fecha' => now(),
            'comentario' => 'Proyecto creado',
        ]);

        foreach ($record->integrantes as $empleado) {
            // Accede al usuario de cada empleado y a su correo
            $usuario = $empleado->user;  // Asumiendo que cada empleado tiene un usuario relacionado
            if ($usuario && $usuario->email) {
                // Enviar el correo al email del usuario
                Mail::to($usuario->email)->send(new CorreoParticipacion());
            }
        }
        // Mail::to('ernesto.moncada@unah.hn')->send(new CorreoParticipacion());


        Notification::make()
            ->title('¡Éxito!')
            ->body('Proyecto creado correctamente')
            ->success()
            ->send();
        $this->js('location.reload();');
    }


    // optimizar esta funcion para despues, es demasiado redundante y lo unico que cambia es el nombre del estado :)
    public function borrador(): void
    {
        $data = $this->form->getState();
        $data['fecha_registro'] = now();
        $record = Proyecto::create($data);
        $this->form->model($record)->saveRelationships();

        $record->firma_proyecto()->create([
            'empleado_id' => auth()->user()->empleado->id,
            'cargo_firma_id' => CargoFirma::where('nombre', 'Coordinador Proyecto')->first()->id,
            'estado_revision' => 'Pendiente',
            'hash' => 'hash'
        ]);

        $record->estado_proyecto()->create([
            'empleado_id' => auth()->user()->empleado->id,
            'tipo_estado_id' => TipoEstado::where('nombre', 'Borrador')->first()->id,
            'fecha' => now(),
            'comentario' => 'Proyecto creado',
        ]);

        Notification::make()
            ->title('¡Éxito!')
            ->body('Proyecto creado correctamente')
            ->success()
            ->send();
        $this->js('location.reload();');
    }

    public function render(): View
    {
        return view('livewire.proyectos.vinculacion.create-proyecto-vinculacion')
            ->layout('components.panel.modulos.modulo-proyectos');
    }
}
