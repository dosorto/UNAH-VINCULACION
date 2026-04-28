<?php

namespace App\Livewire\UnidadAcademica\Carrera;

use App\Models\UnidadAcademica\Carrera;
use App\Models\UnidadAcademica\DepartamentoAcademico;
use App\Models\UnidadAcademica\FacultadCentro;
use App\Support\Notification;
use Illuminate\Contracts\View\View;
use Livewire\Component;
use Livewire\WithPagination;

class CarreraList extends Component
{
    use WithPagination;

    public string $search = '';
    public bool $showTrashed = false;

    public bool $createModal = false;
    public string $create_nombre = '';
    public string $create_siglas = '';
    public ?int $create_facultad_centro_id = null;
    public ?int $create_departamento_academico_id = null;
    public array $create_facultad_centros = [];

    public bool $editModal = false;
    public ?int $editId = null;
    public string $edit_nombre = '';
    public string $edit_siglas = '';
    public ?int $edit_facultad_centro_id = null;
    public ?int $edit_departamento_academico_id = null;
    public array $edit_facultad_centros = [];

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function openCreate(): void
    {
        $this->reset(['create_nombre', 'create_siglas', 'create_facultad_centro_id', 'create_departamento_academico_id', 'create_facultad_centros']);
        $this->createModal = true;
    }

    public function store(): void
    {
        $this->validate([
            'create_nombre'                    => 'required|string|max:255',
            'create_siglas'                    => 'nullable|string|max:10',
            'create_facultad_centro_id'        => 'nullable|exists:centro_facultad,id',
            'create_departamento_academico_id' => 'required|exists:departamento_academico,id',
            'create_facultad_centros'          => 'array',
            'create_facultad_centros.*'        => 'exists:centro_facultad,id',
        ]);

        $carrera = Carrera::create([
            'nombre'                   => $this->create_nombre,
            'siglas'                   => $this->create_siglas,
            'facultad_centro_id'       => $this->create_facultad_centro_id,
            'departamento_academico_id'=> $this->create_departamento_academico_id,
        ]);

        $carrera->facultadCentros()->sync($this->create_facultad_centros);

        $this->createModal = false;
        Notification::make()->title('Carrera creada.')->success()->send();
    }

    public function openEdit(int $id): void
    {
        $carrera = Carrera::with('facultadCentros')->findOrFail($id);
        $this->editId                        = $id;
        $this->edit_nombre                   = $carrera->nombre;
        $this->edit_siglas                   = $carrera->siglas ?? '';
        $this->edit_facultad_centro_id       = $carrera->facultad_centro_id;
        $this->edit_departamento_academico_id= $carrera->departamento_academico_id;
        $this->edit_facultad_centros         = $carrera->facultadCentros->pluck('id')->toArray();
        $this->editModal                     = true;
    }

    public function save(): void
    {
        $this->validate([
            'edit_nombre'                    => 'required|string|max:255',
            'edit_siglas'                    => 'nullable|string|max:10',
            'edit_facultad_centro_id'        => 'nullable|exists:centro_facultad,id',
            'edit_departamento_academico_id' => 'required|exists:departamento_academico,id',
            'edit_facultad_centros'          => 'array',
            'edit_facultad_centros.*'        => 'exists:centro_facultad,id',
        ]);

        $carrera = Carrera::findOrFail($this->editId);
        $carrera->update([
            'nombre'                   => $this->edit_nombre,
            'siglas'                   => $this->edit_siglas,
            'facultad_centro_id'       => $this->edit_facultad_centro_id,
            'departamento_academico_id'=> $this->edit_departamento_academico_id,
        ]);
        $carrera->facultadCentros()->sync($this->edit_facultad_centros);

        $this->editModal = false;
        Notification::make()->title('Carrera actualizada.')->success()->send();
    }

    public function delete(int $id): void
    {
        Carrera::findOrFail($id)->delete();
        Notification::make()->title('Carrera eliminada.')->success()->send();
    }

    public function restore(int $id): void
    {
        Carrera::withTrashed()->findOrFail($id)->restore();
        Notification::make()->title('Carrera restaurada.')->success()->send();
    }

    public function render(): View
    {
        $records = Carrera::with(['facultadcentro', 'departamentoAcademico', 'facultadCentros'])
            ->when($this->showTrashed, fn($q) => $q->withTrashed())
            ->when($this->search, fn($q) =>
                $q->where('nombre', 'like', '%'.$this->search.'%')
                  ->orWhere('siglas', 'like', '%'.$this->search.'%')
            )
            ->orderBy('nombre')
            ->paginate(10);

        return view('livewire.unidad-academica.carrera.carrera-list', [
            'records'              => $records,
            'facultades'           => FacultadCentro::where('es_facultad', 1)->orderBy('nombre')->pluck('nombre', 'id'),
            'centros'              => FacultadCentro::where('es_facultad', 0)->orderBy('nombre')->pluck('nombre', 'id'),
            'departamentosAcad'    => DepartamentoAcademico::orderBy('nombre')->pluck('nombre', 'id'),
        ]);
    }
}
