<?php

namespace App\Livewire\Proyectos\Vinculacion;

use Filament\Forms;
use App\Models\User;
use Filament\Forms\Get;
use Livewire\Component;
use Filament\Forms\Form;
use App\Models\Demografia\Aldea;
use App\Models\Demografia\Ciudad;


use App\Models\Estado\TipoEstado;
use App\Models\Personal\Empleado;
use App\Models\Proyecto\Proyecto;
use App\Models\Proyecto\Modalidad;
use Illuminate\Support\HtmlString;

use App\Models\Proyecto\CargoFirma;
use Illuminate\Contracts\View\View;
use App\Models\Demografia\Municipio;


use App\Models\Estudiante\Estudiante;

use Filament\Forms\Components\Hidden;

use Filament\Forms\Components\Select;

use Filament\Forms\Components\Toggle;

use Filament\Forms\Components\Wizard;
use Illuminate\Support\Facades\Blade;
use Filament\Forms\Components\Builder;
use Filament\Forms\Components\Section;
use Filament\Forms\Contracts\HasForms;
use App\Models\Demografia\Departamento;

use App\Models\UnidadAcademica\Carrera;

use Filament\Forms\Components\Fieldset;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;

use App\Models\Personal\EmpleadoProyecto;

use Filament\Forms\Components\DatePicker;

use Filament\Forms\Components\FileUpload;
use App\Models\UnidadAcademica\FacultadCentro;
use Filament\Forms\Concerns\InteractsWithForms;
use App\Models\UnidadAcademica\EntidadAcademica;

use App\Models\UnidadAcademica\DepartamentoAcademico;
use App\Livewire\Proyectos\Vinculacion\Secciones\SextaParte;
use App\Livewire\Proyectos\Vinculacion\Secciones\CuartaParte;
use App\Livewire\Proyectos\Vinculacion\Secciones\QuintaParte;
use App\Livewire\Proyectos\Vinculacion\Secciones\PrimeraParte;
use App\Livewire\Proyectos\Vinculacion\Secciones\SegundaParte;
use App\Livewire\Proyectos\Vinculacion\Secciones\TerceraParte;

class EditProyectoVinculacionForm extends Component implements HasForms
{
    use InteractsWithForms;

    public ?array $data = [];

    public Proyecto $record;

    public function mount(Proyecto $proyecto)
    {
        $this->record = $proyecto;

        if (in_array($this->record->obtenerUltimoEstado()->tipo_estado_id, TipoEstado::whereIn('nombre', ['Borrador', 'Subsanacion'])->pluck('id')->toArray())) {
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
        return $form
            ->schema([
                Wizard::make([
                    Wizard\Step::make('I.')
                        ->description('Información general del proyecto')
                        ->schema(
                            PrimeraParte::form()
                            // CuartaParte::form(),
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
                'firma_id' => auth()->user()->empleado->firma->id,
                'sello_id' => auth()->user()->empleado->sello->id,
                'hash' => 'hash',
            ]
        );

        $this->record->estado_proyecto()->create([
            'empleado_id' => auth()->user()->empleado->id,
            'tipo_estado_id' => $firmaP->cargo_firma->estado_siguiente_id,
            'fecha' => now(),
            'comentario' => 'Proyecto creado',
        ]);

        Notification::make()
            ->title('¡Éxito!')
            ->body('El proyecto ha sido enviado a firmar exitosamente')
            ->success()
            ->send();
        return redirect()->route('proyectosDocente');
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
        return view('livewire.proyectos.vinculacion.edit-proyecto-vinculacion-form')
            ->layout('components.panel.modulos.modulo-proyectos');
    }
}
