<?php

namespace Tests\Feature;

use App\Models\Personal\Empleado;
use App\Models\Personal\FirmaSelloEmpleado;
use App\Models\Proyecto\CargoFirma;
use App\Models\Proyecto\Proyecto;
use App\Models\Proyecto\TipoCargoFirma;
use App\Models\User;
use App\Services\InformeFinal\InformeFinalPdfGenerator;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Regla global: la firma de quien registra o coordina un expediente nunca lleva
 * sello, aunque el empleado tenga uno en su perfil por otro cargo (p. ej. un
 * Director DVUS que también coordina proyectos). Las firmas administrativas sí.
 */
class FirmaSinSelloDelCoordinadorTest extends TestCase
{
    use DatabaseTransactions;

    public function test_admite_sello_distingue_al_coordinador_de_los_cargos_administrativos(): void
    {
        $coordinador = $this->cargo(TipoCargoFirma::COORDINADOR_PROYECTO);
        $decano = $this->cargo('Director centro');

        $this->assertFalse(CargoFirma::admiteSello($coordinador->id));
        $this->assertTrue(CargoFirma::admiteSello($decano->id));
        $this->assertTrue(CargoFirma::admiteSello(null));
    }

    public function test_al_guardar_la_firma_del_coordinador_se_descarta_el_sello_en_todo_modulo(): void
    {
        $empleado = $this->empleadoConFirmaYSello('coordinador');
        $proyecto = Proyecto::create(['nombre_proyecto' => 'Proyecto '.uniqid()]);

        // Mismo tipo de cargo en el registro del proyecto y en la ficha de actualización.
        foreach (['Proyecto', 'Ficha_actualizacion'] as $descripcion) {
            $firma = $proyecto->firma_proyecto()->create([
                'empleado_id' => $empleado->id,
                'cargo_firma_id' => $this->cargo(TipoCargoFirma::COORDINADOR_PROYECTO, $descripcion)->id,
                'estado_revision' => 'Aprobado',
                'firma_id' => $empleado->firma->id,
                'sello_id' => $empleado->sello->id,
                'hash' => 'hash',
            ]);

            $this->assertNull($firma->fresh()->sello_id, "La firma de coordinador ({$descripcion}) guardó sello.");
            $this->assertSame($empleado->firma->id, (int) $firma->fresh()->firma_id);
        }

        $administrativa = $proyecto->firma_proyecto()->create([
            'empleado_id' => $empleado->id,
            'cargo_firma_id' => $this->cargo('Director centro')->id,
            'estado_revision' => 'Aprobado',
            'sello_id' => $empleado->sello->id,
            'hash' => 'hash',
        ]);

        $this->assertSame($empleado->sello->id, (int) $administrativa->fresh()->sello_id);
    }

    public function test_sello_para_documento_ignora_el_sello_historico_del_coordinador(): void
    {
        $empleado = $this->empleadoConFirmaYSello('coordinador');
        $proyecto = Proyecto::create(['nombre_proyecto' => 'Proyecto '.uniqid()]);
        $firma = $proyecto->firma_proyecto()->create([
            'empleado_id' => $empleado->id,
            'cargo_firma_id' => $this->cargo(TipoCargoFirma::COORDINADOR_PROYECTO)->id,
            'estado_revision' => 'Aprobado',
            'hash' => 'hash',
        ]);
        // Firma anterior a la regla: guardó el sello del perfil.
        DB::table('firma_proyecto')->where('id', $firma->id)->update(['sello_id' => $empleado->sello->id]);

        $this->assertNull($firma->fresh()->selloParaDocumento(respaldoPerfil: true));

        $decano = $this->empleadoConFirmaYSello('decano');
        $firmaDecano = $proyecto->firma_proyecto()->create([
            'empleado_id' => $decano->id,
            'cargo_firma_id' => $this->cargo('Director centro')->id,
            'estado_revision' => 'Aprobado',
            'hash' => 'hash',
        ]);

        // Sin sello guardado, el cargo administrativo usa el sello del perfil como respaldo.
        $this->assertSame($decano->sello->id, $firmaDecano->fresh()->selloParaDocumento(respaldoPerfil: true)?->id);
        $this->assertNull($firmaDecano->fresh()->selloParaDocumento());
    }

