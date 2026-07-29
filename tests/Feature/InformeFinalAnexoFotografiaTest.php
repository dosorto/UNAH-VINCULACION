<?php

namespace Tests\Feature;

use App\Models\InformeFinal\InformeFinalAnexo;
use App\Models\Personal\Empleado;
use App\Models\Proyecto\Proyecto;
use App\Models\User;
use App\Services\InformeFinal\InformeFinalProyectoWorkflowService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class InformeFinalAnexoFotografiaTest extends TestCase
{
    use DatabaseTransactions;

    private function crearAnexoFoto(): array
    {
        Storage::fake('public');
        [$user, $project] = $this->scenario();
        $informe = $project->informeFinalInf001()->firstOrFail();
        $archivo = UploadedFile::fake()->image('test-foto.png', 800, 600);
        $ruta = $archivo->store('informes-finales/'.$informe->id.'/fotografias', 'public');
        $anexo = $informe->anexos()->create([
            'tipo' => 'fotografias',
            'categoria' => 'fotografia',
            'archivo' => $ruta,
            'nombre_archivo' => 'test-foto.png',
            'tamano_bytes' => $archivo->getSize(),
            'fecha' => now()->toDateString(),
            'orden' => 1,
            'origen' => 'INFORME',
        ]);
        return [$user, $anexo, $informe];
    }

    public function test_usuario_autenticado_puede_ver_fotografia(): void
    {
        [$user, $anexo] = $this->crearAnexoFoto();
        $this->actingAs($user)
            ->get(route('informes-finales.anexos.mostrar', $anexo))
            ->assertOk()
            ->assertHeader('Content-Type', 'image/png');
    }

    public function test_vista_fotografia_usa_content_disposition_inline(): void
    {
        [$user, $anexo] = $this->crearAnexoFoto();
        $this->actingAs($user)
            ->get(route('informes-finales.anexos.mostrar', $anexo))
            ->assertOk()
            ->assertHeader('Content-Disposition', 'inline; filename="test-foto.png"');
    }

    public function test_usuario_autenticado_puede_descargar_fotografia(): void
    {
        [$user, $anexo] = $this->crearAnexoFoto();
        $this->actingAs($user)
            ->get(route('informes-finales.anexos.descargar', $anexo))
            ->assertOk();
    }

    public function test_descarga_usa_content_disposition_attachment(): void
    {
        [$user, $anexo] = $this->crearAnexoFoto();
        $this->actingAs($user)
            ->get(route('informes-finales.anexos.descargar', $anexo))
            ->assertOk()
            ->assertHeader('Content-Disposition', 'attachment; filename="test-foto.png"');
    }

    public function test_descarga_tiene_nombre_archivo_valido(): void
    {
        [$user, $anexo] = $this->crearAnexoFoto();
        $response = $this->actingAs($user)
            ->get(route('informes-finales.anexos.descargar', $anexo));
        $disposition = $response->headers->get('Content-Disposition');
        $this->assertStringContainsString('test-foto.png', $disposition);
    }

    public function test_usuario_no_autorizado_recibe_403(): void
    {
        [$user, $anexo] = $this->crearAnexoFoto();
        $otro = User::factory()->create();
        $this->actingAs($otro)
            ->get(route('informes-finales.anexos.mostrar', $anexo))
            ->assertForbidden();
        $this->actingAs($otro)
            ->get(route('informes-finales.anexos.descargar', $anexo))
            ->assertForbidden();
    }

    public function test_usuario_no_autenticado_redirigido_al_login(): void
    {
        Storage::fake('public');
        $anexo = InformeFinalAnexo::factory()->make(['id' => 99999]);
        $this->get(route('informes-finales.anexos.mostrar', $anexo))
            ->assertRedirect(route('login'));
        $this->get(route('informes-finales.anexos.descargar', $anexo))
            ->assertRedirect(route('login'));
    }

    public function test_anexo_inexistente_devuelve_404(): void
    {
        [$user] = $this->scenario();
        $this->actingAs($user)
            ->get('/informes-finales/anexos/999999')
            ->assertNotFound();
        $this->actingAs($user)
            ->get('/informes-finales/anexos/999999/descargar')
            ->assertNotFound();
    }

    public function test_archivo_inexistente_devuelve_404(): void
    {
        Storage::fake('public');
        [$user, $project] = $this->scenario();
        $informe = $project->informeFinalInf001()->firstOrFail();
        $anexo = $informe->anexos()->create([
            'tipo' => 'fotografias',
            'categoria' => 'fotografia',
            'archivo' => 'informes-finales/'.$informe->id.'/fotografias/no-existe.png',
            'nombre_archivo' => 'no-existe.png',
            'origen' => 'INFORME',
        ]);
        $this->actingAs($user)
            ->get(route('informes-finales.anexos.mostrar', $anexo))
            ->assertNotFound();
        $this->actingAs($user)
            ->get(route('informes-finales.anexos.descargar', $anexo))
            ->assertNotFound();
    }

    public function test_ruta_manipulada_devuelve_404(): void
    {
        Storage::fake('public');
        [$user, $project] = $this->scenario();
        $informe = $project->informeFinalInf001()->firstOrFail();
        $anexo = $informe->anexos()->create([
            'tipo' => 'fotografias',
            'categoria' => 'fotografia',
            'archivo' => 'informes-finales/'.$informe->id.'/fotografias/../../../../etc/passwd',
            'nombre_archivo' => 'malicioso.png',
            'origen' => 'INFORME',
        ]);
        $this->actingAs($user)
            ->get(route('informes-finales.anexos.mostrar', $anexo))
            ->assertNotFound();
    }

    public function test_miniatura_usa_ruta_mostrar(): void
    {
        [$user, $project] = $this->scenario();
        $component = Livewire::actingAs($user)->test(
            \App\Livewire\Proyectos\InformeFinal\EditInformeFinalProyecto::class,
            ['proyecto' => $project]
        )->set('currentStep', 8);
        $html = $component->html();
        $this->assertStringContainsString('route(\'informes-finales.anexos.mostrar', $html);
        $this->assertStringNotContainsString('anexoUrl(', $html);
    }

    public function test_enlace_ver_usa_ruta_mostrar(): void
    {
        [$user, $project] = $this->scenario();
        Storage::fake('public');
        $informe = $project->informeFinalInf001()->firstOrFail();
        $archivo = UploadedFile::fake()->image('foto.jpg');
        $ruta = $archivo->store('informes-finales/'.$informe->id.'/fotografias', 'public');
        $informe->anexos()->create([
            'tipo' => 'fotografias', 'categoria' => 'fotografia',
            'archivo' => $ruta, 'nombre_archivo' => 'foto.jpg', 'origen' => 'INFORME',
        ]);
        $component = Livewire::actingAs($user)->test(
            \App\Livewire\Proyectos\InformeFinal\EditInformeFinalProyecto::class,
            ['proyecto' => $project]
        )->set('currentStep', 8);
        $html = $component->html();
        $this->assertStringContainsString('informes-finales.anexos.mostrar', $html);
        $this->assertStringContainsString('target="_blank"', $html);
        $this->assertStringContainsString('rel="noopener noreferrer"', $html);
    }

    public function test_enlace_descargar_usa_ruta_descargar(): void
    {
        [$user, $project] = $this->scenario();
        Storage::fake('public');
        $informe = $project->informeFinalInf001()->firstOrFail();
        $archivo = UploadedFile::fake()->image('foto.jpg');
        $ruta = $archivo->store('informes-finales/'.$informe->id.'/fotografias', 'public');
        $informe->anexos()->create([
            'tipo' => 'fotografias', 'categoria' => 'fotografia',
            'archivo' => $ruta, 'nombre_archivo' => 'foto.jpg', 'origen' => 'INFORME',
        ]);
        $component = Livewire::actingAs($user)->test(
            \App\Livewire\Proyectos\InformeFinal\EditInformeFinalProyecto::class,
            ['proyecto' => $project]
        )->set('currentStep', 8);
        $html = $component->html();
        $this->assertStringContainsString('informes-finales.anexos.descargar', $html);
        $this->assertStringNotContainsString('download', $html);
    }

    public function test_blade_no_usa_storage_url_para_fotografias(): void
    {
        $blade = file_get_contents(resource_path('views/livewire/proyectos/informe-final/edit-informe-final-proyecto.blade.php'));
        $this->assertStringNotContainsString('anexoUrl(', $blade);
        $this->assertStringNotContainsString("Storage::url('", $blade);
        $this->assertStringNotContainsString("asset('storage/", $blade);
        $this->assertStringNotContainsString("url('storage/", $blade);
    }

    public function test_documento_anexo_sigue_funcionando(): void
    {
        Storage::fake('public');
        [$user, $project] = $this->scenario();
        $informe = $project->informeFinalInf001()->firstOrFail();
        $archivo = UploadedFile::fake()->create('documento.pdf', 100, 'application/pdf');
        $ruta = $archivo->store('informes-finales/'.$informe->id.'/documentos', 'public');
        $anexo = $informe->anexos()->create([
            'tipo' => 'manuales', 'categoria' => 'documento_general',
            'archivo' => $ruta, 'nombre_archivo' => 'documento.pdf', 'origen' => 'INFORME',
        ]);
        $this->actingAs($user)
            ->get(route('informes-finales.anexos.mostrar', $anexo))
            ->assertOk()
            ->assertHeader('Content-Type', 'application/pdf');
        $this->actingAs($user)
            ->get(route('informes-finales.anexos.descargar', $anexo))
            ->assertOk()
            ->assertHeader('Content-Disposition', 'attachment; filename="documento.pdf"');
    }

    public function test_boton_quitar_no_fue_alterado(): void
    {
        $blade = file_get_contents(resource_path('views/livewire/proyectos/informe-final/edit-informe-final-proyecto.blade.php'));
        $this->assertStringContainsString('wire:click="quitarFotografia(', $blade);
        $this->assertStringContainsString('wire:confirm="¿Quitar esta fotografía del Informe Final?"', $blade);
    }

    private function scenario(): array
    {
        $user = User::factory()->create(['name' => 'Coordinador Prueba', 'email' => 'coord.'.uniqid().'@example.test']);
        $role = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        $user->assignRole($role);
        $employee = Empleado::create([
            'nombre_completo' => 'Coordinador Prueba',
            'numero_empleado' => (string) random_int(100000, 999999),
            'celular' => '99999999',
            'sexo' => 'Masculino',
            'user_id' => $user->id,
            'tipo_empleado' => 'docente',
        ]);
        $type = \App\Models\Proyecto\VinculacionTipoAccion::firstOrCreate(
            ['codigo' => 'DESARROLLO_LOCAL_REGIONAL'],
            ['nombre' => 'Desarrollo local y regional', 'activo' => true]
        );
        $project = Proyecto::create([
            'tipo_accion_id' => $type->id,
            'codigo_proyecto' => 'PROY-'.uniqid(),
            'nombre_proyecto' => 'Proyecto de prueba',
            'fecha_inicio' => '2026-01-12',
            'fecha_finalizacion' => '2026-11-30',
        ]);
        \App\Models\Proyecto\EmpleadoProyecto::create([
            'empleado_id' => $employee->id,
            'proyecto_id' => $project->id,
            'rol' => 'Coordinador',
        ]);
        $flujo = \App\Models\Proyecto\FlujoAprobacion::create([
            'codigo' => 'FLUJO_'.uniqid(),
            'nombre' => 'Flujo prueba',
            'proceso' => 'PROYECTO',
            'tipo_accion_id' => $type->id,
            'codigo_formulario' => 'FORM-DVUS-001',
            'activo' => true,
        ]);
        $project->update(['flujo_aprobacion_id' => $flujo->id]);
        app(InformeFinalProyectoWorkflowService::class)->crearInformeFinal($project, $user);
        return [$user, $project];
    }
}
