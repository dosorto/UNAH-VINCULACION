<?php

namespace App\Services\Integraciones;

use App\Exceptions\Integraciones\IntegracionApiException;
use RuntimeException;

/**
 * Alias de compatibilidad para consumidores previos del servicio.
 */
class IntegracionApiService extends EstudianteApiService
{
    public const ESTUDIANTE_FIELDS = self::CAMPOS_ESTUDIANTE;

    public function obtenerActivaPara(string $tipoPerfil): ?\App\Models\IntegracionApi
    {
        return $tipoPerfil === \App\Models\IntegracionApi::PERFIL_ESTUDIANTE
            ? $this->obtenerConfiguracionActiva()
            : null;
    }

    public function buscarEstudiantePorCuenta(string $numeroCuenta): array
    {
        try {
            $data = $this->buscarPorNumeroCuenta($numeroCuenta);
        } catch (IntegracionApiException $exception) {
            if ($exception->tipo === 'NO_CONFIGURADA') {
                throw new RuntimeException('La integración de estudiantes no está configurada.');
            }

            throw $exception;
        }
        $data += [
            'nombres' => collect([
                $data['primer_nombre'] ?? null,
                $data['segundo_nombre'] ?? null,
            ])->filter()->implode(' ') ?: null,
            'apellidos' => collect([
                $data['primer_apellido'] ?? null,
                $data['segundo_apellido'] ?? null,
            ])->filter()->implode(' ') ?: null,
            'carrera' => $data['carrera_nombre'] ?? null,
        ];
        $data['correo'] = $data['correo_institucional'] ?? $data['correo'] ?? null;

        return [
            'ok' => $data['encontrado'],
            'datos' => $data,
            'mensaje' => $data['encontrado']
                ? 'Estudiante encontrado.'
                : 'No se encontró el estudiante.',
        ];
    }
}
