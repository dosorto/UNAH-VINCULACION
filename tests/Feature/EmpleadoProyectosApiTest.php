<?php

namespace Tests\Feature;

use App\Models\ApiAccessScope;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Support\IsolatedApiTestCase;

class EmpleadoProyectosApiTest extends IsolatedApiTestCase
{
    public function test_token_ausente(): void
    {
        $this->getJson('/api/empleados/123/proyectos')->assertStatus(401)
            ->assertExactJson(['message' => 'Token de API requerido.']);
    }

    public function test_token_invalido(): void
    {
        $this->secret = 'incorrecto';
        $this->consulta()->assertStatus(401)->assertExactJson(['message' => 'Token de API inválido.']);
    }

    public function test_token_vigente_registra_uso_y_entrega_cuatro_campos(): void
    {
        $id = $this->proyecto($this->empleado());
        $this->consulta()->assertOk()->assertExactJson(['data' => [[
            'nombre_proyecto' => 'Proyecto fixture', 'codigo_proyecto' => 'FIX-'.$id,
            'rol' => 'Coordinador', 'estado' => 'en curso',
        ]]]);
        $this->assertNotNull($this->token->fresh()->ultimo_uso_en);
        $this->assertDatabaseHas('activity_log', ['description' => 'Uso de token API']);
    }

    #[DataProvider('rechazos')]
    public function test_rechazo_no_puede_recuperarse_por_token_legado(string $caso, int $status): void
    {
        config(['services.nexo_api.token' => $this->secret]);
        match ($caso) {
            'revocado' => $this->token->update(['revocado_en' => now()]),
            'expirado' => $this->token->update(['expira_en' => now()->subSecond()]),
            'limite' => $this->token->update(['expira_en' => now()]),
            'sin_alcance' => $this->token->scopes()->detach(),
            'inactivo' => ApiAccessScope::query()->update(['activo' => false]),
            'inexistente' => ApiAccessScope::query()->delete(),
        };
        $this->consulta()->assertStatus($status)->assertExactJson(['message' => $status === 401
            ? 'Token de API inválido.' : 'El token no tiene el alcance requerido.']);
        $this->assertNull($this->token->fresh()->ultimo_uso_en);
    }

    public static function rechazos(): array
    {
        return [['revocado', 401], ['expirado', 401], ['limite', 401],
            ['sin_alcance', 403], ['inactivo', 403], ['inexistente', 403]];
    }

    public function test_encabezado_alternativo_y_prioridad_bearer(): void
    {
        $this->empleado();
        $this->withHeader('X-NEXO-API-TOKEN', $this->secret)->getJson('/api/empleados/EMP-TEST/proyectos')
            ->assertOk()->assertExactJson(['data' => []]);
        $this->withHeader('Authorization', 'Bearer incorrecto')->getJson('/api/empleados/EMP-TEST/proyectos')->assertStatus(401);
    }

    public function test_legado_no_registrado_conserva_compatibilidad(): void
    {
        $this->empleado();
        $this->secret = 'legado_fixture';
        config(['services.nexo_api.token' => $this->secret]);
        $this->consulta()->assertOk();
    }

    public function test_empleado_inexistente_y_sin_proyectos(): void
    {
        $this->consulta()->assertNotFound()->assertExactJson(['message' => 'Empleado no encontrado.']);
        $this->empleado();
        $this->consulta()->assertOk()->assertExactJson(['data' => []]);
    }

    #[DataProvider('estados')]
    public function test_equivalencias_exactas_y_exclusiones(string $original, ?string $normalizado): void
    {
        $this->proyecto($this->empleado(), $original);
        $response = $this->consulta()->assertOk();
        if ($normalizado === null) {
            $response->assertExactJson(['data' => []]);
        } else {
            $response->assertJsonCount(1, 'data')->assertJsonPath('data.0.estado', $normalizado);
        }
    }

    public static function estados(): array
    {
        return [['Finalizado', 'finalizado'], ['Aprobado', 'aprobado'], ['En curso', 'en curso'],
            ['En revision final', 'en curso'], ['En revisión final', 'en curso'],
            ['Informe Final Habilitado', 'en curso'], ['Rechazado', null], ['Cancelado', null],
            ['Borrador', null], ['Autoguardado', null], ['No aprobado', null],
            ['Finalizado parcialmente', null], ['Estado nuevo', null]];
    }

