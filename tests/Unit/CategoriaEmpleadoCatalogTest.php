<?php

namespace Tests\Unit;

use App\Models\Personal\CategoriaEmpleado;
use Database\Seeders\UnidadAcademica\CategoriaEmpleadoSeeder;
use Illuminate\Database\Eloquent\SoftDeletes;
use PHPUnit\Framework\TestCase;

class CategoriaEmpleadoCatalogTest extends TestCase
{
    public function test_seeder_contiene_las_categorias_existentes_del_sistema(): void
    {
        $this->assertSame([
            'Auxiliar',
            'Titular I',
            'Titular II',
            'Titular III',
            'Titular IV',
            'Titular V',
            'Profesores x hora',
            'Profesores horarios',
            'Administrativo',
            'Servicios',
            'Asistentes técnicos laboratorios / Instructores',
        ], array_column(CategoriaEmpleadoSeeder::CATEGORIAS, 'nombre'));
    }

    public function test_modelo_de_categoria_utiliza_borrado_logico(): void
    {
        $this->assertContains(SoftDeletes::class, class_uses_recursive(CategoriaEmpleado::class));
    }

    public function test_catalogo_esta_registrado_en_unidad_academica(): void
    {
        $navbar = require dirname(__DIR__, 2).'/config/navbar.php';
        $unidadAcademica = collect($navbar[0]['items'])
            ->firstWhere('titulo', 'Unidad Academica');

        $this->assertNotNull($unidadAcademica);
        $this->assertContains('categorias-empleado', $unidadAcademica['routes']);
        $this->assertContains('unidad-academica.categoria', $unidadAcademica['permisos']);
        $this->assertContains(
            ['texto' => 'Categorías', 'route' => 'categorias-empleado', 'permiso' => 'unidad-academica.categoria'],
            $unidadAcademica['children'],
        );
    }

    public function test_ruta_y_permiso_del_catalogo_estan_declarados(): void
    {
        $routes = file_get_contents(dirname(__DIR__, 2).'/routes/web.php');
        $permissions = file_get_contents(dirname(__DIR__, 2).'/database/seeders/Personal/PermisosSeeder.php');

        $this->assertStringContainsString("Route::get('categorias-empleado', CategoriaList::class)", $routes);
        $this->assertStringContainsString("->middleware('can:unidad-academica.categoria')", $routes);
        $this->assertStringContainsString("'unidad-academica.categoria'", $permissions);
    }

    public function test_el_catalogo_protege_categorias_asignadas_a_empleados(): void
    {
        $component = file_get_contents(
            dirname(__DIR__, 2).'/app/Livewire/UnidadAcademica/Categoria/CategoriaList.php'
        );

        $this->assertStringContainsString('$categoria->empleados()->exists()', $component);
        $this->assertStringContainsString("Rule::unique('categoria', 'nombre')", $component);
    }

    public function test_migracion_normaliza_duplicados_y_protege_el_nombre_como_unico(): void
    {
        $migration = file_get_contents(
            dirname(__DIR__, 2).'/database/migrations/2026_08_21_000001_normalize_categoria_empleado_catalog.php'
        );

        $this->assertStringContainsString("->whereIn('categoria_id', \$idsDuplicados)", $migration);
        $this->assertStringContainsString("->update(['categoria_id' => \$principal->id])", $migration);
        $this->assertStringContainsString("\$table->unique('nombre'", $migration);
    }
}
