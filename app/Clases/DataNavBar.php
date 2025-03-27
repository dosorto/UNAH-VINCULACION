<?php

namespace App\Clases;

use App\Models\Proyecto\Proyecto;
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

    // metodo para obtener la cantidad de proyectos en estado de "En revisión"
    public static function obtenerCantidadProyectosEnRevision()
    {
        return Proyecto::query()
            ->whereIn('id', function ($query) {
                $query->select('estadoable_id')
                    ->from('estado_proyecto')
                    ->where('estadoable_type', Proyecto::class) // Asegúrate de filtrar por el modelo Proyecto
                    ->where('tipo_estado_id', TipoEstado::where('nombre', 'En revision')->first()->id)
                    ->where('es_actual', true);
            })
            ->count();
    }

    // metodo para obtener la cantidad de proyectos en estado de "En revisión final"
    public static function obtenerCantidadProyectosEnRevisionFinal()
    {
        return Proyecto::query()
            ->whereIn('id', function ($query) {
                $query->select('estadoable_id')
                    ->from('estado_proyecto')
                    ->where('estadoable_type', Proyecto::class) // Asegúrate de filtrar por el modelo Proyecto
                    ->where('tipo_estado_id', TipoEstado::where('nombre', 'En revision final')->first()->id)
                    ->where('es_actual', true);
            })
            ->count();
    }

    // metodo para obtener todos los informes obtenerCantidadInformesSolicitados
    public static function obtenerCantidadInformesSolicitados()
    {
        return DocumentoProyecto::query()
            ->whereIn('id', function ($query) {
                $query->select('estadoable_id')
                    ->from('estado_proyecto')
                    ->where('estadoable_type', DocumentoProyecto::class)
                    ->where('tipo_estado_id', TipoEstado::where('nombre', 'En revision')->first()->id)
                    ->where('es_actual', true);
            })
            ->count();
    }


    // metodo para obtener la cantidad de proyectos del usuario logueado
    public static function obtenerCantidadProyectosPorFirmar()
    {

        return auth()->user()->empleado->firmaProyectoPendientes()
            ->getQuery()
            ->count();
    }
}
