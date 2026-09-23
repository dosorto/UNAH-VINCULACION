<?php

namespace Tests\Feature;

use App\Models\Estado\TipoEstado;
use App\Models\Personal\Empleado;
use App\Models\Proyecto\CargoFirma;
use App\Models\Proyecto\FlujoAprobacion;
use App\Models\Proyecto\FlujoAprobacionEtapa;
use App\Models\Proyecto\Proyecto;
use App\Models\Proyecto\TipoCargoFirma;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

/**
 * Una etapa puede habilitar a varias personas para firmar (una fila de firma por cada
 * usuario con el rol). El cuadro de firma de la ficha es de quien firmó: mientras nadie
 * lo haga va en blanco, y nunca muestra a un candidato como si hubiera firmado.
 */
class FichaFirmaDelFirmanteRealTest extends TestCase
{
    use DatabaseTransactions;

    public function test_el_cuadro_queda_en_blanco_mientras_nadie_firma(): void
    {
        [$proyecto, , $candidatos] = $this->proyectoConDosCandidatos();

        $html = $this->fichaFirmas($proyecto);

        foreach ($candidatos as $empleado) {
            $this->assertStringNotContainsString($empleado->nombre_completo, $html);
        }
        $this->assertStringNotContainsString('Firmado digitalmente', $html);
        $this->assertNull($proyecto->firmasParaFicha()->firstWhere('etapa.codigo', '!=', null)['firma'] ?? null);
    }

    public function test_el_cuadro_muestra_a_quien_firmo_aunque_no_sea_la_ultima_fila(): void
    {
        [$proyecto, $firmas, $candidatos] = $this->proyectoConDosCandidatos();

        // Firma el primero: antes se pintaba el último registro por id, sin mirar quién firmó.
        $firmas[0]->update(['estado_revision' => 'Aprobado', 'fecha_firma' => now()]);

        $html = $this->fichaFirmas($proyecto->fresh());

        $this->assertStringContainsString($candidatos[0]->nombre_completo, $html);
        $this->assertStringNotContainsString($candidatos[1]->nombre_completo, $html);

        $firmaFicha = $proyecto->fresh()->firmasParaFicha()->first()['firma'] ?? null;
        $this->assertNotNull($firmaFicha);
        $this->assertSame($candidatos[0]->id, (int) $firmaFicha->empleado_id);
    }

    private function fichaFirmas(Proyecto $proyecto): string
    {
        return view('components.fichas.firmas-fijas-proyecto', [
            'proyecto' => $proyecto,
            'isPdf' => false,
        ])->render();
    }

    /** @return array{0: Proyecto, 1: array, 2: array<int, Empleado>} */
    private function proyectoConDosCandidatos(): array
    {
        $flujo = FlujoAprobacion::create([
            'codigo' => 'FLUJO_FIRMAS_'.uniqid(),
            'nombre' => 'Flujo firmas ficha',
            'proceso' => Proyecto::FLUJO_INSCRIPCION,
            'activo' => true,
        ]);
        $proyecto = Proyecto::create([
            'nombre_proyecto' => 'Proyecto firmas '.uniqid(),
            'codigo_proyecto' => 'FIRM-'.uniqid(),
            'flujo_aprobacion_id' => $flujo->id,
        ]);
        $cargo = CargoFirma::create([
            'descripcion' => 'Proyecto',
            'tipo_cargo_firma_id' => TipoCargoFirma::firstOrCreate(['nombre' => 'Jefe Departamento'])->id,
            'tipo_estado_id' => TipoEstado::firstOrCreate(['nombre' => 'Estado firmas ficha'])->id,
        ]);
        $etapa = FlujoAprobacionEtapa::create([
            'flujo_aprobacion_id' => $flujo->id,
            'orden' => 1,
            'codigo' => 'FIRMAS_ETAPA_'.uniqid(),
            'nombre' => 'Jefe Departamento',
            'tipo_etapa' => 'APROBACION',
            'cargo_firma_id' => $cargo->id,
            'activo' => true,
        ]);

        $candidatos = [];
        $firmas = [];
        foreach (['Primera Jefatura Candidata', 'Segunda Jefatura Candidata'] as $nombre) {
            $empleado = Empleado::create([
                'nombre_completo' => $nombre.' '.uniqid(),
                'numero_empleado' => 'FIRM-'.uniqid(),
                'celular' => '99999999',
                'user_id' => User::create([
                    'name' => $nombre,
                    'email' => 'firmas-'.uniqid().'@unah.test',
                    'password' => bcrypt('secret-firmas'),
                ])->id,
            ]);
            $candidatos[] = $empleado;
            $firmas[] = $proyecto->firma_proyecto()->create([
                'empleado_id' => $empleado->id,
                'cargo_firma_id' => $cargo->id,
                'estado_revision' => 'Pendiente',
                'hash' => 'hash-test-'.uniqid(),
                'flujo_aprobacion_id' => $flujo->id,
                'flujo_aprobacion_etapa_id' => $etapa->id,
                'orden_revision' => $etapa->orden,
                'etapa_codigo' => $etapa->codigo,
                'etapa_nombre' => $etapa->nombre,
                'revision_ciclo' => 1,
            ]);
        }

        return [$proyecto->fresh(), $firmas, $candidatos];
    }
}
