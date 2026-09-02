<?php

namespace Tests\Feature;

use App\Livewire\UnidadAcademica\TipoAnexo\TipoAnexoList;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class TipoAnexoCatalogoAdminTest extends TestCase
{
    public function test_el_catalogo_tiene_ruta_protegida_en_unidad_academica(): void
    {
        $route = Route::getRoutes()->getByName('unidad-academica.tipo-anexo');

        $this->assertNotNull($route);
        $this->assertSame(TipoAnexoList::class, $route->getActionName());
        $this->assertContains('auth', $route->gatherMiddleware());
        $this->assertContains('can:unidad-academica.tipo-anexo', $route->gatherMiddleware());
    }

    public function test_el_navbar_muestra_tipos_de_anexos_en_unidad_academica(): void
    {
        $items = collect(config('navbar'))
            ->flatMap(fn (array $section) => $section['items'] ?? []);

        $unidadAcademica = $items->firstWhere('titulo', 'Unidad Academica');

        $this->assertNotNull($unidadAcademica);
        $this->assertContains('unidad-academica.tipo-anexo', $unidadAcademica['routes']);
        $this->assertContains('unidad-academica.tipo-anexo', $unidadAcademica['permisos']);
        $this->assertTrue(collect($unidadAcademica['children'])->contains(
            fn (array $child) => $child['texto'] === 'Tipos de anexos'
                && $child['route'] === 'unidad-academica.tipo-anexo'
                && $child['permiso'] === 'unidad-academica.tipo-anexo',
        ));
    }

    public function test_la_vista_del_catalogo_compila_y_expone_sus_acciones(): void
    {
        $path = resource_path('views/livewire/unidad-academica/tipo-anexo/tipo-anexo-list.blade.php');
        $source = File::get($path);

        Blade::compileString($source);

        $this->assertStringContainsString('Tipos de anexos', $source);
        $this->assertStringContainsString('wire:click="openCreate"', $source);
        $this->assertStringContainsString('wire:click="openEdit(', $source);
        $this->assertStringContainsString('$wire.delete(', $source);
        $this->assertStringContainsString('$wire.restore(', $source);
    }
}
