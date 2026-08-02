<?php

namespace App\Services\Constancias;

final class NumeroConstanciaRegistro
{
    public const TIPO = 'REGISTRO';
    public const UNIDAD_EMISORA = 'VRA_DVUS';

    public function format(int $correlativo, int $anio): string
    {
        return sprintf('N.º R-%04d-VRA/DVUS-%d', $correlativo, $anio);
    }
}
