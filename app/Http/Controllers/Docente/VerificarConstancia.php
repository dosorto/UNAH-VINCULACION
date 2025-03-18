<?php

namespace App\Http\Controllers\Docente;

use Exception;
use Illuminate\Http\Request;
use App\Models\Proyecto\Proyecto;
use App\Http\Controllers\Controller;
use App\Models\Personal\Empleado;
use Illuminate\Support\Facades\Crypt;
use App\Models\Personal\EmpleadoProyecto;

class VerificarConstancia extends Controller
{

    // Método para mostrar la vista de verificación de constancia
    public function index($hash)
    {
        try {
            // Desencriptar los IDs
            $empleadoProyecto = EmpleadoProyecto::where('hash', $hash)->firstOrFail();
            // obtener el nombre del proyecto
            $proyecto = $empleadoProyecto->proyecto;
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




            // Aquí puedes realizar las acciones necesarias con los IDs desencriptados
            return view('app.docente.verificacion_constancia', [
                'data' => $data
            ]);
        } catch (Exception $e) {
            // Si falla la desencriptación, puedes manejar el error
            abort(404, 'Datos inválidos');
        }
    }
}
