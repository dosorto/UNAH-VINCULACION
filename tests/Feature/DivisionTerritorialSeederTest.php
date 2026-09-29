<?php

namespace Tests\Feature;

use App\Models\Demografia\Departamento;
use Database\Seeders\Demografia\DepartamentoSeeder;
use Database\Seeders\Demografia\DivisionTerritorialSeeder;
use Database\Seeders\Demografia\MunicipioSeeder;
use Database\Seeders\Demografia\PaisesSeeder;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class DivisionTerritorialSeederTest extends TestCase
{
    use DatabaseTransactions;

    public function test_carga_departamentos_y_municipios_de_cada_pais_sin_duplicar(): void
    {
        $this->assertNotSame('vinculacion', DB::getDatabaseName());

        $this->seed([PaisesSeeder::class, DepartamentoSeeder::class, MunicipioSeeder::class, DivisionTerritorialSeeder::class]);
        $totales = $this->totales();

        // Repetirlos no duplica nada, y MunicipioSeeder ya no confunde La Paz o Colón con los de otros países.
        $this->seed([DivisionTerritorialSeeder::class, MunicipioSeeder::class]);
        $this->assertSame($totales, $this->totales());

        $archivos = glob(database_path('seeders/Demografia/division-territorial/*.json'));
        $this->assertCount(17, $archivos);

        foreach ($archivos as $archivo) {
            $catalogo = json_decode(file_get_contents($archivo), true);
            $paisId = DB::table('pais')->where('codigo_iso', $catalogo['pais'])->value('id');
            $this->assertNotNull($paisId, "No existe el país {$catalogo['pais']}.");

            foreach ($catalogo['departamentos'] as $departamento) {
                $departamentoId = DB::table('departamento')
                    ->where('pais_id', $paisId)
                    ->where('codigo_departamento', $departamento['codigo'])
                    ->value('id');
                $this->assertNotNull($departamentoId, "Falta {$departamento['nombre']} ({$catalogo['pais']}).");

                $cargados = DB::table('municipio')->where('departamento_id', $departamentoId)->whereNull('deleted_at')->pluck('nombre')->all();
                $this->assertEmpty(
                    array_diff(array_column($departamento['municipios'], 'nombre'), $cargados),
                    "Faltan municipios en {$departamento['nombre']} ({$catalogo['pais']})."
                );
            }
        }

        $duplicados = DB::table('municipio')
            ->whereNull('deleted_at')
            ->select('departamento_id', 'nombre')
            ->groupBy('departamento_id', 'nombre')
            ->havingRaw('COUNT(*) > 1')
            ->count();
        $this->assertSame(0, $duplicados);
    }

    public function test_honduras_conserva_sus_departamentos_y_corrige_valle_y_yoro(): void
    {
        $this->seed([PaisesSeeder::class, DepartamentoSeeder::class, MunicipioSeeder::class, DivisionTerritorialSeeder::class]);

        $this->assertSame(18, Departamento::deHonduras()->count());
        $this->assertSame(298, $this->municipiosDeHonduras()->count());

        $this->assertEqualsCanonicalizing(
            ['Nacaome', 'Alianza', 'Amapala', 'Aramecina', 'Caridad', 'Goascorán', 'Langue', 'San Francisco de Coray', 'San Lorenzo'],
            $this->municipiosDeHonduras('Valle')->pluck('municipio.nombre')->all()
        );
        $this->assertEqualsCanonicalizing(
            ['Yoro', 'Arenal', 'El Negrito', 'El Progreso', 'Jocón', 'Morazán', 'Olanchito', 'Santa Rita', 'Sulaco', 'Victoria', 'Yorito'],
            $this->municipiosDeHonduras('Yoro')->pluck('municipio.nombre')->all()
        );
        $this->assertSame(19, $this->municipiosDeHonduras('La Paz')->count());
    }

    private function totales(): array
    {
        return [DB::table('departamento')->count(), DB::table('municipio')->count()];
    }

    private function municipiosDeHonduras(?string $departamento = null)
    {
        return DB::table('municipio')
            ->join('departamento', 'departamento.id', '=', 'municipio.departamento_id')
            ->join('pais', 'pais.id', '=', 'departamento.pais_id')
            ->where('pais.codigo_iso', 'HND')
            ->whereNull('municipio.deleted_at')
            ->whereNull('departamento.deleted_at')
            ->when($departamento, fn ($query) => $query->where('departamento.nombre', $departamento))
            ->select('municipio.nombre');
    }
}
