<?php

namespace Database\Seeders\Demografia;

use App\Models\Demografia\Pais;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Carga los departamentos (provincias, estados, regiones…) y municipios (cantones, comunas,
 * distritos…) del resto de países del catálogo a partir de los JSON de division-territorial/.
 * Honduras sigue en DepartamentoSeeder y MunicipioSeeder.
 *
 * Es idempotente: empareja por código oficial y, si no coincide, por nombre sin tildes, dentro
 * del mismo país o departamento. No modifica ni restaura registros existentes (tampoco los
 * eliminados). Inserta con el query builder para no generar miles de entradas en el log de actividad.
 */
class DivisionTerritorialSeeder extends Seeder
{
    public function run(): void
    {
        foreach (glob(__DIR__.'/division-territorial/*.json') as $archivo) {
            $catalogo = json_decode(file_get_contents($archivo), true, 512, JSON_THROW_ON_ERROR);
            $paisId = Pais::where('codigo_iso', $catalogo['pais'])->value('id');

            if (! $paisId) {
                echo "El país {$catalogo['pais']} no se encontró en la base de datos.\n";
                continue;
            }

            DB::transaction(fn () => $this->sembrarPais($paisId, $catalogo['departamentos']));
        }
    }

    private function sembrarPais(int $paisId, array $departamentos): void
    {
        $existentes = $this->indexar(
            DB::table('departamento')->where('pais_id', $paisId)->get(['id', 'nombre', 'codigo_departamento as codigo'])
        );

        foreach ($departamentos as $departamento) {
            $departamentoId = $this->buscar($existentes, $departamento)?->id
                ?? DB::table('departamento')->insertGetId([
                    'pais_id' => $paisId,
                    'nombre' => $departamento['nombre'],
                    'codigo_departamento' => $departamento['codigo'],
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

            $this->sembrarMunicipios($departamentoId, $departamento['municipios']);
        }
    }

    private function sembrarMunicipios(int $departamentoId, array $municipios): void
    {
        $existentes = $this->indexar(
            DB::table('municipio')->where('departamento_id', $departamentoId)->get(['id', 'nombre', 'codigo_municipio as codigo'])
        );

        $nuevos = collect($municipios)
            ->reject(fn (array $municipio) => $this->buscar($existentes, $municipio))
            ->map(fn (array $municipio) => [
                'departamento_id' => $departamentoId,
                'nombre' => $municipio['nombre'],
                'codigo_municipio' => $municipio['codigo'],
                'created_at' => now(),
                'updated_at' => now(),
            ]);

        foreach ($nuevos->chunk(500) as $lote) {
            DB::table('municipio')->insert($lote->values()->all());
        }
    }

    /** @return array{codigo: Collection, nombre: Collection} */
    private function indexar(Collection $filas): array
    {
        return [
            'codigo' => $filas->whereNotNull('codigo')->keyBy(fn ($fila) => (int) $fila->codigo),
            'nombre' => $filas->keyBy(fn ($fila) => $this->clave($fila->nombre)),
        ];
    }

    private function buscar(array $existentes, array $registro): ?object
    {
        return ($registro['codigo'] !== null ? $existentes['codigo']->get($registro['codigo']) : null)
            ?? $existentes['nombre']->get($this->clave($registro['nombre']));
    }

    private function clave(string $nombre): string
    {
        return Str::of($nombre)->ascii()->lower()->squish()->value();
    }
}
