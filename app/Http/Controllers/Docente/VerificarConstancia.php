<?php

namespace App\Http\Controllers\Docente;

use Exception;
use Illuminate\Http\Request;
use App\Models\Proyecto\Proyecto;
use App\Http\Controllers\Controller;
use App\Models\Constancia\Constancia;
use App\Models\Constancia\TipoConstancia;
use App\Models\Proyecto\FichaActualizacion;
use App\Models\Personal\Empleado;
use Illuminate\Support\Facades\Crypt;
use App\Models\Personal\EmpleadoProyecto;
use App\Services\Constancia\BuilderConstancia;
use App\Services\Constancia\ConstanciaBuilder;
use App\Services\Constancia\MakeConstancia;
use SimpleSoftwareIO\QrCode\Facades\QrCode;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Str;

class VerificarConstancia extends Controller
{
    /*
        CREAR LAS CONSTANCIAS A LOS PROYECTOS 
    */
    public static function makeConstanciasProyecto(Proyecto $proyecto)
    {
        // validar que el proyecto este en estado de 'En curso' o 'Finalizado'
        $nombreEstadoProyecto = $proyecto->estado->tipoestado->nombre;
        if ($nombreEstadoProyecto != 'En curso' && $nombreEstadoProyecto != 'Finalizado') {
            return;
        }

        // mapear los empleados proyectos y llamar al metodo validarConstanciaEmpleado para ver si puede recibir o no su constancia
        $proyecto->docentes_proyecto->map(function ($empleadoProyecto) use ($nombreEstadoProyecto) {
            // validar si el empleado del proyecto se puede generar constancia o no
            if (self::validarConstanciaEmpleado($empleadoProyecto)) {
                // crear la constancia
                $Constancia = Constancia::create([
                    'origen_type' => Proyecto::class,
                    'origen_id' => $empleadoProyecto->proyecto_id,
                    'destinatario_type' => Empleado::class,
                    'destinatario_id' => $empleadoProyecto->empleado_id,
                    'tipo_constancia_id' => $nombreEstadoProyecto == 'En curso'
                        ? TipoConstancia::where('nombre', 'inscripcion')->first()->id
                        : TipoConstancia::where('nombre', 'finalizacion')->first()->id,
                ]);
            }
        });
        return;
    }

    /*
        CREAR LAS CONSTANCIAS A LAS FICHAS DE ACTUALIZACION 
    */
    public static function makeConstanciasActualizacion(FichaActualizacion $fichaActualizacion)
    {
        // validar que la ficha este en estado de 'Actualizcion realizada'
        $nombreEstadoProyecto = $fichaActualizacion->estado->tipoestado->nombre;
        if ($nombreEstadoProyecto != 'Actualizacion realizada') {
            return;
        }

        // mapear los empleados proyectos y llamar al metodo validarConstanciaEmpleado para ver si puede recibir o no su constancia
        $fichaActualizacion->proyecto->docentes_proyecto->map(function ($empleadoProyecto) use ($nombreEstadoProyecto) {
            // validar si el empleado del proyecto se puede generar constancia o no
            if (self::validarConstanciaEmpleadoActualizacion($empleadoProyecto)) {
                // crear la constancia
                $Constancia = Constancia::create([
                    'origen_type' => FichaActualizacion::class,
                    'origen_id' => $empleadoProyecto->proyecto_id,
                    'destinatario_type' => Empleado::class,
                    'destinatario_id' => $empleadoProyecto->empleado_id,
                    'tipo_constancia_id' => $nombreEstadoProyecto == 'Actualizacion realizada'
                        ? TipoConstancia::where('nombre', 'Actualizacion')->first()->id
                        : TipoConstancia::where('nombre', 'Actualizacion')->first()->id,
                ]);
            }
        });


        return;
    }

    // recibe un empleado proyecto y valida si tiene una constancia del tipo inscripcion

