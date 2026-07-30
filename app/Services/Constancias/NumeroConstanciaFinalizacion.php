<?php

namespace App\Services\Constancias;

final class NumeroConstanciaFinalizacion
{
    public const TIPO = 'FINALIZACION';
    public const UNIDAD_EMISORA = 'VRA_DVUS';

    public function format(int $correlativo, int $anio): string
    {
        return sprintf('N.º %04d-VRA/DVUS-%d', $correlativo, $anio);
    }
}
