<?php

namespace App\Http\Requests\ENF;

use Illuminate\Foundation\Http\FormRequest;

class StoreEnfPresupuestoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'enf_accion_id' => ['required', 'exists:enf_acciones,id'],
            'tipo' => ['nullable', 'string', 'max:80'],
            'fuente_financiamiento' => ['nullable', 'string', 'max:180'],
            'monto_solicitado' => ['nullable', 'numeric', 'min:0'],
            'monto_aprobado' => ['nullable', 'numeric', 'min:0'],
            'monto_ejecutado' => ['nullable', 'numeric', 'min:0'],
            'observaciones' => ['nullable', 'string'],
        ];
    }
}
