<?php

namespace Tests\Feature;

use App\Livewire\Proyectos\Vinculacion\CreateProyectoVinculacion;
use App\Models\Proyecto\EntidadContraparte;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

/**
 * Modal de contraparte del paso 3: una contraparte existente se usa tal como está en el
 * catálogo (solo se escriben compromisos e instrumento); crear una nueva exige todos sus datos.
 */
class FormDvus001ContraparteModalTest extends TestCase
{
    use DatabaseTransactions;

    private const INSTRUMENTO = [
        'id' => null, 'tipo_documento' => 'carta_intenciones',
        'documento_url' => 'proyectos/contrapartes/instrumentos/carta.pdf',
        'nombre_archivo' => 'carta.pdf', 'documento_file' => null,
    ];

    private function componente(bool $esVoluntariado = false): CreateProyectoVinculacion
    {
        $component = new CreateProyectoVinculacion;
        $component->esVoluntariado = $esVoluntariado;
        $component->openContraparteModal();

        return $component;
    }

    private function catalogo(string $nombre = 'Alcaldía Municipal de Prueba', ?string $rtn = '08019999000001'): EntidadContraparte
    {
        return EntidadContraparte::create([
            'rtn' => $rtn, 'nombre' => $nombre, 'tipo_entidad' => 'gobierno_municipal',
            'nombre_contacto' => 'Ana López', 'cargo_contacto' => 'Alcaldesa',
            'telefono' => '2222-0000', 'correo' => 'alcaldia@example.com',
        ]);
    }

    private function erroresDe(callable $accion): array
    {
        try {
            $accion();
        } catch (ValidationException $e) {
            return $e->errors();
        }

        $this->fail('Se esperaba ValidationException.');
    }

    public function test_sin_elegir_ni_crear_no_se_puede_guardar(): void
    {
        $component = $this->componente();

        $this->assertNull($component->modoContraparte);

        $component->saveContraparte();

        $this->assertTrue($component->getErrorBag()->has('contraparteSeleccionadoId'));
        $this->assertSame([], $component->entidad_contraparte);
    }

    public function test_contraparte_existente_conserva_los_datos_del_catalogo(): void
    {
        $catalogo = $this->catalogo();
        $component = $this->componente();
        $component->contraparteSeleccionadoId = $catalogo->id;
        $component->agregarContraparteExistente();

        $this->assertSame('existente', $component->modoContraparte);
        $this->assertSame('Ana López', $component->nuevaContraparte['nombre_contacto']);

        // Compromisos obligatorios también para una contraparte existente.
        $component->nuevaContraparte['instrumento_formalizacion'] = [self::INSTRUMENTO];
        $errores = $this->erroresDe(fn () => $component->saveContraparte());
        $this->assertArrayHasKey('nuevaContraparte.descripcion_acuerdos', $errores);
        $this->assertArrayNotHasKey('nuevaContraparte.telefono', $errores);

        // Aunque el formulario llegue alterado, se guardan los datos del catálogo.
        $component->nuevaContraparte['nombre_contacto'] = 'Otra persona';
        $component->nuevaContraparte['telefono'] = '9999-9999';
        $component->nuevaContraparte['descripcion_acuerdos'] = 'Aporta el local comunal.';
        $component->saveContraparte();

        $this->assertCount(1, $component->entidad_contraparte);
        $fila = $component->entidad_contraparte[0];
        $this->assertSame($catalogo->id, $fila['entidad_contraparte_id']);
        $this->assertSame('Ana López', $fila['nombre_contacto']);
        $this->assertSame('2222-0000', $fila['telefono']);
        $this->assertSame('Aporta el local comunal.', $fila['descripcion_acuerdos']);
        $this->assertFalse($component->showContraparteModal);
        $this->assertNull($component->modoContraparte);
    }

