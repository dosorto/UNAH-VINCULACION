<?php

namespace App\Support\PpsServicioSocial;

use Illuminate\Support\Str;

/**
 * Tratamiento del destinatario de la SOLICITUD DE PRÁCTICA. Cada opción lleva su género
 * para que el saludo concuerde: «Estimada Licenciada:», «Estimado Ingeniero:».
 */
final class TratamientoDestinatario
{
    public const SALUDOS = [
        'Licenciada' => 'Estimada Licenciada',
        'Licenciado' => 'Estimado Licenciado',
        'Ingeniera' => 'Estimada Ingeniera',
        'Ingeniero' => 'Estimado Ingeniero',
        'Doctora' => 'Estimada Doctora',
        'Doctor' => 'Estimado Doctor',
        'Máster' => 'Estimado(a) Máster',
        'Abogada' => 'Estimada Abogada',
        'Abogado' => 'Estimado Abogado',
        'Arquitecta' => 'Estimada Arquitecta',
        'Arquitecto' => 'Estimado Arquitecto',
        'Señora' => 'Estimada señora',
        'Señor' => 'Estimado señor',
    ];

    /** @return list<string> */
    public static function opciones(): array
    {
        return array_keys(self::SALUDOS);
    }

    public static function saludo(?string $tratamiento): string
    {
        return self::SALUDOS[Str::of((string) $tratamiento)->trim()->value()] ?? 'Estimado(a) señor(a)';
    }
}
