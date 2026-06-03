<?php

namespace App\Http\Requests\ENF;

use Illuminate\Foundation\Http\FormRequest;

class StoreEnfInformeFinalRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'enf_accion_id' => ['required', 'exists:enf_acciones,id'],
            'fecha_presentacion' => ['nullable', 'date'],
            'resumen_ejecutivo' => ['nullable', 'string'],
            'resultados_obtenidos' => ['nullable', 'string'],
            'limitaciones' => ['nullable', 'string'],
            'conclusiones' => ['nullable', 'string'],
            'recomendaciones' => ['nullable', 'string'],
            'aprobado_por_empleado_id' => ['nullable', 'exists:empleado,id'],
            'fecha_aprobacion' => ['nullable', 'date'],
            'estado' => ['nullable', 'string', 'max:60'],
        ];
    }
}
