<?php

namespace Tests\Feature;

use Tests\TestCase;

/**
 * Los campos obligatorios del INF-001 llevan asterisco rojo en su etiqueta.
 * Ese asterisco es sólo texto en el Blade, así que se desincroniza en silencio
 * cuando alguien cambia una regla de validación y no toca la etiqueta: el
 * usuario rellena el formulario, pulsa guardar y recibe un error por un campo
 * que nada indicaba como obligatorio.
 *
 * Esta prueba compara ambas fuentes y falla si divergen, en cualquier sentido.
 * Recorre todo campo validado con forma `propiedad.campo` —modales, registro
 * manual de estudiantes y pasos del wizard—, no sólo los `*Modal`: la primera
 * versión se limitaba a esos y dejó pasar el registro manual, que valida
 * `estudianteManual.*`.
 */
class InformeFinalInf001AsteriscosObligatoriosTest extends TestCase
{
    private const COMPONENTE = 'app/Livewire/Proyectos/InformeFinal/EditInformeFinalProyecto.php';

    private const VISTA = 'resources/views/livewire/proyectos/informe-final/edit-informe-final-proyecto.blade.php';

    public function test_cada_campo_obligatorio_lleva_asterisco(): void
    {
        [$faltan, , $analizados] = $this->comparar();

        // 43 desde que la sección V sigue el formato (sin aportes ni compromisos cumplidos por
        // contraparte), el total de la valoración se toma del apartado 9 (solo lectura) y los
        // responsables de una acción emergente se eligen de una lista (no es un input).
        $this->assertGreaterThanOrEqual(43, $analizados, 'La comparación dejó de encontrar campos; revisa los patrones.');

        $this->assertSame([], $faltan, "Campos obligatorios sin asterisco:\n  - ".implode("\n  - ", $faltan));
    }

    public function test_ningun_campo_opcional_se_anuncia_como_obligatorio(): void
    {
        [, $sobran] = $this->comparar();

        $this->assertSame([], $sobran, "Campos opcionales marcados con asterisco:\n  - ".implode("\n  - ", $sobran));
    }

    public function test_el_registro_manual_de_estudiantes_esta_cubierto(): void
    {
        // Regresión del punto ciego: si el patrón vuelve a ignorar
        // `estudianteManual.*`, esta prueba lo delata.
        [, , , $cubiertos] = $this->comparar();

        foreach (['nombres', 'apellidos', 'numero_cuenta', 'sexo', 'carrera', 'correo', 'horas_dedicadas'] as $campo) {
            $this->assertContains("estudianteManual.{$campo}", $cubiertos);
        }
    }

    /**
     * @return array{0: list<string>, 1: list<string>, 2: int, 3: list<string>}
     */
    private function comparar(): array
    {
        $faltan = [];
        $sobran = [];
        $cubiertos = [];
        $vista = file_get_contents(base_path(self::VISTA));

        foreach ($this->obligatoriedadPorCampo() as $campo => $obligatorio) {
            $etiqueta = $this->etiquetaDe($vista, $campo);

            if ($etiqueta === null) {
                continue; // no se pinta en esta vista, o no tiene <label> propia
            }

            $cubiertos[] = $campo;
            $marcado = str_contains($etiqueta, '>*<');

            if ($obligatorio && ! $marcado) {
                $faltan[] = $campo;
            }

            if (! $obligatorio && $marcado) {
                $sobran[] = $campo;
            }
        }

        return [$faltan, $sobran, count($cubiertos), $cubiertos];
    }

    /**
     * Un campo es obligatorio si ALGUNA de sus reglas lo exige: el
     * autoguardado usa `nullable` para admitir borradores, pero al avanzar de
     * paso o guardar el modal sí se exige. Los `required_if` y
     * `Rule::requiredIf` cuentan como obligatorios; su etiqueta muestra el
     * asterisco en el caso en que aplican.
     *
     * @return array<string, bool>
     */
    private function obligatoriedadPorCampo(): array
    {
        $componente = file_get_contents(base_path(self::COMPONENTE));

        preg_match_all(
            "/'([a-zA-Z]+)\.([a-z_]+)'\s*=>\s*(\[[^\]]*\]|'[^']*')/",
            $componente,
            $coincidencias,
            PREG_SET_ORDER
        );

        $obligatorio = [];
        foreach ($coincidencias as [, $propiedad, $campo, $reglas]) {
            $clave = "{$propiedad}.{$campo}";
            $exige = (bool) preg_match('/(?<![a-z_])required(_if|_with|_unless)?(?![a-z_])/', $reglas);
            $obligatorio[$clave] = ($obligatorio[$clave] ?? false) || $exige;
        }

        return $obligatorio;
    }

    /** La <label> inmediatamente anterior al input, si no pertenece a otro campo. */
    private function etiquetaDe(string $vista, string $campo): ?string
    {
        $patron = '/wire:model(?:\.[a-z]+)*="'.preg_quote($campo, '/').'"/';

        if (! preg_match($patron, $vista, $coincidencia, PREG_OFFSET_CAPTURE)) {
            return null;
        }

        $posicion = $coincidencia[0][1];
        $anterior = substr($vista, max(0, $posicion - 900), min(900, $posicion));
        $inicio = strrpos($anterior, '<label');

        if ($inicio === false) {
            return null;
        }

        $etiqueta = substr($anterior, $inicio);

        return str_contains($etiqueta, 'wire:model') ? null : $etiqueta;
    }
}
