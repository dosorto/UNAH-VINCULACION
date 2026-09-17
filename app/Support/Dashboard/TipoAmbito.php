<?php

namespace App\Support\Dashboard;

/**
 * Alcance de los datos que ve un usuario en el panel estadístico.
 *
 * No es un concepto nuevo: formaliza el criterio que ya estaba disperso en
 * HistorialProyecto::autorizarAcceso() y DirectorFacultadCentro\ListProyectos.
 */
enum TipoAmbito: string
{
    /** Toda la UNAH. Roles con proyectos.historial / revision-final, y admin. */
    case Global = 'global';

    /** Un centro o facultad (empleado.centro_facultad_id). */
    case Centro = 'centro';

    /** Un departamento académico (empleado.departamento_academico_id). */
    case Departamento = 'departamento';

    /**
     * Proyectos donde el usuario tiene o tuvo una firma. Es el respaldo para
     * revisores sin centro asignado: sin él verían un panel vacío.
     */
    case Revision = 'revision';

    /** Solo los proyectos propios (empleado_proyecto). */
    case Personal = 'personal';

    /** Sin datos que mostrar. */
    case Ninguno = 'ninguno';

    /**
     * ¿El ámbito abarca proyectos ajenos? Determina si el panel muestra la
     * sección de estadística institucional.
     */
    public function esInstitucional(): bool
    {
        return in_array($this, [self::Global, self::Centro, self::Departamento], true);
    }
}
