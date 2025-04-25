<?php

namespace App\Services\Correos;

use App\Models\Estado\TipoEstado;

class Mensajes
{
    // Este método devuelve el mensaje según el tipo de estado
    public function obtenerMensaje(TipoEstado $tipoEstado): string
    {
        // Aquí puedes definir lógicamente los mensajes por tipo de estado.
        switch ($tipoEstado->nombre) {
            case 'En progreso':
                return 'El proyecto está actualmente en progreso. ¡Sigue adelante!';
            case 'Completado':
                return '¡Felicitaciones! El proyecto ha sido completado con éxito.';
            case 'Cancelado':
                return 'El proyecto ha sido cancelado. Por favor, revisa los detalles para más información.';
            default:
                return 'El estado del proyecto ha sido actualizado. Consulta más detalles.';
        }
    }
}
