<?php

namespace App\Livewire\DAFT\Programas;

use App\Models\DAFT\TipoPrograma;
use Illuminate\Contracts\View\View;
use Livewire\Component;
use Livewire\WithFileUploads;

class ListTiposPrograma extends Component
{
    use WithFileUploads;

    public ?int $editingTipoProgramaId = null;

    public array $tipoPrograma = [
        'nombre' => '',
        'modalidad_duracion' => 'HORAS',
        'horas_minimas' => null,
        'horas_maximas' => null,
        'dias_minimos' => null,
        'dias_maximos' => null,
        'horas_minimas_por_dia' => null,
        'dias_consecutivos' => false,
    ];

    public $plantillaDocumento = null;

    protected array $validationAttributes = [
        'tipoPrograma.nombre' => 'nombre',
        'tipoPrograma.horas_minimas' => 'horas mínimas',
        'tipoPrograma.horas_maximas' => 'horas máximas',
        'tipoPrograma.modalidad_duracion' => 'modalidad de duración',
        'tipoPrograma.dias_minimos' => 'días mínimos',
        'tipoPrograma.dias_maximos' => 'días máximos',
        'tipoPrograma.horas_minimas_por_dia' => 'horas mínimas por día',
        'plantillaDocumento' => 'plantilla .docx',
    ];

    protected array $messages = [
        'tipoPrograma.nombre.required' => 'El nombre es obligatorio.',
        'tipoPrograma.horas_minimas.required' => 'Las horas mínimas son obligatorias.',
        'tipoPrograma.horas_maximas.required' => 'Las horas máximas son obligatorias.',
        'tipoPrograma.horas_maximas.gte' => 'Las horas máximas deben ser mayores o iguales que las horas mínimas.',
        'tipoPrograma.dias_minimos.required' => 'Los días mínimos son obligatorios.',
        'tipoPrograma.dias_maximos.required' => 'Los días máximos son obligatorios.',
        'tipoPrograma.dias_maximos.gte' => 'Los días máximos deben ser mayores o iguales que los días mínimos.',
        'tipoPrograma.horas_minimas_por_dia.required' => 'Las horas mínimas por día son obligatorias.',
        'plantillaDocumento.required' => 'La plantilla .docx es obligatoria.',
        'plantillaDocumento.file' => 'La plantilla debe ser un archivo válido.',
        'plantillaDocumento.mimes' => 'La plantilla debe ser un archivo .docx.',
        'plantillaDocumento.max' => 'La plantilla no debe pesar más de 5 MB.',
    ];

    public function saveTipoPrograma(): void
    {
        $rules = [
            'tipoPrograma.nombre' => ['required', 'string', 'max:255'],
            'tipoPrograma.modalidad_duracion' => ['required', 'in:HORAS,DIAS'],
            'tipoPrograma.horas_minimas' => ['required_if:tipoPrograma.modalidad_duracion,HORAS', 'nullable', 'integer', 'min:0'],
            'tipoPrograma.horas_maximas' => ['required_if:tipoPrograma.modalidad_duracion,HORAS', 'nullable', 'integer', 'min:0', 'gte:tipoPrograma.horas_minimas'],
            'tipoPrograma.dias_minimos' => ['required_if:tipoPrograma.modalidad_duracion,DIAS', 'nullable', 'integer', 'min:1'],
            'tipoPrograma.dias_maximos' => ['required_if:tipoPrograma.modalidad_duracion,DIAS', 'nullable', 'integer', 'min:1', 'gte:tipoPrograma.dias_minimos'],
            'tipoPrograma.horas_minimas_por_dia' => ['required_if:tipoPrograma.modalidad_duracion,DIAS', 'nullable', 'integer', 'min:1'],
            'tipoPrograma.dias_consecutivos' => ['boolean'],
            'plantillaDocumento' => [$this->editingTipoProgramaId ? 'nullable' : 'required', 'file', 'mimes:docx', 'max:5120'],
        ];

        $validated = $this->validate($rules);

        $porDias = $validated['tipoPrograma']['modalidad_duracion'] === 'DIAS';
        $payload = [
            'nombre' => $validated['tipoPrograma']['nombre'],
            'modalidad_duracion' => $validated['tipoPrograma']['modalidad_duracion'],
            'horas_minimas' => $porDias ? null : $validated['tipoPrograma']['horas_minimas'],
            'horas_maximas' => $porDias ? null : $validated['tipoPrograma']['horas_maximas'],
            'dias_minimos' => $porDias ? $validated['tipoPrograma']['dias_minimos'] : null,
            'dias_maximos' => $porDias ? $validated['tipoPrograma']['dias_maximos'] : null,
            'horas_minimas_por_dia' => $porDias ? $validated['tipoPrograma']['horas_minimas_por_dia'] : null,
            'dias_consecutivos' => $porDias && ($validated['tipoPrograma']['dias_consecutivos'] ?? false),
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
            'modalidad_duracion' => $tipo->modalidad_duracion ?? 'HORAS',
            'horas_minimas' => $tipo->horas_minimas,
            'horas_maximas' => $tipo->horas_maximas,
            'dias_minimos' => $tipo->dias_minimos,
            'dias_maximos' => $tipo->dias_maximos,
            'horas_minimas_por_dia' => $tipo->horas_minimas_por_dia,
            'dias_consecutivos' => $tipo->dias_consecutivos,
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
            'modalidad_duracion' => 'HORAS',
            'horas_minimas' => null,
            'horas_maximas' => null,
            'dias_minimos' => null,
            'dias_maximos' => null,
            'horas_minimas_por_dia' => null,
            'dias_consecutivos' => false,
        ];
        $this->plantillaDocumento = null;
    }
}
