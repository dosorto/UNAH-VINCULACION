<?php

namespace App\Services\ENF\Constancias;

final class NumeroConstanciaRegistroEnf
{
    public const TIPO = 'ENF_REGISTRO';
    public const UNIDAD_EMISORA = 'VRA_DVUS_ENF';

    public function format(int $correlativo, int $anio): string
    {
        return sprintf('N.º ENF-R-%04d-VRA/DVUS-%d', $correlativo, $anio);
    }
}
