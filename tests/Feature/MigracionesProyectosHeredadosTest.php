<?php

namespace Tests\Feature;

use App\Models\Proyecto\Proyecto;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Migraciones del 17-09 restituidas: se habían ejecutado en algunas bases sin
 * llegar al repositorio, y quien no las corrió veía los proyectos del dump sin
 * tipo de acción y el flujo del FORM-DVUS-001 sin las etapas de Vinculación.
 */
class MigracionesProyectosHeredadosTest extends TestCase
{
    use DatabaseTransactions;

    public function test_los_proyectos_heredados_sin_tipo_pasan_a_desarrollo_local(): void
    {
        $voluntariado = DB::table('vinculacion_tipos_accion')->where('codigo', 'VOLUNTARIADO')->value('id');
        $desarrollo = DB::table('vinculacion_tipos_accion')->where('codigo', 'DESARROLLO_LOCAL_REGIONAL')->value('id');
        $heredado = Proyecto::create(['nombre_proyecto' => 'Heredado sin tipo']);
        $conTipo = Proyecto::create(['nombre_proyecto' => 'Voluntariado', 'tipo_accion_id' => $voluntariado]);

        $this->migracion('2026_09_17_000001_backfill_tipo_accion_on_legacy_proyectos')->up();

        $this->assertSame((int) $desarrollo, (int) $heredado->fresh()->tipo_accion_id);
        $this->assertSame('FORM-DVUS-001', $heredado->fresh()->codigoFormularioFlujo());
        $this->assertSame((int) $voluntariado, (int) $conTipo->fresh()->tipo_accion_id);
    }

    public function test_crea_el_flujo_del_form_dvus_001_si_no_existe(): void
    {
        DB::table('flujos_aprobacion')->where('codigo_formulario', 'FORM-DVUS-001')->update(['activo' => false]);

        $this->migracion('2026_09_17_000002_add_revision_vinculacion_stages_to_form_dvus_001_flow')->up();

        $flujo = DB::table('flujos_aprobacion')->where('codigo_formulario', 'FORM-DVUS-001')->where('activo', true)->sole();
        $etapas = $this->etapas($flujo->id);

        $this->assertSame(
            ['Enlace Vinculacion', 'Jefe Departamento', 'Director centro', 'Revisor Vinculacion', 'Director Vinculacion'],
            $etapas->pluck('nombre')->all()
        );
        $this->assertSame([1, 2, 3, 4, 5], $etapas->pluck('orden')->map(fn ($o) => (int) $o)->all());
        $this->assertSame($etapas->pluck('nombre')->all(), $etapas->pluck('rol')->all());
        // Un cargo por etapa: con cargo compartido la adopción no identifica la etapa.
        $this->assertSame($etapas->pluck('nombre')->all(), $etapas->pluck('cargo')->all());
        // Solo el Director de Vinculación resuelve informes y cierre.
        $this->assertSame([0, 0, 0, 0, 1], $etapas->pluck('aplica_cierre_proyecto')->map(fn ($v) => (int) $v)->all());
        // Sin responsable fijo: se envía a todo el rol y el flujo funciona sin más configuración.
        $this->assertTrue($etapas->every(fn ($etapa) => ! $etapa->requiere_asignacion && $etapa->usuario_responsable_id === null));
    }

    public function test_agrega_solo_las_etapas_de_vinculacion_que_faltan_y_no_las_repite(): void
    {
        DB::table('flujos_aprobacion')->where('codigo_formulario', 'FORM-DVUS-001')->update(['activo' => false]);
        $flujo = DB::table('flujos_aprobacion')->insertGetId([
            'codigo' => 'FORM001_MANUAL_'.uniqid(),
            'nombre' => 'Configurado a mano',
            'proceso' => 'PROYECTO',
            'codigo_formulario' => 'FORM-DVUS-001',
            'activo' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $cargo = DB::table('cargo_firma')->join('tipo_cargo_firma', 'tipo_cargo_firma.id', '=', 'cargo_firma.tipo_cargo_firma_id')
            ->where('tipo_cargo_firma.nombre', 'Enlace Vinculacion')->value('cargo_firma.id');
        foreach (['Enlace propio', 'Jefe propio'] as $i => $nombre) {
            DB::table('flujos_aprobacion_etapas')->insert([
                'flujo_aprobacion_id' => $flujo, 'orden' => $i + 1, 'codigo' => 'PROPIA_'.$i, 'nombre' => $nombre,
                'cargo_firma_id' => $cargo, 'activo' => true, 'configuracion_vigente' => true,
                'created_at' => now(), 'updated_at' => now(),
            ]);
        }

        $migracion = $this->migracion('2026_09_17_000002_add_revision_vinculacion_stages_to_form_dvus_001_flow');
        $migracion->up();
        $migracion->up();

        $this->assertSame(
            ['Enlace propio', 'Jefe propio', 'Revisor Vinculacion', 'Director Vinculacion'],
            $this->etapas($flujo)->pluck('nombre')->all()
        );
    }

    private function migracion(string $nombre): object
    {
        return require database_path("migrations/{$nombre}.php");
    }

    private function etapas(int $flujoId)
    {
        return DB::table('flujos_aprobacion_etapas as e')
            ->leftJoin('roles as r', 'r.id', '=', 'e.rol_revisor_id')
            ->leftJoin('cargo_firma as c', 'c.id', '=', 'e.cargo_firma_id')
            ->leftJoin('tipo_cargo_firma as t', 't.id', '=', 'c.tipo_cargo_firma_id')
            ->where('e.flujo_aprobacion_id', $flujoId)
            ->orderBy('e.orden')
            ->get(['e.*', 'r.name as rol', 't.nombre as cargo']);
    }
}
