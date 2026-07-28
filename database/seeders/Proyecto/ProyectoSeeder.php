<?php

namespace Database\Seeders\Proyecto;

use App\Models\Constancia\TipoConstancia;
use App\Models\Estado\TipoEstado;
use App\Models\Proyecto\CargoFirma;
use App\Models\Proyecto\Categoria;
use App\Models\Proyecto\FlujoAprobacion;
use App\Models\Proyecto\Modalidad;
use App\Models\Proyecto\Od;
use App\Models\Proyecto\Proyecto;
use App\Models\Proyecto\TipoCargoFirma;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ProyectoSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // crear los cargos de las firmas
        $cargosFirmas = collect(config('nexo.cargos_firmas'));

        $cargosFirmas->each(function ($cargo) {
            TipoCargoFirma::firstOrCreate([
                'nombre' => $cargo,
            ]);
        });

        // crear los tipos de estado para el proyecto
        $estadosProyecto = collect(config('nexo.estados_proyecto'));

        $estadosProyecto->each(function ($estado) {
            TipoEstado::firstOrCreate([
                'nombre' => $estado,
            ]);
        });

        // crear los cargos de las firmas de los proyectos
        $fimasCargos = collect(config('nexo.firmas_cargos'));

        $fimasCargos->each(function ($firma) {
            $firmas = collect($firma);
            $firmas->each(function ($firma) {
                CargoFirma::firstOrCreate([
                    'descripcion' => $firma['descripcion'], // Cambiado a notación de arreglo
                    'tipo_cargo_firma_id' => TipoCargoFirma::where('nombre', $firma['cargo'])->first()->id,
                    'tipo_estado_id' => TipoEstado::where('nombre', $firma['estado'])->first()->id,
                    'estado_siguiente_id' => TipoEstado::where('nombre', $firma['estado_siguiente'])->first()->id,
                ]);
            });
        });

        $defaultActionId = DB::table('vinculacion_tipos_accion')
            ->orderBy('orden')
            ->orderBy('nombre')
            ->value('id');

        $defaultFlow = FlujoAprobacion::updateOrCreate(
            ['codigo' => 'PROYECTO_DEFAULT'],
            [
                'nombre' => 'Flujo de aprobacion de proyectos',
                'proceso' => 'PROYECTO',
                'descripcion' => 'Flujo configurable por defecto para proyectos.',
                'activo' => true,
                'tipo_accion_id' => $defaultActionId,
            ]
        );

        $defaultFlow->etapas()->delete();

        $defaultStages = collect(config('nexo.firmas_cargos.revisores_documento_proyecto') ?? []);
        $defaultStages->each(function (array $stage, int $index) use ($defaultFlow) {
            $cargo = CargoFirma::join('tipo_cargo_firma', 'tipo_cargo_firma.id', '=', 'cargo_firma.tipo_cargo_firma_id')
                ->where('tipo_cargo_firma.nombre', $stage['cargo'])
                ->where('cargo_firma.descripcion', 'Proyecto')
                ->select('cargo_firma.*')
                ->first();

            if (! $cargo) {
                return;
            }

            $code = Str::of($stage['cargo'])->upper()->replaceMatches('/[^A-Z0-9]+/', '_')->trim('_')->value();

            $defaultFlow->etapas()->create([
                'orden' => $index + 1,
                'codigo' => $code,
                'nombre' => $stage['cargo'],
                'cargo_firma_id' => $cargo->id,
                'activo' => true,
            ]);
        });

        collect([
            'Transdisciplinar',
            'Interdisciplinar',
            'Multidisciplinar',
            'Unidisciplinar',
        ])->each(fn (string $nombre) => Modalidad::firstOrCreate(['nombre' => $nombre]));

        // crear las categorias para el proyecto
        //  Categorías de proyectos de vinculación
        // Educación No Formal y/o Continua ______
        // APS
        // Desarrollo Regional
        // Desarrollo local
        // Investigación-acción-participación
        // Asesoría técnico-científica
        // Artísticos-culturales
        // Otras áreas

        collect([
            'Desarrollo Regional',
            'Desarrollo Local',
        ])->each(fn (string $nombre) => Categoria::firstOrCreate(['nombre' => $nombre]));

        /*
            ODS en el que se enmarca el proyecto: Utilizar el documento Agenda 20/45 y objetivos de desarrollo sostenible.
        */

        collect([
            '1. Fin de la pobreza',
            '2. Hambre cero',
            '3. Salud y bienestar',
            '4. Educación de calidad',
            '5. Igualdad de género',
            '6. Agua limpia y saneamiento',
            '7. Energía asequible y no contaminante',
            '8. Trabajo decente y crecimiento económico',
            '9. Industria, innovación e infraestructura',
            '10. Reducción de las desigualdades',
            '11. Ciudades y comunidades sostenibles',
            '12. Producción y consumo responsables',
            '13. Acción por el clima',
            '14. Vida submarina',
            '15. Vida de ecosistemas terrestres',
            '16. Paz, justicia e instituciones sólidas',
            '17. Alianzas para lograr los objetivos',
        ])->each(fn (string $nombre) => Od::firstOrCreate(['nombre' => $nombre]));

        collect([
            ['nombre' => 'Inscripcion', 'descripcion' => 'Se emite cuando un proyecto alcanza el estado en curso'],
            ['nombre' => 'Finalizacion', 'descripcion' => 'Se emite cuando un proyecto alcanza el estado Finalizado'],
            ['nombre' => 'Actualizacion', 'descripcion' => 'Se emite cuando hay cambios en el proyecto'],
            ['nombre' => 'Dictamen', 'descripcion' => 'Se emite el dictamen del proyecto'],
        ])->each(fn (array $tipo) => TipoConstancia::updateOrCreate(
            ['nombre' => $tipo['nombre']],
            ['descripcion' => $tipo['descripcion']],
        ));
    }
}
