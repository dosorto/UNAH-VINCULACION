<?php

namespace Tests\Feature;

use App\Mail\EnfRevisionAsignada;
use App\Models\ENF\EnfAccion;
use App\Models\ENF\EnfInformeFinal;
use App\Models\ENF\EnfInformeIntermedio;
use App\Models\Estado\TipoEstado;
use App\Models\Personal\Empleado;
use App\Models\Proyecto\CargoFirma;
use App\Models\Proyecto\FlujoAprobacion;
use App\Models\Proyecto\FlujoAprobacionEtapa;
use App\Models\Proyecto\TipoCargoFirma;
use App\Models\User;
use App\Services\ENF\EnfWorkflowService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class EnfWorkflowResumptionTest extends TestCase
{
    use DatabaseTransactions;

    public function test_inscripcion_reanuda_desde_la_segunda_etapa_y_regresa_al_revisor_que_subsano(): void
    {
        Mail::fake();
        [$accion, $usuario, $etapas] = $this->contexto();
        $service = app(EnfWorkflowService::class);

        $service->enviarInscripcion($accion, $usuario);
        $primera = $accion->fresh()->revisiones()->where('revision_ciclo', 1)->orderBy('orden')->firstOrFail();
        $service->aprobarRevision($primera, $usuario);
        $segunda = $accion->fresh()->revisiones()->where('revision_ciclo', 1)->orderByDesc('orden')->firstOrFail();
        $service->subsanarRevision($segunda, 'Corrija la segunda etapa.', $usuario);

        Mail::fake();
        $service->enviarInscripcion($accion->fresh(), $usuario);

        $nuevas = $accion->fresh()->revisiones()->where('revision_ciclo', 2)->get();
        $this->assertCount(1, $nuevas);
        $this->assertSame($etapas[1]->id, $nuevas->first()->flujo_aprobacion_etapa_id);
        $this->assertSame($usuario->id, $nuevas->first()->asignado_usuario_id);
        $this->assertSame($usuario->id, $nuevas->first()->responsable_usuario_id);
        $this->assertSame('EN_REVISION', $accion->fresh()->estado_flujo);
        Mail::assertQueued(EnfRevisionAsignada::class, 1);
    }

    public function test_informe_intermedio_reanuda_desde_la_segunda_etapa(): void
    {
        Mail::fake();
        Storage::fake('local');
        [$accion, $usuario, $etapas] = $this->contexto(EnfAccion::PROCESO_INFORME_INTERMEDIO);
        $accion->update(['estado_flujo' => 'APROBADO']);
        $contenido = "%PDF-1.4\nInforme intermedio de prueba";
        $path = 'enf/informes-intermedios/'.$accion->id.'/prueba.pdf';
        Storage::disk('local')->put($path, $contenido);
        $informe = EnfInformeIntermedio::create([
            'enf_accion_id' => $accion->id,
            'archivo_pdf' => $path,
            'nombre_original' => 'prueba.pdf',
            'mime_type' => 'application/pdf',
            'tamano_bytes' => strlen($contenido),
            'hash_sha256' => hash('sha256', $contenido),
            'estado' => EnfInformeIntermedio::ESTADO_BORRADOR,
            'revision_ciclo' => 0,
            'subido_por_usuario_id' => $usuario->id,
        ]);
        $service = app(EnfWorkflowService::class);

        $service->enviarInformeIntermedio($informe, $usuario);
        $primera = $accion->revisiones()->where('proceso', EnfAccion::PROCESO_INFORME_INTERMEDIO)
            ->where('revision_ciclo', 1)->orderBy('orden')->firstOrFail();
        $service->aprobarRevision($primera, $usuario);
        $segunda = $accion->revisiones()->where('proceso', EnfAccion::PROCESO_INFORME_INTERMEDIO)
            ->where('revision_ciclo', 1)->orderByDesc('orden')->firstOrFail();
        $service->subsanarRevision($segunda, 'Corrija el informe intermedio.', $usuario);

        Mail::fake();
        $service->enviarInformeIntermedio($informe->fresh('accion'), $usuario);

        $nuevas = $accion->revisiones()->where('proceso', EnfAccion::PROCESO_INFORME_INTERMEDIO)
            ->where('revision_ciclo', 2)->get();
        $this->assertCount(1, $nuevas);
        $this->assertSame($etapas[1]->id, $nuevas->first()->flujo_aprobacion_etapa_id);
        $this->assertSame($usuario->id, $nuevas->first()->asignado_usuario_id);
        $this->assertSame(EnfInformeIntermedio::ESTADO_EN_REVISION, $informe->fresh()->estado);
        Mail::assertQueued(EnfRevisionAsignada::class, 1);
    }

    public function test_informe_final_reanuda_desde_la_segunda_etapa(): void
    {
        Mail::fake();
        Storage::fake('public');
        [$accion, $usuario, $etapas] = $this->contexto(EnfAccion::PROCESO_INFORME_FINAL);
        $accion->update(['estado_flujo' => 'APROBADO']);
        $informe = EnfInformeFinal::create([
            'enf_accion_id' => $accion->id,
            'estado' => EnfInformeFinal::ESTADO_COMPLETO,
            'revision_ciclo' => 0,
        ]);
        $pdf = \Mockery::mock();
        $pdf->shouldReceive('setPaper')->twice()->with('letter', 'portrait')->andReturnSelf();
        $pdf->shouldReceive('output')->twice()->andReturn("%PDF-1.4\nInforme final de prueba");
        Pdf::shouldReceive('loadView')->twice()->andReturn($pdf);
        $service = app(EnfWorkflowService::class);

        $service->enviarInformeFinal($informe, $usuario);
        $primera = $accion->revisiones()->where('proceso', EnfAccion::PROCESO_INFORME_FINAL)
            ->where('revision_ciclo', 1)->orderBy('orden')->firstOrFail();
        $service->aprobarRevision($primera, $usuario);
        $segunda = $accion->revisiones()->where('proceso', EnfAccion::PROCESO_INFORME_FINAL)
            ->where('revision_ciclo', 1)->orderByDesc('orden')->firstOrFail();
        $service->subsanarRevision($segunda, 'Corrija el informe final.', $usuario);

        Mail::fake();
        $service->enviarInformeFinal($informe->fresh('accion'), $usuario);

        $nuevas = $accion->revisiones()->where('proceso', EnfAccion::PROCESO_INFORME_FINAL)
            ->where('revision_ciclo', 2)->get();
        $this->assertCount(1, $nuevas);
        $this->assertSame($etapas[1]->id, $nuevas->first()->flujo_aprobacion_etapa_id);
        $this->assertSame($usuario->id, $nuevas->first()->asignado_usuario_id);
        $this->assertSame(EnfInformeFinal::ESTADO_EN_REVISION, $informe->fresh()->estado);
        Mail::assertQueued(EnfRevisionAsignada::class, 1);
    }

    private function contexto(string $proceso = EnfAccion::PROCESO_INSCRIPCION): array
    {
        $role = Role::findOrCreate('revisor_enf_'.uniqid(), 'web');
        $usuario = User::factory()->create(['active_role_id' => $role->id]);
        $usuario->assignRole($role);
        Empleado::create([
            'nombre_completo' => 'Revisor ENF '.uniqid(),
            'numero_empleado' => (string) random_int(100000, 999999),
            'celular' => '99999999',
            'sexo' => 'Masculino',
            'user_id' => $usuario->id,
            'tipo_empleado' => 'docente',
        ]);

        $flujo = FlujoAprobacion::create([
            'codigo' => 'ENF_TEST_'.uniqid(),
            'nombre' => 'Flujo ENF de prueba',
            'proceso' => 'PROYECTO',
            'codigo_formulario' => 'FORM-DVUS-018',
            'activo' => true,
        ]);

        $etapas = collect();
        foreach ([1, 2] as $orden) {
            $estado = TipoEstado::firstOrCreate(['nombre' => 'Estado ENF '.$orden.'_'.uniqid()]);
            $tipoCargo = TipoCargoFirma::create(['nombre' => 'Cargo ENF '.$orden.'_'.uniqid()]);
            $cargo = CargoFirma::create([
                'descripcion' => 'Proyecto',
                'tipo_cargo_firma_id' => $tipoCargo->id,
                'tipo_estado_id' => $estado->id,
            ]);
            $etapas->push(FlujoAprobacionEtapa::create([
                'flujo_aprobacion_id' => $flujo->id,
                'orden' => $orden,
                'codigo' => 'ENF_ETAPA_'.$orden.'_'.uniqid(),
                'nombre' => 'Etapa ENF '.$orden,
                'tipo_etapa' => 'REVISION',
                'cargo_firma_id' => $cargo->id,
                'rol_revisor_id' => $role->id,
                'usuario_responsable_id' => $usuario->id,
                'aplica_inscripcion' => $proceso === EnfAccion::PROCESO_INSCRIPCION,
                'aplica_informe_intermedio' => $proceso === EnfAccion::PROCESO_INFORME_INTERMEDIO,
                'aplica_cierre_proyecto' => $proceso === EnfAccion::PROCESO_INFORME_FINAL,
                'activo' => true,
            ]));
        }

        $accion = EnfAccion::create([
            'codigo_formulario' => 'FORM-DVUS-018',
            'nombre_accion' => 'Acción ENF de prueba',
            'estado_flujo' => 'BORRADOR',
            'revision_ciclo' => 0,
            'creado_por_usuario_id' => $usuario->id,
        ]);

        return [$accion, $usuario, $etapas];
    }
}
