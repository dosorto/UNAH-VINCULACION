<?php

namespace App\Support\Dashboard\Tramites;

use App\Models\PpsServicioSocial;

/**
 * Prácticas profesionales y servicio social (FORM-DVUS-014).
 *
 * Su itinerario se agota en la autorización: el coordinador de carrera aprueba
 * y se notifica a la institución receptora. No hay informe intermedio ni de
 * cierre, así que un registro autorizado está terminado, no a medias — y por
 * eso declara solo dos fases en vez de heredar las cinco del proyecto.
 *
 * No se puede acotar por centro: `pps_servicio_social` guarda la facultad como
 * texto libre, sin clave foránea. Al no declarar columna ni pivote, el panel
 * sabe que sus cifras son institucionales aunque el ámbito sea de centro.
 */
class FamiliaPps extends FamiliaPorEstados
{
    public function __construct()
    {
        parent::__construct(
            clave: 'pps',
            etiqueta: 'Prácticas y servicio social',
            modelo: PpsServicioSocial::class,
            formularios: ['FORM-DVUS-014'],
            fases: [
                [
                    'clave' => 'solicitud',
                    'etiqueta' => 'En autorización',
                    'tono' => 'info',
                    'estados' => [
                        'Esperando documento', 'Coordinador Proyecto', 'Enlace Vinculacion',
                        'Jefe Departamento', 'Director centro', 'En revision', 'En revision final',
                        'Subsanacion',
                    ],
                ],
                [
                    'clave' => 'autorizado',
                    'etiqueta' => 'Autorizado',
                    'tono' => 'exito',
                    'estados' => ['Aprobado', 'Inscrito', 'En curso', 'Finalizado'],
                ],
            ],
        );
    }
}
