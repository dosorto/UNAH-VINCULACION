<?php

namespace App\Livewire\Proyectos\Actualizacion;

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


// Para enviar Emails
// use App\Mail\Correos\CorreoParticipacion;
use App\Livewire\Proyectos\Actualizacion\Secciones\PrimeraParte;


class EditProyectoActualizacion extends Component implements HasForms
{
    use InteractsWithForms;

    public ?array $data = [];

    public Proyecto $record;

    public function mount(Proyecto $proyecto)
    {
        $this->record = $proyecto;
        // validar que el coordinador del proyecto sea el usuario logueado
        if ($this->record->coordinador_proyecto[0]->empleado_id != auth()->user()->empleado->id) {
            Notification::make()
                ->title('¡Error!')
                ->body('No tienes permisos para editar este proyecto')
                ->danger()
                ->send();
            return redirect()->route('proyectosDocente');
        }


        if (in_array($this->record->obtenerUltimoEstado()
            ->tipo_estado_id, TipoEstado::whereIn('nombre', ['Borrador', 'Subsanacion', 'Enlace Vinculacion', 'En Revision', 'Finalizado', 'En Curso', 'Director Centro'])
            ->pluck('id')->toArray())) {
            // Cargar las relaciones necesarias para el formulario
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
            Notification::make()
                ->title('¡Error!')
                ->body('El proyecto no se encuentra en estado de Borrador o Subsanación')
                ->danger()
                ->send();
            return redirect()->route('proyectosDocente');
        }

        // $this->form->fill($this->record->attributesToArray());
    }

    public function form(Form $form): Form
    {
       // $primeraParte = app(\App\Livewire\Proyectos\Actualizacion\Secciones\PrimeraParte::class, ['proyecto_id' => $this->record->id]);
        return $form
            ->schema([
                Wizard::make([
                    Wizard\Step::make('I.')
                        ->description('ACTUALIZACIÓN DEL EQUIPO EJECUTOR')
                        ->schema(
                           // $primeraParte->form(),
                           PrimeraParte::form(),
                        )
                        ->columns(2),
                ])->submitAction(new HtmlString(Blade::render(<<<BLADE
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
            ->model($this->record);
    }

    public function save()
    {
        $data = $this->form->getState();

        $this->record->update($data);

        // $firmaP = $this->record->firma_proyecto()->create([
        //     'empleado_id' => auth()->user()->empleado->id,
        //     'cargo_firma_id' => CargoFirma::join('tipo_cargo_firma', 'tipo_cargo_firma.id', '=', 'cargo_firma.tipo_cargo_firma_id')
        //         ->where('tipo_cargo_firma.nombre', 'Coordinador Proyecto')
        //         ->where('cargo_firma.descripcion', 'Proyecto')
        //         ->first()->id,
        //     'estado_revision' => 'Aprobado',
        //     'firma_id' => auth()->user()->empleado->firma->id,
        //     'sello_id' => auth()->user()->empleado->sello->id,
        //     'hash' => 'hash'
        // ]);

        $firmaP = $this->record->firma_proyecto()->updateOrCreate(
            // Condiciones para buscar un registro existente
            [
                'empleado_id' => auth()->user()->empleado->id,
                'cargo_firma_id' => CargoFirma::join('tipo_cargo_firma', 'tipo_cargo_firma.id', '=', 'cargo_firma.tipo_cargo_firma_id')
                    ->where('tipo_cargo_firma.nombre', 'Coordinador Proyecto')
                    ->where('cargo_firma.descripcion', 'Proyecto')
                    ->first()->id,
            ],
            // Valores para actualizar o crear
            [
                'empleado_id' => auth()->user()->empleado->id,
                'cargo_firma_id' => CargoFirma::join('tipo_cargo_firma', 'tipo_cargo_firma.id', '=', 'cargo_firma.tipo_cargo_firma_id')
                    ->where('tipo_cargo_firma.nombre', 'Coordinador Proyecto')
                    ->where('cargo_firma.descripcion', 'Proyecto')
                    ->first()->id,
                'estado_revision' => 'Aprobado',
                'firma_id' => auth()->user()?->empleado?->firma?->id,
                'sello_id' => auth()->user()?->empleado?->sello?->id,
                'hash' => 'hash',
                'fecha_firma' => now(),
            ]
        );

        $this->record->estado_proyecto()->create([
            'empleado_id' => auth()->user()->empleado->id,
            'tipo_estado_id' => $firmaP->cargo_firma->estado_siguiente_id,
            'fecha' => now(),
            'comentario' => 'Se ha creado el proyecto y se ha enviado a firmar',
        ]);

        Notification::make()
            ->title('¡Éxito!')
            ->body('El proyecto ha sido enviado a firmar exitosamente')
            ->success()
            ->send();
        return redirect()->route('proyectosDocente');
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
            $creadorEmail = auth()->user()->email; //  usuario autenticado quien crea el proyecto
            $nombreProyecto = $record->nombre_proyecto; // Nombre del proyecto
            $empleadoNombre = auth()->user()->empleado->nombre_completo;  // Nombre del usuario
           // $empleadoCorreo = auth()->user()->email;  // Correo del usuario 
           // SendEmailJob::dispatch($creadorEmail, 'correoEstado', $estadoNombre, $nombreProyecto, $empleadoNombre);
            
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

    public function borrador(): void
    {
        $data = $this->form->getState();

        // dd($data);
        $this->record->update($data);
        $this->record->save();
        // dd($this->record->presupuesto);
        Notification::make()
            ->title('¡Éxito!')
            ->body('Cambios guardados')
            ->success()
            ->send();
        $this->js('location.reload();');
    }

    public function render(): View
    {
        return view('livewire.proyectos.actualizacion.create-proyecto-actualizacion')
            ;//->layout('components.panel.modulos.modulo-proyectos');
    }
}