    public function test_la_ficha_no_dibuja_sello_en_el_cuadro_del_coordinador(): void
    {
        $coordinador = $this->empleadoConFirmaYSello('coordinador');
        $decano = $this->empleadoConFirmaYSello('decano');
        $proyecto = Proyecto::create(['nombre_proyecto' => 'Proyecto '.uniqid()]);
        $firmaCoordinador = $proyecto->firma_proyecto()->create([
            'empleado_id' => $coordinador->id,
            'cargo_firma_id' => $this->cargo(TipoCargoFirma::COORDINADOR_PROYECTO)->id,
            'estado_revision' => 'Aprobado',
            'fecha_firma' => now(),
            'hash' => 'hash',
        ]);
        DB::table('firma_proyecto')->where('id', $firmaCoordinador->id)->update(['sello_id' => $coordinador->sello->id]);
        $proyecto->firma_proyecto()->create([
            'empleado_id' => $decano->id,
            'cargo_firma_id' => $this->cargo('Director centro')->id,
            'estado_revision' => 'Aprobado',
            'fecha_firma' => now(),
            'sello_id' => $decano->sello->id,
            'hash' => 'hash',
        ]);

        $html = view('components.fichas.firmas-fijas-proyecto', ['proyecto' => $proyecto, 'isPdf' => false])->render();

        $this->assertStringNotContainsString('sello/coordinador.png', $html);
        $this->assertStringContainsString('firma/coordinador.png', $html);
        $this->assertStringContainsString('sello/decano.png', $html);
        $this->assertSame(1, substr_count($html, 'alt="Sello de aprobación"'));
    }

    public function test_agregar_firma_del_coordinador_no_guarda_sello(): void
    {
        $this->cargo(TipoCargoFirma::COORDINADOR_PROYECTO);
        $empleado = $this->empleadoConFirmaYSello('coordinador');
        $proyecto = Proyecto::create(['nombre_proyecto' => 'Proyecto '.uniqid()]);

        $firma = $proyecto->agregarFirma(cargoFirma: TipoCargoFirma::COORDINADOR_PROYECTO, empleado: $empleado);

        $this->assertNull($firma->fresh()->sello_id);
        $this->assertSame($empleado->firma->id, (int) $firma->fresh()->firma_id);
    }

    public function test_informe_final_no_muestra_sello_en_la_firma_del_coordinador(): void
    {
        Storage::fake('public');
        $coordinador = $this->empleadoConFirmaYSello('coordinador');
        $decano = $this->empleadoConFirmaYSello('decano');
        foreach (['firma/coordinador.png', 'sello/coordinador.png', 'sello/decano.png'] as $ruta) {
            Storage::disk('public')->put($ruta, 'imagen');
        }
        $proyecto = Proyecto::create(['nombre_proyecto' => 'Proyecto '.uniqid()]);
        $firmaCoordinador = $proyecto->firma_proyecto()->create([
            'empleado_id' => $coordinador->id,
            'cargo_firma_id' => $this->cargo(TipoCargoFirma::COORDINADOR_PROYECTO)->id,
            'estado_revision' => 'Aprobado',
            'firma_id' => $coordinador->firma->id,
            'hash' => 'hash',
        ]);
        DB::table('firma_proyecto')->where('id', $firmaCoordinador->id)->update(['sello_id' => $coordinador->sello->id]);
        $firmaDecano = $proyecto->firma_proyecto()->create([
            'empleado_id' => $decano->id,
            'cargo_firma_id' => $this->cargo('Director centro')->id,
            'estado_revision' => 'Aprobado',
            'sello_id' => $decano->sello->id,
            'hash' => 'hash',
        ]);
        $firmaData = new \ReflectionMethod(InformeFinalPdfGenerator::class, 'firmaData');
        $generador = app(InformeFinalPdfGenerator::class);

        $datosCoordinador = $firmaData->invoke($generador, $firmaCoordinador->fresh(), false);
        $datosDecano = $firmaData->invoke($generador, $firmaDecano->fresh(), false);

        $this->assertNull($datosCoordinador['sello']);
        $this->assertNotNull($datosCoordinador['firma']);
        $this->assertNotNull($datosDecano['sello']);
    }

    private function cargo(string $nombre, string $descripcion = 'Proyecto'): CargoFirma
    {
        $tipo = TipoCargoFirma::firstOrCreate(['nombre' => $nombre]);

        return CargoFirma::firstOrCreate([
            'descripcion' => $descripcion,
            'tipo_cargo_firma_id' => $tipo->id,
        ]);
    }

    private function empleadoConFirmaYSello(string $nombre): Empleado
    {
        $usuario = User::create([
            'name' => ucfirst($nombre).' '.uniqid(),
            'email' => $nombre.'-'.uniqid().'@test.local',
        ]);
        $empleado = Empleado::create([
            'nombre_completo' => ucfirst($nombre).' de prueba',
            'numero_empleado' => 'SELLO-'.uniqid(),
            'user_id' => $usuario->id,
        ]);

        foreach (['firma', 'sello'] as $tipo) {
            FirmaSelloEmpleado::create([
                'empleado_id' => $empleado->id,
                'tipo' => $tipo,
                'ruta_storage' => "{$tipo}/{$nombre}.png",
                'estado' => true,
            ]);
        }

        return $empleado->fresh(['firma', 'sello']);
    }
}
