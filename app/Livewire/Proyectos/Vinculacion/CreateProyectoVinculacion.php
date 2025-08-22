<?php

namespace App\Livewire\Proyectos\Vinculacion;

use Livewire\Component;
use Filament\Forms\Form;
use App\Jobs\SendEmailJob;
use App\Models\Estado\TipoEstado;
use App\Models\Proyecto\Proyecto;
use Illuminate\Support\HtmlString;
use App\Models\Proyecto\CargoFirma;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Mail;
use Filament\Forms\Components\Wizard;
use Illuminate\Support\Facades\Blade;
use Filament\Forms\Contracts\HasForms;

use Filament\Notifications\Notification;
use Filament\Forms\Concerns\InteractsWithForms;
use App\Livewire\Proyectos\Vinculacion\Secciones\SextaParte;
use App\Livewire\Proyectos\Vinculacion\Secciones\CuartaParte;
use App\Livewire\Proyectos\Vinculacion\Secciones\QuintaParte;
use App\Livewire\Proyectos\Vinculacion\Secciones\PrimeraParte;

// Para enviar Emails
use App\Mail\ProyectoCreado;
// use App\Mail\Correos\CorreoParticipacion;
use App\Livewire\Proyectos\Vinculacion\Secciones\SegundaParte;
use App\Livewire\Proyectos\Vinculacion\Secciones\TerceraParte;
use App\Livewire\Proyectos\Vinculacion\Secciones\EquipoEjecutor;
use App\Livewire\Proyectos\Vinculacion\Secciones\MarcoLogico;
use App\Livewire\Proyectos\Vinculacion\Secciones\Presupuesto;

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
                            PrimeraParte::form(),
                        )
                        ->columns(2),
                    Wizard\Step::make('II.')
                        ->description('EQUIPO EJECUTOR DEL PROYECTO')
                        ->schema(
                            EquipoEjecutor::form(),
                        ),
                    Wizard\Step::make('III.')
                        ->description('INFORMACIÓN DE LA ENTIDAD CONTRAPARTE DEL PROYECTO (en caso de contar con una contraparte).')
                        ->schema(
                            SegundaParte::form(),
                        ),
                    Wizard\Step::make('IV.')
                        ->description('CRONOGRAMA DE ACTIVIDADES.')
                        
                        ->schema(
                            TerceraParte::form(),
                        ),
                    Wizard\Step::make('V.')
                        ->description('DATOS DEL PROYECTO')
                        ->schema(
                            CuartaParte::form(),
                        )
                        ->columns(2),
                        Wizard\Step::make('VI.')
                        ->description('RESUMEN MARCO LÓGICO DEL PROYECTO')
                        ->schema(
                            MarcoLogico::form(),
                        )
                        ->columns(2),
                    Wizard\Step::make('VII.')
                        ->description('DETALLES DEL PRESUPUESTO')
                        ->schema(
                            Presupuesto::form(),
                        )
                        ->columns(2),
                    Wizard\Step::make('VIII.')
                        ->description('ANEXOS')
                        ->schema(
                            QuintaParte::form(),
                        ),
                    Wizard\Step::make('IX.')
                        ->description('FIRMAS')
                        ->schema(
                            SextaParte::form(),
                        ),
                ])->submitAction(new HtmlString(Blade::render(<<<BLADE
                
                <x-filament::button
                   wire:click="borrador"
                    size="sm"
                    color="success"
                >
                 Guardar Borrador
                </x-filament::button>
                <x-filament::button
                    type="submit"
                    size="sm"
                    color="info"
                >
                 Enviar a Firmar
                </x-filament::button>
            BLADE))),


            ])
            ->statePath('data')
            ->model(Proyecto::class);
    }

    public function create(): void
    {
        $data = $this->form->getState();
        $record = null;
        
        try {
            // Intentar obtener el estado del formulario y crear el proyecto
            $data['fecha_registro'] = now();
            $record = Proyecto::create($data);
            $this->form->model($record)->saveRelationships();
        } catch (\Exception $e) {
            // Notificación de error si ocurre al crear el proyecto
            // Solo eliminar el registro si fue creado exitosamente
            if ($record) {
                $record->delete();
            }

            Notification::make()
                ->title('Error')
                ->body('Error al crear el proyecto: ' . $e->getMessage())
                ->danger()
                ->send();
            return;
        }

        try {
            // Intentar agregar o actualizar la firma
            $firmaP = $record->firma_proyecto()->updateOrCreate(
                [
                    'empleado_id' => auth()->user()->empleado->id,
                    'cargo_firma_id' => CargoFirma::join('tipo_cargo_firma', 'tipo_cargo_firma.id', '=', 'cargo_firma.tipo_cargo_firma_id')
                        ->where('tipo_cargo_firma.nombre', 'Coordinador Proyecto')
                        ->where('cargo_firma.descripcion', 'Proyecto')
                        ->first()->id,
                ],
                [
                    'estado_revision' => 'Aprobado',
                    'firma_id' => auth()->user()?->empleado?->firma?->id,
                    'sello_id' => auth()->user()?->empleado?->sello?->id,
                    'hash' => 'hash',
                    'fecha_firma' => now(),
                ]
            );
            
            // Intentar agregar el estado del proyecto
            $record->estado_proyecto()->create([
                'empleado_id' => auth()->user()->empleado->id,
                'tipo_estado_id' => $firmaP->cargo_firma->estado_siguiente_id,
                'fecha' => now(),
                'comentario' => 'Proyecto creado exitosamente y enviado a firmar',
            ]);
            
            // Enviar el correo al usuario que creó el proyecto
            // Obtener el nombre del estado al que cambió el proyecto
            $estado = TipoEstado::find($firmaP->cargo_firma->estado_siguiente_id);
            $estadoNombre = $estado ? $estado->nombre : 'Desconocido';

            // Enviar el correo al usuario que creó el proyecto
            try {
                Mail::to(auth()->user()->email)->send(new ProyectoCreado($record, auth()->user()));
            } catch (\Exception $emailException) {
                // Log del error pero no fallar la creación del proyecto
                \Log::warning('Error al enviar correo de proyecto creado: ' . $emailException->getMessage());
            }
            
        } catch (\Exception $e) {
            // Eliminar el proyecto en caso de error
            $record->delete();
            Notification::make()
                ->title('Error')
                ->body('Error al procesar el proyecto: ' . $e->getMessage())
                ->danger()
                ->send();
            return;
        }

            
        // Notificación de éxito si todo se completó correctamente
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
        try {
            $data['fecha_registro'] = now();

            // Intentar crear el proyecto
            $record = Proyecto::create($data);
            $this->form->model($record)->saveRelationships();
        } catch (\Exception $e) {
            Notification::make()
                ->title('Error')
                ->body('Error al crear el proyecto: ' . $e->getMessage())
                ->danger()
                ->send();
            return;
        }

        try {
            // Intentar agregar la firma
            $firmaP = $record->firma_proyecto()->create([
                'empleado_id' => auth()->user()->empleado->id,
                'cargo_firma_id' => CargoFirma::join('tipo_cargo_firma', 'tipo_cargo_firma.id', '=', 'cargo_firma.tipo_cargo_firma_id')
                    ->where('tipo_cargo_firma.nombre', 'Coordinador Proyecto')
                    ->where('cargo_firma.descripcion', 'Proyecto')
                    ->first()->id,
                'estado_revision' => 'Pendiente',
                'hash' => 'hash'
            ]);
        } catch (\Exception $e) {
            // Eliminar el proyecto en caso de error
            $record->delete();
            Notification::make()
                ->title('Error')
                ->body('Error al agregar la firma: ' . $e->getMessage())
                ->danger()
                ->send();
            return;
        }

        try {
            // Intentar agregar el estado del proyecto
            $record->estado_proyecto()->create([
                'empleado_id' => auth()->user()->empleado->id,
                'tipo_estado_id' => TipoEstado::where('nombre', 'Borrador')->first()->id,
                'fecha' => now(),
                'comentario' => 'Proyecto creado en borrador',
            ]);
            
            // Enviar el correo al usuario que creó el proyecto
            try {
                Mail::to(auth()->user()->email)->send(new ProyectoCreado($record, auth()->user()));
            } catch (\Exception $emailException) {
                // Log del error pero no fallar la creación del proyecto
                \Log::warning('Error al enviar correo de proyecto creado en borrador: ' . $emailException->getMessage());
            }
            
        } catch (\Exception $e) {
            // Eliminar el proyecto en caso de error
            $record->delete();
            Notification::make()
                ->title('Error')
                ->body('Error al agregar el estado del proyecto: ' . $e->getMessage())
                ->danger()
                ->send();
            return;
        }

        // Notificación de éxito si todo se completó correctamente
        Notification::make()
            ->title('¡Éxito!')
            ->body('Proyecto creado correctamente')
            ->success()
            ->send();

        $this->js('location.reload();');
    }

    public function render(): View
    {
        return view('livewire.proyectos.vinculacion.create-proyecto-vinculacion');
            ;//->layout('components.panel.modulos.modulo-proyectos');
    }
}
