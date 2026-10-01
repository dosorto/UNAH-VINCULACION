<?php

namespace Tests\Unit;

use App\Models\Pasantia;
use App\Models\Personal\Empleado;
use App\Models\Personal\FirmaSelloEmpleado;
use App\Services\Pasantias\PasantiaWorkflowService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class PasantiaFirmaCreadorTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config()->set('database.default', 'sqlite');
        config()->set('database.connections.sqlite.database', ':memory:');
        DB::purge('sqlite');
        Schema::create('tipo_cargo_firma', function (Blueprint $table) {
            $table->id();
            $table->string('nombre');
        });
        Schema::create('cargo_firma', function (Blueprint $table) {
            $table->id();
            $table->string('descripcion');
            $table->integer('tipo_cargo_firma_id');
        });
        Schema::create('pasantias', function (Blueprint $table) {
            $table->id();
            $table->integer('created_by');
            $table->string('nombre_firma_coordinador')->nullable();
            $table->string('firma_coordinador')->nullable();
            $table->softDeletes();
            $table->timestamps();
        });
        Schema::create('firma_proyecto', function (Blueprint $table) {
            $table->id();
            $table->morphs('firmable');
            foreach (['cargo_firma_id', 'flujo_aprobacion_etapa_id', 'empleado_id', 'responsable_usuario_id', 'firma_id', 'sello_id'] as $field) {
                $table->integer($field)->nullable();
            }
            $table->string('tipo_firma');
            $table->string('estado_revision');
            $table->string('hash');
            $table->timestamp('fecha_firma');
            $table->timestamps();
        });
        DB::table('tipo_cargo_firma')->insert(['id' => 1, 'nombre' => 'Coordinador Proyecto']);
        DB::table('cargo_firma')->insert(['id' => 1, 'descripcion' => 'Proyecto', 'tipo_cargo_firma_id' => 1]);
    }

    public function test_initial_signature_keeps_creator_assets_without_consuming_a_flow_stage(): void
    {
        $registro = Pasantia::create(['created_by' => 42]);
        $empleado = new Empleado;
        $empleado->forceFill(['id' => 7, 'user_id' => 42]);
        $empleado->setRelation('firma', (new FirmaSelloEmpleado)->forceFill(['id' => 10, 'ruta_storage' => 'firmas/coordinador.png']));
        $empleado->setRelation('sello', (new FirmaSelloEmpleado)->forceFill(['id' => 11]));
        $method = new \ReflectionMethod(PasantiaWorkflowService::class, 'registrarFirmaDelCreador');
        $service = new PasantiaWorkflowService;

        $method->invoke($service, $registro, $empleado);
        $firma = $registro->firmasDeEtapa()->firstOrFail();
        $this->assertSame('Aprobado', $firma->estado_revision);
        $this->assertNull($firma->flujo_aprobacion_etapa_id);
        $this->assertEquals(10, $firma->firma_id);
        // La firma de quien registra nunca lleva sello, aunque el empleado tenga uno.
        $this->assertNull($firma->sello_id);
        $this->assertSame('firmas/coordinador.png', $registro->fresh()->firma_coordinador);
        $this->assertNotNull($firma->fecha_firma);

        // Reenviar no duplica ni sustituye la firma histórica del emisor.
        $empleado->setRelation('firma', (new FirmaSelloEmpleado)->forceFill(['id' => 20, 'ruta_storage' => 'otra.png']));
        $method->invoke($service, $registro, $empleado);
        $this->assertSame(1, $registro->firmasDeEtapa()->count());
        $this->assertEquals(10, $firma->fresh()->firma_id);
        $this->assertSame('firmas/coordinador.png', $registro->fresh()->firma_coordinador);
    }

    public function test_another_employee_cannot_sign_as_the_creator(): void
    {
        $registro = Pasantia::create(['created_by' => 42]);
        $empleado = (new Empleado)->forceFill(['id' => 8, 'user_id' => 43]);
        $this->expectException(\RuntimeException::class);
        (new \ReflectionMethod(PasantiaWorkflowService::class, 'registrarFirmaDelCreador'))
            ->invoke(new PasantiaWorkflowService, $registro, $empleado);
    }
}
