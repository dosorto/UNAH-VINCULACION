<?php

namespace Tests\Feature;

use App\Models\ENF\EnfAccion;
use App\Models\ENF\EnfDocumento;
use App\Models\Proyecto\FlujoAprobacion;
use App\Models\User;
use App\Services\Documents\FormDvus018DataMapper;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class EnfDocumentoArchivoTest extends TestCase
{
    use DatabaseTransactions;

    public function test_detalle_enf_muestra_los_archivos_del_paso_10_con_sus_acciones(): void
    {
        Storage::fake('public');
        $user = $this->usuarioConRol();
        $accion = EnfAccion::create([
            'codigo_formulario' => 'FORM-DVUS-018',
            'nombre_accion' => 'Acción con documentos',
            'creado_por_usuario_id' => $user->id,
        ]);
        $documento = $accion->documentos()->create([
            'tipo_documento' => 'oficio_remision_decano',
            'nombre' => 'Oficio de remisión',
            'ruta' => 'enf/documentos/oficio.pdf',
            'mime_type' => 'application/pdf',
            'tamano_bytes' => 9,
        ]);
        Storage::disk('public')->put($documento->ruta, 'contenido');

        $this->actingAs($user)
            ->get(route('enf.acciones.show', $accion))
            ->assertOk()
            ->assertSee('Ver anexo')
            ->assertSee(route('enf.documentos.ver', $documento), false)
            ->assertSee(route('enf.documentos.descargar', $documento), false);

        $this->get(route('enf.acciones.pdf.ver', $accion))
            ->assertOk()
            ->assertSee('Documentos adjuntos del paso 10')
            ->assertSee('Ver anexo')
            ->assertSee(route('enf.documentos.ver', $documento), false)
            ->assertSee(route('enf.documentos.descargar', $documento), false);

        $this->get(route('enf.acciones.edit', $accion))
            ->assertOk()
            ->assertSee('Archivo actual guardado.')
            ->assertSee('Selecciona otro archivo únicamente si deseas reemplazarlo.');
    }

    public function test_archivo_enf_puede_visualizarse_y_descargarse_desde_rutas_autenticadas(): void
    {
        Storage::fake('public');
        $user = $this->usuarioConRol();
        $accion = EnfAccion::create(['nombre_accion' => 'Acción con archivo']);
        $documento = EnfDocumento::create([
            'enf_accion_id' => $accion->id,
            'nombre' => 'Documento perfil',
            'ruta' => 'enf/documentos/perfil.pdf',
            'mime_type' => 'application/pdf',
            'tamano_bytes' => 13,
        ]);
        Storage::disk('public')->put($documento->ruta, 'archivo nexo');

        $response = $this->actingAs($user)
            ->get(route('enf.documentos.ver', $documento));

        $response
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf')
            ->assertHeader('content-disposition', 'inline; filename=documento-perfil.pdf');
        $this->assertSame('archivo nexo', $response->streamedContent());

        $this->get(route('enf.documentos.descargar', $documento))
            ->assertOk()
            ->assertDownload('documento-perfil.pdf');
    }

    public function test_envio_final_guarda_el_archivo_del_paso_10_antes_de_iniciar_el_flujo(): void
    {
        Storage::fake('public');
        $user = $this->usuarioConRol();
        $accion = EnfAccion::create([
            'codigo_formulario' => 'FORM-DVUS-018',
            'nombre_accion' => 'Borrador con oficio',
            'estado_flujo' => 'BORRADOR',
            'creado_por_usuario_id' => $user->id,
        ]);
        FlujoAprobacion::query()
            ->where('proceso', 'PROYECTO')
            ->where('codigo_formulario', 'FORM-DVUS-018')
            ->update(['activo' => false]);

        $response = $this->actingAs($user)->post(route('enf.acciones.store'), [
            'borrador_autoguardado_id' => $accion->id,
            'destinatarios' => [],
            'codigo_formulario' => 'FORM-DVUS-018',
            'nombre_accion' => 'Borrador con oficio',
            'supervisor_documentos' => [
                'oficio_remision_decano' => ['aplica' => 'Si'],
                'documento_perfil_programa' => ['aplica' => 'No'],
                'otros_documentos_respaldo' => ['aplica' => 'No'],
            ],
            'supervisor_documentos_archivos' => [
                'oficio_remision_decano' => UploadedFile::fake()->create('oficio.pdf', 20, 'application/pdf'),
            ],
        ]);

        $response->assertRedirect(route('proyectosDocente'));
        $documento = $accion->documentos()->where('tipo_documento', 'oficio_remision_decano')->sole();
        $this->assertSame('Oficio de remisión del Decano/Director Centro Regional', $documento->nombre);
        Storage::disk('public')->assertExists($documento->ruta);

        $celdas = app(FormDvus018DataMapper::class)->cells($accion->fresh());
        $this->assertContains([12, 2, 3, 'X', false], $celdas);
        $this->assertContains([12, 2, 4, '', false], $celdas);

        $documentoId = $documento->id;
        $rutaAnterior = $documento->ruta;
        $this->actingAs($user)->post(route('enf.acciones.store'), [
            'borrador_autoguardado_id' => $accion->id,
            'destinatarios' => [],
            'codigo_formulario' => 'FORM-DVUS-018',
            'nombre_accion' => 'Borrador con oficio',
            'supervisor_documentos' => [
                'oficio_remision_decano' => ['aplica' => 'Si'],
                'documento_perfil_programa' => ['aplica' => 'No'],
                'otros_documentos_respaldo' => ['aplica' => 'No'],
            ],
            'supervisor_documentos_archivos' => [
                'oficio_remision_decano' => UploadedFile::fake()->create('oficio-actualizado.pdf', 30, 'application/pdf'),
            ],
        ])->assertRedirect(route('proyectosDocente'));

        $documentoActualizado = $accion->documentos()->where('tipo_documento', 'oficio_remision_decano')->sole();
        $this->assertSame($documentoId, $documentoActualizado->id);
        $this->assertNotSame($rutaAnterior, $documentoActualizado->ruta);
        Storage::disk('public')->assertMissing($rutaAnterior);
        Storage::disk('public')->assertExists($documentoActualizado->ruta);
    }

    private function usuarioConRol(): User
    {
        $role = Role::findOrCreate('Usuario ENF documentos', 'web');
        $user = User::factory()->create(['active_role_id' => $role->id]);
        $user->assignRole($role);

        return $user;
    }
}
