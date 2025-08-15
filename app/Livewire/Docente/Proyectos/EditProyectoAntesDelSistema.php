<?php

namespace App\Livewire\Docente\Proyectos;

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
use App\Livewire\Proyectos\Vinculacion\Secciones\MarcoLogico;
use App\Livewire\Proyectos\Vinculacion\Secciones\Presupuesto;
use App\Livewire\Proyectos\Vinculacion\Secciones\EquipoEjecutor;


class editProyectoAntesDelSistema extends Component implements HasForms
{
    use InteractsWithForms;

    public ?array $data = [];

    public Proyecto $record;

    public function mount(Proyecto $proyecto): void
    {
        $this->record = $proyecto;
        
        // Verificar que el proyecto esté en estado PendienteInformacion o Finalizado
        $estadosPermitidos = TipoEstado::whereIn('nombre', ['PendienteInformacion', 'Finalizado'])->pluck('id', 'nombre');
        
        if ($estadosPermitidos->isEmpty()) {
            Notification::make()
                ->title('¡Error de configuración!')
                ->body('Los estados necesarios no existen en el sistema.')
                ->danger()
                ->send();
            redirect()->route('proyectosAntesDelSistema');
            return;
        }

        // Verificar el estado actual del proyecto
        $estadoActual = $this->record->tipo_estado;
        
        if (!$estadoActual) {
            Notification::make()
                ->title('¡Error!')
                ->body('Este proyecto no tiene un estado asignado.')
                ->danger()
                ->send();
            redirect()->route('proyectosAntesDelSistema');
            return;
        }

        if (!$estadosPermitidos->contains($estadoActual->id)) {
            Notification::make()
                ->title('¡Error!')
                ->body('Este proyecto no está disponible para edición. Estado actual: ' . ($estadoActual->nombre ?: 'Sin nombre') . '. Estados permitidos: PendienteInformacion, Finalizado')
                ->danger()
                ->send();
            redirect()->route('proyectosAntesDelSistema');
            return;
        }

        // Verificar que el usuario sea participante del proyecto
        $esParticipante = $this->record->docentes_proyecto()
            ->where('empleado_id', auth()->user()->empleado->id)
            ->exists();
        
        if (!$esParticipante) {
            Notification::make()
                ->title('¡Error!')
                ->body('No tienes permisos para editar este proyecto.')
                ->danger()
                ->send();
            redirect()->route('proyectosAntesDelSistema');
            return;
        }

        // Cargar las relaciones necesarias
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
            'categoria',
            'ejes_prioritarios_unah',
            'metasContribuye',
            'docentes_proyecto.empleado',
            'coordinador_proyecto.empleado',
        ]);

        $this->form->fill($this->record->attributesToArray());
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
                ])->submitAction(new HtmlString(Blade::render(<<<BLADE
                <x-filament::button
                    type="submit"
                    size="sm"
                    wire:loading.attr="disabled"
                    wire:loading.class="opacity-50 cursor-not-allowed"
                    class="bg-blue-600 hover:bg-blue-700 text-white"
                >
                    <span wire:loading.remove>Guardar Proyecto Completo</span>
                    <span wire:loading>Guardando...</span>
                </x-filament::button>
                BLADE)))
            ])
            ->statePath('data')
            ->model($this->record);
    }

   public function save(): void
    {
        $data = $this->form->getState();

        try {
            // Actualizar el proyecto
            $this->record->update($data);
            $this->form->model($this->record)->saveRelationships();

            // Cambiar el estado a "Finalizado" solo si está en "PendienteInformacion"
            $estadoActual = $this->record->tipo_estado;
            if ($estadoActual && $estadoActual->nombre === 'PendienteInformacion') {
                $estadoFinalizado = TipoEstado::where('nombre', 'Finalizado')->first();
                if ($estadoFinalizado) {
                    // Actualizar el estado actual del proyecto
                    $this->record->estado_proyecto()->update([
                        'tipo_estado_id' => $estadoFinalizado->id,
                        'fecha' => now(),
                        'comentario' => 'Proyecto completado desde "Proyectos Antes del Sistema"',
                        'empleado_id' => auth()->user()->empleado->id
                    ]);
                }
                
                $mensaje = 'Proyecto completado exitosamente. El estado se ha actualizado a "Finalizado" y permanece disponible en esta sección.';
            } else {
                $mensaje = 'Proyecto actualizado exitosamente. Permanece disponible en esta sección.';
            }

            Notification::make()
                ->title('¡Éxito!')
                ->body($mensaje)
                ->success()
                ->send();

            // Redireccionar de vuelta a la vista de Proyectos Antes del Sistema
            $this->redirect(route('proyectosAntesDelSistema'));

        } catch (\Exception $e) {
            Notification::make()
                ->title('Error')
                ->body('Error al actualizar el proyecto: ' . $e->getMessage())
                ->danger()
                ->send();
        }
    }

    public function render(): View
    {
        return view('livewire.docente.proyectos.edit-proyecto-antes-del-sistema');
    }
}
