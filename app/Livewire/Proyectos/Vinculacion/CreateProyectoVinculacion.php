<?php

namespace App\Livewire\Proyectos\Vinculacion;

use Livewire\Component;
use Filament\Forms\Form;
use App\Jobs\SendEmailJob;
use App\Livewire\Components\Paso;
use App\Livewire\Components\PasosContainer;
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
use Filament\Forms\Components\Actions\Action;
use Filament\Forms\Components\Wizard\Step;

class CreateProyectoVinculacion extends Component implements HasForms
{
    use InteractsWithForms;

    public ?array $data = [];
    public ?Proyecto $record = null;

    public $model;


    // cambiar esta funcion a un middleware !!!!!!!!!!!
    public function authorizar(Proyecto $record): bool
    {
        // validar que el coordinador del proyecto sea el usuario logueado
        if (
            $record->coordinadorIsCurrentUser()
            && $record->proyectoIsInAnyEstados(['Borrador', 'Subsanación', 'Autoguardado'])
        )
            return true;

        return false;
    }

    public function mount(?Proyecto $record): void
    {
        $this->record = $record;
        $this->model = $this->record->exists ? $this->record : Proyecto::class;

        //$this->form->fill();

        if ($this->record->exists && !$this->authorizar($record))
            abort(403, 'No autorizado para editar este proyecto.');

        if ($this->record->exists) {


            $this->record->load([
                'objetivosEspecificos.resultados',
                'integrantes',
                'estudiante_proyecto',
                'entidad_contraparte.instrumento_formalizacion',
                'actividades.empleados',
                'presupuesto',
                'superavit',
                'ods',
                'anexos',
                'coordinador_proyecto.empleado'
            ]);
            $this->form->fill($this->record->attributesToArray());
        } else {
            $this->form->fill();
        }
    }

