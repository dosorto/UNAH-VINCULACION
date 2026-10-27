<?php

namespace Tests\Feature;

use App\Models\Proyecto\FlujoAprobacion;
use App\Models\Proyecto\FlujoAprobacionEtapa;
use Database\Seeders\Proyecto\FormDvus001FlujoSeeder;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class FormDvus001FlujoSeederTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:']);
        DB::purge('sqlite');
        foreach (['roles', 'tipo_cargo_firma', 'cargo_firma', 'vinculacion_tipos_accion', 'users', 'flujos_aprobacion', 'flujos_aprobacion_etapas'] as $name) {
            Schema::create($name, function (Blueprint $t) use ($name): void {
                $t->id();
                $t->timestamps();
                if ($name === 'roles') {
                    $t->string('name');
                    $t->string('guard_name');
                }
                if ($name === 'tipo_cargo_firma') {
                    $t->string('nombre');
                }
                if ($name === 'cargo_firma') {
                    $t->string('descripcion');
                    $t->integer('tipo_cargo_firma_id');
                }
                if ($name === 'vinculacion_tipos_accion') {
                    $t->string('codigo');
                }
                if ($name === 'users') {
                    $t->softDeletes();
                }
                if ($name === 'flujos_aprobacion') {
                    foreach (['codigo', 'nombre', 'proceso', 'codigo_formulario', 'descripcion'] as $c) {
                        $t->string($c)->nullable();
                    }
                    $t->integer('tipo_accion_id')->nullable();
                    $t->boolean('activo')->default(true);
                }
                if ($name === 'flujos_aprobacion_etapas') {
                    foreach (['flujo_aprobacion_id', 'orden', 'cargo_firma_id', 'rol_revisor_id', 'usuario_responsable_id'] as $c) {
                        $t->integer($c)->nullable();
                    }
                    foreach (['codigo', 'nombre', 'tipo_etapa'] as $c) {
                        $t->string($c);
                    }
                    foreach (['requiere_asignacion', 'emisor_define_destinatario', 'aplica_inscripcion', 'aplica_informe_intermedio', 'aplica_cierre_proyecto'] as $c) {
                        $t->boolean($c)->default(false);
                    }
                    $t->boolean('activo')->default(true);
                    $t->boolean('configuracion_vigente')->default(true);
                }
            });
        }
        DB::table('vinculacion_tipos_accion')->insert(['codigo' => 'DESARROLLO_LOCAL_REGIONAL']);
        foreach (['Jefe Departamento', 'Enlace Vinculacion', 'Director centro', 'Revisor Vinculacion', 'Director Vinculacion'] as $name) {
            DB::table('roles')->insert(['name' => $name, 'guard_name' => 'web']);
            $id = DB::table('tipo_cargo_firma')->insertGetId(['nombre' => $name]);
            DB::table('cargo_firma')->insert(['descripcion' => 'Proyecto', 'tipo_cargo_firma_id' => $id]);
        }
    }

    public function test_crea_el_recorrido_real_sin_usuarios_y_conserva_configuracion_al_repetir(): void
    {
        (new FormDvus001FlujoSeeder)->run();
        $flow = FlujoAprobacion::firstOrFail();
        $this->assertSame(['Jefe Departamento', 'Enlace Vinculacion', 'Director Centro', 'Revisor Vinculacion', 'Director Vinculacion'], $flow->etapas->pluck('nombre')->all());
        $this->assertSame(0, DB::table('users')->count());
        $this->assertSame(5, $flow->etapas()->whereNull('usuario_responsable_id')->count());
        $this->assertSame(5, FlujoAprobacionEtapa::pendientesDeResponsable()->count());
        $this->assertSame(2, $flow->etapas()->where('aplica_informe_intermedio', true)->count());
        $user = DB::table('users')->insertGetId([]);
        $flow->etapas->first()->update(['nombre' => 'Personalizado', 'usuario_responsable_id' => $user, 'orden' => 9]);
        $before = DB::table('flujos_aprobacion_etapas')->orderBy('id')->get()->toJson();
        (new FormDvus001FlujoSeeder)->run();
        $this->assertSame($before, DB::table('flujos_aprobacion_etapas')->orderBy('id')->get()->toJson());
        $this->assertSame(1, FlujoAprobacion::count());
        $this->assertSame(4, FlujoAprobacionEtapa::pendientesDeResponsable()->count());
    }

    public function test_seleccion_por_emisor_no_exige_responsable_fijo(): void
    {
        (new FormDvus001FlujoSeeder)->run();
        $stage = FlujoAprobacionEtapa::firstOrFail();
        $this->assertTrue($stage->responsableFijoPendiente());
        $stage->update(['emisor_define_destinatario' => true]);
        $this->assertFalse($stage->fresh()->responsableFijoPendiente());
        $this->assertSame(4, FlujoAprobacionEtapa::pendientesDeResponsable()->count());
        FlujoAprobacionEtapa::query()->update(['configuracion_vigente' => false]);
        $this->assertSame(0, FlujoAprobacionEtapa::pendientesDeResponsable()->count());
    }

    public function test_aviso_solo_para_quien_configura_y_desaparece_al_completar(): void
    {
        (new FormDvus001FlujoSeeder)->run();
        \Illuminate\Support\Facades\Gate::define('configuracion.flujos', fn (?\App\Models\User $user) => false);
        $this->assertStringNotContainsString('Configure los responsables', view('components.flujos-responsables-pendientes')->render());
        \Illuminate\Support\Facades\Gate::define('configuracion.flujos', fn (?\App\Models\User $user) => true);
        $this->assertStringContainsString('Jefe Departamento', view('components.flujos-responsables-pendientes')->render());
        $user = DB::table('users')->insertGetId([]);
        FlujoAprobacionEtapa::query()->update(['usuario_responsable_id' => $user]);
        $this->assertStringNotContainsString('Configure los responsables', view('components.flujos-responsables-pendientes')->render());
    }

    public function test_dependencias_incompletas_no_dejan_un_flujo_parcial(): void
    {
        DB::table('roles')->where('name', 'Director Vinculacion')->delete();
        try {
            (new FormDvus001FlujoSeeder)->run();
            $this->fail('Debe rechazar catálogos incompletos.');
        } catch (\RuntimeException $e) {
            $this->assertStringContainsString('Director Vinculacion', $e->getMessage());
        }
        $this->assertSame(0, FlujoAprobacion::count());
        $this->assertSame(0, FlujoAprobacionEtapa::count());
    }
}
