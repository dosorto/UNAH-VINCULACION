<?php

namespace App\Http\Controllers\Docente;

use Exception;
use Illuminate\Http\Request;
use App\Models\Proyecto\Proyecto;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Crypt;

class VerificarConstancia extends Controller
{

    // Método para mostrar la vista de verificación de constancia
    public function index ($hashedProjectId, $hashedEmployeeId) {
        try {
            // Desencriptar los IDs
            $proyectoId = unserialize(Crypt::decryptString($hashedProjectId));
            $empleadoId = unserialize(Crypt::decryptString($hashedEmployeeId));
    
            // verificar si el empleado esta participando en el proyecto
            $proyecto_empleado = Proyecto::where('id', $proyectoId)
                ->first()
                ->integrantes
                ->where('id', $empleadoId)
                ->first();
    
            if ($proyecto_empleado) {
                // Aquí puedes realizar las acciones necesarias con los IDs desencriptados
                return view('app.docente.verificacion_constancia', [
                    'mensaje' => 'El empleado es valido en el proyecto'
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
