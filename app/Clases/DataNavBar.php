<?php

namespace App\Clases;

use App\Models\Proyecto\Proyecto;
use App\Models\Proyecto\FichaActualizacion;
use App\Models\Proyecto\FirmaProyecto;
use App\Models\Proyecto\ProyectoEstado;
use App\Models\Estado\TipoEstado;
use App\Models\Proyecto\DocumentoProyecto;

class DataNavBar
{
    public static function obtenerEnlaces()
    {
        return [
            ['nombre' => 'Inicio', 'url' => '/'],
            ['nombre' => 'Acerca de', 'url' => '/acerca'],
            ['nombre' => 'Contacto', 'url' => '/contacto']
        ];
    }


    // metodo para obtner toda la cantidad de proyectos
    public static function obtenerCantidadProyectos()
    {
        return Proyecto::count();
    }

    /// metodo para obtener la cantidad de proyectos en estado de "En revisión"
public static function obtenerCantidadProyectosEnRevision()
{
    $tipoEstado = TipoEstado::where('nombre', 'En revision')->first();
    
    if (!$tipoEstado) {
        return 0; // Return 0 if the estado doesn't exist
    }
    
    return Proyecto::query()
        ->whereIn('id', function ($query) use ($tipoEstado) {
            $query->select('estadoable_id')
                ->from('estado_proyecto')
                ->where('estadoable_type', Proyecto::class)
                ->where('tipo_estado_id', $tipoEstado->id)
                ->where('es_actual', true);
        })
        ->count();
}

// metodo para obtener la cantidad de proyectos en estado de "En revisión final"
public static function obtenerCantidadProyectosEnRevisionFinal()
{
    $tipoEstado = TipoEstado::where('nombre', 'En revision final')->first();
    
    if (!$tipoEstado) {
        return 0; // Return 0 if the estado doesn't exist
    }
    
    return Proyecto::query()
        ->whereIn('id', function ($query) use ($tipoEstado) {
            $query->select('estadoable_id')
                ->from('estado_proyecto')
                ->where('estadoable_type', Proyecto::class)
                ->where('tipo_estado_id', $tipoEstado->id)
                ->where('es_actual', true);
        })
        ->count();
}

// metodo para obtener todos los informes obtenerCantidadInformesSolicitados
public static function obtenerCantidadInformesSolicitados()
{
    $tipoEstado = TipoEstado::where('nombre', 'En revision')->first();
    
    if (!$tipoEstado) {
        return 0; // Return 0 if the estado doesn't exist
    }
    
    return DocumentoProyecto::query()
        ->whereIn('id', function ($query) use ($tipoEstado) {
            $query->select('estadoable_id')
                ->from('estado_proyecto')
                ->where('estadoable_type', DocumentoProyecto::class)
                ->where('tipo_estado_id', $tipoEstado->id)
                ->where('es_actual', true);
        })
        ->count();
}


    // metodo para obtener la cantidad de proyectos del usuario logueado
    public static function obtenerCantidadProyectosPorFirmar()
    {
        $user = auth()->user();

        if (! $user) {
            return 0;
        }

        $activeRoleName = $user->activeRole?->name;
        $empleadoId = $user->empleado?->id;

        return FirmaProyecto::query()
            ->join('cargo_firma', 'firma_proyecto.cargo_firma_id', '=', 'cargo_firma.id')
            ->where('firma_proyecto.estado_revision', 'Pendiente')
            ->where('firma_proyecto.firmable_type', '!=', FichaActualizacion::class)
            ->where(function ($query) use ($activeRoleName, $empleadoId) {
                if ($activeRoleName) {
                    $query->whereHas('cargo_firma.tipoCargoFirma', fn ($roleQuery) => $roleQuery->where('nombre', $activeRoleName));
                    return;
                }

                if ($empleadoId) {
                    $query->where('firma_proyecto.empleado_id', $empleadoId);
                    return;
                }

                $query->whereRaw('1 = 0');
            })
            ->where(function ($query) {
                $query->where(function ($projectQuery) {
                    $projectQuery
                        ->where('firma_proyecto.firmable_type', Proyecto::class)
                        ->whereExists(function ($estadoQuery) {
                            $estadoQuery
                                ->selectRaw('1')
                                ->from('estado_proyecto')
                                ->whereColumn('estado_proyecto.estadoable_id', 'firma_proyecto.firmable_id')
                                ->where('estado_proyecto.estadoable_type', Proyecto::class)
                                ->where('estado_proyecto.es_actual', true)
                                ->whereColumn('estado_proyecto.tipo_estado_id', 'cargo_firma.tipo_estado_id');
                        });
                })->orWhere('firma_proyecto.firmable_type', '!=', Proyecto::class);
            })
            ->count();
    }

    // metodo para obtener la cantidad de fichas de actualización por firmar del usuario logueado
    public static function obtenerCantidadFichasPorFirmar()
    {
        return auth()->user()->empleado->firmaProyectoPendientes()
            ->where('firmable_type', FichaActualizacion::class)
            ->count();
    }
}
