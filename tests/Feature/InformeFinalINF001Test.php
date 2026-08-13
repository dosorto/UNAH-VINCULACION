<?php

namespace Tests\Feature;

use App\Livewire\Proyectos\InformeFinal\EditInformeFinalProyecto;
use App\Livewire\Docente\Proyectos\HistorialProyecto;
use App\Models\Asignatura;
use App\Models\Estudiante\Estudiante;
use App\Models\Estudiante\EstudianteProyecto;
use App\Models\InformeFinal\InformeFinalBeneficiario;
use App\Models\InformeFinal\InformeFinalProyecto;
use App\Models\Constancias\ConstanciaFinalizacionProyecto;
use App\Models\Personal\Empleado;
use App\Models\Personal\EmpleadoProyecto;
use App\Models\Presupuesto\Presupuesto;
use App\Models\Proyecto\Actividad;
use App\Models\Proyecto\AporteInstitucional;
use App\Models\Proyecto\CargoFirma;
use App\Models\Proyecto\EntidadContraparte;
use App\Models\Proyecto\EntidadContraparteProyecto;
use App\Models\Proyecto\FlujoAprobacion;
use App\Models\Proyecto\FlujoAprobacionEtapa;
use App\Models\Proyecto\InstrumenFormalizacion;
use App\Models\Proyecto\ObjetivoEspecifico;
use App\Models\Proyecto\Proyecto;
use App\Models\Proyecto\ResultadoEsperado;
use App\Models\Proyecto\TipoCargoFirma;
use App\Models\Proyecto\VinculacionTipoAccion;
use App\Models\Estado\TipoEstado;
use App\Models\User;
use App\Services\InformeFinal\InformeFinalProyectoInitializer;
use App\Services\InformeFinal\InformeFinalProyectoValidator;
use App\Services\InformeFinal\InformeFinalProyectoWorkflowService;
use App\Services\InformeFinal\InformeFinalPdfGenerator;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\UploadedFile;
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
        $this->assertSame('Fortalecimiento de la gestión comunitaria',$report->nombre_proyecto); $this->assertSame($project->codigo_proyecto,$report->numero_registro); $this->assertSame('2026-01-12',$report->fecha_inicio->format('Y-m-d')); $this->assertSame(266792.44,(float)$report->presupuesto_planificado);
    }

    public function test_el_numero_de_registro_no_usa_el_id_interno_y_solo_se_corrige_en_borrador(): void
    {
        [$user,$project]=$this->scenario();
        $report=$this->initialize($project,$user);
        $report->update(['numero_registro'=>'Proyecto #'.$project->id]);

        $corrected=$this->initialize($project,$user);
        $this->assertSame($project->codigo_proyecto,$corrected->numero_registro);

        $corrected->update(['estado'=>'COMPLETO','numero_registro'=>'Proyecto #'.$project->id]);
        $completed=$this->initialize($project,$user);
        $this->assertSame('Proyecto #'.$project->id,$completed->numero_registro);
    }

    public function test_el_primer_paso_muestra_nombre_registro_y_fecha_en_orden_oficial(): void
    {
        [$user,$project]=$this->scenario();
        $html=$this->livewireComponent($user,$project)->html();

        $namePosition=strpos($html,'Nombre del programa o proyecto');
        $registrationPosition=strpos($html,'Número de registro');
        $datePosition=strpos($html,'Fecha de registro');
        $this->assertNotFalse($namePosition);
        $this->assertNotFalse($registrationPosition);
        $this->assertNotFalse($datePosition);
        $this->assertLessThan($registrationPosition,$namePosition);
        $this->assertLessThan($datePosition,$registrationPosition);
    }

    public function test_el_numero_pendiente_no_se_reemplaza_por_el_id_interno(): void
    {
        [$user,$project]=$this->scenario();
        $project->update(['codigo_proyecto'=>null]);
        $report=$this->initialize($project,$user);

        $this->assertNull($report->numero_registro);
        $this->livewireComponent($user,$project)->assertSee('Pendiente de asignación');
    }

    public function test_el_pdf_usa_el_numero_de_registro_oficial(): void
    {
        [$user,$project]=$this->scenario();
        $report=$this->initialize($project,$user);
        $pdfView=view('pdf.informes-finales.inf-001', ['informe'=>$report])->render();

        $this->assertStringContainsString($project->codigo_proyecto,$pdfView);
        $this->assertStringNotContainsString('Proyecto #'.$project->id,$pdfView);
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

    public function test_se_precargan_resultados_de_mediano_y_largo_plazo_del_proyecto(): void
    {
        [$user,$project]=$this->scenario();
        $mediano=ResultadoEsperado::create(['proyecto_id'=>$project->id,'nombre_resultado'=>'Organizaciones fortalecidas','nombre_indicador'=>'Tres organizaciones fortalecidas','nombre_medio_verificacion'=>'Informe de seguimiento','plazo'=>'mediano_plazo','orden'=>1]);
        $largo=ResultadoEsperado::create(['proyecto_id'=>$project->id,'nombre_resultado'=>'Comunidad autogestionada','nombre_indicador'=>'Autogestión comunitaria sostenida','nombre_medio_verificacion'=>'Informe de impacto','plazo'=>'largo_plazo','orden'=>2]);

        $report=$this->initialize($project,$user);

        $resultadoMediano=$report->resultados->firstWhere('resultado_esperado_id',$mediano->id);
        $resultadoLargo=$report->resultados->firstWhere('resultado_esperado_id',$largo->id);

        $this->assertNotNull($resultadoMediano);
        $this->assertSame('Resultado de mediano plazo del proyecto',$resultadoMediano->objetivo_especifico);
        $this->assertSame('Organizaciones fortalecidas',$resultadoMediano->resultado_planificado);
        $this->assertSame('Tres organizaciones fortalecidas',$resultadoMediano->indicador_propuesto);

        $this->assertNotNull($resultadoLargo);
        $this->assertSame('Resultado de largo plazo del proyecto',$resultadoLargo->objetivo_especifico);
        $this->assertSame('Comunidad autogestionada',$resultadoLargo->resultado_planificado);
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
        $component->assertSet('actividades.0.participantes.1.nombre','Segundo Participante');
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

    public function test_los_campos_de_reflexion_heredados_son_informativos_y_no_pueden_ser_alterados(): void
    {
        [$user,$project]=$this->scenario();
        $origen = [
            'definicion_problema' => 'Problema vigente del proyecto',
            'impacto_deseado' => 'Transformación vigente del proyecto',
            'alineamiento_reforma' => 'Respuesta vigente a la reforma',
            'bibliografia' => 'Bibliografía vigente del proyecto',
        ];
        $project->update($origen);
        $project = $project->fresh();
        $report = $this->initialize($project,$user);

        $this->assertSame($origen['definicion_problema'],$report->problema_inicial);
        $this->assertSame($origen['impacto_deseado'],$report->transformacion_lograda);
        $this->assertSame($origen['alineamiento_reforma'],$report->respuesta_reforma_universitaria);
        $this->assertSame($origen['bibliografia'],$report->bibliografia);

        $component = $this->livewireComponent($user,$project)
            ->set('currentStep',6)
            ->assertSet('general.problema_inicial',$origen['definicion_problema'])
            ->assertSet('general.transformacion_lograda',$origen['impacto_deseado'])
            ->assertSet('general.respuesta_reforma_universitaria',$origen['alineamiento_reforma'])
            ->assertSet('general.bibliografia',$origen['bibliografia']);

        $component->set('general.problema_inicial','Valor manipulado desde el navegador')
            ->call('guardarBorrador')
            ->assertSet('general.problema_inicial',$origen['definicion_problema'])
            ->set('general.lecciones_aprendidas','Lección propia del informe')
            ->call('guardarBorrador')
            ->assertSet('general.lecciones_aprendidas','Lección propia del informe');

        $this->assertDatabaseHas('informe_final_proyectos',['id'=>$report->id,'problema_inicial'=>$origen['definicion_problema'],'lecciones_aprendidas'=>'Lección propia del informe']);
        $this->livewireComponent($user,$project)
            ->assertSet('general.problema_inicial',$origen['definicion_problema']);
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
        $otherGroup=$otherReport->gruposEstudiantes()->create(['tipo_participacion'=>'voluntariado','hombres_planificados'=>0,'mujeres_planificadas'=>0]);
        $otherStudent=$otherReport->estudiantes()->create(['informe_final_grupo_estudiante_id'=>$otherGroup->id,'nombre'=>'Fuera del proyecto','sexo'=>'Masculino','tipo_participacion'=>'voluntariado','cantidad'=>1]);
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

    public function test_repara_participacion_vacia_desde_el_proyecto_y_no_permite_modificar_el_rol(): void
    {
        [$user,$project]=$this->scenario(); $report=$this->initialize($project,$user);
        $miembro=$report->equipoDocente()->firstOrFail();
        $miembro->update(['tipo_participacion'=>'','es_coordinador'=>false]);

        $reabierto=$this->initialize($project,$user);
        $this->assertSame('Coordinador',$reabierto->equipoDocente()->firstOrFail()->tipo_participacion);
        $this->assertTrue($reabierto->equipoDocente()->firstOrFail()->es_coordinador);

        $this->livewireComponent($user,$project)
            ->set('equipo.0.tipo_participacion','Integrante alterado')
            ->set('equipo.0.es_coordinador',false)
            ->call('guardarBorrador')->assertHasNoErrors();

        $this->assertDatabaseHas('informe_final_equipo_docente',['id'=>$miembro->id,'tipo_participacion'=>'Coordinador','es_coordinador'=>true]);
    }

    public function test_se_guardan_beneficiarios(): void
    {
        [$user,$project]=$this->scenario(); $component=$this->livewireComponent($user,$project);
        $component->set('beneficiarios.edad_19_25',3504)->call('guardarBorrador')->assertHasNoErrors();
        $this->assertDatabaseHas('informe_final_beneficiarios',['edad_19_25'=>3504]);
    }

    public function test_beneficiarios_rechaza_decimales_y_ceros_a_la_izquierda(): void
    {
        [$user,$project]=$this->scenario(); $component=$this->livewireComponent($user,$project);

        $component->set('beneficiarios.hombres','03')->assertHasErrors('beneficiarios.hombres');
        $this->assertDatabaseMissing('informe_final_beneficiarios',['hombres'=>3]);

        $component->set('beneficiarios.mujeres','10.5')->assertHasErrors('beneficiarios.mujeres');
        $this->assertDatabaseMissing('informe_final_beneficiarios',['mujeres'=>10.5]);

        $component->set('beneficiarios.hombres','7')->assertHasNoErrors('beneficiarios.hombres');
        $this->assertDatabaseHas('informe_final_beneficiarios',['hombres'=>7]);
    }

    public function test_beneficiario_vacio_se_autoguarda_como_cero_sin_romper(): void
    {
        [$user,$project]=$this->scenario();
        $component=$this->livewireComponent($user,$project);

        $component->set('beneficiarios.edad_36_50','')->assertHasNoErrors('beneficiarios.edad_36_50');
        $this->assertDatabaseHas('informe_final_beneficiarios',['edad_36_50'=>0]);
    }

    public function test_beneficiarios_vacios_se_guardan_como_cero_al_avanzar_de_paso(): void
    {
        [$user,$project]=$this->scenario();
        $component=$this->livewireComponent($user,$project)
            ->set('beneficiarios.edad_36_50','')
            ->set('beneficiarios.edad_66_80','')
            ->call('siguiente')
            ->assertHasNoErrors()
            ->assertSet('currentStep',2);

        $this->assertDatabaseHas('informe_final_beneficiarios',['edad_36_50'=>0,'edad_66_80'=>0]);
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
        $grupo=$report->gruposEstudiantes()->create(['tipo_participacion'=>'pps_servicio_social','hombres_planificados'=>0,'mujeres_planificadas'=>0]);
        $report->estudiantes()->create(['informe_final_grupo_estudiante_id'=>$grupo->id,'nombre'=>'Estudiante prueba','sexo'=>'Masculino','tipo_participacion'=>'pps_servicio_social','cantidad'=>4,'horas_dedicadas'=>320]);
        $this->assertDatabaseHas('informe_final_estudiantes',['nombre'=>'Estudiante prueba','cantidad'=>4]);
    }

    public function test_se_guardan_voluntarios(): void
    {
        [$user,$project]=$this->scenario(); $report=$this->initialize($project,$user);
        $report->voluntarios()->create(['nombre'=>'Voluntario prueba','sexo'=>'Femenino','tipo'=>'egresado','horas_dedicadas'=>20]);
        $this->assertDatabaseHas('informe_final_voluntarios',['nombre'=>'Voluntario prueba']);
    }

    public function test_se_precargan_estudiantes_masculinos_y_femeninos_sin_inferir_el_sexo_por_el_nombre(): void
    {
        [$user,$project]=$this->scenario();
        $male=Estudiante::create(['nombre'=>'Andrea','apellido'=>'Prueba','cuenta'=>'M-'.uniqid(),'sexo'=>'Masculino','user_id'=>$user->id]);
        $female=Estudiante::create(['nombre'=>'Carlos','apellido'=>'Prueba','cuenta'=>'F-'.uniqid(),'sexo'=>'Femenino','user_id'=>$user->id]);
        foreach ([[$male,2,0],[$female,0,3]] as [$student,$men,$women]) {
            EstudianteProyecto::create(['estudiante_id'=>$student->id,'proyecto_id'=>$project->id,'tipo_participacion_estudiante'=>'Voluntariado','cantidad_estudiantes_hombres'=>$men,'cantidad_estudiantes_mujeres'=>$women,'total_estudiantes'=>$men+$women]);
        }
        $report=$this->initialize($project,$user);
        $this->assertDatabaseHas('informe_final_estudiantes',['informe_final_proyecto_id'=>$report->id,'nombre'=>'Andrea Prueba','sexo'=>'Masculino','cantidad'=>1]);
        $this->assertDatabaseHas('informe_final_estudiantes',['informe_final_proyecto_id'=>$report->id,'nombre'=>'Carlos Prueba','sexo'=>'Femenino','cantidad'=>1]);
        $this->livewireComponent($user,$project)->set('currentStep',3)
            ->assertSee('Hombres')
            ->assertSee('Mujeres');
    }

    public function test_se_precarga_sexo_de_voluntarios_y_reabrir_no_duplica_snapshots(): void
    {
        [$user,$project]=$this->scenario();
        $first=$this->initialize($project,$user);
        $component=$this->livewireComponent($user,$project)->call('openVoluntarioModal')
            ->set('voluntarioModal.nombre','Voluntaria Femenina')
            ->set('voluntarioModal.sexo','No permitido')
            ->set('voluntarioModal.identidad','0801-TEST')
            ->set('voluntarioModal.tipo','egresado')
            ->set('voluntarioModal.horas_dedicadas',12)
            ->call('saveVoluntarioModal')
            ->assertHasErrors('voluntarioModal.sexo')
            ->set('voluntarioModal.sexo','Femenino')
            ->call('saveVoluntarioModal')->assertHasNoErrors();
        $count=$first->voluntarios()->count();
        $this->assertDatabaseHas('informe_final_voluntarios',['informe_final_proyecto_id'=>$first->id,'nombre'=>'Voluntaria Femenina','sexo'=>'Femenino']);
        $second=$this->initialize($project,$user);
        $this->assertSame($count,$second->voluntarios()->count());
    }

    public function test_totales_de_participacion_muestran_unicamente_hombres_y_mujeres(): void
    {
        [$user,$project]=$this->scenario();
        $component=$this->livewireComponent($user,$project)
            ->set('estudiantes',[
                ['nombre'=>'A','sexo'=>'Masculino','tipo_participacion'=>'voluntariado','cantidad'=>2,'horas_dedicadas'=>0],
                ['nombre'=>'B','sexo'=>'Femenino','tipo_participacion'=>'voluntariado','cantidad'=>3,'horas_dedicadas'=>0],
            ])
            ->set('voluntarios',[
                ['nombre'=>'V1','sexo'=>'Masculino','tipo'=>'egresado','horas_dedicadas'=>0],
                ['nombre'=>'V2','sexo'=>'Femenino','tipo'=>'egresado','horas_dedicadas'=>0],
            ]);
        $totales=$component->get('totalesParticipacion');
        $this->assertSame(1,$totales['estudiantes_hombres']);
        $this->assertSame(1,$totales['estudiantes_mujeres']);
        $this->assertSame(1,$totales['voluntarios_hombres']);
        $this->assertSame(1,$totales['voluntarios_mujeres']);
        $this->assertSame([],array_filter(array_keys($totales),fn ($key) => str_contains($key,'especificar')));
    }

    public function test_carga_grupos_planificados_con_asignatura_periodo_y_conserva_la_planificacion_al_completar(): void
    {
        [$user,$project]=$this->scenario();
        $asignatura=Asignatura::create(['codigo'=>'AD-201-'.uniqid(),'nombre'=>'ADMON']);
        $plan=EstudianteProyecto::create([
            'estudiante_id'=>null,'proyecto_id'=>$project->id,'tipo_participacion_estudiante'=>'Practica Asignatura',
            'asignatura_id'=>$asignatura->id,'periodo_academico_id'=>'Primer período',
            'cantidad_estudiantes_hombres'=>3,'cantidad_estudiantes_mujeres'=>2,'total_estudiantes'=>5,
        ]);

        $report=$this->initialize($project,$user);
        $grupo=$report->gruposEstudiantes()->with('asignatura')->firstOrFail();
        $this->assertSame($plan->id,$grupo->estudiante_proyecto_id);
        $this->assertSame('practica_asignatura',$grupo->tipo_participacion);
        $this->assertSame(3,$grupo->hombres_planificados);
        $this->assertSame(2,$grupo->mujeres_planificadas);
        $this->assertSame('Primer período',$grupo->periodo_academico);
        $this->assertSame(0,$report->estudiantes()->count());
        $component=$this->livewireComponent($user,$project)->set('currentStep',3)
            ->assertSee($asignatura->codigo.' - ADMON')
            ->assertSee('Primer período')
            ->assertSee('openEstudianteModal(null, '.$grupo->id.')',false);
        $component->call('openEstudianteModal',null,$grupo->id)
            ->assertSet('grupoEstudianteSeleccionadoId',$grupo->id)
            ->assertSet('grupoEstudianteActivo.tipo_participacion','practica_asignatura')
            ->assertSet('grupoEstudianteActivo.periodo_academico','Primer período')
            ->assertSee($asignatura->codigo.' - ADMON')
            ->assertSee('type="hidden" wire:model="grupoEstudianteSeleccionadoId"',false);

        $plan->update(['cantidad_estudiantes_hombres'=>4,'total_estudiantes'=>6]);
        $this->assertSame(4,$this->initialize($project,$user)->gruposEstudiantes()->first()->hombres_planificados);
        $report->update(['estado'=>'COMPLETO']);
        $plan->update(['cantidad_estudiantes_hombres'=>5,'total_estudiantes'=>7]);
        $completed=$this->initialize($project,$user);
        $this->assertSame(4,$completed->gruposEstudiantes()->first()->hombres_planificados);
        $this->assertSame(1,$completed->gruposEstudiantes()->count());
    }

    public function test_cupos_por_sexo_y_resumen_planificado_registrado_pendiente(): void
    {
        [$user,$project]=$this->scenario();
        EstudianteProyecto::create(['estudiante_id'=>null,'proyecto_id'=>$project->id,'tipo_participacion_estudiante'=>'Voluntariado','cantidad_estudiantes_hombres'=>1,'cantidad_estudiantes_mujeres'=>1,'total_estudiantes'=>2]);
        $report=$this->initialize($project,$user);
        $grupo=$report->gruposEstudiantes()->firstOrFail();
        $component=$this->livewireComponent($user,$project);

        foreach ([['Cuenta Uno','20260001001','Masculino'],['Cuenta Dos','20260001002','Femenino']] as [$nombre,$cuenta,$sexo]) {
            $component->call('openEstudianteModal',null,$grupo->id)
                ->set('estudianteManual.nombres',$nombre)
                ->set('estudianteManual.numero_cuenta',$cuenta)
                ->set('estudianteManual.sexo',$sexo)
                ->call('saveEstudianteManual')
                ->assertHasNoErrors();
        }
        $resumen=$component->get('resumenPlanificacionEstudiantes');
        $this->assertSame(['hombres'=>1,'mujeres'=>1,'total'=>2],$resumen['planificados']);
        $this->assertSame(['hombres'=>1,'mujeres'=>1,'total'=>2],$resumen['registrados']);
        $this->assertSame(['hombres'=>0,'mujeres'=>0,'total'=>0],$resumen['pendientes']);

        foreach ([['Cuenta Tres','20260001003','Masculino','hombres'],['Cuenta Cuatro','20260001004','Femenino','mujeres']] as [$nombre,$cuenta,$sexo,$plural]) {
            $component->call('openEstudianteModal',null,$grupo->id)
                ->set('estudianteManual.nombres',$nombre)
                ->set('estudianteManual.numero_cuenta',$cuenta)
                ->set('estudianteManual.sexo',$sexo)
                ->call('saveEstudianteManual')
                ->assertHasErrors('estudianteManual.sexo')
                ->assertSee('Ya se registró la cantidad máxima de '.$plural.' planificada para este grupo.');
        }
        $this->assertSame(2,$report->estudiantes()->count());
    }

    public function test_observacion_es_obligatoria_por_cada_grupo_con_hombres_o_mujeres_pendientes_y_se_autoguarda(): void
    {
        [$user,$project]=$this->scenario();
        EstudianteProyecto::create(['estudiante_id'=>null,'proyecto_id'=>$project->id,'tipo_participacion_estudiante'=>'Voluntariado','cantidad_estudiantes_hombres'=>1,'cantidad_estudiantes_mujeres'=>0,'total_estudiantes'=>1]);
        EstudianteProyecto::create(['estudiante_id'=>null,'proyecto_id'=>$project->id,'tipo_participacion_estudiante'=>'Servicio Social o PPS','cantidad_estudiantes_hombres'=>0,'cantidad_estudiantes_mujeres'=>1,'total_estudiantes'=>1]);
        $report=$this->initialize($project,$user);
        $component=$this->livewireComponent($user,$project)->set('currentStep',3)
            ->assertSee('Observación por estudiantes no incorporados')
            ->call('siguiente')
            ->assertHasErrors([
                'gruposEstudiantes.0.observacion_no_cumplimiento',
                'gruposEstudiantes.1.observacion_no_cumplimiento',
            ]);
        $component->assertSee('Debe explicar por qué no se agregó la totalidad de estudiantes planificados.');

        $component->set('gruposEstudiantes.0.observacion_no_cumplimiento','Los estudiantes cancelaron su participación planificada.')
            ->set('gruposEstudiantes.1.observacion_no_cumplimiento','No hubo matrícula disponible para el período académico.');
        $grupos=$report->gruposEstudiantes()->orderBy('id')->get();
        $this->assertSame('Los estudiantes cancelaron su participación planificada.',$grupos[0]->fresh()->observacion_no_cumplimiento);
        $this->assertSame('No hubo matrícula disponible para el período académico.',$grupos[1]->fresh()->observacion_no_cumplimiento);
        $component->call('siguiente')->assertHasNoErrors()->assertSet('currentStep',4);

        $html=view('pdf.informes-finales.inf-001',['informe'=>$report->fresh()->load('gruposEstudiantes.asignatura','estudiantes')])->render();
        $this->assertStringContainsString('Observación complementaria',$html);
        $this->assertStringContainsString('Los estudiantes cancelaron su participación planificada.',$html);
    }

    public function test_observacion_de_estudiantes_se_oculta_al_completar_planificacion_y_no_se_exige_con_ceros(): void
    {
        [$user,$project]=$this->scenario();
        $plan=EstudianteProyecto::create(['estudiante_id'=>null,'proyecto_id'=>$project->id,'tipo_participacion_estudiante'=>'Voluntariado','cantidad_estudiantes_hombres'=>1,'cantidad_estudiantes_mujeres'=>1,'total_estudiantes'=>2]);
        EstudianteProyecto::create(['estudiante_id'=>null,'proyecto_id'=>$project->id,'tipo_participacion_estudiante'=>'Practica Asignatura','cantidad_estudiantes_hombres'=>0,'cantidad_estudiantes_mujeres'=>0,'total_estudiantes'=>0]);
        $report=$this->initialize($project,$user); $grupo=$report->gruposEstudiantes()->where('estudiante_proyecto_id',$plan->id)->firstOrFail();
        $report->estudiantes()->create(['informe_final_grupo_estudiante_id'=>$grupo->id,'nombre'=>'Estudiante hombre','sexo'=>'Masculino','tipo_participacion'=>'voluntariado']);
        $report->estudiantes()->create(['informe_final_grupo_estudiante_id'=>$grupo->id,'nombre'=>'Estudiante mujer','sexo'=>'Femenino','tipo_participacion'=>'voluntariado']);

        $this->livewireComponent($user,$project)->set('currentStep',3)
            ->assertDontSee('Observación por estudiantes no incorporados')
            ->call('siguiente')->assertHasNoErrors()->assertSet('currentStep',4);
    }

    public function test_sin_planificacion_no_bloquea_y_permite_iniciar_registro_real(): void
    {
        [$user,$project]=$this->scenario();
        $component=$this->livewireComponent($user,$project)->set('currentStep',3)
            ->assertSee('Esto no bloquea el registro de la ejecución real')
            ->set('tipoParticipacionSinPlanificacion','pps_servicio_social')
            ->call('openEstudianteSinPlanificacionModal')
            ->assertSet('showEstudianteModal',true)
            ->assertSet('grupoEstudianteActivo.tipo_participacion','pps_servicio_social');
        $component->call('closeEstudianteModal')->call('siguiente')->assertHasNoErrors()->assertSet('currentStep',4);
    }

    public function test_observacion_vuelve_a_ser_obligatoria_al_quitar_estudiante_y_deja_de_serlo_al_restaurar(): void
    {
        [$user,$project]=$this->scenario();
        $student=Estudiante::create(['nombre'=>'Recalculado','apellido'=>'Participante','cuenta'=>'REC-'.uniqid(),'sexo'=>'Masculino','user_id'=>$user->id]);
        EstudianteProyecto::create(['estudiante_id'=>$student->id,'proyecto_id'=>$project->id,'tipo_participacion_estudiante'=>'Voluntariado','cantidad_estudiantes_hombres'=>1,'cantidad_estudiantes_mujeres'=>0,'total_estudiantes'=>1]);
        $this->initialize($project,$user);
        $component=$this->livewireComponent($user,$project)->set('currentStep',3)->assertDontSee('Observación por estudiantes no incorporados');
        $component->call('openNoParticipacionModal','estudiante',0)
            ->set('observacionNoParticipacion','Se retiró antes de iniciar las actividades.')
            ->call('confirmarNoParticipacion')
            ->assertSee('Observación por estudiantes no incorporados')
            ->call('siguiente')->assertHasErrors('gruposEstudiantes.0.observacion_no_cumplimiento');
        $component->call('restaurarParticipante','estudiante',0)
            ->assertDontSee('Observación por estudiantes no incorporados')
            ->call('siguiente')->assertHasNoErrors()->assertSet('currentStep',4);
    }

    public function test_voluntarios_tienen_observacion_opcional_autoguardada_y_nota_en_pdf(): void
    {
        [$user,$project]=$this->scenario(); $report=$this->initialize($project,$user);
        $texto='No se contó con participación voluntaria durante la ejecución.';
        $this->livewireComponent($user,$project)->set('currentStep',3)
            ->assertSee('Observación por voluntarios no incorporados')
            ->assertSee('planificación desglosada de voluntarios')
            ->set('general.observacion_voluntarios_no_incorporados',$texto)
            ->assertHasNoErrors();
        $this->assertDatabaseHas('informe_final_proyectos',['id'=>$report->id,'observacion_voluntarios_no_incorporados'=>$texto]);
        $html=view('pdf.informes-finales.inf-001',['informe'=>$report->fresh()])->render();
        $this->assertStringContainsString('Observación complementaria sobre voluntarios',$html);
        $this->assertStringContainsString($texto,$html);
    }

    public function test_pdf_calcula_participacion_con_las_dos_opciones_permitidas(): void
    {
        [$user,$project]=$this->scenario();
        $asignatura=Asignatura::create(['codigo'=>'PDF-101-'.uniqid(),'nombre'=>'Asignatura PDF']);
        EstudianteProyecto::create(['estudiante_id'=>null,'proyecto_id'=>$project->id,'tipo_participacion_estudiante'=>'Practica Asignatura','asignatura_id'=>$asignatura->id,'periodo_academico_id'=>'Segundo período','cantidad_estudiantes_hombres'=>2,'cantidad_estudiantes_mujeres'=>1,'total_estudiantes'=>3]);
        $report=$this->initialize($project,$user);
        $grupo=$report->gruposEstudiantes()->firstOrFail();
        $report->estudiantes()->delete();
        $report->voluntarios()->delete();
        foreach (['Masculino','Masculino','Femenino'] as $index=>$sexo) {
            $report->estudiantes()->create(['informe_final_grupo_estudiante_id'=>$grupo->id,'nombre'=>'Estudiante '.$index,'sexo'=>$sexo,'tipo_participacion'=>'practica_asignatura','cantidad'=>1,'horas_dedicadas'=>0]);
        }
        foreach (['Masculino','Femenino','Femenino'] as $index=>$sexo) {
            $report->voluntarios()->create(['nombre'=>'Voluntario '.$index,'sexo'=>$sexo,'tipo'=>'egresado','horas_dedicadas'=>0]);
        }

        $html=view('pdf.informes-finales.inf-001',['informe'=>$report->fresh()])->render();

        $this->assertMatchesRegularExpression('/Participación de estudiantes.*?Expresado en números<\/td><td>2<\/td><td>1<\/td>/s',$html);
        $this->assertMatchesRegularExpression('/Participación de voluntarios.*?Expresado en números<\/td><td>1<\/td><td>2<\/td>/s',$html);
        $this->assertStringContainsString($asignatura->codigo.' - Asignatura PDF · Segundo período',$html);
    }

    public function test_estudiantes_modal_busca_agrega_edita_y_marca_no_participacion_sin_borrar(): void
    {
        [$user,$project]=$this->scenario();
        $student=Estudiante::create(['nombre'=>'Ana','apellido'=>'Modal','cuenta'=>'20260001234','sexo'=>'Femenino','user_id'=>$user->id]);
        EstudianteProyecto::create(['estudiante_id'=>null,'proyecto_id'=>$project->id,'tipo_participacion_estudiante'=>'Voluntariado','cantidad_estudiantes_hombres'=>0,'cantidad_estudiantes_mujeres'=>1,'total_estudiantes'=>1]);
        EstudianteProyecto::create(['estudiante_id'=>null,'proyecto_id'=>$project->id,'tipo_participacion_estudiante'=>'Servicio Social o PPS','cantidad_estudiantes_hombres'=>0,'cantidad_estudiantes_mujeres'=>1,'total_estudiantes'=>1]);
        $report=$this->initialize($project,$user);
        $grupo=$report->gruposEstudiantes()->where('tipo_participacion','voluntariado')->firstOrFail();
        $otroGrupo=$report->gruposEstudiantes()->where('tipo_participacion','pps_servicio_social')->firstOrFail();
        $component=$this->livewireComponent($user,$project)->set('currentStep',3)
            ->call('openEstudianteModal',null,$grupo->id)
            ->assertSet('showEstudianteModal',true)
            ->assertSee('Agregar estudiante participante')
            ->assertSee('Buscar estudiante')
            ->set('estudianteBusquedaCuenta','20260001234')
            ->call('buscarEstudiante')
            ->assertSet('estudianteEncontrado.nombre','Ana Modal')
            ->assertSet('estudianteEncontrado.sexo','Femenino')
            ->set('estudianteModal.horas_dedicadas',120)
            ->call('saveEstudianteModal')
            ->assertHasNoErrors()
            ->assertSet('showEstudianteModal',false);
        $this->assertDatabaseHas('informe_final_estudiantes',['informe_final_proyecto_id'=>$report->id,'informe_final_grupo_estudiante_id'=>$grupo->id,'estudiante_id'=>$student->id,'nombre'=>'Ana Modal','sexo'=>'Femenino','numero_cuenta'=>'20260001234','tipo_participacion'=>'voluntariado','horas_dedicadas'=>120,'cantidad'=>1]);
        $rows=$component->get('estudiantes');
        $index=collect($rows)->search(fn ($row) => (int) ($row['estudiante_id'] ?? 0) === $student->id);
        $component->call('openEstudianteModal',$index)
            ->set('grupoEstudianteSeleccionadoId',$otroGrupo->id)
            ->set('estudianteModal.horas_dedicadas',140)
            ->call('saveEstudianteModal')->assertHasNoErrors();
        $this->assertDatabaseHas('informe_final_estudiantes',['informe_final_proyecto_id'=>$report->id,'informe_final_grupo_estudiante_id'=>$grupo->id,'estudiante_id'=>$student->id,'tipo_participacion'=>'voluntariado','horas_dedicadas'=>140]);
        $this->assertDatabaseMissing('informe_final_estudiantes',['informe_final_proyecto_id'=>$report->id,'informe_final_grupo_estudiante_id'=>$otroGrupo->id,'estudiante_id'=>$student->id]);
        $component->call('openEstudianteModal',null,$grupo->id)->set('estudianteBusquedaCuenta','20260001234')->call('buscarEstudiante')->assertHasErrors('estudianteBusquedaCuenta');
        $component=$this->livewireComponent($user,$project);
        $rows=$component->get('estudiantes');
        $index=collect($rows)->search(fn ($row) => (int) ($row['estudiante_id'] ?? 0) === $student->id);
        $component->call('openNoParticipacionModal','estudiante',$index)
            ->set('observacionNoParticipacion','No completó las horas planificadas.')
            ->call('confirmarNoParticipacion')->assertHasNoErrors();
        $this->assertDatabaseHas('informe_final_estudiantes',['informe_final_proyecto_id'=>$report->id,'estudiante_id'=>$student->id,'estado_participacion'=>'no_participo','observacion_no_participacion'=>'No completó las horas planificadas.']);
        $this->assertDatabaseHas('estudiante_proyecto',['proyecto_id'=>$project->id,'estudiante_id'=>null]);
    }

    public function test_equipo_se_marca_con_observacion_obligatoria_conserva_planificacion_y_se_restaura(): void
    {
        [$user,$project]=$this->scenario(); $report=$this->initialize($project,$user);
        $component=$this->livewireComponent($user,$project)->set('currentStep',2);
        $html=$component->html();
        $this->assertStringNotContainsString('wire:model="equipo.0.tipo_participacion"',$html);
        $component->call('openNoParticipacionModal','equipo',0)->call('confirmarNoParticipacion')->assertHasErrors('observacionNoParticipacion');
        $component->set('observacionNoParticipacion','Se retiró antes de iniciar las actividades.')
            ->call('confirmarNoParticipacion')->assertHasNoErrors();
        $this->assertDatabaseHas('informe_final_equipo_docente',['informe_final_proyecto_id'=>$report->id,'estado_participacion'=>'no_participo']);
        $this->assertDatabaseHas('empleado_proyecto',['proyecto_id'=>$project->id,'rol'=>'Coordinador']);
        $component->call('restaurarParticipante','equipo',0);
        $this->assertDatabaseHas('informe_final_equipo_docente',['informe_final_proyecto_id'=>$report->id,'estado_participacion'=>'activo','observacion_no_participacion'=>null]);
    }

    public function test_participantes_inactivos_no_cuentan_ni_aparecen_en_pdf(): void
    {
        [$user,$project]=$this->scenario(); $report=$this->initialize($project,$user);
        $grupo=$report->gruposEstudiantes()->create(['tipo_participacion'=>'voluntariado','hombres_planificados'=>1,'mujeres_planificadas'=>0,'orden'=>99]);
        $report->estudiantes()->create(['informe_final_grupo_estudiante_id'=>$grupo->id,'nombre'=>'Estudiante excluido','sexo'=>'Masculino','tipo_participacion'=>'voluntariado','estado_participacion'=>'no_participo','observacion_no_participacion'=>'No completó las horas planificadas.']);
        $report->voluntarios()->create(['nombre'=>'Voluntario excluido','sexo'=>'Femenino','tipo'=>'egresado','estado_participacion'=>'retirado','observacion_no_participacion'=>'Se retiró antes de iniciar actividades.']);
        $component=$this->livewireComponent($user,$project);
        $component->assertSet('totalesParticipacion.estudiantes',0)->assertSet('totalesParticipacion.voluntarios',0);
        $html=view('pdf.informes-finales.inf-001',['informe'=>$report->fresh()])->render();
        $this->assertStringNotContainsString('Estudiante excluido',$html);
        $this->assertStringNotContainsString('Voluntario excluido',$html);
    }

    public function test_instrumento_de_contraparte_se_precarga_en_anexos_sin_duplicar_archivo(): void
    {
        [$user,$project]=$this->scenario();
        $pivot=$project->entidad_contraparte_proyecto()->firstOrFail();
        $instrumento=InstrumenFormalizacion::create(['entidad_contraparte_id'=>$pivot->id,'tipo_documento'=>'carta_intenciones','documento_url'=>'instrumentos/carta-intenciones.pdf','nombre_archivo'=>'carta-intenciones.pdf']);
        $report=$this->initialize($project,$user);
        $this->assertDatabaseHas('informe_final_anexos',['informe_final_proyecto_id'=>$report->id,'instrumento_formalizacion_id'=>$instrumento->id,'categoria'=>'instrumento_contraparte','archivo'=>'instrumentos/carta-intenciones.pdf','origen'=>'PROYECTO']);
        $this->initialize($project,$user);
        $this->assertSame(1,$report->anexos()->where('instrumento_formalizacion_id',$instrumento->id)->count());
        $this->livewireComponent($user,$project)->set('currentStep',4)->assertSee('Carta de intenciones con la UNAH')->assertSee('Disponible')->assertSee('carta-intenciones.pdf');
    }

    public function test_fotografias_validan_formato_tamano_limite_muestran_miniatura_y_se_pueden_quitar(): void
    {
        Storage::fake('public');
        [$user,$project]=$this->scenario(); $report=$this->initialize($project,$user);
        $component=$this->livewireComponent($user,$project)->set('currentStep',8)
            ->set('fotografiasTemporales',[
                UploadedFile::fake()->image('evidencia.jpg',800,600)->size(500),
                UploadedFile::fake()->image('segunda.png',800,600)->size(600),
            ])
            ->assertHasNoErrors();
        $foto=$report->anexos()->where('categoria','fotografia')->firstOrFail();
        Storage::disk('public')->assertExists($foto->archivo);
        $component->assertSee('evidencia.jpg');
        $component->assertSee('segunda.png');
        $this->assertStringContainsString('object-cover',$component->html());
        $component->set('fotografiasTemporales',[UploadedFile::fake()->create('evidencia.pdf',100,'application/pdf')])->assertHasErrors('fotografiasTemporales.0');
        $component->set('fotografiasTemporales',[UploadedFile::fake()->image('grande.jpg')->size(10241)])->assertHasErrors('fotografiasTemporales.0');
        $component->set('fotografiasTemporales',[
            UploadedFile::fake()->image('valida.webp',800,600),
            UploadedFile::fake()->create('invalida.txt',20,'text/plain'),
        ])->assertHasErrors('fotografiasTemporales.1');
        $this->assertDatabaseHas('informe_final_anexos',['informe_final_proyecto_id'=>$report->id,'categoria'=>'fotografia','nombre_archivo'=>'valida.webp']);
        $report->anexos()->where('categoria','fotografia')->delete();
        foreach (range(1,20) as $i) $report->anexos()->create(['tipo'=>'fotografias','categoria'=>'fotografia','archivo'=>'fotos/'.$i.'.jpg','nombre_archivo'=>$i.'.jpg','origen'=>'INFORME']);
        $component=$this->livewireComponent($user,$project)->set('currentStep',8)
            ->set('fotografiasTemporales',[UploadedFile::fake()->image('extra.jpg')])->assertHasErrors('fotografiasTemporales');
        $id=$report->anexos()->where('categoria','fotografia')->firstOrFail()->id;
        $component->call('quitarFotografia',$id);
        $this->assertDatabaseMissing('informe_final_anexos',['id'=>$id]);
    }

    public function test_zona_de_fotografias_implementa_drag_drop_progreso_previsualizacion_y_accesibilidad(): void
    {
        $formulario=file_get_contents(resource_path('views/livewire/proyectos/informe-final/edit-informe-final-proyecto.blade.php'));
        $dropzone=file_get_contents(resource_path('views/components/forms/image-dropzone.blade.php'));
        $pdf=file_get_contents(resource_path('views/proyectos/informe-final/partials/inf-001-document.blade.php'));

        $this->assertStringContainsString('<x-forms.image-dropzone model="fotografiasTemporales"',$formulario);
        $this->assertStringNotContainsString('No hay fotografías adjuntas',$formulario);
        foreach (['multiple','accept="{{ $accept }}"','x-on:dragenter.prevent','x-on:drop.prevent','Suelta las fotografías para cargarlas','Arrastra y suelta las fotografías aquí','o haz clic para seleccionarlas','wire:model="{{ $model }}"','livewire-upload-progress','role="progressbar"','previews','object-cover','tabindex="0"','x-on:keydown.enter.prevent','aria-live="assertive"'] as $fragmento) {
            $this->assertStringContainsString($fragmento,$dropzone);
        }
        foreach (['.jpg','.jpeg','.png','.webp'] as $extension) $this->assertStringContainsString($extension,$dropzone);
        $this->assertStringNotContainsString('fotografiasTemporales',$pdf);
        $this->assertStringContainsString("where('categoria', 'fotografia')",$pdf);
    }

    public function test_pdf_lista_documentos_de_contraparte_y_fotografias_con_miniaturas(): void
    {
        [$user,$project]=$this->scenario(); $report=$this->initialize($project,$user); $contraparte=$report->contrapartes()->firstOrFail();
        $report->anexos()->create(['informe_final_contraparte_id'=>$contraparte->id,'tipo'=>'otros','categoria'=>'instrumento_contraparte','descripcion'=>'Convenio marco','archivo'=>'instrumentos/convenio.pdf','nombre_archivo'=>'convenio.pdf','origen'=>'PROYECTO']);
        $report->anexos()->create(['tipo'=>'fotografias','categoria'=>'fotografia','descripcion'=>'Taller comunitario','archivo'=>'fotografias/taller.jpg','nombre_archivo'=>'taller.jpg','origen'=>'INFORME']);
        $html=view('pdf.informes-finales.inf-001',['informe'=>$report->fresh()->load('anexos.contraparte')])->render();
        $this->assertStringContainsString('Instrumentos de formalización y respaldos de contraparte',$html);
        $this->assertStringContainsString('convenio.pdf',$html);
        $this->assertStringContainsString('Fotografías del proyecto',$html);
        $this->assertStringContainsString('Taller comunitario',$html);
        $this->assertStringContainsString('inf-photo-card',$html);
    }

    public function test_vista_previa_usa_rutas_relativas_para_fotografias_sin_depender_de_app_url(): void
    {
        Storage::fake('public');
        [$user,$project]=$this->scenario(); $report=$this->initialize($project,$user);
        Storage::disk('public')->put('informes-finales/fotos/taller.jpg','contenido');
        $report->anexos()->create(['tipo'=>'fotografias','categoria'=>'fotografia','descripcion'=>'Taller comunitario','archivo'=>'informes-finales/fotos/taller.jpg','nombre_archivo'=>'taller.jpg','origen'=>'INFORME']);

        $html=$this->actingAs($user)->get(route('informes-finales.inf-001.preview',$report))->assertOk()->getContent();

        $this->assertStringContainsString('src="/storage/informes-finales/fotos/taller.jpg"',$html);
    }

    public function test_estudiantes_se_muestran_por_grupo_sin_selector_editable_de_participacion(): void
    {
        [$user,$project]=$this->scenario();
        foreach ([['Uno','Masculino','Practica Profesional'],['Dos','Femenino','Servicio Social o PPS'],['Tres','Masculino','Voluntariado']] as $i=>[$name,$sex,$type]) {
            $student=Estudiante::create(['nombre'=>$name,'apellido'=>'Individual','cuenta'=>'IND-'.$i.'-'.uniqid(),'sexo'=>$sex,'user_id'=>$user->id]);
            EstudianteProyecto::create(['estudiante_id'=>$student->id,'proyecto_id'=>$project->id,'tipo_participacion_estudiante'=>$type,'cantidad_estudiantes_hombres'=>$sex==='Masculino'?1:0,'cantidad_estudiantes_mujeres'=>$sex==='Femenino'?1:0,'total_estudiantes'=>1]);
        }
        $component=$this->livewireComponent($user,$project)->set('currentStep',3);
        $component->assertSee('Grupos de estudiantes planificados')->assertSee('Planificados')->assertSee('Registrados')->assertSee('Pendientes');
        $this->assertStringNotContainsString('estudiantes.0.tipo_participacion',$component->html());
        $component->assertSet('totalesParticipacion.estudiantes_practica',1)
            ->assertSet('totalesParticipacion.estudiantes_pps',1)
            ->assertSet('totalesParticipacion.estudiantes_voluntariado',1);
    }

    public function test_busqueda_institucional_asocia_el_estudiante_al_grupo_seleccionado(): void
    {
        [$user,$project]=$this->scenario();
        $student=Estudiante::create(['nombre'=>'Ana','apellido'=>'Institucional','cuenta'=>'20260009992','sexo'=>'Masculino','user_id'=>$user->id]);
        EstudianteProyecto::create(['estudiante_id'=>null,'proyecto_id'=>$project->id,'tipo_participacion_estudiante'=>'Voluntariado','cantidad_estudiantes_hombres'=>1,'cantidad_estudiantes_mujeres'=>0,'total_estudiantes'=>1]);
        $report=$this->initialize($project,$user);
        $grupo=$report->gruposEstudiantes()->firstOrFail();

        $this->livewireComponent($user,$project)
            ->call('openEstudianteModal',null,$grupo->id)
            ->set('estudianteBusquedaCuenta',$student->cuenta)
            ->call('buscarEstudiante')
            ->assertSet('estudianteEncontrado.estudiante_id',$student->id)
            ->set('estudianteModal.horas_dedicadas',10)
            ->call('saveEstudianteModal')
            ->assertHasNoErrors();
        $this->assertDatabaseHas('informe_final_estudiantes',['informe_final_grupo_estudiante_id'=>$grupo->id,'estudiante_id'=>$student->id]);
    }

    public function test_no_permite_crear_estudiantes_sin_un_grupo_del_mismo_informe(): void
    {
        [$user,$project]=$this->scenario();
        $report=$this->initialize($project,$user);

        try {
            $report->estudiantes()->create([
                'nombre'=>'Estudiante huérfano',
                'sexo'=>'Masculino',
                'tipo_participacion'=>'voluntariado',
            ]);
            $this->fail('Se permitió crear un estudiante sin grupo.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('informe_final_grupo_estudiante_id',$exception->errors());
        }

        [, $otherProject]=$this->scenario();
        $otherReport=$this->initialize($otherProject,$user);
        $otherGroup=$otherReport->gruposEstudiantes()->create(['tipo_participacion'=>'voluntariado','hombres_planificados'=>0,'mujeres_planificadas'=>0]);

        $this->expectException(ValidationException::class);
        $report->estudiantes()->create([
            'informe_final_grupo_estudiante_id'=>$otherGroup->id,
            'nombre'=>'Estudiante en grupo ajeno',
            'sexo'=>'Femenino',
            'tipo_participacion'=>'voluntariado',
        ]);
    }

    public function test_al_cargar_un_informe_repara_estudiantes_historicos_sin_grupo(): void
    {
        [$user,$project]=$this->scenario();
        EstudianteProyecto::create(['estudiante_id'=>null,'proyecto_id'=>$project->id,'tipo_participacion_estudiante'=>'Voluntariado','cantidad_estudiantes_hombres'=>1,'cantidad_estudiantes_mujeres'=>0,'total_estudiantes'=>1]);
        $report=$this->initialize($project,$user);
        $studentId=DB::table('informe_final_estudiantes')->insertGetId([
            'informe_final_proyecto_id'=>$report->id,
            'informe_final_grupo_estudiante_id'=>null,
            'nombre'=>'Registro histórico',
            'sexo'=>'Masculino',
            'tipo_participacion'=>'voluntariado',
            'horas_dedicadas'=>0,
            'cantidad'=>1,
            'created_at'=>now(),
            'updated_at'=>now(),
        ]);

        $reopened=$this->initialize($project,$user);
        $student=$reopened->estudiantes()->findOrFail($studentId);

        $this->assertNotNull($student->informe_final_grupo_estudiante_id);
        $this->assertTrue($reopened->gruposEstudiantes()->whereKey($student->informe_final_grupo_estudiante_id)->exists());
        $this->assertSame('voluntariado',$student->tipo_participacion);
        $this->livewireComponent($user,$project)->call('guardarBorrador')->assertHasNoErrors();
    }

    public function test_modal_estudiante_valida_cuenta_y_guarda_registro_manual_sin_crear_maestro(): void
    {
        [$user,$project]=$this->scenario();
        $before=Estudiante::count();
        EstudianteProyecto::create(['estudiante_id'=>null,'proyecto_id'=>$project->id,'tipo_participacion_estudiante'=>'Servicio Social o PPS','cantidad_estudiantes_hombres'=>1,'cantidad_estudiantes_mujeres'=>0,'total_estudiantes'=>1]);
        $report=$this->initialize($project,$user);
        $grupo=$report->gruposEstudiantes()->firstOrFail();
        $component=$this->livewireComponent($user,$project)->call('openEstudianteModal',null,$grupo->id);
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
            ->set('estudianteManual.sexo','No permitido')
            ->call('saveEstudianteManual')
            ->assertHasErrors('estudianteManual.sexo')
            ->set('estudianteManual.sexo','Masculino')
            ->set('estudianteManual.carrera','Ingeniería en Sistemas')
            ->set('estudianteManual.correo','manual@example.test')
            ->set('estudianteManual.horas_dedicadas',40)
            ->call('saveEstudianteManual')
            ->assertHasNoErrors()
            ->assertSet('estadoGuardado','guardado');
        $this->assertSame($before, Estudiante::count());
        $this->assertDatabaseHas('informe_final_estudiantes',['informe_final_grupo_estudiante_id'=>$grupo->id,'nombre'=>'Estudiante Manual','numero_cuenta'=>'20269990000','sexo'=>'Masculino','tipo_participacion'=>'pps_servicio_social','correo'=>'manual@example.test','origen'=>'MANUAL','estudiante_id'=>null]);
    }

    public function test_select_sexo_tiene_opcion_vacia_para_obligar_seleccion_manual(): void
    {
        [$user,$project]=$this->scenario();
        EstudianteProyecto::create(['estudiante_id'=>null,'proyecto_id'=>$project->id,'tipo_participacion_estudiante'=>'Servicio Social o PPS','cantidad_estudiantes_hombres'=>1,'cantidad_estudiantes_mujeres'=>0,'total_estudiantes'=>1]);
        $report=$this->initialize($project,$user);
        $grupo=$report->gruposEstudiantes()->firstOrFail();

        $html=$this->livewireComponent($user,$project)
            ->call('openEstudianteModal',null,$grupo->id)
            ->set('estudianteBusquedaCuenta','20269990000')
            ->call('buscarEstudiante')
            ->html();

        $sexoSelect=match (true) {
            preg_match('/<select wire:model="estudianteManual\.sexo"[^>]*>(.*?)<\/select>/s',$html,$m) === 1 => $m[1],
            default => '',
        };
        $this->assertStringContainsString('<option value="">Seleccione el sexo</option>',$sexoSelect);
    }

    public function test_sexo_masculino_se_guarda_sin_error_required(): void
    {
        [$user,$project]=$this->scenario();
        EstudianteProyecto::create(['estudiante_id'=>null,'proyecto_id'=>$project->id,'tipo_participacion_estudiante'=>'Servicio Social o PPS','cantidad_estudiantes_hombres'=>2,'cantidad_estudiantes_mujeres'=>2,'total_estudiantes'=>4]);
        $report=$this->initialize($project,$user);
        $grupo=$report->gruposEstudiantes()->firstOrFail();
        $component=$this->livewireComponent($user,$project)->call('openEstudianteModal',null,$grupo->id);
        $this->configurarRegistroManual($component)
            ->set('estudianteManual.sexo','Masculino')
            ->call('saveEstudianteManual')
            ->assertHasNoErrors('estudianteManual.sexo');
        $this->assertDatabaseHas('informe_final_estudiantes',['informe_final_grupo_estudiante_id'=>$grupo->id,'sexo'=>'Masculino','origen'=>'MANUAL','numero_cuenta'=>'20269990000']);
    }

    public function test_sexo_femenino_se_guarda_sin_error_required(): void
    {
        [$user,$project]=$this->scenario();
        EstudianteProyecto::create(['estudiante_id'=>null,'proyecto_id'=>$project->id,'tipo_participacion_estudiante'=>'Servicio Social o PPS','cantidad_estudiantes_hombres'=>2,'cantidad_estudiantes_mujeres'=>2,'total_estudiantes'=>4]);
        $report=$this->initialize($project,$user);
        $grupo=$report->gruposEstudiantes()->firstOrFail();
        $component=$this->livewireComponent($user,$project)->call('openEstudianteModal',null,$grupo->id);
        $this->configurarRegistroManual($component)
            ->set('estudianteManual.sexo','Femenino')
            ->call('saveEstudianteManual')
            ->assertHasNoErrors('estudianteManual.sexo');
        $this->assertDatabaseHas('informe_final_estudiantes',['informe_final_grupo_estudiante_id'=>$grupo->id,'sexo'=>'Femenino','origen'=>'MANUAL','numero_cuenta'=>'20269990000']);
    }

    public function test_sexo_vacio_muestra_mensaje_en_espanol(): void
    {
        [$user,$project]=$this->scenario();
        EstudianteProyecto::create(['estudiante_id'=>null,'proyecto_id'=>$project->id,'tipo_participacion_estudiante'=>'Servicio Social o PPS','cantidad_estudiantes_hombres'=>2,'cantidad_estudiantes_mujeres'=>2,'total_estudiantes'=>4]);
        $report=$this->initialize($project,$user);
        $grupo=$report->gruposEstudiantes()->firstOrFail();
        $component=$this->livewireComponent($user,$project)->call('openEstudianteModal',null,$grupo->id);
        $this->configurarRegistroManual($component)
            ->call('saveEstudianteManual')
            ->assertHasErrors(['estudianteManual.sexo'=>'El sexo del estudiante es obligatorio.']);
        $this->assertDatabaseMissing('informe_final_estudiantes',['informe_final_grupo_estudiante_id'=>$grupo->id,'origen'=>'MANUAL']);
    }

    public function test_los_mensajes_del_registro_manual_estan_en_espanol(): void
    {
        [$user,$project]=$this->scenario();
        EstudianteProyecto::create(['estudiante_id'=>null,'proyecto_id'=>$project->id,'tipo_participacion_estudiante'=>'Servicio Social o PPS','cantidad_estudiantes_hombres'=>2,'cantidad_estudiantes_mujeres'=>2,'total_estudiantes'=>4]);
        $report=$this->initialize($project,$user);
        $grupo=$report->gruposEstudiantes()->firstOrFail();
        $component=$this->livewireComponent($user,$project)->call('openEstudianteModal',null,$grupo->id);
        $this->configurarRegistroManual($component)
            ->set('estudianteManual.nombres','')
            ->set('estudianteManual.numero_cuenta','')
            ->set('estudianteManual.sexo','')
            ->set('estudianteManual.horas_dedicadas','')
            ->call('saveEstudianteManual')
            ->assertHasErrors([
                'estudianteManual.nombres'=>'El nombre del estudiante es obligatorio.',
                'estudianteManual.numero_cuenta'=>'El número de cuenta es obligatorio.',
                'estudianteManual.sexo'=>'El sexo del estudiante es obligatorio.',
                'estudianteManual.horas_dedicadas'=>'Las horas reales dedicadas son obligatorias.',
            ]);
    }

    private function configurarRegistroManual($component)
    {
        return $component
            ->set('estudianteBusquedaCuenta','20269990000')
            ->call('buscarEstudiante')
            ->assertSet('mostrarRegistroManual',true)
            ->set('estudianteManual.nombres','Estudiante')
            ->set('estudianteManual.apellidos','Manual')
            ->set('estudianteManual.numero_cuenta','20269990000')
            ->set('estudianteManual.carrera','Ingeniería en Sistemas')
            ->set('estudianteManual.correo','manual@example.test')
            ->set('estudianteManual.horas_dedicadas',40);
    }

    public function test_se_guardan_contrapartes(): void
    {
        [$user,$project]=$this->scenario(); $report=$this->initialize($project,$user);
        $this->assertSame('Asociación Comunitaria de Desarrollo',$report->contrapartes->first()->nombre); $this->assertSame('sociedad_civil',$report->contrapartes->first()->tipo);
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

    public function test_ods_planificado_es_solo_lectura_y_ods_de_ejecucion_permanece_editable(): void
    {
        [$user,$project]=$this->scenario();
        $odsId=DB::table('ods')->where('nombre','6. Agua limpia y saneamiento')->value('id') ?: DB::table('ods')->insertGetId(['nombre'=>'6. Agua limpia y saneamiento','created_at'=>now(),'updated_at'=>now()]);
        $project->ods()->syncWithoutDetaching([$odsId]);
        $report=$this->initialize($project,$user);
        $report->ods()->create(['ods_id'=>$odsId,'nivel_contribucion'=>'indirecta','origen'=>'EJECUCION']);

        $html=$this->livewireComponent($user,$project)->set('currentStep',6)->html();

        $this->assertStringContainsString('Cargado desde el registro del proyecto',$html);
        $this->assertStringContainsString('Ejecución',$html);
        $this->assertStringContainsString('readonly',$html);
        $this->assertStringContainsString('wire:model="ods.1.ods_id"',$html);
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
        $this->assertSame(68792.44,$report->total_contraparte);
    }

    public function test_se_calcula_ejecucion_total(): void
    {
        [$user,$project]=$this->scenario(); $report=$this->initialize($project,$user); $report->update(['aporte_beneficiarios'=>500,'otros_aportes'=>250]); $report->load('presupuestoDetalles');
        $this->assertSame(279542.44,$report->ejecucion_total);
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
        [$user,$project]=$this->scenario(); $report=$this->initialize($project,$user); $this->actingAs($user)->get(route('informes-finales.inf-001.preview',$report))->assertOk()->assertSee('INF-001')->assertSee('Fortalecimiento de la gestión comunitaria');
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
            ->assertSee('Marcar completo')
            ->assertSee('Vista previa')
            ->assertSee('Descargar PDF');
    }

    public function test_pdf_usa_plantilla_institucional_en_carta_vertical_y_generacion_centralizada(): void
    {
        $template=file_get_contents(resource_path('views/pdf/informes-finales/inf-001.blade.php'));
        $documento=file_get_contents(resource_path('views/proyectos/informe-final/partials/inf-001-document.blade.php'));
        $chrome=file_get_contents(resource_path('views/proyectos/informe-final/partials/inf-001-page-chrome.blade.php'));
        $controlador=file_get_contents(app_path('Http/Controllers/Proyectos/InformeFinal/InformeFinalProyectoController.php'));
        $generador=file_get_contents(app_path('Services/InformeFinal/InformeFinalPdfGenerator.php'));
        $this->assertStringContainsString('InformeFinalPdfGenerator',$controlador);
        $this->assertStringContainsString('loadMissing',$generador);
        $this->assertStringContainsString('setPaper([0, 0, 612, 792])',$generador);
        $this->assertStringContainsString("->stream(",$controlador);
        $this->assertStringContainsString("->download(",$controlador);
        $this->assertStringContainsString('page_text',$generador);
        $this->assertStringNotContainsString("setPaper('letter', 'landscape')",$generador);
        $this->assertStringContainsString('body { padding: 78pt 30pt 42pt 30pt',$documento);
        $this->assertStringContainsString('inf-001-document',$template);
        $this->assertStringContainsString('images/enf/form-018-header.png',$chrome);
        $this->assertStringContainsString('images/enf/form-018-watermark.png',$chrome);
        $this->assertStringContainsString("'file://'.public_path",$chrome);
        $this->assertStringContainsString('font-size: 8pt',$documento);
        $this->assertStringContainsString('"Liberation Sans"',$documento);
        $this->assertStringContainsString('width: 12pt; height: 82pt',$chrome);
        $this->assertStringNotContainsString('height: 100%',$chrome);
        $this->assertStringNotContainsString('border-right',$chrome);
        $this->assertStringContainsString('table-layout: fixed',$documento);
        $this->assertStringContainsString('border-collapse: collapse',$documento);
        $this->assertStringContainsString('display: table-header-group',$documento);
        $this->assertDirectoryDoesNotExist(public_path('images/informes-finales/inf-001/pages'));
        $this->assertDirectoryDoesNotExist(resource_path('views/proyectos/informe-final/partials/pages'));
        $this->assertStringNotContainsString('INF-002',$template);
    }

    public function test_vista_previa_y_pdf_usan_el_mismo_partial_compartido(): void
    {
        $template=file_get_contents(resource_path('views/proyectos/informe-final/partials/inf-001-document.blade.php'));
        $preview=file_get_contents(resource_path('views/proyectos/informe-final/inf-001.blade.php'));
        $pdf=file_get_contents(resource_path('views/pdf/informes-finales/inf-001.blade.php'));
        $partial="@include('proyectos.informe-final.partials.inf-001-document'";

        $this->assertStringContainsString('inf-001-page-chrome',$template);
        $this->assertStringContainsString('Pendiente de asignación',$template);
        $this->assertStringNotContainsString(implode('-', ['page','template']),$template);
        $this->assertStringNotContainsString(implode('-', ['page','overlay']),$template);
        $this->assertStringNotContainsString('position: absolute; padding',$template);
        $this->assertStringContainsString($partial,$preview);
        $this->assertStringContainsString($partial,$pdf);
        $this->assertFileDoesNotExist(resource_path('views/proyectos/informe-final/inf-001-pdf.blade.php'));
    }

    public function test_documento_compartido_conserva_campos_y_tablas_oficiales_sin_resumir(): void
    {
        $documento=file_get_contents(resource_path('views/proyectos/informe-final/partials/inf-001-document.blade.php'));
        foreach (['beneficiarios','equipoDocente','cooperacion','estudiantes','voluntarios','contrapartes','resultados','actividades','accionesNoEjecutadas','accionesEmergentes','ods','presupuestoDetalles','anexos','firmas'] as $dato) {
            $this->assertStringContainsString($dato,$documento);
        }
        foreach (['I. Información general del proyecto','II. Equipo ejecutor del proyecto','III. Cuantificación de participación de estudiantes','IV. Cuantificación de participación de voluntarios','V. Información de la entidad contraparte','VI. Informe de ejecución','VII. Reporte de acciones','VIII. Reporte de acciones emergentes','IX. Reflexión','X. Ejecución presupuestaria','XI. Firmas','XII. Anexos'] as $seccion) {
            $this->assertStringContainsString($seccion,$documento);
        }
        $this->assertStringContainsString('<table class="inf-table',$documento);
        $this->assertStringContainsString('ASIG / PPS / VOL',$documento);
        $this->assertStringNotContainsString('<th>Nombre completo</th><th>Cantidad</th>',$documento);
        $this->assertStringContainsString("firstWhere('es_responsable', true)",$documento);
        $this->assertStringNotContainsString("participantes->pluck",$documento);
    }

    public function test_las_filas_excedentes_crecen_dinamicamente_en_tablas_html(): void
    {
        [$user,$project]=$this->scenario();
        $report=$this->initialize($project,$user);
        $grupo=$report->gruposEstudiantes()->create(['tipo_participacion'=>'voluntariado','hombres_planificados'=>0,'mujeres_planificadas'=>0]);
        foreach (range(1,6) as $index) {
            $report->estudiantes()->create([
                'informe_final_grupo_estudiante_id'=>$grupo->id,
                'nombre'=>'Estudiante adicional '.$index,
                'sexo'=>$index % 2 === 0 ? 'Femenino' : 'Masculino',
                'tipo_participacion'=>'voluntariado',
                'cantidad'=>1,
                'horas_dedicadas'=>10,
            ]);
        }

        $html=view('pdf.informes-finales.inf-001',['informe'=>$report->fresh()])->render();

        foreach (range(1,6) as $index) {
            $this->assertStringContainsString('Estudiante adicional '.$index,$html);
        }
        $this->assertStringContainsString('display: table-header-group',$html);
        $this->assertStringNotContainsString('page-'.'04.png',$html);
    }

    public function test_la_tabla_de_firmas_renderiza_solo_firmas_existentes(): void
    {
        $documento=file_get_contents(resource_path('views/proyectos/informe-final/partials/inf-001-document.blade.php'));

        $this->assertStringContainsString("data_get(\$firmas,'coordinador.firma')",$documento);
        $this->assertStringContainsString("data_get(\$firmas,'jefe.firma')",$documento);
        $this->assertStringContainsString("data_get(\$firmas,'enlace.firma')",$documento);
        $this->assertStringContainsString("data_get(\$firmas,'decano.firma')",$documento);
    }

    public function test_se_genera_pdf_inf001(): void
    {
        [$user,$project]=$this->scenario(); $report=$this->initialize($project,$user); $response=$this->actingAs($user)->get(route('informes-finales.inf-001.pdf',$report)); $response->assertOk(); $this->assertStringContainsString('application/pdf',(string)$response->headers->get('content-type'));
    }

    public function test_ruta_imprimir_genera_pdf_inline_con_el_mismo_constructor(): void
    {
        [$user,$project]=$this->scenario();
        $report=$this->initialize($project,$user);
        $response=$this->actingAs($user)->get(route('informes-finales.inf-001.print',$report));

        $response->assertOk();
        $this->assertStringContainsString('application/pdf',(string)$response->headers->get('content-type'));
        $this->assertStringStartsWith('inline; filename="INF-001-',(string)$response->headers->get('content-disposition'));
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

    public function test_accion_de_cierre_depende_de_la_aprobacion_real_de_etapas_normales(): void
    {
        [$user,$project]=$this->scenario();
        $workflow=app(InformeFinalProyectoWorkflowService::class);
        $firmaNormal=$project->firma_proyecto()->whereHas('flujoEtapa',fn($query)=>$query->where('aplica_cierre_proyecto',false))->firstOrFail();
        $firmaNormal->update(['estado_revision'=>'Pendiente']);

        $this->assertFalse($workflow->puedeIniciarInformeFinal($project->fresh(),$user));
        $this->assertFalse($workflow->resumenCierre($project->fresh(),$user)['visible']);

        $firmaNormal->update(['estado_revision'=>'Rechazado']);
        $this->assertFalse($project->fresh()->puedeMostrarCierreProyecto($user));
        $this->assertFalse($workflow->resumenCierre($project->fresh(),$user)['visible']);

        $firmaNormal->update(['estado_revision'=>'Aprobado']);
        $resumen=$workflow->resumenCierre($project->fresh(),$user);
        $this->assertTrue($resumen['visible']);
        $this->assertSame('crear',$resumen['accion']);
        $this->assertSame('Crear informe final',$resumen['texto_accion']);
        Livewire::actingAs($user)->test(HistorialProyecto::class,['proyecto'=>$project->fresh()])
            ->assertSee('Cierre del proyecto')
            ->assertSee('Crear informe final');
    }

    public function test_tarjeta_no_aparece_en_estados_anteriores_a_en_curso(): void
    {
        [$user,$project]=$this->scenario();

        foreach (['Borrador','En revisión','Subsanacion'] as $estado) {
            $this->ponerEstadoProyecto($project,$user,$estado);
            $this->assertFalse($project->fresh()->puedeMostrarCierreProyecto($user));
            Livewire::actingAs($user)->test(HistorialProyecto::class,['proyecto'=>$project->fresh()])
                ->assertDontSee('Cierre del proyecto')
                ->assertDontSee('Informe Final INF-001');
        }
    }

    public function test_backend_rechaza_crear_informe_antes_de_completar_flujo_normal(): void
    {
        [$user,$project]=$this->scenario();
        $this->ponerEstadoProyecto($project,$user,'Borrador');

        $this->actingAs($user)->get(route('proyectos.informe-final',$project))->assertForbidden();
        Livewire::actingAs($user)->test(HistorialProyecto::class,['proyecto'=>$project->fresh()])
            ->call('crearInformeFinal')
            ->assertStatus(403);
        $this->assertDatabaseMissing('informe_final_proyectos',['proyecto_id'=>$project->id]);
    }

    public function test_informe_historico_fuera_de_condicion_solo_es_visible_para_auditoria(): void
    {
        [$coordinador,$project]=$this->scenario();
        $report=$this->initialize($project,$coordinador);
        $coordinador->syncRoles([]);
        $this->ponerEstadoProyecto($project,$coordinador,'Subsanacion');
        $workflow=app(InformeFinalProyectoWorkflowService::class);

        $resumenCoordinador=$workflow->resumenCierre($project->fresh(),$coordinador->fresh());
        $this->assertFalse($resumenCoordinador['visible']);
        $this->actingAs($coordinador->fresh())->get(route('informes-finales.inf-001.preview',$report))->assertForbidden();

        $admin=User::factory()->create();
        $admin->assignRole(Role::firstOrCreate(['name'=>'admin','guard_name'=>'web']));
        $resumenAdmin=$workflow->resumenCierre($project->fresh(),$admin);
        $this->assertFalse($resumenAdmin['visible']);
        Livewire::actingAs($admin)->test(HistorialProyecto::class,['proyecto'=>$project->fresh()])
            ->assertDontSee('Cierre del proyecto')
            ->assertDontSee('Advertencia interna');
        $this->actingAs($admin)->get(route('informes-finales.inf-001.preview',$report))->assertOk();
    }

    public function test_crear_informe_es_idempotente_y_no_inicia_firmas_de_cierre(): void
    {
        [$user,$project]=$this->scenario();
        $workflow=app(InformeFinalProyectoWorkflowService::class);
        $antes=$project->firma_proyecto()->count();
        $primero=$workflow->crearInformeFinal($project,$user);
        $segundo=$workflow->crearInformeFinal($project->fresh(),$user);

        $this->assertSame($primero->id,$segundo->id);
        $this->assertSame(1,InformeFinalProyecto::where('proyecto_id',$project->id)->count());
        $this->assertSame($antes,$project->firma_proyecto()->count());
        $this->assertFalse($project->documentos()->where('tipo_documento','Informe Final')->exists());
    }

    public function test_envio_crea_un_documento_y_solo_firmas_de_cierre_y_bloquea_editor(): void
    {
        Storage::fake('public');
        [$user,$project]=$this->scenario();
        $this->componentReadyForCompletion($user,$project)->call('marcarCompleto')->assertHasNoErrors();
        $report=$project->informeFinalInf001()->firstOrFail();
        $documento=app(InformeFinalProyectoWorkflowService::class)->enviarInformeFinal($report,$user);

        $this->assertSame(1,$project->documentos()->where('tipo_documento','Informe Final')->count());
        $this->assertSame(
            $project->flujoEtapasActivasOrdenadas(Proyecto::FLUJO_CIERRE_PROYECTO)->pluck('id')->sort()->values()->all(),
            $documento->firma_documento()->pluck('flujo_aprobacion_etapa_id')->sort()->values()->all()
        );
        $this->assertSame(InformeFinalProyecto::ESTADO_EN_REVISION,$report->fresh()->estadoFlujo());
        Livewire::actingAs($user)->test(EditInformeFinalProyecto::class,['proyecto'=>$project->fresh()])->assertStatus(403);
        Storage::disk('public')->assertExists($documento->documento_url);
    }

    public function test_subsanacion_reutiliza_documento_preserva_ciclo_y_habilita_edicion(): void
    {
        Storage::fake('public');
        [$user,$project]=$this->scenario();
        $this->componentReadyForCompletion($user,$project)->call('marcarCompleto')->assertHasNoErrors();
        $workflow=app(InformeFinalProyectoWorkflowService::class);
        $report=$project->informeFinalInf001()->firstOrFail();
        $documento=$workflow->enviarInformeFinal($report,$user);
        $firma=$documento->firma_documento()->firstOrFail();
        $firma->update(['estado_revision'=>'Rechazado']);
        $subsanacion=TipoEstado::firstOrCreate(['nombre'=>'Subsanacion']);
        $documento->estado_documento()->create(['empleado_id'=>$user->empleado->id,'tipo_estado_id'=>$subsanacion->id,'fecha'=>now(),'comentario'=>'Corregir anexos.']);

        $this->assertSame(InformeFinalProyecto::ESTADO_RECHAZADO,$report->fresh()->estadoFlujo());
        $this->assertTrue($workflow->puedeContinuarInformeFinal($report->fresh(),$user));
        Livewire::actingAs($user)->test(EditInformeFinalProyecto::class,['proyecto'=>$project->fresh()])->assertOk();

        $reenviado=$workflow->enviarInformeFinal($report->fresh(),$user);
        $this->assertSame($documento->id,$reenviado->id);
        $this->assertSame(1,$project->documentos()->where('tipo_documento','Informe Final')->count());
        $this->assertSame([1,2],$reenviado->firma_documento()->distinct()->orderBy('revision_ciclo')->pluck('revision_ciclo')->all());
        $this->assertDatabaseHas('firma_proyecto',['id'=>$firma->id,'estado_revision'=>'Rechazado','revision_ciclo'=>1]);
    }

    public function test_aprobacion_habilita_pdf_final_sin_marca_borrador(): void
    {
        [$user,$project]=$this->scenario();
        $report=$this->initialize($project,$user);
        $aprobado=TipoEstado::firstOrCreate(['nombre'=>'Aprobado']);
        $documento=$project->documentos()->create(['tipo_documento'=>'Informe Final','documento_url'=>'documentos/final.pdf']);
        $documento->estado_documento()->create(['empleado_id'=>$user->empleado->id,'tipo_estado_id'=>$aprobado->id,'fecha'=>now(),'comentario'=>'[Cierre INF-001] Aprobado.']);

        $this->assertSame(InformeFinalProyecto::ESTADO_APROBADO,$report->fresh()->estadoFlujo());
        $data=app(InformeFinalPdfGenerator::class)->viewData($report->fresh(),true);
        $this->assertFalse($data['esBorrador']);
        $resumen=app(InformeFinalProyectoWorkflowService::class)->resumenCierre($project->fresh(),$user);
        $this->assertSame('aprobado',$resumen['accion']);
        $this->assertSame('Ver informe final aprobado',$resumen['texto_accion']);
    }

    public function test_historial_diferencia_pdf_final_y_constancia_emitida(): void
    {
        [$user, $project, $report, $constancia] = $this->cierreFinalizadoConConstancia(ConstanciaFinalizacionProyecto::ESTADO_EMITIDA);

        $html = Livewire::actingAs($user)->test(HistorialProyecto::class, ['proyecto' => $project])
            ->assertSee('Ver informe final aprobado')
            ->assertSee('Descargar PDF final')
            ->assertSee('Descargar constancia de finalización')
            ->html();

        $this->assertStringContainsString(route('informes-finales.inf-001.pdf', $report, false), $html);
        $this->assertStringContainsString(route('constancias.finalizacion.descargar', $constancia, false), $html);
        $this->assertNotSame(
            route('informes-finales.inf-001.pdf', $report, false),
            route('constancias.finalizacion.descargar', $constancia, false)
        );
    }

    public function test_historial_informa_estados_pendiente_y_error_de_constancia_sin_ocultar_pdf(): void
    {
        foreach ([
            ConstanciaFinalizacionProyecto::ESTADO_PENDIENTE => 'La constancia de finalización está en proceso de generación.',
            ConstanciaFinalizacionProyecto::ESTADO_ERROR => 'No fue posible generar la constancia de finalización.',
        ] as $estado => $mensaje) {
            [$user, $project] = $this->cierreFinalizadoConConstancia($estado);
            Livewire::actingAs($user)->test(HistorialProyecto::class, ['proyecto' => $project])
                ->assertSee('Descargar PDF final')
                ->assertSee($mensaje)
                ->assertDontSee('Descargar constancia de finalización');
        }
    }

    public function test_usuario_no_autorizado_no_puede_descargar_constancia_finalizacion(): void
    {
        [, $project, , $constancia] = $this->cierreFinalizadoConConstancia(ConstanciaFinalizacionProyecto::ESTADO_EMITIDA);
        $otroUsuario = User::factory()->create();

        $this->actingAs($otroUsuario)
            ->get(route('constancias.finalizacion.descargar', $constancia))
            ->assertForbidden();
    }

    public function test_descarga_constancia_entrega_el_pdf_privado_y_nunca_el_inf001(): void
    {
        Storage::fake('local');
        [$user, , , $constancia] = $this->cierreFinalizadoConConstancia(ConstanciaFinalizacionProyecto::ESTADO_EMITIDA);
        $contenido = '%PDF-constancia-finalizacion-prueba';
        Storage::disk('local')->put($constancia->ruta_archivo, $contenido);

        $response = $this->actingAs($user)
            ->get(route('constancias.finalizacion.descargar', ['constancia' => $constancia->id]))
            ->assertOk()
            ->assertHeader('Content-Type', 'application/pdf');

        $disposition = (string) $response->headers->get('Content-Disposition');
        $this->assertStringContainsString('attachment', $disposition);
        $this->assertStringContainsString('constancia-finalizacion-', $disposition);
        $this->assertStringNotContainsString('INF-001', $disposition);
        $this->assertStringNotContainsString('Pendiente-de-asignacion', $disposition);
        $this->assertSame($contenido, $response->streamedContent());
    }

    public function test_verificacion_publica_permite_descargar_unicamente_constancia_vigente(): void
    {
        Storage::fake('local');
        [, , , $constancia] = $this->cierreFinalizadoConConstancia(ConstanciaFinalizacionProyecto::ESTADO_EMITIDA);
        $token = 'token-publico-constancia-vigente';
        $constancia->update([
            'token_hash' => hash('sha256', $token),
            'snapshot' => ['proyecto' => ['nombre' => 'Proyecto público', 'codigo' => 'PUB-001', 'unidad_academica' => 'UNAH']],
        ]);
        $contenido = '%PDF-constancia-vigente';
        Storage::disk('local')->put($constancia->ruta_archivo, $contenido);

        $this->get(route('constancias.finalizacion.verificar', ['token' => $token]))
            ->assertOk()
            ->assertSee('Constancia vigente')
            ->assertSee(route('constancias.finalizacion.verificar.pdf', ['token' => $token], false));

        $response = $this->get(route('constancias.finalizacion.verificar.pdf', ['token' => $token]))
            ->assertOk()
            ->assertHeader('Content-Type', 'application/pdf');

        $this->assertSame($contenido, $response->streamedContent());
    }

    public function test_verificacion_publica_no_expone_pdf_de_constancia_no_vigente_o_token_invalido(): void
    {
        Storage::fake('local');
        [, , , $constancia] = $this->cierreFinalizadoConConstancia(ConstanciaFinalizacionProyecto::ESTADO_PENDIENTE);
        $token = 'token-publico-constancia-pendiente';
        $constancia->update(['token_hash' => hash('sha256', $token)]);

        $this->get(route('constancias.finalizacion.verificar', ['token' => $token]))
            ->assertOk()
            ->assertSee('Constancia no vigente')
            ->assertDontSee('Descargar constancia vigente');

        $this->get(route('constancias.finalizacion.verificar.pdf', ['token' => $token]))->assertNotFound();
        $this->get(route('constancias.finalizacion.verificar', ['token' => 'token-invalido']))->assertNotFound();
    }

    public function test_usuario_no_autorizado_no_puede_crear_enviar_ni_ver_accion_de_cierre(): void
    {
        [$user,$project]=$this->scenario();
        $otro=User::factory()->create();
        $workflow=app(InformeFinalProyectoWorkflowService::class);
        $this->assertFalse($workflow->puedeIniciarInformeFinal($project,$otro));
        $this->assertFalse($workflow->resumenCierre($project,$otro)['visible']);
        $this->actingAs($otro)->get(route('proyectos.informe-final',$project))->assertForbidden();
    }

    public function test_accion_principal_e_historial_de_cierre_estan_solo_en_ver_proyecto(): void
    {
        [$user,$project]=$this->scenario();
        $workflow=app(InformeFinalProyectoWorkflowService::class);
        $report=$workflow->crearInformeFinal($project,$user);
        $report->update(['estado'=>InformeFinalProyecto::ESTADO_COMPLETO]);
        $workflow->registrarInformeCompleto($report,$user);

        $vistaProyecto=file_get_contents(resource_path('views/livewire/docente/proyectos/historial-proyecto.blade.php'));
        $listado=file_get_contents(resource_path('views/livewire/docente/proyectos/proyectos-docente-list.blade.php'));
        $this->assertStringContainsString('Cierre del proyecto',$vistaProyecto);
        $this->assertStringContainsString('wire:click="crearInformeFinal"',$vistaProyecto);
        $this->assertStringContainsString('wire:click="enviarInformeFinal"',$vistaProyecto);
        $this->assertStringContainsString("cierreInformeFinal['visible']",$vistaProyecto);
        $this->assertStringContainsString('Descargar constancia de finalización',$vistaProyecto);
        $this->assertStringContainsString('Flujo de cierre INF-001',$vistaProyecto);
        $this->assertStringContainsString('Flujo normal del proyecto',$vistaProyecto);
        $this->assertStringNotContainsString('route(\'proyectos.informe-final\'',$listado);
        $this->assertDatabaseHas('estado_proyecto',['estadoable_type'=>Proyecto::class,'estadoable_id'=>$project->id,'comentario'=>'[Cierre INF-001] Informe final creado en borrador.']);
        $this->assertDatabaseHas('estado_proyecto',['estadoable_type'=>Proyecto::class,'estadoable_id'=>$project->id,'comentario'=>'[Cierre INF-001] Informe final completado y listo para envío.']);
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

    private function cierreFinalizadoConConstancia(string $estado): array
    {
        [$user, $project] = $this->scenario();
        $report = $this->initialize($project, $user);
        $report->update(['fecha_cierre' => now()->toDateString()]);
        $aprobado = TipoEstado::firstOrCreate(['nombre' => 'Aprobado']);
        $documento = $project->documentos()->create(['tipo_documento' => 'Informe Final', 'documento_url' => 'documentos/final.pdf']);
        $documento->estado_documento()->create(['empleado_id' => $user->empleado->id, 'tipo_estado_id' => $aprobado->id, 'fecha' => now()]);
        $this->ponerEstadoProyecto($project, $user, 'Finalizado');

        $constancia = ConstanciaFinalizacionProyecto::create([
            'proyecto_id' => $project->id,
            'informe_final_proyecto_id' => $report->id,
            'documento_proyecto_id' => $documento->id,
            'numero' => 'VRA-DVUS-'.uniqid(),
            'anio' => (int) now()->year,
            'correlativo' => random_int(100000, 999999),
            'codigo_validacion' => strtoupper(substr(sha1(uniqid('', true)), 0, 20)),
            'token_hash' => hash('sha256', uniqid('', true)),
            'ruta_archivo' => $estado === ConstanciaFinalizacionProyecto::ESTADO_EMITIDA ? 'constancias/finalizacion/prueba.pdf' : null,
            'snapshot' => [],
            'fecha_emision' => now(),
            'emitida_por' => $user->id,
            'estado' => $estado,
        ]);

        return [$user, $project->fresh(), $report->fresh(), $constancia];
    }

    private function ponerEstadoProyecto(Proyecto $project, User $user, string $nombre): void
    {
        $tipoEstado=TipoEstado::firstOrCreate(['nombre'=>$nombre]);
        $project->estado_proyecto()->update(['es_actual'=>false]);
        \App\Models\Estado\EstadoProyecto::withoutEvents(function () use ($project,$user,$tipoEstado): void {
            $project->estado_proyecto()->create([
                'empleado_id'=>$user->empleado->id,
                'tipo_estado_id'=>$tipoEstado->id,
                'fecha'=>now(),
                'comentario'=>'Estado de prueba.',
                'es_actual'=>true,
            ]);
        });
    }

    private function scenario(): array
    {
        $user=User::factory()->create(['name'=>'Dorian Adolfo Ordóñez Osorto','email'=>'coordinador.comunitario.'.uniqid().'@example.test']);
        $role=Role::firstOrCreate(['name'=>'admin','guard_name'=>'web']); $user->assignRole($role);
        $employee=Empleado::create(['nombre_completo'=>'Dorian Adolfo Ordóñez Osorto','numero_empleado'=>(string)random_int(100000,999999),'celular'=>'99999999','sexo'=>'Masculino','user_id'=>$user->id,'tipo_empleado'=>'docente']);
        $type=VinculacionTipoAccion::firstOrCreate(['codigo'=>'DESARROLLO_LOCAL_REGIONAL'],['nombre'=>'Desarrollo local y regional','activo'=>true]);
        $project=Proyecto::create(['tipo_accion_id'=>$type->id,'codigo_proyecto'=>'PROY-COM-'.uniqid(),'nombre_proyecto'=>'Fortalecimiento de la gestión comunitaria','fecha_inicio'=>'2026-01-12','fecha_finalizacion'=>'2026-11-30','objetivo_general'=>'Mejorar la gestión de organizaciones comunitarias','poblacion_participante'=>3504,'hombres'=>1700,'mujeres'=>1804,'mestizos_hombres'=>1700,'mestizos_mujeres'=>1804,'impacto_deseado'=>'Herramientas fortalecidas para la gestión comunitaria','total_aporte_institucional'=>200000]);
        EmpleadoProyecto::create(['empleado_id'=>$employee->id,'proyecto_id'=>$project->id,'rol'=>'Coordinador']);
        $now=now(); $campus=DB::table('campus')->insertGetId(['nombre_campus'=>'UNAH Choluteca '.uniqid(),'direccion'=>'Choluteca','telefono'=>'00000000','url'=>'https://unah.edu.hn','created_at'=>$now,'updated_at'=>$now]);
        $center=DB::table('centro_facultad')->insertGetId(['nombre'=>'UNAH Choluteca','es_facultad'=>false,'siglas'=>'CURLP','campus_id'=>$campus,'created_at'=>$now,'updated_at'=>$now]);
        DB::table('proyecto_centro_facultad')->insert(['proyecto_id'=>$project->id,'centro_facultad_id'=>$center,'created_at'=>$now,'updated_at'=>$now]);
        $catalogo=EntidadContraparte::create(['nombre'=>'Asociación Comunitaria de Desarrollo','tipo_entidad'=>'sociedad_civil','nombre_contacto'=>'Representante comunitario','cargo_contacto'=>'Presidencia','correo'=>'asociacion@example.test','telefono'=>'00000000']);
        EntidadContraparteProyecto::create(['proyecto_id'=>$project->id,'entidad_contraparte_id'=>$catalogo->id,'descripcion_acuerdos'=>'Acompañar y validar los resultados']);
        $objective=ObjetivoEspecifico::create(['proyecto_id'=>$project->id,'descripcion'=>'Fortalecer la gestión comunitaria','orden'=>1]);
        ResultadoEsperado::create(['objetivo_especifico_id'=>$objective->id,'nombre_resultado'=>'Aplicación informática disponible','nombre_indicador'=>'Una aplicación implementada','nombre_medio_verificacion'=>'Acta de entrega','plazo'=>'corto_plazo','orden'=>1]);
        Actividad::create(['proyecto_id'=>$project->id,'descripcion'=>'Levantamiento de requerimientos','fecha_inicio'=>'2026-01-12','fecha_finalizacion'=>'2026-02-15','horas'=>80]);
        AporteInstitucional::create(['proyecto_id'=>$project->id,'concepto'=>'horas_trabajo_docentes','unidad'=>'hra_profes','cantidad'=>1,'costo_unitario'=>200000,'costo_total'=>200000]);
        Presupuesto::create(['proyecto_id'=>$project->id,'aporte_contraparte'=>66792.44]);
        $estadoNormal = TipoEstado::firstOrCreate(['nombre'=>'En curso']);
        $estadoCierre = TipoEstado::firstOrCreate(['nombre'=>'Revisión cierre INF-001']);
        $tipoCargoNormal = TipoCargoFirma::create(['nombre'=>'Revisor normal '.uniqid()]);
        $tipoCargoCierre = TipoCargoFirma::create(['nombre'=>'Revisor cierre '.uniqid()]);
        $cargoNormal = CargoFirma::create(['descripcion'=>'Proyecto','tipo_cargo_firma_id'=>$tipoCargoNormal->id,'tipo_estado_id'=>$estadoNormal->id]);
        $cargoCierre = CargoFirma::create(['descripcion'=>'Proyecto','tipo_cargo_firma_id'=>$tipoCargoCierre->id,'tipo_estado_id'=>$estadoCierre->id]);
        $flujo = FlujoAprobacion::create(['codigo'=>'INF001_'.uniqid(),'nombre'=>'Flujo INF-001','proceso'=>'PROYECTO','tipo_accion_id'=>$type->id,'codigo_formulario'=>'FORM-DVUS-001','activo'=>true]);
        $etapaNormal = FlujoAprobacionEtapa::create(['flujo_aprobacion_id'=>$flujo->id,'orden'=>1,'codigo'=>'NORMAL_'.uniqid(),'nombre'=>'Aprobación normal','tipo_etapa'=>'APROBACION','cargo_firma_id'=>$cargoNormal->id,'usuario_responsable_id'=>$user->id,'activo'=>true,'aplica_inscripcion'=>true,'aplica_cierre_proyecto'=>false]);
        $etapaCierre = FlujoAprobacionEtapa::create(['flujo_aprobacion_id'=>$flujo->id,'orden'=>2,'codigo'=>'CIERRE_'.uniqid(),'nombre'=>'Aprobación cierre','tipo_etapa'=>'APROBACION','cargo_firma_id'=>$cargoCierre->id,'usuario_responsable_id'=>$user->id,'activo'=>true,'aplica_inscripcion'=>true,'aplica_cierre_proyecto'=>true]);
        $project->update(['flujo_aprobacion_id'=>$flujo->id]);
        $project->firma_proyecto()->create(['empleado_id'=>$employee->id,'cargo_firma_id'=>$cargoNormal->id,'estado_revision'=>'Aprobado','hash'=>'normal-'.uniqid(),'flujo_aprobacion_id'=>$flujo->id,'flujo_aprobacion_etapa_id'=>$etapaNormal->id,'orden_revision'=>1,'etapa_codigo'=>$etapaNormal->codigo,'etapa_nombre'=>$etapaNormal->nombre,'revision_ciclo'=>1,'fecha_firma'=>now()]);
        $project->firma_proyecto()->create(['empleado_id'=>$employee->id,'cargo_firma_id'=>$cargoCierre->id,'estado_revision'=>'Aprobado','hash'=>'inscripcion-cierre-'.uniqid(),'flujo_aprobacion_id'=>$flujo->id,'flujo_aprobacion_etapa_id'=>$etapaCierre->id,'orden_revision'=>2,'etapa_codigo'=>$etapaCierre->codigo,'etapa_nombre'=>$etapaCierre->nombre,'revision_ciclo'=>1,'fecha_firma'=>now()]);
        $project->estado_proyecto()->create(['empleado_id'=>$employee->id,'tipo_estado_id'=>$estadoNormal->id,'fecha'=>now(),'comentario'=>'Flujo normal aprobado.','es_actual'=>true]);
        return [$user,$project];
    }
}
