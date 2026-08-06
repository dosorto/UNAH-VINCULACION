<?php

namespace Tests\Feature;

use Database\Seeders\Demografia\DepartamentoSeeder;
use Database\Seeders\Demografia\MunicipioSeeder;
use Database\Seeders\Demografia\PaisesSeeder;
use Database\Seeders\EjesPrioritariosUnahSeeder;
use Database\Seeders\Proyecto\ProyectoSeeder;
use Database\Seeders\Proyecto\VinculacionTiposAccionSeeder;
use Database\Seeders\UnidadAcademica\UnidadAcademicaSeeder;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class CatalogosFormularioSeederTest extends TestCase
{
    use DatabaseTransactions;

    public function test_los_seeders_del_formulario_son_idempotentes(): void
    {
        $this->assertNotSame('vinculacion', DB::getDatabaseName());

        $this->seed(PaisesSeeder::class);
        $this->seed(PaisesSeeder::class);
        $this->seed(DepartamentoSeeder::class);
        $this->seed(DepartamentoSeeder::class);
        $this->seed(MunicipioSeeder::class);
        $this->seed(MunicipioSeeder::class);
        $this->seed(UnidadAcademicaSeeder::class);
        $this->seed(UnidadAcademicaSeeder::class);
        $this->seed(EjesPrioritariosUnahSeeder::class);
        $this->seed(EjesPrioritariosUnahSeeder::class);
        $this->seed(VinculacionTiposAccionSeeder::class);
        $this->seed(ProyectoSeeder::class);
        $this->seed(ProyectoSeeder::class);

        $this->assertSame(4, DB::table('modalidad')->whereNull('deleted_at')->count());
        $this->assertCatalogoSinDuplicados('modalidad', ['nombre']);
        $this->assertCatalogoSinDuplicados('categorias', ['nombre']);
        $this->assertCatalogoSinDuplicados('ods', ['nombre']);
        $this->assertCatalogoSinDuplicados('pais', ['codigo_iso']);
        $this->assertCatalogoSinDuplicados('departamento', ['pais_id', 'codigo_departamento']);
        $this->assertCatalogoSinDuplicados('municipio', ['departamento_id', 'nombre']);
        $this->assertCatalogoSinDuplicados('campus', ['nombre_campus']);
        $this->assertCatalogoSinDuplicados('centro_facultad', ['campus_id', 'nombre']);
        $this->assertCatalogoSinDuplicados('departamento_academico', ['centro_facultad_id', 'nombre']);
        $this->assertCatalogoSinDuplicados('carrera', ['facultad_centro_id', 'nombre']);
    }

    private function assertCatalogoSinDuplicados(string $tabla, array $columnas): void
    {
        $duplicados = DB::table($tabla)
            ->select($columnas)
            ->selectRaw('COUNT(*) AS total')
            ->when(
                in_array('deleted_at', DB::getSchemaBuilder()->getColumnListing($tabla), true),
                fn ($query) => $query->whereNull('deleted_at'),
            )
            ->groupBy($columnas)
            ->havingRaw('COUNT(*) > 1')
            ->count();

        $this->assertSame(0, $duplicados, "El catálogo {$tabla} contiene registros duplicados.");
    }
}
