<?php

namespace Tests\Feature;

use App\Livewire\Proyectos\Vinculacion\CreateProyectoVinculacion;
use App\Models\Estado\TipoEstado;
use App\Models\Personal\CategoriaEmpleado;
use App\Models\Personal\Empleado;
use App\Models\Proyecto\Proyecto;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Paso 2 del FORM-DVUS-001/015: el voluntariado personal de la UNAH (ítem 13 del
 * 001, 15 del 015) se calcula con los empleados del equipo marcados como
 * voluntarios, según la categoría y el sexo de su perfil.
 */
class ProyectoVinculacionVoluntariosTest extends TestCase
{
    use DatabaseTransactions;

    public function test_los_voluntarios_suman_en_la_columna_de_su_categoria_y_sexo(): void
    {
        [$component, $proyecto] = $this->componenteConProyecto();
        $profesora = $this->empleado('Profesora por hora', 'Profesores x hora', 'Femenino');
        $administrativo = $this->empleado('Administrativo', 'Administrativo', 'Masculino');
        $titular = $this->empleado('Titular', 'Titular II', 'Masculino');
        $component->empleado_proyecto = [
            $this->fila($profesora, 'Voluntario'),
            $this->fila($administrativo, 'Voluntario'),
            $this->fila($titular, 'Integrante'),
        ];

        $this->assertTrue($component->autoGuardarBorrador());

        $this->assertSame(1, $component->voluntariado_participacion['vol_profesores_hora_mujeres']);
        $this->assertSame(1, $component->voluntariado_participacion['vol_personal_administrativo_hombres']);
        $this->assertSame(0, $component->voluntariado_participacion['vol_personal_servicio_hombres']);

        $proyecto->refresh();
        $this->assertSame(1, (int) $proyecto->vol_profesores_hora_mujeres);
        $this->assertSame(1, (int) $proyecto->vol_personal_administrativo_hombres);
        $this->assertSame(0, (int) $proyecto->vol_profesores_hora_hombres);
        $this->assertSame(
            ['Voluntario', 'Voluntario', 'Integrante'],
            collect([$profesora, $administrativo, $titular])
                ->map(fn (Empleado $empleado) => $proyecto->empleado_proyecto()->where('empleado_id', $empleado->id)->value('rol'))
                ->all()
        );
    }

    public function test_cambiar_un_voluntario_a_integrante_lo_descuenta(): void
    {
        [$component] = $this->componenteConProyecto();
        $profesora = $this->empleado('Profesora por hora', 'Profesores horarios', 'Femenino');
        $component->empleado_proyecto = [$this->fila($profesora, 'Voluntario')];
        $component->autoGuardarBorrador();
        $this->assertSame(1, $component->voluntariado_participacion['vol_profesores_hora_mujeres']);

        $component->empleado_proyecto[0]['rol'] = 'Integrante';
        $component->updated('empleado_proyecto.0.rol');

        $this->assertSame(0, $component->voluntariado_participacion['vol_profesores_hora_mujeres']);
    }

    public function test_un_docente_permanente_no_puede_ser_voluntario(): void
    {
        [$component] = $this->componenteConProyecto();
        $titular = $this->empleado('Titular', 'Titular II', 'Masculino');
        $component->empleado_proyecto = [$this->fila($titular, 'Voluntario')];

        $component->updated('empleado_proyecto.0.rol');

        $this->assertSame('Integrante', $component->empleado_proyecto[0]['rol']);
        $this->assertSame('No puede ser voluntario', collect(session('flash_notifications'))->last()['title']);
    }

    public function test_un_voluntario_sin_sexo_o_categoria_no_deja_avanzar_el_paso_2(): void
    {
        [$component] = $this->componenteConProyecto();
        $sinSexo = $this->empleado('Sin sexo', 'Servicios', null);
        $sinCategoria = $this->empleado('Sin categoría', null, 'Femenino');
        $component->empleado_proyecto = [
            $this->fila($sinSexo, 'Voluntario'),
            $this->fila($sinCategoria, 'Voluntario'),
        ];

        (new \ReflectionMethod($component, 'validarVoluntariosEquipo'))->invoke($component);

        $this->assertStringContainsString('no tiene sexo', $component->getErrorBag()->first('empleado_proyecto.0'));
        $this->assertStringContainsString('no tiene categoría', $component->getErrorBag()->first('empleado_proyecto.1'));
        $this->assertFalse($component->isStepComplete(2));
    }

    public function test_al_agregar_personal_no_permanente_entra_como_voluntario(): void
    {
        [$component] = $this->componenteConProyecto();
        $asistente = $this->empleado('Asistente', 'Asistentes técnicos laboratorios / Instructores', 'Masculino');
        $titular = $this->empleado('Titular', 'Titular I', 'Femenino');

        $component->selectEmpleadoFromModal($asistente->id, $asistente->nombre_completo);
        $component->selectEmpleadoFromModal($titular->id, $titular->nombre_completo);

        $this->assertSame(['Voluntario', 'Integrante'], array_column($component->empleado_proyecto, 'rol'));
        $this->assertSame(1, $component->voluntariado_participacion['vol_asistentes_tecnicos_hombres']);
    }