    public function test_crear_exige_todos_los_campos_incluido_el_rtn_en_el_001(): void
    {
        $component = $this->componente();
        $component->crearContraparteNueva();

        $this->assertSame('nueva', $component->modoContraparte);

        $errores = $this->erroresDe(fn () => $component->saveContraparte());

        foreach (['rtn', 'nombre', 'tipo_entidad', 'nombre_contacto', 'cargo_contacto', 'telefono', 'correo', 'descripcion_acuerdos'] as $campo) {
            $this->assertArrayHasKey("nuevaContraparte.$campo", $errores);
        }

        // El FORM-DVUS-015 no incluye RTN de la contraparte.
        $voluntariado = $this->componente(esVoluntariado: true);
        $voluntariado->crearContraparteNueva();
        $this->assertArrayNotHasKey('nuevaContraparte.rtn', $this->erroresDe(fn () => $voluntariado->saveContraparte()));
    }

    public function test_crear_una_contraparte_que_ya_existe_pide_seleccionarla(): void
    {
        $this->catalogo('Junta de Agua de Prueba', null);
        $component = $this->componente();
        $component->crearContraparteNueva();
        $component->nuevaContraparte = array_replace($component->nuevaContraparte, [
            'rtn' => '08019999000002', 'nombre' => 'junta de agua de prueba', 'tipo_entidad' => 'sociedad_civil',
            'nombre_contacto' => 'Luis Pérez', 'cargo_contacto' => 'Presidente', 'telefono' => '3333-0000',
            'correo' => 'junta@example.com', 'descripcion_acuerdos' => 'Mano de obra.',
            'instrumento_formalizacion' => [self::INSTRUMENTO],
        ]);

        $component->saveContraparte();

        $this->assertStringContainsString('Usar seleccionada', $component->getErrorBag()->first('nuevaContraparte.nombre'));
        $this->assertSame([], $component->entidad_contraparte);
        $this->assertSame(1, EntidadContraparte::where('nombre', 'Junta de Agua de Prueba')->count());
    }

    public function test_crear_registra_la_contraparte_en_el_catalogo(): void
    {
        $component = $this->componente();
        $component->crearContraparteNueva();
        $component->nuevaContraparte = array_replace($component->nuevaContraparte, [
            'rtn' => 'HN-0801.1999.003', 'nombre' => 'Cooperativa Nueva de Prueba', 'tipo_entidad' => 'sector_privado',
            'nombre_contacto' => 'María Díaz', 'cargo_contacto' => 'Gerente', 'telefono' => '4444-0000',
            'correo' => 'cooperativa@example.com', 'descripcion_acuerdos' => 'Transporte.',
            'instrumento_formalizacion' => [self::INSTRUMENTO],
        ]);

        $component->saveContraparte();

        $catalogo = EntidadContraparte::where('nombre', 'Cooperativa Nueva de Prueba')->first();
        $this->assertNotNull($catalogo);
        $this->assertSame('HN-0801.1999.003', $catalogo->rtn);
        $this->assertSame($catalogo->id, $component->entidad_contraparte[0]['entidad_contraparte_id']);
        $this->assertSame('Transporte.', $component->entidad_contraparte[0]['descripcion_acuerdos']);
    }

    public function test_editar_solo_cambia_compromisos_e_instrumento(): void
    {
        $component = $this->componente();
        $component->entidad_contraparte = [[
            'entidad_contraparte_id' => 1, 'rtn' => '08019999000012', 'nombre' => 'Contraparte registrada',
            'tipo_entidad' => 'ong', 'nombre_contacto' => 'Contacto del proyecto', 'cargo_contacto' => 'Director',
            'telefono' => '5555-0000', 'correo' => 'ong@example.com', 'descripcion_acuerdos' => 'Anterior.',
            'instrumento_formalizacion' => [self::INSTRUMENTO],
        ]];

        $component->openContraparteModal(0);

        $this->assertSame('existente', $component->modoContraparte);

        $component->nuevaContraparte['nombre'] = 'Nombre alterado';
        $component->nuevaContraparte['descripcion_acuerdos'] = 'Compromisos actualizados.';
        $component->saveContraparte();

        $this->assertSame('Contraparte registrada', $component->entidad_contraparte[0]['nombre']);
        $this->assertSame('Contacto del proyecto', $component->entidad_contraparte[0]['nombre_contacto']);
        $this->assertSame('Compromisos actualizados.', $component->entidad_contraparte[0]['descripcion_acuerdos']);
    }