    public function saveData(Step $step)
    {
        // Extraer los datos del formulario del paso actual
        $data = $step->getChildComponentContainer()->getState();

        // Si no hay registro, lo creamos
        if (!$this->record || !$this->record->exists) {
            $this->record = \App\Models\Proyecto\Proyecto::create($data);

            // Obtenemos el contenedor del step
            $container = $step->getChildComponentContainer();

            // Asociamos el modelo al contenedor
            $container->model($this->record);

            // Guardamos las relaciones del paso actual
            $container->saveRelationships();

            $this->record->agregarEstadoByName(
                empleado: auth()->user()->empleado,
                tipoEstadoNombre: "Autoguardado",
                comentario: 'Proyecto guardado como Autoguardado',
            );

            // redireccionar a la misma pagina pero con el id del proyecto creado

            return $this->redirectRoute('crearProyectoVinculacion', ['record' => $this->record->id, 'step' => "ii"]);
        } else {
            // Si ya existe, lo actualizamos con los datos del paso
            $this->record->update($data);

            $container = $step->getChildComponentContainer();

            // Asociamos el modelo al contenedor
            $container->model($this->record);

            // Guardamos las relaciones del paso actual
            $container->saveRelationships();
        }

        // enviar una notificacion que sus datos se han guardado
        Notification::make()
            ->title('¡Datos guardados!')
            ->body('Los datos del paso se han guardado correctamente.')
            ->success()
            ->send();
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
                        ->afterValidation(fn(Step $step) => $this->saveData($step))
                        ->columns(2),
                    Wizard\Step::make('II.')
                        ->description('EQUIPO EJECUTOR DEL PROYECTO')
                        ->schema(
                            EquipoEjecutor::form(),
                        )
                        ->afterValidation(fn(Step $step) => $this->saveData($step)),
                    Wizard\Step::make('III.')
                        ->description('INFORMACIÓN DE LA ENTIDAD CONTRAPARTE DEL PROYECTO (en caso de contar con una contraparte).')
                        ->schema(
                            SegundaParte::form(),
                        )
                        ->afterValidation(fn(Step $step) => $this->saveData($step)),
                    Wizard\Step::make('IV.')
                        ->description('CRONOGRAMA DE ACTIVIDADES.')
                        ->schema(
                            TerceraParte::form(),
                        )
                        ->afterValidation(fn(Step $step) => $this->saveData($step)),
                    Wizard\Step::make('V.')
                        ->description('DATOS DEL PROYECTO')
                        ->schema(
                            CuartaParte::form(),
                        )
                        ->afterValidation(fn(Step $step) => $this->saveData($step))
                        ->columns(2),
                    Wizard\Step::make('VI.')
                        ->description('RESUMEN MARCO LÓGICO DEL PROYECTO')
                        ->schema(
                            MarcoLogico::form(),
                        )
                        ->afterValidation(fn(Step $step) => $this->saveData($step))
                        ->columns(2),
                    Wizard\Step::make('VII.')
                        ->description('DETALLES DEL PRESUPUESTO')
                        ->schema(
                            Presupuesto::form(),
                        )
                        ->afterValidation(fn(Step $step) => $this->saveData($step))
                        ->columns(2),
                    Wizard\Step::make('VIII.')
                        ->description('ANEXOS')
                        ->schema(
                            QuintaParte::form(),
                        )
                        ->afterValidation(fn(Step $step) => $this->saveData($step)),
                    Wizard\Step::make('IX.')
                        ->description('FIRMAS')
                        ->schema(
                            SextaParte::form(),
                        )
                        ->afterValidation(fn(Step $step) => dd($this->saveData($step))),
                ])
                    ->nextAction(
                        fn(Action $action) => $action
                            ->label('Siguiente Paso')
                            ->size('sm')
                            ->color('primary')
                    )
                    // aca se puede personalizar la accion del paso anterior
                    ->previousAction(
                        fn(Action $action) => $action
                            ->label('Paso Anterior')
                            ->size('sm')
                            ->color('primary')
                    )
                    // aca se puede personalizar en que paso aparece por defecto
                    // posiblemente sea mas intuitivo para el usuario continuar justo por donde lo dejo,
                    // pero para eso el proyecto debe almacenar en que paso se cerro
                    // o el paso anterior
                    ->persistStepInQueryString()
                    ->submitAction(new HtmlString(Blade::render(<<<BLADE
                
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
            ->model($this->model);
    }

    public function create(): void
    {
        $data = $this->form->getState();
        try {


            $empleado = auth()->user()->empleado;
            $this->record->update($data);
            $this->form->model($this->record)->saveRelationships();


            $this->record->agregarFirma(
                cargoFirma: 'Coordinador Proyecto',
                empleado: $empleado
            );

            // Intentar agregar el estado del proyecto
            $this->record->agregarEstadoByName(
                empleado: $empleado,
                tipoEstadoNombre: "Enlace Vinculacion",
                comentario: 'Proyecto enviado para firma',
            );
        } catch (\Exception $e) {

            dd($e->getMessage());
            Notification::make()
                ->title('Error')
                ->body('Error al procesar el proyecto: ' . $e->getMessage())
                ->danger()
                ->send();
            return;
        }

        try {
            Mail::to(auth()->user()->email)->send(new ProyectoCreado($this->record, auth()->user()));
        } catch (\Exception $emailException) {
            // Log del error pero no fallar la creación del proyecto
            \Log::warning('Error al enviar correo de proyecto creado: ' . $emailException->getMessage());
        }


        // Notificación de éxito si todo se completó correctamente
        Notification::make()
            ->title('¡Éxito!')
            ->body('Proyecto creado correctamente')
            ->success()
            ->send();

       // redirigir al usuario a la pagina de proyectos
       redirect()->route('proyectosDocente');
    }


    // optimizar esta funcion para despues, es demasiado redundante y lo unico que cambia es el nombre del estado :)
    public function borrador()
    {
        $data = $this->form->getState();
        try {

            $empleado = auth()->user()->empleado;
            $this->record->update($data);
            $this->form->model($this->record)->saveRelationships();

            $this->record->agregarFirma(
                cargoFirma: 'Coordinador Proyecto',
                empleado: $empleado
            );

            // Intentar agregar el estado del proyecto
            $this->record->agregarEstadoByName(
                empleado: $empleado,
                tipoEstadoNombre: 'Borrador',
                comentario: 'Proyecto guardado como borrador',
            );
        } catch (\Exception $e) {
            dd($e->getMessage());
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

        redirect()->route('proyectosDocente');
    }

    public function render(): View
    {
        return view('livewire.proyectos.vinculacion.create-proyecto-vinculacion');; //->layout('components.panel.modulos.modulo-proyectos');
    }
}
