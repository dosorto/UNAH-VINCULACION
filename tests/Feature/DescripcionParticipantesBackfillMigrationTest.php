<?php

namespace Tests\Feature;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class DescripcionParticipantesBackfillMigrationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config([
            'database.default' => 'sqlite',
            'database.connections.sqlite' => [
                'driver' => 'sqlite',
                'database' => ':memory:',
                'prefix' => '',
                'foreign_key_constraints' => true,
            ],
        ]);

        DB::purge('sqlite');

        Schema::create('proyecto', function (Blueprint $table): void {
            $table->id();
            $table->longText('descripcion_participantes')->nullable();
            $table->longText('participacion_unah')->nullable();
            $table->timestamp('deleted_at')->nullable();
        });
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('proyecto');
        DB::purge('sqlite');

        parent::tearDown();
    }

    public function test_copia_el_texto_heredado_sin_sobrescribir_informacion_nueva(): void
    {
        DB::table('proyecto')->insert([
            ['id' => 1, 'descripcion_participantes' => 'Participación heredada', 'participacion_unah' => null, 'deleted_at' => null],
            ['id' => 2, 'descripcion_participantes' => '   ', 'participacion_unah' => null, 'deleted_at' => null],
            ['id' => 3, 'descripcion_participantes' => null, 'participacion_unah' => null, 'deleted_at' => null],
            ['id' => 4, 'descripcion_participantes' => 'Texto anterior', 'participacion_unah' => 'Texto corregido', 'deleted_at' => null],
            ['id' => 5, 'descripcion_participantes' => 'Otra participación', 'participacion_unah' => ' ', 'deleted_at' => null],
            ['id' => 6, 'descripcion_participantes' => 'Proyecto eliminado', 'participacion_unah' => null, 'deleted_at' => '2026-08-11 00:00:00'],
        ]);

        $this->migration()->up();

        $this->assertSame('Participación heredada', DB::table('proyecto')->where('id', 1)->value('participacion_unah'));
        $this->assertNull(DB::table('proyecto')->where('id', 2)->value('participacion_unah'));
        $this->assertNull(DB::table('proyecto')->where('id', 3)->value('participacion_unah'));
        $this->assertSame('Texto corregido', DB::table('proyecto')->where('id', 4)->value('participacion_unah'));
        $this->assertSame('Otra participación', DB::table('proyecto')->where('id', 5)->value('participacion_unah'));
        $this->assertSame('Proyecto eliminado', DB::table('proyecto')->where('id', 6)->value('participacion_unah'));
    }

    public function test_reversion_limpia_solo_valores_que_siguen_iguales_al_texto_heredado(): void
    {
        DB::table('proyecto')->insert([
            ['id' => 1, 'descripcion_participantes' => 'Texto heredado', 'participacion_unah' => null, 'deleted_at' => null],
            ['id' => 2, 'descripcion_participantes' => 'Otro texto heredado', 'participacion_unah' => null, 'deleted_at' => null],
            ['id' => 3, 'descripcion_participantes' => 'Texto anterior', 'participacion_unah' => 'Texto nuevo', 'deleted_at' => null],
        ]);

        $migration = $this->migration();
        $migration->up();

        DB::table('proyecto')->where('id', 1)->update([
            'participacion_unah' => 'Corrección posterior',
        ]);

        $migration->down();

        $this->assertSame('Corrección posterior', DB::table('proyecto')->where('id', 1)->value('participacion_unah'));
        $this->assertNull(DB::table('proyecto')->where('id', 2)->value('participacion_unah'));
        $this->assertSame('Texto nuevo', DB::table('proyecto')->where('id', 3)->value('participacion_unah'));
    }

    private function migration(): object
    {
        return require database_path(
            'migrations/2026_08_11_000001_backfill_descripcion_participantes_to_participacion_unah.php'
        );
    }
}
