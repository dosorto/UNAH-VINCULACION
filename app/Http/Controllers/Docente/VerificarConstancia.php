<?php

namespace App\Http\Controllers\Docente;

use Exception;
use Illuminate\Http\Request;
use App\Models\Proyecto\Proyecto;
use App\Http\Controllers\Controller;
use App\Models\Personal\Empleado;
use Illuminate\Support\Facades\Crypt;

class VerificarConstancia extends Controller
{

    // Método para mostrar la vista de verificación de constancia
    public function index ($hashedProjectId, $hashedEmployeeId) {
        try {
            // Desencriptar los IDs
            $proyectoId = unserialize(Crypt::decryptString($hashedProjectId));
            $empleadoId = unserialize(Crypt::decryptString($hashedEmployeeId));

            // obtener el nombre del proyecto
            $proyecto = Proyecto::where('id', $proyectoId)
                ->first();

                // obtener el nombre del empleado
            $empleado = Empleado::where('id', $empleadoId)
                ->first();


            // calular las horas del empleado en el proyecto
            $horas = Empleado::where('id', $empleadoId)
                ->first()
                ->actividades()
                ->where('proyecto_id', $proyectoId)
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

    
            // verificar si el empleado esta participando en el proyecto
            $proyecto_empleado = Proyecto::where('id', $proyectoId)
                ->first()
                ->integrantes
                ->where('id', $empleadoId)
                ->first();
    
            if ($proyecto_empleado) {
                // Aquí puedes realizar las acciones necesarias con los IDs desencriptados
                return view('app.docente.verificacion_constancia', [
                    'data' => $data
                ]);
            } else {
                return view('app.docente.verificacion_constancia', [
                    'mensaje' => 'La Constancia no es valida'
                ]);
            }
        } catch (Exception $e) {
            // Si falla la desencriptación, puedes manejar el error
            abort(404, 'Datos inválidos');
        }
    }

}
