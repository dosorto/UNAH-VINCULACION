<?php

namespace App\Livewire\Proyectos\Contrapartes;

use App\Models\Proyecto\EntidadContraparte;
use App\Support\Notification;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Validation\Rule;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Catálogo de entidades contraparte. El docente elige de aquí la contraparte de su proyecto
 * sin poder modificarla; el administrador corrige o completa sus datos.
 */
class EntidadContraparteList extends Component
{
    use WithPagination;

    private const CAMPOS = ['rtn', 'nombre', 'tipo_entidad', 'nombre_contacto', 'cargo_contacto', 'telefono', 'correo'];

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
        $this->form = array_fill_keys(self::CAMPOS, '');
        $this->resetValidation();
        $this->formModal = true;
    }

    public function openEdit(int $id): void
    {
        $contraparte = EntidadContraparte::findOrFail($id);

        $this->editId = $contraparte->id;
        $this->form = collect(self::CAMPOS)
            ->mapWithKeys(fn (string $campo) => [$campo => (string) ($contraparte->{$campo} ?? '')])
            ->all();
        $this->resetValidation();
        $this->formModal = true;
    }

    public function save(): void
    {
        $contraparte = $this->editId ? EntidadContraparte::findOrFail($this->editId) : null;
        $this->form = collect(self::CAMPOS)
            ->mapWithKeys(fn (string $campo) => [$campo => trim((string) ($this->form[$campo] ?? ''))])
            ->all();

        // Los duplicados que ya existen se pueden seguir editando; solo se valida el nombre si cambia.
        $nombreCambio = !$contraparte || mb_strtolower($contraparte->nombre) !== mb_strtolower($this->form['nombre']);

        $validated = $this->validate([
            'form.rtn' => [
                ...EntidadContraparte::reglasRtn(),
                Rule::unique('entidad_contraparte', 'rtn')->ignore($this->editId)->whereNull('deleted_at'),
            ],
            'form.nombre' => array_filter([
                'required', 'string', 'max:255',
                $nombreCambio ? Rule::unique('entidad_contraparte', 'nombre')->ignore($this->editId)->whereNull('deleted_at') : null,
            ]),
            'form.tipo_entidad' => ['required', Rule::in(array_keys(EntidadContraparte::TIPOS))],
            'form.nombre_contacto' => ['nullable', 'string', 'max:255'],
            'form.cargo_contacto' => ['nullable', 'string', 'max:255'],
            'form.telefono' => ['nullable', 'string', 'max:255'],
            'form.correo' => ['nullable', 'email', 'max:255'],
        ], [
            'form.rtn.unique' => 'Ya existe otra contraparte con este RTN.',
            'form.nombre.unique' => 'Ya existe otra contraparte con este nombre.',
        ], [
            'form.rtn' => 'RTN / identificador fiscal',
            'form.nombre' => 'nombre',
            'form.tipo_entidad' => 'tipo de contraparte',
            'form.nombre_contacto' => 'nombre del contacto',
            'form.cargo_contacto' => 'cargo del contacto',
            'form.telefono' => 'teléfono',
            'form.correo' => 'correo electrónico',
        ]);

        $datos = collect($validated['form'])->map(fn ($valor) => $valor === '' ? null : $valor)->all();

        if ($contraparte) {
            $contraparte->update($datos);
            Notification::make()->title('Contraparte actualizada.')->success()->send();
        } else {
            EntidadContraparte::create($datos);
            Notification::make()->title('Contraparte creada.')->success()->send();
        }

        $this->formModal = false;
    }

    public function delete(int $id): void
    {
        $contraparte = EntidadContraparte::withCount('vinculacionesProyecto')->findOrFail($id);

        if ($contraparte->vinculaciones_proyecto_count > 0) {
            Notification::make()
                ->title('No se puede eliminar la contraparte')
                ->body('Está registrada en uno o más proyectos. Puede corregir sus datos con «Editar».')
                ->warning()
                ->send();

            return;
        }

        $contraparte->delete();
        Notification::make()->title('Contraparte eliminada.')->success()->send();
    }

    public function restore(int $id): void
    {
        EntidadContraparte::onlyTrashed()->findOrFail($id)->restore();
        Notification::make()->title('Contraparte restaurada.')->success()->send();
    }

    private function recordsQuery(): Builder
    {
        return EntidadContraparte::query()
            ->withCount('vinculacionesProyecto')
            ->when($this->showTrashed, fn (Builder $query) => $query->withTrashed())
            ->when($this->filtroTipo !== '', fn (Builder $query) => $query->where('tipo_entidad', $this->filtroTipo))
            ->when(trim($this->search) !== '', function (Builder $query): void {
                $term = '%'.trim($this->search).'%';
                $query->where(function (Builder $nested) use ($term): void {
                    $nested->where('nombre', 'like', $term)
                        ->orWhere('rtn', 'like', $term)
                        ->orWhere('nombre_contacto', 'like', $term)
                        ->orWhere('correo', 'like', $term);
                });
            })
            ->orderBy('nombre');
    }

    public function render(): View
    {
        return view('livewire.proyectos.contrapartes.entidad-contraparte-list', [
            'records' => $this->recordsQuery()->paginate(15),
            'tipos' => EntidadContraparte::TIPOS,
        ]);
    }
}
