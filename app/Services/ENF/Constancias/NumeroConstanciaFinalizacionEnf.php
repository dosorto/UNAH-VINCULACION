<?php

namespace App\Services\ENF\Constancias;

final class NumeroConstanciaFinalizacionEnf
{
    public const TIPO = 'ENF_FINALIZACION';
    public const UNIDAD_EMISORA = 'VRA_DVUS_ENF';

    public function format(int $correlativo, int $anio): string
    {
        return sprintf('N.º ENF-F-%04d-VRA/DVUS-%d', $correlativo, $anio);
    }
}