    public static function validarConstancia(EmpleadoProyecto $empleadoProyecto, $tipo = 'inscripcion'): bool
    {
        // Obtener el ID del tipo de constancia solo una vez
        $tipoConstanciaId = TipoConstancia::where('nombre', $tipo)->value('id');

        // Realizar la consulta
        $existeConstancia = Constancia::where('origen_type', Proyecto::class)
            ->where('origen_id', $empleadoProyecto->proyecto_id)
            ->where('destinatario_type', Empleado::class)
            ->where('destinatario_id', $empleadoProyecto->empleado_id)
            ->where('tipo_constancia_id', $tipoConstanciaId)
            ->exists(); // Devuelve true si existe, false si no

        return $existeConstancia;
    }

    // recibe un empleado proyecto y valida si tiene una constancia del tipo actualizacion

    public static function validarConstanciaActualizacion(EmpleadoProyecto $empleadoProyecto, $tipo = 'Actualizacion'): bool
    {
        // Obtener el ID del tipo de constancia solo una vez
        $tipoConstanciaId = TipoConstancia::where('nombre', $tipo)->value('id');

        // Realizar la consulta
        $existeConstancia = Constancia::where('origen_type', FichaActualizacion::class)
            ->where('origen_id', $empleadoProyecto->proyecto_id)
            ->where('destinatario_type', Empleado::class)
            ->where('destinatario_id', $empleadoProyecto->empleado_id)
            ->where('tipo_constancia_id', $tipoConstanciaId)
            ->exists(); // Devuelve true si existe, false si no

        return $existeConstancia;
    }


    /*
        metodo para validar si el empleado del proyecto se puede generar constancia o no
    */
    public static function validarConstanciaEmpleado(EmpleadoProyecto $empleadoProyecto)
    {

        $categoriasValidas = ['Titular I', 'Titular II', 'Titular III', 'Titular IV', 'Titular V'];
        $estadosValidos = ['En curso', 'Finalizado', 'Actualizacion realizada'];

        // validar que el proyecto este en estado de 'En curso' o 'Finalizado'
        if (!in_array($empleadoProyecto->proyecto->estado->tipoestado->nombre, $estadosValidos)) {
            return false;
        }


        // validar que la categoria del empleado sea una de las categorias validas
        if (!in_array($empleadoProyecto->empleado->categoria->nombre, $categoriasValidas)) {
            return false;
        }
        // otras validaciones

        return true;
    }

    /*
        metodo para validar si el empleado del proyecto se puede generar constancia o no
    */
    public static function validarConstanciaEmpleadoActualizacion(EmpleadoProyecto $empleadoProyecto)
    {

        $categoriasValidas = ['Titular I', 'Titular II', 'Titular III', 'Titular IV', 'Titular V'];
        $estadosValidos = ['Actualizacion realizada'];

        // validar que la ficha actualizacion este en estado de 'Actualizacion realizada'
        if (!in_array($empleadoProyecto->proyecto->ficha_actualizacion->estado->tipoestado->nombre, $estadosValidos)) {
            return false;
        }


        // validar que la categoria del empleado sea una de las categorias validas
        if (!in_array($empleadoProyecto->empleado->categoria->nombre, $categoriasValidas)) {
            return false;
        }
        // otras validaciones

        return true;
    }

    public static function validarConstanciaInscripcion(EmpleadoProyecto $empleadoProyecto)
    {
        if (!self::validarConstanciaEmpleado($empleadoProyecto)) {
            return false;
        }
        return true;
    }

    public static function validarConstanciaFinalizacion(EmpleadoProyecto $empleadoProyecto)
    {
        if (!self::validarConstanciaEmpleado($empleadoProyecto)) {
            return false;
        }
        
        // validar que el estado del proyecto sea 'Finalizado'
        if ($empleadoProyecto->proyecto->estado->tipoestado->nombre != 'Finalizado') {
            return false;
        }
        return true;
    }

    public static function validarConstanciaActualizacionRealizada(EmpleadoProyecto $empleadoProyecto)
    {
        if (!self::validarConstanciaEmpleadoActualizacion($empleadoProyecto)) {
            return false;
        }
        // validar que el estado del proyecto sea 'Actualizacion realizada'
        if ($empleadoProyecto->proyecto->ficha_actualizacion->estado->tipoestado->nombre != 'Actualizacion realizada') {
            return false;
        }
        return true;
    }

