<?php

namespace Tests\Feature;

use App\Clases\DataNavBar;
use App\Livewire\Docente\Proyectos\ProyectosPorFirmar;
use App\Livewire\Proyectos\Vinculacion\CreateProyectoVinculacion;
use App\Models\Estado\EstadoProyecto;
use App\Models\Estado\TipoEstado;
use App\Models\Personal\Empleado;
use App\Models\Proyecto\CargoFirma;
use App\Models\Proyecto\FirmaProyecto;
use App\Models\Proyecto\FlujoAprobacion;
use App\Models\Proyecto\FlujoAprobacionEtapa;
use App\Models\Proyecto\Proyecto;
use App\Models\Proyecto\TipoCargoFirma;
use App\Models\User;
use App\Support\Proyecto\EstadoGeneralProyecto;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Mail;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Pendientes de la auditoría de flujos (sección 9 de
 * docs/estados-y-etapas-proyectos.md): atomicidad del envío inicial,
 * concurrencia entre revisores y límite de candidatas de la bandeja.
 */
class ProyectoWorkflowConsistenciaTest extends TestCase
{
    use DatabaseTransactions;

    public function test_un_fallo_a_mitad_del_envio_lo_deshace_entero_y_permite_reenviar(): void
    {
        Mail::fake();
        $contexto = $this->contexto(2);
        $proyecto = $contexto['proyecto'];
        $firmasAntes = $proyecto->firma_proyecto()->count();

        // Falla justo después de crear las firmas: al registrar "En revision".
        $fallar = true;
        Event::listen('eloquent.creating: '.EstadoProyecto::class, function () use (&$fallar): void {
            if ($fallar) {
                throw new \RuntimeException('Fallo simulado al registrar el estado.');
            }
        });

        try {
            $this->enviar($proyecto);
            $this->fail('El envío debía fallar.');
        } catch (\RuntimeException $e) {
            $this->assertSame('Fallo simulado al registrar el estado.', $e->getMessage());
        }

        // Antes las firmas quedaban confirmadas con el proyecto en Borrador.
        $this->assertSame($firmasAntes, $proyecto->firma_proyecto()->count());
        $this->assertSame('Borrador', $proyecto->fresh()->estado_general);
        Mail::assertNothingSent();
        Mail::assertNothingQueued();

        // Y el reenvío quedaba bloqueado por las firmas huérfanas.
        $fallar = false;
        $this->enviar($proyecto);

        $this->assertSame('En revision', $proyecto->fresh()->estado_general);
        $this->assertCount(2, $proyecto->firmasDeEtapasDelFlujo($contexto['flujo']->id));
    }

    public function test_aprobar_y_rechazar_bloquean_el_expediente_antes_que_la_firma(): void
    {
        Mail::fake();
        $contexto = $this->contexto(2);
        $proyecto = $contexto['proyecto'];
        $this->enviar($proyecto);
        [$primera, $segunda] = $proyecto->firmasDeEtapasDelFlujo($contexto['flujo']->id)->values()->all();

        $this->actingAs($contexto['revisores'][0]);
        $bloqueos = $this->bloqueosDurante(fn () => (new ProyectosPorFirmar)->aprobar($primera->id));

        $this->assertSame('Aprobado', $primera->fresh()->estado_revision);
        $this->assertSame(['proyecto', 'firma_proyecto'], array_slice($bloqueos, 0, 2));
        // Con el estado fijo en «En revision», el historial nombra las etapas.
        $comentarios = $proyecto->estado_proyecto()->orderBy('id')->pluck('comentario');
        $this->assertContains('Proyecto enviado a revisión; espera en la etapa "Etapa 1".', $comentarios);
        $this->assertContains('Etapa "Etapa 1" aprobada; avanzó a "Etapa 2".', $comentarios);

        $this->actingAs($contexto['revisores'][1]);
        $bandeja = new ProyectosPorFirmar;
        $bandeja->rechazarId = $segunda->id;
        $bandeja->rechazarComentario = 'Corregir el marco lógico';
        $bloqueos = $this->bloqueosDurante(fn () => $bandeja->rechazar());

        $this->assertSame('Rechazado', $segunda->fresh()->estado_revision);
        $this->assertSame(['proyecto', 'firma_proyecto'], array_slice($bloqueos, 0, 2));
    }

