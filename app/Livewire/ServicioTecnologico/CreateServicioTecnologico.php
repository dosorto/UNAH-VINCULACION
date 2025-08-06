<?php

namespace App\Livewire\ServicioTecnologico;

use Livewire\Component;
use Filament\Forms\Form;
use App\Models\ServicioInfraestructura\ServicioTecnologico;
use Illuminate\Support\HtmlString;
use Illuminate\Contracts\View\View;
use Filament\Forms\Components\Wizard;
use Illuminate\Support\Facades\Blade;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Concerns\InteractsWithForms;

//Partes
use App\Livewire\ServicioTecnologico\Secciones\PrimeraParte;
use App\Livewire\ServicioTecnologico\Secciones\EquipoEjecutor;
use App\Livewire\ServicioTecnologico\Secciones\SegundaParte;
use App\Livewire\ServicioTecnologico\Secciones\TerceraParte;


class CreateServicioTecnologico extends Component implements HasForms
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
                        ->description('INFORMACIÓN GENERAL')
                        ->schema(PrimeraParte::form())
                        ->columns(2),

                    Wizard\Step::make('II.')
                        ->description('EQUIPO EJECUTOR DEL PROYECTO')
                        ->schema(EquipoEjecutor::form())
                        ->columns(2),

                    Wizard\Step::make('III.')
                        ->description('DETALLE Y CRONOGRAMA DE ACTIVIDADES')
                        ->schema(SegundaParte::form())
                        ->columns(2),
                    
                    Wizard\Step::make('IV.')
                        ->description('INFORMACIÓN DEL SERVICIO')
                        ->schema(TerceraParte::form())
                        ->columns(2),
                ])
                ->submitAction(new HtmlString(Blade::render(<<<BLADE
                    <x-filament::button
                        type="submit"
                        size="sm"
                        color="info"
                    >
                        Enviar a Guardar
                    </x-filament::button>
                BLADE))),
            ])
            ->statePath('data')
            ->model(ServicioTecnologico::class);
    }

    public function create(): void
    {
        $data = $this->form->getState();

        try {
            //  Solo agregar fecha_registro si ese campo existe en la tabla
            // $data['fecha_registro'] = now();

            $record = ServicioTecnologico::create($data);

            // Guardar relaciones
            $this->form->model($record)->saveRelationships();

            session()->flash('success', 'Servicio tecnológico creado correctamente.');
        } catch (\Exception $e) {
            session()->flash('error', 'Error al crear el servicio: ' . $e->getMessage());
            return;
        }

        $this->js('alert("Servicio creado correctamente"); location.reload();');
    }

    public function render(): View
    {
        return view('livewire.ServicioTecnologico.create-servicio-tecnologico');
    }
}