<?php

namespace App\Support\Dashboard\Tramites;

use App\Support\Dashboard\AmbitoPanel;

/**
 * Una familia de trámites del panel estadístico.
 *
 * NEXO no digitaliza un formulario, sino decenas, y cada uno trae su propio
 * itinerario: un proyecto de vinculación recorre inscripción, informe
 * intermedio —si su flujo lo define— e informe final; una práctica profesional
 * se agota en la autorización del coordinador; una acción de educación no
 * formal repite el ciclo entero dentro de cada uno de sus procesos.
 *
 * Medirlos a todos contra un ciclo único los describe mal: da por incompleto
 * al que ya terminó. Por eso el panel no conoce formularios concretos, sino
 * familias registradas en config('nexo.dashboard.familias_tramite'), y se
 * limita a preguntarle a cada una por su itinerario y sus conteos.
 *
 * Dar de alta un formulario nuevo es añadir una familia a esa lista. Si sigue
 * el patrón habitual del sistema —estado actual en `estado_proyecto` y firmas
 * en `flujos_aprobacion`— basta con declararla con FamiliaPorEstados, sin
 * escribir una clase.
 */
interface FamiliaTramite
{
    /** Identificador estable, usado en claves de caché y en el DOM. */
    public function clave(): string;

    /** Nombre para la interfaz, en plural: "Proyectos de vinculación". */
    public function etiqueta(): string;

    /**
     * Códigos de formulario que cubre, para poder rastrear a qué familia
     * pertenece un registro concreto.
     *
     * @return list<string>
     */
    public function formularios(): array;

    /**
     * Fases por las que pasa este trámite, en orden.
     *
     * Son solo las suyas: una práctica profesional declara dos y ahí termina.
     *
     * @return list<array{clave:string,etiqueta:string,tono:string}>
     */
    public function itinerario(): array;

    /**
     * Cuántos trámites hay en cada fase del itinerario.
     *
     * @return array<string,int> clave de fase => cantidad
     */
    public function conteos(AmbitoPanel $ambito): array;

    /** Total de trámites vivos de esta familia dentro del ámbito. */
    public function total(AmbitoPanel $ambito): int;

    /**
     * Trámites que aún no han entrado al flujo (borradores). Se cuentan aparte:
     * como fase no dicen nada, porque el recorrido todavía no ha empezado.
     */
    public function sinIniciar(AmbitoPanel $ambito): int;

    /**
     * ¿Puede el ámbito acotar esta familia?
     *
     * PPS guarda la facultad como texto libre, así que no se puede filtrar por
     * centro de forma fiable; devolver false permite al panel avisarlo en vez
     * de mostrar un cero engañoso.
     */
    public function admiteAmbito(AmbitoPanel $ambito): bool;
}
