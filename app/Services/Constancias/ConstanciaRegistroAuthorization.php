<?php

namespace App\Services\Constancias;

use App\Models\Constancias\ConstanciaRegistroProyecto;
use App\Models\Proyecto\Proyecto;
use App\Models\User;

class ConstanciaRegistroAuthorization
{
    public function puedeVerProyecto(Proyecto $proyecto, ?User $user): bool
    {
        return (bool) $user && (
            $proyecto->usuarioPuedeGestionarInformeFinal($user)
            || $proyecto->usuarioPuedeAuditarInformeFinal($user)
            || $proyecto->usuarioEsParticipante($user)
        );
    }

    public function puedeDescargar(ConstanciaRegistroProyecto $constancia, ?User $user): bool
    {
        return $this->puedeVerProyecto($constancia->proyecto, $user);
    }
}
