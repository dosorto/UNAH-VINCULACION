<?php

namespace App\Services\Constancia;

use App\Models\Constancia\Constancia;
use App\Models\Constancia\TipoConstancia;
use App\Models\Personal\EmpleadoProyecto;
use App\Models\Personal\Empleado;
use App\Models\Proyecto\Proyecto;

use Carbon\Carbon;
Carbon::setLocale('es');


// CREA LA CONSTANCIA A UN EMPLEADO DENTRO DE UN PROYECTO

class BuilderConstancia
{

    private Constancia $constancia;
    private EmpleadoProyecto $empleadoProyecto;
    private Empleado $empleado;
    private Proyecto $proyecto;

    private Empleado $director;

    private $data;
    private $tipo;


    public function __construct(EmpleadoProyecto $empleadoProyecto, $tipo)
    {
        $this->empleadoProyecto = $empleadoProyecto;
        $this->tipo = $tipo;
        $this->empleado = $empleadoProyecto->empleado;
        $this->proyecto = $empleadoProyecto->proyecto;
        $this->director = $this->proyecto->director_vinculacion;


        $this->constancia = Constancia::where('origen_type', Proyecto::class)
            ->where('origen_id', $empleadoProyecto->proyecto_id)
            ->where('destinatario_type', Empleado::class)
            ->where('destinatario_id', $empleadoProyecto->empleado_id)
            ->where('tipo_constancia_id', TipoConstancia::where('nombre', $tipo)
                ->first()
                ->id)
            ->first();

        $this->getData();
    }


    public static function make(EmpleadoProyecto $empleadoProyecto, $tipo)
    {
        return new static($empleadoProyecto, $tipo);
    }


    public function getConstancia()
    {
        return MakeConstancia::make($this->constancia)
            ->setLayout("app.docente.constancias.constancia_finalizado")
            ->setData($this->data)
            ->generate()
            ->downloadPDF();
    }

    public function getData(): array
    {
        $this->data = []; // Reiniciamos el array

        // Variables fuera del switch
        $director = $this->director?->nombre_completo ?? 'Nombre del Director';
        $empleado = $this->empleado?->nombre_completo ?? 'Nombre del Empleado';
        $numeroEmpleado = $this->empleado?->numero_empleado ?? 'N° empleado';
        $nombreCentro = $this->empleado?->centro_facultad->nombre ?? 'Nombre del Centro';
        $nombreDepartamento = $this->empleado?->departamento_academico->nombre ?? 'Nombre del Departamento';
        
        $fechaInicio = $this->proyecto?->fecha_inicio 
            ? Carbon::parse($this->proyecto->fecha_inicio)->translatedFormat('j \d\e F \d\e Y') 
            : 'Fecha de inicio';
        
        $fechaFinal = $this->proyecto?->fecha_finalizacion
            ? Carbon::parse($this->proyecto->fecha_finalizacion)->translatedFormat('j \d\e F \d\e Y') 
            : 'Fecha final';
        $nombreProyecto = $this->proyecto?->nombre_proyecto ?? 'Nombre del Proyecto';

        switch ($this->tipo) {
            case 'inscripcion':
                $this->data['titulo'] = 'CONSTANCIA DE ACTIVIDAD DE VINCULACIÓN';
                $this->data['texto'] =
                    "<p>La suscrita Directora de Vinculación Universidad-Sociedad-VRA-UNAH, 
                    por este medio hace constar que el empleado <strong>$empleado</strong>
                     con número de empleado <strong>$numeroEmpleado</strong>, 
                     del departamento de <strong>$nombreDepartamento</strong>, 
                     dependiente de <strong>$nombreCentro</strong>, 
                     forma parte del equipo que ejecuta la actividad de vinculación 
                     denominada <strong>$nombreProyecto</strong>, la cual se desarrolla 
                     del <strong>$fechaInicio</strong> hasta el 
                     <strong>$fechaFinal</strong>. Asimismo, se hace constar 
                     que la entrega del informe intermedio y final de dicha actividad 
                     está pendiente. En consecuencia, esta constancia tiene validez 
                     únicamente para fines de inscripción en la Dirección de 
                     Vinculación Universidad – Sociedad.</p>";
                break;

            case 'finalizacion':
                $this->data['titulo'] = 'CONSTANCIA DE FINALIZACIÓN DE ACTIVIDAD DE VINCULACIÓN';
                $this->data['texto'] =
                    "El suscrito Director de Vinculación, <strong>$director</strong>, 
                    hace constar que el empleado <strong>$empleado</strong>, con número de empleado 
                    <strong>$numeroEmpleado</strong>, ha concluido satisfactoriamente su participación 
                    en la actividad de vinculación <strong>$nombreProyecto</strong> 
                    desarrollada del 
                    <strong>$fechaInicio</strong> al <strong>$fechaFinal</strong>.";
                break;

            default:
                $this->data['titulo'] = 'N/D';
                $this->data['texto'] = 'N/D';
                break;
        }

        // Agregar siempre estos datos al final
        $this->data['nombreDirector'] = $this->director?->nombre_completo ?? 'Nombre del Director'; 
        $this->data['firmaDirector'] = $this->director?->firma?->ruta_storage ?? '';
        $this->data['selloDirector'] = $this->director?->sello?->ruta_storage ?? '';
        $this->data['anioAcademico'] = $this->anioAcademico ?? '';
        $this->data['codigoVerificacion'] = $this->constancia->hash ?? '';
        $this->data['anioAcademico'] = 'Año Académico 2025 "José Dionisio de Herrera"';
        return $this->data;
    }
}


    // crear la constancia en funcion del tipo que sea 
