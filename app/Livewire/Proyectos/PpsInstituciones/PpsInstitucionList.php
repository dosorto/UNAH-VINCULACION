<?php

namespace App\Livewire\Proyectos\PpsInstituciones;

use App\Models\Demografia\Pais;
use App\Models\PpsInstitucion;
use App\Support\Notification;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Validation\Rule;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Catálogo de instituciones / empresas del FORM-DVUS-014. El registro de PPS/SS elige de aquí
 * la institución sin poder modificarla; el administrador corrige o completa sus datos.
 */
class PpsInstitucionList extends Component
{
    use WithPagination;

    public string $search = '';

    public string $filtroTipo = '';

    public bool $showTrashed = false;

    public bool $formModal = false;

    public ?int $editId = null;

    public array $form = [];

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingFiltroTipo(): void
    {
        $this->resetPage();
    }

    public function updatingShowTrashed(): void
    {
        $this->resetPage();
    }

    public function openCreate(): void
    {
        $this->editId = null;
        $this->form = array_fill_keys(PpsInstitucion::CAMPOS, '');
        $this->resetValidation();
        $this->formModal = true;
    }

    public function openEdit(int $id): void
    {
        $institucion = PpsInstitucion::findOrFail($id);

        $this->editId = $institucion->id;
        $this->form = collect(PpsInstitucion::CAMPOS)
            ->mapWithKeys(fn (string $campo) => [$campo => (string) ($institucion->{$campo} ?? '')])
            ->all();
        $this->resetValidation();
        $this->formModal = true;
    }

    public function save(): void
    {
        $institucion = $this->editId ? PpsInstitucion::findOrFail($this->editId) : null;
        $this->form = collect(PpsInstitucion::CAMPOS)
            ->mapWithKeys(fn (string $campo) => [$campo => trim((string) ($this->form[$campo] ?? ''))])
            ->all();

        $reglas = PpsInstitucion::reglas('form.');
        // Los duplicados se evitan por nombre; solo se valida si el nombre cambia.
        if (! $institucion || mb_strtolower($institucion->nombre) !== mb_strtolower($this->form['nombre'])) {
            $reglas['form.nombre'][] = Rule::unique('pps_instituciones', 'nombre')->ignore($this->editId)->whereNull('deleted_at');
        }

        $validated = $this->validate($reglas, [
            'form.nombre.unique' => 'Ya existe otra institución con este nombre.',
            'form.pais.required_if' => 'Indique el país de la institución internacional.',
            'form.pais.exists' => 'Seleccione un país de la lista.',
        ], [
            'form.nombre' => 'nombre',
            'form.nacionalidad' => 'nacionalidad',
            'form.pais' => 'país',
            'form.tipo' => 'tipo de institución',
            'form.sector' => 'sector',
            'form.direccion' => 'dirección',
            'form.representante_legal' => 'representante legal',
            'form.telefono' => 'teléfono',
            'form.correo_rrhh' => 'correo de recursos humanos',
        ]);

        $datos = collect($validated['form'])->map(fn ($valor) => $valor === '' ? null : $valor)->all();
        if ($datos['nacionalidad'] === 'Nacional') {
            $datos['pais'] = null;
        }

        if ($institucion) {
            $institucion->update($datos);
            Notification::make()->title('Institución actualizada.')->success()->send();
        } else {
            PpsInstitucion::create($datos);
            Notification::make()->title('Institución creada.')->success()->send();
        }

        $this->formModal = false;
    }

    public function delete(int $id): void
    {
        $institucion = PpsInstitucion::withCount('registros')->findOrFail($id);

        if ($institucion->registros_count > 0) {
            Notification::make()
                ->title('No se puede eliminar la institución')
                ->body('Está registrada en uno o más formularios de PPS / SS. Puede corregir sus datos con «Editar».')
                ->warning()
                ->send();

            return;
        }

        $institucion->delete();
        Notification::make()->title('Institución eliminada.')->success()->send();
    }

    public function restore(int $id): void
    {
        PpsInstitucion::onlyTrashed()->findOrFail($id)->restore();
        Notification::make()->title('Institución restaurada.')->success()->send();
    }

    private function recordsQuery(): Builder
    {
        return PpsInstitucion::query()
            ->withCount('registros')
            ->when($this->showTrashed, fn (Builder $query) => $query->withTrashed())
            ->when($this->filtroTipo !== '', fn (Builder $query) => $query->where('tipo', $this->filtroTipo))
            ->when(trim($this->search) !== '', function (Builder $query): void {
                $term = '%'.trim($this->search).'%';
                $query->where(function (Builder $nested) use ($term): void {
                    $nested->where('nombre', 'like', $term)
                        ->orWhere('representante_legal', 'like', $term)
                        ->orWhere('correo_rrhh', 'like', $term);
                });
            })
            ->orderBy('nombre');
    }

    public function render(): View
    {
        return view('livewire.proyectos.pps-instituciones.pps-institucion-list', [
            'records' => $this->recordsQuery()->paginate(15),
            'tipos' => PpsInstitucion::TIPOS,
            'sectores' => PpsInstitucion::SECTORES,
            'nacionalidades' => PpsInstitucion::NACIONALIDADES,
            'paises' => Pais::where('nombre', '!=', 'Honduras')->orderBy('nombre')->pluck('nombre'),
        ]);
    }
}
