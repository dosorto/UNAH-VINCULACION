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
            'inscritos_hombres' => ['nullable', 'integer', 'min:0'],
            'inscritos_mujeres' => ['nullable', 'integer', 'min:0'],
            'no_presentaron_hombres' => ['nullable', 'integer', 'min:0'],
            'no_presentaron_mujeres' => ['nullable', 'integer', 'min:0'],
            'abandonaron_hombres' => ['nullable', 'integer', 'min:0'],
            'abandonaron_mujeres' => ['nullable', 'integer', 'min:0'],
            'reprobaron_hombres' => ['nullable', 'integer', 'min:0'],
            'reprobaron_mujeres' => ['nullable', 'integer', 'min:0'],
            'aprobaron_hombres' => ['nullable', 'integer', 'min:0'],
            'aprobaron_mujeres' => ['nullable', 'integer', 'min:0'],
            'graduados_unah_hombres' => ['nullable', 'integer', 'min:0'],
            'graduados_unah_mujeres' => ['nullable', 'integer', 'min:0'],
            'contenido_curricular_cambios' => ['nullable', 'string'],
            'cronograma_cambios' => ['nullable', 'string'],
            'modalidad_acreditacion' => ['nullable', 'string', 'max:80'],
            'seguimiento_sistematizacion' => ['nullable', 'string'],
            'dificultades' => ['nullable', 'string'],
            'lecciones_aprendidas' => ['nullable', 'string'],
            'buenas_practicas' => ['nullable', 'string'],
            'transformacion_lograda' => ['nullable', 'string'],
            'desafios' => ['nullable', 'string'],
            'respuesta_reforma_universitaria' => ['nullable', 'string'],
            'valoracion_total_beneficiarios' => ['nullable', 'integer', 'min:0'],
            'valoracion_muestra' => ['nullable', 'integer', 'min:0'],
            'valoracion_excelente' => ['nullable', 'integer', 'min:0'],
            'valoracion_muy_buena' => ['nullable', 'integer', 'min:0'],
            'valoracion_regular' => ['nullable', 'integer', 'min:0'],
            'valoracion_mala' => ['nullable', 'integer', 'min:0'],
            'observaciones_finales' => ['nullable', 'string'],
            'confirmacion_veracidad' => ['nullable', 'boolean'],
            'limitaciones' => ['nullable', 'string'],
            'conclusiones' => ['nullable', 'string'],
            'recomendaciones' => ['nullable', 'string'],
            'aprobado_por_empleado_id' => ['nullable', 'exists:empleado,id'],
            'fecha_aprobacion' => ['nullable', 'date'],
            'estado' => ['nullable', 'string', 'max:60'],
        ];
    }
}
