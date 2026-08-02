<?php

namespace App\Services\Constancias;

use App\Jobs\GenerarPdfConstanciaRegistroProyecto;
use App\Models\Constancias\ConstanciaCorrelativo;
use App\Models\Constancias\ConstanciaRegistroProyecto;
use App\Models\Proyecto\Proyecto;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;

class EmitirConstanciaRegistroProyecto
{
    public function __construct(
        private readonly NumeroConstanciaRegistro $numeros,
        private readonly AutoridadEmisoraConstanciaRegistroResolver $autoridad,
    ) {}

    public function emitir(Proyecto $proyecto, ?int $emitidaPor = null): ConstanciaRegistroProyecto
    {
        return DB::transaction(function () use ($proyecto, $emitidaPor): ConstanciaRegistroProyecto {
            $proyecto = Proyecto::query()->lockForUpdate()->findOrFail($proyecto->id);

            $existente = ConstanciaRegistroProyecto::query()
                ->where('proyecto_id', $proyecto->id)
                ->lockForUpdate()
                ->first();

            if ($existente && ! in_array($existente->estado, [ConstanciaRegistroProyecto::ESTADO_PENDIENTE, ConstanciaRegistroProyecto::ESTADO_ERROR], true)) {
                return $existente;
            }

            $this->validarInscripcion($proyecto);

            if ($existente) {
                $constancia = $existente;
            } else {
                $fecha = now();
                $anio = (int) $fecha->year;
                $correlativo = $this->reservarCorrelativo($anio);
                $numero = $this->numeros->format($correlativo, $anio);
                $token = bin2hex(random_bytes(32));
                $codigo = strtoupper(Str::random(10));

                $constancia = ConstanciaRegistroProyecto::create([
                    'proyecto_id' => $proyecto->id,
                    'numero' => $numero,
                    'anio' => $anio,
                    'correlativo' => $correlativo,
                    'codigo_validacion' => $codigo,
                    'token_hash' => hash('sha256', $token),
                    'token_cifrado' => Crypt::encryptString($token),
                    'snapshot' => $this->snapshot($proyecto, $numero, $codigo, $fecha),
                    'fecha_emision' => $fecha,
                    'emitida_por' => $emitidaPor,
                    'estado' => ConstanciaRegistroProyecto::ESTADO_PENDIENTE,
                ]);
            }

            DB::afterCommit(fn () => GenerarPdfConstanciaRegistroProyecto::dispatch($constancia->id)->afterCommit());

            return $constancia;
        });
    }

    private function reservarCorrelativo(int $anio): int
    {
        $registro = ConstanciaCorrelativo::query()
            ->where('tipo', NumeroConstanciaRegistro::TIPO)
            ->where('anio', $anio)
            ->where('unidad_emisora', NumeroConstanciaRegistro::UNIDAD_EMISORA)
            ->lockForUpdate()
            ->first();

        if (! $registro) {
            try {
                $registro = ConstanciaCorrelativo::create([
                    'tipo' => NumeroConstanciaRegistro::TIPO,
                    'anio' => $anio,
                    'unidad_emisora' => NumeroConstanciaRegistro::UNIDAD_EMISORA,
                    'ultimo_correlativo' => 0,
                ]);
            } catch (QueryException) {
                $registro = ConstanciaCorrelativo::query()
                    ->where('tipo', NumeroConstanciaRegistro::TIPO)
                    ->where('anio', $anio)
                    ->where('unidad_emisora', NumeroConstanciaRegistro::UNIDAD_EMISORA)
                    ->lockForUpdate()
                    ->firstOrFail();
            }
            $registro = ConstanciaCorrelativo::query()->whereKey($registro->id)->lockForUpdate()->firstOrFail();
        }

        $registro->increment('ultimo_correlativo');

        return (int) $registro->fresh()->ultimo_correlativo;
    }

    private function validarInscripcion(Proyecto $proyecto): void
    {
        $firmas = $proyecto->firma_proyecto()
            ->where('firmable_type', Proyecto::class)
            ->whereNotNull('flujo_aprobacion_id')
            ->whereNotNull('revision_ciclo')
            ->get();

        if ($firmas->isEmpty()) {
            throw new RuntimeException('El proyecto no tiene firmas de inscripción registradas.');
        }

        $ciclo = (int) $firmas->max('revision_ciclo');
        $flujoId = (int) $firmas->firstWhere('revision_ciclo', $ciclo)?->flujo_aprobacion_id;

        if ($ciclo < 1 || ! $flujoId) {
            throw new RuntimeException('El proyecto no tiene un ciclo de inscripción válido.');
        }

        if (! $proyecto->firmasDeEtapasCompletadas($flujoId, $ciclo, null)) {
            throw new RuntimeException('El flujo de inscripción no ha completado todas las etapas de aprobación.');
        }
    }

    private function snapshot(Proyecto $proyecto, string $numero, string $codigo, $fecha): array
    {
        $proyecto->loadMissing([
            'coordinador_proyecto.empleado.departamento_academico',
            'coordinador_proyecto.empleado.categoria',
            'coordinador_proyecto.empleado.centro_facultad',
            'facultades_centros',
            'firma_proyecto',
        ]);

        $coordinador = $proyecto->coordinador;
        $unidad = $proyecto->facultades_centros->first()?->nombre
            ?: $coordinador?->centro_facultad?->nombre
            ?: 'No registrado';

        $periodoAcademico = $this->resolverPeriodoAcademico($proyecto);

        $autoridad = $this->autoridad->resolver($proyecto);

        return [
            'constancia' => [
                'numero' => $numero,
                'codigo_validacion' => $codigo,
                'fecha_emision' => $fecha->toIso8601String(),
                'ciudad_emision' => 'Ciudad Universitaria José Trinidad Reyes',
                'periodo_academico' => $periodoAcademico,
            ],
            'proyecto' => [
                'codigo' => $proyecto->codigo_proyecto ?: 'No registrado',
                'nombre' => $proyecto->nombre_proyecto ?: 'No registrado',
                'unidad_academica' => $unidad,
                'centro_regional' => $proyecto->facultades_centros->first()?->nombre ?: 'No registrado',
                'fecha_inicio' => $proyecto->fecha_inicio?->format('d/m/Y') ?: 'No registrado',
                'fecha_fin' => $proyecto->fecha_finalizacion?->format('d/m/Y') ?: 'No registrado',
                'estado_aprobado' => 'Inscrito',
            ],
            'coordinador' => [
                'nombre' => $coordinador?->nombre_completo ?: 'No registrado',
                'numero_empleado' => $coordinador?->numero_empleado ?: 'No registrado',
                'departamento' => $coordinador?->departamento_academico?->nombre ?: 'No registrado',
                'categoria' => $coordinador?->categoria?->nombre ?: 'No registrado',
                'unidad_academica' => $coordinador?->centro_facultad?->nombre ?: 'No registrado',
            ],
            'autoridad' => $autoridad,
        ];
    }

    private function resolverPeriodoAcademico(Proyecto $proyecto): string
    {
        $estudianteProyecto = \App\Models\Estudiante\EstudianteProyecto::query()
            ->where('proyecto_id', $proyecto->id)
            ->whereNotNull('periodo_academico_id')
            ->value('periodo_academico_id');

        if ($estudianteProyecto) {
            return $estudianteProyecto;
        }

        $anio = (int) now()->year;

        return "I PAC {$anio}";
    }
}
