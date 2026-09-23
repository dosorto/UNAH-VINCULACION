<?php

namespace Tests\Feature\Dashboard;

use App\Models\ENF\EnfAccion;
use App\Models\Personal\Empleado;
use App\Models\User;
use App\Services\Dashboard\MisFormulariosService;
use App\Support\Dashboard\AmbitoPanel;
use App\Support\Dashboard\EstadosProyecto;
use App\Support\Dashboard\TipoAmbito;
use App\Support\Dashboard\EstadoGeneral;
use App\Support\Dashboard\Formularios\FormularioEnf;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

/**
 * Una acción de educación no formal es "del usuario" si la creó, si figura en
 * su equipo o si la tiene asignada para revisión. Antes el panel solo miraba
 * al creador, y un docente que participaba en una ENF ajena no la veía.
 *
 * La regla vive en EnfAccion::scopePerteneceA() para que "Mis formularios" y
 * el conteo por familia no puedan divergir.
 */
class EnfDelUsuarioTest extends TestCase
{
    use DatabaseTransactions;

    private User $usuario;

    private Empleado $empleado;

    protected function setUp(): void
    {
        parent::setUp();

        EstadosProyecto::olvidar();
        config(['nexo.dashboard.cache_ttl' => 0]);

        [$this->usuario, $this->empleado] = $this->docente('Docente participante');
    }

    public function test_la_enf_pertenece_a_quien_la_crea_integra_su_equipo_o_la_revisa(): void
    {
        [$otroUsuario, $otroEmpleado] = $this->docente('Docente ajeno');

        $creada = $this->accion('Creada por el docente', $this->usuario);

        $comoUsuario = $this->accion('En su equipo por usuario', $otroUsuario);
        $comoUsuario->equipo()->create(['user_id' => $this->usuario->id, 'nombre_completo' => 'Docente participante']);

        $comoEmpleado = $this->accion('En su equipo por empleado', $otroUsuario);
        $comoEmpleado->equipo()->create(['empleado_id' => $this->empleado->id, 'nombre_completo' => 'Docente participante']);

        $revisada = $this->accion('Asignada para revisión', $otroUsuario, ['responsable_revision_id' => $this->empleado->id]);

        $ajena = $this->accion('Ajena', $otroUsuario);
        $ajena->equipo()->create(['empleado_id' => $otroEmpleado->id, 'nombre_completo' => 'Docente ajeno']);

        $retirado = $this->accion('Retirado del equipo', $otroUsuario);
        $retirado->equipo()->create(['empleado_id' => $this->empleado->id, 'nombre_completo' => 'Docente participante'])->delete();

        $ids = EnfAccion::query()->perteneceA($this->usuario->id, $this->empleado->id)->pluck('id')->all();

        foreach ([$creada, $comoUsuario, $comoEmpleado, $revisada] as $propia) {
            $this->assertContains($propia->id, $ids, "«{$propia->nombre_accion}» debería pertenecer al docente.");
        }

        foreach ([$ajena, $retirado] as $ajenaAccion) {
            $this->assertNotContains($ajenaAccion->id, $ids, "«{$ajenaAccion->nombre_accion}» no debería pertenecer al docente.");
        }
    }

    public function test_mis_formularios_incluye_las_enf_en_las_que_participa(): void
    {
        [$otroUsuario] = $this->docente('Coordinador de la acción');

        $participada = $this->accion('Diplomado en el que participa', $otroUsuario);
        $participada->equipo()->create(['empleado_id' => $this->empleado->id, 'nombre_completo' => 'Docente participante']);

        $nombres = app(MisFormulariosService::class)
            ->para($this->empleado->id, $this->usuario->id)
            ->pluck('nombre');

        $this->assertContains('Diplomado en el que participa', $nombres);
    }

    public function test_una_enf_aprobada_se_etiqueta_aprobado_y_cuenta_en_curso(): void
    {
        $this->accion('Curso aprobado', $this->usuario, ['estado_flujo' => 'APROBADO']);

        $servicio = app(MisFormulariosService::class);
        $fila = $servicio->para($this->empleado->id, $this->usuario->id)->firstWhere('nombre', 'Curso aprobado');

        $this->assertSame('Aprobado', $fila['estado']);
        $this->assertSame(1, $servicio->resumen($this->empleado->id, $this->usuario->id)['en_curso']);
    }

    public function test_el_conteo_personal_del_formulario_enf_incluye_las_participadas(): void
    {
        [$otroUsuario] = $this->docente('Coordinador de la acción');

        $this->accion('Propia', $this->usuario, ['estado_flujo' => 'EN_REVISION']);
        $participada = $this->accion('Participada', $otroUsuario, ['estado_flujo' => 'EN_REVISION']);
        $participada->equipo()->create(['user_id' => $this->usuario->id, 'nombre_completo' => 'Docente participante']);
        $this->accion('Ajena', $otroUsuario, ['estado_flujo' => 'EN_REVISION']);

        $personal = new AmbitoPanel(
            tipo: TipoAmbito::Personal,
            empleadoId: $this->empleado->id,
            userId: $this->usuario->id,
        );

        $conteos = (new FormularioEnf('FORM-DVUS-018', 'Educación no formal'))->conteos($personal);

        $this->assertSame(2, $conteos[EstadoGeneral::EN_REVISION]);
        $this->assertSame(2, array_sum($conteos));
    }

    /** @return array{0: User, 1: Empleado} */
    private function docente(string $nombre): array
    {
        $usuario = User::factory()->create(['email' => 'enf.'.uniqid().'@example.test']);
        $empleado = Empleado::create([
            'nombre_completo' => $nombre,
            'numero_empleado' => (string) random_int(100000, 999999),
            'celular' => '99999999',
            'sexo' => 'Femenino',
            'user_id' => $usuario->id,
            'tipo_empleado' => 'docente',
        ]);

        return [$usuario, $empleado];
    }

    private function accion(string $nombre, User $creador, array $atributos = []): EnfAccion
    {
        return EnfAccion::create(array_merge([
            'codigo_formulario' => 'FORM-DVUS-018',
            'nombre_accion' => $nombre,
            'estado_flujo' => 'BORRADOR',
            'revision_ciclo' => 0,
            'creado_por_usuario_id' => $creador->id,
        ], $atributos));
    }
}
