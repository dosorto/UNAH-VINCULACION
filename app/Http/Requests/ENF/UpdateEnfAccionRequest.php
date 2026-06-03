<?php

namespace App\Http\Requests\ENF;

class UpdateEnfAccionRequest extends StoreEnfAccionRequest
{
    public function rules(): array
    {
        $rules = parent::rules();
        $rules['nombre_accion'] = ['sometimes', 'required', 'string', 'max:250'];

        return $rules;
    }
}