    public function test_la_bandeja_no_pierde_la_etapa_actual_entre_mas_de_250_firmas_pendientes(): void
    {
        $etapas = 260;
        $rol = Role::create(['name' => 'Revisor de volumen '.uniqid(), 'guard_name' => 'web']);
        $revisor = $this->usuarioConEmpleado($rol);
        $cargo = $this->cargo();
        $flujo = FlujoAprobacion::create([
            'codigo' => 'VOLUMEN_'.uniqid(),
            'nombre' => 'Flujo con muchas etapas',
            'proceso' => 'PROYECTO',
            'activo' => true,
        ]);
        $proyecto = Proyecto::create(['nombre_proyecto' => 'Proyecto de volumen', 'flujo_aprobacion_id' => $flujo->id]);
        $this->ponerEstado($proyecto, 'En revision');

        DB::table('flujos_aprobacion_etapas')->insert(collect(range(1, $etapas))->map(fn (int $orden): array => [
            'flujo_aprobacion_id' => $flujo->id,
            'orden' => $orden,
            'codigo' => 'VOL_'.$orden,
            'nombre' => 'Etapa '.$orden,
            'cargo_firma_id' => $cargo->id,
            'rol_revisor_id' => $rol->id,
            'created_at' => now(),
            'updated_at' => now(),
        ])->all());
        $etapaIds = FlujoAprobacionEtapa::query()->where('flujo_aprobacion_id', $flujo->id)->orderBy('orden')->pluck('id');

        // Todas las firmas del recorrido son del mismo revisor. La primera es
        // la única que le llegó y es la más antigua: el recorte de las 250
        // más recientes la dejaba fuera.
        DB::table('firma_proyecto')->insert($etapaIds->values()->map(fn (int $etapaId, int $i): array => [
            'firmable_type' => Proyecto::class,
            'firmable_id' => $proyecto->id,
            'empleado_id' => $revisor->empleado->id,
            'cargo_firma_id' => $cargo->id,
            'estado_revision' => 'Pendiente',
            'hash' => 'volumen',
            'flujo_aprobacion_id' => $flujo->id,
            'flujo_aprobacion_etapa_id' => $etapaId,
            'orden_revision' => $i + 1,
            'etapa_codigo' => 'VOL_'.($i + 1),
            'etapa_nombre' => 'Etapa '.($i + 1),
            'rol_requerido' => $rol->name,
            'revision_ciclo' => 1,
            'created_at' => $i === 0 ? now()->subDays(30) : now(),
            'updated_at' => now(),
        ])->all());
        $actual = FirmaProyecto::query()->where('flujo_aprobacion_etapa_id', $etapaIds->first())->sole();

        $this->actingAs($revisor);
        $bandeja = new ProyectosPorFirmar;
        $ids = (new \ReflectionMethod($bandeja, 'firmasDisponiblesQuery'))->invoke($bandeja)->pluck('firma_proyecto.id')->all();

        $this->assertSame([$actual->id], $ids);
        $this->assertSame(1, DataNavBar::obtenerCantidadProyectosPorFirmar());
    }

    // ── Escenarios ──────────────────────────────────────────────────────────

    private function enviar(Proyecto $proyecto): void
    {
        $componente = new CreateProyectoVinculacion;
        (new \ReflectionMethod($componente, 'enviarPorFlujoDeEtapas'))->invoke($componente, $proyecto->fresh());
    }

    /** Tablas bloqueadas con FOR UPDATE, en orden, mientras corre $accion. */
    private function bloqueosDurante(callable $accion): array
    {
        $bloqueos = [];
        DB::listen(function ($query) use (&$bloqueos): void {
            if (preg_match('/^select .*? from `([a-z_]+)`.* for update$/is', $query->sql, $coincidencia)) {
                $bloqueos[] = $coincidencia[1];
            }
        });

        $accion();

        return $bloqueos;
    }

    /** @return array{proyecto:Proyecto,flujo:FlujoAprobacion,revisores:list<User>} */
    private function contexto(int $cantidadEtapas): array
    {
        $inscriptor = $this->usuarioConEmpleado();
        $flujo = FlujoAprobacion::create([
            'codigo' => 'CONSISTENCIA_'.uniqid(),
            'nombre' => 'Flujo de consistencia',
            'proceso' => 'PROYECTO',
            'activo' => true,
        ]);
        $revisores = [];

        for ($orden = 1; $orden <= $cantidadEtapas; $orden++) {
            $rol = Role::create(['name' => 'Revisor consistencia '.$orden.' '.uniqid(), 'guard_name' => 'web']);
            $revisores[] = $this->usuarioConEmpleado($rol);
            FlujoAprobacionEtapa::create([
                'flujo_aprobacion_id' => $flujo->id,
                'orden' => $orden,
                'codigo' => 'CONS_'.$orden,
                'nombre' => 'Etapa '.$orden,
                'rol_revisor_id' => $rol->id,
                'usuario_responsable_id' => end($revisores)->id,
                'cargo_firma_id' => $this->cargo()->id,
                'aplica_inscripcion' => true,
                'activo' => true,
            ]);
        }

        $proyecto = Proyecto::create([
            'nombre_proyecto' => 'Proyecto de consistencia '.uniqid(),
            'flujo_aprobacion_id' => $flujo->id,
        ]);
        $this->ponerEstado($proyecto, 'Borrador');
        CargoFirma::firstOrCreate([
            'descripcion' => 'Proyecto',
            'tipo_cargo_firma_id' => TipoCargoFirma::firstOrCreate(['nombre' => 'Coordinador Proyecto'])->id,
        ]);
        $this->actingAs($inscriptor);

        return ['proyecto' => $proyecto, 'flujo' => $flujo, 'revisores' => $revisores];
    }

    private function ponerEstado(Proyecto $proyecto, string $nombre): void
    {
        EstadoProyecto::withoutEvents(fn () => $proyecto->estado_proyecto()->create([
            'empleado_id' => Empleado::query()->value('id') ?? $this->usuarioConEmpleado()->empleado->id,
            'tipo_estado_id' => EstadoGeneralProyecto::id($nombre),
            'fecha' => now(),
            'es_actual' => true,
        ]));
    }

    private function usuarioConEmpleado(?Role $rol = null): User
    {
        $usuario = User::factory()->create();
        Empleado::create([
            'nombre_completo' => 'Empleado consistencia',
            'numero_empleado' => 'CON-'.uniqid(),
            'user_id' => $usuario->id,
        ]);

        if ($rol) {
            $usuario->assignRole($rol);
            $usuario->forceFill(['active_role_id' => $rol->id])->save();
        }

        return $usuario->fresh();
    }

    private function cargo(): CargoFirma
    {
        $nombre = 'Cargo consistencia '.uniqid();

        return CargoFirma::create([
            'descripcion' => 'Proyecto',
            'tipo_cargo_firma_id' => TipoCargoFirma::create(['nombre' => $nombre])->id,
            'tipo_estado_id' => TipoEstado::create(['nombre' => 'Estado '.$nombre])->id,
        ]);
    }
}
