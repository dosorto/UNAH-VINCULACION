<?php

namespace App\Exceptions\Integraciones;

use RuntimeException;

class IntegracionApiException extends RuntimeException
{
    public function __construct(
        public readonly string $tipo,
        string $mensaje
    ) {
        parent::__construct($mensaje);
    }
}
