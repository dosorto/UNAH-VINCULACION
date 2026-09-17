<?php

namespace App\Services\Dashboard;

use App\Support\Dashboard\AmbitoPanel;
use App\Support\Dashboard\Tramites\FamiliaTramite;
use Illuminate\Support\Collection;

/**
 * Familias de trámite que el panel conoce.
 *
 * El panel no menciona ningún formulario: pregunta aquí qué familias hay y a
 * cada una por su itinerario. Dar de alta uno de los formularios que quedan por
 * digitalizar es añadir su familia a config('nexo.dashboard.familias_tramite');
 * ninguna vista ni servicio del panel cambia.
 */
class RegistroFamiliasTramite
{
    /** @var Collection<int, FamiliaTramite>|null */
    private ?Collection $familias = null;

    /** @return Collection<int, FamiliaTramite> */
    public function todas(): Collection
    {
        return $this->familias ??= collect(config('nexo.dashboard.familias_tramite', []))
            ->map(fn (string $clase): mixed => app($clase))
            ->filter(fn ($familia): bool => $familia instanceof FamiliaTramite)
            ->values();
    }

    public function porClave(string $clave): ?FamiliaTramite
    {
        return $this->todas()->first(fn (FamiliaTramite $f): bool => $f->clave() === $clave);
    }

    /** La familia a la que pertenece un código de formulario, si alguna lo declara. */
    public function porFormulario(string $codigoFormulario): ?FamiliaTramite
    {
        return $this->todas()->first(
            fn (FamiliaTramite $f): bool => in_array($codigoFormulario, $f->formularios(), true)
        );
    }

    /**
     * Itinerario y conteos de cada familia, listos para pintar un carril por
     * familia. Se omiten las que no tienen ni un trámite en el ámbito, para no
     * llenar el panel de recorridos vacíos.
     *
     * @return list<array{clave:string,etiqueta:string,total:int,sin_iniciar:int,ambito_parcial:bool,fases:list<array{clave:string,etiqueta:string,tono:string,valor:int}>}>
     */
    public function carriles(AmbitoPanel $ambito, bool $incluirVacias = false): array
    {
        return $this->todas()
            ->map(function (FamiliaTramite $familia) use ($ambito): array {
                $conteos = $familia->conteos($ambito);

                return [
                    'clave' => $familia->clave(),
                    'etiqueta' => $familia->etiqueta(),
                    'total' => $familia->total($ambito),
                    'sin_iniciar' => $familia->sinIniciar($ambito),
                    // Si la familia no sabe acotarse al centro, la cifra es
                    // institucional y el panel debe decirlo.
                    'ambito_parcial' => ! $familia->admiteAmbito($ambito),
                    'fases' => array_map(
                        fn (array $fase): array => $fase + ['valor' => $conteos[$fase['clave']] ?? 0],
                        $familia->itinerario()
                    ),
                ];
            })
            ->filter(fn (array $carril): bool => $incluirVacias || $carril['total'] > 0)
            ->values()
            ->all();
    }
}
