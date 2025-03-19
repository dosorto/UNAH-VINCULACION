<?php

namespace App\Http\Controllers\Docente;

use Exception;
use Illuminate\Http\Request;
use App\Models\Proyecto\Proyecto;
use App\Http\Controllers\Controller;
use App\Models\Personal\Empleado;
use Illuminate\Support\Facades\Crypt;
use App\Models\Personal\EmpleadoProyecto;
use SimpleSoftwareIO\QrCode\Facades\QrCode;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Str;

class VerificarConstancia extends Controller
{

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

    public static function CrearPdf(EmpleadoProyecto $empleadoProyecto, String $tipo)
    {
        // validar si el empleado del proyecto se puede generar constancia o no
        if (!self::validarConstanciaEmpleado($empleadoProyecto)) {
            return redirect()->back()->with('error', 'No se puede generar la constancia');
        }

        // obtener el estado del proyecto para seleccionar la vista del tipo de constancia
        $proyecto = $empleadoProyecto->proyecto;
        // crear un nombre random para el archivo qr
        $qrCodeName = Str::random(8) . '.png';
        // Generar la constancia PDF
        $qrcodePath = storage_path('app/public/' . $qrCodeName); // Ruta donde se guardará el QR


        if ($tipo == 'inscripcion') {
            $enlace =  url('/verificacion_constancia/i' . $empleadoProyecto->hash);
            $vista = 'app.docente.constancias.constancia_en_curso';
        } else if ($tipo == 'finalizacion') {
            $vista = 'app.docente.constancias.constancia_finalizado';
            $enlace =  url('/verificacion_constancia/f' . $empleadoProyecto->hash);
        }


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

            // quitar el ultimo caracter del hash 

            $tipo = substr($hash, 0, 1);
            $hash = substr($hash, 1);

            // buscar el empleado proyecto con el hash
            $empleadoProyecto = EmpleadoProyecto::where('hash', $hash)->firstOrFail();

            // validar si el empleado del proyecto se puede generar constancia o n

            // obtener el primer caracter del hash
            if ($tipo == 'i') {
                // validar si el empleado del proyecto se puede generar constancia o no
                if (!self::validarConstanciaInscripcion($empleadoProyecto)) {
                    abort(404, 'No se puede generar la constancia');
                }
                return $this->inscripcion($empleadoProyecto);
            } else if ($tipo == 'f') {

                // validar si el empleado del proyecto se puede generar constancia o no
                if (!self::validarConstanciaFinalizacion($empleadoProyecto)) {
                    abort(404, 'No se puede generar la constancia');
                }

                return $this->finalizacion($empleadoProyecto);
            } else {
                abort(404, 'Datos inválidos');
            }
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
        return view('app.docente.constanciasOnline.vista');
    }
}
