<?php

namespace App\Http\Requests\ENF;

use Illuminate\Foundation\Http\FormRequest;

class StoreEnfCronogramaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'enf_accion_id' => ['required', 'exists:enf_acciones,id'],
            'actividad' => ['required', 'string', 'max:250'],
            'descripcion' => ['nullable', 'string'],
            'fecha_inicio' => ['nullable', 'date'],
            'fecha_finalizacion' => ['nullable', 'date', 'after_or_equal:fecha_inicio'],
            'responsable_empleado_id' => ['nullable', 'exists:empleado,id'],
            'porcentaje_avance' => ['nullable', 'integer', 'min:0', 'max:100'],
            'estado' => ['nullable', 'string', 'max:60'],
        ];
    }
}
