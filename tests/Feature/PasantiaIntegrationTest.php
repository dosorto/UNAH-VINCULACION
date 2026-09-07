<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\WithoutMiddleware;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class PasantiaIntegrationTest extends TestCase
{
    use WithoutMiddleware;

    public function test_rutas_de_pasantias_estan_registradas_sin_duplicados(): void
    {
        foreach (['crearPasantia', 'pasantias.edit', 'pasantias.show'] as $nombre) {
            $this->assertNotNull(Route::getRoutes()->getByName($nombre));
        }

        $this->assertSame('App\\Livewire\\Proyectos\\Vinculacion\\CreatePasantia', Route::getRoutes()->getByName('crearPasantia')->getActionName());
    }
}
