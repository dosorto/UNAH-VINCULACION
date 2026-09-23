<?php

namespace App\Services\Dashboard;

use App\Support\Dashboard\Formularios\FormularioPanel;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Formularios que conoce el panel, leídos de config('nexo.dashboard.formularios').
 *
 * Cada entrada indica su clase y los parámetros de su constructor. Dar de alta
 * un formulario nuevo es añadir una entrada; ninguna vista ni servicio del
 * panel cambia.
 */
class RegistroFormularios
{
    /** @var Collection<int, FormularioPanel>|null */
    private ?Collection $formularios = null;

    /** @var array<string,string>|null */
    private ?array $tiposAccion = null;

    /** @return Collection<int, FormularioPanel> */
    public function todos(): Collection
    {
        return $this->formularios ??= collect(config('nexo.dashboard.formularios', []))
            ->map(function (array $entrada): mixed {
                $clase = $entrada['clase'] ?? null;
                unset($entrada['clase']);

                return $clase ? app()->make($clase, $entrada) : null;
            })
            ->filter(fn ($formulario): bool => $formulario instanceof FormularioPanel)
            ->values();
    }

    public function porCodigo(?string $codigo): ?FormularioPanel
    {
        return $codigo === null
            ? null
            : $this->todos()->first(fn (FormularioPanel $f): bool => $f->codigo() === $codigo);
    }

    /** Nombre del tipo de acción para agrupar; el catálogo vive en la base. */
    public function etiquetaTipoAccion(?string $codigo): string
    {
        if ($codigo === null || $codigo === '') {
            return 'Sin tipo de acción';
        }

        $this->tiposAccion ??= DB::table('vinculacion_tipos_accion')->pluck('nombre', 'codigo')->all();

        return $this->tiposAccion[$codigo] ?? $codigo;
    }
}