    public function test_multiples_proyectos_deduplicacion_y_participaciones_eliminadas(): void
    {
        $empleado = $this->empleado();
        $primero = $this->proyecto($empleado, 'Finalizado');
        $segundo = $this->proyecto($empleado, 'Aprobado', 'Integrante');
        $this->participacion($empleado, $primero, 'Subcoordinador');
        $this->participacion($empleado, $primero, 'Integrante', true);
        $tercero = $this->proyecto($empleado);
        DB::table('empleado_proyecto')->where('proyecto_id', $tercero)->update(['deleted_at' => now()]);
        $this->consulta()->assertOk()->assertJsonCount(2, 'data')
            ->assertJsonPath('data.0.codigo_proyecto', 'FIX-'.$primero)
            ->assertJsonPath('data.0.rol', 'Subcoordinador')
            ->assertJsonPath('data.1.codigo_proyecto', 'FIX-'.$segundo);
    }

    public function test_prioridad_numero_id_usuario_y_ceros_iniciales(): void
    {
        $user = User::factory()->create(['id' => 200]);
        $porUsuario = $this->empleado('OTRO', 201, $user->id);
        $porId = $this->empleado('POR-ID', 200);
        $porNumero = $this->empleado('200', 202);
        foreach ([$porUsuario, $porId, $porNumero] as $id) { $this->proyecto($id); }
        $this->consulta('200')->assertJsonPath('data.0.codigo_proyecto', 'FIX-3');
        $this->consulta('000200')->assertJsonPath('data.0.codigo_proyecto', 'FIX-2');
        DB::table('empleado')->where('id', $porNumero)->update(['deleted_at' => now()]);
        $this->consulta('200')->assertJsonPath('data.0.codigo_proyecto', 'FIX-2');
        DB::table('empleado')->where('id', $porId)->update(['deleted_at' => now()]);
        $this->consulta('200')->assertJsonPath('data.0.codigo_proyecto', 'FIX-1');
    }

    public function test_estado_actual_explicito_ignora_historial_otros_tipos_y_eliminados(): void
    {
        $proyecto = $this->proyecto($this->empleado(), 'Aprobado');
        $this->estado($proyecto, 'Borrador', ['es_actual' => false]);
        $this->estado($proyecto, 'Borrador', ['estadoable_type' => 'OtroModelo']);
        $this->estado($proyecto, 'Borrador', ['deleted_at' => now()]);
        $this->consulta()->assertJsonPath('data.0.estado', 'aprobado');
        $this->estado($proyecto, 'Finalizado');
        $this->consulta()->assertJsonPath('data.0.estado', 'finalizado');
    }

    public function test_sin_estado_y_proyecto_eliminado_no_se_publican(): void
    {
        $id = $this->proyecto($this->empleado());
        DB::table('estado_proyecto')->update(['es_actual' => false]);
        $this->consulta()->assertExactJson(['data' => []]);
        $this->estado($id, 'Aprobado');
        DB::table('proyecto')->update(['deleted_at' => now()]);
        $this->consulta()->assertExactJson(['data' => []]);
    }

    public function test_validacion_no_trunca_enteros_y_devuelve_422(): void
    {
        $this->consulta(str_repeat('a', 81))->assertStatus(422)->assertExactJson(['message' => 'Identificador inválido.']);
        $this->consulta(' EMP-TEST ')->assertStatus(422);
        $this->consulta(str_repeat('9', 80))->assertNotFound();
    }

    public function test_fallo_ultimo_uso_y_auditoria_no_bloquean_consulta(): void
    {
        $this->empleado();
        Schema::table('api_access_tokens', fn ($t) => $t->dropColumn('ultimo_uso_en'));
        Schema::drop('activity_log');
        $this->consulta()->assertOk()->assertExactJson(['data' => []]);
    }

    public function test_error_interno_es_json_sin_detalles(): void
    {
        Schema::disableForeignKeyConstraints();
        Schema::drop('empleado');
        Schema::enableForeignKeyConstraints();
        $this->consulta()->assertStatus(500)->assertExactJson(['message' => 'No fue posible consultar los proyectos.']);
    }
}
