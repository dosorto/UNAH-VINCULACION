<?php

namespace App\Http\Controllers\Docente;

use Exception;
use Illuminate\Http\Request;
use App\Models\Proyecto\Proyecto;
use App\Http\Controllers\Controller;
use App\Models\Constancia\Constancia;
use App\Models\Constancia\TipoConstancia;
use App\Models\Personal\Empleado;
use Illuminate\Support\Facades\Crypt;
use App\Models\Personal\EmpleadoProyecto;
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
                Constancia::create([
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




    /*
        metodo para validar si el empleado del proyecto se puede generar constancia o no
    */
    public static function validarConstanciaEmpleado(EmpleadoProyecto $empleadoProyecto)
    {

        $categoriasValidas = ['Titular I', 'Titular II', 'Titular III', 'Titular IV', 'Titular V'];
        $estadosValidos = ['En curso', 'Finalizado'];

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

    public static function CrearPdfInscripcion(EmpleadoProyecto $empleadoProyecto)
    {
        return self::CrearPdf($empleadoProyecto, 'inscripcion');
    }

    public static function CrearPdfFinalizacion(EmpleadoProyecto $empleadoProyecto)
    {
        return self::CrearPdf($empleadoProyecto, 'finalizacion');
    }

    public static function CrearPdf(EmpleadoProyecto $empleadoProyecto, $tipo)
    {
        // validar si el empleado del proyecto se puede generar constancia o no
        if (!self::validarConstanciaEmpleado($empleadoProyecto)) {
            return redirect()->back()->with('error', 'No se puede generar la constancia');
        }


        $Constancia = Constancia::where('origen_type', Proyecto::class)
            ->where('origen_id', $empleadoProyecto->proyecto_id)
            ->where('destinatario_type', Empleado::class)
            ->where('destinatario_id', $empleadoProyecto->empleado_id)
            ->where('tipo_constancia_id', TipoConstancia::where('nombre', $tipo)->first()->id)
            ->first();



        // obtener el estado del proyecto para seleccionar la vista del tipo de constancia
        $proyecto = $empleadoProyecto->proyecto;
        // crear un nombre random para el archivo qr
        $qrCodeName = Str::random(8) . '.png';
        // Generar la constancia PDF
        $qrcodePath = storage_path('app/public/' . $qrCodeName); // Ruta donde se guardará el QR

        $enlace =  url('/verificacion_constancia/' . $Constancia->hash);

        if ($tipo == 'inscripcion')
            $vista = 'app.docente.constancias.constancia_en_curso';
        else if ($tipo == 'finalizacion')
            $vista = 'app.docente.constancias.constancia_finalizado';



        // Generar el código QR como imagen base64
        QrCode::format('png')->size(200)->errorCorrection('H')->generate($enlace, $qrcodePath);


        // Cargar la imagen (logo) y moverla a la carpeta pública

        // Datos que se pasan a la vista
        $data = [
            'title' => 'Constancia de Participación',
            'proyecto' => $proyecto,
            'empleado' => auth()->user()->empleado,
            'qrCode' => $qrcodePath,
            'enlace' => $enlace,
        ];

        $pdf = PDF::loadView($vista, $data);



        // Generar un nombre único para el archivo basándome en los id del empleado en el proyecto
        $fileName = 'constancia_' . $proyecto->id . '_' . auth()->user()->empleado->id . '_' . Str::random(8) . '.pdf';

        // Definir la ruta con el nombre único
        $filePath = storage_path('app/public/' . $fileName);

        // Guardar el PDF en la ruta
        $pdf->save($filePath);


        // eliminar el qr despues de generar el pdf
        unlink($qrcodePath);

        // Descargar el PDF y eliminarlo al instante para que no quede en storage
        return response()
            ->download($filePath, 'Constancia_Participacion.pdf')
            ->deleteFileAfterSend(true);
    }



    public static function crearConstancia(EmpleadoProyecto $empleadoProyecto) {}


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

            if ($tipo == 'Inscripcion')
                return Self::inscripcion($empleadoProyecto);
            else if ($tipo == 'Finalizacion')
                return Self::finalizacion($empleadoProyecto);

        
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
        return view('app.docente.constancias.constancia_finalizado');
    }
}
