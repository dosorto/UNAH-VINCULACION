<?php

namespace App\Http\Requests\ENF;

use Illuminate\Foundation\Http\FormRequest;

class StoreEnfSistematizacionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'enf_accion_id' => ['required', 'exists:enf_acciones,id'],
            'enf_informe_final_id' => ['nullable', 'exists:enf_informes_finales,id'],
            'antecedentes' => ['nullable', 'string'],
            'descripcion_experiencia' => ['nullable', 'string'],
            'metodologia_sistematizacion' => ['nullable', 'string'],
            'lecciones_aprendidas' => ['nullable', 'string'],
            'buenas_practicas' => ['nullable', 'string'],
            'recomendaciones' => ['nullable', 'string'],
            'estado' => ['nullable', 'string', 'max:60'],
        ];
    }
}
