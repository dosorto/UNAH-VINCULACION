<?php

namespace Tests\Feature;

use App\Livewire\Proyectos\InformeFinal\EditInformeFinalProyecto;
use App\Models\Estudiante\Estudiante;
use App\Models\Estudiante\EstudianteProyecto;
use App\Models\InformeFinal\InformeFinalBeneficiario;
use App\Models\InformeFinal\InformeFinalProyecto;
use App\Models\Personal\Empleado;
use App\Models\Personal\EmpleadoProyecto;
use App\Models\Presupuesto\Presupuesto;
use App\Models\Proyecto\Actividad;
use App\Models\Proyecto\AporteInstitucional;
use App\Models\Proyecto\EntidadContraparte;
use App\Models\Proyecto\ObjetivoEspecifico;
use App\Models\Proyecto\Proyecto;
use App\Models\Proyecto\ResultadoEsperado;
use App\Models\Proyecto\VinculacionTipoAccion;
use App\Models\User;
use App\Services\InformeFinal\InformeFinalProyectoInitializer;
use App\Services\InformeFinal\InformeFinalProyectoValidator;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Route;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class InformeFinalINF001Test extends TestCase
{
    use DatabaseTransactions;

    public function test_se_crea_un_borrador_por_proyecto(): void
    {
        [$user,$project]=$this->scenario(); $report=$this->initialize($project,$user);
        $this->assertSame('BORRADOR',$report->estado); $this->assertDatabaseHas('informe_final_proyectos',['proyecto_id'=>$project->id]);
    }

    public function test_ruta_del_informe_carga_y_compila_la_vista(): void
    {
        [$user,$project]=$this->scenario();
        $this->actingAs($user)
            ->get(route('proyectos.informe-final', $project))
            ->assertOk()
            ->assertSee('INF-001')
            ->assertSee('Paso 1: Información general y beneficiarios');
    }

    public function test_no_se_duplican_informes(): void
    {
        [$user,$project]=$this->scenario(); $a=$this->initialize($project,$user); $b=$this->initialize($project,$user);
        $this->assertSame($a->id,$b->id); $this->assertSame(1,InformeFinalProyecto::where('proyecto_id',$project->id)->count());
    }

    public function test_se_precarga_informacion_general(): void
    {
        [$user,$project]=$this->scenario(); $report=$this->initialize($project,$user);
        $this->assertSame('Transformación digital en patronatos y juntas de agua de Orocuina',$report->nombre_proyecto); $this->assertSame('2026-01-12',$report->fecha_inicio->format('Y-m-d')); $this->assertSame(266792.44,(float)$report->presupuesto_planificado);
    }

    public function test_se_precargan_objetivos(): void
    {
        [$user,$project]=$this->scenario(); $report=$this->initialize($project,$user);
        $this->assertSame('Fortalecer la gestión comunitaria',$report->resultados->first()->objetivo_especifico);
    }

    public function test_se_precargan_resultados(): void
    {
        [$user,$project]=$this->scenario(); $report=$this->initialize($project,$user);
        $this->assertSame('Aplicación informática disponible',$report->resultados->first()->resultado_planificado);
    }

    public function test_se_precargan_actividades(): void
    {
        [$user,$project]=$this->scenario(); $report=$this->initialize($project,$user);
        $this->assertSame('Levantamiento de requerimientos',$report->actividades->first()->actividad_planificada);
    }

    public function test_actividad_precarga_todos_los_participantes_sin_concatenarlos(): void
    {
        [$user,$project]=$this->scenario();
        $activity=$project->actividades()->first();
        $secondUser=User::factory()->create();
        $second=Empleado::create(['nombre_completo'=>'Segundo Participante','numero_empleado'=>(string)random_int(100000,999999),'celular'=>'99999997','sexo'=>'Femenino','user_id'=>$secondUser->id,'tipo_empleado'=>'docente']);
        EmpleadoProyecto::create(['empleado_id'=>$second->id,'proyecto_id'=>$project->id,'rol'=>'Integrante']);
        $employeeIds=$project->docentes_proyecto()->pluck('empleado_id')->push($second->id)->unique()->all();
        $activity->empleados()->sync($employeeIds);
        $report=$this->initialize($project,$user);
        $snapshot=$report->actividades()->with('participantes')->first();
        $this->assertCount(2,$snapshot->participantes);
        $this->assertSame($snapshot->participantes->first()->nombre,$snapshot->responsable);
        $this->assertTrue($snapshot->participantes->first()->es_responsable);
        $component=$this->livewireComponent($user,$project)->set('currentStep',5);
        $component->assertSee('Participantes')->assertSee('Segundo Participante');
        $this->assertStringNotContainsString('Dorian Adolfo Ordóñez Osorto, Segundo Participante',$component->html());
    }

    public function test_autoguardado_conserva_texto_borrador_flujo_y_valor_final(): void
    {
        Notification::fake();
        [$user,$project]=$this->scenario();
        $flow=$project->flujo_aprobacion_id;
        $firmasAntes=DB::table('firma_proyecto')->count();
        $text=str_repeat('Lección comunitaria extensa. ',80);
        $this->livewireComponent($user,$project)
            ->set('general.lecciones_aprendidas',$text)
            ->assertSet('estadoGuardado','guardado');
        $report=InformeFinalProyecto::where('proyecto_id',$project->id)->firstOrFail();
        $this->assertSame($text,$report->lecciones_aprendidas);
        $this->assertSame('BORRADOR',$report->estado);
        $this->assertSame($flow,$project->fresh()->flujo_aprobacion_id);
        $this->assertSame($firmasAntes,DB::table('firma_proyecto')->count());
        Notification::assertNothingSent();
        $this->livewireComponent($user,$project)->assertSet('general.lecciones_aprendidas',$text);
    }

    public function test_autoguardado_no_valida_todo_y_guarda_fila_dinamica(): void
    {
        [$user,$project]=$this->scenario();
        $component=$this->livewireComponent($user,$project)
            ->set('general.recomendaciones','Primera versión')
            ->set('general.recomendaciones','Versión final')
            ->call('agregarFila','actividades');
        $index=count($component->get('actividades'))-1;
        $component->set("actividades.$index.actividad_planificada",'Actividad emergente autoguardada')->assertHasNoErrors();
        $this->assertDatabaseHas('informe_final_proyectos',['proyecto_id'=>$project->id,'recomendaciones'=>'Versión final','estado'=>'BORRADOR']);
        $this->assertDatabaseHas('informe_final_actividades',['informe_final_proyecto_id'=>$component->get('informe')->id,'actividad_planificada'=>'Actividad emergente autoguardada']);
    }

    public function test_actividad_emergente_admite_tipos_distintos_evitar_duplicados_y_conserva_ediciones(): void
    {
        [$user,$project]=$this->scenario();
        $student=Estudiante::create(['nombre'=>'Estudiante','apellido'=>'Actividad','cuenta'=>'EA-'.uniqid(),'sexo'=>'Femenino','user_id'=>$user->id]);
        EstudianteProyecto::create(['estudiante_id'=>$student->id,'proyecto_id'=>$project->id,'tipo_participacion_estudiante'=>'Voluntariado','cantidad_estudiantes_hombres'=>0,'cantidad_estudiantes_mujeres'=>1,'total_estudiantes'=>1]);
        $report=$this->initialize($project,$user);
        $activity=$report->actividades()->first();
        $employeeId=$report->equipoDocente()->first()->empleado_id;
        $studentSnapshot=$report->estudiantes()->first();
        $component=$this->livewireComponent($user,$project)->set('currentStep',5);
        $component->set('participanteSeleccion.0','docente:'.$employeeId)->call('agregarParticipanteActividad',0)->assertHasNoErrors();
        $component->call('agregarParticipanteActividad',0)->assertHasErrors('actividades.0.participantes');
        $component=$this->livewireComponent($user,$project)->set('currentStep',5);
        $component->set('participanteSeleccion.0','estudiante:'.$studentSnapshot->id)->call('agregarParticipanteActividad',0)->assertHasNoErrors();
        $participants=$component->get('actividades.0.participantes');
        $studentIndex=array_key_last($participants);
        $component->set("actividades.0.participantes.$studentIndex.rol",'Apoyo en pruebas')->assertSet('estadoGuardado','guardado');
        $this->assertDatabaseHas('informe_final_actividad_participantes',['informe_final_actividad_id'=>$activity->id,'tipo'=>'estudiante','informe_final_estudiante_id'=>$studentSnapshot->id,'rol'=>'Apoyo en pruebas']);
        $reopened=$this->initialize($project,$user)->actividades()->with('participantes')->first();
        $this->assertSame('Apoyo en pruebas',$reopened->participantes->firstWhere('tipo','estudiante')->rol);
    }

    public function test_no_permite_participantes_de_otro_proyecto(): void
    {
        [$user,$project]=$this->scenario();
        [, $otherProject]=$this->scenario();
        $otherReport=$this->initialize($otherProject,$user);
        $otherStudent=$otherReport->estudiantes()->create(['nombre'=>'Fuera del proyecto','tipo_participacion'=>'voluntariado','cantidad'=>1]);
        $this->livewireComponent($user,$project)
            ->set('participanteSeleccion.0','estudiante:'.$otherStudent->id)
            ->call('agregarParticipanteActividad',0)
            ->assertStatus(422);
    }

    public function test_se_precarga_equipo(): void
    {
        [$user,$project]=$this->scenario(); $report=$this->initialize($project,$user);
        $this->assertSame('Dorian Adolfo Ordóñez Osorto',$report->equipoDocente->first()->nombre); $this->assertTrue($report->equipoDocente->first()->es_coordinador);
    }

    public function test_se_guardan_beneficiarios(): void
    {
        [$user,$project]=$this->scenario(); $component=$this->livewireComponent($user,$project);
        $component->set('beneficiarios.edad_19_25',3504)->call('guardarBorrador')->assertHasNoErrors();
        $this->assertDatabaseHas('informe_final_beneficiarios',['edad_19_25'=>3504]);
    }

    public function test_se_calculan_totales_por_sexo(): void
    {
        $model=new InformeFinalBeneficiario(['hombres'=>1700,'mujeres'=>1804]); $this->assertSame(3504,$model->total_sexo);
    }

    public function test_se_calculan_totales_por_edad(): void
    {
        $model=new InformeFinalBeneficiario(['edad_0_10'=>100,'edad_11_18'=>200,'edad_19_25'=>300]); $this->assertSame(600,$model->total_edad);
    }

    public function test_se_calculan_totales_por_etnia(): void
    {
        $model=new InformeFinalBeneficiario(['indigena_hombres'=>10,'indigena_mujeres'=>20,'mestizo_hombres'=>30,'mestizo_mujeres'=>40]); $this->assertSame(100,$model->total_etnia);
    }

    public function test_se_guardan_estudiantes(): void
    {
        [$user,$project]=$this->scenario(); $report=$this->initialize($project,$user);
        $report->estudiantes()->create(['nombre'=>'Estudiante prueba','tipo_participacion'=>'pps_servicio_social','cantidad'=>4,'horas_dedicadas'=>320]);
        $this->assertDatabaseHas('informe_final_estudiantes',['nombre'=>'Estudiante prueba','cantidad'=>4]);
    }

    public function test_se_guardan_voluntarios(): void
    {
        [$user,$project]=$this->scenario(); $report=$this->initialize($project,$user);
        $report->voluntarios()->create(['nombre'=>'Voluntario prueba','tipo'=>'egresado','horas_dedicadas'=>20]);
        $this->assertDatabaseHas('informe_final_voluntarios',['nombre'=>'Voluntario prueba']);
    }

    public function test_se_precargan_estudiantes_masculinos_femeninos_y_sin_especificar_sin_inferir_nombres(): void
    {
        [$user,$project]=$this->scenario();
        $male=Estudiante::create(['nombre'=>'Andrea','apellido'=>'Prueba','cuenta'=>'M-'.uniqid(),'sexo'=>'Masculino','user_id'=>$user->id]);
        $female=Estudiante::create(['nombre'=>'Carlos','apellido'=>'Prueba','cuenta'=>'F-'.uniqid(),'sexo'=>'Femenino','user_id'=>$user->id]);
        $unknown=Estudiante::create(['nombre'=>'María','apellido'=>'Sin dato','cuenta'=>'N-'.uniqid(),'sexo'=>null,'user_id'=>$user->id]);
        foreach ([[$male,2,0],[$female,0,3],[$unknown,0,0]] as [$student,$men,$women]) {
            EstudianteProyecto::create(['estudiante_id'=>$student->id,'proyecto_id'=>$project->id,'tipo_participacion_estudiante'=>'Voluntariado','cantidad_estudiantes_hombres'=>$men,'cantidad_estudiantes_mujeres'=>$women,'total_estudiantes'=>$men+$women]);
        }
        $report=$this->initialize($project,$user);
        $this->assertDatabaseHas('informe_final_estudiantes',['informe_final_proyecto_id'=>$report->id,'nombre'=>'Andrea Prueba','sexo'=>'Masculino','cantidad'=>1]);
        $this->assertDatabaseHas('informe_final_estudiantes',['informe_final_proyecto_id'=>$report->id,'nombre'=>'Carlos Prueba','sexo'=>'Femenino','cantidad'=>1]);
        $this->assertDatabaseHas('informe_final_estudiantes',['informe_final_proyecto_id'=>$report->id,'nombre'=>'María Sin dato','sexo'=>null]);
        $this->livewireComponent($user,$project)->set('currentStep',3)->assertSee('Sin especificar');
    }

    public function test_se_precarga_sexo_de_voluntarios_y_reabrir_no_duplica_snapshots(): void
    {
        [$user,$project]=$this->scenario();
        $first=$this->initialize($project,$user);
        $component=$this->livewireComponent($user,$project)->call('openVoluntarioModal')
            ->set('voluntarioModal.nombre','Voluntaria Femenina')
            ->set('voluntarioModal.sexo','Femenino')
            ->set('voluntarioModal.identidad','0801-TEST')
            ->set('voluntarioModal.tipo','egresado')
            ->set('voluntarioModal.horas_dedicadas',12)
            ->call('saveVoluntarioModal')->assertHasNoErrors();
        $count=$first->voluntarios()->count();
        $this->assertDatabaseHas('informe_final_voluntarios',['informe_final_proyecto_id'=>$first->id,'nombre'=>'Voluntaria Femenina','sexo'=>'Femenino']);
        $second=$this->initialize($project,$user);
        $this->assertSame($count,$second->voluntarios()->count());
    }

    public function test_totales_de_participacion_normalizan_codigos_y_separan_sin_especificar(): void
    {
        [$user,$project]=$this->scenario();
        $component=$this->livewireComponent($user,$project)
            ->set('estudiantes',[
                ['nombre'=>'A','sexo'=>'M','tipo_participacion'=>'voluntariado','cantidad'=>2,'horas_dedicadas'=>0],
                ['nombre'=>'B','sexo'=>'femenino','tipo_participacion'=>'voluntariado','cantidad'=>3,'horas_dedicadas'=>0],
                ['nombre'=>'C','sexo'=>null,'tipo_participacion'=>'voluntariado','cantidad'=>4,'horas_dedicadas'=>0],
            ])
            ->set('voluntarios',[
                ['nombre'=>'V1','sexo'=>'masculino','tipo'=>'egresado','horas_dedicadas'=>0],
                ['nombre'=>'V2','sexo'=>'F','tipo'=>'egresado','horas_dedicadas'=>0],
                ['nombre'=>'V3','sexo'=>'','tipo'=>'egresado','horas_dedicadas'=>0],
            ]);
        $component->assertSet('totalesParticipacion.estudiantes_hombres',1)
            ->assertSet('totalesParticipacion.estudiantes_mujeres',1)
            ->assertSet('totalesParticipacion.estudiantes_sin_especificar',1)
            ->assertSet('totalesParticipacion.voluntarios_hombres',1)
            ->assertSet('totalesParticipacion.voluntarios_mujeres',1)
            ->assertSet('totalesParticipacion.voluntarios_sin_especificar',1);
    }

    public function test_estudiantes_modal_busca_agrega_edita_y_quita_registro_individual(): void
    {
        [$user,$project]=$this->scenario();
        $report=$this->initialize($project,$user);
        $student=Estudiante::create(['nombre'=>'Ana','apellido'=>'Modal','cuenta'=>'20260001234','sexo'=>'Femenino','user_id'=>$user->id]);
        EstudianteProyecto::create(['estudiante_id'=>$student->id,'proyecto_id'=>$project->id,'tipo_participacion_estudiante'=>'Voluntariado','cantidad_estudiantes_hombres'=>0,'cantidad_estudiantes_mujeres'=>1,'total_estudiantes'=>1]);
        $component=$this->livewireComponent($user,$project)->set('currentStep',3)
            ->call('openEstudianteModal')
            ->assertSet('showEstudianteModal',true)
            ->assertSee('Agregar estudiante participante')
            ->assertSee('Buscar estudiante')
            ->set('estudianteBusquedaCuenta','20260001234')
            ->call('buscarEstudiante')
            ->assertSet('estudianteEncontrado.nombre','Ana Modal')
            ->assertSet('estudianteEncontrado.sexo','Femenino')
            ->set('estudianteModal.tipo_participacion','pps_servicio_social')
            ->set('estudianteModal.horas_dedicadas',120)
            ->call('saveEstudianteModal')
            ->assertHasNoErrors()
            ->assertSet('showEstudianteModal',false);
        $this->assertDatabaseHas('informe_final_estudiantes',['informe_final_proyecto_id'=>$report->id,'estudiante_id'=>$student->id,'nombre'=>'Ana Modal','sexo'=>'Femenino','numero_cuenta'=>'20260001234','tipo_participacion'=>'pps_servicio_social','horas_dedicadas'=>120,'cantidad'=>1]);
        $rows=$component->get('estudiantes');
        $index=collect($rows)->search(fn ($row) => (int) ($row['estudiante_id'] ?? 0) === $student->id);
        $component->call('openEstudianteModal',$index)
            ->set('estudianteModal.tipo_participacion','voluntariado')
            ->set('estudianteModal.horas_dedicadas',140)
            ->call('saveEstudianteModal')->assertHasNoErrors();
        $this->assertDatabaseHas('informe_final_estudiantes',['informe_final_proyecto_id'=>$report->id,'estudiante_id'=>$student->id,'tipo_participacion'=>'voluntariado','horas_dedicadas'=>140]);
        $component->call('openEstudianteModal')->set('estudianteBusquedaCuenta','20260001234')->call('buscarEstudiante')->assertHasErrors('estudianteBusquedaCuenta');
        $component=$this->livewireComponent($user,$project);
        $rows=$component->get('estudiantes');
        $index=collect($rows)->search(fn ($row) => (int) ($row['estudiante_id'] ?? 0) === $student->id);
        $component->call('quitarFila','estudiantes',$index);
        $this->assertDatabaseMissing('informe_final_estudiantes',['informe_final_proyecto_id'=>$report->id,'estudiante_id'=>$student->id]);
    }

    public function test_estudiantes_listado_no_muestra_grupos_ni_cantidad_y_calcula_participacion(): void
    {
        [$user,$project]=$this->scenario();
        foreach ([['Uno','Masculino','Practica Profesional'],['Dos','Femenino','Servicio Social o PPS'],['Tres',null,'Voluntariado']] as $i=>[$name,$sex,$type]) {
            $student=Estudiante::create(['nombre'=>$name,'apellido'=>'Individual','cuenta'=>'IND-'.$i.'-'.uniqid(),'sexo'=>$sex,'user_id'=>$user->id]);
            EstudianteProyecto::create(['estudiante_id'=>$student->id,'proyecto_id'=>$project->id,'tipo_participacion_estudiante'=>$type,'cantidad_estudiantes_hombres'=>0,'cantidad_estudiantes_mujeres'=>0,'total_estudiantes'=>1]);
        }
        $component=$this->livewireComponent($user,$project)->set('currentStep',3);
        $component->assertDontSee('Grupo de estudiantes')->assertDontSee('Cantidad');
        $component->assertSet('totalesParticipacion.estudiantes_practica',1)
            ->assertSet('totalesParticipacion.estudiantes_pps',1)
            ->assertSet('totalesParticipacion.estudiantes_voluntariado',1);
    }

    public function test_busqueda_de_estudiantes_se_limita_a_los_vinculados_al_proyecto(): void
    {
        [$user,$project]=$this->scenario();
        $this->initialize($project,$user);
        $linked=Estudiante::create(['nombre'=>'Ana','apellido'=>'Vinculada','cuenta'=>'20260009991','sexo'=>'Femenino','user_id'=>$user->id]);
        EstudianteProyecto::create(['estudiante_id'=>$linked->id,'proyecto_id'=>$project->id,'tipo_participacion_estudiante'=>'Voluntariado','cantidad_estudiantes_hombres'=>0,'cantidad_estudiantes_mujeres'=>1,'total_estudiantes'=>1]);
        $otherProject=Proyecto::create([
            'tipo_accion_id'=>$project->tipo_accion_id,
            'codigo_proyecto'=>'PROY-OTRO-'.uniqid(),
            'nombre_proyecto'=>'Proyecto externo de prueba',
        ]);
        $other=Estudiante::create(['nombre'=>'Ana','apellido'=>'Externa','cuenta'=>'20260009992','sexo'=>'Masculino','user_id'=>$user->id]);
        EstudianteProyecto::create(['estudiante_id'=>$other->id,'proyecto_id'=>$otherProject->id,'tipo_participacion_estudiante'=>'Voluntariado','cantidad_estudiantes_hombres'=>1,'cantidad_estudiantes_mujeres'=>0,'total_estudiantes'=>1]);

        $this->livewireComponent($user,$project)
            ->call('openEstudianteModal')
            ->set('estudianteBusquedaCuenta',$linked->cuenta)
            ->call('buscarEstudiante')
            ->assertSet('estudianteEncontrado.estudiante_id',$linked->id)
            ->set('estudianteBusquedaCuenta',$other->cuenta)
            ->call('buscarEstudiante')
            ->assertHasErrors('estudianteBusquedaCuenta');
    }

    public function test_modal_estudiante_valida_cuenta_y_guarda_registro_manual_sin_crear_maestro(): void
    {
        [$user,$project]=$this->scenario();
        $before=Estudiante::count();
        $component=$this->livewireComponent($user,$project)->call('openEstudianteModal');
        $component->assertSee('Buscar por número de cuenta')
            ->assertDontSee('Nombre (opcional)')
            ->set('estudianteBusquedaCuenta','')
            ->call('buscarEstudiante')
            ->assertHasErrors('estudianteBusquedaCuenta')
            ->assertSee('Ingrese un número de cuenta para realizar la búsqueda.')
            ->set('estudianteBusquedaCuenta','ABC-123')
            ->call('buscarEstudiante')
            ->assertSee('El número de cuenta no tiene un formato válido.')
            ->set('estudianteBusquedaCuenta','20269990000')
            ->call('buscarEstudiante')
            ->assertSet('mostrarRegistroManual',true)
            ->assertSee('O registrar estudiante manualmente')
            ->call('limpiarSeleccionEstudiante')
            ->assertSet('estudianteEncontrado',null)
            ->assertSet('mostrarRegistroManual',false)
            ->set('estudianteBusquedaCuenta','20269990000')
            ->call('buscarEstudiante')
            ->set('estudianteManual.nombres','Estudiante')
            ->set('estudianteManual.apellidos','Manual')
            ->set('estudianteManual.numero_cuenta','20269990000')
            ->set('estudianteManual.sexo','Otro')
            ->set('estudianteManual.carrera','Ingeniería en Sistemas')
            ->set('estudianteManual.correo','manual@example.test')
            ->set('estudianteManual.horas_dedicadas',40)
            ->call('saveEstudianteManual')
            ->assertHasNoErrors()
            ->assertSet('estadoGuardado','guardado');
        $this->assertSame($before, Estudiante::count());
        $this->assertDatabaseHas('informe_final_estudiantes',['nombre'=>'Estudiante Manual','numero_cuenta'=>'20269990000','correo'=>'manual@example.test','origen'=>'MANUAL','estudiante_id'=>null]);
    }

    public function test_se_guardan_contrapartes(): void
    {
        [$user,$project]=$this->scenario(); $report=$this->initialize($project,$user);
        $this->assertSame('Patronato Pro Mejoramiento de Orocuina',$report->contrapartes->first()->nombre); $this->assertSame('sociedad_civil',$report->contrapartes->first()->tipo);
    }

    public function test_se_calcula_cumplimiento_de_resultados(): void
    {
        [$user,$project]=$this->scenario(); $report=$this->initialize($project,$user); $report->resultados->first()->update(['meta_numerica'=>4,'valor_alcanzado'=>3,'porcentaje_cumplimiento'=>75]);
        $this->assertSame(75.0,(float)$report->resultados()->avg('porcentaje_cumplimiento'));
    }

    public function test_se_registran_acciones_no_ejecutadas(): void
    {
        [$user,$project]=$this->scenario(); $report=$this->initialize($project,$user); $report->accionesNoEjecutadas()->create(['actividad_planificada'=>'Actividad pendiente','explicacion'=>'Clima','impacto'=>'medio']);
        $this->assertDatabaseHas('informe_final_acciones_no_ejecutadas',['actividad_planificada'=>'Actividad pendiente']);
    }

    public function test_se_registran_acciones_emergentes(): void
    {
        [$user,$project]=$this->scenario(); $report=$this->initialize($project,$user); $report->accionesEmergentes()->create(['actividad_realizada'=>'Soporte adicional','justificacion'=>'Necesidad comunitaria','horas'=>10]);
        $this->assertDatabaseHas('informe_final_acciones_emergentes',['actividad_realizada'=>'Soporte adicional']);
    }

    public function test_se_guardan_ods_estructurados(): void
    {
        [$user,$project]=$this->scenario(); $odsId=DB::table('ods')->where('nombre','6. Agua limpia y saneamiento')->value('id') ?: DB::table('ods')->insertGetId(['nombre'=>'6. Agua limpia y saneamiento','created_at'=>now(),'updated_at'=>now()]); $project->ods()->syncWithoutDetaching([$odsId]); $report=$this->initialize($project,$user);
        $this->assertDatabaseHas('informe_final_ods',['informe_final_proyecto_id'=>$report->id,'ods_id'=>$odsId,'nivel_contribucion'=>'directa']);
    }

    public function test_se_valida_muestra_comunitaria(): void
    {
        [$user,$project]=$this->scenario(); $this->livewireComponent($user,$project)->set('general.valoracion_total_beneficiarios',100)->set('general.valoracion_muestra',101)->call('guardarBorrador')->assertHasErrors('general.valoracion_muestra');
    }

    public function test_se_calculan_porcentajes_de_valoracion(): void
    {
        [$user,$project]=$this->scenario(); $this->livewireComponent($user,$project)->set('general.valoracion_muestra',100)->set('general.valoracion_excelente',75)->assertSet('porcentajesValoracion.excelente',75.0);
    }

    public function test_se_calcula_presupuesto_unah(): void
    {
        [$user,$project]=$this->scenario(); $report=$this->initialize($project,$user);
        $this->assertSame(212000.0,$report->total_unah);
    }

    public function test_se_calcula_aporte_contraparte(): void
    {
        [$user,$project]=$this->scenario(); $report=$this->initialize($project,$user); $report->presupuestoDetalles()->create(['fuente'=>'CONTRAPARTE','concepto'=>'Personal','cantidad'=>2,'costo_unitario'=>1000]); $report->load('presupuestoDetalles');
        $this->assertSame(2000.0,$report->total_contraparte);
    }

    public function test_se_calcula_ejecucion_total(): void
    {
        [$user,$project]=$this->scenario(); $report=$this->initialize($project,$user); $report->update(['aporte_beneficiarios'=>500,'otros_aportes'=>250]); $report->load('presupuestoDetalles');
        $this->assertSame(212750.0,$report->ejecucion_total);
    }

    public function test_se_guardan_anexos(): void
    {
        [$user,$project]=$this->scenario(); $report=$this->initialize($project,$user); $report->anexos()->create(['tipo'=>'manuales','descripcion'=>'Manual de usuario','enlace'=>'https://example.test/manual','orden'=>1]);
        $this->assertDatabaseHas('informe_final_anexos',['tipo'=>'manuales','descripcion'=>'Manual de usuario']);
    }

    public function test_se_marca_completo(): void
    {
        [$user,$project]=$this->scenario(); $component=$this->componentReadyForCompletion($user,$project); $component->call('marcarCompleto')->assertHasNoErrors()->assertSet('general.estado','COMPLETO');
        $this->assertDatabaseHas('informe_final_proyectos',['proyecto_id'=>$project->id,'estado'=>'COMPLETO']);
    }

    public function test_no_se_marca_completo_con_inconsistencias(): void
    {
        [$user,$project]=$this->scenario(); $component=$this->livewireComponent($user,$project)->set('general.fecha_cierre','2026-12-01')->set('general.transformacion_lograda','Transformación')->set('general.mecanismos_sostenibilidad','Comité local')->set('general.confirmacion_veracidad',true);
        $component->call('marcarCompleto')->assertHasErrors('beneficiarios'); $this->assertDatabaseHas('informe_final_proyectos',['proyecto_id'=>$project->id,'estado'=>'BORRADOR']);
    }

    public function test_se_genera_vista_previa_inf001(): void
    {
        [$user,$project]=$this->scenario(); $report=$this->initialize($project,$user); $this->actingAs($user)->get(route('informes-finales.inf-001.preview',$report))->assertOk()->assertSee('INF-001')->assertSee('Transformación digital');
    }

    public function test_formulario_web_muestra_los_ocho_pasos_y_acciones_finales(): void
    {
        [$user,$project]=$this->scenario();
        $component=$this->livewireComponent($user,$project);
        foreach (['Info General','Equipo','Participantes','Contrapartes','Resultados','Ejecución','Evaluación','Anexos'] as $label) {
            $component->assertSee($label);
        }
        $component->assertSee('Los datos generales se precargan desde el registro del proyecto.')
            ->set('currentStep',8)
            ->assertSee('Guardar borrador')
            ->assertSee('Validar informe')
            ->assertSee('Vista previa')
            ->assertSee('Descargar PDF');
    }

    public function test_pdf_usa_plantilla_institucional_exclusiva_en_horizontal(): void
    {
        $template=file_get_contents(resource_path('views/pdf/informes-finales/inf-001.blade.php'));
        $this->assertStringContainsString('letter landscape',$template);
        $this->assertStringContainsString('form-018-header.png',$template);
        $this->assertStringContainsString('form-018-watermark.png',$template);
        $this->assertStringContainsString('vinculacion.sociedad@unah.edu.hn',$template);
        $this->assertStringNotContainsString('INF-002',$template);
    }

    public function test_se_genera_pdf_inf001(): void
    {
        [$user,$project]=$this->scenario(); $report=$this->initialize($project,$user); $response=$this->actingAs($user)->get(route('informes-finales.inf-001.pdf',$report)); $response->assertOk(); $this->assertStringContainsString('application/pdf',(string)$response->headers->get('content-type'));
    }

    public function test_no_existe_ruta_o_boton_inf002(): void
    {
        $this->assertNull(Route::getRoutes()->getByName('informes-finales.inf-002')); $this->assertStringNotContainsString('INF-002',file_get_contents(resource_path('views/livewire/proyectos/informe-final/edit-informe-final-proyecto.blade.php')));
    }

    public function test_no_se_crean_firmas(): void
    {
        [$user,$project]=$this->scenario(); $before=DB::table('firma_proyecto')->count(); $this->initialize($project,$user); $this->assertSame($before,DB::table('firma_proyecto')->count());
    }

    public function test_no_se_cambia_el_flujo(): void
    {
        [$user,$project]=$this->scenario(); $flow=$project->flujo_aprobacion_id; $this->initialize($project,$user); $this->assertSame($flow,$project->fresh()->flujo_aprobacion_id);
    }

    public function test_no_se_notifican_revisores(): void
    {
        Notification::fake(); [$user,$project]=$this->scenario(); $this->initialize($project,$user); Notification::assertNothingSent();
    }

    public function test_usuario_no_autorizado_no_puede_editar(): void
    {
        [, $project]=$this->scenario(); $unauthorized=User::factory()->create(); Livewire::actingAs($unauthorized)->test(EditInformeFinalProyecto::class,['proyecto'=>$project])->assertStatus(403);
    }

    public function test_informe_queda_disponible_para_estadisticas(): void
    {
        [$user,$project]=$this->scenario(); $report=$this->initialize($project,$user); $report->update(['estado'=>'COMPLETO','fecha_cierre'=>'2026-12-01']);
        $this->assertTrue(InformeFinalProyecto::completos()->porAnioCierre(2026)->whereKey($report->id)->exists());
    }

    public function test_reapertura_no_sobrescribe_datos_finales(): void
    {
        [$user,$project]=$this->scenario(); $report=$this->initialize($project,$user); $report->update(['transformacion_lograda'=>'Texto final editado']); $project->update(['impacto_deseado'=>'Texto nuevo de ficha']); $again=$this->initialize($project,$user);
        $this->assertSame('Texto final editado',$again->transformacion_lograda);
    }

    private function livewireComponent(User $user, Proyecto $project)
    {
        return Livewire::actingAs($user)->test(EditInformeFinalProyecto::class,['proyecto'=>$project]);
    }

    private function componentReadyForCompletion(User $user, Proyecto $project)
    {
        return $this->livewireComponent($user,$project)
            ->set('beneficiarios.edad_19_25',3504)
            ->set('general.fecha_cierre','2026-12-01')
            ->set('general.transformacion_lograda','Gestión comunitaria digitalizada')
            ->set('general.mecanismos_sostenibilidad','Comité local de soporte')
            ->set('general.confirmacion_veracidad',true);
    }

    private function initialize(Proyecto $project, User $user): InformeFinalProyecto
    {
        $this->actingAs($user); return app(InformeFinalProyectoInitializer::class)->initialize($project,$user->id);
    }

    private function scenario(): array
    {
        $user=User::factory()->create(['name'=>'Dorian Adolfo Ordóñez Osorto','email'=>'dorian.orocuina.'.uniqid().'@example.test']);
        $role=Role::firstOrCreate(['name'=>'admin','guard_name'=>'web']); $user->assignRole($role);
        $employee=Empleado::create(['nombre_completo'=>'Dorian Adolfo Ordóñez Osorto','numero_empleado'=>(string)random_int(100000,999999),'celular'=>'99999999','sexo'=>'Masculino','user_id'=>$user->id,'tipo_empleado'=>'docente']);
        $type=VinculacionTipoAccion::firstOrCreate(['codigo'=>'DESARROLLO_LOCAL_REGIONAL'],['nombre'=>'Desarrollo local y regional','activo'=>true]);
        $project=Proyecto::create(['tipo_accion_id'=>$type->id,'codigo_proyecto'=>'PROY-ORO-'.uniqid(),'nombre_proyecto'=>'Transformación digital en patronatos y juntas de agua de Orocuina','fecha_inicio'=>'2026-01-12','fecha_finalizacion'=>'2026-11-30','objetivo_general'=>'Mejorar la gestión de patronatos y juntas de agua','poblacion_participante'=>3504,'hombres'=>1700,'mujeres'=>1804,'mestizos_hombres'=>1700,'mestizos_mujeres'=>1804,'impacto_deseado'=>'Aplicación informática para gestión comunitaria','total_aporte_institucional'=>200000]);
        EmpleadoProyecto::create(['empleado_id'=>$employee->id,'proyecto_id'=>$project->id,'rol'=>'Coordinador']);
        $now=now(); $campus=DB::table('campus')->insertGetId(['nombre_campus'=>'UNAH Choluteca '.uniqid(),'direccion'=>'Choluteca','telefono'=>'00000000','url'=>'https://unah.edu.hn','created_at'=>$now,'updated_at'=>$now]);
        $center=DB::table('centro_facultad')->insertGetId(['nombre'=>'UNAH Choluteca','es_facultad'=>false,'siglas'=>'CURLP','campus_id'=>$campus,'created_at'=>$now,'updated_at'=>$now]);
        DB::table('proyecto_centro_facultad')->insert(['proyecto_id'=>$project->id,'centro_facultad_id'=>$center,'created_at'=>$now,'updated_at'=>$now]);
        EntidadContraparte::create(['proyecto_id'=>$project->id,'nombre'=>'Patronato Pro Mejoramiento de Orocuina','tipo_entidad'=>'sociedad_civil','nombre_contacto'=>'Representante comunitario','cargo_contacto'=>'Presidencia','correo'=>'patronato@example.test','telefono'=>'00000000','descripcion_acuerdos'=>'Acompañar y validar la aplicación']);
        $objective=ObjetivoEspecifico::create(['proyecto_id'=>$project->id,'descripcion'=>'Fortalecer la gestión comunitaria','orden'=>1]);
        ResultadoEsperado::create(['objetivo_especifico_id'=>$objective->id,'nombre_resultado'=>'Aplicación informática disponible','nombre_indicador'=>'Una aplicación implementada','nombre_medio_verificacion'=>'Acta de entrega','plazo'=>'corto_plazo','orden'=>1]);
        Actividad::create(['proyecto_id'=>$project->id,'descripcion'=>'Levantamiento de requerimientos','fecha_inicio'=>'2026-01-12','fecha_finalizacion'=>'2026-02-15','horas'=>80]);
        AporteInstitucional::create(['proyecto_id'=>$project->id,'concepto'=>'horas_trabajo_docentes','unidad'=>'hra_profes','cantidad'=>1,'costo_unitario'=>200000,'costo_total'=>200000]);
        Presupuesto::create(['proyecto_id'=>$project->id,'aporte_contraparte'=>66792.44]);
        return [$user,$project];
    }
}
