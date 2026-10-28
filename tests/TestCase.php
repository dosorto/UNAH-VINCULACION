<?php

namespace Tests;

use App\Http\Middleware\VerificarPermisoDeCompletarPerfil;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    /**
     * En la aplicación, quien tiene el perfil incompleto no puede navegar. Los usuarios de las
     * pruebas casi nunca tienen celular, firma, etc., así que el middleware se desactiva salvo en
     * las pruebas del perfil obligatorio, que ponen esta propiedad en true.
     */
    protected bool $exigePerfilCompleto = false;

    protected function setUp(): void
    {
        parent::setUp();

        if (! $this->exigePerfilCompleto) {
            $this->withoutMiddleware(VerificarPermisoDeCompletarPerfil::class);
        }
    }
}
