<?php

namespace Tests\Support;

use App\Models\ApiAccessScope;
use App\Models\ApiAccessToken;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

abstract class IsolatedApiTestCase extends TestCase
{
    private ?string $apiDatabase = null;
    protected string $secret = 'nexo_fixture_token_not_a_real_secret';
    protected ApiAccessToken $token;

    protected function setUp(): void
    {
        parent::setUp();
        if (getenv('NEXO_API_ISOLATED_TESTS') !== '1') {
            $this->markTestSkipped('Requiere NEXO_API_ISOLATED_TESTS=1; crea y elimina una base MySQL exclusiva por prueba.');
        }
        config(['cache.default' => 'array', 'session.driver' => 'array', 'logging.default' => 'null',
            'services.nexo_api.token' => null, 'activitylog.database_connection' => null]);
        $connection = config('database.connections.mysql');
        $connection['url'] = null;
        $connection['database'] = null;
        config(['database.connections.api_test_admin' => $connection]);
        $name = 'nexo_api_test_'.bin2hex(random_bytes(8));
        DB::connection('api_test_admin')->statement("CREATE DATABASE `$name`");
        $this->apiDatabase = $name;
        $connection['database'] = $name;
        config(['database.connections.api_test' => $connection, 'database.default' => 'api_test']);
        DB::setDefaultConnection('api_test');

        // Migraciones reales de identidad, permisos, auditoría y tokens.
        foreach ([
            '0001_01_01_000000_create_users_table.php',
            '2024_09_28_213707_create_permission_tables.php',
            '2024_10_12_151511_create_activity_log_table.php',
            '2024_10_12_151512_add_event_column_to_activity_log_table.php',
            '2024_10_12_151513_add_batch_uuid_column_to_activity_log_table.php',
            '2026_09_07_000004_create_api_access_tokens_tables.php',
        ] as $migration) {
            $class = \Illuminate\Support\Str::studly(substr($migration, 18, -4));
            $instance = class_exists($class) ? new $class : require database_path('migrations/'.$migration);
            (is_object($instance) ? $instance : new $class)->up();
        }

        // Esquema mínimo de lectura: no ejecuta flujos, correos ni seeders de módulos.
        Schema::create('empleado', function (Blueprint $t) {
            $t->id(); $t->string('numero_empleado')->nullable()->unique();
            $t->foreignId('user_id')->constrained('users'); $t->softDeletes(); $t->timestamps();
        });
        Schema::create('proyecto', function (Blueprint $t) {
            $t->id(); $t->string('nombre_proyecto')->nullable();
            $t->string('codigo_proyecto')->nullable()->unique(); $t->softDeletes(); $t->timestamps();
        });
        Schema::create('empleado_proyecto', function (Blueprint $t) {
            $t->id(); $t->foreignId('empleado_id')->constrained('empleado');
            $t->foreignId('proyecto_id')->constrained('proyecto');
            $t->enum('rol', ['Coordinador', 'Subcoordinador', 'Integrante']);
            $t->softDeletes(); $t->timestamps();
        });
        Schema::create('tipo_estado', function (Blueprint $t) {
            $t->id(); $t->string('nombre'); $t->softDeletes(); $t->timestamps();
        });
        Schema::create('estado_proyecto', function (Blueprint $t) {
            $t->id(); $t->foreignId('tipo_estado_id')->constrained('tipo_estado');
            $t->morphs('estadoable'); $t->boolean('es_actual')->default(true);
            $t->softDeletes(); $t->timestamps();
        });
        $this->token = ApiAccessToken::create([
            'nombre' => 'Fixture', 'prefijo' => 'nexo_fixture',
            'token_hash' => ApiAccessToken::hashToken($this->secret),
            'created_by' => User::factory()->create()->id,
        ]);
        $this->token->scopes()->attach(ApiAccessScope::where('codigo', 'empleados.proyectos')->firstOrFail());
    }

    protected function tearDown(): void
    {
        try {
            if ($this->apiDatabase !== null) {
                DB::purge('api_test');
                DB::connection('api_test_admin')->statement("DROP DATABASE `{$this->apiDatabase}`");
                DB::purge('api_test_admin');
            }
        } finally {
            parent::tearDown();
        }
    }

    protected function empleado(string $numero = 'EMP-TEST', ?int $id = null, ?int $userId = null): int
    {
        return DB::table('empleado')->insertGetId(array_filter([
            'id' => $id, 'numero_empleado' => $numero,
            'user_id' => $userId ?? User::factory()->create()->id,
        ], fn ($value) => $value !== null));
    }

    protected function proyecto(int $empleado, string $estado = 'En curso', string $rol = 'Coordinador'): int
    {
        $id = DB::table('proyecto')->insertGetId(['nombre_proyecto' => 'Proyecto fixture']);
        DB::table('proyecto')->where('id', $id)->update(['codigo_proyecto' => 'FIX-'.$id]);
        $this->participacion($empleado, $id, $rol);
        $this->estado($id, $estado);
        return $id;
    }

    protected function participacion(int $empleado, int $proyecto, string $rol, bool $eliminada = false): void
    {
        DB::table('empleado_proyecto')->insert(['empleado_id' => $empleado, 'proyecto_id' => $proyecto,
            'rol' => $rol, 'deleted_at' => $eliminada ? now() : null]);
    }

    protected function estado(int $proyecto, string $nombre, array $attributes = []): void
    {
        $tipo = DB::table('tipo_estado')->insertGetId(['nombre' => $nombre]);
        DB::table('estado_proyecto')->insert(array_merge([
            'tipo_estado_id' => $tipo, 'estadoable_id' => $proyecto,
            'estadoable_type' => \App\Models\Proyecto\Proyecto::class, 'es_actual' => true,
        ], $attributes));
    }

    protected function consulta(string $identificador = 'EMP-TEST')
    {
        return $this->withHeader('Authorization', 'Bearer '.$this->secret)
            ->getJson('/api/empleados/'.rawurlencode($identificador).'/proyectos');
    }
}
