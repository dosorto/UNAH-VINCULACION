<?php

namespace App\Livewire\DAFT\Programas;

use App\Models\DAFT\TipoPrograma;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Storage;
use Livewire\Component;
use Livewire\WithFileUploads;

class ListTiposPrograma extends Component
{
    use WithFileUploads;

    public ?int $editingTipoProgramaId = null;

    public array $tipoPrograma = [
        'nombre' => '',
        'horas_minimas' => null,
        'horas_maximas' => null,
    ];

    public $plantillaDocumento = null;

    protected array $validationAttributes = [
        'tipoPrograma.nombre' => 'nombre',
        'tipoPrograma.horas_minimas' => 'horas mínimas',
        'tipoPrograma.horas_maximas' => 'horas máximas',
        'plantillaDocumento' => 'plantilla .docx',
    ];

    protected array $messages = [
        'tipoPrograma.nombre.required' => 'El nombre es obligatorio.',
        'tipoPrograma.horas_minimas.required' => 'Las horas mínimas son obligatorias.',
        'tipoPrograma.horas_maximas.required' => 'Las horas máximas son obligatorias.',
        'plantillaDocumento.required' => 'La plantilla .docx es obligatoria.',
        'plantillaDocumento.file' => 'La plantilla debe ser un archivo válido.',
        'plantillaDocumento.mimes' => 'La plantilla debe ser un archivo .docx.',
        'plantillaDocumento.max' => 'La plantilla no debe pesar más de 5 MB.',
    ];

    public function saveTipoPrograma(): void
    {
        $rules = [
            'tipoPrograma.nombre' => ['required', 'string', 'max:255'],
            'tipoPrograma.horas_minimas' => ['required', 'integer', 'min:0'],
            'tipoPrograma.horas_maximas' => ['required', 'integer', 'min:0'],
            'plantillaDocumento' => [$this->editingTipoProgramaId ? 'nullable' : 'required', 'file', 'mimes:docx', 'max:5120'],
        ];

        $validated = $this->validate($rules);

        $payload = [
            'nombre' => $validated['tipoPrograma']['nombre'],
            'horas_minimas' => $validated['tipoPrograma']['horas_minimas'],
            'horas_maximas' => $validated['tipoPrograma']['horas_maximas'],
            'activo' => true,
        ];

        if ($this->plantillaDocumento) {
            $path = $this->plantillaDocumento->store('daft/plantillas', 'public');
            $payload['plantilla_docx_path'] = $path;
        }

        TipoPrograma::updateOrCreate(
            ['id' => $this->editingTipoProgramaId],
            $payload
        );

        $this->resetForm();
        session()->flash('tipos_programa_status', 'Tipo de programa guardado.');
    }

    public function editTipoPrograma(int $tipoId): void
    {
        $tipo = TipoPrograma::findOrFail($tipoId);
        $this->editingTipoProgramaId = $tipo->id;
        $this->tipoPrograma = [
            'nombre' => $tipo->nombre,
            'horas_minimas' => $tipo->horas_minimas,
            'horas_maximas' => $tipo->horas_maximas,
        ];
        $this->plantillaDocumento = null;
    }

    public function cancelEditTipoPrograma(): void
    {
        $this->resetForm();
    }

    public function toggleTipoPrograma(int $tipoId): void
    {
        $tipo = TipoPrograma::findOrFail($tipoId);
        $tipo->activo = ! $tipo->activo;
        $tipo->save();
    }

    public function render(): View
    {
        $tiposPrograma = TipoPrograma::orderBy('nombre')->get();

        return view('livewire.daft.programas.list-tipos-programa', compact('tiposPrograma'))
            ->layout('layouts.app', ['hideHorizontalNav' => true]);
    }

    protected function resetForm(): void
    {
        $this->editingTipoProgramaId = null;
        $this->tipoPrograma = [
            'nombre' => '',
            'horas_minimas' => null,
            'horas_maximas' => null,
        ];
        $this->plantillaDocumento = null;
    }
}
