<?php

namespace Database\Seeders\Proyecto;

use App\Models\Proyecto\CargoFirma;
use App\Models\Proyecto\FlujoAprobacion;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;

class FormDvus001FlujoSeeder extends Seeder
{
    public function run(): void
    {
        $flujo = DB::transaction(function (): FlujoAprobacion {
            // También reconocer los flujos creados desde la pantalla con otro código.
            $existente = FlujoAprobacion::where('proceso', 'PROYECTO')
                ->where('codigo_formulario', 'FORM-DVUS-001')->orderBy('id')->first();
            if ($existente) {
                return $existente;
            }

            $tipoAccion = DB::table('vinculacion_tipos_accion')
                ->where('codigo', 'DESARROLLO_LOCAL_REGIONAL')->first();
            if (! $tipoAccion) {
                throw new \RuntimeException('Ejecute VinculacionTiposAccionSeeder antes de FormDvus001FlujoSeeder.');
            }

            $flujo = FlujoAprobacion::create([
                'codigo' => 'PROYECTO_FORM_DVUS_001',
                'nombre' => 'Flujo FORM-DVUS-001 - Desarrollo local y regional',
                'proceso' => 'PROYECTO',
                'codigo_formulario' => 'FORM-DVUS-001',
                'tipo_accion_id' => $tipoAccion->id,
                'activo' => true,
                'descripcion' => 'Configure los responsables de cada etapa antes de utilizar el flujo.',
            ]);

            // Configuración real verificada: las dos revisiones DVUS también atienden informes.
            // El cargo de ambas revisiones es Revisor Vinculacion; el rol distingue el acceso.
            $etapas = [
                ['JEFE_DEPARTAMENTO', 'Jefe Departamento', 'Jefe Departamento', 'Jefe Departamento', 'APROBACION', false],
                ['ENLACE_VINCULACION', 'Enlace Vinculacion', 'Enlace Vinculacion', 'Enlace Vinculacion', 'APROBACION', false],
                ['DIRECTOR_CENTRO', 'Director Centro', 'Director centro', 'Director centro', 'APROBACION', false],
                ['REVISION_DVUS', 'Revisor Vinculacion', 'Revisor Vinculacion', 'Revisor Vinculacion', 'REVISION', true],
                ['DIRECCION_DVUS', 'Director Vinculacion', 'Revisor Vinculacion', 'Director Vinculacion', 'REVISION', true],
            ];

            foreach ($etapas as $indice => [$codigo, $nombre, $cargoNombre, $rolNombre, $tipo, $informes]) {
                $cargo = CargoFirma::where('descripcion', 'Proyecto')
                    ->whereHas('tipoCargoFirma', fn ($q) => $q->where('nombre', $cargoNombre))
                    ->orderBy('id')->first();
                $rol = Role::where('guard_name', 'web')->where('name', $rolNombre)->first();
                if (! $cargo || ! $rol) {
                    throw new \RuntimeException("Falta el cargo {$cargoNombre} o el rol {$rolNombre}. Ejecute primero los seeders de catálogos y roles.");
                }

                $flujo->etapas()->create([
                    'codigo' => $codigo,
                    'orden' => $indice + 1,
                    'nombre' => $nombre,
                    'tipo_etapa' => $tipo,
                    'cargo_firma_id' => $cargo->id,
                    'rol_revisor_id' => $rol->id,
                    'usuario_responsable_id' => null,
                    'requiere_asignacion' => true,
                    'emisor_define_destinatario' => false,
                    'aplica_inscripcion' => true,
                    'aplica_informe_intermedio' => $informes,
                    'aplica_cierre_proyecto' => $informes,
                    'activo' => true,
                ]);
            }

            return $flujo;
        });

        $pendientes = $flujo->etapas()->pendientesDeResponsable()->pluck('nombre');
        if ($pendientes->isNotEmpty()) {
            $this->command?->warn('Configure los responsables de FORM-DVUS-001 en Configuración → Flujos: '.$pendientes->implode(', ').'.');
        } else {
            $this->command?->info('Se conserva la configuración existente de FORM-DVUS-001.');
        }
    }
}
