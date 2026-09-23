<?php

namespace App\Support\Dashboard\Formularios;

use App\Support\Dashboard\AmbitoPanel;
use Illuminate\Support\Collection;

/**
 * Un formulario tal como lo ve el panel.
 *
 * NEXO tiene más de treinta formularios, cada uno con su flujo, sus etapas y
 * sus nombres de estado. El panel no conoce ninguno: los recibe de
 * config('nexo.dashboard.formularios') y solo les pregunta tres cosas que
 * todos comparten:
 *
 *  - en qué estado general está cada trámite (EstadoGeneral),
 *  - en qué etapa está esperando ahora y desde cuándo,
 *  - qué etapas tiene su flujo configurado.
 *
 * Los formularios que usan el motor común (flujos_aprobacion, firma_proyecto y
 * estado_proyecto) se declaran en la configuración sin escribir una clase
 * (FormularioMotorComun). Solo necesitan una propia los que tienen un motor
 * distinto, como ENF.
 */
interface FormularioPanel
{
    public const INSCRIPCION = 'inscripcion';

    public const INFORME_INTERMEDIO = 'informe_intermedio';

    public const CIERRE = 'cierre';

    /** Procesos en los que puede estar un trámite, en orden. */
    public const PROCESOS = [
        self::INSCRIPCION => 'Inscripción',
        self::INFORME_INTERMEDIO => 'Informe intermedio',
        self::CIERRE => 'Cierre',
    ];

    /** Código estable del formulario: «FORM-DVUS-015». */
    public function codigo(): string;

    /** Nombre corto para la interfaz. */
    public function nombre(): string;

    /**
     * Código de `vinculacion_tipos_accion` al que pertenece, para agrupar en
     * el panel. Null si el formulario no tiene tipo de acción.
     */
    public function tipoAccion(): ?string;

    /**
     * ¿Puede acotarse a este ámbito? Un formulario que guarda la unidad
     * académica como texto libre no puede, y el panel lo advierte en vez de
     * mostrar una cifra institucional como si fuera del centro.
     */
    public function admiteAmbito(AmbitoPanel $ambito): bool;

    /**
     * Cuántos trámites hay en cada estado general.
     *
     * @return array<string,int> EstadoGeneral::CLAVES => cantidad
     */
    public function conteos(AmbitoPanel $ambito): array;

    /**
     * Trámites que esperan una decisión ahora, uno por trámite.
     *
     * `tramite` identifica el expediente (una fila por expediente y proceso).
     * `heredado` marca los expedientes que siguen el recorrido anterior por
     * cargos (sin adaptar al flujo); su `etapa` es el cargo.
     *
     * @return Collection<int, array{tramite:string,proceso:string,etapa_id:?int,etapa:string,orden:?int,rol:?string,heredado:bool,dias:int}>
     */
    public function esperando(AmbitoPanel $ambito): Collection;

    /**
     * Etapas vigentes del flujo configurado, en orden.
     *
     * @return Collection<int, array{id:int,nombre:string,orden:int,rol:?string,procesos:list<string>}>
     */
    public function etapasConfiguradas(): Collection;
}