    public function test_en_el_015_el_personal_administrativo_entra_como_voluntario(): void
    {
        [$component] = $this->componenteConProyecto();
        $component->esVoluntariado = true;
        $administrativa = $this->empleado('Administrativa', 'Administrativo', 'Femenino', 'administrativo');
        $docenteSinCategoria = $this->empleado('Docente sin categoría', null, 'Masculino');

        $component->selectEmpleadoFromModal($administrativa->id, $administrativa->nombre_completo);
        $component->selectEmpleadoFromModal($docenteSinCategoria->id, $docenteSinCategoria->nombre_completo);

        $this->assertSame(['Voluntario'], array_column($component->empleado_proyecto, 'rol'));
        $this->assertSame('No es docente permanente', collect(session('flash_notifications'))->last()['title']);
    }

    public function test_el_paso_2_muestra_el_rol_y_el_voluntariado_calculado(): void
    {
        [, $proyecto] = $this->componenteConProyecto();
        $profesora = $this->empleado('Voluntaria Horaria Vista', 'Profesores horarios', 'Femenino');
        $proyecto->empleado_proyecto()->create(['empleado_id' => $profesora->id, 'rol' => 'Voluntario']);

        Livewire::test(CreateProyectoVinculacion::class, ['record' => $proyecto->id])
            ->set('currentStep', 2)
            // El voluntariado personal va justo debajo del equipo docente; el internacional, al final.
            ->assertSeeInOrder([
                'Integrantes del Equipo Docente Permanente Tiempo Completo',
                'Voluntariado personal de la UNAH',
                'Docentes Internacionales Participantes en el Proyecto',
                'Voluntariado internacional',
            ])
            ->assertSee('Voluntaria Horaria Vista')
            ->assertSee('Profesores horarios')
            ->assertSee('Suma en «Profesores horario x hora» (mujeres).')
            ->assertSee('Se calcula con los empleados del equipo marcados como voluntarios')
            ->assertSet('voluntariado_participacion.vol_profesores_hora_mujeres', 1);
    }

    public function test_el_pdf_no_lista_voluntarios_en_el_equipo_docente(): void
    {
        [, $proyecto] = $this->componenteConProyecto();
        $titular = $this->empleado('Integrante Titular Prueba', 'Titular III', 'Masculino');
        $profesora = $this->empleado('Voluntaria Horaria Prueba', 'Profesores horarios', 'Femenino');
        $proyecto->empleado_proyecto()->create(['empleado_id' => $titular->id, 'rol' => 'Integrante']);
        $proyecto->empleado_proyecto()->create(['empleado_id' => $profesora->id, 'rol' => 'Voluntario']);

        $html = view('components.fichas.ficha-proyecto-vinculacion', [
            'proyecto' => $proyecto->fresh(),
            'isPdf' => true,
        ])->render();

        $this->assertStringContainsString('Integrante Titular Prueba', $html);
        $this->assertStringNotContainsString('Voluntaria Horaria Prueba', $html);
    }

    /** @return array{0: CreateProyectoVinculacion, 1: Proyecto} */
    private function componenteConProyecto(): array
    {
        $coordinador = $this->empleado('Coordinador', 'Titular II', 'Masculino');
        $proyecto = Proyecto::create(['nombre_proyecto' => 'Proyecto voluntarios '.uniqid()]);
        $proyecto->coordinador_proyecto()->create(['empleado_id' => $coordinador->id, 'rol' => 'Coordinador']);
        $proyecto->estado_proyecto()->create([
            'empleado_id' => $coordinador->id,
            'tipo_estado_id' => TipoEstado::firstOrCreate(['nombre' => 'Borrador'])->id,
            'fecha' => now(),
            'comentario' => 'Borrador de prueba',
            'es_actual' => true,
        ]);

        $this->actingAs($coordinador->user->fresh('empleado'));

        $component = new CreateProyectoVinculacion;
        $component->recordId = $proyecto->id;
        $component->proyectoId = $proyecto->id;
        $component->nombre_proyecto = $proyecto->nombre_proyecto;

        return [$component, $proyecto];
    }

    private function empleado(string $nombre, ?string $categoria, ?string $sexo, string $tipo = 'docente'): Empleado
    {
        $usuario = User::create([
            'name' => $nombre.' '.uniqid(),
            'email' => 'voluntario-'.uniqid().'@test.local',
        ]);

        return Empleado::create([
            'nombre_completo' => $nombre,
            'numero_empleado' => 'VOL-'.uniqid(),
            'user_id' => $usuario->id,
            'sexo' => $sexo,
            'tipo_empleado' => $tipo,
            'categoria_id' => $categoria
                ? CategoriaEmpleado::firstOrCreate(['nombre' => $categoria], ['descripcion' => $categoria])->id
                : null,
        ]);
    }

    private function fila(Empleado $empleado, string $rol): array
    {
        return ['empleado_id' => $empleado->id, 'rol' => $rol, 'nombre' => $empleado->nombre_completo];
    }
}