    public static function CrearPdfInscripcion(EmpleadoProyecto $empleadoProyecto)
    {
        return self::CrearPdf($empleadoProyecto, 'inscripcion');
    }

    public static function CrearPdfFinalizacion(EmpleadoProyecto $empleadoProyecto)
    {
        return self::CrearPdf($empleadoProyecto, 'finalizacion');
    }

    public static function CrearPdfActualizacion(EmpleadoProyecto $empleadoProyecto)
    {
        return self::CrearPdf($empleadoProyecto, 'actualizacion');
    }

    public static function CrearPdf(EmpleadoProyecto $empleadoProyecto, $tipo)
    {
        // validar si el empleado del proyecto se puede generar constancia o no
        return BuilderConstancia::make($empleadoProyecto, $tipo, True)
            ->getConstancia();
    }




    // Método para mostrar la vista de verificación de constancia
    public function index($hash)
    {
        try {
            // buscar el empleado proyecto con el hash
            $Constancia = Constancia::where('hash', $hash)->firstOrFail();
            $tipo = $Constancia->tipo->nombre;
            // validar si el empleado del proyecto se puede generar constancia o n
            $empleadoProyecto = EmpleadoProyecto::where('proyecto_id', $Constancia->origen_id)
                ->where('empleado_id', $Constancia->destinatario_id)
                ->firstOrFail();

                

            $data = BuilderConstancia::make($empleadoProyecto,'inscripcion', false)
                ->getData();

            $Constancia->validaciones += 1;
            $Constancia->save();

            return view('aplicacion.resultado', $data);

        } catch (Exception $e) {
            // Si falla la desencriptación, puedes manejar el error
            abort(404, 'Datos inválidos');
        }
    }

    public function inscripcion(EmpleadoProyecto $empleadoProyecto)
    {
        // obtener el nombre del proyecto
        $proyecto = $empleadoProyecto->proyecto;
        // obtener el nombre del empleado
        $empleado = $empleadoProyecto->empleado;
        // obtener el nombre del empleado
        $empleado = $empleadoProyecto->empleado;
        // calular las horas del empleado en el proyecto
        $horas = $empleadoProyecto->empleado()
            ->first()
            ->actividades()
            ->where('proyecto_id', $proyecto->id)
            ->sum('horas');

        $data = [
            'nombre_proyecto' => $proyecto->nombre_proyecto,
            'nombre_empleado' => $empleado->nombre_completo,
            'numero_dictamen' => $proyecto->numero_dictamen,
            'numero_empleado' => $empleado->numero_empleado,
            'fecha_inicio' => $proyecto->fecha_inicio,
            'fecha_fin' => $proyecto->fecha_fin,
            'horas' => $horas
        ];

        return view('app.docente.constanciasOnline.verificacion_constancia', [
            'data' => $data
        ]);
    }


    public function finalizacion(EmpleadoProyecto $empleadoProyecto)
    {
        // obtener el nombre del proyecto
        $proyecto = $empleadoProyecto->proyecto;
        // obtener el nombre del empleado
        $empleado = $empleadoProyecto->empleado;
        // obtener el nombre del empleado
        $empleado = $empleadoProyecto->empleado;
        // calular las horas del empleado en el proyecto
        $horas = $empleadoProyecto->empleado()
            ->first()
            ->actividades()
            ->where('proyecto_id', $proyecto->id)
            ->sum('horas');

        $data = [
            'nombre_proyecto' => $proyecto->nombre_proyecto,
            'nombre_empleado' => $empleado->nombre_completo,
            'numero_dictamen' => $proyecto->numero_dictamen,
            'numero_empleado' => $empleado->numero_empleado,
            'fecha_inicio' => $proyecto->fecha_inicio,
            'fecha_fin' => $proyecto->fecha_fin,
            'horas' => $horas
        ];

        return view('app.docente.constanciasOnline.verificacion_finalizacion', [
            'data' => $data
        ]);
    }

    public function verificacionConstanciaVista()
    {
        

        return view('aplicacion.validar-constancias');
    }
}