    public function test_contraparte_sin_rtn_exige_escribirlo_y_lo_guarda_en_el_catalogo(): void
    {
        $catalogo = $this->catalogo('Patronato Sin RTN de Prueba', null);
        $this->catalogo('Otra Contraparte con RTN', '08019999000009');
        $component = $this->componente();
        $component->contraparteSeleccionadoId = $catalogo->id;
        $component->agregarContraparteExistente();
        $component->nuevaContraparte['descripcion_acuerdos'] = 'Convoca a la comunidad.';
        $component->nuevaContraparte['instrumento_formalizacion'] = [self::INSTRUMENTO];

        $this->assertTrue($component->contraparteSinRtn);
        $this->assertArrayHasKey('nuevaContraparte.rtn', $this->erroresDe(fn () => $component->saveContraparte()));

        // Texto libre: solo se limita al tamaño de la columna.
        $component->nuevaContraparte['rtn'] = str_repeat('9', 51);
        $this->assertArrayHasKey('nuevaContraparte.rtn', $this->erroresDe(fn () => $component->saveContraparte()));

        // Un RTN que ya pertenece a otra contraparte no se asigna.
        $component->nuevaContraparte['rtn'] = '08019999000009';
        $component->saveContraparte();
        $this->assertStringContainsString('Otra Contraparte con RTN', $component->getErrorBag()->first('nuevaContraparte.rtn'));
        $this->assertSame([], $component->entidad_contraparte);

        $component->nuevaContraparte['rtn'] = 'RTN 0801.1999-0001A';
        $component->saveContraparte();

        $this->assertSame('RTN 0801.1999-0001A', $catalogo->fresh()->rtn);
        $this->assertSame('RTN 0801.1999-0001A', $component->entidad_contraparte[0]['rtn']);
    }

    public function test_en_el_015_el_rtn_faltante_es_opcional(): void
    {
        $catalogo = $this->catalogo('Fundación Sin RTN de Prueba', null);
        $component = $this->componente(esVoluntariado: true);
        $component->contraparteSeleccionadoId = $catalogo->id;
        $component->agregarContraparteExistente();
        $component->nuevaContraparte['descripcion_acuerdos'] = 'Brinda el espacio.';
        $component->nuevaContraparte['instrumento_formalizacion'] = [self::INSTRUMENTO];

        $component->saveContraparte();

        $this->assertCount(1, $component->entidad_contraparte);
        $this->assertNull($catalogo->fresh()->rtn);
    }

    public function test_editar_una_fila_sin_rtn_permite_completarlo(): void
    {
        $catalogo = $this->catalogo('Cooperativa Registrada Sin RTN', null);
        $component = $this->componente();
        $component->entidad_contraparte = [[
            'entidad_contraparte_id' => $catalogo->id, 'rtn' => '', 'nombre' => $catalogo->nombre,
            'tipo_entidad' => 'gobierno_municipal', 'nombre_contacto' => 'Ana López', 'cargo_contacto' => 'Alcaldesa',
            'telefono' => '2222-0000', 'correo' => 'alcaldia@example.com', 'descripcion_acuerdos' => 'Anterior.',
            'instrumento_formalizacion' => [self::INSTRUMENTO],
        ]];

        $component->openContraparteModal(0);
        $this->assertTrue($component->contraparteSinRtn);

        $component->nuevaContraparte['rtn'] = '08019999000011';
        $component->saveContraparte();

        $this->assertSame('08019999000011', $catalogo->fresh()->rtn);
        $this->assertSame('08019999000011', $component->entidad_contraparte[0]['rtn']);
    }

    public function test_el_modal_filtra_por_nombre_y_ofrece_crear(): void
    {
        $vista = file_get_contents(resource_path('views/livewire/proyectos/vinculacion/create-proyecto-vinculacion.blade.php'));

        $this->assertStringContainsString('Buscar contraparte por nombre...', $vista);
        $this->assertStringContainsString('wire:click="crearContraparteNueva"', $vista);
        $this->assertStringContainsString('@readonly($soloLectura)', $vista);
        $this->assertStringNotContainsString('Todos los campos son obligatorios.', $vista);
    }
}
