<?php

namespace App\Services\InformeFinal;

use App\Models\InformeFinal\InformeFinalProyecto;
use Illuminate\Validation\ValidationException;

class InformeFinalProyectoValidator
{
    public function validateForCompletion(InformeFinalProyecto $informe): void
    {
        $informe->load(['beneficiarios', 'resultados', 'actividades', 'presupuestoDetalles', 'anexos']);
        $errors = [];
        $required = [
            'nombre_proyecto' => 'El nombre del proyecto es obligatorio.',
            'fecha_inicio' => 'La fecha de inicio es obligatoria.',
            'fecha_finalizacion' => 'La fecha de finalización es obligatoria.',
            'fecha_cierre' => 'La fecha de cierre es obligatoria.',
            'transformacion_lograda' => 'La transformación lograda es obligatoria.',
            'mecanismos_sostenibilidad' => 'Los mecanismos de sostenibilidad son obligatorios.',
        ];
        foreach ($required as $field => $message) {
            if (blank($informe->{$field})) {
                $errors[$field][] = $message;
            }
        }
        if ($informe->fecha_inicio && $informe->fecha_finalizacion && $informe->fecha_finalizacion->lt($informe->fecha_inicio)) {
            $errors['fecha_finalizacion'][] = 'La fecha de finalización no puede ser anterior a la fecha de inicio.';
        }
        if (! $informe->beneficiarios || ! $informe->beneficiarios->totales_consistentes) {
            $errors['beneficiarios'][] = 'Los totales por sexo, edad y etnia deben coincidir.';
        }
        if ($informe->valoracion_muestra > $informe->valoracion_total_beneficiarios) {
            $errors['valoracion_muestra'][] = 'La muestra no puede superar el total de beneficiarios.';
        }
        $respuestas = $informe->valoracion_excelente + $informe->valoracion_muy_buena + $informe->valoracion_regular + $informe->valoracion_mala;
        if ($respuestas !== $informe->valoracion_muestra) {
            $errors['valoracion_respuestas'][] = 'La suma de valoraciones debe coincidir con la muestra.';
        }
        if ($informe->resultados->isEmpty()) {
            $errors['resultados'][] = 'Debe existir al menos un resultado estructurado.';
        }
        if ($informe->actividades->isEmpty()) {
            $errors['actividades'][] = 'Debe existir al menos una actividad estructurada.';
        }
        if ((float) $informe->presupuesto_planificado <= 0) {
            $errors['presupuesto_planificado'][] = 'El presupuesto planificado debe ser mayor que cero.';
        }
        if (! $informe->confirmacion_veracidad) {
            $errors['confirmacion_veracidad'][] = 'Debe confirmar la veracidad de la información.';
        }
        if ($errors) {
            throw ValidationException::withMessages($errors);
        }
    }
}
