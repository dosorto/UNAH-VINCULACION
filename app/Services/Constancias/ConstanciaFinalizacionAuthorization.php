<?php

namespace App\Services\Constancias;

use App\Models\Constancias\ConstanciaFinalizacionProyecto;
use App\Models\Proyecto\Proyecto;
use App\Models\User;

class ConstanciaFinalizacionAuthorization
{
    public function puedeVerProyecto(Proyecto $proyecto, ?User $user): bool
    {
        return (bool) $user && (
            $proyecto->usuarioPuedeGestionarInformeFinal($user)
            || $proyecto->usuarioPuedeAuditarInformeFinal($user)
        );
    }

    public function puedeDescargar(ConstanciaFinalizacionProyecto $constancia, ?User $user): bool
    {
        return $this->puedeVerProyecto($constancia->proyecto, $user);
    }
}
