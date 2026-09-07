<?php
namespace Tests\Feature;
use Illuminate\Foundation\Testing\WithoutMiddleware;
use Tests\TestCase;
class EmpleadoProyectosApiTest extends TestCase
{
    public function test_requiere_token_de_api(): void
    {
        config(['services.nexo_api.token' => 'secreto-prueba']);
        $this->getJson('/api/empleados/123/proyectos')->assertStatus(401)->assertJsonPath('message', 'Token de API requerido.');
    }
    public function test_rechaza_token_invalido_sin_exponerlo(): void
    {
        config(['services.nexo_api.token' => 'secreto-prueba']);
        $this->withHeader('Authorization', 'Bearer incorrecto')->getJson('/api/empleados/123/proyectos')->assertStatus(401)->assertJsonMissing(['token' => 'incorrecto']);
    }

    public function test_acepta_token_bearer_configurado(): void
    {
        config(['services.nexo_api.token' => 'secreto-prueba']);
        $this->withHeader('Authorization', 'Bearer secreto-prueba')
            ->getJson('/api/empleados/identificador-inexistente/proyectos')
            ->assertStatus(404)
            ->assertJsonPath('message', 'Empleado no encontrado.');
    }

    public function test_acepta_token_en_cabecera_nexo(): void
    {
        config(['services.nexo_api.token' => 'secreto-prueba']);
        $this->withHeader('X-NEXO-API-TOKEN', 'secreto-prueba')
            ->getJson('/api/empleados/identificador-inexistente/proyectos')
            ->assertStatus(404);
    }
}
